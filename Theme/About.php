<?php

namespace Vnet\Theme;

use Vnet\Helpers\Acf;
use Vnet\Helpers\ArrayHelper;
use Vnet\Helpers\Html;

class About
{

    private static $about = null;


    static function setup()
    {
        if (!function_exists('acf_add_options_page')) {
            return;
        }

        acf_add_options_page([
            'page_title' => __('О компании', 'vnet'),
            'menu_title' => __('О компании', 'vnet'),
            'menu_slug' => 'about-company'
        ]);
    }

    /**
     * @return array
     */
    static function getDepartPoints()
    {
        $about = self::getAbout();
        return $about['depart'];
    }


    /**
     * @return array
     */
    static function getAdvantages()
    {
        $about = self::getAbout();
        return $about['advantages'];
    }


    /**
     * - получает мессенджеры
     * @return array
     */
    static function getMessengers()
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['contacts', 'messengers'], []);
    }


    /**
     * - Получает данные адреса
     * @return string
     */
    static function getAddress()
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['contacts', 'address'], '');
    }


    static function getMap(): string
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['contacts', 'map'], '');
    }


    static function getNip(): string
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['legal', 'nip'], '');
    }


    static function getRegon(): string
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['legal', 'regon'], '');
    }


    static function getTouristicNumber(): string
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['legal', 'touristic_number'], '');
    }


    static function getFullName(): string
    {
        return ArrayHelper::get(self::getAbout(), ['legal', 'full_name'], '');
    }


    static function getShortName(): string
    {
        return ArrayHelper::get(self::getAbout(), ['legal', 'short_name'], '');
    }



    /**
     * @return array
     */
    static function getAbout()
    {
        if (self::$about === null) {
            self::$about = self::fetchAbout();
        }
        return self::$about;
    }


    /**
     * @return array
     */
    static function getPhones()
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['contacts', 'phones'], []);
    }


    static function getMainPhone()
    {
        $phones = self::getPhones();
        if (!$phones) {
            return false;
        }
        return $phones[0];
    }


    static function getMainEmail()
    {
        $emails = self::getEmails();
        if (!$emails) {
            return false;
        }
        return $emails[0];
    }


    /**
     * @return array
     */
    static function getEmails()
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['contacts', 'emails'], []);
    }


    /**
     * @return array
     */
    static function getSocials()
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['contacts', 'socials'], []);
    }


    /**
     * @return array 
     */
    static function getChannels()
    {
        $about = self::getAbout();
        return ArrayHelper::get($about, ['contacts', 'channels'], []);
    }


    /**
     * @return array|false
     */
    static function getTgChannel()
    {
        $channels = self::getChannels();
        foreach ($channels as $channel) {
            if ($channel['channel'] === 'tg') {
                return $channel;
            }
        }
        return false;
    }


    static function getLogLight(): string
    {
        $about = self::getAbout();
        foreach ($about['logos'] as $logoData) {
            if (empty($logoData['enable'])) {
                continue;
            }
            if (empty($logoData['light'])) {
                return '';
            }
            $path = get_attached_file($logoData['light']);
            return file_get_contents($path);
        }
        return '';
    }

    static function getLogoDark(): string
    {
        $about = self::getAbout();
        foreach ($about['logos'] as $logoData) {
            if (empty($logoData['enable'])) {
                continue;
            }
            if (empty($logoData['dark'])) {
                return '';
            }
            $path = get_attached_file($logoData['dark']);
            return file_get_contents($path);
        }
        return '';
    }


    /**
     * @return array
     */
    private static function fetchAbout()
    {
        return [
            'contacts' => self::fetchAboutContacts(),
            'depart' => self::fetchAboutDepart(),
            'advantages' => self::fetchAboutAdvatages(),
            'legal' => self::fetchAboutLegal(),
            'logos' => self::fetchLogosAbout()
        ];
    }


    private static function fetchAboutContacts()
    {
        $field = Acf::getField('about_contacts', 'option');

        $res = [
            'messengers' => [],
            'socials' => [],
            'phones' => [],
            'emails' => [],
            'channels' => [],
            'address' => ArrayHelper::get($field, 'address'),
            'map' => ArrayHelper::get($field, 'map')
        ];

        if (!$field) {
            return $res;
        }

        foreach ($field as $key => $val) {
            if ($key === 'channels') {
                if (!$val) {
                    continue;
                }
                foreach ($val as $valItem) {
                    $res['channels'][] = [
                        'link' => $valItem['link'],
                        'name' => $valItem['name'],
                        'channel' => $valItem['channel'],
                        'title' => self::getMessengerTitle($valItem['channel']),
                        'icoDark' => self::getMessengerIco('dark', $valItem['channel']),
                        'icoLight' => self::getMessengerIco('light', $valItem['channel'])
                    ];
                }
                continue;
            }

            if ($key === 'messangers') {
                if (!$val) {
                    $res['messengers'] = [];
                    continue;
                }
                foreach ($val as &$item) {
                    $item = [
                        'link' => $item['link'],
                        'key' => $item['mess'],
                        'title' => self::getMessInfo($item['mess'], 'title'),
                        'ico' => self::getMessInfo($item['mess'], 'ico')
                    ];
                    $res['messengers'][] = $item;
                }
                continue;
            }

            if ($key === 'socials') {
                if (!$val) {
                    $res['socials'] = [];
                    continue;
                }
                foreach ($val as &$item) {
                    $item = [
                        'link' => $item['link'],
                        'key' => $item['network'],
                        'title' => self::getSocialInfo($item['network'], 'title'),
                        'ico' => self::getSocialInfo($item['network'], 'ico')
                    ];
                    $res['socials'][] = $item;
                }
                continue;
            }

            if ($key === 'phones') {
                if (!$val) {
                    $res['phones'] = [];
                }
                foreach ($val as &$item) {
                    $item = [
                        'link' => Html::urlPhone($item['phone']),
                        'label' => $item['phone']
                    ];
                    $res['phones'][] = $item;
                }
                continue;
            }

            if ($key === 'emails') {
                if (!$val) {
                    $res['emails'] = [];
                }
                foreach ($val as &$item) {
                    $item = [
                        'link' => Html::ulrEmail($item['email']),
                        'label' => $item['email']
                    ];
                    $res['emails'][] = $item;
                }
                continue;
            }
        }

        return $res;
    }


    private static function  getMessInfo($key, $info)
    {
        $icons = [
            'whatsapp' => [
                'ico' => 'whatsapp',
                'title' => 'Whatsapp'
            ],
            'tg' => [
                'ico' => 'telegram',
                'title' => 'Telegram'
            ],
            'viber' => [
                'ico' => 'viber',
                'title' => 'Viber'
            ]
        ];
        return $icons[$key][$info];
    }


    private static function getSocialInfo($key, $info)
    {
        $icons = [
            'fb' => [
                'ico' => [
                    'light' => 'fb-new',
                    'dark' => 'fb-new'
                ],
                'title' => 'Facebook'
            ],
            'inst' => [
                'ico' => [
                    'light' => 'inst-new',
                    'dark' => 'inst-new'
                ],
                'title' => 'Instagram'
            ],
            'twit' => [
                'ico' => [
                    'light' => 'twit',
                    'dark' => 'twit-dark'
                ],
                'title' => 'Twitter'
            ],
            'link' => [
                'ico' => [
                    'light' => 'link',
                    'dark' => 'link-dark'
                ],
                'title' => 'Linkedin'
            ]
        ];
        return $icons[$key][$info];
    }


    private static function getMessengerIco($key, $mess)
    {
        $icons = [
            'dark' => [
                'whatsapp' => 'whatsapp',
                'tg' => 'telegram',
                'viber' => 'viber'
            ],
            'light' => [
                'whatsapp' => 'whatsapp',
                'tg' => 'telegram',
                'viber' => 'viber'
            ]
        ];

        return ArrayHelper::get($icons, [$key, $mess], '');
    }


    private static function getMessengerTitle($mess)
    {
        $titles = [
            'whatsapp' => 'Whatsapp',
            'tg' => 'Telegram',
            'viber' => 'Viber'
        ];

        return ArrayHelper::get($titles, $mess, '');
    }


    private static function fetchAboutDepart()
    {
        $field = Acf::getField('about_departs', 'option');
        return $field ? $field : [];
    }

    private static function fetchAboutAdvatages()
    {
        $field = Acf::getField('advantages', 'option');
        return $field ? $field : [];
    }

    private static function fetchAboutLegal(): array
    {
        $field = Acf::getField('legal', 'option');
        return $field ? $field : [];
    }

    private static function fetchLogosAbout(): array
    {
        $field = Acf::getField('site_logos', 'option');
        return $field ? $field : [];
    }
}
