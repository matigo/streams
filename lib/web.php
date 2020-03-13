<?php

/**
 * @author Jason F. Irwin
 *
 * Class Responds to the Web Data Route and Returns the Appropriate Data
 */
require_once(CONF_DIR . '/versions.php');
require_once(CONF_DIR . '/config.php');
require_once( LIB_DIR . '/functions.php');
require_once( LIB_DIR . '/cookies.php');
require_once( LIB_DIR . '/site.php');

class Route extends Streams {
    var $settings;
    var $strings;
    var $site;

    function __construct( $settings, $strings ) {
        $this->settings = $settings;
        $this->strings = $strings;

        $this->site = new Site($this->settings);
    }

    /* ************************************************************************************** *
     *  Function determines what needs to be done and returns the appropriate HTML Document.
     * ************************************************************************************** */
    public function getResponseData() {
        $ThemeLocation = NoNull($this->settings['theme'], 'default');
        $ReplStr = $this->_getReplStrArray();
        $this->settings['status'] = 200;

        $html = readResource(FLATS_DIR . '/templates/500.html', $ReplStr);
        $ThemeFile = THEME_DIR . '/error.html';
        $LoggedIn = false;

        // Collect the Site Data - Redirect if Invalid
        $data = $this->site->getSiteData();
        if ( is_array($data) ) {
            $RedirectURL = NoNull($data['protocol'] . '://' . $data['HomeURL']);
            $PgRoot = strtolower(NoNull($this->settings['PgRoot']));

            // Set some of the Globals
            $GLOBALS['site_id'] = $data['site_id'];

            // Is There an HTTPS Upgrade Request?
            $Protocol = getServerProtocol();

            // Determine if a Redirect is Required
            if ( strtolower($_SERVER['SERVER_NAME']) != NoNull($data['HomeURL']) ) { $data['do_redirect'] = true; }
            if ( $Protocol != $data['protocol'] ) {
                $suffix = '/' . NoNull($this->settings['PgRoot']);
                if ( $suffix != '' ) {
                    for ( $i = 1; $i <= 9; $i++ ) {
                        $itm = NoNull($this->settings['PgSub' . $i]);
                        if ( $itm != '' ) { $suffix .= "/$itm"; }
                    }
                }

                // Redirect to the Appropriate URL
                redirectTo( $data['protocol'] . '://' . NoNull(str_replace('//', '/', $data['HomeURL'] . $suffix), $this->settings ) );
            }

            // Is this a Syndication Request?
            if ( $this->_isSyndicationRequest($data) ) { exit; }

            // Is this a JSON Request?
            $CType = NoNull($_SERVER["CONTENT_TYPE"], 'text/html');
            if ( strtolower($CType) == 'application/json' ) { $this->_handleJSONRequest($data); }

            // Are We Signing In?
            if ( $PgRoot == 'validatetoken' && NoNull($this->settings['token']) != '' ) {
                $this->settings['remember'] = false;
                $data['do_redirect'] = true;
            }

            // Are We Signed In and Accessing Something That Requires Being Signed In?
            if ( $this->settings['_logged_in'] ) {
                switch ( $PgRoot ) {
                    case 'signout':
                    case 'logout':
                        $RedirectURL = NoNull($_SERVER['HTTP_REFERER'], $data['protocol'] . '://' . $data['HomeURL']);

                        require_once(LIB_DIR . '/auth.php');
                        $auth = new Auth($this->settings);
                        $sOK = $auth->performLogout();
                        unset($auth);

                        $this->settings['remember'] = false;
                        $this->settings['PgRoot'] = '/';
                        $data['do_redirect'] = true;
                        break;

                    case 'receive':
                    case 'collect':
                        $this->_handleZIPRequest();
                        break;

                    case 'settings':
                    case 'messages':
                    case 'account':
                    case 'export':
                    case 'write':
                        if ( NoNull($this->settings['_access_level'], 'read') != 'write' ) {
                            $this->settings['status'] = 403;
                            redirectTo( $data['protocol'] . '://' . $data['HomeURL'] . '/403', $this->settings );
                        }
                        break;

                    default:
                        /* Do Nothing Here */
                }
            }

            // Are We NOT Signed In and Accessing Something That Requires Being Signed In?
            if ( $this->settings['_logged_in'] === false ) {
                $checks = array('write', 'export', 'account', 'settings', 'messages');
                $route = strtolower($this->settings['PgRoot']);

                if ( in_array($route, $checks) ) {
                    $this->settings['status'] = 403;
                    redirectTo( $data['protocol'] . '://' . $data['HomeURL'] . '/403', $this->settings );
                }
            }

            // Is There a Language Change Request?
            if ( strtolower(NoNull($this->settings['PgRoot'])) == 'lang' ) {
                $val = NoNull($this->settings['PgSub1'], $this->settings['_language_code']);
                if ( $val != '' ) {
                    if ( $val != NoNull($this->settings['_language_code']) ) {
                        setcookie('DispLang', $val, 3600, "/", NoNull(strtolower($_SERVER['SERVER_NAME'])) );

                        // If We're Signed In on a 10C site, set the Language
                        if ( NoNull($this->settings['token']) != '' ) {
                            require_once(LIB_DIR . '/account.php');
                            $acct = new Account($this->settings);
                            $isOK = $acct->setAccountLanguage($val);
                            unset($acct);
                        }
                    }
                    $data['do_redirect'] = true;
                }
            }

            // Perform the Redirect if Necessary
            $suffix = ( YNBool($this->settings['remember']) ) ? '?remember=Y' : '';
            if ( $data['do_redirect'] ) { redirectTo( $RedirectURL . $suffix, $this->settings ); }

            // Load the Requested HTML Content
            $html = $this->_getPageHTML( $data );
        }

        // Return the HTML With the Appropriate Headers

        unset($this->strings);
        unset($this->site);
        return $html;
    }

    /**
     *  Function Returns the Response Type (HTML / XML / JSON / etc.)
     */
    public function getResponseType() {
        return NoNull($this->settings['type'], 'text/html');
    }

    /**
     *  Function Returns the Reponse Code (200 / 201 / 400 / 401 / etc.)
     */
    public function getResponseCode() {
        return nullInt($this->settings['status'], 200);
    }

    /**
     *  Function Returns any Error Messages that might have been raised
     */
    public function getResponseMeta() {
        return is_array(NoNull($this->settings['errors'])) ? $this->settings['errors'] : false;
    }

