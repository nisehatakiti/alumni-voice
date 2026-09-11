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
			'page_title'            => '卒業生の声を投稿する',
			'content_label'         => '卒業生の声を投稿する',
			'hero_brand'            => 'AlumniVoice',
			'hero_title'            => '卒業生の声を投稿する',
			'hero_description'      => 'みなさんの経験やメッセージが、在校生の未来につながります。',
			'list_page_title'       => '卒業生の声を見る',
			'list_content_label'    => '卒業生の声を見る',
			'list_hero_brand'       => 'AlumniVoice',
			'list_hero_title'       => '卒業生の声を見る',
			'list_hero_description' => '先輩たちの経験やメッセージが、在校生の未来につながります。',
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
		$settings = array();
		foreach ( array_keys( self::public_defaults() ) as $key ) {
			$settings[ $key ] = sanitize_text_field( wp_unslash( $public[ $key ] ?? '' ) );
		}
		$settings = wp_parse_args(
			array_filter( $settings, static function( $value ) { return '' !== $value; } ),
			self::public_defaults()
		);
		update_option( self::OPTION_PUBLIC, $settings );

		if ( class_exists( 'AlumniVoice_Public_Content' ) ) {
			$submit_page_id = (int) get_option( AlumniVoice_Public_Content::OPTION_SUBMIT_PAGE, 0 );
			if ( $submit_page_id && 'page' === get_post_type( $submit_page_id ) ) {
				wp_update_post( array( 'ID' => $submit_page_id, 'post_title' => $settings['page_title'] ) );
			}
			$list_page_id = (int) get_option( AlumniVoice_Public_Content::OPTION_LIST_PAGE, 0 );
			if ( $list_page_id && 'page' === get_post_type( $list_page_id ) ) {
				wp_update_post( array( 'ID' => $list_page_id, 'post_title' => $settings['list_page_title'] ) );
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

		echo '<h2>公開ページ設定：投稿する</h2><table class="form-table" role="presentation"><tbody>';
		self::text_row( 'av-page-title', 'ページ名', 'public[page_title]', $public['page_title'], 'WordPress上の固定ページ名です。' );
		self::text_row( 'av-content-label', 'AlumniCoreのリンク表示名', 'public[content_label]', $public['content_label'], 'トップ画面・メニューの「コンテンツリンク」に表示される名前です。' );
		self::text_row( 'av-hero-brand', 'ブランド名', 'public[hero_brand]', $public['hero_brand'], 'フォーム上部に小さく表示します。例：AlumniVoice' );
		self::text_row( 'av-hero-title', 'フォーム見出し', 'public[hero_title]', $public['hero_title'] );
		self::text_row( 'av-hero-description', '説明文', 'public[hero_description]', $public['hero_description'], '', 'large-text' );
		echo '</tbody></table>';

		echo '<h2>公開ページ設定：卒業生の声を見る</h2><table class="form-table" role="presentation"><tbody>';
		self::text_row( 'av-list-page-title', 'ページ名', 'public[list_page_title]', $public['list_page_title'], 'WordPress上の固定ページ名です。' );
		self::text_row( 'av-list-content-label', 'AlumniCoreのリンク表示名', 'public[list_content_label]', $public['list_content_label'], 'トップ画面・メニューの「コンテンツリンク」に表示される名前です。' );
		self::text_row( 'av-list-hero-brand', 'ブランド名', 'public[list_hero_brand]', $public['list_hero_brand'], '一覧ページ上部に表示します。例：AlumniVoice' );
		self::text_row( 'av-list-hero-title', 'ページ見出し', 'public[list_hero_title]', $public['list_hero_title'] );
		self::text_row( 'av-list-hero-description', '説明文', 'public[list_hero_description]', $public['list_hero_description'], '', 'large-text' );
		echo '</tbody></table><hr>';

		echo '<h2>インタビュー質問</h2><table class="widefat"><thead><tr><th>質問</th><th style="width:90px">必須</th><th style="width:90px"></th></tr></thead><tbody id="av-questions">';
		foreach ( $questions as $i => $q ) self::row( $q, $i );
		echo '</tbody></table><p><button type="button" class="button" id="av-add">質問を追加</button> <button class="button button-primary">保存</button></p></form>';
		echo '<script>document.getElementById("av-add").onclick=function(){var n=document.querySelectorAll("#av-questions tr").length,id="q_"+Date.now(),tr=document.createElement("tr");tr.innerHTML="<td><input type=hidden name=questions["+n+"][id] value="+id+"><textarea class=large-text name=questions["+n+"][question]></textarea></td><td><input type=checkbox name=questions["+n+"][required] value=1></td><td><button type=button class=button onclick=this.closest(\'tr\').remove()>削除</button></td>";document.getElementById("av-questions").appendChild(tr)};</script></div>';
	}

	private static function text_row( $id, $label, $name, $value, $description = '', $class = 'regular-text' ) {
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th><td><input id="' . esc_attr( $id ) . '" class="' . esc_attr( $class ) . '" type="text" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
		if ( '' !== $description ) echo '<p class="description">' . esc_html( $description ) . '</p>';
		echo '</td></tr>';
	}

	private static function row( $q, $i ) {
		echo '<tr><td><input type="hidden" name="questions[' . esc_attr( $i ) . '][id]" value="' . esc_attr( $q['id'] ) . '"><textarea class="large-text" name="questions[' . esc_attr( $i ) . '][question]">' . esc_textarea( $q['question'] ) . '</textarea></td><td><input type="checkbox" name="questions[' . esc_attr( $i ) . '][required]" value="1" ' . checked( ! empty( $q['required'] ), true, false ) . '></td><td><button type="button" class="button" onclick="this.closest(\'tr\').remove()">削除</button></td></tr>';
	}
}
