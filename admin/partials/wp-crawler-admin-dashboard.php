
<div class="wrap">
	<h1><?php echo esc_html( __( 'WP Crawler Dashboard', 'wp-crawler' ) ); ?></h1>
</div>
<br>
<div class="wrap">
	<h2 class="wpc-h2 underline"><i class="bi bi-gear"></i> <?php echo esc_html( __( 'Crawler', 'wp-crawler' ) ); ?></h2>
	<p>
		<?php
			// CODE REVIEW: removed the unnecessary isset() after change in class-admin.php.
			echo esc_html( $dashboard_message );
		?>
	</p>
	<div id="submit_crawl">
		<form method="post">
			<p>
				<?php
				wp_nonce_field( 'submit_crawl', 'nonce_crawl' );
				submit_button( __( 'Crawl Now!', 'wp-crawler' ), 'primary', 'submit-crawl', false );
				?>
			</p>
		</form>
	</div>
</div>
<br>

<?php
if ( get_option( 'wpc_last_crawl' ) ) {
	?>
<div class="wrap">
	<h2 class="wpc-h2 underline"><i class="bi bi-globe"></i> <?php echo esc_html( __( 'Website Pages', 'wp-crawler' ) ); ?></h2>
	<p><?php echo esc_html( __( 'Click on the button below to display the result of the last crawl.', 'wp-crawler' ) ); ?></p>
	<div class="wpc-flex">
		<div id="show-static">
			// CODE REVIEW: esc_url() & esc_attr() are better choices than esc_html() in this context/
			<p><a href="<?php echo esc_url( get_option( 'wpc_homepage_static_url' ) ); ?>" target="_blank"><input type="button" id="button-static" class="button button-primary" name="button-static" value="<?php echo esc_attr( __( 'Open static homepage', 'wp-crawler' ) ); ?>"></a></p>
		</div>
		<div id="show-sitemap">
			<p><a href="<?php echo esc_url( trailingslashit( get_home_url() ) . 'sitemap.html' ); ?>" target="_blank"><input type="button" id="button-sitemap" class="button button-primary" name="button-sitemap" value="<?php echo esc_attr( __( 'Open sitemap.html', 'wp-crawler' ) ); ?>"></a></p>
		</div>
	</div>
</div>

	<?php
}

if ( isset( $webpages ) && ! empty( $webpages ) ) {
	?>

<div id="crawl-pages" class="wrap">
	<h2>Website's internal pages:</h2>

	<ul class="treeview">
	<?php
	foreach ( $webpages as $webpage ) {
		if ( is_null( $webpage->parent_page_id ) ) {
			echo '<li>' . esc_html( $webpage->url ) . ' <a href="' . esc_url( $webpage->url ) . '" target="_blank"><i class="bi bi-box-arrow-up-right external-link"></i></a>';
			echo '<ul>';
		} else {
			echo '<li>' . esc_html( $webpage->url ) . ' <a href="' . esc_url( $webpage->url ) . '" target="_blank"><i class="bi bi-box-arrow-up-right external-link"></i></a></li>';
		}
	}

	echo '</ul>';
	echo '</li>';
	?>
	</ul>
</div>
	<?php
}
?>
