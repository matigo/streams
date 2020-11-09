<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to manage Posts
 */
require_once(LIB_DIR . '/functions.php');

class Bookmark {
    var $settings;
    var $strings;

    function __construct( $settings, $strings = false ) {
        $this->settings = $settings;
        $this->strings = ((is_array($strings)) ? $strings : getLangDefaults($this->settings['_language_code']));
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    public function performAction() {
        $ReqType = NoNull(strtolower($this->settings['ReqType']));
        $rVal = false;

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) {
            $this->_setMetaMessage("You Need to Log In First", 401);
            return false;
        }

        // Perform the Action
        switch ( $ReqType ) {
            case 'get':
                $rVal = $this->_performGetAction();
                break;

            case 'post':
                $rVal = $this->_performPostAction();
                break;

            case 'delete':
                $rVal = $this->_performDeleteAction();
                break;

            default:
                // Do Nothing
        }

        // Return The Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performGetAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case 'check':
            case 'read':
            case '':
                $rVal = $this->_getPageSummary();
                break;

            default:

        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case '':
                $rVal = false;
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performDeleteAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case '':
                $rVal = false;
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

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

    /** ********************************************************************* *
     *  Public Functions
     ** ********************************************************************* */

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    private function _getPageSummary() {
        $ReplStr = array( '&#39;' => "'", '&gt;' => '>', '&lt;' => '<' );
        $SourceURL = NoNull($this->settings['source_url'], NoNull($this->settings['source-url'], NoNull($this->settings['source']), $thiis->settings['url']));
        if ( mb_strlen($SourceURL) <= 9 ) { $this->_setMetaMessage("Invalid URL Provided", 400); return false; }

        $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36';
        $TextLimit = 1200;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $SourceURL);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        curl_close($ch);

        $doc = new DOMDocument();
        @$doc->loadHTML($data);
        $nodes = $doc->getElementsByTagName('title');

        $PageTitle = false;
        $PageDescr = false;
        $PageImage = false;
        $PageText = false;
        $PageKeys = false;

        $metas = $doc->getElementsByTagName('meta');
        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);

            if ( in_array($meta->getAttribute('property'), array('title', 'og:title', 'twitter:title')) ) { $PageTitle = NoNull($meta->getAttribute('content')); }
            if ( $PageTitle === false && in_array($meta->getAttribute('title'), array('image', 'og:title', 'twitter:title')) ) { $PageTitle = NoNull($meta->getAttribute('content')); }

            if ( in_array($meta->getAttribute('property'), array('description', 'twitter:description', 'og:description')) ) { $PageDescr = str_replace(array_keys($ReplStr), array_values($ReplStr), html_entity_decode(NoNull($meta->getAttribute('content')))); }
            if ( $PageDescr === false && in_array($meta->getAttribute('name'), array('description', 'twitter:description', 'og:description')) ) { $PageDescr = str_replace(array_keys($ReplStr), array_values($ReplStr), html_entity_decode(NoNull($meta->getAttribute('content')))); }

            if ( in_array($meta->getAttribute('property'), array('image', 'og:image', 'twitter:image')) ) { $PageImage = NoNull($meta->getAttribute('content')); }
            if ( $PageImage === false && in_array($meta->getAttribute('name'), array('image', 'og:image', 'twitter:image')) ) { $PageImage = NoNull($meta->getAttribute('content')); }

