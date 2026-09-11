<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlumniVoice_Plugin {
	private static $initialized = false;

	public static function bootstrap() {
		if ( ! AlumniVoice_Dependency::is_available() ) {
			add_action( 'admin_notices', array( 'AlumniVoice_Dependency', 'admin_notice' ) );
			return;
		}

		require_once ALUMNI_VOICE_PATH . 'includes/class-post-type.php';
		require_once ALUMNI_VOICE_PATH . 'includes/class-form-settings.php';
		require_once ALUMNI_VOICE_PATH . 'includes/class-submission.php';
		require_once ALUMNI_VOICE_PATH . 'includes/class-public-content.php';
		require_once ALUMNI_VOICE_PATH . 'includes/class-notifications.php';
		require_once ALUMNI_VOICE_PATH . 'admin/class-admin.php';

		/*
		 * Alumni Core is loaded immediately when its plugin file is included.
		 * Depending on WordPress active-plugin load order, the
		 * alumni_core_loaded action may already have fired by the time this
		 * extension reaches plugins_loaded. Initialize immediately in that
		 * case, otherwise wait for the Core readiness hook.
		 */
		if ( did_action( 'alumni_core_loaded' ) ) {
			self::initialize();
		} else {
			add_action( 'alumni_core_loaded', array( __CLASS__, 'initialize' ) );
		}
	}

	public static function initialize() {
		if ( self::$initialized ) {
			return;
		}
		self::$initialized = true;

		AlumniVoice_Post_Type::register();
		AlumniVoice_Form_Settings::ensure_defaults();
		AlumniVoice_Form_Settings::register();
		AlumniVoice_Submission::register();
		AlumniVoice_Public_Content::register();
		AlumniVoice_Notifications::register();
		AlumniVoice_Admin::register();
	}
}