    /** ********************************************************************** *
     *  Private Functions
     ** ********************************************************************** */
    /**
     *  Function Returns an Array With the Appropriate Content
     */
    private function _getPageHTML( $data ) {
        $LockPrefix = '';
        if ( $data['site_locked'] ) { $LockPrefix = getRandomString(18); }

        // If Caching Is Enabled, Check If We Have a Valid Cached Version
        $cache_file = md5($data['site_version'] . '-' . NoNull($LockPrefix, APP_VER . CSS_VER) . '-' .
                          nullInt($this->settings['_token_id']) . '.' . nullInt($this->settings['_persona_id']) . '-' .
                          NoNull($this->settings['ReqURI'], '/') . '-' . nullInt($this->settings['page']));
        if ( defined('ENABLE_CACHING') ) {
            if ( nullInt(ENABLE_CACHING) == 1 ) {
                $html = readCache($data['site_id'], $cache_file);
                if ( $html !== false ) {
                    $this->_getLanguageStrings($data['location']);
                    $SiteLogin = NoNull($this->strings['lblLogin']);
                    if ( $this->settings['_logged_in'] ) { $SiteLogin = '&nbsp;'; }

                    $ReplStr = array( '[lblSiteLogin]'       => NoNull($SiteLogin, $this->strings['lblLogin']),
                                      '[PERSONA_GUID]'       => NoNull($this->settings['_persona_guid']),
                                      '[SITE_OPSBAR]'        => $this->_getSiteOpsBar($data),
                                      '[POPULAR_LIST]'       => $this->_getPopularPosts(),
                                      '[lblContactValidate]' => $this->_getContactQuestion(),
                                      '[NONCE]'              => $this->_getNonceValue(),
                                      '[GenTime]'            => $this->_getRunTime(),
                                     );
                    return str_replace(array_keys($ReplStr), array_values($ReplStr), $html);
                }
            }
        }

        // If We're Here, We Need to Build the File
        $ThemeLocation = THEME_DIR . '/' . $data['location'];
        if ( checkDIRExists($ThemeLocation) === false ) { $data['location'] = 'default'; }

        if ( $data['site_locked'] ) {
            $HomeUrl = NoNull($this->settings['HomeURL']);
            $ReplStr =  array( '[FONT_DIR]'     => $HomeUrl . '/templates/fonts',
                               '[CSS_DIR]'      => $HomeUrl . '/templates/css',
                               '[IMG_DIR]'      => $HomeUrl . '/templates/img',
                               '[JS_DIR]'       => $HomeUrl . '/templates/js',
                               '[HOMEURL]'      => $HomeUrl,

                               '[CSS_VER]'      => CSS_VER,
                               '[GENERATOR]'    => GENERATOR . " (" . APP_VER . ")",
                               '[APP_NAME]'     => APP_NAME,
                               '[APP_VER]'      => APP_VER,
                               '[LANG_CD]'      => NoNull($this->settings['_language_code'], $this->settings['DispLang']),
                               '[YEAR]'         => date('Y'),

                               '[CHANNEL_GUID]' => NoNull($data['channel_guid']),
                               '[CLIENT_GUID]'  => NoNull($data['client_guid']),
                               '[TOKEN]'        => ((YNBool($this->settings['_logged_in'])) ? NoNull($this->settings['token']) : ''),
                               '[NONCE]'        => $this->_getNonceValue(),

                               '[SITE_URL]'     => $this->settings['HomeURL'],
                               '[SITE_NAME]'    => NoNull($data['name']),
                               '[SITE_COLOR]'   => NoNull($data['color'], 'auto'),
                              );
            if ( is_array($this->strings) ) {
                foreach ( $this->strings as $key=>$val ) {
                    $ReplStr["[$key]"] = $val;
                }
            }
            $html = readResource( FLATS_DIR . "/templates/unlock.html", $ReplStr );

        } else {
            // Collect the Preliminary Values
            $this->_getLanguageStrings($data['location']);
            $ReplStr = $this->_getContentArray($data);
            $ReqFile = $this->_getContentPage($data);

            // Populate the Appropriate Language Strings
            if ( is_array($this->strings) ) {
                foreach ( $this->strings as $Key=>$Value ) {
                    $ReplStr["[$Key]"] = NoNull($Value);
                }

                // Set the Site Login String
                $SiteLogin = NoNull($this->strings['lblLogin']);
                if ( $this->settings['_logged_in'] ) { $SiteLogin = '&nbsp;'; }
                $ReplStr['[lblSiteLogin]'] = NoNull($SiteLogin, $this->strings['lblLogin']);
            }

            // If We're Here, We Have Data to Show
            $ReplStr['[PAGEHTML]'] = $this->_getPageContent($data);
            $ReplStr['[CONTENT]'] = readResource($ReqFile, $ReplStr);
            $ReplStr['[NAVMENU]'] = $this->_getSiteNav($data);
            $html = readResource( THEME_DIR . "/" . $data['location'] . "/base.html", $ReplStr );

            // Save the File to Cache if Required and Populate the Base Sections
            if ( defined('ENABLE_CACHING') ) {
                if ( nullInt(ENABLE_CACHING) == 1 ) {
                    saveCache($data['site_id'], $cache_file, $html);

                    $SiteLogin = NoNull($this->strings['lblLogin']);
                    if ( $this->settings['_logged_in'] ) { $SiteLogin = '&nbsp;'; }
                    $ReplStr = array( '[lblSiteLogin]' => NoNull($SiteLogin, $this->strings['lblLogin']),
                                      '[PERSONA_GUID]' => NoNull($this->settings['_persona_guid']),
                                      '[SITE_OPSBAR]'  => $this->_getSiteOpsBar($data),
                                      '[POPULAR_LIST]' => $this->_getPopularPosts(),
                                     );
                    $html = str_replace(array_keys($ReplStr), array_values($ReplStr), $html);
                }
            }
        }

        // Ensure the Contact Validation Element Is Set (if exists)
        $ReplStr = array( '[lblContactValidate]' => $this->_getContactQuestion() );
        $html = str_replace(array_keys($ReplStr), array_values($ReplStr), $html);

        // Get the Run-time
        $runtime = $this->_getRunTime();

        // Return HTML Page Content
        return str_replace('[GenTime]', $runtime, $html);
    }

    /**
     *  Function parses and handles requests for ZIP Files (generally exports)
     */
    private function _handleZIPRequest() {
        $ZipDIR = TMP_DIR . '/export/' . strtolower(NoNull($this->settings['PgSub1']));
        $ZipFile = NoNull($this->settings['PgSub2']);

        /* If No File is Specified, Find the first ZIP in the Directory */
        if ( $ZipFile == '' ) {
            foreach (glob("$ZipDIR/*.zip") as $fileName) {
                if ( $ZipFile == '' ) { $ZipFile = NoNull($fileName); }
            }
        }

        /* If we have a file and it appears valid, send it */
        if ( $ZipFile != '' && file_exists($ZipFile) && filesize($ZipFile) > 0 ) { sendZipFile($ZipFile); }
    }

