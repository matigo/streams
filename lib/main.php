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

        /* So long as we have what appears to be a valid request, respond accoringly */
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

        /* Return the Data in the Correct Format */
        formatResult($rslt, $this->settings, $type, $code, $meta, $more);
    }

    /**
     *  Function Constructs and Returns the Language String Replacement Array
     */
    private function _getReplStrArray() {
        $data = array( '[SITEURL]' => NoNull($this->settings['HomeURL']),
                       '[RUNTIME]' => getRunTime('html'),
                      );
        if ( is_array($this->strings) && count($this->strings) > 0 ) {
            foreach ( $this->strings as $kk=>$vv ) {
                $data["[$kk]"] = NoNull($vv);
            }
        }

        /* Return the completed array */
        return $data;
    }

    /** ********************************************************************** *
     *  Bad Behaviour Functions
     ** ********************************************************************** */
    /**
     *  Function Checks to Ensure a Device Isn't Hammering the System Like an Idiot
     *
     *  Note: Trusted automation (cron / scheduled jobs) is exempted so it can never
     *      be caught by this again - see the 25D060 removal, which pulled this
     *      function entirely because cronjobs sharing an identity bucket were
     *      tripping the limit.
     */
    private function _checkForHammer() {
        if ( defined('CRON_KEY') === false ) { define('CRON_KEY', ''); }
        if ( defined('HAMMER_LIMIT') === false ) { define('HAMMER_LIMIT', 120); }
        if ( defined('HAMMER_LIMIT_AUTH') === false ) { define('HAMMER_LIMIT_AUTH', nullInt(HAMMER_LIMIT, 120) * 10); }
        $HLimit = nullInt(HAMMER_LIMIT, 120);
        if ( $HLimit <= 0 ) { return true; }

        /* Trusted automation presenting the shared Cron Key bypasses the limit entirely */
        if ( mb_strlen(NoNull(CRON_KEY)) >= 20 && NoNull($this->settings['key']) == NoNull(CRON_KEY) ) { return true; }

        /* Requests originating from this server itself are trusted automation, not public traffic */
        $ip = getVisitorIPv4();
        if ( in_array($ip, array('127.0.0.1', '::1')) ) { return true; }

        /* Authenticated requests (generally paying customers) get a much higher ceiling - scrapers don't carry valid tokens */
        $Token = NoNull($this->settings['token']);
        if ( mb_strlen($Token) >= 30 ) {
            $HLimit = nullInt(HAMMER_LIMIT_AUTH, $HLimit * 10);
        } else {
            /* Anonymous traffic - bucket by IP + User-Agent so one shared VPN/NAT egress isn't punished as a single client */
            $Token = $ip . '-' . NoNull($_SERVER['HTTP_USER_AGENT']);
        }

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

            /* Record the current number of requests (short expiry - the minute is baked into the key already) */
            setCacheObject($CleanKey, array('hit_count' => nullInt($reqs)), 120);

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
                        '.env', 'wlwmanifest.xml',
                       );
        if ( in_array(strtolower(NoNull($this->settings['PgSub1'], $this->settings['PgRoot'])), $roots) ) { return false; }
        if ( strpos(strtolower(NoNull($this->settings['ReqURI'])), '.php') !== false ) { return false; }
        if ( strpos(strtolower(NoNull($this->settings['ReqURI'])), '.txt') !== false ) { return false; }
        if ( strpos(strtolower(NoNull($this->settings['ReqURI'])), '.md') !== false ) { return false; }
        return true;
    }
}

?>