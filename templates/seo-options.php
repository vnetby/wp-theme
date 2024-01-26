<?php

/**
 * @var \WP_Post_Type[] $postTypes
 */

use Vnetby\Wptheme\Container;
use Vnetby\Wptheme\Front\Template;

$seo = Container::getSeo();

if (!empty($_REQUEST['update_options'])) {
    $seo->options->saveOptionsFromRequest();
}

$postTypes = Container::getClassSeo()::getPostTypesWithArchives();
$activeTab = $_REQUEST['tab'];

?>
<style>
    .tab-item:not(.active) {
        display: none;
    }

    .tabs-nav {
        display: flex;
        margin-bottom: 30px;
        border-bottom: 1px solid rgba(0, 0, 0, .2);
    }

    .tab-btn {
        cursor: pointer;
        padding: 10px 20px;
        font-weight: 500;
        font-size: 14px;
        user-select: none;
        border: 1px solid transparent;
        border-bottom: none;
    }

    .tab-btn.active {
        background-color: rgba(0, 0, 0, .1);
        border-color: rgba(0, 0, 0, .2);
    }
</style>
<div class="wrap">
    <h1 class="wp-heading-inline">
        <?= __('Настройки СЕО сайта', 'vnet'); ?>
    </h1>
    <hr class="wp-header-end">
    <?php
    if (!empty($_REQUEST['update_options'])) {
    ?>
        <div id="message" class="notice notice-success">
            <p>
                <?= __('Данные успешно обновлены', 'vnet'); ?>
            </p>
        </div>
    <?php
    }
    ?>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="update_options" value="1">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="postbox-container-1" class="postbox-container">
                    <div id="submitdiv" class="postbox">
                        <div class="postbox-header">
                            <h2 class="hndle">Сохранить</h2>
                        </div>
                        <div class="inside">
                            <div class="submitbox" id="submitpost">
                                <div id="major-publishing-actions">
                                    <div id="publishing-action">
                                        <span class="spinner"></span>
                                        <input name="original_publish" type="hidden" id="original_publish" value="Обновить">
                                        <input type="submit" name="save" id="publish" class="button button-primary button-large" value="Обновить">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-2" class="postbox-container">
                    <div class="tabs-wrap js-tabs">
                        <div class="tabs-nav">
                            <div class="tab-btn js-tab-btn<?= !$activeTab || $activeTab === 'common' ? ' active' : ''; ?>" data-tab="common">
                                <?= __('Общие', 'vnet'); ?>
                            </div>
                            <?php
                            foreach ($postTypes as $postType) {
                            ?>
                                <div class="tab-btn js-tab-btn<?= $postType->name === $activeTab ? ' active' : ''; ?>" data-tab="<?= $postType->name; ?>">
                                    <?= $postType->labels->name; ?>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                        <div class="tab-items-wrap">
                            <div class="tab-item js-tab-item<?= !$activeTab || $activeTab === 'common' ? ' active' : ''; ?>" data-tab="common">
                                <?php
                                Template::theFile(Container::getLoader()->libPath('templates/seo-metabox.php'), [
                                    'image' => $seo->options->getCommonImageId(),
                                    'name_image' => 'vnet-seo-common-image'
                                ]);
                                ?>
                            </div>
                            <?php
                            foreach ($postTypes as $postType) {
                            ?>
                                <div class="tab-item js-tab-item<?= $postType->name === $activeTab ? ' active' : ''; ?>" data-tab="<?= $postType->name; ?>">
                                    <p style="font-weight: 500;">
                                        <?= sprintf(__('Данные настройки будут применяться к странице архива: %s.', 'vnet'), $postType->labels->name); ?>
                                    </p>
                                    <?php
                                    Template::theFile(Container::getLoader()->libPath('templates/seo-metabox.php'), [
                                        'title' => $seo->options->getArchiveTitle($postType->name),
                                        'desc' => $seo->options->getArchiveDesc($postType->name),
                                        'image' => $seo->options->getArchiveImageId($postType->name),
                                        'name_title' => 'vnet-seo-post-' . $postType->name . '-title',
                                        'name_desc' => 'vnet-seo-post-' . $postType->name . '-desc',
                                        'name_image' => 'vnet-seo-post-' . $postType->name . '-image'
                                    ]);
                                    ?>
                                </div>
                            <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div><!-- /post-body -->
            <br class="clear">
        </div><!-- /poststuff -->
    </form>
</div>

<script>
    (function() {
        let $wrap = jQuery('.js-tabs');
        $wrap.find('.js-tab-btn').on('click', function(e) {
            e.preventDefault();
            let btn = e.currentTarget;
            $wrap.find('.js-tab-btn').removeClass('active');
            $wrap.find('.js-tab-item').removeClass('active');
            btn.classList.add('active');
            $wrap.find('.js-tab-item[data-tab="' + btn.dataset.tab + '"]').addClass('active');
            let search = window.location.search;
            search = search.replace(/[\?|\&]tab=[^\&]+/, '');
            if (search) {
                search += '&';
            } else {
                search += '?';
            }
            search += 'tab=' + btn.dataset.tab;
            window.history.replaceState(null, '', window.location.pathname + search);
        });
    })();
</script>