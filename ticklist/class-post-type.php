<?php
namespace EM_RSVP\Ticklist;

Class Post_Type {

    public function __construct()
    {

    }

    static function hookup(){
        $self = new self();
        add_action( 'init', array($self, 'register_post_type') );
        
    }

    public function register_post_type() {

        $args = array (
            'label' => esc_html__( 'Ticklists', 'em-rsvp' ),
            'labels' => array(
                'menu_name' => esc_html__( 'Ticklists', 'em-rsvp' ),
                'name_admin_bar' => esc_html__( 'Ticklist', 'em-rsvp' ),
                'add_new' => esc_html__( 'Add new', 'em-rsvp' ),
                'add_new_item' => esc_html__( 'Add new Ticklist', 'em-rsvp' ),
                'new_item' => esc_html__( 'New Ticklist', 'em-rsvp' ),
                'edit_item' => esc_html__( 'Edit Ticklist', 'em-rsvp' ),
                'view_item' => esc_html__( 'View Ticklist', 'em-rsvp' ),
                'update_item' => esc_html__( 'Update Ticklist', 'em-rsvp' ),
                'all_items' => esc_html__( 'All Ticklists', 'em-rsvp' ),
                'search_items' => esc_html__( 'Search Ticklists', 'em-rsvp' ),
                'parent_item_colon' => esc_html__( 'Parent Ticklist', 'em-rsvp' ),
                'not_found' => esc_html__( 'No Ticklists found', 'em-rsvp' ),
                'not_found_in_trash' => esc_html__( 'No Ticklists found in Trash', 'em-rsvp' ),
                'name' => esc_html__( 'Ticklists', 'em-rsvp' ),
                'singular_name' => esc_html__( 'Ticklist', 'em-rsvp' ),
            ),
            'public' => false,
            'description' => 'Ticklists for events.',
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_nav_menus' => false,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'show_in_rest' => true,
            'menu_position' => 25,
            'menu_icon' => 'dashicons-editor-ol',
            'capability_type' => 'post',
            'hierarchical' => false,
            'has_archive' => false,
            'query_var' => true,
            'can_export' => true,
            'rewrite_no_front' => false,
            'supports' => array(
                'title',
                'revisions',
            ),
            'rewrite' => true,
        );
    
        register_post_type( 'ticklist', $args );


        add_submenu_page( 'edit.php?post_type=event', 'Ticklists', 'Ticklists', 'edit_posts', 'ticklists', array($this, 'admin_pages'));        
    }

    public function admin_pages() {
        ?>
        <h2>Ticklists</h2>
        <p>To create ticklists for events, add a new event and select the option to create a ticklist. You will then be able to administer them from this screen.</p>
        <?php
    }
}


