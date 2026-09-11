<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlumniVoice_Admin {
	public static function register() {
		add_action( 'alumni_core_register_admin_pages', array( __CLASS__, 'register_pages' ), 10, 2 );
	}

	public static function register_pages( $parent_slug, $capability ) {
		add_submenu_page(
			$parent_slug,
			'卒業生の声',
			'卒業生の声',
			$capability,
			'alumni-voice',
			array( __CLASS__, 'render_hub' )
		);
	}

	public static function render_hub() {
		$count = wp_count_posts( AlumniVoice_Post_Type::POST_TYPE );
		$all = isset( $count->publish ) ? (int) $count->publish : 0;
		$draft = isset( $count->draft ) ? (int) $count->draft : 0;
		echo '<div class="wrap"><h1>卒業生の声</h1>';
		echo '<p>卒業生の声を構造化して蓄積し、今後の検索・分類・名簿連携に活用するAlumni Core公式拡張です。</p>';
		echo '<div class="card" style="max-width:760px;padding:20px;"><h2>現在の状況</h2>';
		echo '<p>公開：<strong>' . esc_html( $all ) . '</strong>件　下書き：<strong>' . esc_html( $draft ) . '</strong>件</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'edit.php?post_type=' . AlumniVoice_Post_Type::POST_TYPE ) ) . '">卒業生の声一覧</a> ';
		echo '<a class="button" href="' . esc_url( admin_url( 'post-new.php?post_type=' . AlumniVoice_Post_Type::POST_TYPE ) ) . '">新規追加</a></p></div>';
		echo '<div class="card" style="max-width:760px;padding:20px;margin-top:16px;"><h2>構造化データ</h2><ul><li>卒業年・卒業期</li><li>職種（分類）</li><li>進路区分（分類）</li><li>進学先・勤務先</li><li>将来の名簿連携用 人物ID</li></ul><p>検索・フロントエンドの絞り込み機能は次段階で追加します。</p></div></div>';
	}
}
