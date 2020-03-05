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
        if ( !$this->settings['_logged_in'] ) {
            $isOK = isValidCronRequest($this->settings, array('reader/check') );
            if ( $isOK ) {
                $this->settings['_logged_in'] = true;
            } else {
                $this->_setMetaMessage("You Need to Log In First", 401);
                return false;
            }
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
                return $this->_checkFeedUpdates();
                break;

            case 'read':
            case 'get':
            case '':
                return $this->_getSyndicationFeed();
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
                return false;
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
                return false;
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
     *  Automated Functions
     ** ********************************************************************* */
    private function _checkFeedUpdates() {
        if ( !defined('SYND_INTERVAL') ) { define('SYND_INTERVAL', 60); }
        if ( !defined('SYND_LIMIT') ) { define('SYND_LIMIT', 10); }

        $ReplStr = array( '[INTERVAL]' => nullInt(SYND_INTERVAL, 60),
                          '[LIMIT]'    => nullInt(SYND_LIMIT, 10),
                         );
        $sqlStr = prepSQLQuery("CALL GetSyndicationUrlsToUpdate([INTERVAL], [LIMIT]);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();

            foreach ( $rslt as $Row ) {
                $url = NoNull($Row['feed_url']);
                $isOK = $this->_getSyndicationFeed($url);
                if ( is_array($isOK) ) { $data[] = $isOK; }
            }

            // If we have data, return it
            if ( count($data) ) { return $data; }
        }

        // If we're here, no feeds were updated
        $this->_setMetaMessage("No Syndication Feeds Require Updating", 204);
        return array();
    }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    private function _getNamespaceTags( $nsName ) {
        if ( NoNull($nsName) == '' ) { return false; }

        switch ( strtolower($nsName) ) {
            case 'alt':
                return array( 'id', 'content', 'title', 'link', 'updated' );
                break;

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

                // Clean the Url
                $ReplStr = array( '//' => '/', ':/' => '://' );
                for ( $i = 0; $i < 5; $i++ ) {
                    $src = str_replace(array_keys($ReplStr), array_values($ReplStr), $src);
                }

                // Return the Icon Location
                return $src;
            }
        }

        // If We're Here, an Icon Could Not be Determined
        return '';
    }

    private function _getSyndicationFeed( $feedUrl = '', $subscribers = 0 ) {
        $ReplStr = array( '&#39;' => "'", '&gt;' => '>', '&lt;' => '<' );
        $FeedURL = strtolower(NoNull($feedUrl, NoNull($this->settings['source_url'], $this->settings['url'])));
        if ( mb_strlen($FeedURL) <= 9 ) { $this->_setMetaMessage("Invalid URL Provided", 400); return false; }
        $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36';
        // $agent = APP_NAME . ' v' . APP_VER . '';
        if ( nullInt($subscribers) > 0 ) { $agent .= ' (' . $subscribers . ' subscribers)'; }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_URL, $FeedURL);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $data = curl_exec($ch);
        curl_close($ch);

        // Convert the XML Feed to an Array
        $xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
        $data = json_decode(json_encode($xml), true);

        // Parse the Feed
        if ( array_key_exists('channel', $data) || array_key_exists('entry', $data) ) {
            $feed = array();

            // Construct a GUID for the Site Based on an MD5 of the "clean" URL
            $FeedGuid = getGuidFromUrl($FeedURL);
            if ( $FeedGuid === false || strlen($FeedGuid) != 36 ) {
                $this->_setMetaMessage("The Feed URL is Illogical: $FeedURL", 401);
                return false;
            }
            $feed['guid'] = $FeedGuid;

            /* Atom-like Structure */
            if ( is_array($data['entry']) ) {
                $tags = $this->_getNamespaceTags('channel');
                foreach ( $tags as $tag ) {
                    $value = NoNull($data[$tag]);
                    switch ( $tag ) {
                        case 'link':
                            if ( is_array($data[$tag]['@attributes']) ) {
                                $value = $data[$tag]['@attributes']['href'];
                            }
                            break;

                        default:
                            /* Do Nothing */
                    }

                    if ( NoNull($value) != '' ) {
                        $feed[$tag] = NoNull($value);
                    }
                }

                // Attempt to get an Icon
                $feed['icon'] = $this->_getFeedIcon($feed['link']);
                $feed['hash'] = md5(json_encode($feed));
                $feed['items'] = array();

                /* Construct the Post Items */
                foreach ( $data['entry'] as $Key=>$entry) {
                    $item = array();

                    $tags = $this->_getNamespaceTags('alt');
                    foreach ( $tags as $tag ) {
                        $value = NoNull($entry[$tag]);
                        switch ( $tag ) {
                            case 'link':
                                if ( is_array($entry[$tag]['@attributes']) ) {
                                    $value = NoNull($entry[$tag]['@attributes']['href']);
                                }
                                break;

                            default:
                                /* Do Nothing */
                        }
                        if ( NoNull($value) != '' ) { $item[strtolower($tag)] = $value; }
                    }

                    if ( array_key_exists('content', $item) && array_key_exists('description', $item) === false ) {
                        $item['description'] = NoNull($item['content']);
                        unset($item['content']);
                    }

                    // Determine the Content Hash
                    $item['hash'] = md5(json_encode($item));

                    // Determine the Publication Date's Validity (Intentionally after the Hash in the event of Blank)
                    if ( NoNull($item['pubdate'], $item['updated']) != '' ) {
                        $pub_unix = strtotime(NoNull($item['pubdate'], $item['updated']));
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
            }

            /* XML-like Structure */
            if ( is_array($data['channel']) ) {
                $tags = $this->_getNamespaceTags('channel');
                foreach ( $tags as $tag ) {
                    $value = NoNull($data['channel'][$tag]);
                    if ( NoNull($value) != '' ) {
                        $feed[$tag] = NoNull($value);
                    }
                }

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
            }

            // Now that we have a Completed Feed object, it's time to record the feed info to the database
            $strips = array( "\r" => '', "\n" => '', "\t" => '' );
            $FeedID = 0;
            $ReplStr = array( '[FEED_TITLE]'   => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['title'])),
                              '[FEED_DESCR]'   => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['description'])),
                              '[FEED_LINK]'    => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['link'])),
                              '[FEED_URL]'     => sqlScrub($FeedURL),

                              '[FEED_LANG]'    => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['language'])),
                              '[FEED_ICON]'    => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['icon'])),
                              '[FEED_GEN]'     => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['generator'])),

                              '[CAST_IMAGE]'   => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['itunes:image'])),
                              '[CAST_DESCR]'   => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['itunes:subtitle'])),
                              '[CAST_EXPLT]'   => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['itunes:explicit'])),

                              '[UPD_PERIOD]'   => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['sy:updatePeriod'])),
                              '[UPD_FREQ]'     => sqlScrub(str_replace(array_keys($strips), array_values($strips), $feed['sy:updateFrequency'])),

                              '[FEED_GUID]'    => sqlScrub($feed['guid']),
                              '[FEED_HASH]'    => sqlScrub($feed['hash']),
                             );
            $sqlStr = prepSQLQuery( "CALL SetSyndicationHeader('[FEED_TITLE]', '[FEED_DESCR]', '[FEED_LINK]', '[FEED_URL]', " .
                                                              "'[FEED_LANG]', '[FEED_ICON]', '[FEED_GUID]', '[FEED_HASH]', '[FEED_GEN]', " .
                                                              "'[UPD_PERIOD]', '[UPD_FREQ]', '[CAST_IMAGE]', '[CAST_DESCR]', '[CAST_EXPLT]' );", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            foreach ( $rslt as $Row ) {
                $FeedID = nullInt($Row['feed_id']);
            }

            // Parse the Items
            if ( is_array($feed['items']) && count($feed['items']) > 0 ) {
                foreach ( $feed['items'] as $idx=>$item ) {
                    $item['content:encoded'] = $this->_checkForAltText(NoNull($item['content:encoded'], $item['description']), $item['link']);
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
                                          '[ITEM_TEXT]'  => sqlScrub(NoNull($item['content:encoded'], $item['description'])),
                                          '[ITEM_DATE]'  => sqlScrub($item['published_at']),
                                          '[ITEM_UNIX]'  => nullInt($item['published_unix']),
                                          '[ITEM_HASH]'  => sqlScrub($item['hash']),
                                          '[ITEM_LINK]'  => sqlScrub($item['link']),
                                          '[ITEM_GUID]'  => sqlScrub($ItemGuid),
                                          '[ITEM_SRCH]'  => sqlScrub($srch),

                                          '[FEED_ID]'    => nullInt($FeedID),
                                         );
                        $sqlStr = prepSQLQuery("CALL SetSyndicationItem( [FEED_ID], '[ITEM_TITLE]', '[ITEM_TEXT]', '[ITEM_LINK]', '[ITEM_DATE]', '[ITEM_GUID]', '[ITEM_HASH]', '[ITEM_SRCH]' );", $ReplStr);
                        $isOK = doSQLQuery($sqlStr);
                    }
                }
            }

            // If We're Here, Chances are Things are Good. Create a Report
            return array( 'feed'  => array( 'title' => $feed['title'],
                                            'guid'  => $feed['guid'],
                                            'url'   => $feed['link'],
                                           ),
                          'count' => count($feed['items']),
                         );
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
            case 'www.fowllanguagecomics.com':
            case 'fowllanguagecomics.com':
            case 'feed.dilbert.com':
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

    /**
     *  Function performs some "special" checking to see if content needs to be grabbed from the source
     *      URL rather than the syndication feed
     */
    private function _checkForAltText( $text, $SiteUrl ) {
        $agent = APP_NAME . ' v' . APP_VER . '';
        $parse = parse_url($SiteUrl);
        $host = strtolower(NoNull($parse['host']));

        switch ( $host ) {
            case 'www.fowllanguagecomics.com':
            case 'fowllanguagecomics.com':
            case 'feed.dilbert.com':
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, $agent);
                curl_setopt($ch, CURLOPT_URL, $SiteUrl);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                $data = curl_exec($ch);
                curl_close($ch);

                if ( mb_strlen(NoNull($data)) > 10 ) {
                    preg_match('!<head.*?>.*</head>!ims', $data, $match);
                    if (empty($match) || count($match) == 0) { return ''; }
                    $head = NoNull($match[0]);
                    $attr = array();

                    $dom = new DOMDocument();
                    if ( $dom->loadHTML($head) ) {
                        $metas = $dom->getElementsByTagName('meta');
                        foreach ( $metas as $meta ) {
                            $content = NoNull($meta->getAttribute('content'));
                            $prop = NoNull($meta->getAttribute('property'));
                            $name = NoNull($meta->getAttribute('name'));

                            $attr[NoNull($name, $prop)] = $content;
                        }
                    }

                    // Let's Return a formatted HTML Object
                    $title = (NoNull($attr['og:title'], $attr['twitter:title']) != '') ? ' title="' . NoNull($attr['og:title'], $attr['twitter:title']) . '"' : '';
                    $alt = (NoNull($attr['og:description'], $attr['twitter:description']) != '') ? ' alt="' . NoNull($attr['og:description'], $attr['twitter:description']) . '"' : '';
                    $text = '<p><img src="' . NoNull($attr['og:image'], $attr['twitter:image']) . '"' . $alt . $title . '></p>';
                }
                break;

            case 'fborfw.com':
                $SiteUrl = NoNull($parse['scheme'], 'https') . '://' . $host;
                $strips = array( '<div class="lynncomments">' => '<p>', '</div>' => '</p>',
                                 'src="/strip_fix' => 'src="' . $SiteUrl . '/strip_fix'
                                );
                for ( $i = 0; $i < 5; $i++ ) {
                    $text = str_replace(array_keys($strips), array_values($strips), $text);
                }
                break;

            default:
                /* Do Nothing */
        }

        // If we're here, return the Text received
        return $text;
    }

    private function _getPageSummary() {
        $agent = APP_NAME . ' v' . APP_VER . '';
        $ReplStr = array( '&#39;' => "'", '&gt;' => '>', '&lt;' => '<' );
        $PageURL = strtolower(NoNull($this->settings['source_url'], $this->settings['url']));
        if ( mb_strlen($PageURL) <= 9 ) { $this->_setMetaMessage("Invalid URL Provided", 400); return false; }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
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