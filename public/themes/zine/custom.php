<?php

/**
 * Class contains the rules and methods called for the Zine site template
 */
require_once(LIB_DIR . '/functions.php');

class Zine {
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
    private function _getPageHTML( $data ) {
        $HomeUrl = NoNull($this->settings['HomeURL']);
        $Theme = NoNull($data['location'], 'templates');

        /* Determine the Preferences for the Person */
        $BodyClass = ' font-' . NoNull($this->settings['_fontfamily'], 'auto') .
                     ' size-' . NoNull($this->settings['_fontsize'], 'md') .
                     ' ' . NoNull($this->settings['_colour'], 'default');

        /* Construct the Primary Return Array */
        $ReplStr = array( '[SHARED_FONT]'  => $HomeUrl . '/shared/fonts',
                          '[SHARED_CSS]'   => $HomeUrl . '/shared/css',
                          '[SHARED_IMG]'   => $HomeUrl . '/shared/img',
                          '[SHARED_JS]'    => $HomeUrl . '/shared/js',

                          '[SITE_IMG]'     => $HomeUrl . "/themes/$Theme/img",
                          '[SITE_JS]'      => $HomeUrl . "/themes/$Theme/js",
                          '[HOMEURL]'      => $HomeUrl,

                          '[CSS_VER]'      => CSS_VER,
                          '[GENERATOR]'    => GENERATOR . " (" . APP_VER . ")",
                          '[APP_NAME]'     => APP_NAME,
                          '[APP_VER]'      => APP_VER,
                          '[PGSUB_1]'      => NoNull($this->settings['PgSub1']),
                          '[CC_LICENSE]'   => $this->_getCCIcons($data['license']),
                          '[YEAR]'         => date('Y'),

                          '[CHANNEL_GUID]' => NoNull($data['channel_guid']),
                          '[CLIENT_GUID]'  => NoNull($data['client_guid']),

                          '[SITE_URL]'     => $this->settings['HomeURL'],
                          '[SITE_NAME]'    => $data['name'],
                          '[SITEDESCR]'    => $data['description'],
                          '[SITEKEYWD]'    => $data['keywords'],

                          '[BODY_CLASS]'   => NoNull($BodyClass),
                         );
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = NoNull($Value);
        }

        /* Return the Completed HTML */
        $ResFile = $this->_getResourceFile();
        return readResource($ResFile, $ReplStr);
    }

    /**
     *  Function determines which resource file to return
     */
    private function _getResourceFile() {
        $ResDIR = __DIR__ . '/resources';

        /* Which Page Should be Returned? */
        $ReqPage = 'page-' . NoNull($this->settings['PgRoot'], 'main') . '.html';

        /* Confirm the Page Exists and Return the proper Resource path */
        if ( file_exists("$ResDIR/$ReqPage") === false ) { $ReqPage = 'page-404.html'; }
        return "$ResDIR/$ReqPage";
    }

    /**
     *  Function returns the appropriate Creative Commons icons for the current site
     */
    private function _getCCIcons( $license ) {
        $valids = array('zero', 'share', 'sa', 'remix', 'pd', 'nd', 'nc', 'by');
        $icons = explode('-', str_replace(array(' '), '-', strtolower(NoNull($license))));
        $html = '';

        foreach ( $icons as $icon ) {
            $icon = NoNull($icon);
            if ( in_array($icon, $valids) ) {
                if ( mb_strlen($html) <= 0 ) { $html = '<i class="fab fa-creative-commons"></i>'; }

                $html .= '<i class="fab fa-creative-commons-' . $icon . '"></i>';
            }
        }

        /* Return the HTML elements */
        return $html;
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