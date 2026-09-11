<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlumniVoice_Form_Settings {
	const OPTION = 'alumni_voice_interview_questions';

	public static function defaults() {
		return array(
			array( 'id' => 'reason', 'question' => '今の進路を選んだきっかけは何ですか？', 'required' => true ),
			array( 'id' => 'regret', 'question' => '高校時代にやっておけばよかったと思うことは？', 'required' => false ),
			array( 'id' => 'message', 'question' => '在校生に向けて一言。', 'required' => true ),
		);
	}
	public static function get_questions() {
		$questions = get_option( self::OPTION, null );
		return is_array( $questions ) ? $questions : self::defaults();
	}
	public static function ensure_defaults() {
		if ( false === get_option( self::OPTION, false ) ) add_option( self::OPTION, self::defaults() );
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
		echo '<div class="wrap"><h1>卒業生の声 投稿フォーム設定</h1><p>初期質問は自由に変更・追加・削除できます。</p>';
		if ( isset( $_GET['updated'] ) ) echo '<div class="notice notice-success"><p>保存しました。</p></div>';
		echo '<form method="post"><input type="hidden" name="action" value="alumni_voice_save_questions">';
		wp_nonce_field( 'alumni_voice_save_questions', 'alumni_voice_questions_nonce' );
		echo '<table class="widefat"><thead><tr><th>質問</th><th style="width:90px">必須</th><th style="width:90px"></th></tr></thead><tbody id="av-questions">';
		foreach ( $questions as $i => $q ) self::row( $q, $i );
		echo '</tbody></table><p><button type="button" class="button" id="av-add">質問を追加</button> <button class="button button-primary">保存</button></p></form>';
		echo '<script>document.getElementById("av-add").onclick=function(){var n=document.querySelectorAll("#av-questions tr").length,id="q_"+Date.now(),tr=document.createElement("tr");tr.innerHTML="<td><input type=hidden name=questions["+n+"][id] value="+id+"><textarea class=large-text name=questions["+n+"][question]></textarea></td><td><input type=checkbox name=questions["+n+"][required] value=1></td><td><button type=button class=button onclick=this.closest(\'tr\').remove()>削除</button></td>";document.getElementById("av-questions").appendChild(tr)};</script></div>';
	}
	private static function row( $q, $i ) {
		echo '<tr><td><input type="hidden" name="questions[' . esc_attr( $i ) . '][id]" value="' . esc_attr( $q['id'] ) . '"><textarea class="large-text" name="questions[' . esc_attr( $i ) . '][question]">' . esc_textarea( $q['question'] ) . '</textarea></td><td><input type="checkbox" name="questions[' . esc_attr( $i ) . '][required]" value="1" ' . checked( ! empty( $q['required'] ), true, false ) . '></td><td><button type="button" class="button" onclick="this.closest(\'tr\').remove()">削除</button></td></tr>';
	}
}
