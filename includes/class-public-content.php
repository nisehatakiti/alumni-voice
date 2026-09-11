<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Public content bridge for AlumniVoice.
 *
 * Provides two stable public pages that Alumni Core can expose in its
 * homepage/menu content picker:
 *  - 卒業生の声を投稿する
 *  - 卒業生の声を見る
 */
class AlumniVoice_Public_Content {
	const SYSTEM_SUBMIT = 'alumni_voice_submit';
	const SYSTEM_LIST   = 'alumni_voice_list';

	const OPTION_SUBMIT_PAGE = 'alumni_voice_submit_page_id';
	const OPTION_LIST_PAGE   = 'alumni_voice_list_page_id';

	const SUBMIT_SLUG = 'alumni-voice-submit';
	const LIST_SLUG   = 'alumni-voices';

	public static function register() {
		self::register_core_integration();

		add_shortcode( 'alumni_voice_submit', array( __CLASS__, 'render_submit' ) );
		add_shortcode( 'alumni_voice_list', array( __CLASS__, 'render_list' ) );

		add_action( 'init', array( __CLASS__, 'ensure_pages' ), 20 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function register_core_integration() {
		add_filter( 'alumni_core_system_content_keys', array( __CLASS__, 'register_system_keys' ) );
		add_filter( 'alumni_core_system_content_labels', array( __CLASS__, 'register_system_labels' ) );
		add_filter( 'alumni_core_system_content_groups', array( __CLASS__, 'register_system_groups' ) );
		add_filter( 'alumni_core_system_content_url', array( __CLASS__, 'resolve_system_url' ), 10, 2 );
	}

	public static function register_system_keys( $keys ) {
		$keys[] = self::SYSTEM_SUBMIT;
		$keys[] = self::SYSTEM_LIST;
		return array_values( array_unique( $keys ) );
	}

	public static function register_system_groups( $groups ) {
		$groups = is_array( $groups ) ? $groups : array();
		$groups[ self::SYSTEM_SUBMIT ] = 'Alumni-Voice';
		$groups[ self::SYSTEM_LIST ]   = 'Alumni-Voice';
		return $groups;
	}

	public static function register_system_labels( $labels ) {
		$settings = class_exists( 'AlumniVoice_Form_Settings' ) ? AlumniVoice_Form_Settings::get_public_settings() : array();
		$labels[ self::SYSTEM_SUBMIT ] = $settings['content_label'] ?? '卒業生の声を投稿する';
		$labels[ self::SYSTEM_LIST ]   = $settings['list_content_label'] ?? '卒業生の声を見る';
		return $labels;
	}

	public static function resolve_system_url( $url, $system_key ) {
		if ( self::SYSTEM_SUBMIT === $system_key ) return self::get_submit_url();
		if ( self::SYSTEM_LIST === $system_key ) return self::get_list_url();
		return $url;
	}

	public static function ensure_pages() {
		$settings = class_exists( 'AlumniVoice_Form_Settings' ) ? AlumniVoice_Form_Settings::get_public_settings() : array();

		self::ensure_page(
			self::OPTION_SUBMIT_PAGE,
			self::SUBMIT_SLUG,
			$settings['page_title'] ?? '卒業生の声を投稿する',
			'[alumni_voice_submit]'
		);
		self::ensure_page(
			self::OPTION_LIST_PAGE,
			self::LIST_SLUG,
			$settings['list_page_title'] ?? '卒業生の声を見る',
			'[alumni_voice_list]'
		);
	}

	private static function ensure_page( $option, $slug, $title, $content ) {
		$page_id = (int) get_option( $option, 0 );
		if ( $page_id && 'page' === get_post_type( $page_id ) ) return $page_id;

		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			update_option( $option, (int) $existing->ID );
			return (int) $existing->ID;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
			),
			true
		);

		if ( ! is_wp_error( $page_id ) && $page_id ) {
			update_option( $option, (int) $page_id );
			return (int) $page_id;
		}
		return 0;
	}

	public static function body_class( $classes ) {
		if ( ! is_singular( 'page' ) ) return $classes;

		$page_id = (int) get_queried_object_id();
		if ( $page_id === (int) get_option( self::OPTION_SUBMIT_PAGE, 0 ) ) {
			$classes[] = 'alumni-voice-submit-page';
		}
		if ( $page_id === (int) get_option( self::OPTION_LIST_PAGE, 0 ) ) {
			$classes[] = 'alumni-voice-list-page';
		}
		return $classes;
	}

	public static function get_submit_url() {
		self::ensure_pages();
		$page_id = (int) get_option( self::OPTION_SUBMIT_PAGE, 0 );
		return $page_id && 'page' === get_post_type( $page_id ) ? (string) get_permalink( $page_id ) : '';
	}

