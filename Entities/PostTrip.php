<?php

namespace Vnet\Entities;

use Vnet\Cache;
use Vnet\Constants\Cache as ConstantsCache;
use Vnet\Constants\Meta;
use Vnet\Constants\PostTypes;
use Vnet\Constants\QueryVars;
use Vnet\Helpers\ArrayHelper;
use Vnet\Helpers\Date;

class PostTrip extends Post
{

    protected static $postType = PostTypes::TRIPS;


    /**
     * - Получает уникальные города мест сбора которые есть в активных турах
     * @return TermCity[]
     */
    static function getActiveCities(): array
    {
        return Cache::fetch(ConstantsCache::ACTIVE_CITIES, function () {
            $trips = self::getActive();

            $cities = [];

            foreach ($trips as $trip) {
                $tripCities = $trip->getDepartCities();
                foreach ($tripCities as $city) {
                    if (isset($cities[$city->getId()])) {
                        continue;
                    }
                    $cities[$city->getId()] = $city;
                }
            }

            return array_values($cities);
        });
    }


    /**
     * - Получает активные выезды
     * @return self[]
     */
    static function getActive(int $perPage = -1, int $tourId = 0): array
    {
        return Cache::fetch(ConstantsCache::ACTIVE_TRIPS . $perPage . $tourId, function () use ($perPage, $tourId) {
            $args = [
                'posts_per_page' => $perPage,
                'meta_query' => [
                    [
                        'key' => 'trip_info_date',
                        'value' => (int)date('Ymd'),
                        'compare' => '>'
                    ]
                ]
            ];

            if ($tourId) {
                $args['meta_query']['relation'] = 'AND';
                $args['meta_query'][] = [
                    'key' => 'trip_info_tour',
                    'value' => $tourId,
                    'compare' => '='
                ];
            }

            return self::getPublished($args);
        });
    }


    static function getPublished(?array $queryArgs = null): array
    {
        $defArgs = ['orderby' => 'meta_value_num', 'order' => 'ASC', 'meta_key' => 'trip_info_date'];
        if ($queryArgs) {
            $defArgs = array_merge($defArgs, $queryArgs);
        }
        return parent::getPublished($defArgs);
    }


    static function getCurrent(): ?self
    {
        return parent::getCurrent();
        // $tripId = get_query_var(QueryVars::TRIP_ID);
        // return $tripId ? self::getById((int)$tripId) : null;
    }


