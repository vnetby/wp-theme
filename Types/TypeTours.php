<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;
use Vnet\Constants\QueryVars;
use Vnet\Constants\Taxonomies;
use Vnet\Entities\PostTour;
use Vnet\Entities\TermTourCat;
use Vnet\Helpers\Acf;
use Vnet\Helpers\Str;

class TypeTours extends PostType
{

    protected $slug = PostTypes::TOURS;

    function setup()
    {
        $this->acfToPostContentFiltered('program', 'program_documents', 'program_payment', 'program_gallery', 'program_seo_title', 'porgram_seo_desc');
        $this->acfToPostField('program_description', 'post_content');
        $this->acfToPostField('program_excerpt', 'post_excerpt');

        $this->menuColorGreen();
        $this->menuAwesomeIco('f207');

        $this->addImgColumn();
        $this->addColumn('tour_days', 'Дней в туре', function (int $postId) {
            return $this->getTourDays($postId);
        }, 'title');

        // $this->perPage(1);

        $this->rowClass(function (int $postId) {
            $tour = PostTour::getById($postId);
            if ($tour->isHidden()) {
                return ['hidden-tour'];
            }
            return [];
        });

        $this->filterArchiveMainQuery(function (\WP_Query $query) {
            $query->set('meta_key', 'first_trip_date');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'ASC');
        });

        $this->perPage(-1);

        $this->addRoute($this->getSlug() . '/([^/]+)/([a-z]+)/?$', ['name', QueryVars::TOUR_TAB]);

        // обновляет автокатегории тура
        $this->onAcfSave(function (int $id) {
            $tour = PostTour::getById($id);

            if (!$tour) {
                return;
            }

            $cats = TermTourCat::getAll();

            foreach ($cats as $cat) {
                if (!$cat->isAutoCat()) {
                    continue;
                }
                if ($cat->tourMatchAutoSets($tour)) {
                    $tour->addCategory($cat);
                } else {
                    $tour->removeCategory($cat);
                }
            }
        });

        $this->register([
            'label' => 'Туры',
            'has_archive' => true,
            'taxonomies' => [
                Taxonomies::TOUR_CATS
            ],
            'supports' => ['title', 'thumbnail']
        ]);
    }


    private function getTourDays(int $postId): string
    {
        $tour = PostTour::getById($postId);
        $days = $tour->getTourDays();
        $nights = $tour->getTourNights();
        if ($nights) {
            return $days . ' ' . Str::declenNum($days, ['день', 'дня', 'дней']) . ' / ' . $nights . ' ' . Str::declenNum($nights, ['ночь', 'ночи', 'ночей']);
        }
        return $days . ' ' . Str::declenNum($days, ['день', 'дня', 'дней']);
    }
}
