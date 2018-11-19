<?php

include "common/admin.php";

class MWCP_Admin extends MeowApps_Admin {

	public function __construct() {
		parent::__construct( 'wpcp', __FILE__, 'category-permalink' );
		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'app_menu' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	function admin_notices() {
	}


	function common_url( $file ) {
		return trailingslashit( plugin_dir_url( __FILE__ ) ) . 'common/' . $file;
	}

	function app_menu() {

		// SUBMENU > Settings
		add_submenu_page( 'meowapps-main-menu', 'Category Permalink', 'Category Permalink', 'manage_options',
			'wpcp_settings-menu', array( $this, 'admin_settings' ) );

			// SUBMENU > Settings > Settings
			add_settings_section( 'wpcp_settings', null, null, 'wpcp_settings-menu' );
			add_settings_field( 'wpcp_hide_permalink', "Posts List",
				array( $this, 'admin_hide_permalink_callback' ),
				'wpcp_settings-menu', 'wpcp_settings' );

		// SETTINGS
		register_setting( 'wpcp_settings', 'wpcp_hide_permalink' );
	}

	function admin_settings() {
		?>
		<div class="wrap">
			<?php echo $this->display_title( "WP Category Permalink" );  ?>
			<p></p>

			<div class="meow-row">
				<div class="meow-box meow-col meow-span_2_of_2">
					<h3>How to use</h3>
					<div class="inside">
						<?php echo _e( 'For this plugin to work, don\'t forget that you need to use a Permalink Structure that includes <b>%category%</b> (such as "/%category%/%postname%"). This %category% will be handled by the plugin. It can also handle custom post types and taxonomies (such as in gallery plugins, WooCommerce, etc).', 'category-permalink' ) ?>
					</div>
				</div>
			</div>

			<div class="meow-row">

					<div class="meow-col meow-span_1_of_2">

						<div class="meow-box">
							<h3>Settings</h3>
							<div class="inside">
								<form method="post" action="options.php">
								<?php settings_fields( 'wpcp_settings' ); ?>
						    <?php do_settings_sections( 'wpcp_settings-menu' ); ?>
						    <?php submit_button(); ?>
								</form>
							</div>
						</div>

					</div>

					<div class="meow-col meow-span_1_of_2">

						<div class="meow-box">
							<h3>Permalinks</h3>
							<div class="inside">
								<p>The permalinks listed below are the ones based on custom post type and taxonomy created by your theme on another plugins.</p>
								<?php

									$fields = array();
					        $post_types = MWCPPost::post_types();
									foreach ( $post_types as $type => $post_info )
					        {
										echo "<h4>$post_info->label</h4>";

							$taxa = MWCPPost::taxonomies( $type );
							if ( empty( $taxa ) )
								continue;
							$query_vars = array_map(
								function( $a ) {
									return $a->query_var;
								},
								$taxa
							);
										global $wp_rewrite;
						        $post_link = $wp_rewrite->get_extra_permastruct( $type );
				            echo __( '<small>Permalink: ' ) . $post_link . '<br>' .
				              __( 'Post Type: ' ) . $type . '<br />' .
				              __( 'Taxonomies: ' ) . implode( ' ', $query_vars ) . '</small>';
					        }
								?>
							</div>
						</div>

					</div>

			</div>

			<div class="meow-row">
				<form method="post" action="options.php">

				</form>
			</div>

		</div>
		<?php
	}

	/*
		OPTIONS CALLBACKS
	*/

	function admin_hide_permalink_callback( $args ) {
    $value = get_option( 'wpcp_hide_permalink', null );
		$html = '<input type="checkbox" id="wpcp_hide_permalink" name="wpcp_hide_permalink" value="1" ' .
			checked( 1, get_option( 'wpcp_hide_permalink' ), false ) . '/>';
    $html .= '<label> '  . __( 'Hide Permalinks', 'category-permalink' ) . '</label><br>';
		$html .= '<small class="description">'  . __( 'In the listing of posts (or any other post type), don\'t display the permalink below the title.', 'category-permalink' ) . '</small>';
    echo $html;
  }

}

?>
