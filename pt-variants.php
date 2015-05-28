<?php
/*
 * Plugin Name: PT Variants
 * Description: Choose the Portuguese variant that suits your needs. Though Portuguese has no formal variants, beside the default, you can now choose Portuguese Orthografic Agreement form or Informal Portuguese. This project is being curated by the WordPress Portuguese Community
 * Version: 0.1
 * Author: Comunidade Portuguesa de WordPress, Marco Pereirinha, Álvaro Góis dos Santos
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
		private $local_values;
		private $variants;

		function __construct() {
			// Locale definition
			$this->locale = get_locale();

			// If locale isn't Portuguese from Portugal, don't go further
			if ( 'pt_PT' !== $this->locale ) {
				return false;
			}

			// Register plugin textdomain
			add_action( 'admin_init', array( $this, 'load_textdomain' ) );

			// Translations path
			$this->overwrite_folder = trailingslashit( plugin_dir_path( __FILE__ ) . 'languages' );

			// register action that is triggered, whenever a textdomain is loaded
			add_action( 'override_load_textdomain', array( &$this, 'overwrite_textdomain' ), 10, 3 );

			// Register action that will fire admin settigns
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}

		function load() {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				$this->install();
			}

			// Diferent translation projects
			$this->locals = array(
				'default' => __( 'Front end', 'pt_variants' ),
				'admin' => __( 'Back end', 'pt_variants' ),
			);

			$this->local_values = array_keys( $this->locals );

			// Get variants already in use
			$this->variants_in_use[ $this->local_values[0] ] = get_option( self::FE_VERSION_OPTION_NAME );
			$this->variants_in_use[ $this->local_values[1] ] = get_option( self::BE_VERSION_OPTION_NAME );
			// For multisite installs
			$this->variants_in_use[ $this->local_values[1] . '-network' ] = get_option( self::BE_VERSION_OPTION_NAME );
		}

		function load_textdomain() {
			load_plugin_textdomain( 'pt_variants', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			// Options available
			$this->variants = array(
				'none'      => __( 'Default portuguese translation', 'pt_variants' ),
				'pt_PT-AO'  => __( 'Portuguese orthographic agreement', 'pt_variants' ),
				'pt_PT-INF' => __( 'Informal Portuguese', 'pt_variants' ),
			);
		}

		/**
		 * Overwrite strings
		 */
		function overwrite_textdomain( $override, $domain, $mofile ) {
			if ( ! in_array( $domain, $this->local_values ) ) {
				return false;
			}

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
		function admin_init() {
			add_settings_section(
				'pt_variants_section',
				__( 'Portuguese variants', 'pt_variants' ),
				null,
				'general'
			);
			add_settings_field(
				self::FE_VERSION_OPTION_NAME,
				'<label for="' . self::FE_VERSION_OPTION_NAME . '">' . __( 'Front end' , 'pt_variants' ) . '</label>' ,
				array( &$this, 'options_pt_variants' ),
				'general',
				'pt_variants_section',
				array(
					'key' => self::FE_VERSION_OPTION_NAME,
					'in_use' => $this->variants_in_use['default'],
				)
			);
			add_settings_field(
				self::BE_VERSION_OPTION_NAME,
				'<label for="' . self::BE_VERSION_OPTION_NAME . '">' . __( 'Back end' , 'pt_variants' ) . '</label>' ,
				array( &$this, 'options_pt_variants' ),
				'general',
				'pt_variants_section',
				array(
					'key' => self::BE_VERSION_OPTION_NAME,
					'in_use' => $this->variants_in_use['admin'],
				)
			);
			register_setting( 'general', self::FE_VERSION_OPTION_NAME );
			register_setting( 'general', self::BE_VERSION_OPTION_NAME );
		}

		/**
		 * Show Admin avaliable options
		 */
		function options_pt_variants( $args ) { ?>
			<select name="<?php echo esc_attr( $args['key'] ); ?>" id="<?php echo esc_attr( $args['key'] ); ?>">
				<?php foreach ( $this->variants as $code => $title ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"<?php echo ( $args['in_use'] === $code ) ? ' selected' : '' ?>><?php echo esc_html( $title ) ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		/** Lifecycle methods **/
		private function install() {
			$installed_version = get_option( self::VERSION_OPTION_NAME );

			if ( ! $installed_version ) {
				// initial install, set the version of the plugin on options table
				add_option( self::VERSION_OPTION_NAME, self::VERSION );
				add_option( self::FE_VERSION_OPTION_NAME, 'pt_PT-AO' );
				add_option( self::BE_VERSION_OPTION_NAME, 'pt_PT-AO' );
			}

			if ( self::VERSION !== $installed_version ) {
				$this->upgrade();
			}
		}

		// Run when plugin version number changes
		private function upgrade() {
			update_option( self::VERSION_OPTION_NAME, self::VERSION );
		}

	}

	$pt_variants = new PortugueseVariants;
	$pt_variants->load();

}
