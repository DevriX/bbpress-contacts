<?php namespace BBPC\Includes\Admin;

// prevent direct access
defined('ABSPATH') || exit('No direct access allowed' . PHP_EOL);

class Loader
{
    public function __construct()
    {
        // initialize the settings page
        add_action( "admin_menu", array( $this, "AdminMenu" ) );
        // add plugin row meta in plugins list item
        add_filter( "plugin_row_meta", array( $this, "pushRowMetaLinks" ), 10, 2 );
        // add plugin settings link in plugins list item
        add_filter( "plugin_action_links_" . plugin_basename(BBPC_FILE), array( $this, "pushMetaUrls" ) );
    }

    public function AdminMenu()
    {
        add_submenu_page(
            'options-general.php',
            'Settings &lsaquo; bbPress Contacts',
            'bbPress Contacts',
            'manage_options',
            'bbp-contacts',
            array($this, "Screen")
        );

        add_submenu_page(
            null,
            'Translate &lsaquo; bbPress Contacts',
            null,
            'manage_options',
            'bbp-contacts-translate',
            array($this, "Screen")
        );

        add_submenu_page(
            null,
            'About &lsaquo; bbPress Contacts',
            null,
            'manage_options',
            'bbp-contacts-about',
            array($this, "Screen")
        );
    }

    public function Screen()
    {
        $page = !empty( $_GET['page'] ) ? $_GET['page'] : null;
        $p = !empty($page) ? str_replace( array("bbp-contacts-","bbp-contacts"), "", $page ) : "";

        if ( !class_exists('BBPC\Includes\Admin\Screen') ) {
            require BBPC_INC_PATH . "Admin/Screen.php";
        }

        ?>
        <div class="wrap">

            <h2>bbPress Contacts</h2>
    
            <h2 class="nav-tab-wrapper">

                <a class="nav-tab<?php echo!$p?" nav-tab-active":"";?>" href="options-general.php?page=bbp-contacts">
                    <span>Settings</span>
                </a>

                <a class="nav-tab<?php echo"translate"==$p?" nav-tab-active":"";?>" href="options-general.php?page=bbp-contacts-translate">
                    <span>Translate</span>
                </a>

                <a class="nav-tab<?php echo"about"==$p?" nav-tab-active":"";?>" href="options-general.php?page=bbp-contacts-about">
                    <span>About</span>
                </a>

            </h2>

            <p></p>

            <?php new Screen; ?>

        </div>
        <?php
    }

    public static function uiResponse( $new_response )
    {
        if ( is_array($new_response) && isset( $new_response['success'] ) ) {
            global $bbpc_response;
            if ( !is_array($bbpc_response) ) {
                $bbpc_response = array();
            }
            $bbpc_response[] = $new_response;
        }
    }

    public static function pushMetaUrls( $links )
    {
        return array(
            '<a href="' . esc_url( 'options-general.php?page=bbp-contacts' ) . '">' . __( 'Settings' ) . '</a>',
            '<a href="' . esc_url( 'options-general.php?page=bbp-contacts-about' ) . '">' . __( 'About' ) . '</a>'
        ) + $links;
    }

    public static function pushRowMetaLinks( $links, $file ) {
        if ( $file == plugin_basename(BBPC_FILE) ) {
            $links += array(
                'translate' => '<a href="' . esc_url( "options-general.php?page=bbp-contacts-translate" ) . '">' . __( 'Translate' ) . '</a>',
                'support' => '<a href="' . esc_url( "https://wordpress.org/support/plugin/bbp-contacts" ) . '">' . __( 'Support' ) . '</a>',
                'rating' => '<a href="' . esc_url( "https://wordpress.org/support/plugin/bbp-contacts/reviews/?rate=5#new-post" ) . '" title="Thanks for leaving us a rating!">' . __( '&star;&star;&star;&star;&star; rating' ) . '</a>',
            );
        }
        return (array) $links;
    }

}