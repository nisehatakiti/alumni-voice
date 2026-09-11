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
		add_meta_box( 'alumni_voice_profile', '構造化プロフィール', array( __CLASS__, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_meta_box( $post ) {
		wp_nonce_field( 'alumni_voice_profile', 'alumni_voice_profile_nonce' );
		$fields = array(
			'graduation_year' => '卒業年',
			'graduation_term' => '卒業期',
			'school_destination' => '進学先',
			'workplace' => '勤務先',
			'person_id' => '人物ID（将来の名簿連携用）',
		);
		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, '_alumni_voice_' . $key, true );
			echo '<p><label style="display:block;font-weight:600;margin-bottom:4px;">' . esc_html( $label ) . '</label>';
			echo '<input type="text" class="widefat" name="alumni_voice_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"></p>';
		}
	}

	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['alumni_voice_profile_nonce'] ) || ! wp_verify_nonce( $_POST['alumni_voice_profile_nonce'], 'alumni_voice_profile' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;
		foreach ( array( 'graduation_year', 'graduation_term', 'school_destination', 'workplace', 'person_id' ) as $key ) {
			if ( isset( $_POST[ 'alumni_voice_' . $key ] ) ) {
				update_post_meta( $post_id, '_alumni_voice_' . $key, sanitize_text_field( wp_unslash( $_POST[ 'alumni_voice_' . $key ] ) ) );
			}
		}
	}
}
