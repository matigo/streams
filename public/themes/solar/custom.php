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

        /* If the page does not exist, check to see if it's in the database */
        if ( file_exists("$ResDIR/$ReqPage") === false ) {
            $ReqPage = 'content-main.html';
        }

        /* Confirm the Page Exists and Return the proper Resource path */
        if ( file_exists("$ResDIR/$ReqPage") === false ) { $ReqPage = 'content-main.html'; }
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
        $CleanCount = nullInt($this->settings['count']);
        if ( $CleanCount > 25 ) { $CleanCount = 25; }
        if ( $CleanCount < 1 ) { $CleanCount = 10; }
        if ( mb_strlen($LookupUrl) > 0 ) { $CleanCount = 1; }

        $CleanPage = nullInt($this->settings['page']);
        if ( $CleanPage > 0 ) { $CleanPage -= 1; }
        if ( $CleanPage < 0 ) { $CleanPage = 0; }

        /* Prep the output string */
        $html = '';

        /* Collect the Correct set of Posts */
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[SITE_ID]'    => nullInt($this->settings['_site_id'], $this->settings['site_id']),
                          '[LOOKUP]'     => sqlScrub($LookupUrl),
                          '[COUNT]'      => nullInt($CleanCount),
                          '[PAGE]'       => nullInt($CleanPage * $CleanCount),
                         );
        $sqlStr = readResource(SQL_DIR . '/solar/getPostsForPage.sql', $ReplStr);
        if ( mb_strlen($LookupUrl) > 1 ) {
            $sqlStr = readResource(SQL_DIR . '/solar/getPostsByUrl.sql', $ReplStr);
        }

        /* Do we have the current data cached? */
        $CacheKey = 'site-' . paddNumber(nullInt($this->settings['_site_id'], $this->settings['site_id']), 8) . '-page-' . md5($sqlStr);
        $rslt = getCacheObject($CacheKey, 60);
        if ( is_array($rslt) === false || count($rslt) <= 0 ) {
            $rslt = doSQLQuery($sqlStr);

            /* Save the cached data (if exists) */
            if ( is_array($rslt) && count($rslt) > 0 ) { setCacheObject($CacheKey, $rslt, 60); }
        }

        /* Now let's process the data */
        if ( is_array($rslt) && count($rslt) > 0 ) {
            foreach ( $rslt as $Row ) {
                if ( YNBool($Row['is_visible']) ) {
                    $pp = $this->_getPostById( $Row['post_id'], $Row['post_version'] );
                    if ( mb_strlen(NoNull($pp)) > 10 ) {
                        if ( mb_strlen($html) > 10 ) { $html .= "\r\n"; }
                        $html .= $pp;
                    }
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

        /* Do we have the data already? */
        $data = getCacheObject($CacheKey);
        if ( is_array($data) === false || mb_strlen(NoNull($data['post_guid'])) != 36 ) {
            $ReplStr = array( '[POST_ID]' => nullInt($post_id) );
            $sqlStr = readResource(SQL_DIR . '/solar/getPostById.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $version = nullInt($Row['post_version']);
                    $post_id = nullInt($Row['post_id']);
                    $data = $Row;

                    $data['html'] = $this->_getMarkdownHTML($Row['text']);
                }

                /* If the data is valid, let's save it */
                if ( mb_strlen(NoNull($data['post_guid'])) == 36 ) {
                    $CacheKey = 'post-' . paddNumber($post_id, 8) . '-' . paddNumber($version);
                    setCacheObject($CacheKey, $data);
                }
            }

        }

        /* Construct the Post Item */
        if ( is_array($data) && mb_strlen(NoNull($data['post_guid'])) == 36 ) {
            $HomeUrl = NoNull($this->settings['HomeURL']);
            $cdnUrl = getCdnUrl();

            $canEdit = false;
            if ( nullInt($data['account_id']) == nullInt($this->settings['_account_id']) ) {
                $canEdit = true;
            }



            /* Do we have tags? */
            $tagLine = '';
            if ( mb_strlen(NoNull($data['post_tags'])) > 0 ) {
                $tags = explode(',', $data['post_tags'] . ',');
                foreach ( $tags as $tag ) {
                    $tag = NoNull($tag);
                    if ( mb_strlen($tag) >= 1 ) {
                        $tagLine .= '<li class="post-tag" data-tag="' . strtolower($tag) . '">' . $tag . '</li>';
                    }
                }
            }

            /* Do we have web mentions? */
            $WebMentions = '';
            if ( array_key_exists('web_mentions', $data) && is_array($data['web_mentions']) ) {
                $resFile = THEME_DIR . '/' . $data['location'] . '/flats/meta.webmention.html';
                $WebMentions = tabSpace(6) . '<h4 class="webmention-header">' . NoNull($this->strings['lblWebMentions'], "WebMentions") . '</h4>' . "\r\n";
                foreach ( $post['web_mentions'] as $webm ) {
                    $dtls = array( '[SOURCE_URL]'  => NoNull($webm['url']),
                                   '[AVATAR_URL]'  => NoNull($webm['avatar_url']),
                                   '[COMMENT]'     => NoNull($webm['comment']),
                                   '[AUTHOR]'      => NoNull($webm['author']),
                                   '[CREATE_AT]'   => NoNull($webm['created_at']),
                                   '[CREATE_UNIX]' => NoNull($webm['created_unix']),
                                   '[UPDATE_AT]'   => NoNull($webm['updated_at']),
                                   '[UPDATE_UNIX]' => NoNull($webm['updated_unix']),
                                  );
                    $WebMentions .= readResource($resFile, $dtls);
                }
            }

            /* Construct the main replacement array */
            $ReplStr = array( '[POST_GUID]'      => NoNull($data['post_guid']),
                              '[POST_TYPE]'      => NoNull($data['post_type']),
                              '[AUTHOR_GUID]'    => NoNull($data['persona_guid']),
                              '[AUTHOR_NAME]'    => NoNull($data['display_name'], $data['persona_name']),
                              '[AUTHOR_PROFILE]' => $HomeUrl . '/profile',
                              '[AUTHOR_AVATAR]'  => $HomeUrl . '/avatars/' . NoNull($data['avatar_img'], 'default.png'),

                              '[TITLE]'          => NoNull($data['title']),
                              '[CONTENT]'        => NoNull($data['html']),
                              '[SOURCE_TEXT]'    => NoNull($data['text']),

                              '[TAGLINE]'       => NoNull($tagLine),
                              '[HOMEURL]'       => NoNull($this->settings['HomeURL']),
                              '[GEOTAG]'        => '',
                              '[AUDIO]'         => '',
                              '[THREAD]'        => '',
                              '[WEBMENTIONS]'   => $WebMentions,
                              '[PUBLISH_AT]'    => NoNull($data['publish_at']),
                              '[PUBLISH_UNIX]'  => nullInt($data['publish_unix']),
                              '[UPDATED_AT]'    => NoNull($data['updated_at']),
                              '[UPDATED_UNIX]'  => nullInt($data['updated_unix']),
                              '[CANONICAL]'     => NoNull($data['canonical_url']),
                              '[POST_SLUG]'     => NoNull($data['slug']),
                              '[REPLY_TO]'      => NoNull($data['reply_to']),
                              '[CAN_EDIT]'      => BoolYN($canEdit),
                             );

            /* Determine the template and, if it exists, return HTML */
            $ReqFile = FLATS_DIR . '/templates/' . strtolower(NoNull($data['post_type'])) . '.html';
            if ( file_exists($ReqFile) ) { return readResource($ReqFile, $ReplStr); }
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
     *  Markdown Formatting Functions
     ** ********************************************************************* */
    /**
     *  Function Converts a Text String to HTML Via Markdown
     */
    private function _getMarkdownHTML( $text, $isNote = false, $showLinkURL = false ) {
        $illegals = array( '<' => '&lt;', '>' => '&gt;' );
        $Excludes = array("\r", "\n", "\t");
        $ValidateUrls = false;
        if ( defined('VALIDATE_URLS') ) { $ValidateUrls = YNBool(VALIDATE_URLS); }

        // Fix the Lines with Breaks Where Appropriate
        $text = str_replace("\r", "\n", $text);
        $lines = explode("\n", $text);
        $inCodeBlock = false;
        $inTable = false;
        $fixed = '';
        $last = '';

        foreach ( $lines as $line ) {
            $thisLine = NoNull($line);
            if ( mb_strpos($thisLine, '```') !== false ) { $inCodeBlock = !$inCodeBlock; }
            if ( $inCodeBlock ) { $thisLine = $line; }
            $doBR = ( $fixed != '' && $last != '' && $thisLine != '' ) ? true : false;

            // Are we working with a table?
            if ( mb_strpos($thisLine, '--') !== false && mb_strpos($thisLine, '|') !== false ) { $inTable = true; }
            if ( NoNull($thisLine) == '' ) { $inTable = false; }

            // If We Have What Looks Like a List, Prep It Accordingly
            if ( nullInt(mb_substr($thisLine, 0, 2)) > 0 && nullInt(mb_substr($last, 0, 2)) > 0 ) { $doBR = false; }
            if ( mb_substr($thisLine, 0, 2) == '* ' && mb_substr($last, 0, 2) == '* ' ) { $doBR = false; }
            if ( mb_substr($thisLine, 0, 2) == '- ' && mb_substr($last, 0, 2) == '- ' ) { $doBR = false; }

            if ( mb_substr($thisLine, 0, 2) == '* ' && mb_substr($last, 0, 2) != '* ' && strlen($last) > 0 ) {
                $fixed .= "\n";
                $doBR = false;
            }
            if ( mb_substr($thisLine, 0, 2) == '- ' && mb_substr($last, 0, 2) != '- ' && strlen($last) > 0 ) {
                $fixed .= "\n";
                $doBR = false;
            }

            if ( nullInt(mb_substr($thisLine, 0, 2)) > 0 && $last == '' ) { $fixed .= "\n"; }
            if ( mb_substr($thisLine, 0, 2) == '* ' && $last == '' ) { $fixed .= "\n"; }
            if ( mb_substr($thisLine, 0, 2) == '- ' && $last == '' ) { $fixed .= "\n"; }
            if ( $inCodeBlock || mb_strpos($thisLine, '```') !== false ) { $doBR = false; }
            if ( $inTable ) { $doBR = false; }

            $fixed .= ( $doBR ) ? '<br>' : "\n";
            $fixed .= ( $inCodeBlock ) ? str_replace(array_keys($illegals), array_values($illegals), $line) : $thisLine;
            $last = NoNull($thisLine);
        }
        $text = NoNull($fixed);

        // Handle the Footnotes
        $fnotes = '';
        if ( strpos($text, '[') > 0 ) {
            $notes = array();
            $pass = 0;

            while ( $pass < 100 ) {
                $inBracket = false;
                $btxt = '';
                $bidx = '';
                $bid = 0;
                for ( $i = 0; $i < strlen($text); $i++ ) {
                    if ( substr($text, $i, 1) == "[" ) {
                        $bracketValid = false;
                        if ( strpos(substr($text, $i, 6), '. ') > 0 ) { $bracketValid = true; }
                        if ( $bracketValid || $inBracket ) {
                            $inBracket = true;
                            $bid++;
                        }
                    }
                    if ( $inBracket ) { $btxt .= substr($text, $i, 1); }
                    if ( $inBracket && substr($text, $i, 1) == "]" ) {
                        $bid--;
                        if ( $bid <= 0 ) {
                            $n = count($notes) + 1;
                            $ntxt = substr($btxt, strpos($btxt, '. ') + 2);
                            $ntxt = substr($ntxt, 0, strlen($ntxt) - 1);
                            if ( NoNull($ntxt) != '' ) {
                                $text = str_replace($btxt, "<sup>$n</sup>", $text);
                                $notes[] = NoNull($ntxt);
                                $btxt = '';
                                break;
                            }
                        }
                    }
                }
                $pass++;
            }

            if ( count($notes) > 0 ) {
                foreach ( $notes as $note ) {
                    $fnotes .= "<li class=\"footnote\">" . Markdown::defaultTransform($note, $isNote) . "</li>";
                }
            }
        }

        // Handle Code Blocks
        if (preg_match_all('/\```(.+?)\```/s', $text, $matches)) {
            foreach($matches[0] as $fn) {
                $cbRepl = array( '```' => '', '<code><br>' => "<code>", '<br></code>' => '</code>', "\n" => '<br>', ' ' => "&nbsp;" );
                $code = "<pre><code>" . str_replace(array_keys($cbRepl), array_values($cbRepl), $fn) . "</code></pre>";
                $code = str_replace(array_keys($cbRepl), array_values($cbRepl), $code);
                $text = str_replace($fn, $code, $text);
            }
        }

        // Handle Strikethroughs
        if (preg_match_all('/\~~(.+?)\~~/s', $text, $matches)) {
            foreach($matches[0] as $fn) {
                $stRepl = array( '~~' => '' );
                $code = "<del>" . NoNull(str_replace(array_keys($stRepl), array_values($stRepl), $fn)) . "</del>";
                $text = str_replace($fn, $code, $text);
            }
        }

        // Get the Markdown Formatted
        $text = str_replace('\\', '&#92;', $text);
        $rVal = Markdown::defaultTransform($text, $isNote);
        for ( $i = 0; $i <= 5; $i++ ) {
            foreach ( $Excludes as $Item ) {
                $rVal = str_replace($Item, '', $rVal);
            }
        }

        // Replace any Hashtags if they exist
        $rVal = str_replace('</p>', '</p> ', $rVal);
        $words = explode(' ', " $rVal ");
        $out_str = '';
        foreach ( $words as $word ) {
            $clean_word = NoNull(strip_tags($word));
            $hash = '';

            if ( NoNull(substr($clean_word, 0, 1)) == '#' ) {
                $hash_scrub = array('#', '?', '.', ',', '!');
                $hash = NoNull(str_replace($hash_scrub, '', $clean_word));

                if ($hash != '' && mb_stripos($hash_list, $hash) === false ) {
                    if ( $hash_list != '' ) { $hash_list .= ','; }
                    $hash_list .= strtolower($hash);
                }
            }
            $out_str .= ($hash != '') ? str_ireplace($clean_word, '<span class="hash" data-hash="' . strtolower($hash) . '">' . NoNull($clean_word) . '</span> ', $word)
                                      : "$word ";
        }
        $rVal = NoNull($out_str);

        // Format the URLs as Required
        $url_pattern = '#(www\.|https?://)?[a-z0-9]+\.[a-z0-9]\S*#i';
        $fixes = array( 'http//'  => "http://",         'http://http://'   => 'http://',
                        'https//' => "https://",        'https://https://' => 'https://',
                        ','       => '',                'http://https://'  => 'https://',
                       );
        $splits = array( '</p><p>' => '</p> <p>', '<br>' => '<br> ' );
        $scrub = array('#', '?', '.', ':', ';');
        $words = explode(' ', ' ' . str_replace(array_keys($splits), array_values($splits), $rVal) . ' ');

        $out_str = '';
        foreach ( $words as $word ) {
            // Do We Have an Unparsed URL?
            if ( mb_strpos($word, '.') !== false && mb_strpos($word, '.') <= (mb_strlen($word) - 1) && NoNull(str_ireplace('.', '', $word)) != '' &&
                 mb_strpos($word, '[') === false && mb_strpos($word, ']') === false ) {
                $clean_word = str_replace("\n", '', strip_tags($word));
                if ( in_array(substr($clean_word, -1), $scrub) ) { $clean_word = substr($clean_word, 0, -1); }

                $url = ((stripos($clean_word, 'http') === false ) ? "http://" : '') . $clean_word;
                $url = str_ireplace(array_keys($fixes), array_values($fixes), $url);
                $headers = false;

                // Ensure We Have a Valid URL Here
                $hdParts = explode('.', $url);
                $hdCount = 0;

                // Count How Many Parts We Have
                if ( is_array($hdParts) ) {
                    foreach( $hdParts as $item ) {
                        if ( NoNull($item) != '' ) { $hdCount++; }
                    }
                }

                // No URL Has Just One Element
                if ( $hdCount > 0 ) {
                    if ( $ValidateUrls ) {
                        if ( $hdCount > 1 ) { $headers = get_headers($url); }
                        if ( is_array($headers) ) {
                            $okHead = array('HTTPS/1.0 200 OK', 'HTTPS/1.1 200 OK', 'HTTPS/2.0 200 OK',
                                            'HTTP/1.0 200 OK', 'HTTP/1.1 200 OK', 'HTTP/2.0 200 OK');
                            $suffix = '';
                            $rURL = $url;

                            // Do We Have a Redirect?
                            if ( count($headers) > 0 ) {
                                foreach ($headers as $Row) {
                                    if ( mb_strpos(strtolower($Row), 'location') !== false ) {
                                        $rURL = NoNull(str_ireplace('location:', '', strtolower($Row)));
                                        break;
                                    }
                                    if ( in_array(NoNull(strtoupper($Row)), $okHead) ) { break; }
                                }
                            }

                            $host = parse_url($rURL, PHP_URL_HOST);
                            if ( $host != '' && $showLinkURL ) {
                                if ( mb_strpos(strtolower($clean_word), strtolower($host)) === false ) {
                                    $suffix = " [" . strtolower(str_ireplace('www.', '', $host)) . "]";
                                }
                            }

                            $clean_text = $clean_word;
                            if ( mb_stripos($clean_text, '?') ) {
                                $clean_text = substr($clean_text, 0, mb_stripos($clean_text, '?'));
                            }

                            $word = str_ireplace($clean_word, '<a target="_blank" href="' . $rURL . '">' . $clean_text . '</a>' . $suffix, $word);
                        }

                    } else {
                        $hparts = explode('.', parse_url($url, PHP_URL_HOST));
                        $domain = '';
                        $parts = 0;
                        $nulls = 0;

                        for ( $dd = 0; $dd < count($hparts); $dd++ ) {
                            if ( NoNull($hparts[$dd]) != '' ) {
                                $domain = NoNull($hparts[$dd]);
                                $parts++;
                            } else {
                                $nulls++;
                            }
                        }

                        if ( $nulls == 0 && $parts > 1 && isValidTLD($domain) ) {
                            $host = parse_url($url, PHP_URL_HOST);
                            if ( $host != '' && $showLinkURL ) {
                                if ( mb_strpos(strtolower($clean_word), strtolower($host)) === false ) {
                                    $suffix = " [" . strtolower(str_ireplace('www.', '', $host)) . "]";
                                }
                            }

                            $clean_text = $clean_word;
                            if ( mb_stripos($clean_text, '?') ) {
                                $clean_text = substr($clean_text, 0, mb_stripos($clean_text, '?'));
                            }

                            $word = str_ireplace($clean_word, '<a target="_blank" href="' . $url . '">' . $clean_text . '</a>' . $suffix, $word);
                        }
                    }
                }
            }

            // Output Something Here
            $out_str .= " $word";
        }

        // If We Have Footnotes, Add them
        if ( $fnotes != '' ) { $out_str .= '<hr><ol>' . $fnotes . '</ol>'; }

        // Fix any Links that Don't Have Targets
        $rVal = str_ireplace('<a href="', '<a target="_blank" href="', $out_str);
        $rVal = str_ireplace('<a target="_blank" href="http://mailto:', '<a href="mailto:', $rVal);

        // Do Not Permit Any Forbidden Characters to Go Back
        $forbid = array( '<script'      => "&lt;script",    '</script'           => "&lt;/script",   '< script'     => "&lt;script",
                         '<br></p>'     => '</p>',          '<br></li>'          => '</li>',         '<br> '        => '<br>',
                         '&#95;'        => '_',             '&amp;#92;'          => '&#92;',         ' </p>'        => '</p>',
                         '&lt;iframe '  => '<iframe ',      '&gt;&lt;/iframe&gt' => '></iframe>',    '&lt;/iframe>' => '</iframe>',
                         '</p></p>'     => '</p>',          '<p><p>'             => '<p>',
                         '...'          => '…',

                         ':???:'  => "😕",

                         '<p><blockquote>' => '<blockquote>',
                         '<pre><code><br>' => '<pre><code>',
                        );
        for ( $i = 0; $i < 10; $i++ ) {
            $rVal = str_replace(array_keys($forbid), array_values($forbid), $rVal);
        }

        // Return the Markdown-formatted HTML
        return NoNull($rVal);
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