<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for Cookie Handling and
 *      Restoration of Primary Settings in Midori
 */
require_once( LIB_DIR . '/functions.php');

class Cookies {
    var $cookies;

    function __construct() {
        $this->cookies = $this->_getCookies();

        // Perform the PHP Version Check
        $this->_validatePHPVersion();
    }

    /**
     * Function Confirms the Server is Running an Acceptable Version of PHP
     */
    function _validatePHPVersion() {
        if ( defined('MIN_PHPVERSION') === false ) { define('MIN_PHPVERSION', 70000); }
        if ( YNBool(ENFORCE_PHPVERSION) && PHP_VERSION_ID < MIN_PHPVERSION) {
            $rVal = "This Version of PHP (" . phpversion() . ") is Not Supported.";

            header('HTTP/1.1 531 Invalid Server Configuration');
            header("Content-Type: text/html");
            header("Content-Length: " . strlen($rVal));
            header("X-SHA1-Hash: " . sha1( $rVal ));
            exit( $rVal );
        }
    }

    /**
     * Function Collects the Cookies, GET, and POST information and returns an array
     *      containing all of the values the Application will require.
     */
    function _getCookies() {
        $rVal = array();

        $JSON = json_decode(file_get_contents('php://input'), true);
        if ( is_array($JSON) ) {
            foreach( $JSON as $key=>$val ) {
                $cKey = $this->_CleanKey($key);
                $rVal[$cKey] = (is_array($val)) ? $val : $this->_CleanRequest($key, $val);
            }
        }

        if ( is_array($_POST) && count($_POST) > 0 ) {
            foreach( $_POST as $key=>$val ) {
                $cKey = $this->_CleanKey($key);
                $rVal[$cKey] = $this->_CleanRequest($key, $val);
            }
        }

        if ( is_array($_GET) && count($_GET) > 0 ) {
            foreach( $_GET as $key=>$val ) {
                $cKey = $this->_CleanKey($key);
                if ( is_array($val) ) {
                    if ( array_key_exists($cKey, $rVal) === false ) { $rVal[$cKey] = array(); }
                    foreach ( $val as $kk=>$vv ) {
                        $rVal[$cKey][] = NoNull($vv);
                    }

                } else {
                    if ( !array_key_exists($cKey, $rVal) ) { $rVal[$cKey] = $this->_CleanRequest($key, $val); }
                }
            }
        }

        foreach( $_COOKIE as $key=>$val ) {
            $cKey = $this->_CleanKey($key);
            if ( !array_key_exists($cKey, $rVal) ) { $rVal[$cKey] = $this->_CleanRequest($key, $val); }
        }

        $gah = getallheaders();
        if ( is_array($gah) ) {
            $opts = array( 'authorisation'  => 'token',
                           'authorization'  => 'token',
                          );
            foreach ( getallheaders() as $key=>$val ) {
                $cKey = $this->_CleanKey($key);
                if ( array_key_exists($cKey, $opts) ) {
                    $val = $this->_CleanRequest($key, $val);

                    /* If we have a valid string, set it */
                    if ( mb_strlen($val) > 0 ) { $rVal[ $opts[$cKey] ] = $val; }
                }
            }
        }

        /* Determine the Type */
        if ( array_key_exists('HTTP_ACCESS_CONTROL_REQUEST_METHOD', $_SERVER) === false ) { $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = ''; }
        $rVal['ReqType'] = strtoupper( NoNull($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'], NoNull($_SERVER['REQUEST_METHOD'], 'GET')) );
        if ( $rVal['ReqType'] == 'OPTIONS' ) { $rVal['ReqType'] = 'DELETE'; }

        /* Assemble the Appropriate URL Path (Overrides Existing Information) */
        $URLPath = $this->_readURL();
        foreach ( $URLPath as $Key=>$Val ) {
            $rVal[ $Key ] = $Val;
        }

        /* Add Any Missing Data from URL Query String (Does Not Override Existing Data) */
        $missedData = $this->checkForMissingData();
        foreach( $missedData as $key=>$val ) {
            $propKey = str_replace('-', '_', $key);
            if ( !array_key_exists($propKey, $rVal) ) {
                $rVal[ $propKey ] = $this->_CleanRequest($key, $val);
            }
        }

        /* Populate Missing or Blank Array Values with Defaults (Does Not Override Existing Data) */
        $defaults = $this->_getCookieDefaults();
        foreach($defaults as $key=>$val) {
            if ( !array_key_exists($key, $rVal) ) {
                $rVal[ $key ] = $val;
            }
        }

        /* Scrub the Page Pointers */
        foreach ( $rVal as $Key=>$Val ) {
            switch ( strtolower($Key) ) {
                case 'pgroot':
                case 'pgsub1':
                case 'pgsub2':
                case 'pgsub3':
                case 'pgsub4':
                case 'pgsub5':
                case 'pgsub6':
                    $rVal[ $Key ] = $this->_stripQueries( $Val );
                    break;
            }
        }

        /* Do we have a Token Key value? This is part of authentication validation, so override any key that might be in place */
        if ( mb_strlen(NoNull($rVal['token_key'])) >= 40 ) { $rVal['token'] = $rVal['token_key']; }

        /* Get the Appropriate Account Data */
        if ( NoNull($rVal['token']) != '' ) {
            require_once( LIB_DIR . '/auth.php' );
            $auth = new Auth( $rVal );
            $data = $auth->getTokenData($rVal['token']);
            unset($auth);

            if ( is_array($data) ) {
                foreach ( $data as $Key=>$Value ) { $rVal[ $Key ] = $Value; }
            }
        }

        /* Ensure the DispLang value is Logical, as an illogical value can break HTML */
        $rVal['DispLang'] = validateLanguage($rVal['DispLang']);

        /* Don't Keep an Empty Array Object with the Request URI */
        unset($rVal[substr($rVal['ReqURI'], 1)]);

        /* Save Some Cookies for Later Use */
        $this->_saveCookies($rVal);

        /* Return the Cookies */
        return $rVal;
    }

    /**
     * Function Reads the Request URI and Returns the Contents in an Array
     */
    private function checkForMissingData() {
        $rVal = array();
        $vals = explode( "&", substr( $_SERVER["REQUEST_URI"], strpos( $_SERVER["REQUEST_URI"], "?" ) + 1 ) );

        foreach ( $vals as $val ) {
            $keyval = explode( "=", $val );

            if ( is_array($keyval) ) {
                $rVal[ $keyval[0] ] = $keyval[1];
            }
        }

        /* Return an Array Containing the Missing Data */
        return $rVal;
    }

    /**
     * Function Returns the Default Cookie Values
     */
    private function _getCookieDefaults() {
        $SiteUrl = strtolower( NoNull($_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']) );
        $Locale = $this->_getDisplayLanguage();
        $LangCd = validateLanguage($Locale);

        /* Return the Array of Defaults */
        $Protocol = getServerProtocol();
        return array( 'DispLang'       => $LangCd,
                      'HomeURL'        => $Protocol . '://' . $SiteUrl,
                      'Route'          => 'web',
                      'site_id'        => $this->_getSiteID(),

                      '_address'       => getVisitorIPv4(),
                      '_account_id'    => 0,
                      '_persona_id'    => 0,
                      '_display_name'  => '',
                      '_logged_in'     => false,

                      '_account_type'  => 'account.anonymous',
                      '_language_code' => $LangCd,
                      '_site_id'       => $this->_getSiteID(),
                      '_is_admin'      => false,
                      '_is_debug'      => false,
                     );
    }

    /**
     *  Function Returns the Type of Request and the Route Required
     */
    private function _getRouting() {
        $paths = array( 'api'      => 'api',
                        'cdn'      => 'cdn',
                        'i'        => 'cdn',
                        'hooks'    => 'hooks',
                        'webhook'  => 'hooks',
                        'webhooks' => 'hooks',
                        'file'     => 'files',
                        'files'    => 'files'
                       );

        /* Determine the Routing based on the Subdomain */
        $ReqURL = strtolower( NoNull($_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']) );
        $parts = explode('.', $ReqURL);

        /* Return the Routing or an Empty String */
        if ( array_key_exists($parts[0], $paths) ) { return NoNull($paths[$parts[0]]); }
        return '';
    }

    /**
     *  Function Returns the Appropriate Display Language
     */
    private function _getDisplayLanguage( $AccountLang = '' ) {
        if ( array_key_exists('DispLang', $_COOKIE) === false ) { $_COOKIE['DispLang'] = ''; }
        if ( array_key_exists('DispLang', $_GET) === false ) { $_GET['DispLang'] = ''; }

        $langcd = NoNull($_GET['DispLang'], $_COOKIE['DispLang']);
        if ( defined('ENABLE_MULTILANG') === false ) { define('ENABLE_MULTILANG', 0); }
        if ( defined('DEFAULT_LANG') === false ) { define('DEFAULT_LANG', 'en'); }

        if ( $langcd == '' || NoNull($AccountLang) != '' ){ $langcd = NoNull($AccountLang, DEFAULT_LANG); }
        $rVal = DEFAULT_LANG;

        if ( YNBool(ENABLE_MULTILANG) ) {
            if ( array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER) === false ) { $_SERVER['HTTP_ACCEPT_LANGUAGE'] = ''; }
            $rVal = ( NoNull($_SERVER['HTTP_ACCEPT_LANGUAGE']) == '' ) ? NoNull($langcd, DEFAULT_LANG) : mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5);

        } else {
            $rVal = DEFAULT_LANG;
        }

        return strtolower($rVal);
    }

    /**
     * Function Returns the proper key for use in the settings array
     */
    private function _CleanKey( $Key ) {
        if ( defined('TOKEN_KEY') === false ) { define('TOKEN_KEY', 'token'); }
        $Key = strtolower(str_replace('-', '_', $Key));

        /* "token" is a protected word. Do not permit it unless that is the TOKEN_KEY value. */
        if ( TOKEN_KEY != 'token' && $Key == 'token' ) { $Key = getRandomString(6); }

        /* If we have a TOKEN_KEY, set the proper key value */
        if ( $Key == TOKEN_KEY ) { $Key = 'token'; }
        return NoNull($Key);
    }

    /**
     *  Function Cleans the Value, URL Decoding It and Returning the Results
     */
    private function _CleanRequest( $Key, $Value ) {
        if ( defined('TOKEN_PREFIX') === false ) { define('TOKEN_PREFIX', getRandomString(8)); }
        $special = array('RefVisible');

        /* Are we handling a special key? */
        switch ( strtolower($Key) ) {
            case 'authorization':
            case 'authorisation':
            case 'token_key':
            case 'token':
                $Value = NoNull(str_ireplace(array('bearer', ':'), '', $Value));
                if ( mb_strlen($Value) >= 36 ) {
                    if ( mb_substr($Value, 0, mb_strlen(TOKEN_PREFIX)) != TOKEN_PREFIX ) { return ''; }
                }
                break;

            case 'refvisible':
                if ( is_array($Value) ) { return NoNull(implode(',', $Value)); }
                break;

            default:
                /* Do Nothing */
        }

        /* Return the trimmed value */
        return NoNull($Value);
    }

    /**
     *  Function Removes the Queries from the URL Passed
     */
    private function _stripQueries( $String ) {
        $pgItem = explode('?', $String);
        $rVal = $pgItem[0];

        // Return the Item
        return $rVal;
    }

    /**
     * Function Determines the Appropriate Location and Returns an Array Containing
     *      the Display Page as well as the Page Root.
     */
    private function _readURL() {
        $ReqURI = substr($_SERVER['REQUEST_URI'], 1);
        if ( strpos($ReqURI, "?") ) { $ReqURI = substr($ReqURI, 0, strpos($ReqURI, "?")); }
        $filters = array('api', 'cdn', 'hooks', 'files');

        /* Change the ReqURI if an old pattern is found */
        $oldNew = array( 'api/content/blurbs/global' => 'api/posts/global',
                         'content/blurbs/global'     => 'api/posts/global'
                        );
        if ( in_array(strtolower($ReqURI), array_keys($oldNew)) ) {
            $ReqURI = $oldNew[$ReqURI];
        }

        /* Let's continue ... */
        $BasePath = explode( '/', BASE_DIR );
        $URLPath = explode( '/', $ReqURI );
        $route = $this->_getRouting();

        /* Ensure There Are No Blanks in the URL Path */
        $FullPath = explode('/', $ReqURI);
        $URLPath = array();
        foreach ( $FullPath as $sec ) {
            if ( NoNull($sec) != '' ) { $URLPath[] = NoNull($sec); }
        }

        /* Determine If We're In a Sub-Folder */
        foreach ( $BasePath as $Folder ) {
            if ( $Folder != "" ) {
                $idx = array_search($Folder, $URLPath);
                if ( is_numeric($idx) ) { unset( $URLPath[$idx] ); }
            }
        }

        /* Re-Assemble the URL Path */
        $URLPath = explode('/', implode('/', $URLPath));

        /* Confirm the Routing */
        if ( $route == '' ) { $route = (in_array($URLPath[0], $filters) ? $URLPath[0] : 'web'); }

        /* Construct the Return Array */
        $rVal = array( 'ReqURI' => '/' . NoNull(urldecode(implode('/', $URLPath))),
                       'Route'  => NoNull($route, 'web'),
                       'PgRoot' => urldecode((in_array($URLPath[0], $filters) ? $URLPath[1] : $URLPath[0])),
                      );

        /* Construct the Rest of the URL Items */
        $idx = 1;
        if ( count($URLPath) >= 2 ) {
            for ( $i = ((in_array($URLPath[0], $filters) ? 1 : 0) + 1); $i <= count($URLPath); $i++ ) {
                if ( NoNull($URLPath[$i]) != "" && (is_numeric($URLPath[$i]) || !in_array($URLPath[$i], array_values($rVal))) ) {
                    $rVal["PgSub$idx"] = urldecode($URLPath[$i]);
                    $idx++;
                }
            }
        }

        /* Return the Array of Values */
        return $rVal;
    }

    /**
     *  Function Determines the Site.id Value and Returns It
     */
    private function _getSiteID() {
        $SiteUrl = strtolower( NoNull($_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']) );
        $CleanKey = 'lookup-' . $SiteUrl;
        $data = getCacheObject($CleanKey);

        /* If we do not have data, collect it from the database */
        if ( is_array($data) === false ) {
            $ReplStr = array( '[SITE_URL]' => sqlScrub($SiteUrl) );
            $sqlStr = readResource(SQL_DIR . '/system/getSiteID.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $data = false;

                foreach ( $rslt as $Row ) {
                    if ( nullInt($Row['site_id']) > 0 ) {
                        $data = array( 'site_id' => nullInt($Row['site_id']) );
                    }
                }
            }

            /* If we have data, let's save it */
            if ( is_array($data) && count($data) > 0 ) { setCacheObject($CleanKey, $data); }
        }

        /* Return the Site.id value */
        return nullInt($data['site_id']);
    }

    /**
     * Function Saves the Cookies to the Browser's Cache (If Cookies Enabled)
     */
    private function _saveCookies( $cookieVals ) {
        if ( defined('TOKEN_KEY') === false ) { define('TOKEN_KEY', 'token'); }

        if (!headers_sent()) {
            $cookieVals['remember'] = BoolYN(YNBool(NoNull($cookieVals['remember'], 'N')));
            $valids = array( 'token', 'DispLang', 'remember', 'invite' );
            $longer = array( 'DispLang' );
            $domain = strtolower($_SERVER['SERVER_NAME']);

            $isHTTPS = false;
            $protocol = getServerProtocol();
            if ( $protocol == 'https' ) { $isHTTPS = true; }

            $RememberMe = YNBool(NoNull($cookieVals['remember'], 'N'));
            if ( $RememberMe !== true ) { unset($cookieVals['remember']); }

            foreach( $cookieVals as $key=>$val ) {
                if( in_array($key, $valids) ) {
                    $Expires = time() + COOKIE_EXPY;
                    $LifeTime = COOKIE_EXPY;

                    /* Determine the length of time the current cookie should exist for */
                    if ( $RememberMe ) { $LifeTime = 3600 * 24 * 30; }
                    if ( array_key_exists('remember', $_COOKIE) && $RememberMe !== true ) { $LifeTime = COOKIE_EXPY; }
                    if ( in_array($key, $longer) ) { $LifeTime = 3600 * 24 * 365; }
                    if ( $key == 'remember' && $RememberMe !== true ) { $LifeTime = -3600; }

                    /* Are we dealing with an authorisation token? */
                    if ( strtolower($key) == 'token' ) {
                        if ( mb_strlen($val) < 36 ) { $LifeTime = -3600; }
                        $key = TOKEN_KEY;
                    }

                    /* Get the expiration Unix timestamp */
                    $Expires = time() + $LifeTime;

                    /* Set the Cookie */
                    if ( $isHTTPS ) {
                        setcookie($key, "$val", $Expires, '/', $domain, $isHTTPS, true);
                    } else {
                        setcookie($key, "$val", $Expires, '/', $domain);
                    }
                }
            }
        }
    }
}

?>