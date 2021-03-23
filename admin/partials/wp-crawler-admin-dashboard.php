<?php

?>



<div class="wrap">
    <h1>WP Crawler Dashboard</h1>
	<?php
	echo $dashboard_message;
	?>
	<form  method="post">
        <input id="submit_crawl" name="submit_crawl" type="submit" value="Crawl now!">
    </form>
</div>


<div id="crawl-pages" class="wrap">
    <h2>Website's internal pages:</h2>
    <ul class="treeview">
        <?php
        foreach ( $pages as $page )
        {
            if ( is_null( $page->parent_page_id ) ) {

                echo '<li>' . $page->url . ' <a href="' . $page->url . '" target="_blank"><img src="' . WP_CRAWLER_ASSETS_IMG_URL . 'external-link.png" alt="See the page..."></a>';
                echo '<ul>';
            } else {

                echo '<li>' . $page->url . ' <a href="' . $page->url . '" target="_blank"><img src="' . WP_CRAWLER_ASSETS_IMG_URL . 'external-link.png" alt="See the page..."></a></li>';

            }
        }
        echo '</li></ul>';
        ?>

    </ul>
</div>

