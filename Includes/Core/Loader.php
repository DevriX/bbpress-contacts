<?php namespace BBPC\Includes\Core;

Class Loader
{

    public function init()
    {
        // setup AJAX
        add_action( "wp_ajax_bbp_contacts", array( $this, "ajaxCallback" ) );
        // contacts list via AJAX
        add_action( "wp_ajax_bbp_contacts_list", array( $this, "parseProfileContactsAjax" ) );
        // parse button in bbP profile
        add_action( "bbp_template_after_user_profile", array( $this, "parseProfileButton" ) );
        // place contacts in bbP profile for current user
        add_action( "bbp_template_after_user_profile", array( $this, "parseProfileContacts" ) );
        // manage headers for non-ajax
        add_action( "init", array( $this, "manageHeaders" ) );
        // enqueue scripts
        add_action( "wp_enqueue_scripts", array( $this, "enqueueScripts" ) );
        // add button to bbP forums
        add_action( "bbp_theme_after_reply_author_details", array( $this, "parseForumsButton" ) );
        // apply admin settings: ajax
        add_filter( "bbpc_enable_ajax", array( $this, "applySettingsAjax" ) );
        // apply admin settings: pagination
        add_filter( "bbpc_items_per_page", array( $this, "applySettingsPagination" ) );
    }

    public static function ajaxCallback()
    {
        if ( !isset( $_REQUEST['bbpc_nonce'] ) || !wp_verify_nonce( $_REQUEST['bbpc_nonce'], 'bbpc_nonce' ) ) {
            wp_send_json(
                apply_filters(
                    "bbpc_wp_send_json_args",
                    array("success"=>false, "message"=>"Bad authentication")
                )
            );
        }

        if ( empty( $_REQUEST['contact_ID'] ) ) {
            wp_send_json(
                apply_filters(
                    "bbpc_wp_send_json_args",
                    array("success"=>false, "message"=>"No contact specified or invalid user")
                )
            );
        } else {
            $contact = get_user_by('ID', $_REQUEST['contact_ID']);
            if ( !$contact->ID ) {
                wp_send_json(
                    apply_filters(
                        "bbpc_wp_send_json_args",
                        array("success"=>false, "message"=>"No contact specified or invalid user")
                    )
                );
            }
        }

        if ( !isset( $_REQUEST['task'] ) ) {
            wp_send_json(
                apply_filters(
                    "bbpc_wp_send_json_args",
                    array("success"=>false, "message"=>"No task specified")
                )
            );
        } else {
            $task = esc_attr( strtolower( $_REQUEST['task'] ) );

            if ( "remove" === $task ) {
                $done = (bool) self::removeContact( $contact->ID );
            } else {
                $done = (bool) self::addContact( $contact->ID );
            }

            if ( !$done ) {
                wp_send_json(
                    apply_filters(
                        "bbpc_wp_send_json_args",
                        array("success"=>false, "message"=>"Error occured while adding or removing this contact")
                    )
                );
            } else {
                ob_start();
                self::parseButton( $contact->ID );
                wp_send_json(
                    apply_filters(
                        "bbpc_wp_send_json_args",
                        array("success"=>true, "button"=> ob_get_clean())
                    )
                );
            }
        }
        // end response
        wp_die();
    }

    public static function enqueueScripts()
    {
        if ( is_bbpress() ) { // run only on bbPress pages
            // bbpc plugin DIR url
            $url = plugin_dir_url(BBPC_FILE);
            // this is the style file
            wp_enqueue_style( "bbpc", $url . 'assets/css/style.css' );
            // if ajax is enabled, add JS
            if ( (bool) apply_filters( "bbpc_enable_ajax", true ) ) {
                // this is the JS file for AJAX
                wp_enqueue_script( "bbpc", $url . 'assets/js/bbp-contacts.js', array('jquery') );
                // add BBPC.AJAX object for admin-ajax.php path
                wp_localize_script( "bbpc", "BBPC", array( "AJAX" => admin_url('admin-ajax.php') ) );
            }
        }
    }

    public static function getUserContactsRaw( $user_id )
    {
        $contacts = get_user_meta( $user_id, "bbp_contacts", 1 );
        if ( $contacts ) {
            $contacts = explode( ",", $contacts );
        } else {
            $contacts = array();
        }
        if ( $contacts ) {
            foreach ( $contacts as $i => $uid ) {
                $contacts[$i] = (int) $uid;
            }
            $contacts = array_filter( array_unique( $contacts ) );
            if ( $contacts ) {
                $contacts = array_reverse( $contacts );
            }
        }
        return $contacts;
    }

    public static function addContact( $contact_ID, $this_user = null )
    {
        if ( !$this_user ) {
            global $current_user;
            $this_user = $current_user->ID;
        }
        if ( !$this_user ) return;
        // contacts
        $contacts = self::getUserContactsRaw( $this_user );
        if ( !apply_filters( "bbpc_addContact_pass", true, $contacts, $contact_ID, $this_user ) ) {
            return;
        }
        // push contact
        if ( !in_array($contact_ID, $contacts) ) {
            $contacts[] = $contact_ID;
            $contacts = array_filter( array_unique( $contacts ) );
            update_user_meta( $this_user, "bbp_contacts", implode( ",", $contacts ) );
            return true;
        }
        return;
    }

    public static function removeContact( $contact_ID, $this_user = null )
    {
        if ( !$this_user ) {
            global $current_user;
            $this_user = $current_user->ID;
        }
        if ( !$this_user ) return;
        // get contacts list
        $contacts = self::getUserContactsRaw( $this_user );

        if ( !apply_filters( "bbpc_removeContact_pass", true, $contacts, $contact_ID, $this_user ) ) {
            return;
        }

        if ( $contacts ) {
            if ( !in_array($contact_ID, $contacts) ) {
                return; // contact not there
            } else {
                foreach ( $contacts as $i => $uid ) {
                    if ( (int) $uid === (int) $contact_ID ) {
                        unset( $contacts[$i] );
                        $contacts = array_filter( array_unique( $contacts ) );
                        if ( $contacts ) {
                            update_user_meta( $this_user, "bbp_contacts", implode( ",", $contacts ) );
                        } else {
                            delete_user_meta( $this_user, "bbp_contacts" );
                        }
                        return true;
                        break;
                    }
                }
            }
        } else {
            delete_user_meta( $this_user, "bbp_contacts" );
            return;
        }
    }

    public static function parseButton( $user_id )
    {
        // check if within contacts
        $isContact = self::isContact( $user_id );
        // output
        printf(
            '<a href="%s" class="%s" data-contact-id="%d" data-nonce="%s" data-before="%s">%s</a>',
            defined('DOING_AJAX') && DOING_AJAX ? 'javascript:;' : self::link( $user_id, !$isContact ? 'add' : 'remove' ),
            apply_filters( "bbpc_btn_classes", sprintf("bbpc-btn bbpc-%s", $isContact ? 'remove' : 'add') ),
            $user_id,
            wp_create_nonce('bbpc_nonce'),
            $isContact ? '&times;' : '&plus;',
            $isContact ? self::translate('Remove Contact') : self::translate('Add Contact')
        );
    }

    public static function link( $user_id, $action = 'add', $url = null )
    {
        if ( !$url ) { //
            $url = $_SERVER['REQUEST_URI'];
        }
        return add_query_arg( array(
            "bbpc_{$action}_contact" => $user_id,
            'bbpc_nonce' => wp_create_nonce( 'bbpc_nonce' ),
        ), $url );
    }

    public static function removeParams( $url, $params = array( 'bbpc_add_contact', 'bbpc_remove_contact', 'bbpc_nonce' ) )
    {
        return remove_query_arg( $params, $url );
    }

    public static function isContact( $contact_ID, $this_user = null )
    {
        if ( !$this_user ) {
            global $current_user;
            $this_user = $current_user->ID;
        }
        if ( !$this_user ) return;
        // contacts list
        $contacts = self::getUserContactsRaw( $this_user );
        // check
        return $contacts && in_array( $contact_ID, $contacts );
    }

    public static function translate( $string )
    {
        global $bbpc_translations;

        if ( !isset( $bbpc_translations ) || !is_array( $bbpc_translations ) ) {
            $bbpc_translations = array();

            $terms = array( 'Remove Contact', 'Contacts', 'There are no contacts to show.', 'Add Contact', 'No contacts have matched your search query.', 'My Contacts', 'Search contacts', 'Showing search results for %s:', 'View %s\'s profile', 'Are you sure?', 'Page %1$s/%2$s', 'Next page', 'Previous page' );
            $terms = apply_filters( "bbpc_translations_terms", $terms );

            $data = get_option( "bbpc_translations", array() );

            foreach ( $terms as $i => $term ) {
                $bbpc_translations[$term] = isset( $data[$term] ) ? wp_unslash($data[$term]) : $term;
            }
        }

        return !empty( $bbpc_translations[$string] ) ? $bbpc_translations[$string] : $string;
    }

    public static function parseProfileButton()
    {
        // displayed user
        $user_id = bbp_get_displayed_user_id();
        // current user
        global $current_user;
        // user not logged in | no user
        if ( !$current_user->ID ) return;
        // current user's profile
        if ( $current_user->ID == (int) $user_id ) return;
        // output the button
        print( '<p class="bbpc-container">' );
        self::parseButton( $user_id );
        print( '</p>' );
    }

    public static function manageHeaders()
    {

        if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            return;
        }

        if ( isset( $_GET['bbpc_add_contact'] ) || isset( $_GET['bbpc_remove_contact'] ) ) {

            global $current_user;
            $current_user = $current_user->ID;
            // if user logged out
            if ( !$current_user ) return;

            if ( !empty( $_GET['bbpc_add_contact'] ) ) {
                $contact_ID = (int) $_GET['bbpc_add_contact'];
            } else {
                $contact_ID = (int) $_GET['bbpc_remove_contact'];
                $remove = true;
            }

            // check if user exists
            if ( !get_userdata( $contact_ID )->ID ) return;

            if ( isset( $remove ) ) {
                $done = self::removeContact( $contact_ID, $current_user );
            } else {
                $done = self::addContact( $contact_ID, $current_user );
            }

            $taskArgs = array(
                'task' => isset($remove) ? 'remove' : 'add',
                'contact_ID' => $contact_ID,
                'current_user' => $current_user,
                'task_done' => (bool) $done
            );

            // fire hook
            do_action( "bbpc_noajax_task_done", $taskArgs );
            // redirect to strip params
            $redir = apply_filters( "bbpc_noajax_task_done_redirect", self::removeParams( $_SERVER['REQUEST_URI'] ), $taskArgs );
            // redirect
            if ( trim( $redir ) ) {
                wp_redirect( esc_url( $redir ) );
                exit;
            }
        }

    }

    public static function parseForumsButton()
    {
        // topic/reply author
        $user_id = bbp_get_reply_author_id();
        // current user
        global $current_user;
        // user not logged in | no user
        if ( !$current_user->ID ) return;
        // current user's profile
        if ( $current_user->ID == (int) $user_id ) return;
        // output the button
        print( '<p class="bbpc-container">' );
        self::parseButton( $user_id );
        print( '</p>' );
    }

    public static function parseProfileContactsAjax()
    {
        $user_id = isset( $_REQUEST['user_id'] ) ? (int) $_REQUEST['user_id'] : mull;
        self::parseProfileContacts( $user_id, true );
        wp_die();
    }

    public static function parseProfileContacts( $user_id = null, $ajax = null )
    {
        // logged-out
        if ( !is_user_logged_in() ) return;
        // displayed user
        if ( !$user_id ) {
            $user_id = bbp_get_displayed_user_id();
        }
        // current user
        global $current_user;
        // current user's profile
        if ( $current_user->ID !== (int) $user_id ) {
            /*// allow admins
            if ( !current_user_can('manage_options') ) return;
            $moderated = true;*/
            return;
        }
        // contacts list
        $contacts = self::getUserContactsRaw( $user_id );
        // search term
        $search = trim( isset( $_REQUEST['csearch'] ) ? esc_attr( $_REQUEST['csearch'] ) : '' );
        // paged
        $page = isset( $_REQUEST['page'] ) && (int) $_REQUEST['page'] > 1 ? (int) $_REQUEST['page'] : 1;
        // after
        $pagi_after = null;
        if ( $search ) {
            $contacts = $contacts ? get_users(
                array(
                    "search" => "*{$search}*",
                    "include" => $contacts
                )
            ) : $contacts;
            $pagi_after .= "&csearch={$search}";
        }

        $contacts = apply_filters( "bbpc_contacts", $contacts, $user_id, $current_user, $search, $page );

        printf('<div class="bbp-contacts" data-user-id="%d">', $user_id);

        printf(
            '<p><strong>%s%s:</strong></p>',
            isset($moderated) ? self::translate('Contacts') : self::translate('My Contacts'),
            apply_filters( "bbpc_after_contacts_heading", "", $contacts, $user_id, $search, $page )
        );

        if ( $contacts || $search ) {
            print( apply_filters("bbpc_search_form", sprintf(
                '<form method="get"><p><input type="text" name="csearch" value="%s" placeholder="%s" /></p></form>',
                $search,
                self::translate('Search contacts')
            ), $search, $user_id) );
        }

        if ( $search ) {
            printf('<p><em>%s</em></p>', sprintf(self::translate('Showing search results for %s:'), $search));            
        }

        if ( !$contacts ) {
            if ( $search ) {
                printf('<p>%s</p>',self::translate('No contacts have matched your search query.'));
            } else {
                printf('<p>%s</p>',self::translate('There are no contacts to show.'));
                return;
            }
        }

        $paged = self::paginate( $contacts, apply_filters( "bbpc_items_per_page", 10, $contacts ), "cpage" );

        if ( $paged->data ) {
            foreach ( $paged->data as $user ) {
                if ( !isset($user->ID) ) {
                    $user = get_userdata( $user );
                }
                $profileUrl = bbp_get_user_profile_url( $user->ID );
                $title = sprintf( self::translate( 'View %s\'s profile' ), $user->display_name );

                print('<li>');

                do_action( "bbpc_before_contact_icon", $user );

                printf(
                    '<a href="%1$s" title="%2$s">%3$s</a>',
                    $profileUrl,
                    $title,
                    get_avatar( $user->ID, 55 )
                );

                printf(
                    '<div><a href="%1$s" title="%2$s">%3$s</a> <a href="%5$s" class="rem" title="%4$s" data-nonce="%7$s" data-contact-id="%8$s" data-conf="%6$s" onclick="return confirm(this.dataset.conf)">[x]</a></div>',
                    $profileUrl,
                    $title,
                    $user->display_name,
                    self::translate('Remove Contact'),
                    $ajax ? 'javascript:;' : self::link( $user->ID, 'remove' ),
                    self::translate('Are you sure?'),
                    wp_create_nonce('bbpc_nonce'),
                    $user->ID
                );

                do_action( "bbpc_after_contact_icon", $user );

                print('</li>');
            }
        }

        if ( $paged->pagi->available ) {
    
                print('<div class="pagination">');
                
                if ( $paged->pagi->previous ) {
                    printf( '<a href="%s" title="%s" data-page="%s">&laquo;</a>', $ajax ? 'javascript:;' : '?cpage='. $paged->pagi->previous . $pagi_after, self::translate('Previous page'), $paged->pagi->previous );
                }

                print('<span class="status">');
                printf( self::translate('Page %1$s/%2$s'), $paged->pagi->current_page, $paged->pagi->last_page );
                print('</span>');

                if ( $paged->pagi->next ) {
                    printf( '<a href="%s" title="%s" data-page="%s">&raquo;</a>', $ajax ? 'javascript:;' : '?cpage='. $paged->pagi->next . $pagi_after, self::translate('Next page'), $paged->pagi->next );
                }

                print('</div>');

        }

        print( '</div><!-- /.bbp-contacts -->' );

    }

    public static function paginate( $data, $per_page = 10, $param = 'page' ) {
        $current_page = !empty( $_REQUEST[$param] ) ? (int) $_REQUEST[$param] : 0;
        $current_page = $current_page <= 0 ? 0 : abs($current_page - 1);
        $last_page = abs( count( $data ) / $per_page );
        if( is_float( $last_page ) ) { $last_page = abs(  (int) $last_page + 1 ); }
        if( $current_page > abs( $last_page - 1 ) ) { $current_page = abs( $last_page - 1 ); }
        $offset = abs($per_page * $current_page);
        $next = abs( $current_page + 2 ) <= $last_page ? abs( $current_page + 2 ) : false;
        $previous = $current_page > 0 ? abs( $current_page ) : false;
        $current_page = abs( $current_page + 1 );
        $paged_data = array_slice( $data, $offset, $per_page );
        $paged_data = (object) array(
            "data" => $paged_data,
            "pagi" => (object) array(
                'available' => $last_page > 1,
                'offset' => $offset,
                'current' => $current_page,
                'previous' => $previous,
                'current_page' => $current_page,
                'next' => $next,
                'last_page' => $last_page
            )
        );
        return apply_filters( "bbpc_paginate", $paged_data, $data, $per_page, $param);
    }

    public static function applySettingsAjax( $enable )
    {
        if ( get_option( "bbpc_settings_disableajax" ) ) {
            $enable = false;
        }
        return $enable;
    }

    public static function applySettingsPagination( $per_page )
    {
        $setting = (int) get_option( "bbpc_settings_perpage" );
        if ( $setting ){
            $per_page = $setting;
        }
        return $per_page;
    }


}