    /**
     *  Function Parses and Handles Requests that Come In with an Application/JSON Header
     */
    private function _handleJSONRequest( $site ) {
        $Action = strtolower(NoNull($this->settings['PgSub1'], $this->settings['PgRoot']));
        $format = strtolower(NoNull($_SERVER['CONTENT_TYPE'], 'text/plain'));
        $valids = array( 'application/json' );
        $meta = array();
        $data = false;
        $code = 401;

        if ( in_array($format, $valids) ) {
            switch ( $Action ) {
                case 'profile':
                    require_once(LIB_DIR . '/account.php');
                    $acct = new Account( $this->settings, $this->strings );
                    $data = $acct->getPublicProfile();
                    $meta = $acct->getResponseMeta();
                    $code = $acct->getResponseCode();
                    unset($acct);
                    break;

                default:
                    require_once(LIB_DIR . '/posts.php');
                    $post = new Posts( $this->settings, $this->strings );
                    $data = $post->getPageJSON( $site );
                    $meta = $post->getResponseMeta();
                    $code = $post->getResponseCode();
                    unset($post);
            }
        }

        // If We Have an Array of Data, Return It
        if ( is_array($data) ) { formatResult($data, $this->settings, 'application/json', $code, $meta); }
    }

    /**
     *  Function Returns the Requisite Content That People Would Expect to See on a
     *      page based on its purpose.
     */
    private function _getPageContent( $data ) {
        $ThemeLocation = THEME_DIR . '/' . $data['location'];

        // Is there a custom.php file in the theme that will provide the requisite data?
        $ResDIR = $ThemeLocation . "/resources";
        if ( file_exists("$ThemeLocation/custom.php") ) {
            require_once("$ThemeLocation/custom.php");
            $ClassName = ucfirst($data['location']);

            $res = new $ClassName( $this->settings, $this->strings );
            $html = $res->getPageHTML($data);
            $this->settings['errors'] = $res->getResponseMeta();
            $this->settings['status'] = $res->getResponseCode();
            unset($res);

            // Return the Completed HTML
            return $html;

        } else {
            require_once( LIB_DIR . '/posts.php' );
            $post = new Posts( $this->settings, $this->strings );
            $html = $post->getPageHTML($data);

            $this->settings['status'] = $post->getResponseCode();
            $this->settings['errors'] = $post->getResponseMeta();
            unset($post);

            // Return the Completed HTML
            return $html;
        }

        // If We're Here, Something's Wrong
        return "This is a bad error message ...";
    }

    /**
     *  Function Returns the Pagingation Bar if Applicable
     */
    private function _getPagination( $data ) {
        $Excludes = array( 'write', 'settings', 'account' );
        $CanonURL = $this->_getCanonicalURL();
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        $tObj = strtolower(str_replace('/', '', $CanonURL));

        $posts = 0;
        $pages = 0;

        $rslt = getPaginationSets();
        if ( is_array($rslt) === false && in_array($PgRoot, $Excludes) === false ) {
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                              '[SITE_TOKEN]' => sqlScrub(NoNull($this->settings['site_token'])),
                              '[SITE_GUID]'  => sqlScrub($data['site_guid']),
                              '[CANON_URL]'  => sqlScrub($CanonURL),
                              '[PGROOT]'     => sqlScrub($PgRoot),
                              '[OBJECT]'     => sqlScrub($tObj),
                              '[PGSUB1]'     => sqlScrub($this->settings['PgSub1']),
                             );
            $sqlStr = prepSQLQuery("CALL GetSitePagination([ACCOUNT_ID], '[SITE_GUID]', '[SITE_TOKEN]', '[CANON_URL]', '[PGROOT]', '[OBJECT]', '[PGSUB1]');", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
        }

        // Let's build some Pagination
        if ( is_array($rslt) ) {
            setPaginationSets($rslt);
            foreach ( $rslt as $Row ) {
                $posts = nullInt($Row['post_count']);
                $pages = nullInt($Row['page_count']);
            }
        }

        // Construct Some Pagination Only If There Is More than One Page
        if ( $pages > 1 ) {
            $base_url = NoNull($this->settings['HomeURL']) . $CleanReq;
            $html = '';

            $max = nullInt($this->settings['page'], 1) + 2;
            $min = nullInt($this->settings['page'], 1) - 2;
            if ( $min < 1 ) {
                $max = 5;
                $min = 1;
            }
            for ( $i = 1; $i <= $pages; $i++ ) {
                $atag = '<a href="' .$base_url . '?page=' . $i . '" title="">' . $i . '</a>';
                $actv = '';
                $visible = false;

                if ( $i == 1 ) { $atag = '<a href="' .$base_url . '" title="">' . $i . '</a>'; }
                if ( $i == nullInt($this->settings['page'], 1) ) {
                    $actv = ' selected';
                    $atag = $i;
                }

                if ( $i == 1 ) { $visible = true; }
                if ( $i >= $min && $i <= $max ) { $visible = true; }
                if ( $i == $pages ) { $visible = true; }

                // If the Item is Visible, Add it
                if ( $i == ($min - 1) && $html != '' ) { $html .= '<li class="page">&hellip;</li>'; }
                if ( $visible ) { $html .= '<li class="page">' . $atag. '</li>'; }
                if ( $i == ($max + 1) && $i < ($pages - 1) ) { $html .= '<li class="page">&hellip;</li>'; }
            }

            // If We Have a List, Send It
            if ( $html != '' ) { return '<ul class="page-list" data-posts="' . $posts . '">' . $html . '</ul>'; }
        }

        // If We're Here, No Pagination is Required
        return '';
    }

