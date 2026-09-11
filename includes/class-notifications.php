<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Email notifications for AlumniVoice submissions and publication.
 *
 * The editorial flow itself remains standard WordPress:
 * public form -> draft -> administrator review/edit -> publish.
 */
class AlumniVoice_Notifications {
	const OPTION = 'alumni_voice_notification_settings';
	const META_PUBLICATION_SENT = '_alumni_voice_publication_notification_sent';

	public static function defaults() {
		return array(
			'admin_enabled' => 1,
			'admin_emails' => get_option( 'admin_email' ),
			'admin_subject' => '【AlumniVoice】新しい「卒業生の声」が投稿されました',
			'admin_message' => "新しい「卒業生の声」が投稿され、下書きとして保存されました。\n\n公開表示名：{display_name}\n卒業年：{graduation_year}\n卒業期：{graduation_term}\n\n管理画面で内容を確認してください。\n{edit_url}",
			'author_enabled' => 1,
			'author_subject' => '【AlumniVoice】ご投稿いただいた「卒業生の声」が公開されました',
			'author_message' => "{display_name} 様\n\nこのたびはAlumniVoiceへ「卒業生の声」をご投稿いただき、ありがとうございました。\n\nご投稿いただいた内容を確認し、公開いたしました。\n\nこちらからご覧いただけます。\n{permalink}\n\n今後ともよろしくお願いいたします。",
		);
	}

