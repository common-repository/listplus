<?php

namespace ListPlus;

use  ListPlus\CRUD\Listing_Type ;
use  ListPlus\CRUD\Listing ;
use  ListPlus\CRUD\Listing_Dynamic_Tax ;
use  ListPlus\CRUD\Enquiry ;
use  ListPlus\CRUD\Review ;
use  ListPlus\CRUD\Report ;
use  ListPlus\CRUD\Claim ;
use  ListPlus\Taxonomies ;
use  ListPlus\Helper ;
use  ListPlus\Post_Types ;
use  ListPlus\CRUD\Listing_Category ;
class Ajax_Form
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
        // Handle listing item.
        add_action( 'wp_ajax_listplus_save_listing', array( $this, 'ajax_save_listing' ) );
        add_action( 'wp_ajax_nopriv_listplus_save_listing', array( $this, 'ajax_save_listing' ) );
        // Handle listing type.
        add_action( 'wp_ajax_listplus_save_listing_type', array( $this, 'ajax_save_listing_type' ) );
        // Save report.
        add_action( 'wp_ajax_listplus_save_report', array( $this, 'ajax_save_report' ) );
        add_action( 'wp_ajax_nopriv_listplus_save_report', array( $this, 'ajax_save_report' ) );
        // Save enquiry.
        add_action( 'wp_ajax_listplus_save_enquiry', array( $this, 'ajax_save_enquiry' ) );
        add_action( 'wp_ajax_nopriv_listplus_save_enquiry', array( $this, 'ajax_save_enquiry' ) );
        // Save claim.
        add_action( 'wp_ajax_listplus_save_claim', array( $this, 'ajax_save_claim' ) );
        add_action( 'wp_ajax_nopriv_listplus_save_claim', array( $this, 'ajax_save_claim' ) );
        // Claim actions.
        add_action( 'wp_ajax_listplus_claim_actions', array( $this, 'ajax_claim_actions' ) );
        add_action( 'wp_ajax_nopriv_listplus_claim_actions', array( $this, 'ajax_claim_actions' ) );
        // Save review.
        add_action( 'wp_ajax_listplus_save_review', array( $this, 'ajax_save_review' ) );
        add_action( 'wp_ajax_nopriv_listplus_save_review', array( $this, 'ajax_save_review' ) );
        // Ajax load review.
        add_action( 'wp_ajax_listplus_load_reviews', array( $this, 'ajax_load_reviews' ) );
        add_action( 'wp_ajax_nopriv_listplus_load_reviews', array( $this, 'ajax_load_reviews' ) );
    }
    
    public function ajax_respond( $data )
    {
    }
    
    public function get_nonce()
    {
        $nonce = ( isset( $_REQUEST['_nonce'] ) ? sanitize_text_field( $_REQUEST['_nonce'] ) : false );
        return $nonce;
    }
    
    public function verify_nonce( $action = -1 )
    {
        $nonce = $this->get_nonce();
        if ( !wp_verify_nonce( $nonce, $action ) ) {
            die( 'Access denied.' );
        }
    }
    
    public function ajax_load_reviews()
    {
        $page = ( isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1 );
        $listing_id = ( isset( $_GET['listing_id'] ) ? intval( $_GET['listing_id'] ) : false );
        if ( !$listing_id ) {
            die;
        }
        $listing = \ListPlus\get_listing( $listing_id );
        \set_query_var( 'r_paged', $page );
        ListPlus()->template->get_part( 'reviews.php' );
        die;
    }
    
    public function ajax_save_report()
    {
        \ListPlus()->error->reset();
        $this->maybe_check_captcha();
        $user = wp_get_current_user();
        $data = [];
        $data['ip'] = \ListPlus\get_client_ip();
        $data['user_id'] = 0;
        $data['post_id'] = ( isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : '' );
        $data['status'] = 'unread';
        $data['reason'] = ( isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '' );
        
        if ( $user && $user->ID ) {
            $data['user_id'] = $user->ID;
            $data['email'] = $user->user_email;
            $data['name'] = $user->display_name;
            if ( !$data['name'] ) {
                $data['name'] = $user->user_login;
            }
        }
        
        $listing = new Listing( $data['post_id'] );
        if ( !$listing->is_existing_listing() ) {
            \ListPlus()->error->add( 'invalid_listing', __( 'Invalid listing.', 'list-plus' ) );
        }
        if ( !$listing->reports_open() ) {
            \ListPlus()->error->add( 'report_status', __( 'Report disabled for this listing.', 'list-plus' ) );
        }
        if ( !$data['reason'] ) {
            \ListPlus()->error->add( 'invalid_message', __( 'Please enter your message.', 'list-plus' ) );
        }
        $count_report = Report::query()->where( 'user_id', $data['user_id'] )->where( 'post_id', $listing->get_id() )->count();
        if ( $count_report > 0 ) {
            \ListPlus()->error->add( 'already_report', __( 'You\'ve already reported this listing. It is currently being reviewed.', 'list-plus' ) );
        }
        $data['created_at'] = current_time( 'mysql', true );
        $id = false;
        $entry = false;
        $error_html = false;
        $success_html = false;
        
        if ( !\ListPlus()->error->has_errors() ) {
            $report = new Report( $data );
            $report->save();
            if ( $report->get_id() ) {
                $id = $report->get_id();
            }
            $success_html = '<div class="lp-success"><div class="success-msg">' . __( 'Your report was submitted successfully. It will be shortly reviewed by our team.', 'list-plus' ) . '</div></div>';
        } else {
            $error_html = \ListPlus()->error->to_html();
        }
        
        $respond = [
            'success'      => ( $id ? true : false ),
            'id'           => $id,
            'error_html'   => $error_html,
            'success_html' => $success_html,
        ];
        if ( $respond['success'] ) {
            do_action( 'listplus_submitted_report', $report, $listing );
        }
        wp_send_json( $respond );
        die;
    }
    
    public function ajax_claim_actions()
    {
        $this->verify_nonce( 'listplus_claim_actions' );
    }
    
    public function ajax_save_claim()
    {
        \ListPlus()->error->reset();
        $this->maybe_check_captcha();
        $user = wp_get_current_user();
        $data = [];
        $data['ip'] = \ListPlus\get_client_ip();
        $data['user_id'] = 0;
        $data['post_id'] = ( isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : '' );
        $data['email'] = ( isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : '' );
        $data['name'] = ( isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '' );
        $data['status'] = 'pending';
        $data['title'] = ( isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '' );
        $data['content'] = ( isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '' );
        
        if ( $user && $user->ID ) {
            $data['user_id'] = $user->ID;
            $data['email'] = $user->user_email;
            $data['name'] = $user->display_name;
            if ( !$data['name'] ) {
                $data['name'] = $user->user_login;
            }
        }
        
        $listing = new Listing( $data['post_id'] );
        if ( !$listing->is_existing_listing() ) {
            \ListPlus()->error->add( 'invalid_listing', __( 'Invalid listing.', 'list-plus' ) );
        }
        if ( !$listing->claims_open() ) {
            \ListPlus()->error->add( 'claim_status', __( 'Claim disabled for this listing.', 'list-plus' ) );
        }
        if ( !$listing->is_claimable() ) {
            \ListPlus()->error->add( 'claim_status_2', __( 'You not able to claim this listing.', 'list-plus' ) );
        }
        if ( !$data['name'] ) {
            \ListPlus()->error->add( 'invalid_name', __( 'Please enter your name', 'list-plus' ) );
        }
        if ( !is_email( $data['email'] ) ) {
            \ListPlus()->error->add( 'invalid_email', __( 'Please enter valid email.', 'list-plus' ) );
        }
        if ( !$data['content'] ) {
            \ListPlus()->error->add( 'invalid_message', __( 'Please enter your message.', 'list-plus' ) );
        }
        $data['created_at'] = current_time( 'mysql', true );
        $id = false;
        $entry = false;
        $error_html = false;
        $success_html = false;
        
        if ( !\ListPlus()->error->has_errors() ) {
            $claim = new Claim( $data );
            $claim->save();
            if ( $claim->get_id() ) {
                $id = $claim->get_id();
            }
            $success_html = '<div class="lp-success"><div class="success-msg">' . __( 'Your claim was submitted successfully. It will be shortly reviewed by our team.', 'list-plus' ) . '</div></div>';
        } else {
            $error_html = \ListPlus()->error->to_html();
        }
        
        $respond = [
            'success'      => ( $id ? true : false ),
            'id'           => $id,
            'error_html'   => $error_html,
            'success_html' => $success_html,
        ];
        if ( $respond['success'] ) {
            do_action( 'listplus_submitted_claim', $claim, $listing );
        }
        wp_send_json( $respond );
        die;
    }
    
    public function ajax_save_enquiry()
    {
        \ListPlus()->error->reset();
        $this->maybe_check_captcha();
        $user = wp_get_current_user();
        $field_title = ListPlus()->settings->get( 'enquiry_title' );
        $data = [];
        $data['ip'] = \ListPlus\get_client_ip();
        $data['user_id'] = 0;
        $data['post_id'] = ( isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : '' );
        $data['email'] = ( isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : '' );
        $data['name'] = ( isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '' );
        $data['status'] = 'unread';
        $data['title'] = ( isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '' );
        $data['content'] = ( isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '' );
        
        if ( $user && $user->ID ) {
            $data['user_id'] = $user->ID;
            $data['email'] = $user->user_email;
            $data['name'] = $user->display_name;
            if ( !$data['name'] ) {
                $data['name'] = $user->user_login;
            }
        }
        
        $listing = new Listing( $data['post_id'] );
        if ( !$listing->is_existing_listing() ) {
            \ListPlus()->error->add( 'invalid_listing', __( 'Invalid listing.', 'list-plus' ) );
        }
        if ( !$listing->enquiries_open() ) {
            \ListPlus()->error->add( 'enquiry_status', __( 'Enquiries disabled.', 'list-plus' ) );
        }
        if ( !$data['name'] ) {
            \ListPlus()->error->add( 'invalid_name', __( 'Please enter your name', 'list-plus' ) );
        }
        if ( !is_email( $data['email'] ) ) {
            \ListPlus()->error->add( 'invalid_email', __( 'Please enter valid email.', 'list-plus' ) );
        }
        if ( $field_title ) {
            if ( !$data['title'] ) {
                \ListPlus()->error->add( 'invalid_title', __( 'Please enter your title.', 'list-plus' ) );
            }
        }
        if ( !$data['content'] ) {
            \ListPlus()->error->add( 'invalid_message', __( 'Please enter your message.', 'list-plus' ) );
        }
        $data['created_at'] = current_time( 'mysql', true );
        $id = false;
        $entry = false;
        $error_html = false;
        $success_html = false;
        
        if ( !\ListPlus()->error->has_errors() ) {
            $enquiry = new Enquiry( $data );
            $enquiry->save();
            if ( $enquiry->get_id() ) {
                $id = $enquiry->get_id();
            }
            $success_html = '<div class="lp-success"><div class="success-msg">' . __( 'Thank you for your message. It has been sent.', 'list-plus' ) . '</div></div>';
        } else {
            $error_html = \ListPlus()->error->to_html();
        }
        
        $respond = [
            'success'      => ( $id ? true : false ),
            'id'           => $id,
            'error_html'   => $error_html,
            'success_html' => $success_html,
        ];
        if ( $respond['success'] ) {
            do_action( 'listplus_submitted_enquery', $enquiry, $listing );
        }
        wp_send_json( $respond );
        die;
    }
    
    public function ajax_save_review()
    {
        \ListPlus()->error->reset();
        $this->maybe_check_captcha();
        $user = wp_get_current_user();
        $max = ListPlus()->settings->get( 'review_max' );
        $field_title = ListPlus()->settings->get( 'review_title' );
        $default_status = ListPlus()->settings->get( 'review_default_status', 'approved' );
        $data = [];
        $data['ip'] = \ListPlus\get_client_ip();
        $data['user_id'] = 0;
        $data['post_id'] = ( isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : '' );
        // WPCS: Input var okay, sanitization ok..
        $data['email'] = ( isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : '' );
        // WPCS: Input var okay, sanitization ok..
        $data['name'] = ( isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '' );
        // WPCS: Input var okay, sanitization ok..
        $data['status'] = $default_status;
        $data['title'] = ( isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '' );
        // Input var okay.
        $data['content'] = ( isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '' );
        // Input var okay.
        $data['rating'] = ( isset( $_POST['rating'] ) ? floatval( $_POST['rating'] ) : '' );
        $data['weight'] = 1;
        
        if ( $user && $user->ID ) {
            $data['user_id'] = $user->ID;
            $data['email'] = $user->user_email;
            $data['name'] = $user->display_name;
            if ( !$data['name'] ) {
                $data['name'] = $user->user_login;
            }
        }
        
        $listing = new Listing( $data['post_id'] );
        if ( !$listing->is_existing_listing() ) {
            \ListPlus()->error->add( 'invalid_listing', __( 'Invalid listing.', 'list-plus' ) );
        }
        if ( !$listing->reviews_open() ) {
            \ListPlus()->error->add( 'report_status', __( 'Report disabled for this listing.', 'list-plus' ) );
        }
        if ( !$data['name'] ) {
            \ListPlus()->error->add( 'invalid_name', __( 'Please enter your name', 'list-plus' ) );
        }
        if ( !is_email( $data['email'] ) ) {
            \ListPlus()->error->add( 'invalid_email', __( 'Please enter a valid email.', 'list-plus' ) );
        }
        if ( $field_title ) {
            if ( !$data['title'] ) {
                \ListPlus()->error->add( 'invalid_title', __( 'Please enter your summary.', 'list-plus' ) );
            }
        }
        if ( !$data['rating'] ) {
            \ListPlus()->error->add( 'invalid_message', __( 'Please select your rating.', 'list-plus' ) );
        }
        if ( !$data['content'] ) {
            \ListPlus()->error->add( 'invalid_message', __( 'Please enter your review.', 'list-plus' ) );
        }
        $now = current_time( 'mysql', true );
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $id = false;
        $entry = false;
        $error_html = false;
        $success_html = false;
        
        if ( !\ListPlus()->error->has_errors() ) {
            $review = new Review( $data );
            $review->save();
            
            if ( $review->get_id() ) {
                $id = $review->get_id();
                $success_html = '<div class="lp-success"><div class="success-msg">' . __( 'Thank you for your review. It has been saved.', 'list-plus' ) . '</div></div>';
            } else {
                $error_html = \ListPlus()->error->to_html();
            }
        
        } else {
            $error_html = \ListPlus()->error->to_html();
        }
        
        $respond = [
            'success'      => ( $id ? true : false ),
            'id'           => $id,
            'error_html'   => $error_html,
            'success_html' => $success_html,
        ];
        if ( $respond['success'] ) {
            // Add cron to calc listing rating score.
            ListPlus()->cron->add_review_task( $listing->get_id() );
        }
        if ( $respond['success'] ) {
            do_action( 'listplus_submitted_review', $review, $listing );
        }
        wp_send_json( $respond );
        die;
    }
    
    public function maybe_check_captcha()
    {
        $respond = [
            'success' => false,
        ];
        if ( !ListPlus()->permissions->is_user_admin() ) {
            
            if ( !\ListPlus()->form->verify_captcha() ) {
                \ListPlus()->error->add( 'robot_check', __( 'Please verify that you aren\'t not a robot.', 'list-plus' ) );
                $respond['error_html'] = \ListPlus()->error->to_html();
                $respond['error_codes'] = \ListPlus()->error->to_html();
                wp_send_json( $respond );
            }
        
        }
    }
    
    public function ajax_save_listing()
    {
        ob_start();
        \ListPlus()->error->reset();
        $this->maybe_check_captcha();
        $respond = [
            'success' => false,
        ];
        $post = wp_kses_post_deep( wp_unslash( $_POST ) );
        $type = \ListPlus\get_type_for_editing_listing();
        
        if ( !$type->get_id() ) {
            \ListPlus()->error->add( 'invalid_listing', __( 'The listing type doesn\'t exists.', 'list-plus' ) );
            $respond['error_html'] = \ListPlus()->error->to_html();
            wp_send_json( $respond );
        }
        
        // Check if submit page not set then skip the submit listing on frontend.
        
        if ( !\ListPlus()->permissions->is_user_admin() ) {
            $submit_page = \ListPlus()->settings->submit_page;
            
            if ( !$submit_page ) {
                // If submit listing page not set.
                \ListPlus()->error->add( 'submit_listing_disabed', __( 'Submit listing was disabled by admin.', 'list-plus' ) );
                $respond['error_html'] = \ListPlus()->error->to_html();
                wp_send_json( $respond );
            }
        
        }
        
        $listing_fields = $type->get_fields();
        $validate = new \ListPlus\Validate( $listing_fields, $post );
        $listing_id = ( isset( $post['ID'] ) ? $validate->sanitize_int( $post['ID'] ) : null );
        $listing_data = $validate->get_data();
        $respond['data'] = $listing_data;
        if ( $listing_id ) {
            $listing_data['ID'] = $listing_id;
        }
        $listing_data['listing_type'] = $type->get_slug();
        $listing_data['type_id'] = $type->get_id();
        $default_status = \ListPlus()->settings->get( 'listing_status' );
        // If current user is admin then they can change the listing status.
        
        if ( \ListPlus()->permissions->is_user_admin() ) {
            foreach ( $listing_data as $k => $v ) {
                $post[$k] = $v;
            }
            $listing_data = $post;
            $default_fields = [
                'post_status'    => 'publish',
                'post_author'    => 0,
                'claimed'        => 0,
                'verified'       => 0,
                'enquiry_status' => '',
                'review_status'  => '',
                'claim_status'   => '',
                'report_status'  => '',
                'comment_status' => '',
                'is_featured'    => '',
            ];
            foreach ( $default_fields as $name => $val ) {
                if ( !isset( $listing_data[$name] ) ) {
                    $listing_data[$name] = $val;
                }
            }
        } else {
            $listing_data['post_status'] = $default_status;
        }
        
        
        if ( !$validate->ok() ) {
            \ListPlus()->error->add( 'requried_fields', __( 'Please fill all required fields.', 'list-plus' ) );
            $respond['error_html'] = \ListPlus()->error->to_html();
            $respond['error_codes'] = $validate->get_error_codes();
            wp_send_json( $respond );
        }
        
        foreach ( $listing_data as $k => $v ) {
            if ( is_null( $v ) ) {
                $listing_data[$k] = '';
            }
        }
        $respond['listing_data'] = $listing_data;
        $item = new Listing( $listing_data, $_FILES );
        if ( !$item->support_price_range() ) {
            if ( isset( $listing_data['price'] ) ) {
                $item->price_range = \ListPlus\Helper::to_price_range( $listing_data['price'] );
            }
        }
        // Post date.
        if ( !isset( $item['post_date'] ) || !$item['post_date'] ) {
            $item['post_date'] = \current_time( 'mysql' );
        }
        
        if ( \ListPlus()->error->has_errors() ) {
            $respond['error_html'] = \ListPlus()->error->to_html();
            $respond['error_codes'] = $validate->get_error_codes();
            wp_send_json( $respond );
        }
        
        
        if ( !$item->can_edit() ) {
            \ListPlus()->error->add( 'access_denined', __( 'You have not permisions to submmit listing.', 'list-plus' ) );
            $respond['error_html'] = \ListPlus()->error->to_html();
            wp_send_json( $respond );
        } else {
            if ( 'pending' == $item['post_status'] ) {
                $item['post_status'] = 'pending_review';
            }
            $item->save();
            $item->handle_media_upload();
        }
        
        $success = true;
        $success_html = '';
        
        if ( !\ListPlus()->error->has_errors() ) {
            $respond['success'] = true;
            $respond['success_html'] = '<div class="lp-success"><div class="success-msg">' . __( 'Your listing was submitted successfully. It will be shortly reviewed by our team.', 'list-plus' ) . '</div></div>';
        }
        
        $redirect_url = wp_unslash( $post['_wp_http_referer'] );
        $redirect_url = \html_entity_decode( $redirect_url );
        $redirect_url = add_query_arg( [
            'id' => $item->get_id(),
        ], $redirect_url );
        $respond['redirect_url'] = $redirect_url;
        $html = \ob_get_contents();
        \ob_clean();
        $respond['debug_html'] = $html;
        wp_send_json( $respond );
        die;
    }
    
    public function ajax_save_listing_type()
    {
        \ListPlus()->error->reset();
        $respond = [
            'success'      => __return_false(),
            'success_html' => '',
            'error_html'   => false,
        ];
        $success = true;
        $redirect_url = false;
        $success_html = '';
        $redirect_url = wp_unslash( $_POST['_wp_http_referer'] );
        $redirect_url = \html_entity_decode( $redirect_url );
        
        if ( ListPlus()->permissions->is_user_admin() ) {
            $post = wp_kses_post_deep( wp_unslash( $_POST ) );
            $item = new Listing_Type( $post );
            $item->save();
            $redirect_url = add_query_arg( [
                'id' => $item->get_id(),
            ], $redirect_url );
            $respond['redirect_url'] = $redirect_url;
            // $respond['item_data'] = $item->to_array();
        } else {
            \ListPlus()->error->add( 'access_denined', __( 'You have not permisions to add or edit listing type.', 'list-plus' ) );
        }
        
        
        if ( !\ListPlus()->error->has_errors() ) {
            $respond['success'] = true;
            $respond['success_html'] = __( 'Listing type saved.' );
        } else {
            $error_html = \ListPlus()->error->to_html();
            $respond['error_html'] = $error_html;
        }
        
        wp_send_json( $respond );
        die;
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
    
    public function handle()
    {
        wp_send_json( $_FILES );
        die;
    }

}