    /**
     *  Collect the Language Strings that Will Be Used In the Theme
     *  Note: The Default Theme Language is Loaded First To Reduce the Risk of NULL Descriptors
     */
    private function _getLanguageStrings( $Location ) {
        $ThemeLocation = THEME_DIR . '/' . $Location;
        if ( checkDIRExists($ThemeLocation) === false ) { $ThemeLocation = THEME_DIR . '/default'; }
        $rVal = array();

        // Collect the Default Langauge Strings
        $LangFile = "$ThemeLocation/lang/" . strtolower(DEFAULT_LANG) . '.json';
        if ( file_exists( $LangFile ) ) {
            $json = readResource( $LangFile );
            $items = objectToArray(json_decode($json));

            if ( is_array($items) ) {
                foreach ( $items as $Key=>$Value ) {
                    $rVal["$Key"] = NoNull($Value);
                }
            }
        }

        // Is Multi-Lang Enabled And Required? If So, Load It
        $LangCode = NoNull($this->settings['DispLang'], $this->settings['_language_code']);
        if ( ENABLE_MULTILANG == 1 && (strtolower($LangCode) != strtolower(DEFAULT_LANG)) ) {
            $LangFile = "$ThemeLocation/lang/" . strtolower($LangCode) . '.json';
            if ( file_exists( $LangFile ) ) {
                $json = readResource( $LangFile );
                $items = objectToArray(json_decode($json));

                if ( is_array($items) ) {
                    foreach ( $items as $Key=>$Value ) {
                        $rVal["$Key"] = NoNull($Value);
                    }
                }
            }
        }

        // Do We Have a Special File for the Page?
        $LangFile = "$ThemeLocation/lang/" . NoNull($this->settings['PgRoot']) . '_' . strtolower(DEFAULT_LANG) . '.json';
        if ( file_exists( $LangFile ) ) {
            $json = readResource( $LangFile );
            $items = objectToArray(json_decode($json));

            if ( is_array($items) ) {
                foreach ( $items as $Key=>$Value ) {
                    $rVal["$Key"] = NoNull($Value);
                }
            }
        }

        $LangCode = NoNull($this->settings['DispLang'], $this->settings['_language_code']);
        if ( ENABLE_MULTILANG == 1 && (strtolower($LangCode) != strtolower(DEFAULT_LANG)) ) {
            $LangFile = "$ThemeLocation/lang/" . NoNull($this->settings['PgRoot']) . '_' . strtolower($LangCode) . '.json';
            if ( file_exists( $LangFile ) ) {
                $json = readResource( $LangFile );
                $items = objectToArray(json_decode($json));

                if ( is_array($items) ) {
                    foreach ( $items as $Key=>$Value ) {
                        $rVal["$Key"] = NoNull($Value);
                    }
                }
            }
        }

        // Update the Language Strings for the Class
        if ( is_array($rVal) ) {
            foreach ( $rVal as $Key=>$Value ) {
                $this->strings = $rVal;
            }
        }
    }

    /**
     *  Function Returns the 10C Ops Bar for a Site (if available) so long as the
     *      current person has a valid Authentication Token
     */
    private function _getSiteOpsBar( $data ) {
        $OpsBarFile = THEME_DIR . '/' . $data['location'] . '/resources/nav-opsbar.html';

        if ( $this->settings['_logged_in'] ) {
            if ( file_exists( $OpsBarFile ) ) {
                require_once(LIB_DIR . '/contact.php');
                $msgs = new Contact($this->settings);
                $data = $msgs->getMessageCount();
                unset($msgs);

                $SiteUrl = NoNull($this->settings['HomeURL']);
                $ReplStr = array( '[AVATAR_IMG]'    => $SiteUrl . '/avatars/' . NoNull($this->settings['_avatar_file'], 'default.png'),
                                  '[ACCESS_LEVEL]'  => NoNull($this->settings['_access_level'], 'read'),
                                  '[DISPLAY_NAME]'  => NoNull($this->settings['_display_name']),
                                  '[PERSONA_GUID]'  => NoNull($this->settings['_persona_guid']),
                                  '[STORAGE_TOTAL]' => nullInt($this->settings['_storage_total']),
                                  '[STORAGE_USED]'  => nullInt($this->settings['_storage_used']),
                                  '[HOMEURL]'       => NoNull($this->settings['HomeURL']),

                                  '[UNREAD_COUNT]'  => nullInt($data['unread']),
                                  '[UNREAD_CLASS]'  => ((nullInt($data['unread']) > 0) ? '' : ' hidden'),
                                  '[MY_HOME]'       => NoNull($data['my_home']),
                                 );
                if ( is_array($this->strings) ) {
                    foreach ( $this->strings as $key=>$val ) {
                        $ReplStr["[$key]"] = $val;
                    }
                }

                return readResource( $OpsBarFile, $ReplStr );
            }
        }

        // If We're Here, There's Nothing
        return '';
    }

    /**
     *  Function Collects the Navigation Bar for the Site
     */
    private function _getSiteNav( $data ) {
        $ThemeLocation = THEME_DIR . '/' . $data['location'];
        $html = '';

        // Is there a custom.php file in the theme that will provide the requisite data?
        $ResDIR = $ThemeLocation . "/resources";
        if ( file_exists("$ThemeLocation/custom.php") ) {
            require_once("$ThemeLocation/custom.php");
            $ClassName = ucfirst($data['location']);
            $res = new $ClassName( $this->settings, $this->strings );
            if ( function_exists($res->getSiteNav) ) {
                $html = $res->getSiteNav($data);
                $this->settings['errors'] = $res->getResponseMeta();
                $this->settings['status'] = $res->getResponseCode();
            }
            unset($res);

        } else {
            $ReplStr = array( '[SITE_ID]' => nullInt($data['site_id']) );
            $sqlStr = prepSQLQuery("CALL GetSiteNav([SITE_ID]);", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $SiteUrl = $data['protocol'] . '://' . $data['HomeURL'];
                foreach ( $rslt as $Row ) {
                    if ( YNBool($Row['is_visible']) ) {
                        if ( $html != '' ) { $html .= "\r\n"; }
                        $lblName = NoNull($this->strings[$Row['label']], $Row['label']);
                        $html .= tabSpace(5) . '<li class="main-nav__item">' .
                                    '<a href="' . $SiteUrl . NoNull($Row['url']) . '" title="">' . NoNull($Row['title'], $lblName) . '</a>' .
                                 '</li>';
                    }
                }
            }
        }

        // Return the Completed HTML if it Exists
        return $html;
    }

