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

        /* Ensure the Asset Version.id Is Set */
        if ( defined('CSS_VER') === false ) {
            $ver = filemtime(CONF_DIR . '/versions.php');
            if ( nullInt($ver) <= 0 ) { $ver = nullInt(APP_VER); }
            define('CSS_VER', $ver);
        }

        $sets = new Cookies();
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

        // Check to Ensure the Visitor is not Overwhelming the Server(s) and Respond Accordingly
        if ( $this->_checkForHammer() && $this->_isValidRequest() ) {
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
            $code = $this->_isValidRequest() ? 420 : 422;
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
        if ( defined('HTTPS_ENABLED') === false ) { define('HTTPS_ENABLED', 0); }
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
        $HLimit = (defined('HAMMER_LIMIT')) ? nullInt(HAMMER_LIMIT, 120) : 120;
        if ( $HLimit <= 0 ) { return true; }

        $Token = NoNull($this->settings['token']);
        if ( mb_strlen($Token) < 30 ) { $Token = NoNull($this->settings['_address']); }
        if ( mb_strlen($Token) < 7 ) { $Token = getVisitorIPv4(); }

        /* Check To See If Everything's Good */
        if ( mb_strlen($Token) >= 7 ) {
            $CleanKey = 'hammer-' . md5($Token . apiDate(strtotime(date("Y-m-d H:i:00")), 'U'));
            $data = getCacheObject($CleanKey);
            $reqs = 0;

            /* If we have data, how many requests currently exist? */
            if ( is_array($data) ) {
                $reqs = nullInt($data['hit_count']);
                if ( $reqs <= 0 ) { $reqs = 0; }
            }
            $reqs++;

            /* Record the current number of requests */
            setCacheObject($CleanKey, array('hit_count' => nullInt($reqs)) );

            /* Return a boolean based on the hit count */
            if ( $reqs < $HLimit ) { return true; }
        }

        /* If we're here, we must assume the connection is invalid */
        return false;
    }

    /**
     *  Function determines if the request is looking for a WordPress, phpMyAdmin, or other
     *      open-source package-based attack vector and returns an abrupt message if so.
     */
    private function _isValidRequest() {
        $roots = array( 'phpmyadmin', 'phpmyadm1n', 'phpmy', 'pass',
                        'tools', 'typo3', 'xampp', 'www', 'web',
                        'wp-admin', 'wp-content', 'wp-includes', 'vendor',
                        '.env', 'ads.txt', 'wlwmanifest.xml',
                       );
        if ( in_array(strtolower(NoNull($this->settings['PgSub1'], $this->settings['PgRoot'])), $roots) ) { return false; }
        if ( strpos(strtolower(NoNull($this->settings['ReqURI'])), '.php') !== false ) { return false; }
        if ( strpos(strtolower(NoNull($this->settings['ReqURI'])), '.txt') !== false ) { return false; }
        if ( strpos(strtolower(NoNull($this->settings['ReqURI'])), '.md') !== false ) { return false; }
        return true;
    }
}

?>