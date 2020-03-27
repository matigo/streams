<?php

/**
 * @author Jason F. Irwin
 * @copyright 2016
 *
 * Class contains the rules and methods called for Cookie Handling and
 *		Restoration of Primary Settings in Advantage
 */
require_once( LIB_DIR . '/functions.php');

class cookies {
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
        if ( ENFORCE_PHPVERSION == 1 && PHP_VERSION_ID < MIN_PHPVERSION) {
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
                $rVal[ $key ] = (is_array($val)) ? $val : $this->_CleanRequest($key, $val);
            }
        }

        foreach( $_POST as $key=>$val ) {
            $rVal[ $key ] = $this->_CleanRequest($key, $val);
        }

        foreach( $_GET as $key=>$val ) {
            if ( is_array($val) ) {
                if ( array_key_exists($key, $rVal) === false ) { $rVal[ $key ] = array(); }
                foreach ( $val as $kk=>$vv ) {
                    $rVal[ $key ][] = NoNull($vv);
                }

            } else {
                if ( !array_key_exists($key, $rVal) ) { $rVal[ $key ] = $this->_CleanRequest($key, $val); }
            }

        }

        foreach( $_COOKIE as $key=>$val ) {
            if ( !array_key_exists($key, $rVal) ) { $rVal[ $key ] = $this->_CleanRequest($key, $val); }
        }

        $gah = getallheaders();
        if ( is_array($gah) ) {
            $opts = array( 'authorisation'     => 'token',
                           'authorization'     => 'token',
                          );
            foreach ( getallheaders() as $key=>$val ) {
                if ( array_key_exists(strtolower($key), $opts) ) {
                    $rVal[ $opts[strtolower($key)] ] = $this->_CleanRequest($key, $val);
                }
            }
        }

        // Determine the Type
        $rVal['ReqType'] = strtoupper( NoNull($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'], NoNull($_SERVER['REQUEST_METHOD'], 'GET')) );
        if ( $rVal['ReqType'] == 'OPTIONS' ) { $rVal['ReqType'] = 'DELETE'; }

        // Assemble the Appropriate URL Path (Overrides Existing Information)
        $URLPath = $this->_readURL();
        foreach ( $URLPath as $Key=>$Val ) {
	        $rVal[ $Key ] = $Val;
        }

        // Add Any Missing Data from URL Query String (Does Not Override Existing Data)
        $missedData = $this->checkForMissingData();
        foreach( $missedData as $key=>$val ) {
            if ( !array_key_exists($key, $rVal) ) {
                $rVal[ $key ] = $this->_CleanRequest($key, $val);
            }
        }

        // Populate Missing or Blank Array Values with Defaults (Does Not Override Existing Data)
        $defaults = $this->_getCookieDefaults();
        foreach($defaults as $key=>$val) {
            if ( !array_key_exists($key, $rVal) ) {
                $rVal[ $key ] = $val;
            }
        }

        // Ensure the Token Value (if exists) Is Correctly Formatted
        if ( array_key_exists('token', $rVal) && NoNull($rVal['token']) != '' ) {
            if ( strpos($rVal['token'], 'Bearer ') == 0 ) {
                $rVal['token'] = NoNull(str_replace('Bearer ', '', $rVal['token']));
            }
        }

        // Scrub the Page Pointers
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

                case '_device_id':
                case '__cfduid':
                    if ( NoNull($Val) != '' ) {
                        $rVal['_device_id'] = NoNull($Val);
                    }
                    break;
            }
        }

        // Ensure there is a Device ID
        if ( NoNull($rVal['_device_id']) == '' ) { $rVal['_device_id'] = NoNull($rVal['__cfduid'], getRandomString(32)); }

        // Get the Appropriate Account Data
        if ( NoNull($rVal['token']) != '' ) {
            require_once( LIB_DIR . '/auth.php' );
            $auth = new Auth( $rVal );
            $data = $auth->getTokenData($rVal['token']);
            unset($auth);

            if ( is_array($data) ) {
                foreach ( $data as $Key=>$Value ) { $rVal[ $Key ] = $Value; }
            }

            // Set the Display Language
            $rVal['DispLang'] = $this->_getDisplayLanguage($rVal['_language_code']);
        }

        // Don't Keep an Empty Array Object with the Request URI
        unset($rVal[substr($rVal['ReqURI'], 1)]);

        // Save Some Cookies for Later Use
        $this->_saveCookies($rVal);

        // Return the Cookies
        return $rVal;
    }

    /**
     * Function Returns a Token without the Preceeding Pound
     */
    private function cleanToken( $Token ) {
        return NoNull(str_replace( "#", "", $Token ));
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

        // Return an Array Containing the Missing Data
        return $rVal;
    }

    /**
     * Function Returns the Default Cookie Values
     */
    private function _getCookieDefaults() {
        $SiteURL = strtolower( NoNull($_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']) );
        $LangCd = $this->_getDisplayLanguage();

        // Return the Array of Defaults
        $Protocol = getServerProtocol();
        return array( 'DispLang'       => $LangCd,
                      'HomeURL'        => $Protocol . '://' . $SiteURL,
                      'Route'          => 'web',
                      'site_id'        => $this->_getSiteID(),

                      '_address'       => getVisitorIPv4(),
                      '_account_id'    => 0,
                      '_persona_id'    => 0,
                      '_display_name'  => '',
                      '_account_type'  => 'account.anonymous',
                      '_language_code' => $LangCd,
                      '_logged_in'     => false,
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

        // Determine the Routing based on the Subdomain
        $ReqURL = strtolower( NoNull($_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']) );
        $parts = explode('.', $ReqURL);

        // Return the Routing or an Empty String
        if ( array_key_exists($parts[0], $paths) ) { return NoNull($paths[$parts[0]]); }
        return '';
    }

    /**
     *  Function Returns the Appropriate Display Language
     */
    private function _getDisplayLanguage( $AccountLang = '' ) {
        $langcd = NoNull($_GET['DispLang'], $_COOKIE['DispLang']);
        if ( defined('ENABLE_MULTILANG') === false ) { define('ENABLE_MULTILANG', 0); }
        if ( defined('DEFAULT_LANG') === false ) { define('DEFAULT_LANG', 'en'); }

        if ( $langcd == '' || NoNull($AccountLang) != '' ){ $langcd = NoNull($AccountLang, DEFAULT_LANG); }
        $rVal = DEFAULT_LANG;

        if ( ENABLE_MULTILANG == 1 ) {
            $rVal = ( substr( NoNull($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ) == '' ) ? NoNull($langcd, DEFAULT_LANG)
                                                                                 : substr( $_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 5);
        } else {
            $rVal = DEFAULT_LANG;
        }

        return strtolower($rVal);
    }

    /**
     *  Function Cleans the Value, URL Decoding It and Returning the Results
     */
    private function _CleanRequest( $Key, $Value ) {
        $special = array('RefVisible');
        $rVal = '';

        if( in_array($Key, $special) ) {
            $rVal = implode(',', $Value);
        } else {
            $rVal = $Value;
        }

        //Return the Cleaned Request Value
        return NoNull($rVal);
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
     *		the Display Page as well as the Page Root.
     */
    private function _readURL() {
        $ReqURI = substr($_SERVER['REQUEST_URI'], 1);
        if ( strpos($ReqURI, "?") ) { $ReqURI = substr($ReqURI, 0, strpos($ReqURI, "?")); }
        $filters = array('api', 'cdn', 'files');

        // Change the ReqURI if an old pattern is found
        $oldNew = array( 'api/content/blurbs/global' => 'api/posts/global',
                         'content/blurbs/global'     => 'api/posts/global'
                        );
        if ( in_array(strtolower($ReqURI), array_keys($oldNew)) ) {
            $ReqURI = $oldNew[$ReqURI];
        }

        // Let's continue ...
        $BasePath = explode( '/', BASE_DIR );
        $URLPath = explode( '/', $ReqURI );
        $route = $this->_getRouting();

        // Ensure There Are No Blanks in the URL Path
        $FullPath = explode('/', $ReqURI);
        $URLPath = array();
        foreach ( $FullPath as $sec ) {
            if ( NoNull($sec) != '' ) { $URLPath[] = NoNull($sec); }
        }

        // Determine If We're In a Sub-Folder
        foreach ( $BasePath as $Folder ) {
        	if ( $Folder != "" ) {
	        	$idx = array_search($Folder, $URLPath);
	        	if ( is_numeric($idx) ) { unset( $URLPath[$idx] ); }
        	}
        }

        // Re-Assemble the URL Path
        $URLPath = explode('/', implode('/', $URLPath));

        // Confirm the Routing
        if ( $route == '' ) { $route = (in_array($URLPath[0], $filters) ? $URLPath[0] : 'web'); }

        // Construct the Return Array
        $rVal = array( 'ReqURI'	=> '/' . NoNull(urldecode(implode('/', $URLPath))),
                       'Route'  => NoNull($route, 'web'),
                       'PgRoot' => urldecode((in_array($URLPath[0], $filters) ? $URLPath[1] : $URLPath[0])),
                      );

        // Construct the Rest of the URL Items
        $idx = 1;
		if ( count($URLPath) >= 2 ) {
			for ( $i = ((in_array($URLPath[0], $filters) ? 1 : 0) + 1); $i <= count($URLPath); $i++ ) {
				if ( NoNull($URLPath[$i]) != "" && (is_numeric($URLPath[$i]) || !in_array($URLPath[$i], array_values($rVal))) ) {
					$rVal["PgSub$idx"] = urldecode($URLPath[$i]);
					$idx++;
				}
			}
		}

        // Return the Array of Values
        return $rVal;
    }

    /**
     *  Function Determines the Site.id Value and Returns It
     */
    private function _getSiteID() {
        $SiteURL = strtolower( NoNull($_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']) );

        $id = readSetting($SiteURL, 'id');
        if ( nullInt($id) <= 0 ) {
            $ReplStr = array( '[SITE_URL]' => sqlScrub($SiteURL) );
            $sqlStr = readResource(SQL_DIR . '/system/getSiteID.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $id = nullInt($Row['site_id']);
                }
            }

            // If We Have a Valid Value, Save It
            if ( $id > 0 ) { saveSetting($SiteURL, 'id', $id); }
        }

        // Return the ID
        return nullInt($id);
    }

    /**
     * Function Saves the Cookies to the Browser's Cache (If Cookies Enabled)
     */
    private function _saveCookies( $cookieVals ) {
        if (!headers_sent()) {
            $cookieVals['remember'] = BoolYN(YNBool(NoNull($cookieVals['remember'], 'N')));
            $valids = array( 'token', 'DispLang', 'remember', 'invite', '_device_id' );
            $longer = array( 'DispLang', '_device_id' );
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
                    if ( $RememberMe ) { $LifeTime = 3600 * 24 * 30; }
                    if ( array_key_exists('remember', $_COOKIE) && $RememberMe !== true ) { $LifeTime = COOKIE_EXPY; }
                    if ( in_array($key, $longer) ) { $LifeTime = 3600 * 24 * 365; }
                    if ( $key == 'remember' && $RememberMe !== true ) { $LifeTime = -3600; }
                    $Expires = time() + $LifeTime;

                    // Set the Cookie
                    if ( $isHTTPS ) {
                        setcookie($key, $val, $Expires, '/', strtolower($_SERVER['SERVER_NAME']), $isHTTPS, true);
                    } else {
                        setcookie($key, $val, $Expires);
                    }
                }
            }
        }
    }
}

?>