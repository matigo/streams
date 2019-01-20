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
        $PageURL = strtolower(NoNull($this->settings['source_url'], $this->settings['url']));
        if ( mb_strlen($PageURL) <= 9 ) { $this->_setMetaMessage("Invalid URL Provided", 400); return false; }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $PageURL);
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
            if ( $PageImage === false && in_array($meta->getAttribute('title'), array('image', 'og:title', 'twitter:title')) ) { $PageTitle = NoNull($meta->getAttribute('content')); }

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
            if ( NoNull($value->nodeValue) != '' ) { $PageText = NoNull($value->nodeValue); }
        }

        // Return the Summary Data If We Have It
        if ( NoNull($data) != '' ) {
            return array( 'title'    => $PageTitle,
                          'summary'  => $PageDescr,
                          'image'    => $PageImage,
                          'keywords' => $PageKeys,
                          'text'     => $PageText,
                         );
        }

        // If We're Here, There Is No Summary (That We Know Of)
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