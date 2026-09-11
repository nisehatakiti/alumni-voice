<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Public content bridge for AlumniVoice.
 *
 * Provides two stable public pages that Alumni Core can expose in its
 * homepage/menu content picker:
 *  - 卒業生の声を投稿する
 *  - 卒業生の声を参照する
 */
class AlumniVoice_Public_Content {
	const SYSTEM_SUBMIT = 'alumni_voice_submit';
	const SYSTEM_LIST   = 'alumni_voice_list';

	const OPTION_SUBMIT_PAGE = 'alumni_voice_submit_page_id';
	const OPTION_LIST_PAGE   = 'alumni_voice_list_page_id';

	const SUBMIT_SLUG = 'alumni-voice-submit';
	const LIST_SLUG   = 'alumni-voice';

	public static function register() {
		add_shortcode( 'alumni_voice_submit', array( __CLASS__, 'render_submit' ) );
		add_shortcode( 'alumni_voice_list', array( __CLASS__, 'render_list' ) );

		add_action( 'init', array( __CLASS__, 'ensure_pages' ), 20 );

		// Alumni Core automatically discovers these as selectable public content.
		add_filter( 'alumni_core_system_content_keys', array( __CLASS__, 'register_system_keys' ) );
		add_filter( 'alumni_core_system_content_labels', array( __CLASS__, 'register_system_labels' ) );
		add_filter( 'alumni_core_system_content_url', array( __CLASS__, 'resolve_system_url' ), 10, 2 );
	}

	public static function register_system_keys( $keys ) {
		$keys[] = self::SYSTEM_SUBMIT;
		$keys[] = self::SYSTEM_LIST;
		return array_values( array_unique( $keys ) );
	}

	public static function register_system_labels( $labels ) {
		$labels[ self::SYSTEM_SUBMIT ] = '卒業生の声を投稿する';
		$labels[ self::SYSTEM_LIST ]   = '卒業生の声を参照する';
		return $labels;
	}

	public static function resolve_system_url( $url, $system_key ) {
		if ( self::SYSTEM_SUBMIT === $system_key ) {
			return self::get_submit_url();
		}
		if ( self::SYSTEM_LIST === $system_key ) {
			return self::get_list_url();
		}
		return $url;
	}

	public static function ensure_pages() {
		self::ensure_page(
			self::OPTION_SUBMIT_PAGE,
			self::SUBMIT_SLUG,
			'卒業生の声を投稿する',
			'[alumni_voice_submit]'
		);
		self::ensure_page(
			self::OPTION_LIST_PAGE,
			self::LIST_SLUG,
			'卒業生の声',
			'[alumni_voice_list]'
		);
	}

	private static function ensure_page( $option, $slug, $title, $content ) {
		$page_id = (int) get_option( $option, 0 );
		if ( $page_id && 'page' === get_post_type( $page_id ) ) {
			return $page_id;
		}

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
		if ( ! class_exists( 'AlumniVoice_Submission' ) ) {
			return '';
		}
		return AlumniVoice_Submission::shortcode();
	}

	public static function render_list() {
		$query = new WP_Query(
			array(
				'post_type'      => AlumniVoice_Post_Type::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'paged'          => max( 1, get_query_var( 'paged' ) ),
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		ob_start();
		?>
		<div class="alumni-voice-directory">
			<header class="alumni-voice-directory-header">
				<h2>卒業生の声</h2>
				<p>卒業生の進路や経験、在校生へのメッセージをご覧いただけます。</p>
			</header>

			<?php if ( $query->have_posts() ) : ?>
				<div class="alumni-voice-directory-list">
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php
						$post_id      = get_the_ID();
						$display_name = (string) get_post_meta( $post_id, '_alumni_voice_display_name', true );
						$year         = (int) get_post_meta( $post_id, '_alumni_voice_graduation_year', true );
						$term         = (int) get_post_meta( $post_id, '_alumni_voice_graduation_term', true );
						$jobs         = wp_get_post_terms( $post_id, 'alumni_voice_job', array( 'fields' => 'names' ) );
						$careers      = wp_get_post_terms( $post_id, 'alumni_voice_career', array( 'fields' => 'names' ) );
						$summary      = '';
						foreach ( AlumniVoice_Form_Settings::get_questions() as $question ) {
							$answer = trim( (string) get_post_meta( $post_id, '_alumni_voice_answer_' . $question['id'], true ) );
							if ( '' !== $answer ) {
								$summary = wp_trim_words( wp_strip_all_tags( $answer ), 36 );
								break;
							}
						}
						?>
						<article class="alumni-voice-card">
							<h3><a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a></h3>
							<div class="alumni-voice-card-meta">
								<?php if ( $display_name ) : ?><span><?php echo esc_html( $display_name ); ?></span><?php endif; ?>
								<?php if ( $year ) : ?><span><?php echo esc_html( $year ); ?>年卒</span><?php endif; ?>
								<?php if ( $term ) : ?><span><?php echo esc_html( $term ); ?>期</span><?php endif; ?>
								<?php foreach ( array_merge( is_wp_error( $jobs ) ? array() : $jobs, is_wp_error( $careers ) ? array() : $careers ) as $term_name ) : ?>
									<span><?php echo esc_html( $term_name ); ?></span>
								<?php endforeach; ?>
							</div>
							<?php if ( $summary ) : ?><p><?php echo esc_html( $summary ); ?></p><?php endif; ?>
							<p><a href="<?php the_permalink(); ?>">続きを読む</a></p>
						</article>
					<?php endwhile; ?>
				</div>
			<?php else : ?>
				<p>現在公開されている卒業生の声はありません。</p>
			<?php endif; ?>

			<?php
			$links = paginate_links(
				array(
					'total'   => (int) $query->max_num_pages,
					'current' => max( 1, get_query_var( 'paged' ) ),
					'type'    => 'list',
				)
			);
			if ( $links ) {
				echo '<nav class="alumni-voice-directory-pagination" aria-label="卒業生の声ページ送り">' . $links . '</nav>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php
		wp_reset_postdata();
		return ob_get_clean();
	}
}