    /**
     * - Получает города отправления
     * @return TermCity[] 
     */
    function getDepartCities(): array
    {
        return $this->fetchCache(ConstantsCache::DEPART_CITIES, function () {
            $route = $this->getRoute();

            if (!$route) {
                return [];
            }

            $cities = [];

            foreach ($route as $routeData) {
                if (empty($routeData['place'])) {
                    continue;
                }

                $place = TermPlace::getById((int)$routeData['place']);

                if (!$place) {
                    continue;
                }

                if (isset($cities[$place->getCityId()])) {
                    continue;
                }

                $cities[$place->getId()] = $place->getCity();
            }

            return array_values($cities);
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
     * - Проверяет действительный ли выезд
     * @return bool 
     */
    function isValid(): bool
    {
        $date = $this->getStartDate();
        return Date::toTime($date) > time();
    }


    /**
     * - Получает дату последней рассылки
     * @return string 
     */
    function getLastEmailDate(): string
    {
        if ($date = $this->getMeta(Meta::TRIP_LAST_MAIL_DATE, true)) {
            return $date;
        }
        return '';
    }

    function updateEmailLastDate()
    {
        $this->updateMeta(Meta::TRIP_LAST_MAIL_DATE, date('Y-m-d'));
    }

    function getMailerId(): string
    {
        return $this->getMeta(Meta::TRIP_MAILER_ID, true);
    }

    function setMailerId(string $id)
    {
        $this->updateMeta(Meta::TRIP_MAILER_ID, $id);
    }

    function deleteMailerId()
    {
        $this->deleteMeta(Meta::TRIP_MAILER_ID);
    }

    function getTour(): ?PostTour
    {
        $tour = $this->fetchCache(ConstantsCache::TRIP_TOUR, function () {
            $tourId = $this->getTourId();

            if (!$tourId) {
                return false;
            }

            $tour = PostTour::getById($tourId);

            return $tour ? $tour : false;
        });

        return $tour ? $tour : null;
    }


    function getTourId(): int
    {
        return (int)$this->getInfo('tour', 0);
    }

    /**
     * - Получает базовую стоимость с учетом раннего бронировани
     * @return float 
     */
    function getPrice(): float
    {
        if ($this->isValidEarlyPrice()) {
            return $this->getEarlyPrice();
        }

        return $this->getBasePrice();
    }

    /**
     * - Проверяет действует ли еще раннее бронирование
     * @return bool 
     */
    function isValidEarlyPrice(): bool
    {
        if (!$this->hasEarlyPrice()) {
            return false;
        }
        return (Date::toTime($this->getEarlyFinish()) >= strtotime(date('Y-m-d')));
    }

    /**
     * - Получает базовую стоимость без учето раннего бронирования
     * @return float
     */
    function getBasePrice(): float
    {
        return (float)$this->getInfo('price', 0);
    }

    /**
     * - Получает базовую стоимость раннего бронирования
     * @return float 
     */
    function getEarlyPrice(): float
    {
        return (float)$this->getInfo('price_early', 0);
    }


    function getEarlyFinish($format = null): string
    {
        $date = $this->getInfo('date_finish_early', '');
        if (!$date || !$format) {
            return $date;
        }
        return date($format, strtotime($date));
    }


    function hasEarlyPrice(): bool
    {
        return (!!$this->getEarlyFinish() && !!$this->getEarlyPrice());
    }


    function getImage($size = 'post-thumbnail'): string
    {
        if ($img = $this->getThumbnailUrl($size)) {
            return $img;
        }
        return get_the_post_thumbnail_url($this->getTourId(), $size);
    }


    /**
     * - Получает дату первого дня тура
     * @return string Y-m-d
     */
    function getStartDate(): string
    {
        return (string)$this->getInfo('date');
    }


    /**
     * - Получает дату последнего дня тура
     * @return string Y-m-d
     */
    function getEndDate(): string
    {
        $startDate = $this->getStartDate();
        $days = $this->getAllDays();
        return Date::addDays($startDate, $days - 1);
    }


    /**
     * - Получает все дни тура с учетом дороги
     * @return int 
     */
    function getAllDays(): int
    {
        $program = $this->getProgram();
        return count($program);
    }


    /**
     * - Получает строкой весь период тура
     * @return string 
     */
    function getFullDate(): string
    {
        $start = Date::ruShortDate($this->getStartDate());
        $end = Date::ruShortDate($this->getEndDate());
        return $start . ' - ' . $end;
    }


    /**
     * - Получает фактические дни тура
     * @return int 
     */
    function getTourDays(): int
    {
        return $this->getTour()->getTourDays();
    }


    /**
     * - Получает кол-во ночей в туре
     * @return int
     */
    function getTourNights(): int
    {
        return $this->getTour()->getTourNights();
    }


    /**
     * - Получает программу тура
     * @return array 
     */
    function getProgram(): array
    {
        // if ($this->getField('trip_use_tour_program')) {
        //     $tour = $this->getTour();
        //     return $tour->getProgram();
        // }
        if ($program = $this->getField('trip_program')) {
            return $program;
        }
        return [];
    }


    /**
     * - Получает информацию по оплатам
     * - Если не установлена в выезде - вернет информацию из тура
     * @return string
     */
    function getPaymentInfo(): string
    {
        $info = $this->getField('trip_payment');
        if ($info) {
            return $info;
        }
        $tour = $this->getTour();
        if (!$tour) {
            return '';
        }
        return $tour->getPaymentInfo();
    }


    /**
     * - Получает сумму тур услуги
     * @return float
     */
    function getTax(): float
    {
        return (float)$this->getInfo('tax', 0);
    }


    function getPrepayment(): float
    {
        return (float)$this->getInfo('prepayment', 0);
    }


    function getPrepaymentCurrency(): string
    {
        return (string)$this->getInfo('prepayment_currency', '');
    }


    function getHotelPrice(): float
    {
        return (float)$this->getInfo('hotel_price', '');
    }


    function getHotelPriceCurrency(): string
    {
        return (string)$this->getInfo('hotel_price_currency', '');
    }


    /**
     * - Получает валюту тур услуги
     * @return string
     */
    function getTaxCurrency(): string
    {
        return $this->getInfo('tax_currency', '');
    }


    function getRoute(): array
    {
        return $this->fetchCache(ConstantsCache::TRIP_ROUTE, function () {
            $route = $this->getField('trip_route');
            return $route ? $route : [];
        });
    }


    /**
     * - Получает дополнительную стоимость в зависимости от города посадки
     * @return float 
     */
    function getCityAddPrice(int $cityId): float
    {
        $route = $this->getRoute();

        foreach ($route as $routeData) {
            if (empty($routeData['place'])) {
                continue;
            }

            $place = TermPlace::getById((int)$routeData['place']);

            if (!$place) {
                continue;
            }

            if ($place->getCityId() === $cityId) {
                return (float)$routeData['price'];
                break;
            }
        }

        return 0;
    }


    /**
     * - Получает места сбора
     * @return TermPlace[] 
     */
    function getPlaces(): array
    {
        $route = $this->getRoute();

        $res = [];

        foreach ($route as $routeData) {
            if (empty($routeData['place'])) {
                continue;
            }
            $res[] = TermPlace::getById((int)$routeData['place']);
        }

        return $res;
    }


    function getRouteCitiesStr(): string
    {
        $places = array_column($this->getRoute(), 'place');
        $cities = [];
        foreach ($places as $placeId) {
            $place = TermPlace::getById((int)$placeId);
            $city = $place->getCity();
            if (!isset($cities[$city->getId()])) {
                $cities[$city->getId()] = $city->getName();
            }
        }
        return implode(', ', $cities);
    }


    function getPlacePrices(int $placeId): ?array
    {
        $route = $this->getRoute();

        if (!$route) {
            return null;
        }

        foreach ($route as $routeData) {
            if (empty($routeData['price'])) {
                continue;
            }

            if ($routeData['place'] != $placeId) {
                continue;
            }

            $res = [
                'base' => $this->getBasePrice() + (float)$routeData['price'],
            ];

            if (!$this->hasEarlyPrice()) {
                return $res;
            }

            $res['early'] = $this->getEarlyPrice() + (float)$routeData['price'];

            return $res;
        }

        return null;
    }


    function getCrmTripId(): int
    {
        return (int)$this->getField('trip_crm_id');
    }

    private function getInfo($key = null, $def = null)
    {
        $info = $this->fetchCache(ConstantsCache::TRIP_INFO, function () {
            $info = $this->getField('trip_info');
            return $info ? $info : [];
        });
        return $key ? ArrayHelper::get($info, $key, $def) : $info;
    }
}
