<?php

namespace ListPlus;

use  ListPlus\CRUD\Listing_Type ;
use  ListPlus\CRUD\Listing ;
use  ListPlus\CRUD\Listing_Dynamic_Tax ;
use  ListPlus\CRUD\Enquiry ;
use  ListPlus\CRUD\Review ;
use  ListPlus\Taxonomies ;
use  ListPlus\Helper ;
use  ListPlus\Post_Types ;
use  ListPlus\CRUD\Listing_Category ;
class Form
{
    /**
     * Saved data from database.
     *
     * @var array
     */
    private  $data = array() ;
    private  $validate_field = false ;
    private  $number_term = 10 ;
    public  $submit_page_id = 336 ;
    private  $fields = null ;
    public function __construct()
    {
        ob_start();
        
        if ( is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        } else {
            add_action( 'wp_enqueue_scripts', array( $this, 'admin_scripts' ) );
        }
        
        add_action( 'wp_ajax_listplus_save_form', array( $this, 'handle' ) );
        add_action( 'wp_ajax_nopriv_listplus_save_form', array( $this, 'handle' ) );
        add_action( 'wp_ajax_listplus_author_ajax', array( $this, 'ajax_author_search' ) );
        add_action( 'wp_ajax_listplus_tax_ajax', array( $this, 'ajax_tax_search' ) );
        add_action( 'wp_ajax_nopriv_listplus_tax_ajax', array( $this, 'ajax_tax_search' ) );
        add_action( 'wp_ajax_listplus_get_icons', array( $this, 'ajax_get_icons' ) );
        add_action( 'wp_ajax_nopriv_listplus_get_icons', array( $this, 'ajax_get_icons' ) );
        add_filter( 'the_content', [ $this, 'maybe_include_frontend_form' ], 1989 );
    }
    
    public function maybe_include_frontend_form( $content )
    {
        $this->submit_page_id = \ListPlus()->settings->submit_page;
        
        if ( is_page( $this->submit_page_id ) ) {
            $item = \ListPlus\get_editing_listing();
            $type = \ListPlus\get_type_for_editing_listing();
            $listing_type = $type->get_slug();
            
            if ( !$item->can_edit() || !$type->valid() ) {
                $active_types = \Listplus\CRUD\Listing_Type::get_all_active();
                $can_edit = $item->is_existing_listing() && !$item->can_edit();
                $content = ListPlus()->template->load_template( 'submit-listing/select-type.php', [
                    'active_types'   => $active_types,
                    'can_edit'       => $can_edit,
                    'alllowed_types' => [],
                ] );
            } else {
                $content = ListPlus()->template->load_template( 'submit-listing/form.php' );
            }
        
        }
        
        return $content;
    }
    
    public function ajax_respond( $data )
    {
    }
    
    public function ajax_tax_search()
    {
        if ( !\wp_verify_nonce( $_REQUEST['_nonce'], 'listplus_tax_search' ) ) {
            die( 'Access denied!' );
        }
        $taxonomy = ( isset( $_REQUEST['taxonomy'] ) ? sanitize_text_field( $_REQUEST['taxonomy'] ) : '' );
        $q = ( isset( $_REQUEST['q'] ) ? sanitize_text_field( $_REQUEST['q'] ) : '' );
        $terms = get_terms( [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'search'     => $q,
            'number'     => $this->number_term,
        ] );
        $items = [];
        if ( $terms && !\is_wp_error( $terms ) ) {
            foreach ( (array) $terms as $t ) {
                $items[] = \apply_filters( 'listplus_select2_tax_item', [
                    'id'   => $t->term_id,
                    'text' => $t->name,
                    'tax'  => $taxonomy,
                ], $t );
            }
        }
        $data = [
            'results'    => $items,
            'pagination' => [
            'more' => false,
        ],
        ];
        wp_send_json( $data );
        die;
    }
    
    public function ajax_get_icons()
    {
        wp_send_json( array_values( \ListPlus()->icons->get_icons() ) );
        die;
    }
    
    public function ajax_author_search()
    {
        if ( !\wp_verify_nonce( $_REQUEST['_nonce'], 'ajax_author_search' ) ) {
            die( 'Access denied!' );
        }
        $q = ( isset( $_REQUEST['q'] ) ? sanitize_text_field( $_REQUEST['q'] ) : '' );
        // Get users.
        $user_query = new \WP_User_Query( [
            'orderby'        => 'login',
            'order'          => 'asc',
            'number'         => 15,
            'search'         => "*{$q}*",
            'search_columns' => [ 'user_login', 'user_email', 'display_name' ],
        ] );
        $items = [];
        foreach ( (array) $user_query->get_results() as $user ) {
            $items[] = [
                'id'   => $user->ID,
                'text' => sprintf( '%s (%s)', $user->user_login, $user->user_email ),
            ];
        }
        $data = [
            'results'    => $items,
            'pagination' => [
            'more' => false,
        ],
        ];
        wp_send_json( $data );
    }
    
    public function verify_captcha( $captcha_respond = false )
    {
        $enable = \ListPlus()->settings->get( 'recaptcha_enable' );
        if ( !$enable ) {
            return true;
        }
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        if ( !$captcha_respond ) {
            if ( isset( $_POST['recaptcha_respond'] ) ) {
                $captcha_respond = sanitize_text_field( $_POST['recaptcha_respond'] );
            }
        }
        if ( !$captcha_respond ) {
            return false;
        }
        $respond = \wp_remote_post( $url, [
            'body'    => array(
            'secret'   => \ListPlus()->settings->get( 'recaptcha_scret' ),
            'response' => $captcha_respond,
        ),
            'cookies' => array(),
        ] );
        $code = \wp_remote_retrieve_response_code( $respond );
        if ( 200 != $code ) {
            return false;
        }
        $body = \wp_remote_retrieve_body( $respond );
        if ( !is_array( $body ) ) {
            $body = \json_decode( $body, true );
        }
        if ( !is_array( $body ) ) {
            return false;
        }
        if ( $body['success'] ) {
            return true;
        }
        return false;
    }
    
    public function handle()
    {
        wp_send_json( $_FILES );
        die;
    }
    
    public function atts_to_html( $attrs = array() )
    {
        $html = '';
        foreach ( $attrs as $k => $v ) {
            if ( \is_array( $v ) ) {
                $v = wp_json_encode( $v );
            }
            $html .= " {$k}=\"" . \esc_attr( $v ) . '" ';
        }
        return $html;
    }
    
    public function close_icon()
    {
        return '<svg class="fill-current" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title> <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"></path>';
    }
    
    /**
     * Check if this field is required.
     *
     * @param array $field
     * @return boolean
     */
    public function is_required( $field )
    {
        $required = false;
        
        if ( isset( $field['custom'] ) ) {
            if ( isset( $field['custom']['required'] ) ) {
                $required = $field['custom']['required'];
            }
        } else {
            if ( isset( $field['required'] ) && $field['required'] ) {
                $required = true;
            }
        }
        
        return $required;
    }
    
    public function the_title( $args, $for_id = null )
    {
        
        if ( $args['title'] ) {
            ?>
			<label <?php 
            if ( $for_id ) {
                echo  'for="' . esc_attr( $for_id ) . '"' ;
            }
            if ( $this->is_required( $args ) ) {
                $args['title'] = sprintf( __( '%s <span class="required">*</span>', 'list-plus' ), $args['title'] );
            }
            ?> class="lb u"><?php 
            echo  $args['title'] ;
            ?></label>
		<?php 
        }
    
    }
    
    public function the_desc( $args )
    {
        
        if ( isset( $args['desc'] ) && $args['desc'] ) {
            ?>
			<p class="description"><?php 
            echo  $args['desc'] ;
            ?></p>
		<?php 
        }
    
    }
    
