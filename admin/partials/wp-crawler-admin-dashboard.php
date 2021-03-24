<?php

?>

<div class="wrap">
    <h1><?php echo __( 'WP Crawler Dashboard', 'wp-crawler' ); ?></h1>
</div>
<br>
<div class="wrap">
    <h2 class="wpc-h2 underline"><i class="bi bi-gear"></i> <?php echo __( 'Crawler', 'wp-crawler' ); ?></h2>
	<p><?php echo $dashboard_message; ?></p>
    <div id="submit_crawl">
        <form  method="post">
            <p><?php submit_button( __( 'Crawl Now!', 'wp-crawler' ), 'primary', 'submit-crawl',  false ); ?></p>
        </form>
    </div>
</div>
<br>

<?php
if ( get_option( 'wpc_last_crawl' ) ) {
?>
<div class="wrap">
    <h2 class="wpc-h2 underline"><i class="bi bi-globe"></i> <?php echo __( 'Website Pages', 'wp-crawler' ); ?></h2>
    <p><?php echo __( 'Click on the button below to display the result of the last crawl.', 'wp-crawler' ); ?></p>
    <div class="wpc-flex">
        <div id="submit_results">
            <form  method="post">
                <p><?php submit_button( __( 'Show pages', 'wp-crawler' ), 'primary', 'submit-results', false ); ?></p>
            </form>
        </div>

        <div id="submit_static">
            <p><a href="<?php echo get_option( 'wpc_homepage_static_url' ); ?>" target="_blank"><input type="button" id="button-static" class="button button-primary" name="button-static" value="<?php echo __( 'Open static homepage', 'wp-crawler' ); ?>"></a></p>
        </div>
    </div>
</div>

<?php
}

if ( isset( $pages ) ) {
?>

<div id="crawl-pages" class="wrap">
    <h2>Website's internal pages:</h2>

    <ul class="treeview">
    <?php
    foreach ( $pages as $page )
    {
        ?>

        <?php
        if ( is_null( $page->parent_page_id ) ) {
            echo '<li>' . $page->url . ' <a href="' . $page->url . '" target="_blank"><i class="bi bi-box-arrow-up-right external-link"></i></a>';
            echo '<ul>';
        } else {
            echo '<li>' . $page->url . ' <a href="' . $page->url . '" target="_blank"><i class="bi bi-box-arrow-up-right external-link"></i></a></li>';
        }
    }
    ?>
            </ul>
        </li>
    </ul>
</div>
<?php
}
?>



