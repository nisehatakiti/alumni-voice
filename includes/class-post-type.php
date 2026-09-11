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
			'graduation_term' => '卒業期（どちらか一方を入力すると自動補完）',
			'class_name' => '組',
			'club_activity' => '部活動',
			'committee_activity' => '委員会',
			'email' => 'メールアドレス',
			'display_name' => '公開表示名',
			'career_place' => '卒業後の進路（勤務先／学校等）',
			'current_affiliation' => '現在の所属（勤務先／学校等）',
		);

		$career_terms = wp_get_object_terms( $post->ID, 'alumni_voice_career', array( 'fields' => 'ids' ) );
		$job_terms = get_terms( array( 'taxonomy' => 'alumni_voice_job', 'hide_empty' => false ) );
		echo '<p><label style="display:block;font-weight:600;margin-bottom:4px;">卒業後の進路</label><select class="widefat" name="alumni_voice_career_path"><option value="">選択してください</option>';
		foreach ( array( '進学', '就職', 'その他' ) as $career_label ) {
			$term = get_term_by( 'name', $career_label, 'alumni_voice_career' );
			if ( ! $term ) $term = wp_insert_term( $career_label, 'alumni_voice_career' );
			$term_id = is_wp_error( $term ) ? 0 : ( is_object( $term ) ? $term->term_id : (int) $term['term_id'] );
			echo '<option value="' . esc_attr( $term_id ) . '" ' . selected( in_array( $term_id, $career_terms, true ), true, false ) . '>' . esc_html( $career_label ) . '</option>';
		}
		echo '</select></p>';
		echo '<p><label style="display:block;font-weight:600;margin-bottom:4px;">職種</label><select class="widefat" name="alumni_voice_job"><option value="">選択してください</option>';
		$current_jobs = wp_get_object_terms( $post->ID, 'alumni_voice_job', array( 'fields' => 'ids' ) );
		foreach ( $job_terms as $job ) echo '<option value="' . esc_attr( $job->term_id ) . '" ' . selected( in_array( $job->term_id, $current_jobs, true ), true, false ) . '>' . esc_html( $job->name ) . '</option>';
		echo '</select></p>';

		foreach ( $fields as $key => $label ) {
			$value = get_post_meta( $post->ID, '_alumni_voice_' . $key, true );
			$type = $key === 'email' ? 'email' : ( $key === 'graduation_year' ? 'number' : 'text' );
			echo '<p><label style="display:block;font-weight:600;margin-bottom:4px;">' . esc_html( $label ) . '</label>';
			echo '<input type="' . esc_attr( $type ) . '" class="widefat" name="alumni_voice_' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"></p>';
		}

		
	}

	public static function save_meta( $post_id ) {
		if ( ! isset( $_POST['alumni_voice_profile_nonce'] ) || ! wp_verify_nonce( $_POST['alumni_voice_profile_nonce'], 'alumni_voice_profile' ) ) return;
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		foreach ( array( 'full_name', 'furigana', 'class_name', 'club_activity', 'committee_activity', 'display_name', 'career_place', 'current_affiliation' ) as $key ) {
			if ( isset( $_POST[ 'alumni_voice_' . $key ] ) ) {
				update_post_meta( $post_id, '_alumni_voice_' . $key, sanitize_text_field( wp_unslash( $_POST[ 'alumni_voice_' . $key ] ) ) );
			}
		}

		if ( isset( $_POST['alumni_voice_career_path'] ) ) {
			$career_id = absint( $_POST['alumni_voice_career_path'] );
			wp_set_object_terms( $post_id, $career_id ? array( $career_id ) : array(), 'alumni_voice_career', false );
		}
		if ( isset( $_POST['alumni_voice_job'] ) ) {
			$job_id = absint( $_POST['alumni_voice_job'] );
			wp_set_object_terms( $post_id, $job_id ? array( $job_id ) : array(), 'alumni_voice_job', false );
		}

		if ( isset( $_POST['alumni_voice_email'] ) ) {
			update_post_meta( $post_id, '_alumni_voice_email', sanitize_email( wp_unslash( $_POST['alumni_voice_email'] ) ) );
		}

		$year = isset( $_POST['alumni_voice_graduation_year'] ) ? absint( $_POST['alumni_voice_graduation_year'] ) : 0;
		$term = isset( $_POST['alumni_voice_graduation_term'] ) ? absint( $_POST['alumni_voice_graduation_term'] ) : 0;

		// Prefer the value explicitly entered by the editor and use Alumni Core
		// to complement the missing side.
		if ( $year > 0 && $term <= 0 ) {
			$resolved_term = apply_filters( 'alumni_core_graduation_term_from_year', null, $year, $post_id );
			if ( null !== $resolved_term && '' !== $resolved_term ) {
				$term = absint( $resolved_term );
			}
		} elseif ( $term > 0 && $year <= 0 && function_exists( 'alumni_core_graduation_term_to_year' ) ) {
			$resolved_year = alumni_core_graduation_term_to_year( $term );
			if ( null !== $resolved_year && '' !== $resolved_year ) {
				$year = absint( $resolved_year );
			}
		}

		if ( $year > 0 ) {
			update_post_meta( $post_id, '_alumni_voice_graduation_year', $year );
		} else {
			delete_post_meta( $post_id, '_alumni_voice_graduation_year' );
		}

		if ( $term > 0 ) {
			update_post_meta( $post_id, '_alumni_voice_graduation_term', $term );
		} else {
			delete_post_meta( $post_id, '_alumni_voice_graduation_term' );
		}
	}
}