    private function _getContentArray( $data ) {
        $SiteUrl = NoNull($data['protocol'] . '://' . $data['HomeURL'] . '/themes/' . $data['location']);
        $HomeUrl = NoNull($data['protocol'] . '://' . $data['HomeURL']);
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        $ApiUrl = getApiUrl();
        $CdnUrl = getCdnUrl();

        // Get the Banner (if one exists)
        $banner_img = NoNull($data['banner_img']);
        if ( NoNull($banner_img) == '' ) { $banner_img = NoNull($data['protocol'] . '://' . $data['HomeURL'] . '/images/default_banner.jpg'); }

        // Get the Shortcut Icon (if one exists)
        $short_ico = $HomeUrl . '/avatars/' . NoNull($data['site_icon'], 'favicon.png');
        if ( is_array($data) && is_array($data['personas']) ) {
            foreach ( $data['personas'] as $pa ) {
                if ( NoNull($pa['avatar_img']) != '' ) { $short_ico = NoNull($pa['avatar_img']); }
            }
        }
        $ico_type = getFileExtension($short_ico);
        $ico_mime = getMimeFromExtension($ico_type);

        // Construct the Core Array
        $rVal = array( '[FONT_DIR]'     => $SiteUrl . '/fonts',
                       '[CSS_DIR]'      => $SiteUrl . '/css',
                       '[IMG_DIR]'      => $SiteUrl . '/img',
                       '[JS_DIR]'       => $SiteUrl . '/js',
                       '[HOMEURL]'      => NoNull($this->settings['HomeURL']),
                       '[API_URL]'      => NoNull($data['api_url'], $ApiUrl),
                       '[CDN_URL]'      => NoNull($data['cdn_url'], $CdnUrl),

                       '[CSS_VER]'      => CSS_VER,
                       '[GENERATOR]'    => GENERATOR . " (" . APP_VER . ")",
                       '[APP_NAME]'     => APP_NAME,
                       '[APP_VER]'      => APP_VER,
                       '[LANG_CD]'      => NoNull($this->settings['_language_code'], $this->settings['DispLang']),
                       '[AVATAR_URL]'   => NoNull($this->settings['HomeURL']) . '/avatars/' . $this->settings['_avatar_file'],
                       '[UPDATED_AT]'   => NoNull($data['updated_at']),
                       '[SHORT_ICO]'    => NoNull($short_ico),
                       '[MIME_ICO]'     => NoNull($ico_mime, 'image/png'),
                       '[PGSUB_1]'      => NoNull($this->settings['PgSub1']),
                       '[YEAR]'         => date('Y'),

                       '[CHANNEL_GUID]' => NoNull($data['channel_guid']),
                       '[CLIENT_GUID]'  => NoNull($data['client_guid']),
                       '[PERSONA_GUID]' => NoNull($this->settings['_persona_guid']),
                       '[TOKEN]'        => ((YNBool($this->settings['_logged_in'])) ? NoNull($this->settings['token']) : ''),
                       '[NONCE]'        => $this->_getNonceValue(),

                       '[SITE_URL]'     => $this->settings['HomeURL'],
                       '[SITE_NAME]'    => $data['name'],
                       '[SITEDESCR]'    => $data['description'],
                       '[SITEKEYWD]'    => $data['keywords'],
                       '[SITE_COLOR]'   => NoNull($data['color'], 'auto'),
                       '[PAGE_TITLE]'   => $this->_getPageTitle($data),
                       '[META_TITLE]'   => $this->_getPageTitle($data, true),
                       '[META_DOMAIN]'  => NoNull($data['HomeURL']),
                       '[META_TYPE]'    => NoNull($data['page_type'], 'website'),
                       '[META_DESCR]'   => NoNull($data['description']),
                       '[CSS_EXTEND]'   => $this->_getCustomCSS($data),
                       '[FONT_SIZE]'    => NoNull($data['font-size'], 'md'),

                       '[PREF_CONMAIL]' => (($this->settings['_send_contact_mail']) ? ' checked' : ''),
                       '[PAGE_URL]'     => $this->_getPageURL($data),
                       '[ACCESS_LEVEL]' => NoNull($this->settings['_access_level'], 'read'),
                       '[BANNER_IMG]'   => $banner_img,
                       '[AUTHOR_TOOLS]' => $this->_getAuthoringTools($data),
                       '[SETTINGS]'     => $this->_getSettingsPanel($data),
                       '[PAGINATION]'   => $this->_getPagination($data),
                       '[POST_CLASS]'   => '',
                      );

        // Is there anything Extra that's required?
        $extra = $this->_getPrivateArray( $data );
        if ( is_array($extra) ) {
            foreach ( $extra as $Key=>$Value ) {
                $rVal[ strtoupper($Key) ] = $Value;
            }
        }

        // Add the Requisite Items when Caching is Not Enabled
        if ( defined('ENABLE_CACHING') ) {
            if ( nullInt(ENABLE_CACHING) != 1 ) {
                $rVal['[SITE_OPSBAR]'] = $this->_getSiteOpsBar($data);
                $rVal['[POPULAR_LIST]'] = $this->_getPopularPosts();
            }
        }

        // Return the Strings
        return $rVal;
    }

    /**
     *  Function returns an array for _getContentArray() based on the PgRoot value
     */
    private function _getPrivateArray( $data ) {
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        switch ( $PgRoot ) {
            case 'account':
                $ReplStr = array( '[TOKEN_GUID]' => NoNull($this->settings['_token_guid']),
                                  '[TOKEN_ID]'   => nullInt($this->settings['_token_id']),
                                 );
                $sqlStr = readResource(SQL_DIR . '/account/getAccountDetail.sql', $ReplStr);
                $rslt = doSQLQuery($sqlStr);
                if ( is_array($rslt) ) {
                    foreach ( $rslt as $Row ) {
                        return array( '[ACCOUNT_ID]'   => nullInt($Row['account_id']),
                                      '[FIRST_NAME]'   => NoNull($Row['first_name']),
                                      '[LAST_NAME]'    => NoNull($Row['last_name']),
                                      '[DISPLAY_NAME]' => NoNull($Row['display_name']),
                                      '[LANG_CODE]'    => NoNull($Row['language_code']),
                                      '[CREATED_AT]'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                                      '[CREATED_UNIX]' => strtotime($Row['created_at']),

                                      '[ACCT_MAIL]'    => strtolower(NoNull($Row['email'], $this->settings['_email'])),
                                      '[TIMEZONES]'    => $this->_getTimezoneList(),

                                      '[SITE_GEO]'     => (YNBool($Row['show_geo']) ? ' checked' : ''),
                                      '[SHOW_NOTE]'    => (YNBool($Row['show_reminder']) ? ' checked' : ''),

                                      '[SUB_ACTIVE]'   => NoNull($Row['sub_active'], 'N'),
                                      '[SUB_UNTIL]'    => date("Y-m-d", strtotime($Row['sub_until'])),
                                      '[SUB_UUNIX]'    => strtotime($Row['sub_until']),
                                      '[SUB_CLASS]'    => ((NoNull($Row['sub_active'], 'N') == 'N') ? ' hidden' : ''),
                                      '[SUB_RESUB]'    => ((NoNull($Row['sub_active'], 'N') == 'N') ? '' : ' hidden'),
                                      '[SUB_UMSG]'     => ((NoNull($Row['sub_active'], 'N') == 'N') ? $this->strings['lblPaypalSubExpyd'] : $this->strings['lblPaypalSubUntil']),

                                      '[ERR_CLASS]'    => ((NoNull($this->settings['err']) == '') ? ' hidden' : ''),
                                      '[ERR_MSG]'      => $this->_getErrorMessage(),
                                     );
                    }
                }
                break;

            case 'settings':
                $SitePass = '';
                if ( $data['channel_privacy'] == 'visibility.password' ) {
                    $SitePass = str_repeat('*', 12);
                }

                return array( '[SITE_NOTE]'    => $this->_checkboxValue($data, 'show_note'),
                              '[SITE_ARTICLE]' => $this->_checkboxValue($data, 'show_article'),
                              '[SITE_BMARKS]'  => $this->_checkboxValue($data, 'show_bookmark'),
                              '[SITE_PLACES]'  => $this->_checkboxValue($data, 'show_location'),
                              '[SITE_PHOTOS]'  => $this->_checkboxValue($data, 'show_photo'),
                              '[SITE_QUOTES]'  => $this->_checkboxValue($data, 'show_quotation'),
                              '[SITE_GEO]'     => $this->_checkboxValue($data, 'show_geo'),
                              '[SITE_LOCKED]'  => (($SitePass != '') ? ' checked' : ''),
                              '[SITE_PASS]'    => $SitePass,

                              '[FAMILY_AUTO]'     => $this->_selectValue($data, 'font-family', 'auto'),
                              '[FAMILY_LATO]'     => $this->_selectValue($data, 'font-family', 'lato'),
                              '[FAMILY_LIBRE]'    => $this->_selectValue($data, 'font-family', 'librebaskerville'),
                              '[FAMILY_OPENSANS]' => $this->_selectValue($data, 'font-family', 'open-sans'),
                              '[FAMILY_QSAND]'    => $this->_selectValue($data, 'font-family', 'quicksand'),
                              '[FAMILY_UBUNTU]'   => $this->_selectValue($data, 'font-family', 'ubuntu'),

                              '[FSIZE_XS]'     => $this->_selectValue($data, 'font-size', 'xs'),
                              '[FSIZE_SM]'     => $this->_selectValue($data, 'font-size', 'sm'),
                              '[FSIZE_MD]'     => $this->_selectValue($data, 'font-size', 'md'),
                              '[FSIZE_LG]'     => $this->_selectValue($data, 'font-size', 'lg'),
                              '[FSIZE_XL]'     => $this->_selectValue($data, 'font-size', 'xl'),
                              '[FSIZE_XX]'     => $this->_selectValue($data, 'font-size', 'xx'),

                              '[THEME_LIGHT]'  => $this->_selectValue($data, 'color', 'light'),
                              '[THEME_DARK]'   => $this->_selectValue($data, 'color', 'dark'),
                              '[THEME_AUTO]'   => $this->_selectValue($data, 'color', 'auto'),
                             );
                break;

            default:
                /* Do Nothing */
        }

        /* If there's no need for Private information ... */
        return false;
    }

