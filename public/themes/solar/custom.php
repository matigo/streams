<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for the Solar site template
 */
require_once(LIB_DIR . '/functions.php');
require_once(LIB_DIR . '/markdown.php');
use \Michelf\Markdown;

class Solar {
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
        $Theme = NoNull( substr(strrchr(__DIR__, '/'), 1) );
        if ( is_array($data) === false ) { $data = $this->site; }
        $langRepl = array( '{year}' => date('Y') );

        /* Determine the Body Classes */
        $BodyClass = ' font-' . NoNull($this->settings['_fontfamily'], NoNull($data['font-family'], 'auto')) .
                     ' size-' . NoNull($this->settings['_fontsize'], NoNull($data['font-size'], 'md')) .
                     ' ' . NoNull($this->settings['_color'], NoNull($data['color'], 'auto'));

        /* Is there a Target URL Required? */
        $TargetUrl = NoNull($this->settings['target_url'], $this->settings['target']);
        if ( mb_strlen($TargetUrl) <= 5 ) { $TargetUrl = ''; }
        if ( mb_strpos($TargetUrl, $HomeUrl) == 0 ) {
            $TargetUrl = str_replace($HomeUrl, '', $TargetUrl);
        } else {
            $TargetUrl = '';
        }

        /* Construct the Primary Return Array */
        $ReplStr = array( '[SHARED_FONT]'     => $HomeUrl . '/shared/fonts',
                          '[SHARED_CSS]'      => $HomeUrl . '/shared/css',
                          '[SHARED_IMG]'      => $HomeUrl . '/shared/images',
                          '[SHARED_JS]'       => $HomeUrl . '/shared/js',

                          '[SITE_FONT]'       => $HomeUrl . "/themes/$Theme/fonts",
                          '[SITE_CSS]'        => $HomeUrl . "/themes/$Theme/css",
                          '[SITE_IMG]'        => $HomeUrl . "/themes/$Theme/img",
                          '[SITE_JS]'         => $HomeUrl . "/themes/$Theme/js",
                          '[HOMEURL]'         => $HomeUrl,

                          '[CSS_VER]'         => CSS_VER,
                          '[GENERATOR]'       => GENERATOR . " (" . APP_VER . ")",
                          '[APP_NAME]'        => APP_NAME,
                          '[APP_VER]'         => APP_VER,
                          '[LANG_CD]'         => NoNull($this->settings['_language_code'], $this->strings['displang']),
                          '[YEAR]'            => date('Y'),

                          '[TARGET_URL]'      => NoNull($TargetUrl),
                          '[CHANNEL_GUID]'    => NoNull($data['channel_guid']),
                          '[SITE_GUID]'       => NoNull($data['site_guid']),

                          '[SITE_URL]'        => $this->settings['HomeURL'],
                          '[SITE_NAME]'       => NoNull($data['name'], $data['SiteName']),
                          '[SITEDESCR]'       => NoNull($data['description'], $data['SiteDescr']),
                          '[SITEKEYWD]'       => NoNull($data['keywords'], $data['SiteKeyword']),

                          '[ACCOUNT_TYPE]'    => NoNull($this->settings['_account_type'], 'account.guest'),
                          '[ACCOUNT_GUID]'    => NoNull($this->settings['_account_guid']),
                          '[AVATAR_URL]'      => NoNull($this->settings['_avatar_file']),
                          '[DISPLAY_NAME]'    => NoNull($this->settings['_display_name'], $this->settings['_first_name']),
                          '[TIMEZONE]'        => NoNull($this->settings['_timezone'], 'UTC'),
                          '[LANGUAGE]'        => str_replace('_', '-', NoNull($this->settings['_language_code'], DEFAULT_LANG)),

                          '[BODY_CLASSLIST]'  => strtolower(NoNull($BodyClass)),

                          '[CONTENT]'         => $this->_getPageContent(),
                         );

        /* Add the Language Strings */
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = str_replace(array_keys($langRepl), array_values($langRepl), NoNull($Value));
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
        $ReqPage = 'content-' . NoNull($this->settings['PgRoot'], 'main') . '.html';

