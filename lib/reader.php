<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to Read XML or JSON-based Syndication feeds
 */
require_once(LIB_DIR . '/functions.php');

class Reader {
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
            case 'read':
            case 'get':
            case '':
                $rVal = $this->_getSyndicationFeed();
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
    private function _getNamespaceTags( $nsName ) {
        if ( NoNull($nsName) == '' ) { return false; }

        switch ( strtolower($nsName) ) {
            case 'atom':
                return array( 'atom:link' );
                break;

            case 'content':
                return array( 'content:encoded' );
                break;

            case 'dc':
                return array( 'dc:creator' );
                break;

            case 'itunes':
                return array( 'itunes:author', 'itunes:explicit', 'itunes:image', 'itunes:owner', 'itunes:name', 'itunes:email', 'itunes:subtitle', 'itunes:category' );
                break;

            case 'sy':
                return array( 'sy:updatePeriod', 'sy:updateFrequency', 'sy:updateBase' );
                break;

            default:
                /* Return the Default RSS Objects for Channels & Items, which is not 1:1 but has a lot of overlap */
                return array( 'title', 'category', 'copyright', 'description', 'enclosure', 'generator', 'image', 'language', 'lastBuildDate', 'link', 'managingEditor', 'pubDate', 'webMaster', 'ttl' );
        }

        return false;
    }

    private function _getArrayValsRecursive($array, $isSub = false) {
        $flat = array();

        foreach( $array as $key=>$value ) {
            if ( is_array($value) ) {
                $flat = array_merge($flat, $this->_getArrayValsRecursive($value, true));
            } else {
                $flat[strtolower($key)] = $value;
            }
        }

        // Return Either a Single Value, or the Array
        if ( count($flat) == 1 && $isSub === false ) {
            $flat = json_decode(json_encode($flat), true);
            foreach ( $flat as $key=>$val ) {
                return $val;
                break;
            }

        } else {
            if ( $isSub === false && count($flat) < 1 ) { return false; }
            return $flat;
        }
    }

    private function _getNamespaceTagValue( $value ) {
        $data = json_decode(json_encode($value), true);
        $rVal = $this->_getArrayValsRecursive($data);

        return $rVal;
    }

    /**
     *  Function Queries the Website for an Icon
     */
    private function _getFeedIcon( $SiteUrl ) {
        if ( mb_strlen(NoNull($SiteUrl)) <= 5 ) return '';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $SiteUrl);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        curl_close($ch);

        if ( mb_strlen(NoNull($data)) > 10 ) {
            preg_match('!<head.*?>.*</head>!ims', $data, $match);
            if (empty($match) || count($match) == 0) { return ''; }
            $head = NoNull($match[0]);
            $icns = array();

            $dom = new DOMDocument();
            if ( $dom->loadHTML($head) ) {
                $links = $dom->getElementsByTagName('link');
                foreach ( $links as $link ) {
                    if ( $link->hasAttribute('rel') && $href = $link->getAttribute('href') ) {
                        $attribute = $link->getAttribute('rel');

                        // Make sure the href is an absolute URL.
                        if ($href && filter_var($href, FILTER_VALIDATE_URL) === false) { $href = NoNull($SiteUrl . '/' . $href); }
                        $size = $link->hasAttribute('sizes') ? $link->getAttribute('sizes') : [];
                        $size = !is_array($size) ? explode('x', $size) : $size;
                        $type = false;

                        switch(strtolower($attribute)) {
                            case 'apple-touch-icon':
                                $type = 'apple-touch-icon';
                                break;

                            default:
                                if( strpos(strtolower($attribute), 'icon') !== false ) {
                                    $type = 'favicon';
                                    $size = [];
                                }
                        };

                        if( !empty($type) && filter_var($href, FILTER_VALIDATE_URL) ) {
                            $icns[] = array( 'type' => $type,
                                             'href' => $href,
                                             'size' => $size
                                            );
                        }
                    }
                }
            }

            // Return the Best-Sized Image
            if ( count($icns) > 0 ) {
                $src = '';
                $px = 0;

                foreach ( $icns as $ico ) {
                    if ( NoNull($ico['type']) == 'apple-touch-icon`' ) {
                        if ( is_array($ico['size']) && count($ico['size']) > 0 ) {
                            if ( nullInt($ico['size'][0]) > $px ) {
                                $src = NoNull($ico['href']);
                                $px = nullInt($ico['size'][0]);
                            }
                        }

                    } else {
                        if ( NoNull($src) == '' ) { $src = NoNull($ico['href']); }
                    }
                }

                // Return the Icon Location
                return $src;
            }
        }

