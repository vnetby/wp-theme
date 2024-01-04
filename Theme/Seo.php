<?php

namespace Vnet\Theme;

use Vnet\Constants\PostTypes;
use Vnet\Constants\QueryVars;
use Vnet\Entities\PostBlog;
use Vnet\Entities\PostTour;
use Vnet\Entities\PostTrip;
use Vnet\Entities\TermCity;
use Vnet\Entities\TermTourCat;
use Vnet\Helpers\Acf;
use Vnet\Helpers\Path;
use Vnet\Helpers\Str;
use Vnet\Router;
use WP_Query;

class Seo
{

    static function setup()
    {
        add_filter('pre_get_document_title', function (string $title) {
            if ($pageTitle = self::getPageTitle()) {
                return $pageTitle;
            }
            return $title;
        });

        add_action('wp_head', function () {
            if ($pageDesc = self::getPageDesc()) {
                echo '<meta name="description" content="' . $pageDesc . '">';
            }
            self::renderOgMeta();
            self::generateJsonLd();
        });

        add_filter('get_canonical_url', function ($url) {
            if ($canonical = self::getPageCanonical()) {
                return $canonical;
            }
            return $url;
        });
    }


    private static function renderOgMeta()
    {
        $metaTags = [
            [
                'property' => 'og:title',
                'content' => self::getPageTitle()
            ],
            [
                'property' => 'og:description',
                'content' => self::getPageDesc()
            ],
            [
                'property' => 'og:image',
                'content' => self::getImage()
            ],
            [
                'property' => 'og:locale',
                'content' => get_locale()
            ],
            [
                'property' => 'og:type',
                'content' => 'website'
            ],
            [
                'property' => 'og:url',
                'content' => Router::getCurrentUrl()
            ],
            [
                'property' => 'og:site_name',
                'content' => get_bloginfo('description')
            ]
        ];

        foreach ($metaTags as $metData) {
            $str = '<meta property="' . $metData['property'] . '" content="' . $metData['content'] . '">' . "\r\n";
            echo $str;
        }
    }


    private static function getPageCanonical(): string
    {
        if (is_singular(PostTypes::TRIPS)) {
            $trip = PostTrip::getCurrent();
            if ($tour = $trip->getTour()) {
                return $tour->getPermalink();
            }
        }
        return '';
    }

    /**
     * - Генерирует JSON-lD разметку
     */
    private static function generateJsonLd()
    {
        $data = [
            self::getBreadcrumbsJsonLd()
        ];

        $pageData = self::getPageJsonLd();

        if ($pageData) {
            if (!empty($pageData['@context'])) {
                $data[] = $pageData;
            } else {
                $data = array_merge($data, $pageData);
            }
        }

        foreach ($data as $info) {
            self::renderJsonLd($info);
        }
    }


    /**
     * - Получает хлебные крошки JSON-LD текущей страницы
     */
    private static function getBreadcrumbsJsonLd(): array
    {
        $links = self::getBreadcrumbs();

        $res = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        foreach ($links as $i => $link) {
            $res['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => ($i + 1),
                'item' => [
                    '@id' => $link['url'],
                    'name' => $link['text']
                ]
            ];
        }

        return $res;
    }


    private static function renderJsonLd($data)
    {
        if (!$data) {
            return;
        }

        echo '<script type="application/ld+json">';
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo '</script>';
    }


    /**
     * - Получает массив хлебных крошек
     */
    static function getBreadcrumbs(): array
    {
        $breads = [
            [
                'url' => get_home_url(),
                'text' => __('Главная', 'vnet')
            ]
        ];

        if (is_front_page()) {
            return $breads;
        }

        if (is_archive()) {
            $postType = get_query_var('post_type');
            if ($postType) {
                $breads[] = [
                    'url' => get_post_type_archive_link($postType),
                    'text' => get_post_type_labels(get_post_type_object($postType))->name
                ];
            }
        }

        if (is_tax()) {
            $obj = get_queried_object();
            $breads[] = [
                'url' => Router::getCurrentUrl(),
                'text' => $obj->name
            ];
        }

        if (is_singular(PostTypes::BLOG)) {
            $breads[] = [
                'url' => get_post_type_archive_link(PostTypes::BLOG),
                'text' => __('Блог', 'vnet')
            ];
            $breads[] = [
                'url' => get_permalink(),
                'text' => get_the_title()
            ];
        }

        if (is_singular(PostTypes::TOURS)) {
            $breads[] = [
                'url' => get_post_type_archive_link(PostTypes::TOURS),
                'text' => __('Туры', 'vnet')
            ];
            $breads[] = [
                'url' => get_permalink(),
                'text' => get_the_title()
            ];
        }

        return $breads;
    }


