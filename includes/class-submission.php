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
		$public_settings = AlumniVoice_Form_Settings::get_public_settings();
		$graduation_lookup_url = function_exists( 'alumni_core_get_graduation_lookup_url' )
			? (string) alumni_core_get_graduation_lookup_url()
			: '';

		?>
		<style>
		/* Hide the theme's duplicate page title only on the generated AlumniVoice submit page. */
		.alumni-voice-submit-page .entry-title,
		.alumni-voice-submit-page .page-title,
		.alumni-voice-submit-page .wp-block-post-title {
			display: none;
		}
		.alumni-voice-form {
			max-width: 1280px;
			margin: 0 auto;
			padding: 0 1rem 3rem;
			color: #22364f;
		}
		.alumni-voice-form *,
		.alumni-voice-form *::before,
		.alumni-voice-form *::after {
			box-sizing: border-box;
		}
		.alumni-voice-form__hero {
			margin: 0 -1rem 2rem;
			padding: 2.4rem max( 1rem, calc( ( 100vw - 1280px ) / 2 ) ) 2.4rem;
			background: linear-gradient( 105deg, #eef5fb 0%, #f8fbfe 58%, #edf6ef 100% );
			border-bottom: 1px solid #dbe6ef;
		}
		.alumni-voice-form__hero-inner {
			max-width: 1280px;
			margin: 0 auto;
			padding-left: 1.4rem;
			border-left: 6px solid #2e67a6;
		}
		.alumni-voice-form__hero-brand {
			margin: 0 0 .35rem;
			color: #2e67a6;
			font-size: .82rem;
			font-weight: 800;
			letter-spacing: .12em;
		}
		.alumni-voice-form__hero h1 {
			margin: 0 0 .45rem;
			font-size: clamp( 1.8rem, 4vw, 2.7rem );
			line-height: 1.2;
			letter-spacing: .04em;
		}
		.alumni-voice-form__hero p {
			margin: 0;
			font-size: 1.05rem;
		}
		.alumni-voice-form__section {
			margin: 1.5rem 0;
			padding: 1.6rem 1.8rem 1.9rem;
			background: #fff;
			border: 1px solid #d9e1e8;
			border-radius: 12px;
			box-shadow: 0 10px 28px rgba( 33, 54, 79, .06 );
		}
		.alumni-voice-form__section-title {
			margin: 0 0 .65rem;
			padding: 0 0 .5rem .8rem;
			border-bottom: 1px solid #c9d8e7;
			border-left: 7px solid #2e67a6;
			font-size: 1.45rem;
			line-height: 1.2;
		}
		.alumni-voice-form__notice {
			margin: .25rem 0;
			line-height: 1.75;
		}
		.alumni-voice-form__success {
			margin: 0 0 1.5rem;
			padding: 1rem 1.2rem;
			background: #edf8ef;
			border: 1px solid #b8d8bd;
			border-radius: 8px;
		}
		.alumni-voice-form__grid {
			display: grid;
			grid-template-columns: repeat( 12, minmax( 0, 1fr ) );
			gap: 1.1rem 1.6rem;
			margin-top: 1.35rem;
		}
		.alumni-voice-form__field {
			min-width: 0;
		}
		.alumni-voice-form__field label,
		.alumni-voice-form__question label {
			display: flex;
			align-items: center;
			gap: .55rem;
			font-weight: 700;
			margin-bottom: .5rem;
			color: #22364f;
		}
		.alumni-voice-form__required {
			display: inline-block;
			padding: .18rem .5rem;
			border-radius: 5px;
			background: #f8e9ec;
			color: #b33b4b;
			font-size: .78rem;
			font-weight: 700;
			line-height: 1.25;
		}
		.alumni-voice-form__field input,
		.alumni-voice-form__field select,
		.alumni-voice-form__question textarea {
			width: 100%;
			max-width: 100%;
			border: 1px solid #b8c7d4;
			border-radius: 7px;
			background: #fff;
			color: #22364f;
			box-shadow: inset 0 1px 2px rgba( 33, 54, 79, .03 );
		}
		.alumni-voice-form__field input,
		.alumni-voice-form__field select {
			height: 48px;
			padding: .65rem .8rem;
		}
		.alumni-voice-form__field input:focus,
		.alumni-voice-form__field select:focus,
		.alumni-voice-form__question textarea:focus {
			outline: 0;
			border-color: #2e67a6;
			box-shadow: 0 0 0 3px rgba( 46, 103, 166, .12 );
		}
		.alumni-voice-form__field--half { grid-column: span 6; }
		.alumni-voice-form__field--year { grid-column: span 3; }
		.alumni-voice-form__field--term { grid-column: span 3; }
		.alumni-voice-form__field--lookup {
			grid-column: span 3;
			display: flex;
			align-items: flex-end;
		}
		.alumni-voice-form__field--lookup a {
			padding-bottom: .8rem;
			color: #245fa2;
			font-weight: 700;
			text-decoration: none;
		}
		.alumni-voice-form__field--lookup a:hover { text-decoration: underline; }
		.alumni-voice-form__field--class { grid-column: span 3; }
		.alumni-voice-form__field--club { grid-column: span 4; }
		.alumni-voice-form__field--committee { grid-column: span 5; }
		.alumni-voice-form__field--career { grid-column: span 12; }
		.alumni-voice-form__field--career-place { grid-column: span 12; }
		.alumni-voice-form__field--job { grid-column: span 6; }
		.alumni-voice-form__field--current { grid-column: span 6; }
		.alumni-voice-form__career-options { display:flex; flex-wrap:wrap; gap:.7rem 1.2rem; }
		.alumni-voice-form__career-options label { margin:0; font-weight:600; }
		.alumni-voice-form__career-options input { width:auto; height:auto; margin-right:.35rem; }
		.alumni-voice-form__consent { margin-top:1.2rem; padding:1rem 1.2rem; background:#f5f8fc; border:1px solid #d6e1eb; border-radius:8px; }
		.alumni-voice-form__consent label { display:flex; align-items:flex-start; gap:.6rem; font-weight:700; line-height:1.65; }
		.alumni-voice-form__consent input { margin-top:.25rem; }
		.alumni-voice-form__input-with-suffix {
			display: flex;
			align-items: center;
			gap: .55rem;
		}
		.alumni-voice-form__input-with-suffix input { min-width: 0; }
		.alumni-voice-form__suffix {
			font-weight: 700;
			white-space: nowrap;
		}
		.alumni-voice-form__interview-description {
			margin: 0 0 1.15rem;
			line-height: 1.75;
		}
		.alumni-voice-form__question {
			margin-top: 1rem;
			padding: 1.1rem 1.25rem 1.2rem;
			background: #f5f8fc;
			border-radius: 9px;
		}
		.alumni-voice-form__question textarea {
			display: block;
			min-height: 190px;
			padding: .9rem 1rem;
			resize: vertical;
			line-height: 1.7;
		}
		.alumni-voice-form__submit {
			margin: 1.5rem 0 0;
			text-align: center;
		}
		.alumni-voice-form__submit button {
			min-width: 180px;
			padding: .9rem 1.8rem;
			border: 0;
			border-radius: 7px;
			background: #2e67a6;
			color: #fff;
			font-weight: 700;
			font-size: 1rem;
			cursor: pointer;
		}
		.alumni-voice-form__submit button:hover { opacity: .92; }
		@media ( max-width: 782px ) {
			.alumni-voice-form { padding: 0 .75rem 2rem; }
			.alumni-voice-form__hero { margin: 0 -.75rem 1.25rem; padding: 1.6rem .75rem; }
			.alumni-voice-form__section { padding: 1.2rem 1rem 1.35rem; border-radius: 9px; }
			.alumni-voice-form__grid { grid-template-columns: 1fr; gap: 1rem; }
			.alumni-voice-form__field,
			.alumni-voice-form__field--half,
			.alumni-voice-form__field--year,
			.alumni-voice-form__field--term,
			.alumni-voice-form__field--lookup,
			.alumni-voice-form__field--class,
			.alumni-voice-form__field--club,
			.alumni-voice-form__field--committee { grid-column: auto; }
			.alumni-voice-form__field--lookup { align-items: flex-start; }
			.alumni-voice-form__field--lookup a { padding-bottom: 0; }
		}
		</style>

		<div class="alumni-voice-form__hero"><div class="alumni-voice-form__hero-inner"><?php if ( '' !== trim( (string) $public_settings['hero_brand'] ) ) : ?><p class="alumni-voice-form__hero-brand"><?php echo esc_html( $public_settings['hero_brand'] ); ?></p><?php endif; ?><h1><?php echo esc_html( $public_settings['hero_title'] ); ?></h1><p><?php echo esc_html( $public_settings['hero_description'] ); ?></p></div></div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-voice-form">
			<?php if ( isset( $_GET['alumni_voice_submitted'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['alumni_voice_submitted'] ) ) ) : ?>
				<div class="alumni-voice-form__success">ご投稿を受け付けました。内容を確認のうえ、管理者が編集・公開します。</div>
			<?php endif; ?>
			<input type="hidden" name="action" value="alumni_voice_submit">
			<?php wp_nonce_field( 'alumni_voice_submit', 'alumni_voice_nonce' ); ?>

			<section class="alumni-voice-form__section alumni-voice-form__intro">
				<h2 class="alumni-voice-form__section-title">基本情報</h2>
				<p class="alumni-voice-form__notice">入力いただいた個人情報は、本校の同窓生であることを確認する用途で使用させていただきます。個人情報については開示されません。</p>
				<p class="alumni-voice-form__notice">いただいた連絡先情報は公開されませんが、今後の同窓会活動でご連絡を差し上げる場合があります。</p>
			</section>

			<div class="alumni-voice-form__grid">
				<div class="alumni-voice-form__field alumni-voice-form__field--half">
					<label for="alumni_voice_full_name">氏名 <span class="alumni-voice-form__required">必須</span></label>
					<input id="alumni_voice_full_name" type="text" name="full_name" placeholder="例）山田 太郎" required>
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--half">
					<label for="alumni_voice_furigana">ふりがな</label>
					<input id="alumni_voice_furigana" type="text" name="furigana" placeholder="例）やまだ たろう">
				</div>

				<div class="alumni-voice-form__field alumni-voice-form__field--half">
					<label for="alumni_voice_display_name">公開表示名 <span class="alumni-voice-form__required">必須</span></label>
					<input id="alumni_voice_display_name" type="text" name="display_name" placeholder="例）山田 太郎（旧3年2組）" required>
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--half">
					<label for="alumni_voice_email">メールアドレス <span class="alumni-voice-form__required">必須</span></label>
					<input id="alumni_voice_email" type="email" name="email" placeholder="例）example@example.com" required>
				</div>

				<div class="alumni-voice-form__field alumni-voice-form__field--year">
					<label for="alumni_voice_graduation_year">卒業年</label>
					<div class="alumni-voice-form__input-with-suffix"><input id="alumni_voice_graduation_year" type="number" name="graduation_year" min="1000" max="9999" inputmode="numeric" placeholder="2020"><span class="alumni-voice-form__suffix">年卒</span></div>
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--term">
					<label for="alumni_voice_graduation_term">卒業期</label>
					<div class="alumni-voice-form__input-with-suffix"><input id="alumni_voice_graduation_term" type="number" name="graduation_term" min="1" max="999" inputmode="numeric" placeholder="123"><span class="alumni-voice-form__suffix">期</span></div>
				</div>
				<?php if ( '' !== $graduation_lookup_url ) : ?>
					<div class="alumni-voice-form__field alumni-voice-form__field--lookup">
						<a href="<?php echo esc_url( $graduation_lookup_url ); ?>" target="_blank" rel="noopener noreferrer">卒業期早見表</a>
					</div>
				<?php endif; ?>

				<div class="alumni-voice-form__field alumni-voice-form__field--class">
					<label for="alumni_voice_class_name">組</label>
					<input id="alumni_voice_class_name" type="text" name="class_name" maxlength="6" size="6" placeholder="例）2">
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--club">
					<label for="alumni_voice_club_activity">部活動</label>
					<input id="alumni_voice_club_activity" type="text" name="club_activity" placeholder="例）サッカー部">
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--committee">
					<label for="alumni_voice_committee_activity">委員会</label>
					<input id="alumni_voice_committee_activity" type="text" name="committee_activity" placeholder="例）生徒会">
				</div>
			</div>

			<section class="alumni-voice-form__section alumni-voice-form__career-section">
				<h2 class="alumni-voice-form__section-title">卒業後・現在の情報</h2>
				<div class="alumni-voice-form__grid">
					<div class="alumni-voice-form__field alumni-voice-form__field--career">
						<label>卒業後の進路 <span class="alumni-voice-form__required">必須</span></label>
						<div class="alumni-voice-form__career-options">
							<label><input type="radio" name="career_path" value="進学" required>進学</label>
							<label><input type="radio" name="career_path" value="就職">就職</label>
							<label><input type="radio" name="career_path" value="その他">その他</label>
						</div>
					</div>
					<div class="alumni-voice-form__field alumni-voice-form__field--career-place">
						<label for="alumni_voice_career_place">卒業後の進路（勤務先／学校等）</label>
						<input id="alumni_voice_career_place" type="text" name="career_place" placeholder="例）○○大学 ○○学部、○○株式会社">
					</div>
					<div class="alumni-voice-form__field alumni-voice-form__field--job">
						<label for="alumni_voice_job">職種</label>
						<select id="alumni_voice_job" name="job"><option value="">選択してください</option><?php foreach ( get_terms( array( 'taxonomy' => 'alumni_voice_job', 'hide_empty' => false ) ) as $job_term ) : ?><option value="<?php echo esc_attr( $job_term->term_id ); ?>"><?php echo esc_html( $job_term->name ); ?></option><?php endforeach; ?></select>
					</div>
					<div class="alumni-voice-form__field alumni-voice-form__field--current">
						<label for="alumni_voice_current_affiliation">現在の所属（勤務先／学校等）</label>
						<input id="alumni_voice_current_affiliation" type="text" name="current_affiliation" placeholder="例）○○株式会社、○○大学大学院">
					</div>
				</div>
			</section>

			<section class="alumni-voice-form__section alumni-voice-form__interview">
				<h2 class="alumni-voice-form__section-title">インタビュー</h2>
				<p class="alumni-voice-form__interview-description">それぞれの質問に、できるだけ具体的にお答えください。在校生や卒業生のみなさんにとって、貴重なメッセージとなります。</p>
				<?php foreach ( $questions as $q ) : ?>
					<div class="alumni-voice-form__question">
						<label for="alumni_voice_answer_<?php echo esc_attr( $q['id'] ); ?>"><?php echo esc_html( $q['question'] ); ?><?php if ( ! empty( $q['required'] ) ) : ?> <span class="alumni-voice-form__required">必須</span><?php endif; ?></label>
						<textarea id="alumni_voice_answer_<?php echo esc_attr( $q['id'] ); ?>" rows="7" name="answers[<?php echo esc_attr( $q['id'] ); ?>]" maxlength="2000" placeholder="できるだけ具体的にご記入ください。"<?php echo ! empty( $q['required'] ) ? ' required' : ''; ?>></textarea>
					</div>
				<?php endforeach; ?>
			</section>

			<div class="alumni-voice-form__consent"><label><input type="checkbox" name="public_consent" value="1" required>投稿内容が、卒業生の声としてWebサイト上で公開されることに同意します。 <span class="alumni-voice-form__required">必須</span></label></div>
			<p class="alumni-voice-form__submit"><button type="submit">送信する</button></p>
		</form>
		<?php

		return ob_get_clean();
	}

	public static function submit() {
		if ( ! isset( $_POST['alumni_voice_nonce'] ) || ! wp_verify_nonce( $_POST['alumni_voice_nonce'], 'alumni_voice_submit' ) ) wp_die( '不正な送信です。' );
		$name = sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) );
		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$display = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
		$year_input = absint( $_POST['graduation_year'] ?? 0 );
		$term_input = absint( $_POST['graduation_term'] ?? 0 );
		$career_path = sanitize_text_field( wp_unslash( $_POST['career_path'] ?? '' ) );
		if ( '' === $name || '' === $display || ! is_email( $email ) || ( $year_input <= 0 && $term_input <= 0 ) || ! in_array( $career_path, array( '進学', '就職', 'その他' ), true ) || empty( $_POST['public_consent'] ) ) wp_die( '必須項目を確認してください。' );
		$post_id = wp_insert_post( array( 'post_type'=>AlumniVoice_Post_Type::POST_TYPE, 'post_status'=>'draft', 'post_title'=>$display, 'post_content'=>'' ), true );
		if ( is_wp_error( $post_id ) ) wp_die( '保存に失敗しました。' );
		$fields = array('full_name','furigana','class_name','club_activity','committee_activity','display_name','career_place','current_affiliation');
		foreach($fields as $key) update_post_meta($post_id,'_alumni_voice_'.$key,sanitize_text_field(wp_unslash($_POST[$key]??'')));
		update_post_meta($post_id,'_alumni_voice_email',$email);
		$year=$year_input; $term=$term_input;
		if($year>0&&$term<=0) $term=absint(apply_filters('alumni_core_graduation_term_from_year',null,$year,$post_id));
		elseif($term>0&&$year<=0&&function_exists('alumni_core_graduation_term_to_year')) $year=absint(alumni_core_graduation_term_to_year($term));
		if($year>0) update_post_meta($post_id,'_alumni_voice_graduation_year',$year);
		if($term>0) update_post_meta($post_id,'_alumni_voice_graduation_term',$term);
		update_post_meta($post_id,'_alumni_voice_public_consent',1);
		wp_set_object_terms($post_id,$career_path,'alumni_voice_career',false);
		$job_id=absint($_POST['job']??0); if($job_id>0) wp_set_object_terms($post_id,array($job_id),'alumni_voice_job',false); else wp_set_object_terms($post_id,array(),'alumni_voice_job',false);
		$answers=(array)($_POST['answers']??array());
		foreach(AlumniVoice_Form_Settings::get_questions() as $q){$answer=sanitize_textarea_field(wp_unslash($answers[$q['id']]??''));if(!empty($q['required'])&&''===$answer){wp_delete_post($post_id,true);wp_die('必須の質問に回答してください。');}update_post_meta($post_id,'_alumni_voice_answer_'.$q['id'],$answer);}
		wp_safe_redirect(add_query_arg('alumni_voice_submitted','1',wp_get_referer()?:home_url('/')));
		exit;
	}
}
