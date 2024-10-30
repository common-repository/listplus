<?php

namespace ListPlus;

use ListPlus;

class Listing_Display {
	public $listing = null;
	private $fields = [];
	public $no_heading = false;

	public function __construct( $listing = null ) {
		if ( ! $listing ) {
			$listing = get_listing();
		}
		$this->listing = $listing;

		$fields = Helper::get_listing_display_fields();
		foreach ( $fields as $field ) {
			$this->fields[ $field['id'] ] = $field;
		}
	}

	public function get_listing() {
		return $this->listing;
	}

	public function loop( $field_name ) {
		$args = [
			'_loop' => true,
			'_wrapper' => true,
		];

		$method = 'field_' . $field_name;
		if ( \method_exists( $this, $method ) ) {
			\call_user_func_array( [ $this, $method ], [ $args ] );
		}

	}

	public function parse_args( $args ) {
		$default = [
			'id'            => '',
			'_type'         => '',
			'type'          => '',
			'name'          => '',
			'title'         => '',
			'allow_new'     => '',
			'tax'           => '',
			'frontend_name' => '',
			'_loop' => false,
			'_wrapper' => false,
		];
		if ( isset( $args['id'] ) && isset( $this->fields[ $args['id'] ] ) ) {
			$default = $this->fields[ $args['id'] ];
		}
		$args = wp_parse_args(
			$args,
			$default
		);
		if ( ! isset( $args['custom'] ) || ! \is_array( $args['custom'] ) ) {
			$args['custom'] = [];
		}
		$args['custom'] = wp_parse_args(
			$args['custom'],
			[
				'style' => '',
				'icon' => '',
				'label' => '',
			]
		);
		return $args;
	}

	public function is_child( $args ) {
		if ( ! is_array( $args ) ) {
			return false;
		}
		if ( empty( $args ) ) {
			return false;
		}
		return isset( $args['_child'] );
	}

	public function get_icon( $args, $tag = '' ) {
		if ( ! is_array( $args ) ) {
			return;
		}
		if ( empty( $args ) ) {
			return;
		}

		$icon_id = '';

		if ( isset( $args['icon'] ) ) {
			$icon_id = $args['icon'];
		}

		if ( isset( $args['custom'] ) ) {
			if ( $args['custom']['label'] ) {
				$text = $args['custom']['label'];
			}
			if ( $args['custom']['icon'] ) {
				$icon_id = $args['custom']['icon'];
			}
		}
		if ( ! $tag ) {
			$tag = 'span';
		}
		if ( $icon_id ) {
			$svg = \ListPlus()->icons->the_icon_svg( $icon_id );
			if ( $svg ) {
				return '<' . $tag . ' class="l-icon f-icon">' . $svg . '</' . $tag . '>';
			}
		}
		return false;
	}

	public function item_heading( $args ) {
		if ( ! is_array( $args ) ) {
			return;
		}
		if ( empty( $args ) ) {
			return;
		}

		if ( $this->no_heading ) {
			return;
		}

		if ( isset( $args['_hide_heading'] ) && $args['_hide_heading'] ) {
			return;
		}

		if ( isset( $args['_loop'] ) && $args['_loop'] ) {
			return;
		}

		if ( isset( $args['_child'] ) && $args['_child'] ) {
			if ( 'yes' == $args['_parent']['hide_sub_heading'] ) {
				return;
			}
		}

		if ( 'group' == $args['_type'] ) {
			if ( isset( $args['hide_heading'] ) && 'yes' == $args['hide_heading'] ) {
				return;
			}
		}

		$text = $this->get_item_heading( $args );

		if ( $text ) {
			$icon = $this->get_icon( $args );
			echo '<h2 class="f-heading">' . $icon . esc_html( $text ) . '</h2>';
		}

	}

