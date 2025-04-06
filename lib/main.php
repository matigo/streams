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
        if ( $this->_isValidRequest() ) {
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