    private function tax_li(
        $name,
        $args = array(),
        $index = false,
        $add_value = true
    )
    {
        $args = wp_parse_args( $args, [
            'term_id'      => '',
            'post_id'      => '',
            'name'         => '',
            'taxonomy'     => '',
            'custom_value' => '',
        ] );
        $label = '';
        $icon = '';
        $image = '';
        
        if ( $args['term_id'] ) {
            
            if ( $args['name'] ) {
                $label = esc_html( $args['name'] );
            } else {
                $label = '<em>' . __( '(Removed)', 'list-plus' ) . '</em>';
            }
            
            $icon = get_term_meta( $args['term_id'], '_icon', true );
            $image = get_term_meta( $args['term_id'], '_image', true );
        }
        
        
        if ( $image ) {
            $thumbnail_url = wp_get_attachment_thumb_url( $image );
            $label = '<img class="name-img" src="' . esc_url( $thumbnail_url ) . '" alt=""/>' . $label;
        } elseif ( $icon && \ListPlus()->icons->the_icon_svg( $icon ) ) {
            $label = '<span class="name-svg">' . \ListPlus()->icons->the_icon_svg( $icon ) . '</span>' . $label;
        }
        
        if ( !$index ) {
            $index = \uniqid( 't-' );
        }
        ?>
		<li>
			<input type="hidden" class="input-val-id" value="<?php 
        echo  esc_attr( $args['term_id'] ) ;
        ?>" name="<?php 
        echo  esc_attr( $name . "[{$index}][term_id]" ) ;
        ?>">
			<input type="hidden" class="input-term-name" value="" name="<?php 
        echo  esc_attr( $name . "[{$index}][name]" ) ;
        ?>">
			<a href="#" class="li-remove">
				<?php 
        echo  $this->close_icon() ;
        ?>
			</a>
			<span class="name"><?php 
        echo  $label ;
        ?></span>
			<?php 
        
        if ( $add_value ) {
            ?>
			<span class="fli-meta"><label>
				<input type="text" name="<?php 
            echo  esc_attr( $name . "[{$index}][custom_value]" ) ;
            ?>" value="<?php 
            echo  esc_attr( $args['custom_value'] ) ;
            ?>" class="input-val-text" placeholder="<?php 
            \esc_attr_e( 'Custom value', 'list-plus' );
            ?>"></label>
			</span>
			<?php 
        }
        
        ?>
		</li>
		<?php 
    }
    
    private function list_li( $name, $args = array() )
    {
        $args = wp_parse_args( $args, [
            'value' => '',
            'label' => '',
        ] );
        $label = '';
        if ( $args['value'] ) {
            
            if ( $args['label'] ) {
                $label = $args['label'];
            } else {
                $label = '<em>' . __( '(Removed)', 'list-plus' ) . '</em>';
            }
        
        }
        ?>
		<li>
			<input type="hidden" class="input-val-id" value="<?php 
        echo  esc_attr( $args['value'] ) ;
        ?>" name="<?php 
        echo  esc_attr( $name ) ;
        ?>">
			<a href="#" class="li-remove">
				<?php 
        echo  $this->close_icon() ;
        ?>
			</a>
			<span class="name"><?php 
        echo  esc_html( $label ) ;
        ?></span>
		</li>
		<?php 
    }
    
    public function field_list_sort( $args )
    {
        $input_name = $args['name'] . '[]';
        $values = $this->get_value( $args );
        $is_tax = false;
        if ( isset( $args['tax'] ) && $args['tax'] ) {
            $is_tax = true;
        }
        ?>
		<div class="ff ff-dl ff-l-wrapper">
				<?php 
        $this->the_title( $args );
        ?>
				<script type="text/html" class="list-li-template">
					<?php 
        $this->list_li( $input_name );
        ?>
				</script>
				<ul class="fls-1 ff-s-list fs-list list-sortable">
					<?php 
        foreach ( (array) $values as $value ) {
            
            if ( !$is_tax ) {
                
                if ( isset( $args['options'][$value] ) ) {
                    $li_args = [
                        'value' => $value,
                        'label' => $args['options'][$value],
                    ];
                    $this->list_li( $input_name, $li_args );
                }
            
            } else {
                
                if ( is_object( $value ) ) {
                    $li_args = [
                        'value' => $value->term_id,
                        'label' => $value->name,
                    ];
                    $this->list_li( $input_name, $li_args );
                }
            
            }
        
        }
        ?>
				</ul>
				
				<?php 
        
        if ( $is_tax ) {
            $cat_retrict_ids = false;
            
            if ( 'listing_cat' == $args['tax'] ) {
                $type = \ListPlus\get_type_for_editing_listing();
                $cat_retrict_ids = $type['restrict_categories'];
                if ( !is_array( $cat_retrict_ids ) ) {
                    $cat_retrict_ids = \json_decode( $cat_retrict_ids, true );
                }
            }
            
            $get_term_args = [
                'hierarchical'      => true,
                'taxonomy'          => $args['tax'],
                'class'             => 'select2 data-to-list',
                'name'              => \uniqid( 'ls-tax-' ),
                'show_option_none'  => __( 'Select an item to add', 'list-plus' ),
                'option_none_value' => '',
                'hide_empty'        => 0,
            ];
            
            if ( $cat_retrict_ids && is_array( $cat_retrict_ids ) ) {
                $cat_retrict_ids = array_map( 'absint', $cat_retrict_ids );
                $get_term_args['hierarchical'] = false;
                $get_term_args['include'] = $cat_retrict_ids;
            }
            
            \wp_dropdown_categories( $get_term_args );
        } else {
            $this->the_field( [
                'type'    => 'select',
                'options' => $args['options'],
                'atts'    => [
                'placeholder'  => __( 'Select an item to add', 'list-plus' ),
                'data-to-list' => 'yes',
            ],
            ] );
        }
        
        ?>
			</div>
		<?php 
    }
    
    public function field_dynamic_taxs( $args )
    {
        $listing_type = $args['listing_type'];
        $name = $args['name'];
        $type = new Listing_Type( $listing_type );
        $support_taxs = $type->get_support_taxs();
        $list_taxs = [];
        $all_taxs = \ListPlus()->taxonomies->get_all();
        foreach ( $support_taxs as $key ) {
            if ( isset( $all_taxs[$key] ) ) {
                $list_taxs[$key] = $all_taxs[$key];
            }
        }
        $values = $this->get_value( $args );
        foreach ( $list_taxs as $tax_key => $tax ) {
            $input_name = $name . "__{$tax_key}";
            ?>
			<div class="form-box">
			<?php 
            
            if ( $tax['plural'] ) {
                ?>
			<h3><?php 
                echo  esc_html( $tax['plural'] ) ;
                ?></h3>
			<?php 
            }
            
            ?>
			<div class="inner">
				<div class="ff ff-dl ff-l-wrapper">
					<script type="text/html" class="list-li-template">
						<?php 
            $this->tax_li( $input_name );
            ?>
					</script>
					<ul class="fls-1 ff-s-list fs-list list-sortable">
						<?php 
            foreach ( (array) $values as $value ) {
                if ( isset( $value['taxonomy'] ) && $value['taxonomy'] == $tax_key ) {
                    $this->tax_li( $input_name, $value );
                }
            }
            ?>
					</ul>
					<?php 
            $this->the_field( [
                'type' => 'select',
                'tax'  => $tax_key,
                'atts' => [
                'placeholder'  => __( 'Select an item to add', 'list-plus' ),
                'data-to-list' => 'yes',
            ],
            ] );
            ?>
				</div>
			</div>
			</div><!-- /.end-box -->
			<?php 
        }
    }
    
