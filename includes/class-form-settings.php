<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlumniVoice_Form_Settings {
	const OPTION = 'alumni_voice_interview_questions';
	const OPTION_PUBLIC = 'alumni_voice_public_page_settings';

	public static function defaults() {
		return array(
			array( 'id' => 'reason', 'question' => '今の進路を選んだきっかけは何ですか？', 'required' => true ),
			array( 'id' => 'regret', 'question' => '高校時代にやっておけばよかったと思うことは？', 'required' => false ),
			array( 'id' => 'message', 'question' => '在校生に向けて一言。', 'required' => true ),
		);
	}
	public static function public_defaults() {
		return array(
			'page_title'       => '卒業生の声を投稿する',
			'content_label'    => '卒業生の声を投稿する',
			'hero_brand'       => 'AlumniVoice',
			'hero_title'       => '卒業生の声を投稿する',
			'hero_description' => 'みなさんの経験やメッセージが、在校生の未来につながります。',
		);
	}
	public static function get_public_settings() {
		$saved = get_option( self::OPTION_PUBLIC, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::public_defaults() );
	}
	public static function get_questions() {
		$questions = get_option( self::OPTION, null );
		return is_array( $questions ) ? $questions : self::defaults();
	}
	public static function ensure_defaults() {
		if ( false === get_option( self::OPTION, false ) ) add_option( self::OPTION, self::defaults() );
		if ( false === get_option( self::OPTION_PUBLIC, false ) ) add_option( self::OPTION_PUBLIC, self::public_defaults() );
	}
	public static function register() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'save' ) );
	}
	public static function menu() {
		add_submenu_page( 'edit.php?post_type=alumni_voice', '投稿フォーム設定', '投稿フォーム設定', 'manage_options', 'alumni-voice-form', array( __CLASS__, 'page' ) );
	}
	public static function save() {
		if ( ! isset( $_POST['alumni_voice_questions_nonce'] ) || ! wp_verify_nonce( $_POST['alumni_voice_questions_nonce'], 'alumni_voice_save_questions' ) ) return;
		if ( ! current_user_can( 'manage_options' ) ) return;
		$public = isset( $_POST['public'] ) ? (array) $_POST['public'] : array();
		$settings = array(
			'page_title'       => sanitize_text_field( wp_unslash( $public['page_title'] ?? '' ) ),
			'content_label'    => sanitize_text_field( wp_unslash( $public['content_label'] ?? '' ) ),
			'hero_brand'       => sanitize_text_field( wp_unslash( $public['hero_brand'] ?? '' ) ),
			'hero_title'       => sanitize_text_field( wp_unslash( $public['hero_title'] ?? '' ) ),
			'hero_description' => sanitize_text_field( wp_unslash( $public['hero_description'] ?? '' ) ),
		);
		$settings = wp_parse_args( array_filter( $settings, static function( $value ) { return '' !== $value; } ), self::public_defaults() );
		update_option( self::OPTION_PUBLIC, $settings );
		if ( class_exists( 'AlumniVoice_Public_Content' ) ) {
			$page_id = (int) get_option( AlumniVoice_Public_Content::OPTION_SUBMIT_PAGE, 0 );
			if ( $page_id && 'page' === get_post_type( $page_id ) ) {
				wp_update_post( array( 'ID' => $page_id, 'post_title' => $settings['page_title'] ) );
			}
		}

		$rows = isset( $_POST['questions'] ) ? (array) $_POST['questions'] : array();
		$questions = array();
		foreach ( $rows as $row ) {
			$text = isset( $row['question'] ) ? sanitize_textarea_field( wp_unslash( $row['question'] ) ) : '';
			if ( '' === $text ) continue;
			$questions[] = array(
				'id' => sanitize_key( $row['id'] ?? wp_generate_uuid4() ),
				'question' => $text,
				'required' => ! empty( $row['required'] ),
			);
		}
		update_option( self::OPTION, array_values( $questions ) );
		wp_safe_redirect( admin_url( 'edit.php?post_type=alumni_voice&page=alumni-voice-form&updated=1' ) );
		exit;
	}
	public static function page() {
		$questions = self::get_questions();
		$public = self::get_public_settings();
		echo '<div class="wrap"><h1>AlumniVoice 投稿フォーム設定</h1><p>公開ページの文言とインタビュー質問を自由に変更できます。</p>';
		if ( isset( $_GET['updated'] ) ) echo '<div class="notice notice-success"><p>保存しました。</p></div>';
		echo '<form method="post"><input type="hidden" name="action" value="alumni_voice_save_questions">';
		wp_nonce_field( 'alumni_voice_save_questions', 'alumni_voice_questions_nonce' );
		echo '<h2>公開ページ設定</h2><table class="form-table" role="presentation"><tbody>';
		echo '<tr><th scope="row"><label for="av-page-title">ページ名</label></th><td><input id="av-page-title" class="regular-text" type="text" name="public[page_title]" value="' . esc_attr( $public['page_title'] ) . '"><p class="description">WordPress上の固定ページ名です。</p></td></tr>';
		echo '<tr><th scope="row"><label for="av-content-label">AlumniCoreのリンク表示名</label></th><td><input id="av-content-label" class="regular-text" type="text" name="public[content_label]" value="' . esc_attr( $public['content_label'] ) . '"><p class="description">トップ画面・メニューの「コンテンツリンク」に表示される名前です。</p></td></tr>';
		echo '<tr><th scope="row"><label for="av-hero-brand">ブランド名</label></th><td><input id="av-hero-brand" class="regular-text" type="text" name="public[hero_brand]" value="' . esc_attr( $public['hero_brand'] ) . '"><p class="description">フォーム上部に小さく表示します。例：AlumniVoice</p></td></tr>';
		echo '<tr><th scope="row"><label for="av-hero-title">フォーム見出し</label></th><td><input id="av-hero-title" class="regular-text" type="text" name="public[hero_title]" value="' . esc_attr( $public['hero_title'] ) . '"></td></tr>';
		echo '<tr><th scope="row"><label for="av-hero-description">説明文</label></th><td><input id="av-hero-description" class="large-text" type="text" name="public[hero_description]" value="' . esc_attr( $public['hero_description'] ) . '"></td></tr>';
		echo '</tbody></table><hr><h2>インタビュー質問</h2><table class="widefat"><thead><tr><th>質問</th><th style="width:90px">必須</th><th style="width:90px"></th></tr></thead><tbody id="av-questions">';
		foreach ( $questions as $i => $q ) self::row( $q, $i );
		echo '</tbody></table><p><button type="button" class="button" id="av-add">質問を追加</button> <button class="button button-primary">保存</button></p></form>';
		echo '<script>document.getElementById("av-add").onclick=function(){var n=document.querySelectorAll("#av-questions tr").length,id="q_"+Date.now(),tr=document.createElement("tr");tr.innerHTML="<td><input type=hidden name=questions["+n+"][id] value="+id+"><textarea class=large-text name=questions["+n+"][question]></textarea></td><td><input type=checkbox name=questions["+n+"][required] value=1></td><td><button type=button class=button onclick=this.closest(\'tr\').remove()>削除</button></td>";document.getElementById("av-questions").appendChild(tr)};</script></div>';
	}
	private static function row( $q, $i ) {
		echo '<tr><td><input type="hidden" name="questions[' . esc_attr( $i ) . '][id]" value="' . esc_attr( $q['id'] ) . '"><textarea class="large-text" name="questions[' . esc_attr( $i ) . '][question]">' . esc_textarea( $q['question'] ) . '</textarea></td><td><input type="checkbox" name="questions[' . esc_attr( $i ) . '][required]" value="1" ' . checked( ! empty( $q['required'] ), true, false ) . '></td><td><button type="button" class="button" onclick="this.closest(\'tr\').remove()">削除</button></td></tr>';
	}
}
