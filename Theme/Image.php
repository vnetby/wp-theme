<?php

/**
 * - Класс для работы с картинками
 * - Логика работы любой формы оптимизации:
 *   - поступает запрос с броузера
 *   - идет проверка на наличие файла нужной оптимизаии, файла блокировки и файла ошибки
 *   - если есть файл оптимизации - дальше ничего не происходит (возвращается относительный путь к файлу)
 *   - если есть файл ошибки или блокировки - дальше ничего не происходит (возвращается переданный файл)
 *   - если файла нет:
 *      - создается файл блокировки
 *      - запускается в фоновом режиме соответствующий процесс
 *      - в процессе устанавливается максимальное время выполнения (избегаются зависания)
 *      - процесс запускает нужный метод оптимизации
 *      - в случае ошибки, метод создает файл ошибки (.error), таким образом дает понять что оптимизация данного файла невозможна, и оптимизация больше запускатья не будет
 *      - в случае успеха, создается оптимизированный файл и удаляется файл блокировки
 */

namespace Vnet\Theme;

use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Spatie\Async\Pool;
use Vnet\Helpers\Path;
use WebPConvert\Convert\Converters\Stack;

class Image
{


    /**
     * - На сколько процентов увеличть размер миниатюр
     *   в целях сохранения лучшего качества
     * @var  
     */
    private static $incresePercent = 5;

    /**
     * - Качество при оптимизации
     * @var int
     */
    private static $qualityPercent = 100;

    /**
     * - Без закрывающего и открывающего слеша
     * @var string
     */
    private static $optimizePath = 'wp-content/uploads/optimize';


    static function optimize(string $src, int $width, int $height): string
    {
        $path = Path::urlToPath($src, $width, $height);

        $resizePath = self::getResizeFile($path, $width, $height);
        $webpPath = self::getWebpFile($resizePath);

        // все есть
        if (file_exists($webpPath)) {
            return Path::pathToUrl($webpPath);
        }

        // уже есть миниматюра
        // надо сделать webp
        if (file_exists($resizePath)) {
            // уже запущен процесс webp
            if (self::isWebpLocked($resizePath) || !self::canConvertWebp($resizePath)) {
                return Path::pathToUrl($resizePath);
            }
            self::lockWebp($resizePath);
            self::startWebpProcess($resizePath);
            return Path::pathToUrl($resizePath);
        }

        // миниатюры и webp нет

        // уже запущен процесс
        if (self::isResizeLocked($path, $width, $height) || self::isWebpLocked($resizePath) || !self::canResize($path, $width, $height)) {
            return $src;
        }

        // запускаем процесс
        // при этом блокируем любую оптимизацию
        self::lockResize($path, $width, $height);
        self::lockWebp($resizePath);
        self::startResizeProcess($path, $width, $height, true);

        return $src;
    }


    static function resize(string $src, int $width, int $height): string
    {
        $path = Path::urlToPath($src);

        if (self::isResizeLocked($path, $width, $height) || !self::canResize($path)) {
            return $src;
        }

        $resizePath = self::getResizeFile($path, $width, $height);

        if (file_exists($resizePath)) {
            return Path::pathToUrl($resizePath);
        }

        self::lockResize($path, $width, $height);
        self::startResizeProcess($path, $width, $height);

        return $src;
    }


    static function webp(string $src): string
    {
        $path = Path::urlToPath($src);

        if (self::isWebpLocked($path) || !self::canConvertWebp($path)) {
            return $src;
        }

        $webpPath = self::getWebpFile($path);

        if (file_exists($webpPath)) {
            return Path::pathToUrl($webpPath);
        }

        self::lockWebp($path);
        self::startWebpProcess($path);

        return $src;
    }


    static function getWebpFile(string $path): string
    {
        $info = pathinfo($path);

        $dirname = $info['dirname'];
        $ext = $info['extension'];
        $filename = $info['filename'];

        return self::getOptimizeFilePath($dirname, $filename . '.' . $ext . '.webp');

        // return Path::join($dirname, $filename . '.' . $ext . '.webp');
    }


    static function isWebpLocked(string $path): bool
    {
        return self::isFileLocked(self::getWebpFile($path));
    }

    static function lockWebp(string $path)
    {
        self::lockFile(self::getWebpFile($path));
    }

    static function unlockWebp(string $path)
    {
        self::unlockFile(self::getWebpFile($path));
    }

    static function errorWebp(string $path, string $message = '')
    {
        self::errorFile(self::getWebpFile($path), $message);
    }

    private static function canConvertWebp($path): bool
    {
        if (!file_exists($path)) {
            return false;
        }
        $ext = strtolower(pathinfo($path)['extension'] ?? '');
        return in_array($ext, ['png', 'jpg', 'jpeg']);
    }

