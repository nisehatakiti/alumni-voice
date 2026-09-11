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
		$graduation_lookup_url = function_exists( 'alumni_core_get_graduation_lookup_url' )
			? (string) alumni_core_get_graduation_lookup_url()
			: '';

		?>
		<style>
		.alumni-voice-form {
			max-width: 1100px;
			margin: 0 auto;
		}
		.alumni-voice-form__intro {
			margin-bottom: 2rem;
			padding-bottom: 1.5rem;
			border-bottom: 1px solid #ddd;
		}
		.alumni-voice-form__notice {
			margin: .5rem 0;
		}
		.alumni-voice-form__grid {
			display: grid;
			grid-template-columns: repeat( 12, minmax( 0, 1fr ) );
			gap: 1.25rem 1.5rem;
			margin: 1.5rem 0 2.5rem;
		}
		.alumni-voice-form__field {
			min-width: 0;
		}
		.alumni-voice-form__field label {
			display: block;
			font-weight: 600;
			margin-bottom: .45rem;
		}
		.alumni-voice-form__field input,
		.alumni-voice-form__field textarea {
			box-sizing: border-box;
			width: 100%;
			max-width: 100%;
		}
		.alumni-voice-form__field--half { grid-column: span 6; }
		.alumni-voice-form__field--third { grid-column: span 4; }
		.alumni-voice-form__field--year { grid-column: span 3; }
		.alumni-voice-form__field--term { grid-column: span 3; }
		.alumni-voice-form__field--class { grid-column: span 2; }
		.alumni-voice-form__field--club { grid-column: span 5; }
		.alumni-voice-form__field--committee { grid-column: span 5; }
		.alumni-voice-form__field--lookup {
			grid-column: span 6;
			display: flex;
			align-items: flex-end;
			padding-bottom: .1rem;
		}
		.alumni-voice-form__field--lookup a {
			display: inline-block;
		}
		.alumni-voice-form__suffix {
			margin-left: .4rem;
			white-space: nowrap;
		}
		.alumni-voice-form__interview {
			margin-top: 2rem;
			padding-top: .25rem;
			border-top: 1px solid #ddd;
		}
		.alumni-voice-form__question {
			margin: 1.5rem 0;
			max-width: 50%;
		}
		.alumni-voice-form__question label {
			display: block;
			font-weight: 600;
			margin-bottom: .6rem;
		}
		.alumni-voice-form__question textarea {
			box-sizing: border-box;
			width: 100%;
			min-height: 180px;
			resize: vertical;
		}
		@media ( max-width: 782px ) {
			.alumni-voice-form__grid {
				grid-template-columns: 1fr;
				gap: 1rem;
			}
			.alumni-voice-form__field,
			.alumni-voice-form__field--half,
			.alumni-voice-form__field--third,
			.alumni-voice-form__field--year,
			.alumni-voice-form__field--term,
			.alumni-voice-form__field--class,
			.alumni-voice-form__field--club,
			.alumni-voice-form__field--committee,
			.alumni-voice-form__field--lookup {
				grid-column: auto;
			}
			.alumni-voice-form__field--lookup {
				align-items: flex-start;
				padding-bottom: 0;
			}
			.alumni-voice-form__question {
				max-width: 100%;
			}
		}
		</style>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="alumni-voice-form">
			<input type="hidden" name="action" value="alumni_voice_submit">
			<?php wp_nonce_field( 'alumni_voice_submit', 'alumni_voice_nonce' ); ?>

			<section class="alumni-voice-form__intro">
				<h2>個人情報</h2>
				<p class="alumni-voice-form__notice">入力いただいた個人情報は、本校の同窓生であることを確認する用途で使用させていただきます。個人情報については開示されません。</p>
				<p class="alumni-voice-form__notice">いただいた連絡先情報は公開されませんが、今後の同窓会活動でご連絡を差し上げる場合があります。</p>
			</section>

			<div class="alumni-voice-form__grid">
				<div class="alumni-voice-form__field alumni-voice-form__field--half">
					<label for="alumni_voice_full_name">氏名</label>
					<input id="alumni_voice_full_name" type="text" name="full_name" required>
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--half">
					<label for="alumni_voice_furigana">ふりがな</label>
					<input id="alumni_voice_furigana" type="text" name="furigana">
				</div>

				<div class="alumni-voice-form__field alumni-voice-form__field--half">
					<label for="alumni_voice_display_name">公開表示名</label>
					<input id="alumni_voice_display_name" type="text" name="display_name" required>
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--half">
					<label for="alumni_voice_email">メールアドレス</label>
					<input id="alumni_voice_email" type="email" name="email" required>
				</div>

				<div class="alumni-voice-form__field alumni-voice-form__field--year">
					<label for="alumni_voice_graduation_year">卒業年</label>
					<div><input id="alumni_voice_graduation_year" type="number" name="graduation_year" min="1000" max="9999" inputmode="numeric" placeholder="2020"><span class="alumni-voice-form__suffix">年卒</span></div>
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--term">
					<label for="alumni_voice_graduation_term">卒業期</label>
					<div><input id="alumni_voice_graduation_term" type="number" name="graduation_term" min="1" max="999" inputmode="numeric" placeholder="123"><span class="alumni-voice-form__suffix">期</span></div>
				</div>
				<?php if ( '' !== $graduation_lookup_url ) : ?>
					<div class="alumni-voice-form__field alumni-voice-form__field--lookup">
						<a href="<?php echo esc_url( $graduation_lookup_url ); ?>" target="_blank" rel="noopener noreferrer">卒業期早見表を別窓で開く</a>
					</div>
				<?php endif; ?>

				<div class="alumni-voice-form__field alumni-voice-form__field--class">
					<label for="alumni_voice_class_name">組</label>
					<input id="alumni_voice_class_name" type="text" name="class_name" maxlength="6" size="6">
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--club">
					<label for="alumni_voice_club_activity">部活動</label>
					<input id="alumni_voice_club_activity" type="text" name="club_activity">
				</div>
				<div class="alumni-voice-form__field alumni-voice-form__field--committee">
					<label for="alumni_voice_committee_activity">委員会</label>
					<input id="alumni_voice_committee_activity" type="text" name="committee_activity">
				</div>
			</div>

			<section class="alumni-voice-form__interview">
				<h2>インタビュー</h2>
				<?php foreach ( $questions as $q ) : ?>
					<div class="alumni-voice-form__question">
						<label for="alumni_voice_answer_<?php echo esc_attr( $q['id'] ); ?>"><?php echo esc_html( $q['question'] ); ?></label>
						<textarea id="alumni_voice_answer_<?php echo esc_attr( $q['id'] ); ?>" rows="7" name="answers[<?php echo esc_attr( $q['id'] ); ?>]"<?php echo ! empty( $q['required'] ) ? ' required' : ''; ?>></textarea>
					</div>
				<?php endforeach; ?>
			</section>

			<p><button type="submit">送信する</button></p>
		</form>
		<?php

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
