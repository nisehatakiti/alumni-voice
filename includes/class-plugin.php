<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlumniVoice_Plugin {
	public static function bootstrap() {
		if ( ! AlumniVoice_Dependency::is_available() ) {
			add_action( 'admin_notices', array( 'AlumniVoice_Dependency', 'admin_notice' ) );
			return;
		}

		require_once ALUMNI_VOICE_PATH . 'includes/class-post-type.php';
		require_once ALUMNI_VOICE_PATH . 'includes/class-form-settings.php';
		require_once ALUMNI_VOICE_PATH . 'includes/class-submission.php';
		require_once ALUMNI_VOICE_PATH . 'includes/class-public-content.php';
		require_once ALUMNI_VOICE_PATH . 'admin/class-admin.php';

		add_action( 'alumni_core_loaded', array( __CLASS__, 'initialize' ) );
	}

	public static function initialize() {
		AlumniVoice_Post_Type::register();
		AlumniVoice_Form_Settings::ensure_defaults();
		AlumniVoice_Form_Settings::register();
		AlumniVoice_Submission::register();
		AlumniVoice_Public_Content::register();
		AlumniVoice_Admin::register();
	}
}