    /**
     *  Function Checks the Array and Returns an HTML Value based on what it sees
     */
    private function _checkboxValue($data, $item) {
        $enabled = YNBool(BoolYN($data[$item]));

        if ( $enabled ) { return ' checked'; }
        return '';
    }

    private function _selectValue($data, $item, $val) {
        $value = NoNull($data[$item]);

        if ( strtolower($value) == strtolower($val) ) { return ' selected'; }
        return '';
    }

    private function _getPopularPosts() {
        require_once( LIB_DIR . '/posts.php' );
        $post = new Posts( $this->settings, $this->strings );
        $html = $post->getPopularPosts();
        unset($post);

        return $html;
    }

    private function _getAuthoringTools($data) {
        $can_write = false;
        if ( NoNull($this->settings['_access_level']) == 'write' ) { $can_write = true; }

        if ( nullInt($this->settings['_account_id']) > 0 ) {
            $SiteUrl = NoNull($data['protocol'] . '://' . $data['HomeURL'] . '/themes/' . $data['location']);
            $ApiUrl = getApiUrl();
            $CdnUrl = getCdnUrl();

            // Compile the Personas
            $personas = '';
            if ( is_array($data['personas']) ) {
                $personas = json_encode($data['personas']);
            } else {
                $pInfo = $this->_getChannelInteractPersonas($data['channel_guid']);
                $personas = json_encode($pInfo);
            }

            // Prep the Replacement Array
            $ReplStr = array( '[CSS_VER]'      => CSS_VER,
                              '[GENERATOR]'    => GENERATOR . " (" . APP_VER . ")",
                              '[APP_NAME]'     => APP_NAME,
                              '[APP_VER]'      => APP_VER,

                              '[IMG_DIR]'      => $SiteUrl . '/img',
                              '[JS_DIR]'       => $SiteUrl . '/js',
                              '[HOMEURL]'      => $SiteUrl,
                              '[API_URL]'      => NoNull($data['api_url'], $ApiUrl),
                              '[CDN_URL]'      => NoNull($data['cdn_url'], $CdnUrl),

                              '[SITE_URL]'     => $data['HomeURL'],
                              '[CHANNEL_GUID]' => NoNull($data['channel_guid']),
                              '[CLIENT_GUID]'  => NoNull($data['client_guid']),
                              '[PERSONAS]'     => NoNull($personas, 'false'),
                              '[CAN_EDIT]'     => (($data['can_edit']) ? '' : ' hidden'),
                             );
            if ( $can_write ) {
                return readResource(FLATS_DIR . '/templates/authoring.html', $ReplStr);
            } else {
                return readResource(FLATS_DIR . '/templates/interact.html', $ReplStr);
            }
        }

        // If We're Here, We're Not Logged In
        return '';
    }

    /**
     *  Function Returns Some form of Contact question used for the Validation
     */
    private function _getContactQuestion() {
        $opts = array( 'Write the number forty-two',
                       '90 + 90 + 90 - 228',
                       '12 * 4 - 6',
                       '64 + 128 - 150',
                       '21 * 2 / 1',
                       '22 + 22 - 22 + 20',
                      );
        $idx = array_rand($opts);

        return NoNull($opts[$idx], "90 + 152 - 200");
    }

    private function _getChannelInteractPersonas( $ChannelGUID ) {
        if ( nullInt($this->settings['_account_id']) <= 0 ) { return false; }
        if ( NoNull($ChannelGUID) == '' ) { return false; }

        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[CHANNEL_GUID]' => nullInt($ChannelID),
                         );
        $sqlStr = readResource(SQL_DIR . '/system/getChannelInteractPersonas.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $CdnUrl = getCdnUrl();
            $data = array();

            foreach ( $rslt as $Row ) {
                $data[] = array( 'name'         => NoNull($Row['name']),
                                 'guid'         => NoNull($Row['guid']),
                                 'avatar_img'   => $CdnUrl . '/avatars/' . NoNull($Row['avatar_img'], 'default.png'),
                                 'channel_guid' => NoNull($Row['channel_guid']),
                                 'channel_name' => NoNull($Row['channel_name']),
                                );
            }

            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There are None
        return false;
    }

    private function _getNonceValue() {
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        switch ( $PgRoot ) {
            case 'contact':
                return md5(NoNull($this->settings['HomeURL']) . NoNull($this->settings['_address']));
                break;

            default:
                /* Do Nothing */
        }
        return '';
    }

    /**
     *  Construct a <select> list for timezones
     */
    private function _getTimezoneList() {
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        $valids = array( 'account', 'settings' );

        /* If we're in a correct location, build a <select> list */
        if ( in_array($PgRoot, $valids) ) {
            $zones = getTimeZoneList();
            $html = '';

            if ( is_array($zones) ) {
                foreach ( $zones as $Key=>$Value ) {
                    $selected = '';
                    if ( strtolower($Value) == strtolower($this->settings['_timezone']) ) { $selected = ' selected'; }
                    $html .= tabSpace(10) . "<option value=\"$Value\"$selected>$Key</option>\n";
                }
            }

            return NoNull($html);
        }

        /* If we're here, return an empty string */
        return '';
    }

