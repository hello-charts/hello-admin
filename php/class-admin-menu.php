<?php
/**
 * Register admin menu items.
 *
 * @package Hello_Admin
 */

namespace Hello_Admin;

/**
 * Class Admin_Menu.
 */
class Admin_Menu {
	/**
	 * Menu slug prefix.
	 *
	 * @var string
	 */
	public $prefix = 'admin-page-';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}

	/**
	 * Register any hooks that this class needs.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_menu_items' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	/**
	 * Register the custom post type.
	 */
	public function register_menu_items() {
		$admin_pages_query = new \WP_Query(
			[
				'posts_per_page' => 99,
				'post_type'      => hello_admin()->post_type->slug,
				'meta_key'       => 'sub_menu',
				'orderby'        => 'meta_value_num',
				'order'          => 'ASC',
			]
		);

		while ( $admin_pages_query->have_posts() ) {
			$admin_pages_query->the_post();
			$menu_position = (int) get_post_meta(
				get_the_ID(),
				hello_admin()->post_type->menu_position_key,
				true
			);

			$sub_menu = get_post_meta(
				get_the_ID(),
				hello_admin()->post_type->sub_menu_key,
				true
			);

			if ( '' === $sub_menu ) {
				update_post_meta( get_the_ID(), hello_admin()->post_type->sub_menu_key, 0 );
			}

			$title     = get_the_title();
			$menu_slug = $this->prefix . get_post_field( 'post_name' );

			if ( ! $sub_menu ) {
				add_menu_page(
					$title,
					$title,
					'read',
					$menu_slug,
					[ $this, 'render_menu_page' ],
					'',
					$menu_position
				);
			} else {
				$menu_parent = get_post_meta(
					get_the_ID(),
					hello_admin()->post_type->menu_parent_key,
					true
				);

				$parent_slug = $this->prefix . get_post_field( 'post_name', $menu_parent );

				add_submenu_page(
					$parent_slug,
					$title,
					$title,
					'read',
					$menu_slug,
					[ $this, 'render_menu_page' ],
					$menu_position
				);
			}
		}

		wp_reset_postdata();
	}

	/**
	 * Enqueue admin assets.
	 */
	public function enqueue_admin_assets() {
		if ( ! is_admin() ) {
			return;
		}

		$version = hello_admin_version();

		// Enqueue block editor styles for backend.
		wp_enqueue_style(
			'hello-admin-css',
			plugins_url( '/build/admin.css', dirname( __FILE__ ) ),
			[],
			$version
		);
	}

	/**
	 * Render the menu page.
	 */
	public function render_menu_page() {
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		$slug = substr( $page, strlen( $this->prefix ) );
		$post = get_posts(
			[
				'name'           => $slug,
				'post_type'      => hello_admin()->post_type->slug,
				'posts_per_page' => 1,
			]
		)[0];

		wp_enqueue_style( 'wp-block-library' );
		wp_enqueue_style( 'wp-block-library-theme' );

		do_action( 'hello_admin_pre_render_content' );
		$content = apply_filters( 'the_content', $post->post_content );
		$content = apply_filters( 'hello_admin_page_content', $content );

		echo '<div class="wrap hello-admin-wrap">';
		echo '<h1>' . esc_html( get_the_title( $post->ID ) ) . '</h1>';
		echo wp_kses_post( $content );
		echo '</div>';

		do_action( 'hello_admin_post_render_content' );
	}
}
