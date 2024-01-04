<?php

namespace Vnet\Entities;

use Vnet\Constants\PostTypes;
use Vnet\Constants\Taxonomies;
use Vnet\Helpers\Acf;
use Vnet\Helpers\Date;

class TermTourCat extends Term
{
    protected static $tax = Taxonomies::TOUR_CATS;


    /**
     * - Получает категории для вывода на фронте
     * @return self[]
     */
    static function getOnFront(): array
    {
        $terms = get_terms([
            'taxonomy' => self::$tax,
            'hide_empty' => true
        ]);
        $res = [];
        foreach ($terms as $term) {
            $cat = self::getByTerm($term);
            if (!$cat->showOnFront()) {
                continue;
            }
            $res[] = $cat;
        }
        return $res;
    }

    function getArchiveContent(): ?array
    {
        if ($data = $this->getField('archive_tour_content')) {
            return $data;
        }
        return null;
    }


    function showOnFront(): bool
    {
        return !!$this->getField('cat_show_main');
    }


    function autoAssign()
    {
        if (!$this->isAutoCat()) {
            return;
        }

        $page = 1;
        $perPage = 20;

        while ($tours = PostTour::getAll($page, $perPage)) {
            $page++;
            foreach ($tours as $tour) {
                if (!$this->tourMatchAutoSets($tour)) {
                    $tour->removeCategory($this);
                } else {
                    $tour->addCategory($this);
                }
            }
        }
    }


    /**
     *  - Проверяет соответствует ли тур условию авто категории
     */
    function tourMatchAutoSets(PostTour $tour): bool
    {
        if (!$this->isAutoCat()) {
            return false;
        }

        $sets = $this->getAutoCatSettings();

        if (empty($sets['city']) && empty($sets['date_from']) && empty($sets['date_to']) && empty($sets['days_from']) && empty($sets['days_to'])) {
            return false;
        }

        if (!empty($sets['city'])) {
            if (!array_intersect($sets['city'], array_keys($tour->getTripsDepartCities()))) {
                return false;
            }
        }

        if (!empty($sets['date_from'])) {
            $trips = $tour->getActiveTrips();
            $matchDate = false;
            foreach ($trips as $trip) {
                $start = $trip->getStartDate();
                if (!$start) {
                    continue;
                }
                if (Date::toTime($start) >= Date::toTime($sets['date_from'])) {
                    $matchDate = true;
                    break;
                }
            }
            if (!$matchDate) {
                return false;
            }
        }

        if (!empty($sets['date_to'])) {
            $trips = $tour->getActiveTrips();
            $matchDate = false;
            foreach ($trips as $trip) {
                $start = $trip->getStartDate();
                if (!$start) {
                    continue;
                }
                if (Date::toTime($start) <= Date::toTime($sets['date_to'])) {
                    $matchDate = true;
                    break;
                }
            }
            if (!$matchDate) {
                return false;
            }
        }

        if (!empty($sets['days_from'])) {
            $days = $tour->getTourDays();
            if ($days && $days < (int)$sets['days_from']) {
                return false;
            }
        }

        if (!empty($sets['days_to'])) {
            $days = $tour->getTourDays();
            if ($days && $days > (int)$sets['days_to']) {
                return false;
            }
        }

        return true;
    }


    function isAutoCat(): bool
    {
        if ($data = $this->getAutoCatSettings()) {
            return !!$data['auto'];
        }
        return false;
    }


    function getAutoCatSettings(): ?array
    {
        if ($data = $this->getField('auto_tour_cat')) {
            return $data;
        }
        return null;
    }
}