	public static function get_settings() {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function register() {
		add_action( 'transition_post_status', array( __CLASS__, 'maybe_send_publication_notification' ), 10, 3 );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'save_settings' ) );
	}

	public static function send_admin_new_submission( $post_id ) {
		$settings = self::get_settings();
		if ( empty( $settings['admin_enabled'] ) ) return;
		$emails = self::emails( $settings['admin_emails'] );
		if ( empty( $emails ) ) return;
		wp_mail( $emails, self::replace_tokens( $settings['admin_subject'], $post_id ), self::replace_tokens( $settings['admin_message'], $post_id ) );
	}

	public static function maybe_send_publication_notification( $new_status, $old_status, $post ) {
		if ( AlumniVoice_Post_Type::POST_TYPE !== $post->post_type || 'publish' !== $new_status || 'publish' === $old_status ) return;
		if ( get_post_meta( $post->ID, self::META_PUBLICATION_SENT, true ) ) return;
		$settings = self::get_settings();
		$email = sanitize_email( get_post_meta( $post->ID, '_alumni_voice_email', true ) );
		if ( empty( $settings['author_enabled'] ) || ! is_email( $email ) ) return;
		$sent = wp_mail( $email, self::replace_tokens( $settings['author_subject'], $post->ID ), self::replace_tokens( $settings['author_message'], $post->ID ) );
		if ( $sent ) update_post_meta( $post->ID, self::META_PUBLICATION_SENT, current_time( 'mysql' ) );
	}

	private static function emails( $value ) {
		$items = preg_split( '/[\s,;]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY );
		return array_values( array_unique( array_filter( array_map( 'sanitize_email', $items ), 'is_email' ) ) );
	}

	private static function replace_tokens( $text, $post_id ) {
		$map = array(
			'{display_name}' => (string) get_post_meta( $post_id, '_alumni_voice_display_name', true ),
			'{graduation_year}' => (string) get_post_meta( $post_id, '_alumni_voice_graduation_year', true ),
			'{graduation_term}' => (string) get_post_meta( $post_id, '_alumni_voice_graduation_term', true ),
			'{edit_url}' => get_edit_post_link( $post_id, '' ),
			'{permalink}' => get_permalink( $post_id ),
		);
		return strtr( (string) $text, $map );
	}

	public static function add_settings_page() {
		add_submenu_page( 'edit.php?post_type=alumni_voice', '通知設定', '通知設定', 'manage_options', 'alumni-voice-notifications', array( __CLASS__, 'render_page' ) );
	}

	public static function save_settings() {
		if ( ! isset( $_POST['alumni_voice_notifications_nonce'] ) || ! wp_verify_nonce( $_POST['alumni_voice_notifications_nonce'], 'alumni_voice_save_notifications' ) || ! current_user_can( 'manage_options' ) ) return;
		$raw = isset( $_POST['notifications'] ) ? (array) $_POST['notifications'] : array();
		$defaults = self::defaults();
		$settings = array(
			'admin_enabled' => ! empty( $raw['admin_enabled'] ) ? 1 : 0,
			'admin_emails' => sanitize_text_field( wp_unslash( $raw['admin_emails'] ?? '' ) ),
			'admin_subject' => sanitize_text_field( wp_unslash( $raw['admin_subject'] ?? $defaults['admin_subject'] ) ),
			'admin_message' => sanitize_textarea_field( wp_unslash( $raw['admin_message'] ?? $defaults['admin_message'] ) ),
			'author_enabled' => ! empty( $raw['author_enabled'] ) ? 1 : 0,
			'author_subject' => sanitize_text_field( wp_unslash( $raw['author_subject'] ?? $defaults['author_subject'] ) ),
			'author_message' => sanitize_textarea_field( wp_unslash( $raw['author_message'] ?? $defaults['author_message'] ) ),
		);
		update_option( self::OPTION, $settings );
		wp_safe_redirect( admin_url( 'edit.php?post_type=alumni_voice&page=alumni-voice-notifications&updated=1' ) );
		exit;
	}

	public static function render_page() {
		$s = self::get_settings();
		echo '<div class="wrap"><h1>AlumniVoice 通知設定</h1><p>投稿時の管理者通知と、初回公開時の投稿者通知を設定します。</p>';
		if ( isset( $_GET['updated'] ) ) echo '<div class="notice notice-success"><p>保存しました。</p></div>';
		echo '<form method="post">';
		wp_nonce_field( 'alumni_voice_save_notifications', 'alumni_voice_notifications_nonce' );
		echo '<h2>管理者への新規投稿通知</h2><table class="form-table"><tr><th>通知</th><td><label><input type="checkbox" name="notifications[admin_enabled]" value="1" ' . checked( ! empty( $s['admin_enabled'] ), true, false ) . '> 新しい投稿を通知する</label></td></tr>';
		self::row( '通知先メールアドレス', 'notifications[admin_emails]', $s['admin_emails'], '複数指定する場合はカンマまたは改行で区切ります。' );
		self::row( '件名', 'notifications[admin_subject]', $s['admin_subject'] );
		self::textarea( '本文', 'notifications[admin_message]', $s['admin_message'], '利用可能：{display_name} {graduation_year} {graduation_term} {edit_url}' );
		echo '</table><hr><h2>投稿者への公開通知</h2><table class="form-table"><tr><th>通知</th><td><label><input type="checkbox" name="notifications[author_enabled]" value="1" ' . checked( ! empty( $s['author_enabled'] ), true, false ) . '> 初回公開時に投稿者へ通知する</label></td></tr>';
		self::row( '件名', 'notifications[author_subject]', $s['author_subject'] );
		self::textarea( '本文', 'notifications[author_message]', $s['author_message'], '利用可能：{display_name} {permalink}' );
		echo '</table><p class="submit"><button class="button button-primary">通知設定を保存</button></p></form></div>';
	}

	private static function row( $label, $name, $value, $description = '' ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><input class="regular-text" type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		if ( $description ) echo '<p class="description">' . esc_html( $description ) . '</p>';
		echo '</td></tr>';
	}

	private static function textarea( $label, $name, $value, $description = '' ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><textarea class="large-text code" rows="8" name="' . esc_attr( $name ) . '">' . esc_textarea( $value ) . '</textarea>';
		if ( $description ) echo '<p class="description">' . esc_html( $description ) . '</p>';
		echo '</td></tr>';
	}
}
