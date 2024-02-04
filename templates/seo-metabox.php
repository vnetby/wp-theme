<?php

/**
 * @var \Vnetby\Wptheme\Front\Template $this
 */

use Vnetby\Wptheme\Container;

/**
 * @var \WP_Post $post
 */
$post = $this->getArg('post');

$title = $this->getArg('title', '');
$desc = $this->getArg('desc', '');
$imgId = (int)$this->getArg('image', 0);

$nameTitle = $this->getArg('name_title');
$nameDesc = $this->getArg('name_desc');
$nameImg = $this->getArg('name_image');

$seo = Container::getSeo();

if ($nameTitle) {
?>
    <div style="margin-bottom: 10px;">
        <label for="<?= $nameTitle; ?>" style="display: block; margin-bottom: 3px; width: fit-content;">
            <?= __('Заголовок', 'vnet'); ?>
        </label>
        <input type="text" name="<?= $nameTitle; ?>" id="<?= $nameTitle; ?>" style="width: 100%;" value="<?= htmlspecialchars($title); ?>" maxlength="<?= $seo::LIMIT_TITLE; ?>">
        <p style="margin: 0px; margin-top: 3px;">
            <?= __('Маскимальное кол-во символов: ' . $seo::LIMIT_TITLE, 'vnet'); ?>
        </p>
    </div>
<?php
}

if ($nameDesc) {
?>
    <div style="margin-bottom: 10px;">
        <label for="<?= $nameDesc; ?>" style="display: block; margin-bottom: 3px; width: fit-content;">
            <?= __('Описание', 'vnet'); ?>
        </label>
        <textarea name="<?= $nameDesc; ?>" id="<?= $nameDesc; ?>" style="width: 100%; min-height: 150px;" maxlength="<?= $seo::LIMIT_DESC; ?>"><?= htmlspecialchars($desc); ?></textarea>
        <p style="margin: 0px; margin-top: 3px;">
            <?= __('Маскимальное кол-во символов: ' . $seo::LIMIT_DESC, 'vnet'); ?>
        </p>
    </div>
<?php
}

if ($nameImg) {
?>
    <div>
        <label for="<?= $nameImg; ?>" style="display: block; margin-bottom: 3px; width: fit-content;">
            <?= __('Изображение', 'vnet'); ?>
        </label>
        <?php
        if ($imgId) {
        ?>
            <img src="<?= wp_get_attachment_image_url($imgId, 'full'); ?>" class="js-<?= $nameImg; ?>" alt="image" style="width: 350px; height: 200px; object-fit: contain; margin-bottom: 5px;">
        <?php
        } else {
        ?>
            <img src="" class="js-<?= $nameImg; ?>" alt="image" style="width: 350px; height: 200px; object-fit: contain; margin-bottom: 5px; display: none;">
        <?php
        }
        ?>
        <input type="hidden" name="<?= $nameImg; ?>" value="<?= $imgId; ?>">
        <div style="display: flex; align-items: center;">
            <button type="button" class="js-<?= $nameImg; ?>-upload button" id="<?= $nameImg; ?>"><?= __('Загрузить', 'vnet'); ?></button>
            <button type="button" class="button js-<?= $nameImg; ?>-delete" style="margin-left: 10px;<?= !$imgId ? ' display: none;' : ''; ?>">Удалить</button>
        </div>
        <p style="margin: 0px; margin-top: 3px;">
            <?= __('Рекомендуемй размер: ' . $seo::LIMIT_IMG_WIDTH . 'x' . $seo::LIMIT_IMG_HEIGHT, 'vnet'); ?>
        </p>
    </div>
    <script>
        jQuery('.js-<?= $nameImg; ?>-upload').on('click', function(e) {
            e.preventDefault();
            let frame = wp.media({
                title: '<?= __('Выберите изображение', 'vnet'); ?>',
                library: {
                    type: 'image'
                },
                button: {
                    text: '<?= __('Загрузить', 'vnet'); ?>',
                    close: false
                },
                multiple: false
            });

            frame.on('select', function() {
                let image = frame.state().get('selection').first();
                let id = image.id;
                let url = image.attributes.link;
                jQuery('.js-<?= $nameImg; ?>').attr('src', url).css('display', 'block');
                jQuery('[name="<?= $nameImg; ?>"]').val(id);
                jQuery('.js-<?= $nameImg; ?>-delete').css('display', 'inline-block');
                frame.close();
            });

            frame.open();
        });

        jQuery('.js-<?= $nameImg; ?>-delete').on('click', function(e) {
            e.preventDefault();
            jQuery('.js-<?= $nameImg; ?>').css('display', 'none');
            jQuery('.js-<?= $nameImg; ?>-delete').css('display', 'none');
            jQuery('[name="<?= $nameImg; ?>"]').val(0);
        });
    </script>
<?php
}
?>