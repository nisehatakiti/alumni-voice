<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlumniVoice_Submission {
	public static function register() {
		add_shortcode( 'alumni_voice_form', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_post_nopriv_alumni_voice_submit', array( __CLASS__, 'submit' ) );
		add_action( 'admin_post_alumni_voice_submit', array( __CLASS__, 'submit' ) );
	}
	public static function shortcode() {
		ob_start();
		$questions = AlumniVoice_Form_Settings::get_questions();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="alumni-voice-form">';
		echo '<input type="hidden" name="action" value="alumni_voice_submit">';
		wp_nonce_field( 'alumni_voice_submit', 'alumni_voice_nonce' );
		echo '<h2>個人情報</h2><p>入力いただいた個人情報は、本校の同窓生であることを確認する用途で使用させていただきます。個人情報については開示されません。</p><p>いただいた連絡先情報は公開されませんが、今後の同窓会活動でご連絡を差し上げる場合があります。</p>';
		foreach ( array( 'full_name'=>'氏名','furigana'=>'ふりがな','graduation_year'=>'卒業年','graduation_term'=>'卒業期','class_name'=>'組','club_activity'=>'部活動','committee_activity'=>'委員会','email'=>'メールアドレス','display_name'=>'公開表示名' ) as $key=>$label ) {
			$type = $key === 'email' ? 'email' : ( in_array( $key, array('graduation_year','graduation_term'), true ) ? 'number' : 'text' );
			echo '<p><label>' . esc_html( $label ) . '<br><input type="' . esc_attr( $type ) . '" name="' . esc_attr( $key ) . '" class="regular-text"' . ( in_array( $key,array('full_name','email','display_name'),true) ? ' required' : '' ) . '></label></p>';
		}
		echo '<h2>インタビュー</h2>';
		foreach ( $questions as $q ) echo '<p><label>' . esc_html( $q['question'] ) . '<br><textarea class="large-text" rows="5" name="answers[' . esc_attr( $q['id'] ) . ']"' . ( ! empty( $q['required'] ) ? ' required' : '' ) . '></textarea></label></p>';
		echo '<p><button type="submit">送信する</button></p></form>';
		return ob_get_clean();
	}
	public static function submit() {
		if ( ! isset( $_POST['alumni_voice_nonce'] ) || ! wp_verify_nonce( $_POST['alumni_voice_nonce'], 'alumni_voice_submit' ) ) wp_die( '不正な送信です。' );
		$name = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$display = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
		if ( '' === $name || '' === $display || ! is_email( $email ) ) wp_die( '必須項目を確認してください。' );
		$post_id = wp_insert_post( array( 'post_type'=>AlumniVoice_Post_Type::POST_TYPE, 'post_status'=>'draft', 'post_title'=>$display, 'post_content'=>'' ), true );
		if ( is_wp_error( $post_id ) ) wp_die( '保存に失敗しました。' );
		$fields = array('full_name','furigana','class_name','club_activity','committee_activity','display_name');
		foreach($fields as $key) update_post_meta($post_id,'_alumni_voice_'.$key,sanitize_text_field(wp_unslash($_POST[$key]??'')));
		update_post_meta($post_id,'_alumni_voice_email',$email);
		$year=absint($_POST['graduation_year']??0); $term=absint($_POST['graduation_term']??0);
		if($year>0&&$term<=0) $term=absint(apply_filters('alumni_core_graduation_term_from_year',null,$year,$post_id));
		elseif($term>0&&$year<=0&&function_exists('alumni_core_graduation_term_to_year')) $year=absint(alumni_core_graduation_term_to_year($term));
		if($year>0) update_post_meta($post_id,'_alumni_voice_graduation_year',$year);
		if($term>0) update_post_meta($post_id,'_alumni_voice_graduation_term',$term);
		$answers=(array)($_POST['answers']??array());
		foreach(AlumniVoice_Form_Settings::get_questions() as $q){$answer=sanitize_textarea_field(wp_unslash($answers[$q['id']]??''));if(!empty($q['required'])&&''===$answer){wp_delete_post($post_id,true);wp_die('必須の質問に回答してください。');}update_post_meta($post_id,'_alumni_voice_answer_'.$q['id'],$answer);}
		wp_safe_redirect(add_query_arg('alumni_voice_submitted','1',wp_get_referer()?:home_url('/')));
		exit;
	}
}
