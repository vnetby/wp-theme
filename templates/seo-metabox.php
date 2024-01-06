<?php

/**
 * @var \Vnetby\Wptheme\Front\Template $this
 */

use Vnetby\Wptheme\Seo;

/**
 * @var \WP_Post $post
 */
$post = $this->getArg('post');

$postType = get_post_type_object($post->post_type);

// тип поста не имеет публичной части
// значит сео нет смысла выводить
if (!$postType->publicly_queryable) {
    return;
}

?>

<div style="margin-bottom: 10px;">
    <label for="vnet-seo-title" style="display: block; margin-bottom: 3px; width: fit-content;">
        <?= __('Заголовок', 'vnet'); ?>
    </label>
    <input type="text" name="vnet-seo-title" id="vnet-seo-title" style="width: 100%;" value="<?= Seo::getPostTitle($post->ID); ?>">
    <p style="margin: 0px; margin-top: 3px;">
        <?= __('Маскимальное кол-во символов: 70', 'vnet'); ?>
    </p>
</div>
<div style="margin-bottom: 10px;">
    <label for="vnet-seo-desc" style="display: block; margin-bottom: 3px; width: fit-content;">
        <?= __('Описание', 'vnet'); ?>
    </label>
    <textarea name="vnet-seo-desc" id="vnet-seo-desc" style="width: 100%; min-height: 150px;"><?= Seo::getPostDesc($post->ID); ?></textarea>
    <p style="margin: 0px; margin-top: 3px;">
        <?= __('Маскимальное кол-во символов: 300', 'vnet'); ?>
    </p>
</div>
<div>
    <label for="vnet-seo-image" style="display: block; margin-bottom: 3px; width: fit-content;">
        <?= __('Изображение', 'vnet'); ?>
    </label>
    <?php
    if ($img = Seo::getPostImage($post->ID)) {
    ?>
        <img src="<?= $img; ?>" class="js-vnet-seo-image" alt="image" style="width: 350px; height: 200px; object-fit: contain; margin-bottom: 5px;">
    <?php
    } else {
    ?>
        <img src="" class="js-vnet-seo-image" alt="image" style="width: 350px; height: 200px; object-fit: contain; margin-bottom: 5px; display: none;">
    <?php
    }
    ?>
    <input type="hidden" name="vnet-seo-image" value="<?= Seo::getPostImageId($post->ID); ?>">
    <div style="display: flex; align-items: center;">
        <button type="button" class="js-vnet-seo-image-upload button" id="vnet-seo-image"><?= __('Загрузить', 'vnet'); ?></button>
        <button type="button" class="button js-vnet-seo-image-delete" style="margin-left: 10px;<?= !Seo::getPostImageId($post->ID) ? ' display: none;' : ''; ?>">Удалить</button>
    </div>
    <p style="margin: 0px; margin-top: 3px;">
        <?= __('Рекомендуемй размер: 1200x630', 'vnet'); ?>
    </p>
</div>
<script>
    jQuery('.js-vnet-seo-image-upload').on('click', function(e) {
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
            jQuery('.js-vnet-seo-image').attr('src', url).css('display', 'block');
            jQuery('[name="vnet-seo-image"]').val(id);
            jQuery('.js-vnet-seo-image-delete').css('display', 'inline-block');
            frame.close();
        });

        frame.open();
    });

    jQuery('.js-vnet-seo-image-delete').on('click', function(e) {
        e.preventDefault();
        jQuery('.js-vnet-seo-image').css('display', 'none');
        jQuery('.js-vnet-seo-image-delete').css('display', 'none');
        jQuery('[name="vnet-seo-image"]').val(0);
    });
</script>