        /* Confirm the Page Exists and Return the proper Resource path */
        if ( file_exists("$ResDIR/$ReqPage") === false ) { $ReqPage = 'content-404.html'; }
        return "$ResDIR/$ReqPage";
    }

    /** ********************************************************************* *
     *  Page Content Functions
     ** ********************************************************************* */
    /**
     *  Function returns a set number of recent posts for the current site, taking into account whether the
     *      visitor is signed in or not.
     */
    private function _getPageContent() {
        $LookupUrl = NoNull($this->settings['PgRoot']);
        if ( mb_strlen($LookupUrl) > 0 ) {
            for ( $i = 1; $i <= 9; $i++ ) {
                $sub = NoNull($this->settings['PgSub' . $i]);
                if ( mb_strlen($sub) > 0 ) { $LookupUrl .= '/' . $sub; }
            }
        }
        $CleanPage = nullInt($this->settings['page']);
        if ( $CleanPage > 0 ) { $CleanPage -= 1; }
        if ( $CleanPage < 0 ) { $CleanPage = 0; }

        /* Prep the output string */
        $html = '';

        /* Collect the Correct set of Posts */
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[SITE_ID]'    => nullInt($this->settings['_site_id'], $this->settings['site_id']),
                          '[LOOKUP]'     => sqlScrub($LookupUrl),
                          '[PAGE]'       => nullInt($CleanPage),
                         );
        $sqlStr = readResource(SQL_DIR . '/solar/getPostsForPage.sql', $ReplStr);
        if ( mb_strlen($LookupUrl) > 1 ) {
            $sqlStr = readResource(SQL_DIR . '/solar/getPostsByUrl.sql', $ReplStr);
        }
        $rslt = doSQLQuery($sqlStr);
        foreach ( $rslt as $Row ) {
            if ( YNBool($Row['is_visible']) ) {
                $pp = $this->_getPostByGuid( $Row['post_id'], $Row['post_version'] );
                if ( mb_strlen(NoNull($pp)) > 10 ) {
                    if ( mb_strlen($html) > 10 ) { $html .= "\r\n"; }
                    $html .= $pp;
                }
            }
        }

        /* If we have valid data, return it */
        if ( mb_strlen($html) > 10 ) { return $html; }

        /* If we have a lookup, but no content, then it's a 404 */
        if ( mb_strlen($LookupUrl) > 1 && mb_strlen($html) <= 0 ) { return $this->_getFlatPage('404'); }

        /* If we're here, something is messed up. Return a 401 equivalent */
        if ( mb_strlen($LookupUrl) > 1 && mb_strlen($html) <= 0 ) { return $this->_getFlatPage('400'); }
    }

    /**
     *  Function returns a single Post for the current site based on either the Post.canonical_url or the Post.guid value
     */
    private function _getPostById( $post_id = 0, $version = 0 ) {
        $version = nullInt($version);
        $post_id = nullInt($post_id);

        /* Do not continue with a bad Post.id */
        if ( $post_id <= 0 ) { return false; }

        /* Check the cache for the Post data */
        $CacheKey = 'post-' . paddNumber($post_id, 8) . '-' . paddNumber($version);
        $data = getCacheObject($CacheKey);
        if ( is_array($data) === false ) {

        }

        /* Construct the Post Item */
        if ( is_array($data) && mb_strlen(NoNull($data['guid'])) == 36 ) {
            $ReplStr = array( ''
                             );
        }

        /* If we're here, we do not have a valid post */
        return false;
    }

    /**
     *  Function returns a flattened page from a given template or an unhappy boolean
     */
    private function _getFlatPage( $PageSuffix = '404' ) {
        $HomeUrl = NoNull($this->settings['HomeURL']);
        $Theme = NoNull( substr(strrchr(__DIR__, '/'), 1) );

        $ReplStr = array( '[SITE_FONT]'       => $HomeUrl . "/themes/$Theme/fonts",
                          '[SITE_CSS]'        => $HomeUrl . "/themes/$Theme/css",
                          '[SITE_IMG]'        => $HomeUrl . "/themes/$Theme/img",
                          '[SITE_JS]'         => $HomeUrl . "/themes/$Theme/js",
                          '[HOMEURL]'         => $HomeUrl,

                          '[CSS_VER]'         => CSS_VER,
                          '[GENERATOR]'       => GENERATOR . " (" . APP_VER . ")",
                          '[APP_NAME]'        => APP_NAME,
                          '[APP_VER]'         => APP_VER,
                          '[LANG_CD]'         => NoNull($this->settings['_language_code'], $this->strings['displang']),
                          '[YEAR]'            => date('Y'),
                         );

        /* Add the Language Strings */
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = NoNull($Value);
        }

        /* Which Page Should be Returned? */
        $ReqFile = __DIR__ . '/resources/page-' . NoNull($PageSuffix) . '.html';
        if ( file_exists($ReqFile) ) { return readResource($ReqFile, $ReplStr); }
        return false;
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