            if ( $meta->getAttribute('property') == 'keywords' ) { $PageKeys = NoNull($meta->getAttribute('content')); }
            if ( $PageKeys === false && $meta->getAttribute('name') == 'keywords' ) { $PageKeys = NoNull($meta->getAttribute('content')); }
        }

        // If There Is No Title from Meta, Grab It From the Head
        if ( $PageTitle === false ) { $PageTitle = NoNull($nodes->item(0)->nodeValue); }

        // Get the Page Text
        $xpath = new DOMXPath($doc);
        $els = $xpath->query("//*[contains(@class, 'e-content')]");
        foreach($els as $key=>$value) {
            if ( NoNull($value->nodeValue) != '' && $PageText === false ) { $PageText = NoNull($value->nodeValue); }
        }

        // Is there a better Page Text value?
        $els = $doc->getElementsByTagName('p');
        $paragraphs = false;

        if ( $els->length > 0 ) {
            foreach ( $els as $pp ) {
                $ppText = NoNull($pp->nodeValue, $pp->textContent);

                // Now Clear the Rest
                $ppText = $this->_cleanText($ppText);
                if ( $ppText != '' ) {
                    if ( $paragraphs === false ) { $paragraphs = array(); }
                    $paragraphs[] = NoNull($ppText);
                }
            }
        }

        // Is there an audio file that can be included?
        $audios = $doc->getElementsByTagName('audio');
        $audioObj = false;

        if ( $audios->length > 0 ) {
            foreach ( $audios as $audio ) {
                if ( $audioObj === false ) {
                    $url = NoNull($audio->nodeValue, $audio->textContent);
                    $ext = getFileExtension($url);
                    $mime = getMimeFromExtension($ext);

                    if ( in_array($mime, array('audio/mp3', 'audio/m4a')) ) {
                        $audioObj = array( 'url' => NoNull($url),
                                           'mime' => $mime,
                                          );
                    }
                }
            }
        }

        // Return the Summary Data If We Have It
        if ( NoNull($data) != '' ) {
            $PageText = $this->_cleanText(strip_tags($PageText));
            $AltText = '';
            if ( is_array($paragraphs) ) {
                foreach ( $paragraphs as $pp ) {
                    if ( $AltText == '' && mb_strlen($pp) >= 100 && mb_strlen($pp) < $TextLimit && mb_strpos($pp, '©') === false ) { $AltText = $pp; }
                }
            }

            if ( $PageText == '' ) {
                foreach ( $paragraphs as $pp ) {
                    if ( $PageText == '' && mb_strlen($pp) > 50 ) { $PageText = $pp; }
                }
            }

            return array( 'title'      => $this->_cleanText(strip_tags($PageTitle)),
                          'summary'    => $this->_cleanText(strip_tags($PageDescr)),
                          'image'      => $PageImage,
                          'keywords'   => $this->_cleanText(strip_tags($PageKeys)),
                          'text'       => NoNull(((mb_strlen($PageText) >= $TextLimit && mb_strlen($AltText) > 20 && mb_strlen($AltText) < $TextLimit ) ? $AltText : $PageText), $PageText),
                          'audio'      => $audioObj,
                          'paragraphs' => $paragraphs,
                         );
        }

        // If We're Here, There Is No Summary (That We Know Of)
        return false;
    }

    private function _cleanText( $text ) {
        $inplace = array( '’' => "'", '‘' => "'", '“' => '"', '”' => '"', "\t" => ' ', "\r" => ' ', "\n" => ' ',
                          "â" => '–', "" => '–', "" => '', "" => '',
                         );
        for ( $i = 2; $i < 50; $i++ ) {
            $inplace[str_repeat(' ', $i)] = '';
        }
        $text = NoNull(str_replace(array_keys($inplace), array_values($inplace), $text));

        /* Now Ditch Inline Styling (if applicable) */
        $text = preg_replace('/(<[^>]*) style=("[^"]+"|\'[^\']+\')([^>]*>)/i', '$1$3', $text);

        /* Try to Handle Inline HTML */
        $text = NoNull(str_replace('<', '&lt;', $text));
        $ReplStr = array( '&lt;section' => '<section', '&lt;iframe' => '<iframe',
                          '&lt;str' => '<str', '&lt;del' => '<del', '&lt;pre' => '<pre',
                          '&lt;h1' => '<h1', '&lt;h2' => '<h2', '&lt;h3' => '<h3',
                          '&lt;h4' => '<h4', '&lt;h5' => '<h5', '&lt;h6' => '<h6',
                          '&lt;ol' => '<ol', '&lt;ul' => '<ul', '&lt;li' => '<li',
                          '&lt;b' => '<b', '&lt;i' => '<i', '&lt;u' => '<u',
                         );
        $text = NoNull(str_replace(array_keys($ReplStr), array_values($ReplStr), $text));

        // Now Clear and Return the Rest
        return NoNull($text);
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