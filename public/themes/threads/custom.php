<?php

/**
 * Class contains the rules and methods called for the Threads social theme
 */
require_once(LIB_DIR . '/functions.php');

class Threads {
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
    public function getPagination( $data ) { return ''; }
    public function getPopularPosts( $data ) { return ''; }
    public function getSiteNav( $data ) { return ''; }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function returns a completed HTML document to present to the browser
     */
    private function _getPageHTML( $data ) {
        $HomeUrl = NoNull($this->settings['HomeURL']);
        $ResFile = $this->_getResourceFile();
        $Theme = NoNull($data['location']);

        /* Construct the Primary Return Array */
        $ReplStr = array( '[FONT_DIR]'      => $HomeUrl . "/themes/$Theme/fonts",
                          '[CSS_DIR]'       => $HomeUrl . "/themes/$Theme/css",
                          '[IMG_DIR]'       => $HomeUrl . "/themes/$Theme/img",
                          '[JS_DIR]'        => $HomeUrl . "/themes/$Theme/js",
                          '[HOMEURL]'       => $HomeUrl,

                          '[CSS_VER]'       => CSS_VER,
                          '[GENERATOR]'     => GENERATOR . " (" . APP_VER . ")",
                          '[APP_NAME]'      => APP_NAME,
                          '[APP_VER]'       => APP_VER,
                          '[LANG_CD]'       => NoNull($this->settings['_language_code'], $this->settings['DispLang']),
                          '[YEAR]'          => date('Y'),

                          '[SITE_URL]'      => $this->settings['HomeURL'],
                          '[SITE_NAME]'     => $data['name'],
                          '[SITEDESCR]'     => $data['description'],
                          '[SITEKEYWD]'     => $data['keywords'],
                         );
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = NoNull($Value);
        }

        /* Return the Completed HTML */
        return readResource($ResFile, $ReplStr);
    }

    /**
     *  Function determines which resource file to return
     */
    private function _getResourceFile() {
        $ReqPage = 'page-' . NoNull($this->settings['PgRoot'], 'main') . '.html';
        $ResDIR = __DIR__ . '/resources';

        /* Confirm the Page Exists and Return the proper Resource path */
        if ( file_exists("$ResDIR/$ReqPage") === false ) { $ReqPage = 'page-404.html'; }
        return "$ResDIR/$ReqPage";
    }

    /** ********************************************************************* *
     *  Class Functions
     ** ********************************************************************* */
    /**
     *  Function Sets a Message in the Meta Field
     */
    private function _setMetaMessage( $msg, $code = 0 ) {
        if ( is_array($this->settings['errors']) === false ) { $this->settings['errors'] = array(); }
        if ( NoNull($msg) != '' ) { $this->settings['errors'][] = NoNull($msg); }
        if ( $code > 0 && nullInt($this->settings['status']) == 0 ) { $this->settings['status'] = nullInt($code); }
        return false;
    }
}
?>