    private function _getErrorMessage() {
        $key = NoNull($this->settings['err']);
        if ( $key != '' ) {
            switch ( strtolower($key) ) {
                case 'bad_pass':
                    return "Error: The password you provided is not particularly good.";
                    break;

                case 'bad_guid':
                    return "Error: An important identifier was not provided.<br>This sort of thing shouldn't happen.";
                    break;

                case 'bad_name':
                    return "Error: Please provide at least one name. It does not need to be your real name.";
                    break;
            }
        }
        return '';
    }

    private function _getSettingsPanel( $data ) {
        $can_write = false;
        if ( NoNull($this->settings['_access_level']) == 'write' ) { $can_write = true; }
        if ( $can_write && $data['can_edit'] ) {
            $ReplStr = array( '[SITE_DESCR]' => NoNull($data['description']),
                              '[SITE_KEYS]'  => NoNull($data['keywords']),
                              '[SITE_NAME]'  => NoNull($data['name']),

                              '[SITE_COLOR]'       => NoNull($data['color']),
                              '[SITE_THEME]'       => NoNull($data['location']),
                              '[SITE_FONT_FAMILY]' => NoNull($data['font-family']),
                              '[SITE_FONT_SIZE]'   => NoNull($data['font-size']),

                              '[SHOW_GEO_N]'  => (($data['show_geo']) ? '' : ' btn-active'),
                              '[SHOW_GEO_Y]'  => (($data['show_geo']) ? ' btn-active' : ''),
                              '[SHOW_NOTE_N]' => (($data['show_note']) ? '' : ' btn-active'),
                              '[SHOW_NOTE_Y]' => (($data['show_note']) ? ' btn-active' : ''),
                              '[SHOW_BLOG_N]' => (($data['show_article']) ? '' : ' btn-active'),
                              '[SHOW_BLOG_Y]' => (($data['show_article']) ? ' btn-active' : ''),
                              '[SHOW_BKMK_N]' => (($data['show_bookmark']) ? '' : ' btn-active'),
                              '[SHOW_BKMK_Y]' => (($data['show_bookmark']) ? ' btn-active' : ''),
                              '[SHOW_QUOT_N]' => (($data['show_quotation']) ? '' : ' btn-active'),
                              '[SHOW_QUOT_Y]' => (($data['show_quotation']) ? ' btn-active' : ''),
                             );
            foreach ( $this->strings as $Key=>$Value ) {
                $ReplStr["[$Key]"] = $Value;
            }
            return readResource(FLATS_DIR . '/templates/settings.html', $ReplStr);
        }

        // If We're Here, We're Not Logged In
        return '';
    }

    private function _getPreferencesPanel( $data ) {
        $can_write = false;
        if ( NoNull($this->settings['_access_level']) == 'write' ) { $can_write = true; }
        if ( $can_write && nullInt($this->settings['_account_id']) > 0 ) {
            // Build the Timezone Panel
            $tzlist = getTimeZones();
            $tzhtml = '';

            if ( is_array($tzlist) ) {
                foreach ( $tzlist as $Row ) {
                    if ( $tzhtml != '' ) { $tzhtml .= "\r\n"; }
                    $selected = (NoNull($Row['name']) == NoNull($this->settings['_timezone'])) ? ' selected' : '';
                    $tzhtml .= tabSpace(4) . '<option value="' . NoNull($Row['name']) . '"' . $selected . '>' . NoNull($Row['description']) . '</option>';
                }
            }

            // Build Replacement Array and Preferences Panel
            $ReplStr = array( '[PREF_NAME]'  => NoNull($this->settings['_display_name']),
                              '[PREF_LANG]'  => NoNull($this->settings['_language_code']),
                              '[PREF_MAIL]'  => NoNull($this->settings['_email']),
                              '[PREF_TIME]'  => NoNull($this->settings['_timezone']),
                              '[ACCT_TYPE]'  => NoNull($this->settings['_account_type']),
                              '[AVATAR_IMG]' => NoNull($this->settings['_avatar_file']),

                              '[STORE_LIMIT]' => nullInt($this->settings['_storage_total']),
                              '[STORE_USED]'  => nullInt($this->settings['_storage_used']),
                              '[ZONE_LIST]'   => $tzhtml,
                             );
            foreach ( $this->strings as $Key=>$Value ) {
                $ReplStr["[$Key]"] = $Value;
            }
            return readResource(FLATS_DIR . '/templates/preferences.html', $ReplStr);
        }

        // If We're Here, We're Not Logged In
        return '';
    }

    /**
     *  Function Collects the Necessary Page Contents
     */
    private function _getContentPage( $data ) {
        $ResDIR = THEME_DIR . "/" . NoNull($data['location'], getRandomString(6));
        if ( checkDIRExists($ResDIR) === false ) { $data['location'] = 'default'; }
        $valids = array('', 'forgot', 'register', 'rights', 'terms', 'tos');

        $ResDIR = THEME_DIR . "/" . $data['location'] . "/resources/";
        $rVal = 'content-' . NoNull($this->settings['PgRoot'], 'main') . '.html';
        if ( file_exists($ResDIR . $rVal) === false ) { $rVal = 'content-main.html'; }

        if ( $rVal == 'content-404.html' ) { $this->settings['status'] = 404; }
        if ( $rVal == 'content-403.html' ) { $this->settings['status'] = 403; }

        // Return the Necessary Page
        return $ResDIR . $rVal;
    }

    /**
     *  Function returns resource links for Custom CSS files if any have been
     *      defined for the current site.
     */
    private function _getCustomCSS( $data ) {
        if ( is_array($data) === false ) { return ''; }
        $SiteUrl = NoNull($data['protocol'] . '://' . $data['HomeURL'] . '/themes/' . $data['location']);
        $rVal = '';

        if ( NoNull($data['font-family'], 'auto') != 'auto' ) {
            $rVal .= tabSpace(2) . '<link rel="stylesheet" type="text/css" href="' . $SiteUrl . '/css/font-' . NoNull($data['font-family'], 'auto') . '.css?ver=' . CSS_VER . '">'  . "\r\n";
        }

        // Return the Additional CSS If Any Exists
        return $rVal;
    }

    /**
     *  Function Returns the Page-Specific CSS File Required
     */
    private function _getContentCSS( $data ) {
        $ResDIR = THEME_DIR . "/" . $data['location'] . "/resources/";
        $rVal = 'css-default.html';

        if ( $this->settings['_logged_in'] == 'Y' ) {
            $rVal = 'css-' . NoNull($this->settings['PgRoot'], 'main') . '.html';
            if ( file_exists($ResDIR . $rVal) === false ) { $rVal = 'css-default.html'; }
        }

        // Return the Required JavaScript File
        return $ResDIR . $rVal;
    }

