<?php namespace BBPC\Includes\Admin;

// prevent direct access
defined('ABSPATH') || exit('No direct access allowed' . PHP_EOL);

class Screen
{
    
    const PAGE = null;

	public function __construct()
	{
        $this->PAGE = !empty($_GET['page']) ? esc_attr($_GET['page']) : null;
        $this->PAGE = str_replace(
            array('bbp-contacts-','bbp-contacts'),
            '',
            $this->PAGE
        );
        $this->html();
	}

    public function html()
    {
        switch ( $this->PAGE ) :

            case 'about':
                $this->about();
                break;

            case 'translate':
                $this->translate();
                break;

            default:
                $this->settings();
                break;

        endswitch;
    }

    public function settings()
    {
    
        $pagi = apply_filters( "bbpc_items_per_page", 10 );
        $ajax = apply_filters( "bbpc_enable_ajax", true );

        if ( isset( $_POST['submit'] ) ) {

            if ( !isset( $_POST['bbpc_nonce'] ) || !wp_verify_nonce( $_POST['bbpc_nonce'], 'bbpc_nonce' ) ) {
                Loader::uiResponse( array( "success" => false, "message" => "Error occured: Bad authentication." ) );
            } else {

                if ( isset($_POST['pagi']) && !empty( $_POST['pagi'] ) ) {
                    update_option( "bbpc_settings_perpage", (int) $_POST['pagi'] );
                    $pagi = (int) $_POST['pagi'];
                } else {
                    delete_option( "bbpc_settings_perpage" );
                    $pagi = 10;
                }

                if ( isset( $_POST['ajax'] ) ) {
                    delete_option( "bbpc_settings_disableajax" );
                    $ajax = true;
                } else {
                    update_option( "bbpc_settings_disableajax", time() );
                    $ajax = false;
                }

                Loader::uiResponse( array( "success" => true, "message" => "Settings saved successfully!" ) );
            }
        }

        global $bbpc_response;
        if ( $bbpc_response && is_array($bbpc_response) ) {
            foreach ( $bbpc_response as $i => $res ) {
                if ( empty( $res['message'] ) ) continue;
                printf('<div class="%s notice is-dismissible"><p>',!empty($res['success'])?'updated':'error');
                printf( $res['message'] );
                print('</p></div>');
            }
        }

        ?>
        <style type="text/css">.bbpc-section{display: block; background: #fff; padding: 1em; padding-top: 0.5em; border: 1px solid #dcdbdb;}</style>

        <form method="post">

            <div class="bbpc-section">
                
                <h3>Pagination</h3>

                <p><label><strong>Contacts Per Page</strong><br/>
                <input type="number" min="1" max="99" name="pagi" value="<?php echo $pagi; ?>" /></label></p>

                <p><em>Select how many contacts to show per user profile.</em></p>

            </div>

            <p></p>

            <div class="bbpc-section">
                    
                <h3>AJAX</h3>

                <p><label><input type="checkbox" name="ajax" <?php checked($ajax); ?> />Check this to enable AJAX.</label></p>

                <p><em>AJAX permits to load the add/remove contact buttons faster, process them, and process the contacts within the bbPress profile page as well..</em></p>

            </div>

            <?php wp_nonce_field('bbpc_nonce','bbpc_nonce'); ?>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes'); ?>" />
                <input type="submit" name="submit" id="submit" class="button" value="<?php _e('Reset Settings'); ?>" style="display:none" />
            </p>

        </form>

        <?php
    }

    public static function translate()
    {

        $terms = array( 'Remove Contact', 'Contacts', 'There are no contacts to show.', 'Add Contact', 'No contacts have matched your search query.', 'My Contacts', 'Search contacts', 'Showing search results for %s:', 'View %s\'s profile', 'Are you sure?', 'Page %1$s/%2$s', 'Next page', 'Previous page' );
        $terms = apply_filters( "bbpc_translations_terms", $terms );

        $values = array();

        $data = get_option( "bbpc_translations", array() );

        foreach ( $terms as $i => $term ) {
            $values[$term] = isset( $data[$term] ) ? wp_unslash($data[$term]) : null;
        }

        if ( isset( $_POST['submit'] ) ) {

            if ( !isset( $_POST['bbpc_nonce'] ) || !wp_verify_nonce( $_POST['bbpc_nonce'], 'bbpc_nonce' ) ) {
                Loader::uiResponse( array( "success" => false, "message" => "Error occured: Bad authentication." ) );
            } else {

                $newValues = array();

                foreach ( $terms as $i => $term ) {
                    $values[$term] = isset( $data[$term] ) ? $data[$term] : null;
                    if ( isset( $_POST['translate'][$i] ) ) {
                        if ( !empty( $_POST['translate'][$i] ) ) {
                            $newValues[$term] = esc_attr( $_POST['translate'][$i] );
                            $values[$term] = wp_unslash( $newValues[$term] );
                        } else {
                            $values[$term] = null;
                        }
                    }
                }

                if ( $newValues ) {
                    update_option( "bbpc_translations", $newValues );
                } else {
                    delete_option( "bbpc_translations" );
                }

                Loader::uiResponse( array( "success" => true, "message" => "Translations saved successfully!" ) );
            }
        }

        global $bbpc_response;
        if ( $bbpc_response && is_array($bbpc_response) ) {
            foreach ( $bbpc_response as $i => $res ) {
                if ( empty( $res['message'] ) ) continue;
                printf('<div class="%s notice is-dismissible"><p>',!empty($res['success'])?'updated':'error');
                printf( $res['message'] );
                print('</p></div>');
            }
        }

        ?>

        <form method="post">

            <div>
                
                <table class="widefat striped">
                    <tr style="background: #ececec; text-align: left;">
                        <th style="text-decoration:underline">Term</th>
                        <th style="text-decoration:underline">Translation</th>
                    </tr>

                    <?php foreach ( $terms as $i => $term ) : ?>

                        <tr>
                            <td valign="top">
                               <label for="term_<?php echo $i; ?>"><code><?php echo esc_attr( $term ); ?></code></label>
                            </td>
                            <td>
                                <textarea style="background-color: #fff;display: inline-block;font-family: Consolas,Monaco,monospace;border: 1px solid #C7C7C7;max-width: 100%;" cols="50" rows="2" name="translate[<?php echo $i; ?>]" id="term_<?php echo $i; ?>"><?php echo esc_attr( $values[$term] ); ?></textarea>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    <tr style="background: #ececec; text-align: left;">
                        <th style="text-decoration:underline">Term</th>
                        <th style="text-decoration:underline">Translation</th>
                    </tr>

                </table>


            </div>

            <?php wp_nonce_field('bbpc_nonce','bbpc_nonce'); ?>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes'); ?>" />
                <input type="submit" name="submit" id="submit" class="button" value="<?php _e('Reset Settings'); ?>" style="display:none" />
            </p>

        </form>

        <?php

    }

    public static function about()
    {
        ?>

        <script type="text/javascript">(function(){var a=document.querySelector('#adminmenu a[href*="options-general.php?page=bbp-contacts"]');null!==a&&(a.parentNode.className="current")})();</script>

        <p>Thank you for using <a href="https://samelh.com/wordpress-plugins/">bbPress Contacts</a>!</p>
        
        <li><a href="https://wordpress.org/support/plugin/bbp-contacts">Support</li>
        <li><a href="https://samelh.com/contact/">Contact Us</li>
        <li><a href="https://wordpress.org/support/plugin/bbp-contacts/reviews/?rate=5#new-post">Rate this plugin</a></li>
        <p style="font-weight:600">More bbPress plugins by Samuel Elh:</p>
        <li><a href="https://go.samelh.com/get/bbpress-ultimate/">bbPress Ultimate</a>: Add more user info to your forums/profiles, e.g online status, user country, social profiles and more..</li>
        <li><a href="https://go.samelh.com/get/bbpress-messages/">bbPress Messages</a>: Adds private messaging functionality to your bbPress forums.</li>
        <li><a href="https://go.samelh.com/get/bbpress-thread-prefixes/">bbPress Thread Prefixes</a>: Easily generate prefixes for topics and assign groups of prefixes for each forum..</li>
        <p style="font-weight:600">Subscribe for more!</p>
        <p>We have upcoming bbPress projects that we are very excited to work on. <a href="https://go.samelh.com/newsletter/">Subscribe to our newsletter</a> to get them first!</p>
        <p style="font-weight:600">Need a custom bbPress plugin? <a href="https://samelh.com/work-with-me/">Hire me!</a></p>

        <?php
    }

}