    private static function getPageJsonLd(): ?array
    {
        if (is_front_page()) {
            return self::getFrontPageJson();
        }

        if (PostTour::isSingular()) {
            return self::getTourJson();
        }
        if (self::isContactsPage()) {
            return self::getContactsPageJson();
        }

        if (PostBlog::isSingular()) {
            return self::getBlogPostJson();
        }

        if (is_singular('page') || is_archive()) {
            return self::getPageJson();
        }

        return null;
    }


    static function isAboutPage()
    {
        return self::isPageTemplate('pages/page-about.php');
    }


    static function isContactsPage()
    {
        return self::isPageTemplate('pages/page-contacts.php');
    }


    static function isPageTemplate($template)
    {
        return (get_post_meta(get_the_ID(), '_wp_page_template', true) === $template);
    }


    private static function getFrontPageJson()
    {
        $title = self::getPageTitle();
        $desc = self::getPageDesc();

        $res = [];

        $res[] = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $desc,
            'image' => THEME_URI . 'img/company-image.jpg',
            'publisher' => self::getOrganizationJsonLd(false)
        ];

        if ($faq = self::getFaqJsonLd()) {
            $res[] = $faq;
        }

        return $res;
    }


    static function getPageTitle(): string
    {
        return htmlspecialchars((function () {
            if (is_404()) {
                return __('Страница не найдена', 'vnet');
            }
            if (PostTour::isSingular()) {
                return self::getTourTitle();
            }
            if (TermCity::isSingular()) {
                return self::getCityTitle();
            }
            if (TermTourCat::isSingular()) {
                return self::getCatTitle();
            }
            if (is_singular()) {
                $data = Acf::getField('page_seo');
                if (!empty($data['title'])) {
                    return $data['title'];
                }
                return $GLOBALS['post']->post_title;
            }
            if (PostTour::isArchive()) {
                return self::getTourArchiveTitle();
            }
            if (PostBlog::isArchive()) {
                $info = ArchiveOptions::getBlog();
                return $info['page_seo']['title'] ?? '';
            }
            return '';
        })());
    }


    static function getPageDesc(): string
    {
        return htmlspecialchars((function () {
            if (is_404()) {
                return __('Страница не найдена', 'vnet');
            }

            if (PostTour::isSingular()) {
                return self::getTourDesc();
            }

            if (is_singular()) {
                $info = Acf::getField('page_seo');
                if (!empty($info['desc'])) {
                    return $info['desc'];
                }
                return $GLOBALS['post']->post_excerpt;
            }

            if (PostTour::isArchive()) {
                return self::getTourArchiveDesc();
            }

            if (TermCity::isSingular()) {
                return self::getCityDesc();
            }

            if (TermTourCat::isSingular()) {
                return self::getCatDesc();
            }

            if (PostBlog::isArchive()) {
                $info = ArchiveOptions::getBlog();
                return $info['page_seo']['desc'] ?? '';
            }

            if (is_singular()) {
                return get_the_excerpt();
            }

            return '';
        })());
    }


    private static function getOrganizationJsonLd($context = true)
    {
        $res = [
            '@type' => 'Organization',
            'areaServed' => 'PL',
            'name' => About::getShortName(),
            'description' => get_bloginfo('description'),
            'logo' => self::getOrganizationLogo(),
            'url' => get_site_url(),
            'contactPoint' => self::getContactsJsonLd(),
            'aggregateRating' => self::getMainAggregateRating()
        ];

        if ($address = About::getAddress()) {
            $res['address'] = $address;
        }

        if ($context) {
            $res['@context'] = 'https://schema.org';
        }

        return $res;
    }


    static function getOrganizationLogo()
    {
        return THEME_URI . 'img/logo-png.png';
    }


    private static function getContactsJsonLd()
    {
        $res = [];
        $phones = About::getPhones();
        $emails = About::getEmails();

        foreach ($phones as $phone) {
            $res[] = [
                '@type' => 'ContactPoint',
                'telephone' => $phone['label']
            ];
        }

        foreach ($emails as $email) {
            $res[] = [
                '@type' => 'ContactPoint',
                'email' => $email['label']
            ];
        }

        return $res;
    }


    private static function getMainAggregateRating()
    {
        $res = [
            '@type' => 'AggregateRating',
            'ratingValue' => Comments::getSiteRating(),
            'reviewCount' => Comments::countPostComments()
        ];
        return $res;
    }


    /**
     * @return false|array
     */
    private static function getFaqJsonLd()
    {
        $query = new WP_Query([
            'post_type' => 'faq',
            'post_status' => 'publish',
            'posts_per_page' => -1
        ]);

        if (!$query->have_posts()) {
            return false;
        }

        $res = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => []
        ];

        foreach ($query->posts as $item) {
            $res['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $item->post_title,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($item->post_content)
                ]
            ];
        }

        return $res;
    }


    private static function getTourJson()
    {
        $img = self::getImage();

        $tour = PostTour::getCurrent();
        $lastTrip = $tour->getLastTrip();

        $res = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            // 'aggregateRating' => self::getTourRating(),
            'description' => $tour->getExcerpt(),
            'name' => $tour->getTitle(),
            'image' => $img,
            'brand' => self::getOrganizationJsonLd(false),
            'manufacturer' => self::getOrganizationJsonLd(false),
            'offers' => [
                '@type' => 'Offer',
                'availability' => 'https://schema.org/InStock',
                'price' => $lastTrip ? $lastTrip->getPrice() : '',
                'priceCurrency' => Price::getMainCurrency(true)
            ]
        ];

        if ($rating = Comments::getPostRating($tour->getId())) {
            $res['aggregateRating'] = $rating;
        }

        return $res;
    }


    private static function getPageJson($title = '', $desc = '', $img = '')
    {
        if (!$title) {
            $title = self::getPageTitle();
        }

        if (!$desc) {
            $desc = self::getPageDesc();
        }

        if (!$img) {
            $img = self::getImage();
        }

        $res = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'description' => $desc,
            'image' => $img,
            'publisher' => self::getOrganizationJsonLd(false)
        ];

        return $res;
    }


    private static function getTourTitle(): string
    {
        $tour = PostTour::getCurrent();
        return $tour->getSeoTitle();
    }


    private static function getCityTitle(): string
    {
        $city = TermCity::getCurrent();
        $data = $city->getArchiveContent();
        $title = __('Автобусные туры в Европу из Польши', 'vnet');
        if (!empty($data['main_title'])) {
            $title = $data['main_title'];
        }
        return $title;
    }


    private static function getCatTitle(): string
    {
        $cat = TermTourCat::getCurrent();
        $data = $cat->getArchiveContent();
        $title = __('Автобусные туры в Европу из Польши', 'vnet');
        if (!empty($data['main_title'])) {
            $title = $data['main_title'];
        }
        return $title;
    }


    private static function getTourArchiveTitle(): string
    {
        $info = ArchiveOptions::getTours();
        return $info['page_seo']['title'] ?? '';
    }


    static function getImage()
    {
        if (is_front_page()) {
            return THEME_URI . 'img/holly-travel-image-2.jpg';
        }

        if ($img = get_the_post_thumbnail_url(null, 'medium')) {
            return $img;
        }

        return THEME_URI . 'img/holly-travel-image-2.jpg';
    }


    private static function getTourRating(int $tour)
    {
        /**
         * @var \Vnet\Entity\Tour $entity
         */
        global $entity;

        $rating = $entity->getRating();

        if (!$rating) {
            return null;
        }

        $count = $entity->getReviewsCount();

        return [
            '@type' => 'AggregateRating',
            'ratingValue' => $rating,
            'reviewCount' => $count
        ];
    }


    private static function getTourDesc(): string
    {
        $tour = PostTour::getCurrent();
        $desc = $tour->getSeoDesc();
        return $desc;
    }


    private static function getTourArchiveDesc(): string
    {
        $info = ArchiveOptions::getTours();
        return $info['page_seo']['desc'] ?? '';
    }


    private static function getCityDesc(): string
    {
        $city = TermCity::getCurrent();
        $desc = $city->getArchiveContent()['description'] ?? '';
        if ($desc) {
            return $desc;
        }
        return self::getTourArchiveDesc();
    }


    private static function getCatDesc(): string
    {
        $city = TermTourCat::getCurrent();
        $desc = $city->getArchiveContent()['description'] ?? '';
        if ($desc) {
            return $desc;
        }
        return self::getTourArchiveDesc();
    }


    private static function getContactsPageJson()
    {
        $res = [
            '@context' => 'https://schema.org',
            '@type' => 'ContactPage',
            'lastReviewed' => get_the_date('Y-m-d'),
            'image' => self::getImage(),
            'reviewedBy' => self::getOrganizationJsonLd(false)
        ];

        return $res;
    }


    private static function getBlogPostJson()
    {
        global $post;

        $res = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'name' => $post->post_title,
            'description' => $post->post_excerpt,
            'image' => self::getImage(),
            'dateCreated' => get_the_date('', $post),
            'publisher' => self::getOrganizationJsonLd(false)
        ];

        return $res;
    }


    /**
     * - Генерирует карту сайта
     */
    static function generateSitemap()
    {
        $pages = self::getSitemapPosts('page');
        $blogs = self::getSitemapPosts(PostTypes::BLOG);
        $tours = self::getSitemapTours();

        $archiveBlog = [
            [
                'url' => get_post_type_archive_link(PostTypes::BLOG)
            ]
        ];

        $archiveTours = [
            [
                'url' => get_post_type_archive_link(PostTypes::TOURS)
            ]
        ];

        // $archiveCities = [
        //     [
        //         'url' => TermCity::getArchiveUrl()
        //     ]
        // ];

        // $archiveCats = [
        //     [
        //         'url' => TermTourCat::getArchiveUrl()
        //     ]
        // ];

        self::theSitemap($pages, $archiveBlog, $blogs, $archiveTours, $tours);
    }


    private static function theSitemap(...$args)
    {
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        foreach ($args as $urlSet) {
            foreach ($urlSet as $data) {
                echo '<url>';
                echo "<loc>{$data['url']}</loc>";
                if (!empty($data['modified'])) {
                    $date = date('Y-m-d', strtotime($data['modified']));
                    echo "<lastmod>{$date}</lastmod>";
                }
                echo '</url>';
            }
        }

        echo '</urlset>';
    }


    private static function getSitemapTours()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'posts';
        $postType = PostTypes::TOURS;

        $dbPosts = $wpdb->get_results("SELECT `ID` FROM `{$table}` WHERE `post_status` = 'publish' AND `post_type` = '{$postType}'", ARRAY_A);

        $res = [];

        foreach ($dbPosts as $postData) {
            $id = $postData['ID'];
            $res[] = [
                'url' => get_permalink($id),
                'modified' => get_the_date('', $id)
            ];
        }

        foreach (CITIES_SLUGS as $citySlug) {
            $city = TermCity::getBySlug($citySlug);
            if (!$city) {
                continue;
            }
            $res[] = [
                'url' => Path::join(get_post_type_archive_link($postType), $citySlug)
            ];
        }

        return $res;
    }


    private static function getSitemapPosts($postType)
    {
        global $wpdb;
        $table = $wpdb->posts;

        $postTypeSets = get_post_type_object($postType);

        if ($postTypeSets->exclude_from_search) {
            return [];
        }

        $resIds = $wpdb->get_results("SELECT `ID`, `post_modified`, `post_name` FROM `$table` WHERE `post_type` = '$postType' AND `post_status` = 'publish'", ARRAY_A);

        if (!$resIds || is_wp_error($resIds)) {
            return [];
        }

        $res = [];

        foreach ($resIds as $data) {
            if ($data['post_name'] !== 'inst') {
                continue;
            }
            $url = get_permalink($data['ID']);
            $res[] = [
                'url' => $url,
                'modified' => $data['post_modified']
            ];
        }

        return $res;
    }
}