        // If We're Here, an Icon Could Not be Determined
        return '';
    }

    private function _getSyndicationFeed() {
        $ReplStr = array( '&#39;' => "'", '&gt;' => '>', '&lt;' => '<' );
        $FeedURL = strtolower(NoNull($this->settings['source_url'], $this->settings['url']));
        if ( mb_strlen($FeedURL) <= 9 ) { $this->_setMetaMessage("Invalid URL Provided", 400); return false; }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $FeedURL);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        curl_close($ch);

        // Convert the XML Feed to an Array
        $xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
        $data = json_decode(json_encode($xml), true);

        // Parse the Feed
        if ( array_key_exists('channel', $data) ) {
            if ( is_array($data['channel']) ) {
                $feed = array();

                $tags = $this->_getNamespaceTags('channel');
                foreach ( $tags as $tag ) {
                    $value = NoNull($data['channel'][$tag]);
                    if ( NoNull($value) != '' ) {
                        $feed[$tag] = NoNull($value);
                    }
                }

                // Construct a GUID for the Site Based on an MD5 of the "clean" URL
                $FeedGuid = getGuidFromUrl($FeedURL);
                if ( $FeedGuid === false || strlen($FeedGuid) != 36 ) {
                    $this->_setMetaMessage("The Feed URL is Illogical: $FeedURL", 401);
                    return false;
                }
                $feed['guid'] = $FeedGuid;

                // Attempt to get an Icon
                $feed['icon'] = $this->_getFeedIcon($feed['link']);

                // Collect the Namespaces in the Document and fill in any gaps
                $nsList = $xml->getDocNamespaces(true);
                foreach ( $nsList as $ns=>$url ) {
                    $xml->registerXPathNamespace( $ns, $url );

                    $tags = $this->_getNamespaceTags($ns);
                    foreach ( $tags as $tag ) {
                        $rslt = $xml->xpath("//$tag");
                        $value = json_decode(json_encode($rslt), true);
                        if ( count($value) == 1 ) {
                            $value = $this->_getNamespaceTagValue($value);
                            if ( $value ) {
                                $feed[$tag] = $value;
                            }
                        }
                    }
                }
                $feed['hash'] = md5(json_encode($feed));
                $feed['items'] = array();

                $idx = 0;
                foreach ( $xml->xpath('//item') as $node) {
                    $item = array();

                    $tags = $this->_getNamespaceTags('channel');
                    foreach( $tags AS $tag ) {
                        $rslt = $node->xpath( $tag );
                        $value = $this->_getNamespaceTagValue($rslt);
                        if ( $value ) { $item[strtolower($tag)] = $value; }
                    }

                    foreach ( $nsList as $ns=>$url ) {
                        $tags = $this->_getNamespaceTags($ns);
                        foreach ( $tags as $tag ) {
                            $rslt = $node->xpath( $tag );
                            $value = $this->_getNamespaceTagValue($rslt);
                            if ( $value ) { $item[strtolower($tag)] = $value; }
                        }
                    }

                    // Clean Up the Item
                    if ( is_array($item['guid']) ) {
                        $item['ispermalink'] = BoolYN(YNBool($item['guid']['ispermalink']));
                        foreach ( $item['guid'] as $k=>$v) {
                            if ( is_array($item['guid']) ) {
                                if ( mb_strlen($v) > 5 ) { $item['guid'] = $v; }
                            }
                        }
                    }

                    // Determine the Content Hash
                    $item['hash'] = md5(json_encode($item));

                    // Determine the Publication Date's Validity (Intentionally after the Hash in the event of Blank)
                    if ( NoNull($item['pubdate']) != '' ) {
                        $pub_unix = strtotime($item['pubdate']);
                        if ( $pub_unix ) {
                            $item['published_at'] = date("Y-m-d H:i:s", $pub_unix);
                            $item['published_unix'] = $pub_unix;
                        }
                    }
                    if ( array_key_exists('published_at', $item) === false ) {
                        $pub_unix = time();
                        $item['published_at'] = date("Y-m-d H:i:s", $pub_unix);
                        $item['published_unix'] = $pub_unix;
                    }

                    // If We Have Data, Add It (Until a Maximum of 200 Items is hit)
                    if ( count($item) > 0 && count($feed['items']) < 200 ) { $feed['items'][] = $item; }
                }

                // Now that we have a Completed Feed object, it's time to record the feed info to the database
                $ReplStr = array( '[FEED_TITLE]'   => sqlScrub($feed['title']),
                                  '[FEED_DESCR]'   => sqlScrub($feed['description']),
                                  '[FEED_LINK]'    => sqlScrub($feed['link']),
                                  '[FEED_HASH]'    => sqlScrub($feed['hash']),
                                  '[FEED_GUID]'    => sqlScrub($feed['guid']),

                                  '[SQL_SPLITTER]' => SQL_SPLITTER,
                                 );
                $sqlStr = readResource(SQL_DIR . '/reader/setFeedHead.sql', $ReplStr);
                $rslt = doSQLExecute($sqlStr);

                // Parse the Items
                if ( is_array($feed['items']) && count($feed['items']) > 0 ) {
                    foreach ( $feed['items'] as $idx=>$item ) {
                        $text = $this->_checkSpecialText(NoNull($item['content:encoded'], $item['description']), $item['link']);
                        $srch = '';
                        if ( NoNull($text) != '' ) {
                            $uniques = UniqueWords($text);
                            if ( is_array($uniques) ) { $srch = implode(',', $uniques); }
                        }

                        // Construct a GUID for the Site Based on an MD5 of the "clean" URL
                        $ItemGuid = getGuidFromUrl($item['link']);

                        if ( $ItemGuid !== false && strlen($ItemGuid) == 36 ) {
                            $ReplStr = array( '[ITEM_TITLE]' => sqlScrub($item['title']),
                                              '[ITEM_DATE]'  => sqlScrub($item['published_at']),
                                              '[ITEM_GUID]'  => sqlScrub($ItemGuid),
                                              '[ITEM_HASH]'  => sqlScrub($item['hash']),
                                              '[ITEM_LINK]'  => sqlScrub($item['link']),
                                              '[ITEM_SRCH]'  => sqlScrub($srch),
                                              '[ITEM_TYPE]'  => sqlScrub(NoNull($item['type'], 'post.article')),
                                              '[ITEM_UNIX]'  => nullInt($item['published_unix']),

                                              '[FEED_GUID]'    => sqlScrub($feed['guid']),
                                              '[SQL_SPLITTER]' => SQL_SPLITTER,
                                             );
                            $sqlStr = readResource(SQL_DIR . '/reader/setFeedItem.sql', $ReplStr);
                            $isOK = doSQLExecute($sqlStr);
                        }
                    }
                }

                // Save the Feed Object
                saveFeedObject($feed);

                // If We're Here, Chances are Things are Good. Create a Report
                return array( 'feed'  => array( 'title' => $feed['title'],
                                                'guid'  => $feed['guid'],
                                                'url'   => $feed['link'],
                                               ),
                              'count' => count($feed['items']),
                             );
            }
        }

        // If We're Here, No Dice
        return $this->_setMetaMessage("Could Not Read $FeedURL", 401);
    }

    /**
     *  Function performs some "special" filtering of the text in the event the Site is worth the effort
     */
    private function _checkSpecialText( $text, $SiteUrl ) {
        $ReplStr = array( '’' => "'", '“' => '"', '”' => '"', '-' => ' ' );

        $parse = parse_url($SiteUrl);
        $host = strtolower(NoNull($parse['host']));

        switch ( $host ) {
            case 'xkcd.com':
                /* Get the Image Text and Append It to the Body */
                $doc = new DOMDocument();
                @$doc->loadHTML($text);

                $tags = $doc->getElementsByTagName('img');
                foreach ($tags as $tag) {
                    $ttl = NoNull($tag->getAttribute('title'), $tag->getAttribute('alt'));
                    if ( $ttl != '' ) { $text .= '<p>' . $ttl . '</p>'; }
                }
                break;

            default:
                /* Do Nothing */
        }

        // Return the Text
        return NoNull(str_replace(array_keys($ReplStr), array_values($ReplStr), $text));
    }

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