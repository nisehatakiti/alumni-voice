<?php
/**
 * Plugin Name: AlumniVoice
 * Description: Official Alumni Core extension for structured alumni voices, profiles and future search/discovery.
 * Version: 0.1.5
 * Requires PHP: 7.4
 * Text Domain: alumni-voice
 */

if ( ! defined( 'ABSPATH' ) ) exit;
define( 'ALUMNI_VOICE_VERSION', '0.1.5' );
define( 'ALUMNI_VOICE_FILE', __FILE__ );
define( 'ALUMNI_VOICE_PATH', plugin_dir_path( __FILE__ ) );

require_once ALUMNI_VOICE_PATH . 'includes/class-dependency.php';
require_once ALUMNI_VOICE_PATH . 'includes/class-public-content.php';
require_once ALUMNI_VOICE_PATH . 'includes/class-plugin.php';

// Register the Alumni Core content-discovery filters during plugin loading,
// before either plugin's later runtime hooks can affect the picker.
AlumniVoice_Public_Content::register_core_integration();

add_action( 'plugins_loaded', array( 'AlumniVoice_Plugin', 'bootstrap' ), 20 );