	public static function get_list_url() {
		self::ensure_pages();
		$page_id = (int) get_option( self::OPTION_LIST_PAGE, 0 );
		return $page_id && 'page' === get_post_type( $page_id ) ? (string) get_permalink( $page_id ) : '';
	}

	public static function render_submit() {
		if ( ! class_exists( 'AlumniVoice_Submission' ) ) return '';
		return AlumniVoice_Submission::shortcode();
	}

	private static function request_text( $key ) {
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
	}

	private static function request_int( $key ) {
		return isset( $_GET[ $key ] ) ? absint( $_GET[ $key ] ) : 0;
	}

	private static function card_summary( $post_id ) {
		foreach ( AlumniVoice_Form_Settings::get_questions() as $question ) {
			$answer = trim( (string) get_post_meta( $post_id, '_alumni_voice_answer_' . $question['id'], true ) );
			if ( '' !== $answer ) return wp_trim_words( wp_strip_all_tags( $answer ), 42 );
		}
		return '';
	}

	private static function meta_query( $keyword, $year_from, $year_to, $term_from, $term_to, $club, $committee ) {
		$meta_query = array( 'relation' => 'AND' );

		if ( '' !== $keyword ) {
			$keyword_query = array( 'relation' => 'OR' );
			foreach ( array( 'display_name', 'club_activity', 'committee_activity' ) as $key ) {
				$keyword_query[] = array(
					'key'     => '_alumni_voice_' . $key,
					'value'   => $keyword,
					'compare' => 'LIKE',
				);
			}
			foreach ( AlumniVoice_Form_Settings::get_questions() as $question ) {
				$keyword_query[] = array(
					'key'     => '_alumni_voice_answer_' . $question['id'],
					'value'   => $keyword,
					'compare' => 'LIKE',
				);
			}
			$meta_query[] = $keyword_query;
		}

		if ( $year_from || $year_to ) {
			$range = array( 'key' => '_alumni_voice_graduation_year', 'type' => 'NUMERIC' );
			if ( $year_from && $year_to ) {
				$range['value'] = array( $year_from, $year_to );
				$range['compare'] = 'BETWEEN';
			} elseif ( $year_from ) {
				$range['value'] = $year_from;
				$range['compare'] = '>=';
			} else {
				$range['value'] = $year_to;
				$range['compare'] = '<=';
			}
			$meta_query[] = $range;
		}

		if ( $term_from || $term_to ) {
			$range = array( 'key' => '_alumni_voice_graduation_term', 'type' => 'NUMERIC' );
			if ( $term_from && $term_to ) {
				$range['value'] = array( $term_from, $term_to );
				$range['compare'] = 'BETWEEN';
			} elseif ( $term_from ) {
				$range['value'] = $term_from;
				$range['compare'] = '>=';
			} else {
				$range['value'] = $term_to;
				$range['compare'] = '<=';
			}
			$meta_query[] = $range;
		}

		if ( '' !== $club ) {
			$meta_query[] = array( 'key' => '_alumni_voice_club_activity', 'value' => $club, 'compare' => 'LIKE' );
		}
		if ( '' !== $committee ) {
			$meta_query[] = array( 'key' => '_alumni_voice_committee_activity', 'value' => $committee, 'compare' => 'LIKE' );
		}

		return count( $meta_query ) > 1 ? $meta_query : array();
	}

