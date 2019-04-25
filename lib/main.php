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
    var $messages;

    function __construct() {
        $GLOBALS['Perf']['app_s'] = getMicroTime();

        $sets = new cookies;
        $this->settings = $sets->cookies;
        $this->messages = getLangDefaults($this->settings['_language_code']);
        unset( $sets );
    }

    /* ********************************************************************* *
     *  Function determines what needs to be done and returns the
     *      appropriate JSON Content
     * ********************************************************************* */
    function buildResult() {
        $ReplStr = $this->_getReplStrArray();
        $html = readResource(FLATS_DIR . '/templates/500.html', $ReplStr);
        $type = 'text/html';
        $rslt = false;
        $meta = false;
        $code = 500;

        // Check to Ensure the Visitor is not Overwhelming the Server(s) and Respond Accordingly
        if ( $this->_checkForHammer() ) {
            switch ( strtolower($this->settings['Route']) ) {
                case 'api':
                    require_once(LIB_DIR . '/api.php');
                    break;

                default:
                    require_once(LIB_DIR . '/web.php');
                    break;
            }

            $data = new Route($this->settings, $this->messages);
            $rslt = $data->getResponseData();
            $type = $data->getResponseType();
            $code = $data->getResponseCode();
            $meta = $data->getResponseMeta();
            $more = ((method_exists($data, 'getHasMore')) ? $data->getHasMore() : false);
            unset($data);

        } else {
            $html = readResource( FLATS_DIR . "/templates/420.html", $ReplStr);
            $code = 420;
        }

        // Close the Persistent SQL Connection If Needs Be
        closePersistentSQLConn();

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
     *  Function Ensures the Token Is Updated When an API Call is Made
     */
    private function _touchTokenRecord() {
        $Token = NoNull($this->settings['token']);
        $TokenID = 0;

        if ( $Token != '' ) {
            $data = explode('_', $Token);
            if ( count($data) == 3 ) {
                if ( $data[0] == str_replace('_', '', TOKEN_PREFIX) ) {
                    $TokenID = alphaToInt($data[1]);
                    $TokenGUID = NoNull($data[2]);
                }
            }

            // No Point Continuing If We Have Nothing Here
            if ( $TokenID <= 0 || $TokenGUID == '' ) { return false; }

            // Construct the SQL Query
            $ReplStr = array( '[TOKEN_ID]'   => nullInt($TokenID),
                              '[TOKEN_GUID]' => sqlScrub($TokenGUID),
                              '[LIFESPAN]'   => nullInt(COOKIE_EXPY),
                             );
            $sqlStr = readResource(SQL_DIR . '/auth/touchToken.sql', $ReplStr, true);
            writeSQLCache($sqlStr);
        }
    }

    /**
     *  Function Constructs and Returns the Language String Replacement Array
     */
    private function _getReplStrArray() {
        $rVal = array( '[SITEURL]' => NoNull($this->settings['HomeURL']),
                       '[RUNTIME]' => getRunTime('html'),
                      );
        foreach ( $this->messages as $Key=>$Val ) {
            $rVal["[$Key]"] = NoNull($Val);
        }

        // Return the Array
        return $rVal;
    }
}

?>