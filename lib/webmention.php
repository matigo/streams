<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to manage Geocoding
 */
require_once(LIB_DIR . '/functions.php');

class Webmention {
    var $settings;
    var $strings;
    var $cache;

    function __construct( $settings, $strings = false ) {
        $this->settings = $settings;
        $this->strings = ((is_array($strings)) ? $strings : getLangDefaults($this->settings['_language_code']));
        $this->cache = array();
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    public function performAction() {
        $ReqType = NoNull(strtolower($this->settings['ReqType']));
        $rVal = false;

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

        switch ( $Activity ) {
            case 'test':
                $SourceURL = NoNull($this->settings['url']);
                $BodyText = NoNull($this->settings['text']);
                return $this->sendMentions($SourceURL, $BodyText);
                break;

            case '':
                $data = json_encode($this->settings);
                writeNote( "Webmention Received! [GET]", true );
                writeNote( $data, true );
                return array();
                break;

            default:

        }

        // Return the Array of Data or an Unhappy Boolean
        return false;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));

        switch ( $Activity ) {
            default:
                $data = json_encode($this->settings);
                writeNote( "Webmention Received! [POST]", true );
                writeNote( $data, true );
                return array();
        }

        // Return the Array of Data or an Unhappy Boolean
        return false;
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
    /**
     * Function Collects an Array of URLs from an HTML body and attempts to notify those
     *      sites of a Mention via Webmention or Pingback. Internal sites have records
     *      auto-recorded to the database without the unnecessary HTTP traffic.
     */
    public function sendMentions( $SourceURL, $SourceBody = '' ) {
        if ( mb_strlen(NoNull($SourceBody)) <= 10 ) { return false; }
        if ( mb_strlen(NoNull($SourceURL)) <= 10 ) { return false; }

        // Extract Any Links from the Source Body
        $links = $this->_getLinkArray( $SourceBody );
        if ( is_array($links) && count($links) > 0 ) {
            $this->cache['html'] = $SourceBody;
            $this->_getInternalUrls();
            $rsp = array( 'webmention'  => false,
                          'pingback'    => false,
                          'internal'    => false,
                         );

            foreach ( $links as $url ) {
                $url = str_replace('https://', '', $url);
                $url = str_replace('http://', '', $url);

                // Is the Link Internal or External?
                $isInternal = $this->_checkIsInternalUrl($url);
                $isSent = false;

                // If this is going to an Internal Site, Simply Record the Data
                if ( $isInternal ) {

                } else {
                    // Notify the Externally-Hosted Site
                    $endpoints = $this->_getHeaders( $url );

                    // If Webmentions are Possible, This Is Ideal
                    if ( $isSent === false && is_string($endpoints['webmention']) && mb_strlen($endpoints['webmention']) > 10 ) {
                        $head = array( 'Content-type: application/x-www-form-urlencoded',
                                       'Accept: application/json, */*;q=0.8'
                                      );
                        $data = array( 'source' => $SourceURL,
                                       'target' => $url,
                                      );

                        // Send the POST Request
                        $rslt = $this->_sendPostRequest( $endpoints['webmention'], http_build_query($data), $head );

                        // If the Code is Successful, It's Good!
                        if ( is_array($rslt) && $rslt['code'] >= 200 && $rslt['code'] <= 210 ) {
                            if ( is_array($rsp['webmention']) === false ) { $rsp['webmention'] = array(); }
                            $rsp['webmention'][] = $url;
                            $isSent = true;
                        }
                    }

                    // If Webmentions are Not Possible but Pingbacks Are
                    if ( $isSent === false && is_string($endpoints['pingback']) && mb_strlen($endpoints['pingback']) > 10 ) {
                        $head = array( 'Content-type: application/xml' );
                        $ReplStr = array( '[SOURCE_URL]' => htmlspecialchars($SourceURL),
                                          '[TARGET_URL]' => htmlspecialchars($url)
                                         );
                        $data = readResource(FLATS_DIR . '/templates/pingback.ping.xml', $ReplStr);

                        // Send the POST Request
                        $rslt = $this->_sendPostRequest( $endpoints['pingback'], $data, $head );

                        // If the Code is Successful, It's Good!
                        if ( is_array($rslt) && $rslt['code'] >= 200 && $rslt['code'] <= 210 ) {
                            if ( is_array($rsp['pingback']) === false ) { $rsp['pingback'] = array(); }
                            $rsp['pingback'][] = $url;
                            $isSent = true;
                        }
                    }
                }
            }

            // Return the Array of Results
            return $rsp;
        }

        // If We're Here, There Were No External Webmentions to Send
        return array();
    }

    /**
     *  Function Sends a Post Request and Returns a Simple Array of Data
     */
    private function _sendPostRequest( $RemoteURL, $PostData, $PostHead = false ) {
        if ( mb_strlen(NoNull($RemoteURL)) <= 10 ) { return false; }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, is_array($PostHead));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ( is_array($PostHead) ) { curl_setopt($ch, CURLOPT_HTTPHEADER, $PostHead); }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $PostData);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $RemoteURL);

        $rslt = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        // Return an Array of Results
        return array( 'code' => $code,
                      'rslt' => $rslt,
                      'size' => $size
                     );
    }

    /**
     *  Function Extracts any Links from the supplied HTML that are wrapped in <a> tags
     */
    private function _getLinkArray( $html ) {
        if( is_string($html) && mb_strlen($html) > 10 ) {
            preg_match_all("/<a[^>]+href=.(https?:\/\/[^'\"]+)/i", $html, $matches);
            return array_unique($matches[1]);
        }

        // If We're Here, There Are No Links
        return false;
    }

    /**
     *  Function Populates the Cache with a List of Local URLs and their Current Locations
     */
    private function _getInternalUrls() {
        if ( array_key_exists('internals', $this->cache) && is_array($this->cache['internals']) ) { return; }
        $this->cache['internals'] = false;

        $sqlStr = readResource(SQL_DIR . '/webmention/getInternalUrls.sql');
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();
            foreach ( $rslt as $Row ) {
                $data[ NoNull($Row['orig_url']) ] = NoNull($Row['live_url']);
            }

            // Write the Data to the Cache
            if ( count($data) > 0 ) { $this->cache['internals'] = $data; }
        }
    }

    private function _checkIsInternalUrl( $url ) {
        if ( array_key_exists('internals', $this->cache) && is_array($this->cache['internals']) ) {
            foreach ( $this->cache['internals'] as $Key=>$Location ) {
                if ( strtolower($Key) == strtolower($url) ) { return true; }
            }
        }
        return false;
    }

    /**
     * Function Collects the HTTP Headers from the Target URL and Returns an Array of Data or
     *      an unhappy boolean.
     */
    private function _getHeaders( $RemoteURL ) {
        if ( is_string($RemoteURL) && mb_strlen($RemoteURL) > 10 ) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $RemoteURL);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $rslt = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ( $code >= 200 && $code <= 210 ) {
                $doc = new DOMDocument();
                @$doc->loadHTML($rslt);
                $links = $doc->getElementsByTagName('link');
                $data = array( 'webmention' => false,
                               'pingback'   => false,
                              );

                for ($i = 0; $i < $links->length; $i++) {
                    $link = $links->item($i);

                    if ( in_array($link->getAttribute('rel'), array('http://webmention.org/', 'webmention')) ) { $data['webmention'] = NoNull($link->getAttribute('href')); }
                    if ( in_array($link->getAttribute('rel'), array('pingback')) ) { $data['pingback'] = NoNull($link->getAttribute('href')); }
                }

                // Return the Data
                return $data;
            }
        }

        // If We're Here, Nothing is Supported
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