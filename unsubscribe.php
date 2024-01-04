<?php

use Vnet\Theme\Subscribe;

require $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

$key = $_REQUEST['key'];

$res = Subscribe::unsubscribe($key);

get_header();

?>
<section class="section section-unsubscribe">
    <div class="container">
        <h2 class="section-title">Ваша подписка успешно отменена</h2>
    </div>
</section>
<?php

get_footer();