	public function get_item_heading( $args ) {

		$args = $this->parse_args( $args );

		if ( 'price' == $args['id'] ) {
			if ( ! $this->listing->support_price() ) {
				return '';
			}
		}
		if ( 'price_range' == $args['id'] ) {
			if ( ! $this->listing->support_price_range() ) {
				return '';
			}
		}

		$text = '';
		if ( 'group' != $args['_type'] ) {
			if ( isset( $args['frontend_name'] ) && $args['frontend_name'] ) {
				$text = $args['frontend_name'];
			} elseif ( isset( $args['title'] ) && $args['title'] ) {
				$text = $args['title'];
			}
		}

		if ( isset( $args['custom'] ) ) {
			if ( $args['custom']['label'] ) {
				$text = $args['custom']['label'];
			}
		}

		return $text;
	}

	public function wrapper_open( $args, $more_class = null ) {
		if ( isset( $args['_wrapper'] ) && $args['_wrapper'] ) {
			return;
		}

		if ( isset( $args['_child'] ) && $args['_child'] ) {
			$classes = [ 'l-child-wrapper' ];
		} else {
			$classes = [ 'l-wrapper' ];
		}

		if ( isset( $args['tax'] ) && $args['tax'] ) {
			$classes[] = 'l-' . $args['type'];
		} else {
			$classes[] = 'l-' . $args['id'];
		}

		$classes = join( ' ', $classes );
		if ( $more_class ) {
			$classes .= ' ' . $more_class;
		}

		echo '<div class="' . esc_attr( $classes ) . '">';
	}

	public function wrapper_close( $args ) {
		if ( isset( $args['_wrapper'] ) && $args['_wrapper'] ) {
			return;
		}
		echo '</div>';
	}

	public function field_post_title( $args = [] ) {
		if ( ! ListPlus()->template->is_theme_support() ) {
			return; // Do not support this field if current not support listing.
		}
		if ( $this->listing->post_title ) {
			echo '<h1 class="l-title">' . \apply_filters( 'the_title', $this->listing->post_title, $this->listing->ID ) . '</h1>';
		}
	}

	public function field_title_link( $args = [] ) {

		if ( $this->listing->post_title ) {
			echo '<h2 class="l-title"><a href="' . \esc_url( $this->listing->get_view_link() ) . '">' . \apply_filters( 'the_title', $this->listing->post_title, $this->listing->ID ) . '</a></h2>';
		}
	}

	public function field_status( $args = [] ) {
		$all_status = \ListPlus\Post_Types::get_status();
		$status = isset( $all_status[ $this->listing->post_status ] ) ? $all_status[ $this->listing->post_status ] : $this->listing->post_status;
		echo '<div class="l-author-status"><span class="ls-status stt-' . esc_attr( $this->listing->post_status ) . '">' . \esc_html( $status ) . '</span></div>';
	}

	public function field_claimed_status( $args = [] ) {
		if ( ! $this->listing->claimed ) {
			return;
		}

		echo '<div class="l-edit-claimed">' . \__( 'Claimed', 'list-plus' ) . '</a></div>';

	}

	public function field_author_edit_link( $args = [] ) {
		if ( ! $this->listing->can_edit() ) {
			return;
		}
		$submit_page = ListPlus()->settings->get( 'submit_page' );
		if ( $submit_page ) {
			$link = \get_permalink( $submit_page );
			$link = add_query_arg(
				[
					'id' => $this->listing->get_id(),
					'from' => 'dashboard',
				],
				$link
			);
			echo '<a class="l-edit-link" href="' . esc_url( $link ) . '">' . \__( 'Edit', 'list-plus' ) . '</a>';
		}
	}


	public function field_excerpt( $args = [] ) {
		if ( $this->listing->post_content ) {
			echo '<div class="l-excerpt">' . wp_trim_words( $this->listing->post_content, 30 ) . '</div>';
		}
	}

