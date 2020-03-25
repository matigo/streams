<?php

/**
 * @author Jason F. Irwin
 *
 * Class Responds to the Data Route and Returns the Appropriate Data
 */
require_once(CONF_DIR . '/versions.php');
require_once(CONF_DIR . '/config.php');
require_once(LIB_DIR . '/functions.php');
require_once(LIB_DIR . '/cookies.php');

class Streams {
    var $settings;
    var $strings;

    function __construct() {
        $GLOBALS['Perf']['app_s'] = getMicroTime();

        $sets = new cookies;
        $this->settings = $sets->cookies;
        $this->strings = getLangDefaults($this->settings['_language_code']);
        unset( $sets );
    }

    /* ********************************************************************* *
     *  Function determines what needs to be done and returns the
     *      appropriate JSON Content
     * ********************************************************************* */
    function buildResult() {
        $ReplStr = $this->_getReplStrArray();
        $rslt = readResource(FLATS_DIR . '/templates/500.html', $ReplStr);
        $type = 'text/html';
        $meta = false;
        $code = 500;

        // Set some of the Globals
        $GLOBALS['site_id'] = $data['site_id'];

        // Check to Ensure the Visitor is not Overwhelming the Server(s) and Respond Accordingly
        if ( $this->_checkForHammer() && $this->_isValidRequest() && $this->_isValidAgent() ) {
            switch ( strtolower($this->settings['Route']) ) {
                case 'api':
                    require_once(LIB_DIR . '/api.php');
                    break;

                case 'hooks':
                    require_once(LIB_DIR . '/hooks.php');
                    break;

                default:
                    require_once(LIB_DIR . '/web.php');
                    break;
            }

            $data = new Route($this->settings, $this->strings);
            $rslt = $data->getResponseData();
            $type = $data->getResponseType();
            $code = $data->getResponseCode();
            $meta = $data->getResponseMeta();
            $more = ((method_exists($data, 'getHasMore')) ? $data->getHasMore() : false);
            unset($data);

        } else {
            if ( $this->_isValidAgent() ) {
                $code = $this->_isValidRequest() ? 420 : 422;
            } else {
                $code = 403;
            }
            $rslt = readResource( FLATS_DIR . "/templates/$code.html", $ReplStr);
        }

        // Return the Data in the Correct Format
        formatResult($rslt, $this->settings, $type, $code, $meta, $more);
    }

    /**
     *  Function Checks to Ensure the Connecting Protocol is Correct. Incorrect Protocols
     *      need to be corrected
     */
    private function _checkValidProtocol() {
        $protocol = getServerProtocol();
        $rVal = false;

        if ( HTTPS_ENABLED == 1 && $protocol == 'https' ) { $rVal = true; }
        if ( HTTPS_ENABLED == 0 && $protocol == 'http' ) { $rVal = true; }

        // Return the Boolean Response
        return $rVal;
    }

    /**
     *  Function Constructs and Returns the Language String Replacement Array
     */
    private function _getReplStrArray() {
        $rVal = array( '[SITEURL]' => NoNull($this->settings['HomeURL']),
                       '[RUNTIME]' => getRunTime('html'),
                      );
        foreach ( $this->strings as $Key=>$Val ) {
            $rVal["[$Key]"] = NoNull($Val);
        }

        // Return the Array
        return $rVal;
    }

    /** ********************************************************************** *
     *  Bad Behaviour Functions
     ** ********************************************************************** */
    /**
     *  Function Checks to Ensure a Device Isn't Hammering the System Like an Idiot
     */
    private function _checkForHammer() {
        $Token = NoNull($this->settings['token']);
        $HLimit = (defined(HAMMER_LIMIT)) ? nullInt(HAMMER_LIMIT, 120) : 120;
        $TokenGUID = '';
        $hitCount = 0;

        if ( $Token != '' ) {
            $data = explode('_', $Token);
            if ( count($data) == 3 ) {
                if ( $data[0] == str_replace('_', '', TOKEN_PREFIX) ) { $TokenGUID = NoNull($data[2]); }
            }
        }

        // If There Is No Token, Grab the Device's IP Address
        if ( NoNull($TokenGUID) == '' ) { $TokenGUID = getVisitorIPv4(); }

        // Check To See If Everything's Good
        if ( $TokenGUID != '' && mb_strlen($TokenGUID) >= 6 ) {
            $key = NoNull(strtotime(date("Y-m-d H:i:00")));
            $hitCount = nullInt(readSetting($TokenGUID, $key));
            if ( $hitCount <= 0 ) { $hitCount = 0; }
            $hitCount++;
            saveSetting($TokenGUID, $key, $hitCount);

            // If the HitCount Has Exceeded Limits, Return a Failure
            if ( $hitCount > $HLimit ) { return false; }
        }

        // If We're Here, We Must Assume the Connection is Valid
        return true;
    }

    /**
     *  Function determines if the request is looking for a WordPress, phpMyAdmin, or other
     *      open-source package-based attack vector and returns an abrupt message if so.
     */
    private function _isValidRequest() {
        $roots = array( 'phpmyadmin', 'phpmyadm1n', 'phpmy',
                        'tools', 'typo3', 'xampp', 'www', 'web',
                        'wp-admin', 'wp-content', 'wp-includes', 'vendor',
                       );
        if ( in_array(strtolower(NoNull($this->settings['PgSub1'], $this->settings['PgRoot'])), $roots) ) { return false; }
        if ( strpos(strtolower(NoNull($this->settings['ReqURI'])), '.php') !== false ) { return false; }

        return true;
    }

    /**
     *  Function determines if the reported agent is valid for use or not. This is not meant to be a comprehensive list of
     *      unacceptable agents, as agent strings are easily spoofed.
     */
    private function _isValidAgent() {
        $excludes = array( 'ahrefsbot', 'mj12bot', 'mb2345browser', 'semrushbot', 'mmb29p', 'mbcrawler', 'blexbot', 'sogou web spider',
                           'serpstatbot', 'semanticscholarbot', 'yandexbot', 'yandeximages', 'gwene', 'barkrowler', 'yeti',
                           'seznambot', 'domainstatsbot', 'sottopop', 'megaindex.ru', '9537.53', 'seekport crawler', 'iccrawler',
                           'magpie-crawler', 'crawler4j', 'facebookexternalhit', 'turnitinbot', 'netestate',
                           'thither.direct', 'liebaofast', 'micromessenger', 'youdaobot', 'theworld', 'qqbrowser',
                           'dotbot', 'exabot', 'gigabot', 'slurp', 'keybot translation', 'searchatlas.com',
                           'bingbot/2.0', 'aspiegelbot', 'baiduspider', 'ruby',
                           'zh-cn;oppo a33 build/lmy47v', 'oppo a33 build/lmy47v;wv' );
        $agent = strtolower(NoNull($_SERVER['HTTP_USER_AGENT']));
        if ( $agent != '' ) {
            foreach ( $excludes as $chk ) {
                if ( mb_strpos($agent, $chk) !== false ) { return false; }
            }
        }
        return true;
    }
}

?>