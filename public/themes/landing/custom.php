<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for the Landing site template
 */
require_once(LIB_DIR . '/functions.php');

class Landing {
    var $settings;
    var $strings;

    function __construct( $settings, $strings = false ) {
        $this->settings = $settings;
        $this->strings = ((is_array($strings)) ? $strings : getLangDefaults($this->settings['_language_code']));
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    /**
     *  Function Returns the Response Type (HTML / XML / JSON / etc.)
     */
    public function getResponseType() {
        return NoNull($this->settings['type'], 'application/json');
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
        return is_array($this->settings['errors']) ? $this->settings['errors'] : false;
    }

    /**
     *  Function Returns Whether the Dataset May Have More Information or Not
     */
    public function getHasMore() {
        return BoolYN($this->settings['has_more']);
    }

    /** ********************************************************************* *
     *  Public Functions
     ** ********************************************************************* */
    public function getPageHTML( $data ) { return $this->_getPageHTML($data); }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    private function _getPageHTML( $data ) {
        $ThemeLocation = THEME_DIR . '/' . $data['location'];
        $ResDIR = $ThemeLocation . "/resources";

        // Which Page Should be Returned?
        $ReqPage = 'page-' . NoNull($this->settings['PgRoot'], 'landing') . '.html';
        if ( file_exists("$ResDIR/$ReqPage") === false ) { $ReqPage = 'page-404.html'; }

        // Construct the String Replacement Array
        $SiteUrl = NoNull($data['protocol'] . '://' . $data['HomeURL'] . '/themes/' . $data['location']);
        $ReplStr = array( '[HOMEURL]'      => NoNull($this->settings['HomeURL']),
                          '[CSS_VER]'      => CSS_VER,
                          '[GENERATOR]'    => GENERATOR . " (" . APP_VER . ")",
                          '[APP_NAME]'     => APP_NAME,
                          '[APP_VER]'      => APP_VER,
                          '[LANG_CD]'      => NoNull($this->settings['_language_code'], $this->settings['DispLang']),
                          '[PGSUB_1]'      => NoNull($this->settings['PgSub1']),

                          '[FONT_DIR]'     => $SiteUrl . '/fonts',
                          '[CSS_DIR]'      => $SiteUrl . '/css',
                          '[IMG_DIR]'      => $SiteUrl . '/img',
                          '[JS_DIR]'       => $SiteUrl . '/js',

                          '[CHANNEL_GUID]' => NoNull($data['channel_guid']),
                          '[CLIENT_GUID]'  => NoNull($data['client_guid']),
                          '[PRIMARY_URL]'  => NoNull($this->settings['_primary_url']),
                          '[SOCIAL_URL]'   => $this->_getSocialSiteUrl(),
                          '[AUTH_TOKEN]'   => NoNull($this->settings['token']),

                          '[SITE_URL]'     => $this->settings['HomeURL'],
                          '[SITE_NAME]'    => $data['name'],
                          '[SITEDESCR]'    => $data['description'],
                          '[SITEKEYWD]'    => $data['keywords'],
                         );
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = NoNull($Value);
        }

        // Return the Completed HTML
        return readResource("$ResDIR/$ReqPage", $ReplStr);
    }

    /** ********************************************************************* *
     *  Class Functions
     ** ********************************************************************* */
    /**
     *  Function Returns the URL for the primary Social Site or an empty string
     */
    private function _getSocialSiteUrl() {
        $sqlStr = readResource(SQL_DIR . '/site/getSocialUrl.sql');
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                return NoNull($Row['social_url']);
            }
        }
        return '';
    }

    /**
     *  Function Sets a Message in the Meta Field
     */
    private function _setMetaMessage( $msg, $code = 0 ) {
        if ( is_array($this->settings['errors']) === false ) { $this->settings['errors'] = array(); }
        if ( NoNull($msg) != '' ) { $this->settings['errors'][] = NoNull($msg); }
        if ( $code > 0 && nullInt($this->settings['status']) == 0 ) { $this->settings['status'] = nullInt($code); }
    }
}
?>