    public function field_dynamic_tax( $args )
    {
        $id = $args['id'];
        $name = $args['name'];
        $tax_key = $args['tax'];
        $list_taxs = [];
        $all_taxs = \ListPlus()->taxonomies->get_all();
        if ( !isset( $all_taxs[$tax_key] ) ) {
            return;
        }
        $tax = $all_taxs[$tax_key];
        $values = $this->get_value( [
            'name' => 'taxonomies[' . $tax_key . ']',
        ] );
        $input_name = "tax_{$tax_key}";
        $allow_new = ( isset( $tax['allow_new'] ) ? $tax['allow_new'] : false );
        $add_value = ( isset( $tax['custom_value'] ) ? $tax['custom_value'] : false );
        $new_name = '';
        $args['title'] = $tax['frontend_name'];
        ?>
		<div data-id="<?php 
        echo  esc_attr( $id ) ;
        ?>" class="ff">
			<?php 
        $this->the_title( $args );
        ?>
			<div class="tt-inner">
				<div class="ff-dl ff-l-wrapper">
					<script type="text/html" class="list-li-template">
						<?php 
        $this->tax_li(
            $input_name,
            [],
            '__IDX__',
            $add_value
        );
        ?>
					</script>
					<ul class="fls-1 ff-s-list fs-list list-sortable">
						<?php 
        foreach ( (array) $values as $value ) {
            if ( isset( $value['taxonomy'] ) && $value['taxonomy'] == $tax_key ) {
                $this->tax_li(
                    $input_name,
                    $value,
                    false,
                    $add_value
                );
            }
        }
        ?>
					</ul>
					<div class="ff-d-tax-new ">
						<div class="ff-dt-select <?php 
        echo  ( $allow_new ? 'add-new-w' : '' ) ;
        ?>">
						<?php 
        $this->the_field( [
            'type'        => 'select',
            'tax'         => $tax_key,
            'atts'        => [
            'placeholder'  => __( 'Select an item to add', 'list-plus' ),
            'data-to-list' => 'yes',
        ],
            '_skip_check' => true,
        ] );
        
        if ( $allow_new ) {
            ?>
							<div class="dt-actions">
								<span class="dt-new-btn" title="<?php 
            esc_attr_e( 'Add new term', 'list-plus' );
            ?>">
									<span class="dashicons dashicons-plus-alt"></span>
								</span>
							</div>
							<?php 
        }
        
        ?>
						</div>
						<?php 
        
        if ( $allow_new ) {
            ?>
							<div class="ff-dt-f">
								<input type="text" placeholder="<?php 
            \esc_attr_e( 'Enter new item name', 'list-plus' );
            ?>" data-tax="<?php 
            echo  esc_attr( $tax_key ) ;
            ?>" class="new-term-name">
							</div>
							<?php 
        }
        
        ?>
					</div>
					
				</div>
			</div><!-- /.tt-inner -->
		</div><!-- /.ff -->
		<?php 
    }
    
    public function field_gallery( $args )
    {
        $input_name = $args['name'];
        $values = $this->get_value( $args );
        if ( !\is_array( $values ) ) {
            $values = [];
        }
        ?>
		<div data-id="<?php 
        echo  esc_attr( $input_name ) ;
        ?>" data-name="<?php 
        echo  esc_attr( $input_name ) ;
        ?>" class="ff">
			<?php 
        $this->the_title( $args );
        ?>
			<div class="fm-g f-dropzone upload_files" data-name="<?php 
        echo  esc_attr( $input_name ) ;
        ?>">
				<div class="f-media sortable">
					<?php 
        foreach ( (array) $values as $id ) {
            $key = \uniqid( 'f_' );
            $thumbnail_url = wp_get_attachment_thumb_url( $id );
            ?>
					<div class="fm-i">
						<div class="fm-ii">
							<input type="hidden" name="<?php 
            echo  esc_attr( $input_name . '_order[' . $key . ']' ) ;
            ?>" value="1">
							<input type="hidden" name="<?php 
            echo  esc_attr( $input_name . '[' . $key . ']' ) ;
            ?>" value="<?php 
            echo  esc_attr( $id ) ;
            ?>">
							<a class="fm-ri" href="#"><?php 
            echo  $this->close_icon() ;
            ?></a>
							<img src="<?php 
            echo  esc_url( $thumbnail_url ) ;
            ?>" alt="" />
						</div>
					</div>
					<?php 
        }
        ?>
					<div class="fm-i ui-state-disabled">
						<div class="fm-ii fm-more-files">
							<label class="fm-more-lb">
								<?php 
        _e( 'Upload files', 'list-plus' );
        ?>
								<input type='file' class="input-file-pickup" accept="image/*" name="media_files[]" multiple="multiple">
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php 
    }
    
    public function field_map( $args )
    {
        $names = wp_parse_args( $args['name'], [
            'lat'          => 'lat',
            'lng'          => 'lng',
            'zipcode'      => 'zipcode',
            'address'      => 'address',
            'state'        => 'state',
            'city'         => 'city',
            'country_code' => 'country_code',
        ] );
        $args['_skip_check'] = true;
        $values = [];
        foreach ( $names as $key => $custom_name ) {
            $values[$key] = $this->get_value( [
                'name' => $custom_name,
            ] );
        }
        ?>
		<div  data-id="map" class="ff ff-map">
			<?php 
        $this->the_title( $args );
        ?>
			<?php 
        $this->the_field( [
            'type'        => 'address',
            'id'          => 'address',
            'name'        => $names['address'],
            'atts'        => [
            'autocomplete' => 'falses',
            'placeholder'  => __( 'E.g New York', 'list-plus' ),
        ],
            '_skip_check' => true,
        ] );
        ?>
			<div class="lp-map ff-map-inner"></div>
			<?php 
        $this->the_field( [
            'type'          => 'group',
            'wrapper_class' => [ 'mb-0' ],
            '_skip_check'   => true,
            'fields'        => [ [
            'type'        => 'text',
            'id'          => 'lat',
            'name'        => $names['lat'],
            'title'       => __( 'Latitude', 'list-plus' ),
            'atts'        => [
            'class' => 'lat-val',
        ],
            '_skip_check' => true,
        ], [
            'type'        => 'text',
            'id'          => 'lng',
            'name'        => $names['lng'],
            'title'       => __( 'Longitude', 'list-plus' ),
            'atts'        => [
            'class' => 'lng-val',
        ],
            '_skip_check' => true,
        ] ],
        ] );
        ?>
		
		</div>
		<?php 
    }
    
    public function field_websites( $args )
    {
        $values = $this->get_value( $args );
        $name = $args['name'];
        if ( !\strpos( $name, '[' ) ) {
            $name .= '[]';
        }
        if ( !$values || empty($values) ) {
            $values = [ '' ];
        }
        ?>
		<div data-id="<?php 
        echo  esc_attr( $args['name'] ) ;
        ?>" class="ff ff-webistes">
			<?php 
        $this->the_title( $args );
        ?>
			<div class="ls-websistes">
				<?php 
        foreach ( $values as $link ) {
            ?>
				<div class="wi">
					<input type="text" value="<?php 
            echo  esc_attr( $link ) ;
            ?>" name="<?php 
            echo  esc_attr( $name ) ;
            ?>" placeholder="<?php 
            _e( 'Enter URL...', 'list-plus' );
            ?>"/>
					<span class="remove" ><span class="dashicons dashicons-dismiss"></span></span>
				</div>
				<?php 
        }
        ?>
			</div>
			<a class="add-new"  href="#"><?php 
        _e( 'Add new', 'lis-plus' );
        ?></a>
		</div>
		<?php 
    }
    
