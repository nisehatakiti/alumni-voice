<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlumniVoice_Post_Type {
	const POST_TYPE = 'alumni_voice';

	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomies' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ) );
	}

	public static function register_post_type() {
		register_post_type( self::POST_TYPE, array(
			'labels' => array(
				'name' => '卒業生の声',
				'singular_name' => '卒業生の声',
				'add_new' => '新規追加',
				'add_new_item' => '卒業生の声を追加',
				'edit_item' => '卒業生の声を編集',
				'view_item' => '卒業生の声を表示',
				'search_items' => '卒業生の声を検索',
				'not_found' => '投稿がありません',
				'menu_name' => '卒業生の声',
			),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'has_archive' => true,
			'rewrite' => array( 'slug' => 'alumni-voice' ),
			'show_in_rest' => true,
		) );
	}

	public static function register_taxonomies() {
		register_taxonomy( 'alumni_voice_job', self::POST_TYPE, array(
			'label' => '職種',
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_rest' => true,
		) );
		register_taxonomy( 'alumni_voice_career', self::POST_TYPE, array(
			'label' => '進路区分',
			'hierarchical' => true,
			'show_ui' => true,
			'show_in_rest' => true,
		) );
	}

	public static function add_meta_boxes() {
		add_meta_box( 'alumni_voice_profile', '個人情報・公開情報', array( __CLASS__, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'alumni_voice_profile', 'alumni_voice_profile_nonce' );

		echo '<div class="notice notice-info inline"><p>入力いただいた個人情報は、本校の同窓生であることを確認する用途で使用させていただきます。個人情報については開示されません。</p><p>いただいた連絡先情報は公開されませんが、今後の同窓会活動でご連絡を差し上げる場合があります。</p></div>';

		$fields = array(
			'full_name' => '氏名',
			'furigana' => 'ふりがな',
			'graduation_year' => '卒業年',
			'class_name' => '組',
			'email' => 'メールアドレス',
			'display_name' => '公開表示名',
		);

		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, '_alumni_voice_' . $key, true );
			$type = $key === 'email' ? 'email' : ( $key === 'graduation_year' ? 'number' : 'text' );
			echo '<p><label style="display:block;font-weight:600;margin-bottom:4px;">' . esc_html( $label ) . '</label>';
			echo '<input type="' . esc_attr( $type ) . '" class="widefat" name="alumni_voice_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"></p>';
		}

		$term = get_post_meta( $post->ID, '_alumni_voice_graduation_term', true );
		if ( $term !== '' ) {
			echo '<p><strong>卒業期（Alumni Coreから自動補完）</strong><br>' . esc_html( $term ) . '期</p>';
		}
	}

	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['alumni_voice_profile_nonce'] ) || ! wp_verify_nonce( $_POST['alumni_voice_profile_nonce'], 'alumni_voice_profile' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		foreach ( array( 'full_name', 'furigana', 'class_name', 'display_name' ) as $key ) {
			if ( isset( $_POST[ 'alumni_voice_' . $key ] ) ) {
				update_post_meta( $post_id, '_alumni_voice_' . $key, sanitize_text_field( wp_unslash( $_POST[ 'alumni_voice_' . $key ] ) ) );
			}
		}

		if ( isset( $_POST['alumni_voice_email'] ) ) {
			update_post_meta( $post_id, '_alumni_voice_email', sanitize_email( wp_unslash( $_POST['alumni_voice_email'] ) ) );
		}

		if ( isset( $_POST['alumni_voice_graduation_year'] ) ) {
			$year = absint( $_POST['alumni_voice_graduation_year'] );
			update_post_meta( $post_id, '_alumni_voice_graduation_year', $year );

			/**
			 * Alumni Core integrations can return the graduation term calculated
			 * from the saved school baseline and graduation year.
			 */
			$term = apply_filters( 'alumni_core_graduation_term_from_year', null, $year, $post_id );
			if ( null !== $term && '' !== $term ) {
				update_post_meta( $post_id, '_alumni_voice_graduation_term', absint( $term ) );
			}
		}
	}
}
