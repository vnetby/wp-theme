<?php

namespace Vnet\Types;

use Vnet\Constants\PostTypes;
use Vnet\Constants\QueryVars;
use Vnet\Entities\PostTour;
use Vnet\Entities\PostTrip;
use Vnet\Entities\TermPlace;
use Vnet\Entities\TermTourCat;
use Vnet\Helpers\Acf;
use Vnet\Helpers\Date;
use Vnet\Helpers\Path;
use Vnet\Theme\Template;

class TypeTrips extends PostType
{

    protected $slug = PostTypes::TRIPS;


    function setup()
    {
        $this->acfToPostContentFiltered('trip_program', 'trip_route', 'trip_payment', 'trip_crm_id');

        $this->menuColorOrange();
        $this->menuAwesomeIco('f073');

        $this->autoTitle(function (array $data) {
            return $this->createAdminTitle($data);
        });

        $this->addColumn('start_date', 'Выезд', function (int $postId) {
            return $this->renderAdminStartDate($postId);
        });

        $this->addColumn('early_price', 'Р. брон.', function (int $postId) {
            return $this->renderAdminEarlyPriceDate($postId);
        });

        $this->addColumn('trip_image', 'Картинка', function (int $postId) {
            $trip = PostTrip::getById($postId);
            if ($img = $trip->getImage()) {
                echo '<img src="' . $img . '" style="width: 100px; height: auto;">';
            }
        }, 'title');

        $this->removeColumn('date');

        $this->addAdminFilter(
            function () {
                $this->renderToursSelect();
            },
            function (\WP_Query $query) {
                if (!empty($_GET['tour'])) {
                    $query->set('meta_query', [
                        'relation' => 'OR',
                        [
                            'key' => 'trip_info_tour',
                            'value' => esc_sql($_GET['tour'])
                        ]
                    ]);
                }
            }
        );

        $this->removeAdminFilter('filter-by-date');

        $this->sortableColumn('start_date', function (\WP_Query $query) {
            $query->set('meta_key', 'trip_info_date');
            $query->set('orderby', 'meta_value');
        });

        $this->sortableColumn('early_price', function (\WP_Query $query) {
            $query->set('meta_key', 'trip_info_date_finish_early');
            $query->set('orderby', 'meta_value');
        });

        $this->defaultSort(function (\WP_Query $query) {
            $query->set('meta_key', 'trip_info_date');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');
        });

        $this->addMetabox(
            'trip-mailer',
            'Рассылка',
            function () {
                Template::theTemplate('admin/mailer-metabox');
            },
            null,
            'side'
        );

        $this->perPage(-1);

        // $this->filterPermalink(function (string $permalink, \WP_Post $post, bool $leavename) {
        //     $trip = PostTrip::getByPost($post);
        //     $tour = $trip->getTour();
        //     if (!$tour) {
        //         return '';
        //     }
        //     return Path::join($tour->getPermalink(), $trip->getId(), '/');
        // });

        $this->filterTitle(function (string $title, int $postId) {
            $trip = PostTrip::getById($postId);
            if ($tour = $trip->getTour()) {
                return $tour->getTitle();
            }
            return $title;
        });

        $this->filterExcerpt(function (string $exerpt, \WP_Post $post) {
            $trip = PostTrip::getByPost($post);
            if ($tour = $trip->getTour()) {
                return get_the_excerpt($tour->getPost());
            }
            return $exerpt;
        });

        // $this->onAcfSave(function (int $postId) {
        //     $trip = PostTrip::getById($postId);
        //     if (!$trip->getProgram() && $trip->getTourId()) {
        //         $this->updateProgramFromTour($trip);
        //     }
        // });

        $this->rowClass(function (int $postId) {
            $trip = PostTrip::getById($postId);
            if (!$trip->isValid()) {
                return ['invalid-trip'];
            }
            return [];
        });

        $this->addRoute($this->getSlug() . '/([^/]+)/([a-z]+)/?$', ['name', QueryVars::TOUR_TAB]);

        // обновляет первую дату выезда в связанном туре
        // используется для сортировки туров на странице выезда
        $this->onAcfSave(function (int $id) {
            $trip = PostTrip::getById($id);

            if (!$trip) {
                return;
            }

            $tour = $trip->getTour();
            $tripDate = $trip->getStartDate();

            if (!$tour || !$tripDate) {
                return;
            }

            $tour->updateFirstDateTrip($tripDate);
        });

        // обновляет автокатегории тура
        $this->onAcfSave(function (int $id) {
            $trip = PostTrip::getById($id);

            if (!$trip) {
                return;
            }

            $tour = $trip->getTour();

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
            'label' => 'Выезды',
            // 'publicly_queryable' => false,
            'has_archive' => false,
            'exclude_from_search' => true,
            'supports' => ['title', 'thumbnail']
        ]);
    }


