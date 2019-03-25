<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for the Documentation site template
 */
require_once(LIB_DIR . '/functions.php');
require_once(LIB_DIR . '/markdown.php');
use \Michelf\Markdown;

class Docs {
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
        $SiteUrl = NoNull($data['protocol'] . '://' . $data['HomeURL'] . '/themes/' . $data['location']);
        $PageUrl = strtolower(NoNull($this->settings['PgRoot'], 'intro'));
        for ( $i = 1; $i <= 9; $i++ ) {
            if ( NoNull($this->settings['PgSub' . $i]) != '' ) { $PageUrl .= '-' . strtolower(NoNull($this->settings['PgSub' . $i])); }
        }
        $PageFile = THEME_DIR . '/' . NoNull($data['location']) . "/resources/page-404.html";
        $mdFile = THEME_DIR . '/' . NoNull($data['location']) . "/source/$PageUrl.md";
        $this->settings['_location'] = NoNull($data['location']);
        $this->settings['_siteurl'] = NoNull($SiteUrl);
        $ima = time();

        $ReplStr = array( '[SITE_NAME]' => $data['name'],
                          '[HOMEURL]'   => NoNull($this->settings['HomeURL']),
                          '[LANG_CD]'   => NoNull($this->settings['_language_code'], $this->settings['DispLang']),
                          '[IMG_DIR]'   => $this->settings['_siteurl'] . '/img',
                          '[JS_DIR]'    => $this->settings['_siteurl'] . '/js',

                          '[NOW_AT]'    => date("Y-m-d\TH:i:s\Z", $ima),
                          '[NOW_UNIX]'  => $ima,

                          '[FOOTNAV]'   => $this->_getNavigation('foot'),
                          '[PAGENAV]'   => $this->_getNavigation('page'),
                          '[SIDENAV]'   => $this->_getNavigation('side'),
                          '[CONTENT]'   => ''
                         );
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = NoNull($Value);
        }

        // If the File Exists, Convert the Markdown to HTML and Return a Completed Page. Otherwise, 404.
        if ( file_exists($mdFile) ) {
            $PageFile = THEME_DIR . '/' . NoNull($data['location']) . "/resources/page-standard.html";
            require_once(LIB_DIR . '/posts.php');
            $post = new Posts($this->settings);

            // Get the Text and HTML (Pretty-Printing is done here on purpose)
            $txt = readResource($mdFile, $ReplStr);
            $html = $post->getMarkdownHTML( $txt, 0, false, true );

            // Clean up the HTML Output
            $expand = array( '<h1>' => 7, '<h2>' => 7, '<h3>' => 7, '<h4>' => 7, '<h5>' => 7, '<h6>' => 7,
                             '<p>' => 7, '<ul>' => 7, '</ul>' => 7, '<li>' => 8,
                            );
            foreach ( $expand as $tag=>$cnt ) {
                $html = str_replace($tag, "\r\n" . tabSpace($cnt) . $tag, $html);
            }

            // Construct the output HTML
            $ReplStr['[CONTENT]'] = tabSpace(5) . '<div class="col-md-7 col-xl-8 ml-md-auto py-8">' . "\r\n" .
                                    tabSpace(6) . '<article>' . "\r\n" .
                                    tabSpace(7) . NoNull($html) . "\r\n" .
                                    tabSpace(6) . '</article>' . "\r\n" .
                                    tabSpace(5) . '</div>';
            unset($post);
        }

        // If We Have Nothing, then check if this is a page. If it's not, 404.
        if ( $ReplStr['[CONTENT]'] == '' ) {
            $PageFile = THEME_DIR . '/' . NoNull($data['location']) . '/resources/page-' . strtolower($PageUrl) . '.html';
            if ( file_exists($PageFile) === false ) { redirectTo( $this->settings['HomeURL'] . '/404' ); }
        }

        // Return the Completed HTML
        return readResource($PageFile, $ReplStr);
    }

    private function _getNavigation( $topSide = 'top' ) {
        $ReplStr = array( '[SITE_NAME]' => $data['name'],
                          '[HOMEURL]'   => NoNull($this->settings['HomeURL']),
                          '[IMG_DIR]'   => $this->settings['_siteurl'] . '/img',
                         );
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = NoNull($Value);
        }

        $NavFile = THEME_DIR . '/' . NoNull($this->settings['_location']) . "/resources/nav-$topSide.html";
        if ( file_exists($NavFile) ) {
            return readResource($NavFile, $ReplStr);
        }

        // If we're here, the Navigation Item Does Not Exist
        return '';
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
    }
}
?>