    public function field_open_hours( $args = array() )
    {
        $name = $args['name'];
        $days = ListPlus()->settings->get_days();
        $values = $this->get_value( $args );
        if ( \is_string( $values ) ) {
            $values = \json_decode( $values, true );
        }
        if ( !\is_array( $values ) ) {
            $values = [];
        }
        $all_status = [
            'enter'   => __( 'Enter hours', 'list-plus' ),
            'all_day' => __( 'All day', 'list-plus' ),
            'closed'  => __( 'Closed all day', 'list-plus' ),
        ];
        ?>
		<div data-id="<?php 
        echo  esc_attr( $name ) ;
        ?>" class="ff open-hours">
			<?php 
        $this->the_title( $args );
        ?>
			<div class="hour-inputs">
			<?php 
        foreach ( $days as $key => $label ) {
            $val = ( isset( $values[$key] ) ? $values[$key] : [] );
            $val = \wp_parse_args( $val, [
                'hours'  => [],
                'status' => '',
            ] );
            $hour_rows = ( is_array( $val['hours'] ) ? $val['hours'] : [] );
            if ( empty($hour_rows) ) {
                $hour_rows = [ '' ];
            }
            ?>
				<div class="hb">
					<label><?php 
            echo  $label ;
            ?></label>
					<div class="hd-row" data-status="<?php 
            echo  esc_attr( $val['status'] ) ;
            ?>">
						<span class="clear cursor">
							<span class="dashicons dashicons-update-alt"></span>
						</span>
						<select class="status" data-status="<?php 
            echo  esc_attr( $val['status'] ) ;
            ?>" name="<?php 
            echo  esc_attr( $name ) ;
            ?>[<?php 
            echo  esc_attr( $key ) ;
            ?>][status]">
							<?php 
            foreach ( $all_status as $stt_key => $label ) {
                ?>
							<option <?php 
                \selected( $val['status'], $stt_key );
                ?> value="<?php 
                echo  esc_attr( $stt_key ) ;
                ?>"><?php 
                echo  $label ;
                ?></option>
							<?php 
            }
            ?>
						</select>

						<div class="hd-hours">
							<?php 
            foreach ( $hour_rows as $rhi => $rh ) {
                $rh = wp_parse_args( $rh, [
                    'from' => '',
                    'to'   => '',
                ] );
                $input_name = "{$name}[{$key}][hours][{$rhi}]";
                ?>
							<div class="hd-hour" data-name="<?php 
                echo  esc_attr( $name . "[{$key}][hours]" ) ;
                ?>">
								<input name="<?php 
                echo  esc_attr( $input_name . '[from]' ) ;
                ?>" data-name="from" value="<?php 
                echo  esc_attr( $rh['from'] ) ;
                ?>" placeholder="<?php 
                esc_attr_e( '09 AM', 'list-plus' );
                ?>" type="text" class="ih">
								<input name="<?php 
                echo  esc_attr( $input_name . '[to]' ) ;
                ?>" data-name="to" value="<?php 
                echo  esc_attr( $rh['to'] ) ;
                ?>" placeholder="<?php 
                esc_attr_e( '21 PM', 'list-plus' );
                ?>"  type="text" class="ih">
								<span class="cursor h-add" title="<?php 
                esc_attr_e( 'Add more open hour', 'list-plus' );
                ?>"><span class="dashicons dashicons-plus-alt"></span></span>
								<span class="cursor h-remove" title="<?php 
                esc_attr_e( 'Remove', 'list-plus' );
                ?>"><span class="dashicons dashicons-dismiss"></span></span>
							</div>
							<?php 
            }
            ?>
						</div>

					</div>
				</div>
			<?php 
        }
        ?>
			</div>
			</div>
		<?php 
    }
    
    public function hidden( $args = array() )
    {
        foreach ( $args as $k => $value ) {
            echo  '<input ' . $this->atts_to_html( [
                'name'  => $k,
                'value' => $value,
                'type'  => 'hidden',
                'id'    => 'fh_' . $k,
            ] ) . ' /> ' ;
        }
    }
    
    /**
     * Get value value from form field.
     *
     * Example: a field has input name: contact[address][city]
     *
     * @param array $field
     * @return mixed
     */
    private function get_value( $field )
    {
        $data = $this->data;
        $field_name = $field['name'];
        if ( isset( $field['tax'] ) && $field_name ) {
            $field_name = 'taxonomies[' . $field['tax'] . ']';
        }
        
        if ( \is_array( $field_name ) ) {
            $find_data = [];
            foreach ( $field_name as $key => $fn ) {
                $find_data[$key] = $this->get_value( [
                    'name' => $fn,
                ] );
            }
            return $find_data;
        } else {
            $value = ( isset( $field['value'] ) ? $field['value'] : null );
            // Get default value.
            if ( is_null( $value ) ) {
                if ( isset( $field['default'] ) ) {
                    $value = $field['default'];
                }
            }
            $pos = \strpos( $field_name, '[' );
            
            if ( $pos <= 0 ) {
                
                if ( isset( $data[$field['name']] ) ) {
                    $value = $data[$field['name']];
                } else {
                    if ( isset( $field['default'] ) ) {
                        $value = $field['default'];
                    }
                }
                
                // if ( ! empty( $data ) && isset( $data[ $field['name'] ] ) ) {
                // $value = $data[ $field['name'] ];
                // }
                return $value;
            }
            
            preg_match_all( '/\\[([^\\]]*)\\]/', $field_name, $matches );
            if ( empty($matches[1]) ) {
                return $value;
            }
            $first = substr( $field_name, 0, $pos );
            $n = 0;
            $find_data = ( isset( $data[$first] ) ? $data[$first] : null );
            if ( is_null( $find_data ) || !is_array( $find_data ) ) {
                return null;
            }
            while ( $n < count( $matches[1] ) && null !== $find_data ) {
                $key = $matches[1][$n];
                $find_data = ( isset( $find_data[$key] ) ? $find_data[$key] : null );
                $n++;
            }
        }
        
        return $find_data;
    }
    
    public function is_field_exists( $field_id )
    {
        
        if ( \is_null( $this->fields ) ) {
            $this->fields = [];
            $fields = Helper::get_listing_fields();
            foreach ( $fields as $field ) {
                $this->fields[$field['id']] = $field;
            }
        }
        
        return isset( $this->fields[$field_id] );
    }
    
    public function set_validate_field( $set = true )
    {
        $this->validate_field = $set;
    }
    