	public static function render_list() {
		$settings = AlumniVoice_Form_Settings::get_public_settings();

		$keyword   = self::request_text( 'av_keyword' );
		$year_from = self::request_int( 'av_year_from' );
		$year_to   = self::request_int( 'av_year_to' );
		$term_from = self::request_int( 'av_term_from' );
		$term_to   = self::request_int( 'av_term_to' );
		$club      = self::request_text( 'av_club' );
		$committee = self::request_text( 'av_committee' );
		$sort      = self::request_text( 'av_sort' );
		$sort      = in_array( $sort, array( 'newest', 'oldest' ), true ) ? $sort : 'newest';

		$paged = max( 1, get_query_var( 'paged' ), self::request_int( 'av_page' ) );
		$args = array(
			'post_type'      => AlumniVoice_Post_Type::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'oldest' === $sort ? 'ASC' : 'DESC',
		);

		$meta_query = self::meta_query( $keyword, $year_from, $year_to, $term_from, $term_to, $club, $committee );
		if ( ! empty( $meta_query ) ) $args['meta_query'] = $meta_query;

		$query = new WP_Query( $args );
		$current_url = get_permalink( (int) get_option( self::OPTION_LIST_PAGE, 0 ) );
		$active_filters = array_filter(
			array(
				'av_keyword'   => $keyword,
				'av_year_from' => $year_from,
				'av_year_to'   => $year_to,
				'av_term_from' => $term_from,
				'av_term_to'   => $term_to,
				'av_club'      => $club,
				'av_committee' => $committee,
				'av_sort'      => 'newest' === $sort ? '' : $sort,
			),
			static function( $value ) { return '' !== $value && 0 !== $value; }
		);

		ob_start();
		?>
		<style>
		.alumni-voice-list-page .entry-title,
		.alumni-voice-list-page .page-title,
		.alumni-voice-list-page .wp-block-post-title { display:none; }
		.alumni-voice-directory{max-width:1280px;margin:0 auto;padding:0 1rem 3rem;color:#22364f;box-sizing:border-box}
		.alumni-voice-directory *,.alumni-voice-directory *:before,.alumni-voice-directory *:after{box-sizing:border-box}
		.alumni-voice-directory__hero{margin:0 -1rem 2rem;padding:2.5rem max(1rem,calc((100vw - 1280px)/2));background:linear-gradient(105deg,#eef5fb 0%,#f8fbfe 58%,#edf6ef 100%);border-bottom:1px solid #dbe6ef}
		.alumni-voice-directory__hero-inner{max-width:1280px;margin:0 auto;padding-left:1.4rem;border-left:6px solid #2e67a6}
		.alumni-voice-directory__brand{margin:0 0 .35rem;color:#2e67a6;font-size:.82rem;font-weight:800;letter-spacing:.12em}
		.alumni-voice-directory__hero h1{margin:0 0 .45rem;font-size:clamp(1.9rem,4vw,2.8rem);line-height:1.2;letter-spacing:.04em}
		.alumni-voice-directory__hero p{margin:0;font-size:1.05rem}
		.alumni-voice-directory__layout{display:grid;grid-template-columns:290px minmax(0,1fr);gap:1.5rem;align-items:start}
		.alumni-voice-directory__filter{padding:1.25rem;background:#fff;border:1px solid #d9e1e8;border-radius:12px;box-shadow:0 10px 28px rgba(33,54,79,.06)}
		.alumni-voice-directory__filter h2{margin:0 0 1rem;padding:0 0 .6rem .75rem;border-left:5px solid #2e67a6;border-bottom:1px solid #d6e0ea;font-size:1.15rem}
		.alumni-voice-directory__field{margin:0 0 1rem}
		.alumni-voice-directory__field label{display:block;margin-bottom:.38rem;font-weight:700;font-size:.9rem}
		.alumni-voice-directory__field input,.alumni-voice-directory__field select{width:100%;height:44px;padding:.55rem .7rem;border:1px solid #b8c7d4;border-radius:7px;background:#fff;color:#22364f}
		.alumni-voice-directory__range{display:grid;grid-template-columns:1fr 1fr;gap:.45rem;align-items:center}
		.alumni-voice-directory__range input{min-width:0}
		.alumni-voice-directory__buttons{display:grid;gap:.65rem;margin-top:1.15rem}
		.alumni-voice-directory__buttons button,.alumni-voice-directory__clear{display:block;width:100%;padding:.8rem 1rem;border-radius:7px;text-align:center;font-weight:700;text-decoration:none}
		.alumni-voice-directory__buttons button{border:0;background:#2e67a6;color:#fff;cursor:pointer}
		.alumni-voice-directory__clear{border:1px solid #b9cadb;color:#2e67a6;background:#fff}
		.alumni-voice-directory__privacy{margin-top:1rem;padding:1rem;background:#f5f8fc;border-radius:8px;font-size:.83rem;line-height:1.7;color:#526579}
		.alumni-voice-directory__main{min-width:0}
		.alumni-voice-directory__toolbar{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin:0 0 .9rem}
		.alumni-voice-directory__toolbar h2{margin:0;font-size:1.35rem}
		.alumni-voice-directory__count{color:#5d7085;font-size:.9rem}
		.alumni-voice-directory__sort{min-width:150px}
		.alumni-voice-directory__card{position:relative;margin:0 0 .85rem;padding:1.2rem 1.3rem 1.15rem 6.1rem;background:#fff;border:1px solid #d9e1e8;border-radius:12px;box-shadow:0 8px 24px rgba(33,54,79,.05)}
		.alumni-voice-directory__avatar{position:absolute;left:1.25rem;top:1.25rem;width:3.7rem;height:3.7rem;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#e6f0f8,#d9e8f4);color:#2e67a6;font-size:1.35rem;font-weight:800}
		.alumni-voice-directory__date{position:absolute;right:1.2rem;top:1.15rem;color:#64778b;font-size:.82rem}
		.alumni-voice-directory__meta{display:flex;flex-wrap:wrap;gap:.4rem;margin:0 0 .5rem;padding-right:7rem}
		.alumni-voice-directory__meta span{padding:.28rem .55rem;border-radius:6px;background:#edf3f8;color:#355b7c;font-size:.78rem;font-weight:700}
		.alumni-voice-directory__meta .alumni-voice-directory__name{padding:0;background:transparent;color:#22364f;font-size:1rem}
		.alumni-voice-directory__card h3{margin:0 0 .45rem;font-size:1.2rem;line-height:1.45}
		.alumni-voice-directory__card h3 a{color:#22364f;text-decoration:none}
		.alumni-voice-directory__summary{margin:.2rem 0 0;color:#4f6174;line-height:1.7}
		.alumni-voice-directory__more{display:inline-flex;margin-top:.7rem;color:#245fa2;font-weight:700;text-decoration:none}
		.alumni-voice-directory__empty{padding:2.5rem 1.5rem;text-align:center;background:#f7fafc;border:1px dashed #c8d6e3;border-radius:10px;color:#617386}
		.alumni-voice-directory-pagination{margin:1.4rem 0 0}
		.alumni-voice-directory-pagination ul{display:flex;flex-wrap:wrap;gap:.35rem;padding:0;margin:0;list-style:none}
		.alumni-voice-directory-pagination a,.alumni-voice-directory-pagination span{display:flex;min-width:38px;height:38px;padding:0 .65rem;align-items:center;justify-content:center;border:1px solid #cbd8e4;border-radius:6px;text-decoration:none;color:#2e67a6;background:#fff}
		.alumni-voice-directory-pagination .current{background:#2e67a6;color:#fff;border-color:#2e67a6}
		@media(max-width:782px){.alumni-voice-directory{padding:0 .75rem 2rem}.alumni-voice-directory__hero{margin:0 -.75rem 1.25rem;padding:1.7rem .75rem}.alumni-voice-directory__layout{grid-template-columns:1fr}.alumni-voice-directory__filter{order:2}.alumni-voice-directory__toolbar{align-items:flex-start;flex-direction:column}.alumni-voice-directory__sort{width:100%}.alumni-voice-directory__card{padding:5.9rem 1rem 1rem}.alumni-voice-directory__avatar{left:1rem;top:1rem}.alumni-voice-directory__date{left:5.6rem;right:auto;top:2.45rem}.alumni-voice-directory__meta{padding-right:0}}
		</style>

		<div class="alumni-voice-directory">
			<header class="alumni-voice-directory__hero">
				<div class="alumni-voice-directory__hero-inner">
					<?php if ( '' !== trim( (string) $settings['list_hero_brand'] ) ) : ?><p class="alumni-voice-directory__brand"><?php echo esc_html( $settings['list_hero_brand'] ); ?></p><?php endif; ?>
					<h1><?php echo esc_html( $settings['list_hero_title'] ); ?></h1>
					<p><?php echo esc_html( $settings['list_hero_description'] ); ?></p>
				</div>
			</header>

			<div class="alumni-voice-directory__layout">
				<aside class="alumni-voice-directory__filter">
					<h2>絞り込み検索</h2>
					<form method="get" action="<?php echo esc_url( $current_url ); ?>">
						<div class="alumni-voice-directory__field">
							<label for="av-keyword">キーワード</label>
							<input id="av-keyword" type="search" name="av_keyword" value="<?php echo esc_attr( $keyword ); ?>" placeholder="名前・内容など">
						</div>
						<div class="alumni-voice-directory__field">
							<label>卒業年</label>
							<div class="alumni-voice-directory__range"><input type="number" name="av_year_from" value="<?php echo esc_attr( $year_from ?: '' ); ?>" placeholder="例）2010"><input type="number" name="av_year_to" value="<?php echo esc_attr( $year_to ?: '' ); ?>" placeholder="例）2020"></div>
						</div>
						<div class="alumni-voice-directory__field">
							<label>卒業期</label>
							<div class="alumni-voice-directory__range"><input type="number" name="av_term_from" value="<?php echo esc_attr( $term_from ?: '' ); ?>" placeholder="例）100"><input type="number" name="av_term_to" value="<?php echo esc_attr( $term_to ?: '' ); ?>" placeholder="例）120"></div>
						</div>
						<div class="alumni-voice-directory__field">
							<label for="av-club">部活動</label>
							<input id="av-club" type="text" name="av_club" value="<?php echo esc_attr( $club ); ?>" placeholder="例）サッカー部">
						</div>
						<div class="alumni-voice-directory__field">
							<label for="av-committee">委員会</label>
							<input id="av-committee" type="text" name="av_committee" value="<?php echo esc_attr( $committee ); ?>" placeholder="例）生徒会">
						</div>
						<input type="hidden" name="av_sort" value="<?php echo esc_attr( $sort ); ?>">
						<div class="alumni-voice-directory__buttons">
							<button type="submit">この条件で検索</button>
							<a class="alumni-voice-directory__clear" href="<?php echo esc_url( $current_url ); ?>">条件をクリア</a>
						</div>
					</form>
					<div class="alumni-voice-directory__privacy">掲載されている内容は、卒業生のみなさんから寄せられたメッセージです。個人が特定される情報や連絡先情報は公開されません。</div>
				</aside>

				<main class="alumni-voice-directory__main">
					<div class="alumni-voice-directory__toolbar">
						<div><h2>投稿一覧 <span class="alumni-voice-directory__count">（全 <?php echo esc_html( (int) $query->found_posts ); ?> 件）</span></h2></div>
						<form method="get" action="<?php echo esc_url( $current_url ); ?>">
							<?php foreach ( $active_filters as $key => $value ) : ?><input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>"><?php endforeach; ?>
							<select class="alumni-voice-directory__sort" name="av_sort" onchange="this.form.submit()" aria-label="並び順">
								<option value="newest"<?php selected( $sort, 'newest' ); ?>>新しい順</option>
								<option value="oldest"<?php selected( $sort, 'oldest' ); ?>>古い順</option>
							</select>
						</form>
					</div>

					<?php if ( $query->have_posts() ) : ?>
						<div class="alumni-voice-directory__cards">
							<?php while ( $query->have_posts() ) : $query->the_post(); ?>
								<?php
								$post_id = get_the_ID();
								$display_name = (string) get_post_meta( $post_id, '_alumni_voice_display_name', true );
								$year = (int) get_post_meta( $post_id, '_alumni_voice_graduation_year', true );
								$term = (int) get_post_meta( $post_id, '_alumni_voice_graduation_term', true );
								$club_name = (string) get_post_meta( $post_id, '_alumni_voice_club_activity', true );
								$committee_name = (string) get_post_meta( $post_id, '_alumni_voice_committee_activity', true );
								$initial = $display_name ? mb_substr( $display_name, 0, 1 ) : '卒';
								?>
								<article class="alumni-voice-directory__card">
									<div class="alumni-voice-directory__avatar" aria-hidden="true"><?php echo esc_html( $initial ); ?></div>
									<time class="alumni-voice-directory__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'Y年n月j日' ) ); ?></time>
									<div class="alumni-voice-directory__meta">
										<?php if ( $display_name ) : ?><span class="alumni-voice-directory__name"><?php echo esc_html( $display_name ); ?></span><?php endif; ?>
										<?php if ( $year ) : ?><span><?php echo esc_html( $year ); ?>年卒</span><?php endif; ?>
										<?php if ( $term ) : ?><span><?php echo esc_html( $term ); ?>期</span><?php endif; ?>
										<?php if ( $club_name ) : ?><span><?php echo esc_html( $club_name ); ?></span><?php endif; ?>
										<?php if ( $committee_name ) : ?><span><?php echo esc_html( $committee_name ); ?></span><?php endif; ?>
									</div>
									<h3><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
									<?php $summary = self::card_summary( $post_id ); ?>
									<?php if ( $summary ) : ?><p class="alumni-voice-directory__summary"><?php echo esc_html( $summary ); ?></p><?php endif; ?>
									<a class="alumni-voice-directory__more" href="<?php the_permalink(); ?>">続きを読む&nbsp;›</a>
								</article>
							<?php endwhile; ?>
						</div>
					<?php else : ?>
						<div class="alumni-voice-directory__empty">条件に一致する卒業生の声はありません。</div>
					<?php endif; ?>

					<?php
					$pagination_args = $active_filters;
					$pagination_args['av_sort'] = $sort;
					$links = paginate_links(
						array(
							'base'      => add_query_arg( 'av_page', '%#%', $current_url ),
							'format'    => '',
							'total'     => max( 1, (int) $query->max_num_pages ),
							'current'   => $paged,
							'type'      => 'list',
							'add_args'  => $pagination_args,
							'prev_text' => '‹',
							'next_text' => '›',
						)
					);
					if ( $links ) echo '<nav class="alumni-voice-directory-pagination" aria-label="卒業生の声ページ送り">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</main>
			</div>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}
}
