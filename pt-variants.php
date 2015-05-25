<?php
/*
 * Plugin Name: PT Variants
 * Description: Choose the Portuguese variant that suits your needs. You can choose between Portuguese orthografic agreement or Informal Portuguese. This project it's being curated by the WordPress Portuguese Community
 * Version: 0.1
 * Author: Comunidade Portuguesa do WordPress, Alvaro Góis dos Santos, Marco Pereirinha
 * Author URI: http://wp-portugal.com
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! class_exists( 'PortugueseVariants' ) ) {

	class PortugueseVariants {

		const VERSION = '0.1';
		const VERSION_OPTION_NAME = 'pt_variants_version';
		const FE_VERSION_OPTION_NAME = 'pt_variants_fe';
		const BE_VERSION_OPTION_NAME = 'pt_variants_be';

		private $locale;
		private $overwrite_folder;
		private $variants_in_use;
		private $locals;
		private $variants;


		function __construct() {

			// Locale definition
			$this->locale = get_locale();

			// If locale isn't Portuguese from Portugal, don't go further
			if ( 'pt_PT' !== $this->locale ) {
				return false;
			}

			// Register plugin textdomain
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			// Translations path
			$this->overwrite_folder = trailingslashit( plugin_dir_path( __FILE__ ) . 'languages' );

			// Diferent translation projects
			$this->locals = array(
				'default' => __( 'Front end', 'pt_variants' ),
				'admin' => __( 'Back end', 'pt_variants' ),
			);

			// Options available
			$this->variants = array(
				'none'      => __( 'Default portuguese translation', 'pt_variants' ),
				'pt_PT-AO'  => __( 'Portuguese orthographic agreement', 'pt_variants' ),
				'pt_PT-INF' => __( 'Informal Portuguese', 'pt_variants' ),
			);

			// Get variants already in use
			$this->variants_in_use[ array_keys( $this->locals )[0] ] = get_option( SELF::FE_VERSION_OPTION_NAME );
			$this->variants_in_use[ array_keys( $this->locals )[1] ] = get_option( SELF::BE_VERSION_OPTION_NAME );

			// register action that is triggered, whenever a textdomain is loaded
			add_action( 'override_load_textdomain', array( $this, 'overwrite_textdomain' ), 10, 3 );

			// Register action that will fire admin settigns
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}

		public function load() {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				$this->install();
			}
		}

		static function load_textdomain() {
			load_plugin_textdomain( 'pt_variants', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Overwrite strings
		 */
		public function overwrite_textdomain( $override, $domain, $mofile ) {
			// if the filter was not called with an overwrite mofile, return false which will proceed with the mofile given and prevents an endless recursion
			if ( strpos( $mofile, $this->overwrite_folder ) !== false ) {
				return false;
			}

			// Act on all locals
			foreach ( $this->variants_in_use as $local => $variant ) {

				// There's nothing to do here
				if ( 'none' === $variant ) {
					continue;
				}
				// if an overwrite file exists, load it to overwrite the original strings
				$overwrite_mofile = $local . '-' . $variant . '.mo';

				// check if a global overwrite mofile exists and load it
				$global_overwrite_file = $this->overwrite_folder . $overwrite_mofile;

				if ( file_exists( $global_overwrite_file ) ) {
					load_textdomain( $domain, $global_overwrite_file );
				}
			}

			return false;
		}

		/**
		 * Admin stuff
		 */
		public function admin_init() {
			register_setting( 'general', SELF::FE_VERSION_OPTION_NAME );
			register_setting( 'general', SELF::BE_VERSION_OPTION_NAME );
			add_settings_field(
				'pt_variants_fe',
				'<label for="' . SELF::FE_VERSION_OPTION_NAME . '">' . __( 'Front end' , 'pt_variants' ) . '</label>' ,
				array( &$this, 'options_pt_variants' ),
				'general',
				'default',
				array(
					'key' => SELF::FE_VERSION_OPTION_NAME,
					'in_use' => $this->variants_in_use['default'],
				)
			);
			add_settings_field(
				'pt_variants_be',
				'<label for="' . SELF::BE_VERSION_OPTION_NAME . '">' . __( 'Back end' , 'pt_variants' ) . '</label>' ,
				array( &$this, 'options_pt_variants' ),
				'general',
				'default',
				array(
					'key' => SELF::BE_VERSION_OPTION_NAME,
					'in_use' => $this->variants_in_use['admin'],
				)
			);
		}

		/**
		 * Show Admin avaliable options
		 */
		function options_pt_variants( $args ) { ?>
			<select name="<?php echo esc_attr( $args['key'] ); ?>" id="<?php echo esc_attr( $args['key'] ); ?>">
				<?php foreach ( $this->variants as $code => $title ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"<?php echo ( $args['in_use'] === $code ) ? ' selected' : '' ?>><?php echo $title ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		/** Lifecycle methods **/

		private function install() {
			$installed_version = get_option( SELF::VERSION_OPTION_NAME );

			if ( ! $installed_version ) {
				// initial install, set the version of the plugin on options table
				add_option( SELF::VERSION_OPTION_NAME, SELF::VERSION );
				add_option( SELF::FE_VERSION_OPTION_NAME, array_keys( $this->variants )[0] );
				add_option( SELF::BE_VERSION_OPTION_NAME, array_keys( $this->variants )[1] );
			}

			if ( SELF::VERSION !== $installed_version ) {
				$this->upgrade();
			}
		}

		// Run when plugin version number changes
		private function upgrade() {
			update_option( SELF::VERSION_OPTION_NAME, SELF::VERSION );
		}

	}

	$pt_variants = new PortugueseVariants;
	$pt_variants->load();

}