    public function the_field( $args = array() )
    {
        $args = wp_parse_args( $args, [
            'id'             => false,
            'wrapper_class'  => [],
            'type'           => 'text',
            'input_only'     => false,
            'title'          => '',
            'name'           => '',
            'value'          => '',
            'atts'           => [],
            'options'        => [],
            'checkbox_label' => '',
            'checked_value'  => '1',
            '_skip_check'    => false,
        ] );
        if ( $this->validate_field ) {
            if ( !$args['_skip_check'] ) {
                
                if ( !$args['id'] || !$this->is_field_exists( $args['id'] ) ) {
                    return;
                    // Skip if the field is not support.
                }
            
            }
        }
        // Get value.
        $args['value'] = $this->get_value( $args );
        $wrapper_class = [ 'ff' ];
        if ( !empty($args['wrapper_class']) ) {
            $wrapper_class = \array_merge( $wrapper_class, $args['wrapper_class'] );
        }
        $atts = [];
        if ( !\is_array( $args['atts'] ) ) {
            $args['atts'] = [];
        }
        $input_atts = $args['atts'];
        $input_atts['name'] = $args['name'];
        $input_atts['type'] = $args['type'];
        
        if ( !isset( $input_atts['class'] ) ) {
            $input_atts['class'] = 'f-input';
        } else {
            $input_atts['class'] .= ' f-input';
        }
        
        $data_id = '';
        $data_id = $args['id'];
        
        if ( is_array( $args['name'] ) ) {
            $input_atts['id'] = \uniqid( 'f-' . join( '-', $args['name'] ) . '-' );
        } else {
            $input_atts['id'] = \uniqid( 'f-' . $args['name'] . '-' );
        }
        
        if ( isset( $args['_type'] ) && 'custom' == $args['_type'] ) {
            $data_id = $args['name'];
        }
        $cb = [ $this, 'field_' . $args['type'] ];
        /**
         * Allow 3rd party to add more form fields.
         */
        $cb = \apply_filters(
            'listplus_render_form_field',
            $cb,
            $args,
            $this
        );
        $cb = \apply_filters(
            'listplus_render_form_field_' . $args['type'],
            $cb,
            $args,
            $this
        );
        
        if ( \is_callable( $cb ) ) {
            \call_user_func_array( $cb, [ $args ] );
        } else {
            
            if ( !$args['input_only'] ) {
                ?>
			<div <?php 
                echo  ( $data_id ? ' data-id="' . \esc_attr( $data_id ) . '" ' : '' ) ;
                ?> class="<?php 
                echo  esc_attr( join( ' ', $wrapper_class ) ) ;
                ?>">
				<?php 
                $this->the_title( $args, $input_atts['id'] );
            }
            
            switch ( $args['type'] ) {
                case 'editor':
                    wp_editor( $args['value'], $args['name'], [
                        'textarea_name' => $args['name'],
                        'editor_class'  => $input_atts['class'],
                        'textarea_rows' => 8,
                        'teeny'         => false,
                        'dfw'           => true,
                        'quicktags'     => false,
                        'media_buttons' => false,
                        'tinymce'       => array(
                        'toolbar1' => 'bold,italic,underline,strikethrough,separator,alignleft,aligncenter,alignright, alignjustify,separator,link,unlink,undo,redo',
                        'toolbar2' => '',
                        'toolbar3' => '',
                    ),
                    ] );
                    break;
                case 'textarea':
                    unset( $atts['type'] );
                    echo  '<textarea ' . $this->atts_to_html( $input_atts ) . '>' . esc_textarea( $args['value'] ) . '</textarea>' ;
                    break;
                case 'wp_upload':
                    $src = '';
                    if ( $args['value'] ) {
                        $src = wp_get_attachment_thumb_url( $args['value'] );
                    }
                    echo  '<div class="ff-wpmedia item-media ' . (( $src ? 'has-media' : '' )) . '">' ;
                    echo  '<input class="image_id" name="' . esc_attr( $args['name'] ) . '" type="hidden"/>' ;
                    echo  '<div class="plc"><span class="dashicons dashicons-format-image"></span></div>' ;
                    echo  '<div class="thumbnail-image">' ;
                    if ( $src ) {
                        echo  '<img src="' . esc_url( $src ) . '" alt=""/>' ;
                    }
                    echo  '</div>' ;
                    echo  '<span class="remove remove-button">' . $this->close_icon() . '</span>' ;
                    echo  '</div>' ;
                    break;
                case 'checkbox':
                    $selected = '';
                    $input_atts['value'] = $args['checked_value'];
                    if ( $args['checked_value'] == $args['value'] ) {
                        $input_atts['checked'] = 'checked';
                    }
                    echo  '<label><input ' . $this->atts_to_html( $input_atts ) . '> ' . $args['checkbox_label'] . '</label>' ;
                    break;
                case 'icon':
                    $input_atts['class'] .= ' icon-select';
                    $option_none_html = '';
                    $options_html = '';
                    
                    if ( isset( $input_atts['multiple'] ) ) {
                        if ( $input_atts['name'] ) {
                            $input_atts['name'] .= '[]';
                        }
                    } else {
                        $option_none_html = '<option  value="">' . __( 'Select...', 'list-plus' ) . '</option>';
                    }
                    
                    if ( !empty($args['value']) ) {
                        
                        if ( is_array( $args['value'] ) ) {
                            foreach ( $args['value'] as $k ) {
                                $options_html .= '<option selected="selected" value="' . \esc_attr( $k ) . '">' . esc_html( $k ) . '</option>';
                            }
                        } else {
                            $options_html .= '<option selected="selected" value="' . \esc_attr( $args['value'] ) . '">' . esc_html( $args['value'] ) . '</option>';
                        }
                    
                    }
                    echo  '<select ' . $this->atts_to_html( $input_atts ) . '>' . $option_none_html . $options_html . '</select>' ;
                    break;
                case 'select':
                case 'rating':
                    $options_html = '';
                    
                    if ( isset( $args['author'] ) && $args['author'] ) {
                        $input_atts['class'] .= ' select2-author';
                        
                        if ( $args['value'] && \is_numeric( $args['value'] ) ) {
                            $user = \get_user_by( 'id', $args['value'] );
                            if ( $user ) {
                                $options_html .= '<option selected="selected" value="' . \esc_attr( $user->ID ) . '">' . sprintf( '%s (%s)', $user->user_login, $user->user_email ) . '</option>';
                            }
                        }
                    
                    } elseif ( isset( $args['tax'] ) && $args['tax'] ) {
                        if ( is_null( $args['value'] ) ) {
                            $args['value'] = $this->get_value( [
                                'name' => $args['name'],
                            ] );
                        }
                        $input_atts['class'] .= ' select2-tax';
                        $input_atts['data-tax'] = $args['tax'];
                        if ( $args['value'] ) {
                            
                            if ( is_array( $args['value'] ) ) {
                                foreach ( $args['value'] as $v ) {
                                    $kv = $v;
                                    $kl = $v;
                                    
                                    if ( \is_array( $v ) || \is_object( $v ) ) {
                                        $v = (array) $v;
                                        $kv = $v['term_id'];
                                        $kl = $v['name'];
                                    } else {
                                        // $t = \get_term( $kv, $args['tax'], ARRAY_A );
                                        // if ( $t && ! \is_wp_error( $t ) ) {
                                        // $kv = $t['term_id'];
                                        // $kl = $t['name'];
                                        // } else {
                                        // $kv = '';
                                        // }
                                    }
                                    
                                    if ( $kv ) {
                                        $options_html .= '<option selected="selected" value="' . \esc_attr( $kv ) . '">' . $kl . '</option>';
                                    }
                                }
                            } else {
                                $v = $args['value'];
                                
                                if ( \is_array( $v ) || \is_object( $v ) ) {
                                    $v = (array) $v;
                                    $kv = $v['term_id'];
                                    $kl = $v['name'];
                                } else {
                                    $kv = $v;
                                    $kl = $v;
                                }
                                
                                $options_html .= '<option selected="selected" value="' . \esc_attr( $kv ) . '">' . $kl . '</option>';
                            }
                        
                        }
                    } else {
                        $input_atts['class'] .= ' select2';
                    }
                    
                    $option_none_html = '';
                    
                    if ( isset( $input_atts['multiple'] ) ) {
                        if ( $input_atts['name'] ) {
                            $input_atts['name'] .= '[]';
                        }
                    } else {
                        $option_none_html = '<option  value="">' . __( 'Select...', 'list-plus' ) . '</option>';
                    }
                    
                    if ( isset( $args['no_option_none'] ) && $args['no_option_none'] ) {
                        $option_none_html = '';
                    }
                    foreach ( $args['options'] as $key => $o_label ) {
                        $selected = '';
                        
                        if ( is_array( $args['value'] ) && !empty($args['value']) ) {
                            if ( \in_array( $key, $args['value'], true ) ) {
                                $selected = ' selected="selected" ';
                            }
                        } else {
                            if ( $key == $args['value'] ) {
                                $selected = ' selected="selected" ';
                            }
                        }
                        
                        $options_html .= '<option ' . $selected . ' value="' . \esc_attr( $key ) . '">' . $o_label . '</option>';
                    }
                    unset( $input_atts['type'] );
                    echo  '<select ' . $this->atts_to_html( $input_atts ) . '>' . $option_none_html . $options_html . '</select>' ;
                    if ( 'rating' == $args['type'] ) {
                        echo  '<div class="rateit svg" ' . Helper::rating_atts() . ' data-rateit-backingfld="#' . esc_attr( $input_atts['id'] ) . '"></div>' ;
                    }
                    break;
                case 'pages':
                    $dropdown_args = array(
                        'depth'                 => 0,
                        'child_of'              => 0,
                        'selected'              => $args['value'],
                        'echo'                  => 1,
                        'name'                  => $input_atts['name'],
                        'id'                    => '',
                        'class'                 => 'select2 f-input',
                        'show_option_none'      => __( 'Select a page', 'list-plus' ),
                        'show_option_no_change' => '',
                        'option_none_value'     => '',
                        'value_field'           => 'ID',
                    );
                    wp_dropdown_pages( $dropdown_args );
                    break;
                case 'date':
                    $input_atts['type'] = $args['type'];
                    $input_atts['value'] = $args['value'];
                    $args['type'] = 'text';
                    $input_atts['class'] .= ' f-datepicker';
                    echo  '<div class="fd-flatpickr">
						<input type="text" ' . $this->atts_to_html( $input_atts ) . '  data-input />
						<span class="input-button l-clear-btn" title="' . esc_attr__( 'Clear', 'list-plus' ) . '" title="clear" data-clear><span class="dashicons dashicons-dismiss"></span></span>
					</div>' ;
                    break;
                default:
                    if ( !$args['type'] ) {
                        $args['type'] = 'text';
                    }
                    
                    if ( 'date' == $args['type'] ) {
                        $args['type'] = 'text';
                        $input_atts['class'] .= ' f-datepicker';
                    }
                    
                    
                    if ( 'address' == $args['type'] ) {
                        $args['type'] = 'text';
                        $input_atts['class'] .= ' f-address';
                    }
                    
                    $input_atts['type'] = $args['type'];
                    $input_atts['value'] = $args['value'];
                    echo  '<input ' . $this->atts_to_html( $input_atts ) . ' /> ' ;
            }
            $this->the_desc( $args );
            if ( !$args['input_only'] ) {
                ?>
			</div>
				<?php 
            }
        }
    
    }
    