	public function field_post_content( $args = [] ) {
		if ( $this->listing->post_content ) {
			$show_more = ListPlus()->settings->get( 'single_desc_more', 1 );

			$args = $this->parse_args( $args, 'no-js' );
			$this->wrapper_open( $args );
			$this->item_heading( $args );
			$content = apply_filters( 'the_listing_content', $this->listing->post_content );

			$class = 'l-description';
			if ( $show_more ) {
				$class .= ' l-des-more no-js';
			}
			echo '<div class="' . $class . '">'; // WPCS: XSS ok.
			echo $content; // WPCS: XSS ok.
			echo '</div>';
			if ( $show_more ) {
				echo '<a href="#" data-more="' . __( 'Read more', 'list-plus' ) . '"  data-less="' . __( 'Show less', 'list-plus' ) . '" class="l-more-btn no-js">' . __( 'Read more', 'list-plus' ) . '</a>';
			}

			$this->wrapper_close( $args );
		}
	}

	public function field_post_date( $args = [] ) {
		if ( ! $this->listing->post_date ) {
			return;
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		$icon = '';
		if ( $this->is_child( $args ) ) {
			$icon = $this->get_icon( $args );
		}
		$date_format = \get_option( 'date_format' );
		$time_format = \get_option( 'time_format' );
		$date = date_i18n( $date_format . __( ' @ ', 'list-plus' ) . $time_format, \strtotime( $this->listing->post_date ) );
		echo '<div class="l-date">' . $icon . apply_filters( 'the_listing_date', $date, $this->listing->post_date ) . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_address( $args = [] ) {
		if ( ! $this->listing->address ) {
			return;
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		$icon = '';
		if ( $this->is_child( $args ) ) {
			$icon = $this->get_icon( $args );
		}

		echo '<div class="l-address">' . $icon . esc_html( $this->listing->address ) . '</div>';
		$this->wrapper_close( $args );
	}
	public function field_price( $args = [] ) {
		if ( ! $this->listing->support_price() ) {
			return '';
		}
		// if ( ! $this->listing->price ) {
		// return;
		// }
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		$icon = '';
		if ( $this->is_child( $args ) ) {
			$icon = $this->get_icon( $args );
		}

		echo '<div class="l-price">' . $icon . apply_filters( 'the_listing_price', ListPlus\Helper::price_format( $this->listing->price ), $this->listing ) . '</div>';
		$this->wrapper_close( $args );
	}


	public function field_price_range( $args = [] ) {
		if ( ! $this->listing->support_price_range() ) {
			return '';
		}
		if ( ! $this->listing->price_range ) {
			return '';
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		$text = \str_repeat( '$', $this->listing->price_range );
		$icon = '';
		if ( $this->is_child( $args ) ) {
			$icon = $this->get_icon( $args );
		}
		echo '<div class="l-price_range">' . $icon . $text . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_post_author( $args = [] ) {
		if ( ! $this->listing->post_author ) {
			return '';
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		$name = \get_the_author_meta( 'display_name', $this->listing->post_author );
		$icon = '';
		if ( $this->is_child( $args ) ) {
			$icon = $this->get_icon( $args );
		}
		echo '<div class="l-user-avt l-post_author">' . $icon . $name . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_claimed( $args = [] ) {
		if ( ! $this->listing->claimed ) {
			return '';
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		$name = \get_the_author_meta( 'display_name', $this->listing->claimed );
		$email = \get_the_author_meta( 'email', $this->listing->claimed );
		$avatar = \get_avatar_url( $email );
		if ( $avatar ) {
			$avatar = '<img src="' . $avatar . '" alt=""/>';
		}
		if ( $name ) {
			$name = '<span class="name">' . esc_html( $name ) . '</span>';
		}
		echo '<div class="l-user-avt l-post_claimed">' . $avatar . $name . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_highlighs_short( $args = [] ) {
		$type = $this->listing->get_listing_type();
		$tax_highlight = $type->tax_highlight;
		$tax_term = get_term( $tax_highlight, 'listing_tax' );
		if ( ! $tax_term || \is_wp_error( $tax_term ) ) {
			return;
		}

		$terms = $this->listing->get_terms( 'ltx_' . $tax_term->slug );
		$limit = (int) $type->highlight_limit;
		if ( ! $limit ) {
			$limit = 2;
		}
		if ( empty( $terms ) ) {
			return;
		}
		$columns = 1;
		$terms = array_slice( $terms, 0, $limit );
		?>
		<ul class="l-terms l-highlighs" data-column="<?php echo \esc_attr( $columns ); ?>">
			<?php
			foreach ( $terms as $term ) {
				$t = new \ListPlus\CRUD\Taxonomy( $term );
				echo $t->to_html();
			}
			?>
		</ul>
		<?php

	}

	public function field_categories_short( $args = [] ) {
		if ( ! $this->listing->categories ) {
			return '';
		}
		$limit = 1;
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		$order_cats = [];
		if ( \class_exists( '\ListPlus\Filter' ) ) {
			$filter_cats = \ListPlus()->filter->get_var( 'l_cat' );
			if ( ! empty( $filter_cats ) ) {
				foreach ( $filter_cats as $oid ) {
					foreach ( $this->listing->categories as $t ) {
						if ( $oid == $t->term_id ) {
							$order_cats[] = $t;
						}
					}
				}
			}
		}
		if ( empty( $order_cats ) ) {
			$order_cats = $this->listing->categories;
		}

		$list = [];
		foreach ( array_slice( $order_cats, 0, $limit ) as $term ) {
			$list[] = sprintf( '<a href="%1$s">%2$s</a>', get_term_link( $term ), $term->name );
		}
		echo '<div class="l-categories">' . join( ', ', $list ) . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_categories( $args = [] ) {
		if ( ! $this->listing->categories ) {
			return '';
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		$list = [];
		foreach ( $this->listing->categories as $term ) {
			$list[] = sprintf( '<a href="%1$s">%2$s</a>', get_term_link( $term ), $term->name );
		}
		echo '<div class="l-categories">' . join( ', ', $list ) . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_region( $args = [] ) {

		if ( ! $this->listing->region ) {
			return;
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		$terms = $this->listing->region;
		$term = $terms[0];
		$icon = '';
		if ( $this->is_child( $args ) ) {
			$icon = $this->get_icon( $args );
		}
		$text = sprintf( '<a href="%1$s">%2$s</a>', get_term_link( $term ), $term->name );
		echo '<div class="l-region">' . $icon . $text . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_email( $args = [] ) {
		if ( ! $this->listing->email ) {
			return '';
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		$email = esc_html( antispambot( $this->listing->email ) );
		$icon = '';
		if ( $this->is_child( $args ) ) {
			$icon = $this->get_icon( $args );
		}
		echo '<div class="l-email">' . $icon . $email . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_header( $args = [] ) {
		echo '<div class="l-header l-wrapper">';
		$this->loop( 'review_sumary' );
		echo '<div class="l-item-meta">';
			$this->loop( 'price' );
			$this->loop( 'price_range' );
		if ( \is_singular( 'listing' ) && $this->listing->get_id() == get_the_id() ) {
			$this->loop( 'categories' );
		} else {
			$this->loop( 'categories_short' );
		}

		echo '</div>';
		$this->loop( 'actions' );
		echo '</div>';
	}

	private function modal_enquiry( $id = null ) {
		if ( ! $id ) {
			$id = \uniqid( 'mod-' );
		}
		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="l-modal">
			<div class="l-modal-inner lm-wrapper">
				<span class="lm-close"></span>
				<div class="lm-content">
					<?php
					\ListPlus()->template->get_part( 'form/enquiry.php' );
					?>
				</div>
			</div>	
		</div>
		<?php
	}

	private function modal_review( $id = null ) {
		if ( ! $id ) {
			$id = \uniqid( 'mod-' );
		}
		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="l-modal">
			<div class="l-modal-inner lm-wrapper">
				<span class="lm-close"></span>
				<div class="lm-content">
					<?php
					\ListPlus()->template->get_part( 'form/review.php' );
					?>
				</div>
			</div>	
		</div>
		<?php
	}

	private function modal_claim( $id = null ) {
		if ( ! $id ) {
			$id = \uniqid( 'mod-' );
		}
		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="l-modal">
			<div class="l-modal-inner lm-wrapper">
				<span class="lm-close"></span>
				<div class="lm-content">
					<?php
					\ListPlus()->template->get_part( 'form/claim.php' );
					?>
				</div>
			</div>	
		</div>
		<?php
	}

	private function modal_report( $id = null ) {
		if ( ! $id ) {
			$id = \uniqid( 'mod-' );
		}
		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="l-modal">
			<div class="l-modal-inner lm-wrapper">
				<span class="lm-close"></span>
				<div class="lm-content">
					<?php
					\ListPlus()->template->get_part( 'form/report.php' );
					?>
				</div>
			</div>	
		</div>
		<?php
	}

	public function field_actions( $args = [] ) {

		$args = $this->parse_args( $args );

		$actions = [
			'write-a-review' => [
				'icon' => \ListPlus()->icons->the_icon_svg( 'star-full', true ),
				'text' => __( 'Write a Review', 'list-plus' ),
				'show_cb' => [ $this->listing, 'reviews_open' ],
				'modal_content' => [ $this, 'modal_review' ],
				'login' => true,
			],
			'enquiry' => [
				'icon' => \ListPlus()->icons->the_icon_svg( 'envelope', true ),
				'text' => __( 'Send an Enquiry', 'list-plus' ),
				'show_cb' => [ $this->listing, 'enquiries_open' ],
				'modal_content' => [ $this, 'modal_enquiry' ],
				'login' => true,
			],
			'claim' => [
				'icon' => \ListPlus()->icons->the_icon_svg( 'download', true ),
				'text' => __( 'Claim', 'list-plus' ),
				'show_cb' => [ $this->listing, 'claims_open' ],
				'modal_content' => [ $this, 'modal_claim' ],
				'login' => true,
			],
			'report' => [
				'icon' => \ListPlus()->icons->the_icon_svg( 'flag', true ),
				'text' => __( 'Report', 'list-plus' ),
				'show_cb' => [ $this->listing, 'reports_open' ],
				'modal_content' => [ $this, 'modal_report' ],
				'login' => true,
			],

		];

		$buttons = [];
		$is_logged_in = \is_user_logged_in();

		foreach ( $actions as $action => $act_args ) {
			if ( isset( $act_args['show_cb'] ) ) {
				$check = \call_user_func_array( $act_args['show_cb'], [] );
				if ( ! $check ) {
					continue;
				}
			}
			$item_attrs = [];
			$id = 'l-act-' . $action;
			$classes = [ 'action-btn', 'act-' . esc_attr( $action ) ];
			$show_modal = true;
			if ( isset( $act_args['login'] ) ) {
				if ( $act_args['login'] ) {
					if ( ! $is_logged_in ) {
						$show_modal = false;
					}
				}
			}

			$link = \ListPlus()->request->to_url( $action, [ 'name' => $this->listing->get_slug() ] );
			if ( $show_modal && isset( $act_args['modal_content'] ) ) {
				\call_user_func_array( $act_args['modal_content'], [ 'id' => $id ] );
				$classes[] = 'l-toggle-modal';

			} else {
				$link = wp_login_url( $link );
			}

			if ( $act_args['text'] ) {
				$act_args['text'] = '<span class="btn-txt">' . $act_args['text'] . '</span>';
			}

			$item_attrs['class'] = $classes;
			$item_attrs['data-selector'] = '#' . $id;

			// Maybe need to use rel nofollow for this button link.
			$buttons[] = sprintf( '<a ' . \ListPlus\Helper::to_html_attrs( $item_attrs ) . ' ref="noindex, nofollow" href="%1$s">%2$s%3$s</a>', $link, $act_args['icon'], $act_args['text'] );
		}

		if ( count( $buttons ) ) {
			$this->wrapper_open( $args );
			echo '<div class="l-actions">' . join( ' ', $buttons ) . '</div>';
			$this->wrapper_close( $args );
		}

	}

	public function field_phone( $args = [] ) {
		if ( ! $this->listing->phone ) {
			return '';
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		$phone = esc_html( antispambot( $this->listing->phone ) );
		$icon = '';
		if ( $this->is_child( $args ) ) {
			$icon = $this->get_icon( $args );
		}
		echo '<div class="l-phone">' . $icon . $phone . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_map( $args = [] ) {
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$map_data = [
			'lat' => $this->listing->lat,
			'lng' => $this->listing->lng,
			'address' => $this->listing->address,
		];
		$this->item_heading( $args );
		echo '<div class="l-map-inner">';
			echo '<div class="l-single-map" data-map="' . \esc_attr( \wp_json_encode( $map_data ) ) . '"></div>';
		if ( $this->listing->address ) {
			echo '<div class="l-address">' . esc_html( $this->listing->address ) . '</div>';
		}
		echo '</div>';
		$this->wrapper_close( $args );
	}

	public function field_websites( $args = [] ) {
		if ( ! $this->listing->websites ) {
			return '';
		}

		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		$support_icons = [
			'facebook.' => 'facebook',
			'fb.' => 'facebook',
			'play.google' => 'google',
			'youtube' => 'youtube',
			'youtu.be' => 'youtube',
			'google.' => 'google',
			'twitter.' => 'twitter',
			'tumblr.' => 'tumblr',
			'twitch.' => 'twitch',
			'github.' => 'github',
			'instagram.' => 'instagram',
			'gitlab.' => 'gitlab',
			'spotify.' => 'spotify',
			'pinterest.' => 'pinterest',
			'snapchat.' => 'snapchat-ghost',
		];

		$default_icon = 'globe';

		$list = [];
		if ( is_array( $this->listing->websites ) ) {
			foreach ( $this->listing->websites as $link ) {
				$link = trim( $link );
				if ( ! $link ) {
					continue;
				}
				$icon = '';
				foreach ( $support_icons as $key => $value ) {
					if ( \strpos( $link, $key ) ) {
						$icon = $value;
						break;
					}
				}

				if ( ! $icon ) {
					$icon = $default_icon;
				}

				if ( $icon ) {
					$svg = \ListPlus()->icons->the_icon_svg( $icon );
					if ( $svg ) {
						$icon = '<span class="f-icon">' . $svg . '</span>';
					} else {
						$icon = '';
					}
				}

				$text_link = $link;
				if ( 'https://' == \substr( $link, 0, 8 ) ) {
					$text_link = \substr( $link, 8 );
				} elseif ( 'http://' == \substr( $link, 0, 7 ) ) {
					$text_link = \substr( $link, 7 );
				}

				$list[] = sprintf( '<li>%2$s<a href="%1$s">%3$s</a></li>', esc_url( $link ), $icon, esc_html( $text_link ) );
			}
		}

		echo '<ul class="l-websites">' . join( ' ', $list ) . '</ul>';
		$this->wrapper_close( $args );
	}

	public function field_media_files( $args = [] ) {
		// if ( ! $this->listing->media_files ) {
		// return '';
		// }
		$this->field_gallery( $args );
	}


	public function field_review_sumary( $args = [] ) {

		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		// $this->item_heading( $args );
		$max = (int) ListPlus()->settings->get( 'review_max' );
		$score = $this->listing->rating_score;

		echo '<div class="rateit-wrapper">';
		echo '<div class="rateit svg" data-preset="' . ( ceil( $score ) ) . '" ' . Helper::rating_atts() . ' data-rateit-max="' . $max . '" data-rateit-value="' . $score . '" data-rateit-ispreset="true" data-rateit-readonly="true"></div>';

		if ( '1' == $this->listing->count_review ) {
			/* translators: %s: post title */
			_ex( 'One review', 'review count', 'list-plus' );
		} else {
			printf(
				/* translators: 1: number of comments, 2: post title */
				_nx(
					'%1$s review',
					'%1$s reviews',
					$this->listing->count_review,
					'review count',
					'list-plus'
				),
				number_format_i18n( $this->listing->count_review )
			);
		}

		echo '</div>';

		$this->wrapper_close( $args );
	}

	public function field_reviews( $args = [] ) {
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		\ListPlus()->template->get_part( 'single/reviews.php' );
		$this->wrapper_close( $args );
	}

	public function field_video_url( $args = [] ) {
		if ( ! $this->listing->video_url ) {
			return '';
		}

		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		global $wp_embed;
		$video = $wp_embed->autoembed( $this->listing->video_url );
		echo '<div class="l-video">' . $video . '</div>';
		$this->wrapper_close( $args );
	}

	public function field_open_hours( $args = [] ) {
		if ( ! $this->listing->open_hours ) {
			return '';
		}
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		$days = ListPlus()->settings->get_days();

		$html = '';
		foreach ( (array) $this->listing->open_hours as $day => $day_settings ) {
			$day_settings = wp_parse_args(
				$day_settings,
				[
					'status' => '',
					'hours' => [],
				]
			);

			$day_name = $days[ $day ];

			$day_html = '';

			switch ( $day_settings['status'] ) {
				case 'closed':
					$day_html .= sprintf( '<div class="day-name">%1$s</div><div class="day-hours">%2$s</div>', $day_name, __( 'Closed', 'list-plus' ) );
					break;
				case 'all_day':
					$day_html .= sprintf( '<div class="day-name">%1$s</div><div class="day-hours">%2$s</div>', $day_name, __( 'Open 24 hours', 'list-plus' ) );
					break;
				default:
					$sub_h = '';
					foreach ( $day_settings['hours'] as $h ) {
						if ( $h['from'] && $h['to'] ) {
							$sub_h .= \sprintf( '<div class="h">' . __( '%1$s - %2$s', 'list-plus' ) . '</div>', esc_html( $h['from'] ), esc_html( $h['to'] ) );
						}
					}
					if ( $sub_h ) {
						$day_html .= sprintf( '<div class="day-name">%1$s</div><div class="day-hours">%2$s</div>', $day_name, $sub_h );
					}
			}

			if ( $day_html ) {
				$html .= '<div class="day-row">' . $day_html . '</div>';
			}
		}

		echo '<div class="l-open_hours">';
		echo $html;
		echo '</div>';
		$this->wrapper_close( $args );
	}

	public function field_enquiry( $args = [] ) {

		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		echo '<div class="l-enquiry">';
		\ListPlus()->template->get_part( 'form/enquiry.php' );
		echo '</div>';
		$this->wrapper_close( $args );
	}

	public function field_gallery( $args = [] ) {
		$image_ids = $this->listing->get_gallery();
		if ( empty( $image_ids ) ) {
			return;
		}

		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		$total_count = $this->listing->media_count;
		$max_show = 5;
		$image_ids = array_slice( $image_ids, 0, $max_show );
		$photos_link = $this->listing->get_view_link( 'photos' );

		?>
		<div class="lightSlider l-carousel-photos">
			<?php
			if ( $this->listing->video_url ) {
				$thumb = '';
				if ( $this->listing->youtube_id ) {
					$thumb = 'https://img.youtube.com/vi/' . $this->listing->youtube_id . '/mqdefault.jpg';
				}
				?>
				<div class="img-inner video-inner <?php echo ( $thumb ) ? 'has-thumb' : 'no-thumb'; ?>" data-thumb="<?php echo esc_url( $thumb ); ?>"  data-src="<?php echo esc_attr( $this->listing->video_url ); ?>">
					<?php
					if ( $thumb ) {
						echo '<img src="' . esc_url( $thumb ) . '" alt=""/>';
					}

					echo '<span class="video-icon">' . \ListPlus()->icons->the_icon_svg( 'controller-play' ) . '</span>';

					?>
				</div>
				<?php
			}
			$n = count( $image_ids );
			for ( $i = 0; $i < $n; $i++ ) {
				$id = $image_ids[ $i ];
				$url = wp_get_attachment_image_src( $id, 'medium' );
				if ( $url ) {
					$url = $url[0];
				}
				$full = wp_get_attachment_url( $id );
				$is_last = $i == $n - 1 && $total_count > $max_show;
				if ( $url ) {
					?>
					<div class="img-inner" data-thumb="<?php echo esc_url( $url ); ?>"  data-src="<?php echo esc_attr( $full ); ?>">
						<img src="<?php echo esc_url( $url ); ?>" alt=""/>
					</div>
				<?php } ?>
			<?php } ?>
			<?php if ( $total_count > $max_show ) { ?>        
			<div class="img-inner last">
				<?php
					printf( '<a href="%1$s" class="see-all">' . __( 'See All %2$s', 'list-plus' ) . '</a>', $photos_link, number_format_i18n( $total_count ) );
				?>
			</div>
			<?php } ?>
		</div>
		<?php if ( $total_count > $max_show ) { ?>        
			<p>
			<?php
				printf( '<a href="%1$s" class="see-all">' . __( 'See All %2$s', 'list-plus' ) . '</a>', $photos_link, number_format_i18n( $total_count ) );
			?>
			</p>
		<?php }
		$this->wrapper_close( $args );
	}

	public function field_custom( $args = [] ) {
		if ( ! isset( $args['custom']['name'] ) ) {
			return;
		}
		$name = isset( $args['custom']['name'] ) ? $args['custom']['name'] : false;
		$args = $this->parse_args( $args );
		$this->wrapper_open( $args );
		$this->item_heading( $args );
		$value = $this->listing->get_meta( $name );
		echo '<div class="l-text">' . esc_html( $value ) . '</div>';
		$this->wrapper_close( $args );
	}


	public function field_dynamic_tax( $args = [] ) {
		$tax = $args['tax'];
		$terms = $this->listing->get_terms( $tax );
		if ( ! $terms || ! count( $terms ) ) {
			return;
		}

		$args = $this->parse_args( $args );
		$columns = isset( $args['custom']['column'] ) ? absint( $args['custom']['column'] ) : 0;
		if ( 0 >= $columns ) {
			$columns  = 1;
		}
		$this->wrapper_open( $args );
		$this->item_heading( $args );

		?>
		<ul class="l-terms" data-column="<?php echo \esc_attr( $columns ); ?>">
			<?php
			foreach ( $terms as $term ) {
				$t = new \ListPlus\CRUD\Taxonomy( $term );
				echo $t->to_html();
			}
			?>
		</ul>
		<?php
		$this->wrapper_close( $args );
	}

	public function field_group( $args = [] ) {
		if ( ! isset( $args['children'] ) ) {
			return;
		}
		$this->wrapper_open( $args );
		$args = $this->parse_args( $args );
		$title = $this->get_item_heading( $args );
		$style = $args['custom']['style'];
		$icon = $this->get_icon( $args );
		if ( ! $style ) {
			$style = 'default';
		}
		if ( ! $this->no_heading && $title ) {
			echo '<h2 class="f-g-title f-heading">' . $icon . esc_html( $title ) . '</h2>';
		}

		echo '<div class="f-group style-' . esc_attr( $style ) . '">';
			echo '<div class="f-group-inner">';
			$this->render( (array) $args['children'], $args['custom'] );
			echo '</div>';
		echo '</div>';
		$this->wrapper_close( $args );

	}

	public function render( $fields, $parent_args = null ) {
		foreach ( $fields as $item ) {

			if ( ! isset( $this->fields[ $item['id'] ] ) ) {
				continue;
			}

			if ( 'tax_ltx' == substr( $item['id'], 0, 7 ) ) {
				$cb = [ $this, 'field_dynamic_tax' ];
			} else {
				$cb = [ $this, 'field_' . $item['id'] ];
			}

			if ( $parent_args && is_array( $parent_args ) ) {
				$parent_args = wp_parse_args(
					$parent_args,
					[
						'style' => '',
						'hide_heading' => 'no',
						'hide_sub_heading' => 'no',
					]
				);
				$item['_child'] = true;
				$item['_parent'] = $parent_args;
			}

			$cb = \apply_filters( 'listplus_listing_display_field', $cb, $item );

			if ( is_callable( $cb ) ) {
				call_user_func_array( $cb, [ $item ] );
			}
		}
	}



}