    /**
     *  Function Returns the Required JavaScript File
     */
    private function _getContentJS( $data, $isHeader = false ) {
        $ResDIR = THEME_DIR . "/" . $data['location'] . "/resources/";
        $ResFile = ( $isHeader ) ? 'js-header.html' : 'js-footer.html';

        if ( file_exists($ResDIR . $ResFile) ) { return $ResDIR . $ResFile; }

        // Return an Empty String if Not found
        return '';
    }

    /**
     *  Function Returns the Page Title
     */
    private function _getPageTitle( $data, $isMeta = false ) {
        $lblDefault = '';
        $lblName = 'page' . ucfirst(NoNull($this->settings['PgRoot'], $lblDefault));

        if ( $isMeta ) {
            $rslt = NoNull($this->strings[$lblName], NoNull($data['page_title'], $data['name']));
            return htmlspecialchars(strip_tags($rslt), ENT_QUOTES, 'UTF-8');
        } else {
            return NoNull($this->strings[$lblName], NoNull($data['page_title'], $data['name']));
        }
    }

    /**
     *  Function Determines the Current Page URL
     */
    private function _getPageURL( $data ) {
        $rVal = $data['protocol'] . '://' . $data['HomeURL'] . '/';

        if ( NoNull($this->settings['PgRoot']) != '' ) { $rVal .= NoNull($this->settings['PgRoot']) . '/'; }
        for ( $i = 1; $i <= 9; $i++ ) {
            if ( NoNull($this->settings['PgSub' . $i]) != '' ) {
                $rVal .= NoNull($this->settings['PgSub' . $i]) . '/';
            } else {
                return $rVal;
            }
        }

        // Return the Current URL
        return $rVal;
    }

    /**
     *  Function Constructs and Returns the Language String Replacement Array
     */
    private function _getReplStrArray() {
        $rVal = array( '[SITEURL]' => NoNull($this->settings['HomeURL']),
                       '[RUNTIME]' => $this->_getRunTime('html'),
                      );
        foreach ( $this->strings as $Key=>$Val ) {
            $rVal["[$Key]"] = NoNull($Val);
        }

        // Return the Array
        return $rVal;
    }

    /**
     *  Function Returns the Page Execution Time
     */
    private function _getRunTime() {
        $precision = 6;
        $GLOBALS['Perf']['app_f'] = getMicroTime();
        $App = round(( $GLOBALS['Perf']['app_f'] - $GLOBALS['Perf']['app_s'] ), $precision);
        $SQL = nullInt( $GLOBALS['Perf']['queries'] );

        $lblSecond = ( $App == 1 ) ? "Second" : "Seconds";
        $lblQuery  = ( $SQL == 1 ) ? "Query"  : "Queries";

        // Return the Run Time String
        return "    <!-- Page generated in roughly: $App $lblSecond $SQL SQL $lblQuery -->";
    }

    /** ********************************************************************** *
     *  RSS (Syndication) Functions
     ** ********************************************************************** */
    /**
     *  Function Determines if Request is an RSS (XML/JSON) Request, Processes the data accordingly, and returns a Boolean response
     */
    private function _isSyndicationRequest( $site ) {
        $valids = array( 'rss', 'feed', 'social', 'socials' );
        $types = array( 'rss', 'feed', 'social', 'socials', 'podcast', 'note', 'article', 'quotation', 'bookmark' );
        foreach ( $types as $type ) {
            $valids[] = "$type.json";
            $valids[] = "$type.xml";
        }
        if ( is_array($site['custom_feeds']) ) {
            foreach ( $site['custom_feeds'] as $feedUrl ) {
                $valids[] = $feedUrl;
            }
        }

        // Do Not Continue if the Site is Locked
        if ( $data['site_locked'] ) { return false; }

        // Determine the Request by the Requested URI
        $ReqURI = NoNull($this->settings['ReqURI']);
        if ( strpos($ReqURI, "?") ) { $ReqURI = substr($ReqURI, 0, strpos($ReqURI, "?")); }

        // Determine if there's a Custom Request
        if ( strpos($ReqURI, '.xml') || strpos($ReqURI, '.json') ) {
            $ReplStr = array( '/' => '-', '.xml' => '', '.json' => '' );
            $ReqTypes = explode('-', str_ireplace(array_keys($ReplStr), array_values($ReplStr), $ReqURI));

            foreach ( $ReqTypes as $req ) {
                $req = strtolower($req);
                if ( in_array(NoNull($req), $types) ) {
                    if ( array_key_exists('rss_filter_on', $this->settings) === false ) {
                        $this->settings['rss_filter_on'] = array();
                    }

                    // Set the Values
                    if ( in_array(NoNull($req), $valids) === false ) { $this->settings['rss_filter_on'][] = NoNull($req); }
                    if ( in_array($ReqURI, $valids) === false ) { $valids[] = NoNull(str_ireplace('/', '', $ReqURI)); }
                }
            }

            // Ditch the Filter If Zero Values Exist
            if ( count($this->settings['rss_filter_on']) <= 0 ) { unset($this->settings['rss_filter_on']); }
        }

        $fullPath = explode('/', $ReqURI);
        $lastSeg = NoNull($fullPath[(count($fullPath) - 1)]);
        $format = ( strpos($lastSeg, 'json') ) ? 'json' : 'xml';
        if ( in_array($lastSeg, $valids) ) {
            require_once( LIB_DIR . '/posts.php' );
            $post = new Posts( $this->settings, $this->strings );
            $feed = $post->getRSSFeed($site, $format);
            $this->settings['status'] = $post->getResponseCode();
            $this->settings['errors'] = $post->getResponseMeta();
            unset($post);

            switch ( $format ) {
                case 'json':
                    $this->settings['type'] = 'application/json';
                    break;

                default:
                    $this->settings['type'] = 'application/rss+xml';
            }

            // Return the Response Data
            formatResult($feed, $this->settings, $this->settings['type'], $this->settings['status'], $this->settings['errors']);
            exit();
        }

        // If We're Here, There is no Syndication Resource Request
        return false;
    }

    /**
     *  Function Determines the Current Page URL
     */
    private function _getCanonicalURL() {
        if ( NoNull($this->settings['PgRoot']) == '' ) { return ''; }

        $rVal = '/' . NoNull($this->settings['PgRoot']);
        for ( $i = 1; $i <= 9; $i++ ) {
            if ( NoNull($this->settings['PgSub' . $i]) != '' ) {
                $rVal .= '/' . NoNull($this->settings['PgSub' . $i]);
            } else {
                return $rVal;
            }
        }

        // Return the Canonical URL
        return $rVal;
    }
}
?>