    public function field_group( $args )
    {
        $args = wp_parse_args( $args, [
            'fields' => [],
        ] );
        $n = count( $args['fields'] );
        if ( !$n ) {
            return;
        }
        $classes = [ 'f' . $n ];
        if ( !empty($args['wrapper_class']) ) {
            $classes = \array_merge( $classes, $args['wrapper_class'] );
        }
        echo  '<div class="flb l-group ' . esc_attr( join( ' ', $classes ) ) . '">' ;
        foreach ( $args['fields'] as $field ) {
            $this->the_field( $field );
        }
        echo  '</div>' ;
    }
    
    public function field_box( $args )
    {
        $args = wp_parse_args( $args, [
            'title'     => '',
            'no_border' => '',
            'fields'    => [],
        ] );
        $classes = [ 'form-box' ];
        if ( $args['no_border'] ) {
            $classes[] = 'no-border';
        }
        if ( !empty($args['wrapper_class']) ) {
            $classes = \array_merge( $classes, $args['wrapper_class'] );
        }
        if ( !is_array( $args['fields'] ) ) {
            return;
        }
        ?>
		<div class="<?php 
        echo  esc_attr( join( ' ', $classes ) ) ;
        ?>">
			<?php 
        
        if ( $args['title'] ) {
            ?>
			<h3><?php 
            echo  $args['title'] ;
            ?></h3>
			<?php 
        }
        
        ?>
			<div class="inner">
			<?php 
        foreach ( $args['fields'] as $field ) {
            $this->the_field( $field );
        }
        ?>
			</div><!-- /.inner -->
		</div><!-- /.form-box -->
		<?php 
    }
    
    private function modal_content( $callback, $args = array(), $id = null )
    {
    }
    
    private function modal_layout_fields( $id = '', $args = array() )
    {
    }
    
    public function field_single_layout( $args )
    {
        $args = wp_parse_args( $args, [
            'title'     => '',
            'no_border' => '',
            'fields'    => [],
        ] );
        $classes = [ 'form-box' ];
        if ( $args['no_border'] ) {
            $classes[] = 'no-border';
        }
        if ( !empty($args['wrapper_class']) ) {
            $classes = \array_merge( $classes, $args['wrapper_class'] );
        }
        ?>
		<div class="<?php 
        echo  esc_attr( join( ' ', $classes ) ) ;
        ?>">
			<?php 
        
        if ( $args['title'] ) {
            ?>
			<h3><?php 
            echo  $args['title'] ;
            ?></h3>
			<?php 
        }
        
        ?>
			<div class="inner">
				<?php 
        $this->the_field( [
            'id'      => 'single_layout',
            'type'    => 'select',
            'name'    => 'single_layout',
            'title'   => __( 'Layout', 'list-plus' ),
            'default' => 'default',
            'options' => [
            'full-width'      => __( 'Default - Single column', 'list-plus' ),
            'two-columns'     => __( 'Two columns', 'list-plus' ),
            'content-sidebar' => __( 'Two thirds / One third', 'list-plus' ),
            'sidebar-content' => __( 'One third / Two thirds', 'list-plus' ),
        ],
        ] );
        ?>
				<p>
					<?php 
        $main_id = \uniqid( 'lm-' );
        ?>
					<a href="#" data-selector="#<?php 
        echo  esc_attr( $main_id ) ;
        ?>" class="sl-toggle-modal"><?php 
        _e( 'Edit Main Column', 'list-plus' );
        ?></a>
				</p>
				<p>
					<?php 
        $sidebar_id = \uniqid( 'ls-' );
        ?>
					<a href="#" data-selector="#<?php 
        echo  esc_attr( $sidebar_id ) ;
        ?>" class="sl-toggle-modal"><?php 
        _e( 'Edit Sidebar Column', 'list-plus' );
        ?></a>
				</p>
				<?php 
        $this->modal_layout_fields( $main_id, [
            'type'     => 'display_builder',
            'name'     => 'single_main',
            'data_key' => 'single_main',
            'title'    => __( 'Main Column', 'list-plus' ),
            'fields'   => \ListPlus\Helper::get_listing_display_fields(),
        ] );
        $this->modal_layout_fields( $sidebar_id, [
            'type'     => 'display_builder',
            'name'     => 'single_sidebar',
            'data_key' => 'single_sidebar',
            'title'    => __( 'Sidebar Column', 'list-plus' ),
            'fields'   => \ListPlus\Helper::get_listing_display_fields(),
        ] );
        ?>

	
			</div><!-- /.inner -->
		</div>
		<?php 
    }
    
