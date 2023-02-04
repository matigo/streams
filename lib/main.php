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
        $HLimit = (defined(HAMMER_LIMIT)) ? nullInt(HAMMER_LIMIT, 120) : 120;
        if ( $HLimit <= 0 ) { return true; }

        $Token = NoNull($this->settings['token']);
        if ( mb_strlen($Token) < 30 ) { $Token = NoNull($this->settings['_address']); }
        if ( mb_strlen($Token) < 7 ) { $Token = getVisitorIPv4(); }

        /* Check To See If Everything's Good */
        if ( $Token != '' && mb_strlen($Token) >= 7 ) {
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
            if ( $reqs <= $HLimit ) { return true; }
        }

        /* If we're here, we must assume the connection is invalid */
        return false;
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
                           'serpstatbot', 'semanticscholarbot', 'yandexbot', 'yandeximages', 'gwene', 'barkrowler', 'yeti', 'ccbot',
                           'seznambot', 'domainstatsbot', 'sottopop', 'megaindex.ru', '9537.53', 'seekport crawler', 'iccrawler',
                           'magpie-crawler', 'crawler4j', 'facebookexternalhit', 'turnitinbot', 'netestate', 'dataforseo',
                           'thither.direct', 'liebaofast', 'micromessenger', 'youdaobot', 'theworld', 'qqbrowser',
                           'dotbot', 'exabot', 'gigabot', 'slurp', 'keybot translation', 'searchatlas.com',
                           'bingbot/2.0', 'aspiegelbot', 'baiduspider', 'ruby', 'LanaiBotmarch',
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