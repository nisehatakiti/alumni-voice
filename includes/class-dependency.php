<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlumniVoice_Dependency {
	const MINIMUM_API_VERSION = '1.0';

	public static function is_available() {
		return function_exists( 'alumni_core_is_extension_compatible' )
			&& alumni_core_is_extension_compatible( self::MINIMUM_API_VERSION );
	}

	public static function admin_notice() {
		if ( ! current_user_can( 'activate_plugins' ) || self::is_available() ) return;
		echo '<div class="notice notice-error"><p><strong>AlumniVoice</strong> requires Alumni Core with Extension API ' . esc_html( self::MINIMUM_API_VERSION ) . ' or later.</p></div>';
	}
}