    public function custom_fields_content( $args )
    {
        $args = wp_parse_args( $args, [
            'title'    => '',
            'name'     => '',
            'data_key' => '',
            'fields'   => [],
        ] );
        $values = $this->get_value( $args );
        ?>
		<div class="lp-form-builder ff-l-wrapper" data-fields="<?php 
        echo  esc_attr( wp_json_encode( $args['fields'] ) ) ;
        ?>" data-key="<?php 
        echo  esc_attr( $args['data_key'] ) ;
        ?>">
			
			<input type="hidden" name="<?php 
        echo  esc_attr( $args['name'] ) ;
        ?>" value='<?php 
        echo  esc_attr( $values ) ;
        ?>' class="ff-l-values">
			<script class="fbf-field-tpl" type="text/html">
				<% 
					if ( typeof _type  === 'undefined' ) {
						var _type = ''; 
					}
					if ( typeof custom  === 'undefined' ) {
						custom = {};  
					}
				%>
				<div class="lp-fb-edit-f">
					<div>
						<label><?php 
        _e( 'Label', 'list-plus' );
        ?></label>
						<input data-key="label" value="<%- custom.label %>" type="text">
					</div>
					<% if ( typeof _type !== 'undefined' && _type === 'custom' ) { %>
						<div>
							<label><?php 
        _e( 'Custom key', 'list-plus' );
        ?></label>
							<input data-key="name" value="<%- custom.name %>" type="text">
						</div>
					<% } %>
					<div>
						<label><?php 
        _e( 'Placeholder', 'list-plus' );
        ?></label>
						<input data-key="placeholder" value="<%- custom.placeholder %>"  type="text">
					</div>
					<% if ( typeof input_options !== 'undefined' && input_options ) { %>
						<div>
							<label><?php 
        _e( 'Options', 'list-plus' );
        ?></label>
							<textarea rows="6" data-key="options"><%- custom.placeholder %></textarea>
						</div>
					<% } %>
					<div>
						<label><?php 
        _e( 'Description', 'list-plus' );
        ?></label>
						<input data-key="desc" value="<%- custom.desc %>"  type="text">
					</div>
					<div>
						<label><input data-key="required" <% if ( custom.required ) { %>checked="checked"<% } %> type="checkbox"> <?php 
        _e( 'Required field', 'list-plus' );
        ?></label>
					</div>
					<div>
						<label><?php 
        _e( 'Required message', 'list-plus' );
        ?></label>
						<input data-key="required_msg" value="<%- custom.required_msg %>" type="text">
					</div>
					<div>
						<button class="button-secondary" type="button"><?php 
        _e( 'Done', 'list-plus' );
        ?></button>
					</div>
				</div>
			</script>

			<script class="list-li-template" type="text/html">
				<%
					if ( typeof custom === 'undefined' ) {
						var custom = {};
					}
					if ( typeof title === 'undefined' ) {
						var title = '';
					}
				%>
				<div class="lp-fb-g <% if ( _type === 'group' ) { %> g-nest <% } %>">
					<div class='lp-fb-head'>
						<div class="lp-fb-tg">
							<span class="dashicons dashicons-arrow-down"></span>
						</div>
						<div class="lp-fb-lb">
							<div class="lp-fb-title"><%= custom.label || title %></div>
							<div class="lp-fb-fn"><%= title %></div>
						</div>
						<div class="lp-fb-rm">
							<span class="dashicons dashicons-trash"></span>
						</div>
					</div>
					<% if ( _type === 'group' ) { %>
					<div class="children_fields"></div>
					<% } %>
				</div>
			</script>

			<div class="inner lp-fb-wrapper">
				<div class="lp-fb-fields ff-s-list">	
				</div><!-- /.lp-fb-fields -->

				<div class="lp-fb-available">
					<div class="preset">
						<label><?php 
        _e( 'Preset Fields', 'list-plus' );
        ?></label>
					</div>
					<div class="custom">
						<label><?php 
        _e( 'Create a custom field', 'list-plus' );
        ?></label>
					</div>
				</div>	
			</div><!-- /.inner -->
		</div>
		<?php 
    }
    
    public function field_form_builder( $args )
    {
        echo  '<div class="form-box">' ;
        
        if ( $args['title'] ) {
            ?>
		<h3><?php 
            echo  $args['title'] ;
            ?></h3>
		<?php 
        }
        
        ?>
		<div class="inner">
		<?php 
        // $this->modal_content( [ $this, 'custom_fields_content' ], $args, $f_id );
        $this->custom_fields_content( $args );
        echo  '</div>' ;
        echo  '</div>' ;
    }
    
    public function field_display_builder( $args )
    {
        $args = wp_parse_args( $args, [
            'title'    => '',
            'name'     => '',
            'data_key' => '',
            'fields'   => [],
        ] );
        $values = $this->get_value( $args );
        ?>
		<div class="form-box-- lp-form-builder ff-l-wrapper" data-fields="<?php 
        echo  esc_attr( wp_json_encode( $args['fields'] ) ) ;
        ?>" data-key="<?php 
        echo  esc_attr( $args['data_key'] ) ;
        ?>">
			<?php 
        
        if ( $args['title'] ) {
            ?>
			<h3><?php 
            echo  $args['title'] ;
            ?></h3>
			<?php 
        }
        
        ?>
			<input type="hidden" name="<?php 
        echo  esc_attr( $args['name'] ) ;
        ?>" value='<?php 
        echo  esc_attr( $values ) ;
        ?>' class="ff-l-values">
			<script class="fbf-field-tpl" type="text/html">
				<div class="lp-fb-edit-f">
					<% 
						if ( typeof custom  === 'undefined' ) {
							custom = {};  
						}
						if ( typeof type  === 'undefined' ) {
							type = '';  
						}
					%>
					<div>
						<label><?php 
        _e( 'Label', 'list-plus' );
        ?></label>
						<input data-key="label" value="<%- custom.label %>" type="text">
					</div>
					<div>
						<label><?php 
        _e( 'Icon', 'list-plus' );
        ?></label>
						<select data-key="icon" class="icon-select">
							<% if ( custom.icon ) { %>
								<option selected value="<%- custom.icon %>"><%- custom.icon %></option>
							<% } else { %>
								<option value=""></option>
							<% } %>
						</select>
					</div>

					<% if ( type === 'dynamic_tax' ) { %>
						<div>
							<label><?php 
        _e( 'Layout Column', 'list-plus' );
        ?></label>
							<select data-key="column" >
								<option <% if ( custom.column === '1' ) { %> selected <% } %> value="1"><?php 
        _e( '1', 'list-plus' );
        ?></option>
								<option <% if ( custom.column === '2' ) { %> selected <% } %> value='2'><?php 
        _e( '2', 'list-plus' );
        ?></option>
								<option <% if ( custom.column === '3' ) { %> selected <% } %> value='3'><?php 
        _e( '3', 'list-plus' );
        ?></option>
							</select>
						</div>
						
					<% } %>

					<% if ( typeof _type !== 'undefined' && _type === 'custom' ) { %>
						<div>
							<label><?php 
        _e( 'Field Name', 'list-plus' );
        ?></label>
							<input data-key="name" value="<%- custom.name %>" type="text">
						</div>
						<div>
							<label><?php 
        _e( 'Content', 'list-plus' );
        ?></label>
							<input data-key="desc" value="<%- custom.desc %>"  type="text">
						</div>
					<% } %>
					<% if ( typeof _type !== 'undefined' && _type === 'group' ) { %>
						<% 
							if ( typeof custom.style === 'undefined'  ) { 
								custom.style = '';
							}
						%>
						<div>
							<label><?php 
        _e( 'Display Style', 'list-plus' );
        ?></label>
							<select data-key="style" >
								<option <% if ( custom.style === 'default' ) { %> selected <% } %> value="default"><?php 
        _e( 'Default', 'list-plus' );
        ?></option>
								<option <% if ( custom.style === 'default' ) { %> selected <% } %> value="no-box"><?php 
        _e( 'Box no border', 'list-plus' );
        ?></option>
								<option <% if ( custom.style === 'list' ) { %> selected <% } %> value='list'><?php 
        _e( 'List', 'list-plus' );
        ?></option>
								<option <% if ( custom.style === 'inline' ) { %> selected <% } %> value='inline'><?php 
        _e( 'Inline', 'list-plus' );
        ?></option>
								<option <% if ( custom.style === 'column' ) { %> selected <% } %> value='column'><?php 
        _e( 'Column', 'list-plus' );
        ?></option>
							</select>
						</div>
						<div>
							<label><input data-key="hide_heading" <% if ( custom.hide_heading ) { %>checked="checked"<% } %> type="checkbox"> <?php 
        _e( 'Hide group heading', 'list-plus' );
        ?></label>
						</div>
						<div>
							<label><input data-key="hide_sub_heading" <% if ( custom.hide_sub_heading ) { %>checked="checked"<% } %> type="checkbox"> <?php 
        _e( 'Hide sub items heading', 'list-plus' );
        ?></label>
						</div>
					<% } %>
				</div>
			</script>

			<script class="list-li-template" type="text/html">
				<%
					if ( typeof custom === 'undefined' ) {
						var custom = {};
					}
					if ( typeof title === 'undefined' ) {
						var title = '';
					}
				%>
				<div class="lp-fb-g <% if ( _type === 'group' ) { %> g-nest <% } %>">
					<div class='lp-fb-head'>
						<div class="lp-fb-tg">
							<span class="dashicons dashicons-arrow-down"></span>
						</div>
						<div class="lp-fb-lb">
							<div class="lp-fb-title"><%= custom.label || title %></div>
							<div class="lp-fb-fn"><%= title %></div>
						</div>
						<div class="lp-fb-rm">
							<span class="dashicons dashicons-trash"></span>
						</div>
					</div>
					<% if ( _type === 'group' ) { %>
					<div class="children_fields"></div>
					<% } %>
				</div>
			</script>

			<div class="inner lp-fb-wrapper">
				<div class="lp-fb-fields ff-s-list">	
				</div><!-- /.lp-fb-fields -->

				<div class="lp-fb-available">
					<div class="preset">
						<label><?php 
        _e( 'Preset Fields', 'list-plus' );
        ?></label>
					</div>
					<div class="custom">
						<label><?php 
        _e( 'Custom Fields', 'list-plus' );
        ?></label>
					</div>
				</div>	
			</div><!-- /.inner -->
		</div>
		<?php 
    }
    