    private static function startWebpProcess(string $path)
    {
        $script = Path::join(THEME_PATH, 'scripts/image-webp.php');
        $command = "php {$script} {$path} > /dev/null 2> /dev/null &";
        exec($command);
    }

    static function realWebpImage(string $path): string
    {
        if (!self::canConvertWebp($path)) {
            return $path;
        }
        try {
            $webpPath = self::getWebpFile($path);
            $options = [
                'png' => [
                    'encoding' => 'auto',
                    'near-lossless' => 80,
                    'quality' => 90,
                    'sharp-yuv' => true,
                ],
                'jpeg' => [
                    'encoding' => 'auto',
                    'quality' => 'auto',
                    'max-quality' => 90,
                    'default-quality' => 80,
                    'sharp-yuv' => true,
                ]
            ];
            Stack::convert($path, $webpPath, $options);
            return $webpPath;
        } catch (\Exception $e) {
            self::errorWebp($path, $e->getMessage());
            return $path;
        }
    }


    static function getResizeFile(string $filePath, int $width, int $height): string
    {
        $info = pathinfo($filePath);

        $dirname = $info['dirname'];
        $ext = $info['extension'];
        $filename = $info['filename'];

        return self::getOptimizeFilePath($dirname, $filename . '-' . $width . 'x' . $height . '.' . $ext);

        // return Path::join($dirname, $filename . '-resize-' . $width . 'x' . $height . '.' . $ext);
    }


    /**
     * - Запускает в фоновом режиме процесс
     * @param string $path абсолютный путь к файлу картинки 
     * @param int $width 
     * @param int $height 
     * @param bool $webp конвертировать миниатюру в webp
     * @return void
     */
    private static function startResizeProcess(string $path, int $width, int $height, $webp = false)
    {
        $script = Path::join(THEME_PATH, 'scripts/image-resize.php');
        $command = "php {$script} {$path} {$width} {$height}";
        if ($webp) {
            $command .= ' 1';
        }
        $command .= " > /dev/null 2> /dev/null &";
        exec($command);
    }


    /**
     * - Выполняет ресайц картинки
     * @param string $path 
     * @param int $width 
     * @param int $height 
     * @return string полный путь к файлу 
     */
    static function realResizeImage(string $path, int $width, int $height): string
    {
        if (!self::canResize($path)) {
            return $path;
        }
        try {
            $image = new ImageResize($path);

            $image->quality_jpg = self::$qualityPercent;
            $image->quality_png = self::$qualityPercent;
            $image->quality_webp = self::$qualityPercent;

            $resizeFile = self::getResizeFile($path, $width, $height);

            $image->crop(($width + (self::$incresePercent * $width / 100)), ($height + (self::$incresePercent * $height / 100)), false, ImageResize::CROPCENTRE);

            $image->save($resizeFile);

            return $resizeFile;
        } catch (ImageResizeException $e) {
            self::errorResize($path, $width, $height, $e->getMessage());
            return $path;
        }
    }

    private static function canResize(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }
        $ext = strtolower(pathinfo($path)['extension'] ?? '');
        return in_array($ext, ['png', 'jpg', 'jpeg', 'webp']);
    }

    static function isResizeLocked(string $path, int $width, int $height): bool
    {
        return self::isFileLocked(self::getResizeFile($path, $width, $height));
    }

    static function lockResize(string $path, int $width, int $height)
    {
        self::lockFile(self::getResizeFile($path, $width, $height));
    }

    static function unlockResize(string $path, int $width, int $height)
    {
        self::unlockFile(self::getResizeFile($path, $width, $height));
    }

    static function errorResize(string $path, int $width, int $height, string $message = '')
    {
        self::errorFile(self::getResizeFile($path, $width, $height), $message);
    }


    private static function isFileLocked(string $file): bool
    {
        return (file_exists(self::errorFileName($file)) || file_exists(self::lockFileName($file)));
    }

    private static function lockFile(string $file)
    {
        file_put_contents(self::lockFileName($file), getmygid() . ' ' . date('Y-m-d H:i:s'));
    }

    private static function errorFile(string $file, string $message = '')
    {
        file_put_contents(self::errorFileName($file), $message);
    }

    private static function unlockFile(string $file)
    {
        @unlink(self::lockFileName($file));
    }

    private static function lockFileName(string $file): string
    {
        return $file . '.lock';
    }

    private static function errorFileName(string $file): string
    {
        return $file . '.error';
    }

    private static function getOptimizeFilePath(string $dirname, $fileName): string
    {
        $dirUrl = Path::pathToUrl($dirname);
        $reg = "^/" . self::$optimizePath;
        $dirUrl = preg_replace("%{$reg}%", '', $dirUrl);
        $fullPath = Path::join(ABSPATH, self::$optimizePath, $dirUrl);
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }
        return Path::join($fullPath, $fileName);
    }
}
