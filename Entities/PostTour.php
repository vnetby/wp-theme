<?php

namespace Vnet\Entities;

use Vnet\Cache as VnetCache;
use Vnet\Constants\Cache;
use Vnet\Constants\PostTypes;
use Vnet\Constants\Taxonomies;
use Vnet\Helpers\Date;
use Vnet\Theme\Comments;
use Vnet\Theme\Image;
use WP_Query;

class PostTour extends Post
{
    protected static $postType = PostTypes::TOURS;


    /**
     * - Получает активные катгории
     * @return array<string,TermTourCat> ключ = ID категории
     */
    static function getActiveCats()
    {
        return VnetCache::fetch(Cache::TOUR_ACTIVE_CATS, function () {
            $visibleTours = self::getAllVisible();
            $res = [];
            foreach ($visibleTours as $tour) {
                $cats = $tour->getCategories();
                foreach ($cats as $cat) {
                    $res[$cat->getId()] = $cat;
                }
            }
            usort($res, function (TermTourCat $a, TermTourCat $b) {
                if ($a->getOrder() > $b->getOrder()) {
                    return 1;
                }
                if ($a->getOrder() < $b->getOrder()) {
                    return -1;
                }
                return 0;
            });
            return $res;
        });
    }

    /**
     * - Получает все не скрытые туры (устанавливается в админке)
     * @return self[]
     */
    static function getAllVisible(): array
    {
        $args = [
            'meta_key' => 'first_trip_date',
            'orderby'  => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'hide_archive_tour',
                    'value' => '1',
                    'compare' => '!='
                ]
            ]
        ];
        return self::getPublished($args);
    }

    /**
     * @param int[] $cities ID городов выездов
     * @param int[] $cats ID категорий
     * @return self[]
     */
    static function filter(array $cities = [], array $cats = [])
    {
        $tours = PostTour::getAllVisible();
        if ($cities) {
            foreach ($tours as &$tour) {
                if (!$tour) {
                    continue;
                }
                $activeCities = array_keys($tour->getTripsDepartCities());
                if (!array_intersect($activeCities, $cities)) {
                    $tour = null;
                }
            }
        }
        if ($cats) {
            foreach ($tours as &$tour) {
                if (!$tour) {
                    continue;
                }
                $activeCats = array_keys($tour->getCategories());
                if (!array_intersect($activeCats, $cats)) {
                    $tour = null;
                }
            }
        }
        return array_values(array_filter($tours));
    }

    /**
     * - Получает активные выезды по туру
     * @return PostTrip[]
     */
    function getActiveTrips(): array
    {
        return $this->fetchCache(Cache::TOUR_ACTIVE_TRIPS, function () {
            $trips = PostTrip::getActive();
            $res = [];

            foreach ($trips as $trip) {
                if ($trip->getTourId() === $this->getId()) {
                    $res[] = $trip;
                }
            }

            return $res;
        });
    }


    /**
     * - Поулчает все выезды привязанные к данному туру
     * @return PostTrip[]
     */
    function getAllTrips(): array
    {
        return $this->fetchCache(Cache::TOUR_ALL_TRIPS, function () {
            $trips = PostTrip::getAll();
            $res = [];

            foreach ($trips as $trip) {
                if ($trip->getTourId() === $this->getId()) {
                    $res[] = $trip;
                }
            }

            return $res;
        });
    }


    /**
     * - Проверяет есть ли активные выезды по туру
     * @return bool 
     */
    function hasActiveTrips(): bool
    {
        return $this->fetchCache(Cache::TOUR_HAS_TRIPS, function () {
            return !!PostTrip::getActive(1, $this->getId());
        });
    }


    /**
     * - Получает туры в которых нет выездов
     * @return self[]
     */
    static function getWithoutTrips(): array
    {
        global $wpdb;

        $tablePosts = $wpdb->posts;

        $typeTours = self::$postType;
        $activeTrips = PostTrip::getActive();
        $activeIds = [];

        foreach ($activeTrips as $trip) {
            $activeIds[] = $trip->getTourId();
        }

        $sqlActiveIds = implode(',', array_unique($activeIds));

        $allIds = array_column(
            $wpdb->get_results("SELECT `ID` FROM `{$tablePosts}` WHERE `post_status` = 'publish' AND `post_type` = '{$typeTours}' AND `ID` NOT IN ({$sqlActiveIds})", ARRAY_A),
            'ID'
        );

        $res = [];

        foreach ($allIds as $tourId) {
            $res[] = self::getById((int)$tourId);
        }

        return $res;
    }


    /**
     * - Получает туры в которых есть активные выезды
     * @return self[]
     */
    static function getWithTrips(): array
    {
        global $wpdb;

        $tablePosts = $wpdb->posts;

        $typeTours = self::$postType;
        $activeTrips = PostTrip::getActive();
        $activeIds = [];

        foreach ($activeTrips as $trip) {
            $activeIds[] = $trip->getTourId();
        }

        $sqlActiveIds = implode(',', array_unique($activeIds));

        $allIds = array_column(
            $wpdb->get_results("SELECT `ID` FROM `{$tablePosts}` WHERE `post_status` = 'publish' AND `post_type` = '{$typeTours}' AND `ID` IN ({$sqlActiveIds})", ARRAY_A),
            'ID'
        );

        $res = [];

        foreach ($allIds as $tourId) {
            $res[] = self::getById((int)$tourId);
        }

        return $res;
    }


    function getRating(): float
    {
        return Comments::getPostRating($this->getId());
    }


    function getReviewsCount(): int
    {
        return Comments::countPostComments($this->getId());
    }


    /**
     * - Получает фактические дни тура
     * @return int 
     */
    function getTourDays(): int
    {
        if ($days = $this->getField('tour_days')) {
            return (int)$days;
        }
        return 0;
    }


    function getTourNights(): int
    {
        if ($nights = $this->getField('tour_nights')) {
            return (int)$nights;
        }
        return 0;
    }


    /**
     * - Получает массив дней программы
     * @return array 
     */
    function getProgram(): array
    {
        if ($program = $this->getField('program')) {
            return $program;
        }
        return [];
    }


    function getDocuments(): string
    {
        if ($documents = $this->getField('program_documents')) {
            return $documents;
        }
        return '';
    }


    function getSeoDesc(): string
    {
        if ($desc = $this->getField('porgram_seo_desc')) {
            return $desc;
        }
        return '';
    }


    function getSeoTitle(): string
    {
        if ($title = $this->getField('program_seo_title')) {
            return $title;
        }
        return '';
    }


    /**
     * - Получает города отправления
     * @return TermCity[] 
     */
    function getDepartCities(): array
    {
        if ($ids = $this->getField('tour_depart_cities')) {
            $res = [];
            foreach ($ids as $id) {
                $res[] = TermCity::getById((int)$id);
            }
            return $res;
        }
        return [];
    }


    /**
     * - Получает уникальные города выездов прикрепленные к активным выездам
     * @return array<string,TermCity> ключ = ID города
     */
    function getTripsDepartCities(): array
    {
        return $this->fetchCache(Cache::TOUR_TRIPS_CITIES, function () {
            $trips = $this->getActiveTrips();
            $res = [];
            foreach ($trips as $trip) {
                $cities = $trip->getDepartCities();
                foreach ($cities as $city) {
                    $res[$city->getId()] = $city;
                }
            }
            return $res;
        });
    }


    /**
     * - Получает категории тура
     * @return array<string,TermTourCat> ключ = ID категории
     */
    function getCategories(): array
    {
        return $this->fetchCache(Cache::TOUR_CATEGORIES, function () {
            /**
             * @var \WP_Term[] $terms
             */
            $terms = wp_get_post_terms($this->getId(), Taxonomies::TOUR_CATS);
            if (!$terms || is_wp_error($terms)) {
                return [];
            }
            $res = [];
            foreach ($terms as $term) {
                $res[$term->term_id] = TermTourCat::getByTerm($term);
            }
            return $res;
        });
    }


    function getDepartCitiesStr(): string
    {
        if ($cities = $this->getDepartCities()) {
            $str = '';
            foreach ($cities as $city) {
                if (!$str) {
                    $str = $city->getName();
                } else {
                    $str .= ', ' . $city->getName();
                }
            }
            return $str;
        }
        return '';
    }


    /**
     * - Получает места сбора
     * @return TermPlace[] 
     */
    function getDepartPlaces(): array
    {
        $cities = $this->getDepartCities();

        if (!$cities) {
            return [];
        }

        $res = [];

        foreach ($cities as $city) {
            $places = $city->getPlaces();
            foreach ($places as $place) {
                if (!isset($res[$place->getId()])) {
                    $res[$place->getId()] = $place;
                }
            }
        }

        return array_values($res);
    }


    /**
     * - Получает последний выезд по туру
     * @return null|PostTrip 
     */
    function getLastTrip(): ?PostTrip
    {
        return $this->fetchCache(Cache::TOUR_LAST_TRIP, function () {
            return $this->getActiveTrips()[0] ?? null;
            // global $wpdb;

            // $tableMeta = $wpdb->postmeta;

            // $query = "SELECT `post_id` FROM `{$tableMeta}` 
            // WHERE 
            // `meta_key` = 'trip_info_date' 
            // AND 
            // `post_id` IN (
            //     SELECT DISTINCT(`post_id`) FROM `{$tableMeta}` WHERE `meta_key` = 'trip_info_tour' AND `meta_value` = {$this->getId()}
            // ) 
            // ORDER BY `meta_value` ASC LIMIT 1";

            // $resTrips = $wpdb->get_results($query, ARRAY_A);

            // if (is_wp_error($resTrips) || !$resTrips) {
            //     return null;
            // }

            // return PostTrip::getById((int)$resTrips[0]['post_id']);
        });
    }


    function getGallery(): array
    {
        return $this->fetchCache(Cache::TOUR_GALLERY, function () {
            $imgIds = $this->getField('program_gallery');

            if (!$imgIds) {
                return [];
            }

            $imagePosts = (new WP_Query(['post__in' => $imgIds, 'post_status' => 'inherit', 'post_type' => 'attachment', 'posts_per_page' => -1, 'orderby' => 'post__in']))->posts;

            $res = [];

            foreach ($imagePosts as $imagePost) {
                $imgId = $imagePost->ID;
                $res[] = [
                    'id' => $imgId,
                    'title' => $imagePost->post_title,
                    'caption' => $imagePost->post_excerpt,
                    'thumb' => Image::optimize(wp_get_attachment_image_url($imgId, 'full'), 100, 100), // 100x100
                    'medium' => Image::optimize(wp_get_attachment_image_url($imgId, 'full'), 650, 520), // 650x520
                    'large' => wp_get_attachment_image_url($imgId, 'large'),
                    'full' => wp_get_attachment_image_url($imgId, 'full')
                ];
            }

            return $res;
        });
    }


    function getContent(): string
    {
        if ($content = $this->getField('program_description')) {
            return apply_filters('the_content', $content);
        }
        return '';
    }


    function getPaymentInfo(): string
    {
        if ($info = $this->getField('program_payment')) {
            return $info;
        }
        return '';
    }


    function isHidden(): bool
    {
        return !!$this->getField('hide_archive_tour');
    }


    function isOldVersion(): bool
    {
        return !!$this->getField('tour_old_version');
    }


    /**
     * - Обновляет дату ближайшего выезда выезда
     * @param string $currentDate если передать - данная дата будет участвовать в сортировке
     */
    function updateFirstDateTrip(string $currentDate = '')
    {
        $trips = $this->getActiveTrips();
        $dates = [];

        if ($currentDate) {
            $dates[] = Date::format('Ymd', $currentDate);
        }

        foreach ($trips as $trip) {
            if ($date = $trip->getStartDate('Ymd')) {
                $dates[] = Date::format('Ymd', $date);
            }
        }

        sort($dates);

        // ближайшая дата должна быть всегда
        // чтобы работала корректно выборка с сортировкой
        $firstDate = $dates[0] ?? '99999999';

        $this->updateMeta('first_trip_date', $firstDate);
    }


    /**
     * - Привязывает категорию к туру
     */
    function addCategory(TermTourCat $cat)
    {
        wp_set_post_terms($this->getId(), [$cat->getId()], $cat->getTaxonomy(), true);
    }


    /**
     * - Удаляет категорию из тура
     */
    function removeCategory(TermTourCat $cat)
    {
        wp_remove_object_terms($this->getId(), [$cat->getId()], $cat->getTaxonomy());
    }
}