    public function reset()
    {
        $this->data = [];
    }
    
    public function set_data( $data = array() )
    {
        if ( !array( $data ) ) {
            $data = [];
        }
        $this->data = $data;
    }
    
    public function render( $configs, $data = null )
    {
        if ( !\is_null( $data ) ) {
            $this->set_data( $data );
        }
        $configs = wp_parse_args( $configs, [
            'fields' => [],
        ] );
        foreach ( $configs['fields'] as $field ) {
            $this->the_field( $field );
        }
    }
    
    public function admin_scripts( $hook )
    {
        $this->scripts();
    }
    
    protected static function terms_hierarchy(
        &$output,
        &$list,
        $depth = 0,
        $parent_id = 0,
        $prefix = ''
    )
    {
        foreach ( $list as $k => $item ) {
            
            if ( $item['parent'] == $parent_id ) {
                $output[] = \apply_filters( 'listplus_select2_tax_item', [
                    'id'    => (string) $item->get_id(),
                    'text'  => $prefix . $item->get_name(),
                    'tax'   => $item['taxonomy'],
                    'svg'   => $item->get_icon(),
                    'image' => $item->get_image(),
                ], $item );
                unset( $list[$k] );
                $new_prefix = $prefix . $item->get_name() . ' &rarr; ';
                static::terms_hierarchy(
                    $output,
                    $list,
                    $depth + 1,
                    $item->get_id(),
                    $new_prefix
                );
            }
        
        }
    }
    
    public function scripts()
    {
        if ( !\ListPlus()->is_listing_forms() ) {
            return;
        }
        wp_enqueue_media();
        wp_register_style(
            'select2',
            LISTPLUS_URL . '/assets/css/select2.css',
            false,
            '1.0.0'
        );
        wp_register_style(
            'listplus',
            LISTPLUS_URL . '/assets/css/styles.css',
            false,
            '1.0.0'
        );
        wp_enqueue_style( 'listplus' );
        wp_enqueue_style( 'select2' );
        $gmap_api = \ListPlus()->settings->get( 'gmap_api' );
        $js_data = [
            'ajax_url' => \admin_url( 'admin-ajax.php' ),
            'taxs'     => [],
        ];
        $deps = array(
            'jquery',
            'underscore',
            'jquery-ui-sortable',
            'select2',
            'datepicker',
            'gmap',
            'form-map'
        );
        wp_register_script(
            'gmap',
            'https://maps.googleapis.com/maps/api/js?libraries=places&key=' . $gmap_api,
            array(),
            true
        );
        wp_register_script(
            'form-map',
            LISTPLUS_URL . '/assets/js/form-gmap.js',
            array(),
            true
        );
        wp_register_script(
            'select2',
            LISTPLUS_URL . '/assets/js/select2.full.min.js',
            array(),
            true
        );
        wp_register_script(
            'datepicker',
            LISTPLUS_URL . '/assets/js/datepicker.js',
            array(),
            true
        );
        
        if ( !is_admin() && ListPlus()->settings->get( 'recaptcha_enable' ) ) {
            $deps[] = 'recaptcha';
            $js_data['recaptcha_key'] = ListPlus()->settings->get( 'recaptcha_key' );
            wp_register_script(
                'recaptcha',
                'https://www.google.com/recaptcha/api.js?render=' . \ListPlus()->settings->get( 'recaptcha_key' ),
                array(),
                true
            );
        }
        
        wp_enqueue_script(
            'listplus',
            LISTPLUS_URL . '/assets/js/scripts.js',
            $deps,
            '1.0'
        );
        $tax_classes = [];
        $cat_retrict_ids = false;
        $is_editing_lt = \ListPlus()->is_edit_listing_type();
        
        if ( $is_editing_lt ) {
            $tax_classes = [ 'listing_cat', 'listing_tax' ];
        } elseif ( \ListPlus()->is_submit_listing_form() ) {
            $tax_classes = [ 'listing_cat', 'listing_region' ];
            $item = \ListPlus\get_editing_listing();
            $type = \ListPlus\get_type_for_editing_listing();
            $cat_retrict_ids = $type['restrict_categories'];
            foreach ( (array) $type->get_support_taxs() as $custom_tax ) {
                $tax_classes[] = $custom_tax;
            }
        }
        
        foreach ( $tax_classes as $tax ) {
            $catgories = [
                'more'  => false,
                'items' => [],
            ];
            $max = 350;
            $display_hierarchy = false;
            Listing_Dynamic_Tax::set_current_tax( $tax );
            $tax_args = ListPlus()->taxonomies->get_custom( $tax );
            if ( $tax_args && $tax_args['hierarchical'] ) {
                $display_hierarchy = true;
            }
            $args = [
                'number' => $max,
            ];
            if ( 'listing_cat' == $tax ) {
                
                if ( !$is_editing_lt ) {
                    
                    if ( $cat_retrict_ids && !empty($cat_retrict_ids) ) {
                        $args['include'] = $cat_retrict_ids;
                        $display_hierarchy = false;
                    }
                
                } else {
                    $display_hierarchy = true;
                }
            
            }
            $query_args = \apply_filters( 'listplus_scripts_get_terms', $args );
            $results = Listing_Dynamic_Tax::query( $query_args, true );
            
            if ( $display_hierarchy ) {
                static::terms_hierarchy( $catgories['items'], $results['items'] );
            } else {
                foreach ( $results['items'] as $k => $item ) {
                    $catgories['items'][] = \apply_filters( 'listplus_select2_tax_item', [
                        'id'    => (string) $item->get_id(),
                        'text'  => $item->get_name(),
                        'tax'   => $item['taxonomy'],
                        'svg'   => $item->get_icon(),
                        'image' => $item->get_image(),
                    ], $item );
                }
            }
            
            if ( $results['found'] > $max ) {
                $catgories['more'] = true;
            }
            $js_data['taxs'][Listing_Dynamic_Tax::type()] = $catgories;
        }
        $js_data['tax_nonce'] = wp_create_nonce( 'listplus_tax_search' );
        $js_data['close_icon'] = $this->close_icon();
        
        if ( \ListPlus()->is_submit_listing_form() ) {
            $js_data['author_nonce'] = wp_create_nonce( 'ajax_author_search' );
            // Get users.
            $max_user = 30;
            $user_query = new \WP_User_Query( [
                'orderby' => 'login',
                'order'   => 'asc',
                'number'  => $max_user,
            ] );
            $js_data['authors'] = [
                'items' => [],
                'more'  => false,
            ];
            foreach ( (array) $user_query->get_results() as $user ) {
                $js_data['authors']['items'][] = [
                    'id'   => $user->ID,
                    'text' => sprintf( '%s (%s)', $user->user_login, $user->user_email ),
                ];
            }
            if ( $user_query->get_total() > $max_user ) {
                $js_data['authors']['more'] = true;
            }
        }
        
        // end if is listing form.
        // For debug.
        $js_data['debug'] = false;
        if ( isset( $_GET['dev'] ) ) {
            $js_data['debug'] = true;
        }
        // Form builder fields.
        // $js_data['listing_fields'] = Helper::get_listing_fields();
        // END Form builder fields.
        \wp_localize_script( 'listplus', 'ListPlus', $js_data );
    }

}