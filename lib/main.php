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
     *  Vulnerability-Seeking Bastard Functions
     ** ********************************************************************** */
    /**
     *  Function determines if the request is looking for a WordPress, phpMyAdmin, or other
     *      open-source package-based attack vector and returns an abrupt message if so.
     */
    private function _isValidRequest() {
        $roots = array( 'phpmyadmin', 'phpmyadm1n', 'phpmy',
                        'tools', 'typo3', 'xampp', 'www', 'web',
                        'wp-admin', 'wp-content', 'wp-includes', 'vendor', 'wp-login.php'
                       );
        return !in_array(strtolower(NoNull($this->settings['PgRoot'])), $roots);
    }
}

?>