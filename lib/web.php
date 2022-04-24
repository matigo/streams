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
    var $custom;
    var $posts;
    var $site;

    function __construct( $settings, $strings ) {
        $this->settings = $settings;
        $this->strings = $strings;
        $this->custom = false;
        $this->posts = false;

        $this->site = new Site($this->settings);

        /* Ensure the Asset Version.id Is Set */
        if ( defined('CSS_VER') === false ) {
            $ver = filemtime(CONF_DIR . '/versions.php');
            if ( nullInt($ver) <= 0 ) { $ver = nullInt(APP_VER); }
            define('CSS_VER', $ver);
        }
    }

    /* ************************************************************************************** *
     *  Function determines what needs to be done and returns the appropriate HTML Document.
     * ************************************************************************************** */
    public function getResponseData() {
        $ReplStr = $this->_getReplStrArray();
        $this->settings['status'] = 200;

        $html = readResource(FLATS_DIR . '/templates/500.html', $ReplStr);
        $ThemeFile = THEME_DIR . '/error.html';
        $LoggedIn = false;

        // Collect the Site Data - Redirect if Invalid
        $data = $this->site->getSiteData();
        $this->settings['_theme'] = NoNull($data['location'], 'default');
        if ( is_array($data) ) {
            $RedirectURL = NoNull($data['protocol'] . '://' . $data['HomeURL']);
            $PgRoot = strtolower(NoNull($this->settings['PgRoot']));

            // Is this a favicon request?
            if ( strpos(strtolower(NoNull($this->settings['ReqURI'])), 'favicon.png') !== false ) { $this->_handleFaviconReq($data); }

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
            if ( array_key_exists('CONTENT_TYPE', $_SERVER) === false ) { $_SERVER['CONTENT_TYPE'] = ''; }
            $CType = NoNull($_SERVER['CONTENT_TYPE'], 'text/html');
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

                    case 'syndication':
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
                $checks = array('write', 'export', 'account', 'syndication', 'settings', 'messages');
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
        $isCacheable = $this->_isCacheable();
        $LockPrefix = '';
        if ( $data['site_locked'] ) { $LockPrefix = getRandomString(18); }

        /* If Caching Is Enabled, Check If We Have a Valid Cached Version */
        $cache_file = md5($data['site_version'] . '-' . NoNull($LockPrefix, APP_VER . CSS_VER) . '-' .
                          nullInt($this->settings['_token_id']) . '.' . nullInt($this->settings['_persona_id']) . '-' .
                          NoNull($this->settings['ReqURI'], '/') . '-' . nullInt($this->settings['page']));
        if ( $isCacheable && defined('ENABLE_CACHING') ) {
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
        $ThemeLocation = THEME_DIR . '/' . $this->settings['_theme'];
        if ( checkDIRExists($ThemeLocation) === false ) {
            $this->settings['_theme'] = 'default';
            $data['location'] = 'default';
        }

        if ( $data['site_locked'] ) {
            $HomeUrl = NoNull($this->settings['HomeURL']);
            $ReplStr =  array( '[SHARED_FONT]'  => $HomeUrl . '/shared/fonts',
                               '[SHARED_CSS]'   => $HomeUrl . '/shared/css',
                               '[SHARED_IMG]'   => $HomeUrl . '/shared/img',
                               '[SHARED_JS]'    => $HomeUrl . '/shared/js',

                               '[FONT_DIR]'     => $HomeUrl . '/templates/fonts',
                               '[CSS_DIR]'      => $HomeUrl . '/templates/css',
                               '[IMG_DIR]'      => $HomeUrl . '/templates/img',
                               '[JS_DIR]'       => $HomeUrl . '/templates/js',
                               '[HOMEURL]'      => $HomeUrl,

                               '[CSS_VER]'      => CSS_VER,
                               '[GENERATOR]'    => GENERATOR . " (" . APP_VER . ")",
                               '[APP_NAME]'     => APP_NAME,
                               '[APP_VER]'      => APP_VER,
                               '[LANG_CD]'      => validateLanguage(NoNull($this->settings['_language_code'], $this->settings['DispLang'])),
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

            /* Do we have any page-specific Header details? */
            $ReplStr['[PAGEDESCR]'] = NoNull($GLOBALS['post_summary'], $ReplStr['[SITEDESCR]']);

            /* Generate the Output */
            $ReplStr['[CONTENT]'] = readResource($ReqFile, $ReplStr);
            $ReplStr['[NAVMENU]'] = $this->_getSiteNav($data);
            $html = readResource( THEME_DIR . "/" . $data['location'] . "/base.html", $ReplStr );

            // Save the File to Cache if Required and Populate the Base Sections
            if ( defined('ENABLE_CACHING') ) {
                if ( nullInt(ENABLE_CACHING) == 1 ) {
                    if ( $isCacheable ) {
                        saveCache($data['site_id'], $cache_file, $html);
                    }

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
     *  Function Handles a Favicon PNG Request when one doesn't exist
     */
    private function _handleFaviconReq( $site ) {
        $src = BASE_DIR . '/apple-touch-icon.png';
        if ( array_key_exists('personas', $site) && is_array($site['personas']) ) {
            foreach ( $site['personas'] as $persona ) {
                $src = BASE_DIR . str_replace($this->settings['HomeURL'], '', NoNull($persona['avatar_img']));
                break;
            }
        }

        $cacheDIR = TMP_DIR . '/' . intToAlpha($site['site_id']);
        checkDIRExists($cacheDIR);

        $cacheFile = $cacheDIR . '/' . md5($src) . '.png';
        if ( file_exists($cacheFile) === false ) {
            $srcExt = strtolower(pathinfo($src, PATHINFO_EXTENSION));
            switch ($srcExt) {
                case 'jpeg':
                case 'jpg':
                    $image = imagecreatefromjpeg($src);
                    break;

                case 'gif':
                    $image = imagecreatefromgif($src);
                    break;

                case 'png':
                    $image = imagecreatefrompng($src);
                    break;
            }

            /* Create the Appropriate PNG File */
            list($width_orig, $height_orig) = getimagesize($src);
            $image_p = imagecreatetruecolor(150, 150);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, 150, 150, $width_orig, $height_orig);
            imagepng($image_p, $cacheFile, 9);
        }

        /* Output the File */
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1');
        $szOrigin = NoNull($_SERVER['HTTP_ORIGIN'], '*');

        header($protocol . ' 200 ' . getHTTPCode(200) );
        header("Content-Type: image/png");
        header("Access-Control-Allow-Origin: $szOrigin");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Credentials: true");
        header("P3P: CP=\"ALL IND DSP COR ADM CONo CUR CUSo IVAo IVDo PSA PSD TAI TELo OUR SAMo CNT COM INT NAV ONL PHY PRE PUR UNI\"");
        header("X-Perf-Stats: " . getRunTime('header'));
        header("X-Content-Length: " . filesize($cacheFile));

        // Record the Usage Statistic
        recordUsageStat( $this->settings, 200, '' );

        // Close the Persistent SQL Connection If Needs Be
        closePersistentSQLConn();

        readfile($cacheFile);
        exit();
    }

    /**
     *  Function determines whether the cache should be read or not to load the site/page/resource
     */
    private function _isCacheable() {
        $Excludes = array( 'write', 'settings', 'syndication', 'account' );
        $cacheFile = substr('00000000' . $this->settings['site_id'], -8) . '-' . NoNull(APP_VER);
        $data = getCacheObject($cacheFile);

        /* Get the Theme-specific List of Non-Cacheable Pages (if applicable) */
        if ( is_array($data) === false || $data === false ) {
            if ( is_array($this->site->cache) ) {
                foreach ( $this->site->cache as $site ) {
                    /* Is the Site marked as uncacheable? */
                    if ( $site['cacheable'] === false ) { return false; }

                    /* Build the Excludes */
                    $Location = THEME_DIR . '/' . NoNull($site['location']);
                    if ( file_exists($Location) ) {
                        foreach ( glob($Location . "/resources/content-*.html") as $filename) {
                            $key = strtolower(str_replace(array("$Location/resources/", 'content-', '.html'), '', $filename));
                            if ( in_array($key, $Excludes) === false ) {
                                $Excludes[] = $key;
                            }
                        }
                    }
                }
            }
            setCacheObject($cacheFile, $Excludes);

        } else {
            $Excludes = $data;
        }

        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        if ( in_array($PgRoot, $Excludes) ) {
            return false;
        } else {
            return true;
        }
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
                    if ( $this->posts === false ) {
                        require_once( LIB_DIR . '/posts.php' );
                        $this->posts = new Posts( $this->settings, $this->strings );
                    }
                    $data = $this->posts->getPageJSON( $site );
                    $meta = $this->posts->getResponseMeta();
                    $code = $this->posts->getResponseCode();
            }
        }

        // If We Have an Array of Data, Return It
        if ( is_array($data) ) { formatResult($data, $this->settings, 'application/json', $code, $meta); }
    }

    /**
     *  Function Returns the Requisite Content That People Would Expect to See on a
     *      page based on its purpose.
     */
    private function _getPageContent($data) {
        $ThemeLocation = THEME_DIR . '/' . $this->settings['_theme'];

        // Is there a custom.php file in the theme that will provide the requisite data?
        $ResDIR = $ThemeLocation . "/resources";
        if ( file_exists("$ThemeLocation/custom.php") ) {
            if ( $this->custom === false ) {
                require_once("$ThemeLocation/custom.php");
                $ClassName = ucfirst($this->settings['_theme']);
                $this->custom = new $ClassName( $this->settings, $this->strings );
            }
            $html = $this->custom->getPageHTML($data);
            $this->settings['errors'] = $this->custom->getResponseMeta();
            $this->settings['status'] = $this->custom->getResponseCode();

            // Return the Completed HTML
            return $html;

        } else {
            if ( $this->posts === false ) {
                require_once( LIB_DIR . '/posts.php' );
                $this->posts = new Posts( $this->settings, $this->strings );
            }
            $html = $this->posts->getPageHTML($data);
            $this->settings['status'] = $this->posts->getResponseCode();
            $this->settings['errors'] = $this->posts->getResponseMeta();

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
        $Excludes = array( 'write', 'settings', 'syndication', 'account' );
        $CanonURL = $this->_getCanonicalURL();
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        $tObj = strtolower(str_replace('/', '', $CanonURL));

        $posts = 0;
        $pages = 0;

        // Is there a custom.php file in the theme that will provide the requisite data?
        $ThemeLocation = THEME_DIR . '/' . $this->settings['_theme'];
        if ( file_exists("$ThemeLocation/custom.php") ) {
            if ( $this->custom === false ) {
                require_once("$ThemeLocation/custom.php");
                $ClassName = ucfirst($this->settings['_theme']);
                $this->custom = new $ClassName( $this->settings, $this->strings );
            }
            if ( method_exists($this->custom, 'getPagination') ) {
                $this->settings['errors'] = $this->custom->getResponseMeta();
                $this->settings['status'] = $this->custom->getResponseCode();
                return $this->custom->getPagination($data);
            }
        }

        /* If we're here, let's keep going */
        if ( in_array($PgRoot, $Excludes) === false ) {
            $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                              '[SITE_TOKEN]'   => sqlScrub(NoNull($this->settings['site_token'])),
                              '[SITE_GUID]'    => sqlScrub($data['site_guid']),
                              '[CANON_URL]'    => sqlScrub($CanonURL),
                              '[PGROOT]'       => sqlScrub($PgRoot),
                              '[OBJECT]'       => sqlScrub($tObj),
                              '[PGSUB1]'       => sqlScrub($this->settings['PgSub1']),
                              '[SITE_VERSION]' => nullInt($data['updated_unix']),
                              '[APP_VERSION]'  => sqlScrub(APP_VER),
                             );
            $cacheFile = substr('00000000' . $this->settings['site_id'], -8) . '-' . sha1(serialize($ReplStr));
            $rslt = getCacheObject($cacheFile);
            if ( is_array($rslt) === false ) {
                $sqlStr = prepSQLQuery("CALL GetSitePagination([ACCOUNT_ID], '[SITE_GUID]', '[SITE_TOKEN]', '[CANON_URL]', '[PGROOT]', '[OBJECT]', '[PGSUB1]');", $ReplStr);
                $rslt = doSQLQuery($sqlStr);
                setCacheObject($cacheFile, $rslt);
            }

            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $posts = nullInt($Row['post_count']);
                    $pages = nullInt($Row['page_count']);
                }
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
                $this->strings["$Key"] = NoNull($Value);
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
        $ThemeLocation = THEME_DIR . '/' . $this->settings['_theme'];
        $html = '';

        // Is there a custom.php file in the theme that will provide the requisite data?
        $ResDIR = $ThemeLocation . "/resources";
        if ( file_exists("$ThemeLocation/custom.php") ) {
            if ( $this->custom === false ) {
                require_once("$ThemeLocation/custom.php");
                $ClassName = ucfirst($this->settings['_theme']);
                $this->custom = new $ClassName( $this->settings, $this->strings );
            }
            if ( method_exists($this->custom, 'getSiteNav') ) {
                $this->settings['errors'] = $this->custom->getResponseMeta();
                $this->settings['status'] = $this->custom->getResponseCode();
                $html = $this->custom->getSiteNav($data);
            }

        } else {
            $ReplStr = array( '[SITE_ID]'      => nullInt($data['site_id']),
                              '[SITE_VERSION]' => nullInt($data['updated_unix']),
                              '[APP_VERSION]'  => sqlScrub(APP_VER),
                             );
            $cacheFile = substr('00000000' . $this->settings['site_id'], -8) . '-' . sha1(serialize($ReplStr));
            $rslt = getCacheObject($cacheFile);
            if ( is_array($rslt) === false ) {
                $sqlStr = prepSQLQuery("CALL GetSiteNav([SITE_ID]);", $ReplStr);
                $rslt = doSQLQuery($sqlStr);
                setCacheObject($cacheFile, $rslt);
            }
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
        $rVal = array( '[SHARED_FONT]'  => $HomeUrl . '/shared/fonts',
                       '[SHARED_CSS]'   => $HomeUrl . '/shared/css',
                       '[SHARED_IMG]'   => $HomeUrl . '/shared/img',
                       '[SHARED_JS]'    => $HomeUrl . '/shared/js',

                       '[FONT_DIR]'     => $SiteUrl . '/fonts',
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
                       '[LANG_CD]'      => validateLanguage(NoNull($this->settings['_language_code'], $this->settings['DispLang'])),
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
                       '[CC-LICENSE]'   => $this->_getCCLicense(NoNull($data['license'], 'CC BY-NC-ND')),

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
     *  Function returns a constructed Creative Commons license statement for the footer of a page
     */
    private function _getCCLicense( $license ) {
        $idx = array( '0'     => array( 'icon' => 'zero',  'text' => 'No Rights Reserved' ),
                      'by'    => array( 'icon' => 'by',    'text' => 'Attribution' ),
                      'nc'    => array( 'icon' => 'nc',    'text' => 'NonCommercial' ),
                      'nd'    => array( 'icon' => 'nd',    'text' => 'NoDerivatives' ),
                      'pd'    => array( 'icon' => 'pd',    'text' => 'PublicDomain' ),
                      'sa'    => array( 'icon' => 'sa',    'text' => 'ShareAlike' ),
                      'remix' => array( 'icon' => 'remix', 'text' => 'Remix' ),
                      'share' => array( 'icon' => 'share', 'text' => 'Share' ),
                     );
        $valids = array('CC0', 'CC BY', 'CC BY-SA', 'CC BY-ND', 'CC BY-NC', 'CC BY-NC-SA', 'CC BY-NC-ND');
        if ( in_array(strtoupper($license), $valids) === false ) {
            $license = 'CC BY-NC-ND';
        }

        $type = strtolower(NoNull(str_replace(array('CC', '4.0'), '', $license)));
        $icon = '<i class="fab fa-creative-commons"></i> ';
        $desc = '';

        $els = explode('-', $type);
        foreach ( $els as $el ) {
            $icon .= '<i class="fab fa-creative-commons-' . $idx[strtolower($el)]['icon'] . '"></i> ';
            if ( $desc != '' ) { $desc .= '-'; }
            $desc .= NoNull($idx[strtolower($el)]['text']);
        }

        // Return the License String
        return $icon . 'This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/' . $type . '/4.0/">Creative Commons ' . NoNull($desc) . ' 4.0 International License</a>.';
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
                                      '[LANG_CODE]'    => validateLanguage(NoNull($this->settings['_language_code'], $this->settings['DispLang'])),
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

            case 'syndication':
                $rslt = $this->_getSyndicationMeta( $data );
                if ( is_array($rslt) ) {
                    $podCategories = getPodcastCategories( $this->strings, true );
                    $cat1 = NoNull($rslt['category1sub'], $rslt['category1']);
                    $cat2 = NoNull($rslt['category2sub'], $rslt['category2']);
                    $cat3 = NoNull($rslt['category3sub'], $rslt['category3']);

                    return array( '[COVER_IMG]'  => NoNull($rslt['cover_img']),
                                  '[LICENSE]'    => NoNull($rslt['license'], 'CC BY-NC-SA'),
                                  '[LICENSE_1]'  => $this->_selectValue($rslt, 'license', 'CC CC0'),
                                  '[LICENSE_2]'  => $this->_selectValue($rslt, 'license', 'CC BY'),
                                  '[LICENSE_3]'  => $this->_selectValue($rslt, 'license', 'CC BY-SA'),
                                  '[LICENSE_4]'  => $this->_selectValue($rslt, 'license', 'CC BY-NC'),
                                  '[LICENSE_5]'  => $this->_selectValue($rslt, 'license', 'CC BY-NC-SA'),
                                  '[LICENSE_6]'  => $this->_selectValue($rslt, 'license', 'CC BY-ND'),
                                  '[LICENSE_7]'  => $this->_selectValue($rslt, 'license', 'CC BY-NC-ND'),

                                  '[RSS_ITEMS]'  => NoNull($rslt['rss-items'], 15),
                                  '[RSSCOUNT001]' => $this->_selectValue($rslt, 'rss-items', '1'),
                                  '[RSSCOUNT015]' => $this->_selectValue($rslt, 'rss-items', '15'),
                                  '[RSSCOUNT025]' => $this->_selectValue($rslt, 'rss-items', '25'),
                                  '[RSSCOUNT050]' => $this->_selectValue($rslt, 'rss-items', '50'),
                                  '[RSSCOUNT100]' => $this->_selectValue($rslt, 'rss-items', '100'),
                                  '[RSSCOUNT250]' => $this->_selectValue($rslt, 'rss-items', '250'),
                                  '[RSSCOUNTALL]' => $this->_selectValue($rslt, 'rss-items', '99999'),

                                  '[SUMMARY]'    => NoNull($rslt['summary']),
                                  '[AUTHOR]'     => NoNull($rslt['author']),
                                  '[EXPLICIT_C]' => $this->_selectValue($rslt, 'explicit', 'c'),
                                  '[EXPLICIT_N]' => $this->_selectValue($rslt, 'explicit', 'n'),
                                  '[EXPLICIT_Y]' => $this->_selectValue($rslt, 'explicit', 'y'),

                                  '[CAT_LIST_1]' => (( $cat1 != '' ) ? str_replace( '"' . $cat1 . '"', '"' . $cat1 . '" selected', $podCategories) : $podCategories),
                                  '[CAT_LIST_2]' => (( $cat2 != '' ) ? str_replace( '"' . $cat2 . '"', '"' . $cat2 . '" selected', $podCategories) : $podCategories),
                                  '[CAT_LIST_3]' => (( $cat3 != '' ) ? str_replace( '"' . $cat3 . '"', '"' . $cat3 . '" selected', $podCategories) : $podCategories),

                                  '[CATEGORY_1]' => NoNull($rslt['category1']),
                                  '[CAT_SUB_1]'  => NoNull($rslt['category1sub']),
                                  '[CATEGORY_2]' => NoNull($rslt['category2']),
                                  '[CAT_SUB_2]'  => NoNull($rslt['category2sub']),
                                  '[CATEGORY_3]' => NoNull($rslt['category3']),
                                  '[CAT_SUB_3]'  => NoNull($rslt['category3sub']),
                                 );
                }
                break;

            default:
                /* Do Nothing */
        }

        /* If there's no need for Private information ... */
        return false;
    }

    private function _getSyndicationMeta( $data ) {
        if ( $this->settings['_logged_in'] !== true ) { return false; }

        $ReplStr = array( '[SITE_ID]' => $this->settings['site_id'] );
        $sqlStr = readResource(SQL_DIR . '/site/getSyndicationMeta.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $cdnUrl = getCdnUrl();
            $cover = $this->settings['HomeURL'] . '/favicon.png';
            $author = '';
            if ( is_array($data['personas']) ) {
                foreach ( $data['personas'] as $pa ) {
                    if ( NoNull($pa['name']) != '' ) {
                        if ( $author != '' ) { $author .= ', '; }
                        $author .= NoNull($pa['name']);
                    }
                }
            }

            foreach ( $rslt as $Row ) {
                if ( NoNull($Row['cover']) != '' ) {
                    $coverPath = CDN_PATH . NoNull($Row['cover']);
                    if ( file_exists($coverPath) ) {
                        $cover = $cdnUrl . NoNull($Row['cover']);
                    }
                }

                return array( 'site_id'      => nullInt($Row['site_id']),
                              'cover_img'    => NoNull($cover),
                              'explicit'     => NoNull($Row['explicit'], 'c'),
                              'summary'      => NoNull($Row['summary']),
                              'license'      => NoNull($Row['license'], 'CC BY-NC-SA'),
                              'author'       => NoNull($Row['author'], $author),
                              'category1'    => NoNull($Row['category1']),
                              'category1sub' => NoNull($Row['category1sub']),
                              'category2'    => NoNull($Row['category2']),
                              'category2sub' => NoNull($Row['category2sub']),
                              'category3'    => NoNull($Row['category3']),
                              'category3sub' => NoNull($Row['category3sub']),
                              'rss-items'    => nullInt($Row['rss-items'], 15),
                             );
            }
        }

        // If we're here, there's nothing
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
        // Is there a custom.php file in the theme that will provide the requisite data?
        $ThemeLocation = THEME_DIR . '/' . $this->settings['_theme'];
        if ( file_exists("$ThemeLocation/custom.php") ) {
            if ( $this->custom === false ) {
                require_once("$ThemeLocation/custom.php");
                $ClassName = ucfirst($this->settings['_theme']);
                $this->custom = new $ClassName( $this->settings, $this->strings );
            }
            if ( method_exists($this->custom, 'getPopularPosts') ) {
                $this->settings['errors'] = $this->custom->getResponseMeta();
                $this->settings['status'] = $this->custom->getResponseCode();
                return $this->custom->getPopularPosts($data);
            }
        }

        if ( $this->posts === false ) {
            require_once( LIB_DIR . '/posts.php' );
            $this->posts = new Posts( $this->settings, $this->strings );
        }
        $html = $this->posts->getPopularPosts();

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
            if ( $this->posts === false ) {
                require_once( LIB_DIR . '/posts.php' );
                $this->posts = new Posts( $this->settings, $this->strings );
            }
            $feed = $this->posts->getRSSFeed($site, $format);
            $this->settings['status'] = $this->posts->getResponseCode();
            $this->settings['errors'] = $this->posts->getResponseMeta();

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