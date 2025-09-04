<?php
/**
 * @package WordPress
 * @subpackage Timberland
 * @since Timberland 2.2.0
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

Timber\Timber::init();
Timber::$dirname    = array( 'views', 'blocks' );
Timber::$autoescape = false;

class Timberland extends Timber\Site {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'after_setup_theme', array( $this, 'theme_supports' ) );
		add_filter( 'timber/context', array( $this, 'add_to_context' ) );
		add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
		add_action( 'block_categories_all', array( $this, 'block_categories_all' ) );
		add_action( 'acf/init', array( $this, 'acf_register_blocks' ) );
		add_action( 'acf/init', array( $this, 'register_options_pages' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );

		parent::__construct();
	}

	public function register_options_pages() {
    if ( function_exists( 'acf_add_options_page' ) ) {
      acf_add_options_page([
        'page_title' => 'Theme Settings',
        'menu_title' => 'Theme Settings',
        'menu_slug'  => 'theme-settings',
        'capability' => 'manage_options',
        'redirect'   => false,
				'icon_url' 	 => 'dashicons-admin-generic',
      ]);

      acf_add_options_sub_page([
        'page_title'  => 'Header',
        'menu_title'  => 'Header',
        'parent_slug' => 'theme-settings',
      ]);
      acf_add_options_sub_page([
        'page_title'  => 'Footer',
        'menu_title'  => 'Footer',
        'parent_slug' => 'theme-settings',
      ]);
    }
  }

	public function add_to_context( $context ) {
		$context['site'] = $this;
		$context['menu'] = Timber::get_menu();

		$context['primary_menu'] = Timber::get_menu('primary');
		$context['footer_menu']  = Timber::get_menu('footer');
		$context['options']      = function_exists('get_fields') ? get_fields('option') : [];

		// Require block functions files
		foreach ( glob( __DIR__ . '/blocks/*/functions.php' ) as $file ) {
			require_once $file;
		}

		return $context;
	}

	public function add_to_twig( $twig ) {
		return $twig;
	}

	public function theme_supports() {
		add_theme_support( 'automatic-feed-links' );
		add_theme_support(
			'html5',
			array(
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
			)
		);
		add_theme_support( 'menus' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'editor-styles' );

		register_nav_menus([
			'primary' => 'Primary Menu',
			'footer'  => 'Footer Menu',
		]);
	}

	public function enqueue_assets() {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-block-style' );
		wp_dequeue_script( 'jquery' );
		wp_dequeue_style( 'global-styles' );

		$vite_env = 'production';

		if ( file_exists( get_template_directory() . '/../config.json' ) ) {
			$config   = json_decode( file_get_contents( get_template_directory() . '/../config.json' ), true );
			$vite_env = $config['vite']['environment'] ?? 'production';
		}

		$dist_uri  = get_template_directory_uri() . '/assets/dist';
		$dist_path = get_template_directory() . '/assets/dist';
		$manifest  = null;

		if ( file_exists( $dist_path . '/.vite/manifest.json' ) ) {
			$manifest = json_decode( file_get_contents( $dist_path . '/.vite/manifest.json' ), true );
		}

		if ( is_array( $manifest ) ) {
			if ( $vite_env === 'production' || is_admin() ) {
				$js_file = 'theme/assets/main.js';
				wp_enqueue_style( 'main', $dist_uri . '/' . $manifest[ $js_file ]['css'][0] );
				$strategy = is_admin() ? 'async' : 'defer';
				$in_footer = is_admin() ? false : true;
				wp_enqueue_script(
					'main',
					$dist_uri . '/' . $manifest[ $js_file ]['file'],
					array(),
					'',
					array(
						'strategy'  => $strategy,
						'in_footer' => $in_footer,
					)
				);

				// wp_enqueue_style('prefix-editor-font', '//fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&display=swap');
				$editor_css_file = 'theme/assets/styles/editor-style.css';
				add_editor_style( $dist_uri . '/' . $manifest[ $editor_css_file ]['file'] );
			}
		}

		if ( $vite_env === 'development' ) {
			function vite_head_module_hook() {
				echo '<script type="module" crossorigin src="http://localhost:3001/@vite/client"></script>';
				echo '<script type="module" crossorigin src="http://localhost:3001/theme/assets/main.js"></script>';
			}
			add_action( 'wp_head', 'vite_head_module_hook' );
		}
	}

	public function block_categories_all( $categories ) {
		return array_merge(
			array(
				array(
					'slug'  => 'custom',
					'title' => __( 'Custom' ),
				),
			),
			$categories
		);
	}

	public function acf_register_blocks() {
		$blocks = array();

		foreach ( new DirectoryIterator( __DIR__ . '/blocks' ) as $dir ) {
			if ( $dir->isDot() ) {
				continue;
			}

			if ( file_exists( $dir->getPathname() . '/block.json' ) ) {
				$blocks[] = $dir->getPathname();
			}
		}

		asort( $blocks );

		foreach ( $blocks as $block ) {
			register_block_type( $block );
		}
	}
}

new Timberland();

function acf_block_render_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {
  $context = Timber::context();
  $context['post']       = Timber::get_post();
  $context['block']      = $block;
  $context['fields']     = get_fields();
  $context['content']    = $content;
  $context['is_preview'] = $is_preview;

  $slug     = explode('/', $block['name'])[1];
  $template = 'blocks/' . $slug . '/index.twig';

  Timber::render( $template, $context );
}

// Sanitize + inline an SVG attachment, let Twig print SVG as HTML.
function inline_svg($attachment, $opts = []) {
  $id   = is_array($attachment) ? ($attachment['ID'] ?? null) : (int)$attachment;
  $path = $id ? get_attached_file($id) : (is_string($attachment) ? $attachment : null);
  if (!$path || !file_exists($path)) return '';

  $svg = file_get_contents($path);

  // basic hardening (use a real sanitizer in production: enshrined/svg-sanitizer or Safe SVG plugin).
  $svg = preg_replace('/<\?xml.*?\?>/i', '', $svg);
  $svg = preg_replace('#<!DOCTYPE.*?>#i', '', $svg);
  $svg = preg_replace('#<(script|foreignObject)\b[^>]*>.*?</\1>#is', '', $svg);

  // force monochrome if requested (replace fills/strokes with currentColor)
  if (!empty($opts['monochrome'])) {
    $svg = preg_replace('/\sfill="(?!none)[^"]*"/i', ' fill="currentColor"', $svg);
    $svg = preg_replace('/\sstroke="(?!none)[^"]*"/i', ' stroke="currentColor"', $svg);
  }

  // add class/title on root <svg>
  if (!empty($opts['class']))  $svg = preg_replace('/<svg\b/i', '<svg class="'.esc_attr($opts['class']).'"', $svg, 1);
  if (!empty($opts['title']))  $svg = preg_replace('/<svg\b/i', '<svg role="img" aria-label="'.esc_attr($opts['title']).'"', $svg, 1);

  return $svg;
}

add_filter('timber/twig', function($twig){
  $twig->addFunction(new \Twig\TwigFunction('inline_svg', 'inline_svg', ['is_safe' => ['html']]));
  return $twig;
});