    // /**
    //  * - Записывает программу из тура элемента
    //  * @param PostTrip $postId 
    //  * @return void 
    //  */
    // private function updateProgramFromTour(PostTrip $trip)
    // {
    //     $tour = PostTour::getById($trip->getTourId());

    //     if (!$tour) {
    //         return;
    //     }

    //     $tourProgram = $tour->getProgram();

    //     if (!$tourProgram) {
    //         return;
    //     }

    //     update_field('trip_program', $tourProgram, $trip->getId());
    // }


    /**
     * - Выводит быбор тура в фильтре
     */
    private function renderToursSelect()
    {
        $tours = PostTour::getPublished();
        $current = $_GET['tour'] ?? '';

        echo '<select name="tour">';
        echo '<option value="" ' . (!$current ? 'selected' : '') . '>Все туры</option>';
        foreach ($tours as $tour) {
            echo '<option value="' . $tour->getId() . '" ' . ($current == $tour->getId() ? 'selected' : '') . '>' . $tour->getTitle() . '</option>';
        }
        echo '</select>';
    }


    private function renderAdminPrices(int $postId): string
    {
        $trip = PostTrip::getById($postId);

        if (!$trip) {
            return '';
        }

        $str = 'Базовая цена: ' . $trip->getBasePrice();
        if ($trip->hasEarlyPrice()) {
            $str .= '<br>До ' . $trip->getEarlyFinish('d.m.Y') . ': ' . $trip->getEarlyPrice();
        }

        $route = $trip->getRoute();

        if (!$route) {
            return $str;
        }

        $str .= '<table style="border-collapse: collapse; margin-top: 3px;">';

        foreach ($route as $routeData) {
            if (empty($routeData['place'])) {
                continue;
            }

            $place = TermPlace::getById((int)$routeData['place']);

            if (!$place) {
                continue;
            }

            $prices = $trip->getPlacePrices((int)$routeData['place']);
            $arPrices = [];

            if ($prices) {
                if (isset($prices['early'])) {
                    $arPrices[] = $prices['early'];
                }
                $arPrices[] = $prices['base'];
            }

            $strPrice = '';

            if ($prices) {
                $strPrice = implode(' / ', $arPrices);
            }

            $str .= '<tr>';
            $str .= "<td style='padding: 0px 3px;'>{$place->getName()}</td><td style='padding: 0px 3px;'>{$strPrice}</td>";
            $str .= '</tr>';
        }

        $str .= '</table>';

        return $str;
    }


    private function renderAdminStartDate(int $postId): string
    {
        $trip = PostTrip::getById($postId);
        return Date::format('d.m.Y', $trip->getStartDate());
    }


    private function renderAdminEarlyPriceDate(int $postId): string
    {
        $trip = PostTrip::getById($postId);
        return Date::format('d.m.Y', $trip->getEarlyFinish());
    }


    /**
     * - Автоматическое формирование заголовка
     * @param array $data
     */
    private function createAdminTitle(array $data)
    {
        $title = $data['post_title'];

        $date = Acf::getPostFieldValue(['trip_info', 'date']);
        $tourId = Acf::getPostFieldValue(['trip_info', 'tour']);

        if (!$date || !$tourId) {
            return $title;
        }

        return implode(' - ', array_filter([date('d.m.Y', strtotime($date)), get_the_title($tourId)]));
    }
}
