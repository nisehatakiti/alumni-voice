<?php
/**
 * Plugin Name: AlumniVoice
 * Description: Official Alumni Core extension for structured alumni voices, profiles and future search/discovery.
 * Version: 0.1.0
 * Requires PHP: 7.4
 * Text Domain: alumni-voice
 */

if ( ! defined( 'ABSPATH' ) ) exit;
define( 'ALUMNI_VOICE_VERSION', '0.1.0' );
define( 'ALUMNI_VOICE_FILE', __FILE__ );
define( 'ALUMNI_VOICE_PATH', plugin_dir_path( __FILE__ ) );

require_once ALUMNI_VOICE_PATH . 'includes/class-dependency.php';
require_once ALUMNI_VOICE_PATH . 'includes/class-plugin.php';

add_action( 'plugins_loaded', array( 'AlumniVoice_Plugin', 'bootstrap' ), 20 );
