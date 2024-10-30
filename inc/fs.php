<?php

if ( !function_exists( 'fs' ) ) {
    /**
     *  Create a helper function for easy SDK access.
     *
     * @return boolean
     */
    function fs()
    {
        global  $fs ;
        
        if ( !isset( $fs ) ) {
            // Include Freemius SDK.
            require_once LISTPLUS_PATH . '/3rd/fs/start.php';
            $fs = fs_dynamic_init( array(
                'id'             => '5646',
                'slug'           => 'listplus',
                'premium_slug'   => 'listplus-pro',
                'type'           => 'plugin',
                'public_key'     => 'pk_57203264fa5ffb86ed5fb84ac41ca',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'menu'           => array(
                'slug'    => 'edit.php?post_type=listing',
                'support' => false,
                'account' => true,
            ),
                'is_live'        => true,
            ) );
        }
        
        return $fs;
    }
    
    // Init Freemius.
    fs();
    // Signal that SDK was initiated.
    do_action( 'fs_loaded' );
}
