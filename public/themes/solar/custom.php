<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for the Murasaki site template
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
    public function getSchemaMeta($data) { return $this->_getSchemaMeta($data); }

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
                          '[PGSUB_1]'       => NoNull($this->settings['PgSub1']),
                          '[YEAR]'          => date('Y'),

                          '[CHANNEL_GUID]'  => NoNull($data['channel_guid']),
                          '[NONCE]'         => md5(NoNull($this->settings['HomeURL']) . NoNull($this->settings['_address'])),

                          '[SITE_URL]'      => $this->settings['HomeURL'],
                          '[SITE_NAME]'     => $data['name'],
                          '[SITEDESCR]'     => $data['description'],
                          '[SITEKEYWD]'     => $data['keywords'],
                         );
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = NoNull($Value);
        }

        /* Collect the page content */
        $page = $this->_getPageContent();
        if ( is_array($page) && count($page) > 0 ) {
            foreach ( $page as $Key=>$Value ) {
                $Key = strtoupper("[$Key]");
                if ( array_key_exists($Key, $ReplStr) === false ) { $ReplStr[$Key] = $Value; }
            }
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

        /* Collect the page lookup details */
        $data = $this->_getPageLookupData();
        if ( is_array($data) && mb_strlen(NoNull($data['guid'])) == 36 ) {
            if ( YNBool($data['is_match']) ) { $ReqPage = 'page-' . NoNull($data['template']) . '.html'; }
        }

        /* Confirm the Page Exists and Return the proper Resource path */
        if ( file_exists("$ResDIR/$ReqPage") === false ) { $ReqPage = 'page-404.html'; }
        return "$ResDIR/$ReqPage";
    }

    /** ********************************************************************* *
     *  Page Content Functions
     ** ********************************************************************* */
    /**
     *  Function builds the page content for the requested URL
     */
    private function _getPageContent() {
        $page = $this->_getPageLookupData();
        $data = false;

        /* If we have lookup data, let's collect the return results */
        if ( is_array($page) && mb_strlen(NoNull($page['guid'])) == 36 ) {
            $CacheKey = $this->_getRequestCacheKey('page');
            $data = getCacheObject($CacheKey);

            /* If we do not have an already complete HTML render, let's build one */
            if ( is_array($data) === false || mb_strlen(NoNull($data['post_guid'])) != 36 ) {
                $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                                  '[POST_GUID]'  => sqlScrub($page['guid']),
                                 );
                $sqlStr = readResource(SQL_DIR . '/posts/getPostByGuid.sql', $ReplStr);
                $rslt = doSQLQuery($sqlStr);
                if ( is_array($rslt) ) {
                    foreach ( $rslt as $Row ) {
                        if ( YNBool($Row['is_visible']) ) {
                            $data = array();

                            foreach ( $Row as $col=>$val ) {
                                $data[$col] = $val;
                            }

                            /* Set the special values */
                            $data['title_letter'] = mb_substr(NoNull($Row['title']), 0, 1);
                            $data['content_html'] = $this->_getMarkdownHTML($Row['content_text']);
                            $data['publish_on'] = date('F jS, Y', $Row['publish_unix']);
                            $data['tag_list'] = '';

                            /* Handle the tags */
                            if ( mb_strlen(NoNull($Row['post_tags'])) > 0 ) {
                                $tags = explode(',', $Row['post_tags']);
                                $list = array();

                                foreach ( $tags as $idx=>$tag ) {
                                    $tag = explode('|', $tag);
                                    $key = strtolower(NoNull($tag[0]));
                                    if ( array_key_exists($key, $list) === false ) {
                                        $list[$key] = NoNull($tag[1]);
                                    }
                                }

                                if ( is_array($list) && count($list) > 0 ) {
                                    $data['tag_list'] = "\n" . tabSpace(4) . '<ul class="tags">';

                                    foreach ( $list as $tag=>$label ) {
                                        $data['tag_list'] .= "\n" . tabSpace(5) . '<li data-name="' . $tag . '">' . $label . '</li>';
                                    }

                                    $data['tag_list'] .= "\n" . tabSpace(4) . '</ul>';
                                }
                            }
                        }
                    }
                }

                /* If we have something that looks valid, let's save it to the cache */
                if ( is_array($data) && mb_strlen(NoNull($data['post_guid'])) == 36 ) { setCacheObject($CacheKey, $data); }
            }
        }

        /* Return the completed HTML if it exists ... or an empty string */
        if ( is_array($data) && count($data) > 0 ) { return $data; }
        return false;
    }

    /** ********************************************************************* *
     *  Page Schema Functions
     ** ********************************************************************* */
    /**
     *  Function returns the schema for a given page
     */
    private function _getSchemaMeta( $data ) {
        $page = $this->_getPageLookupData();

        if ( is_array($page) && array_key_exists('guid', $page) && mb_strlen(NoNull($page['guid'])) == 36 ) {
            $CacheKey = 'schema-' . NoNull($page['guid']) . '-' . nullInt($data['updated_unix']);
            $data = getCacheObject($CacheKey);
            if ( is_array($data) === false || array_key_exists('url', $data) === false ) {
                $ReplStr = array( '[SITE_ID]'   => nullInt($this->settings['_site_id']),
                                  '[POST_GUID]' => sqlScrub($page['guid']),
                                 );
                $sqlStr = readResource(SQL_DIR . '/posts/getPostSchema.sql', $ReplStr);
                $rslt = doSQLQuery($sqlStr);
                if ( is_array($rslt) ) {
                    foreach ( $rslt as $Row ) {
                        $summ = NoNull(preg_replace('/\s*/m', '', NoNull($Row['summary'])));
                        if ( mb_strlen($summ) > 3 ) {
                            $data = array( '@context' => 'https://schema.org',
                                           '@type'    => 'BlogPosting',
                                           'headline' => NoNull($Row['title']),
                                           'description' => NoNull($Row['summary']),
                                           'author'   => array( '@type' => 'Person',
                                                                'name'  => NoNull($Row['author_name']),
                                                               ),
                                           'publisher' => array( '@type' => 'Organization',
                                                                 'name'  => NoNull($Row['domain']),
                                                                ),
                                           'datePublished' => NoNull($Row['publish_ymd']),
                                           'dateModified' => NoNull($Row['updated_ymd']),
                                           'mainEntityOfPage' => array( '@type' => 'WebPage',
                                                                        '@id'   => NoNull($Row['url']),
                                                                       ),
                                           'url' => NoNull($Row['url']),
                                          );
                        }

                        /* If the data is valid, let's cache it */
                        if ( is_array($data) && count($data) > 0 ) { setCacheObject($CacheKey, $data); }
                    }
                }
            }

            /* If we have data, let's build the output */
            if ( is_array($data) && mb_strlen(NoNull($data['url'])) > 10 ) {

                $out = '<script type="application/ld+json">' . "\n" .
                       json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n" .
                       '</script>' . "\n";
                $meta = '';

                /* Ensure the padding is correct */
                $lines = explode("\n", $out);
                if ( is_array($lines) && count($lines) > 0 ) {
                    foreach ( $lines as $line ) {
                        if ( mb_strlen(NoNull($line)) > 0 ) {
                            $meta .= tabSpace(2) . $line . "\n";
                        }
                    }
                }

                /* So long as we have something that looks normal, return it */
                if ( mb_strlen($meta) > 10 ) { return $meta; }
            }
        }

        /* If we're here, there's nothing to return */
        return '';
    }

    /** ********************************************************************* *
     *  Page Cache Functions
     ** ********************************************************************* */
    /**
     *  Function returns a key string that is unique for the current site, user, minute, and URL. This
     *      can be used with Redis or local caching to reduce the number of expensive lookups performed
     *      during an HTTP request.
     */
    private function _getRequestCacheKey( $prefix = '' ) {
        return NoNull($prefix, 'solar') . '-' .
               substr('00000000' . NoNull($this->settings['_site_id'], $this->settings['site_id']), -8) . '_' .
               substr('00000000' . NoNull($this->settings['_account_id']), -8) . '_' .
               substr('0000' . date('Hi'), -4) . '_' .
               md5(strtolower(NoNull($this->settings['ReqURI'])));
    }

    /**
     *  Function returns an array with basic lookup data or an unhappy boolean
     */
    private function _getPageLookupData() {
        $CacheKey = $this->_getRequestCacheKey('po');

        $data = getCacheObject( $CacheKey );
        if ( is_array($data) === false || mb_strlen(NoNull($data['guid'])) != 36 ) {
            $ReplStr = array( '[REQ_URI]' => sqlScrub($this->settings['ReqURI']),
                              '[SITE_ID]' => nullInt($this->settings['_site_id']),
                             );
            $sqlStr = readResource(SQL_DIR . '/web/chkReqUri.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    if ( YNBool($Row['is_match']) ) {
                        $data = array( 'guid'     => NoNull($Row['guid']),
                                       'is_match' => YNBool($Row['is_match']),
                                       'template' => NoNull($Row['template']),
                                      );
                    }
                }
            }

            /* Save the cache if we have something that looks valid and return the array */
            if ( is_array($data) && mb_strlen(NoNull($data['guid'])) == 36 ) { setCacheObject($CacheKey, $data); }
        }

        /* If we're here, there is no matching record */
        return ((is_array($data) && mb_strlen(NoNull($data['guid'])) == 36) ? $data : false);
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

        /* Handle Code Blocks */
        if (preg_match_all('/\```(.+?)\```/s', $text, $matches)) {
            foreach($matches[0] as $fn) {
                $cbRepl = array( '```' => '', '<code><br>' => "<code>", '<br></code>' => '</code>', "\n" => '<br>', ' ' => "&nbsp;" );
                $code = "<pre><code>" . str_replace(array_keys($cbRepl), array_values($cbRepl), $fn) . "</code></pre>";
                $code = str_replace(array_keys($cbRepl), array_values($cbRepl), $code);
                $text = str_replace($fn, $code, $text);
            }
        }

        /* Handle Strikethroughs */
        if (preg_match_all('/\~~(.+?)\~~/s', $text, $matches)) {
            foreach($matches[0] as $fn) {
                if ( mb_strpos($fn, "\n") === false && mb_strpos($fn, "\r") === false ) {
                    $stRepl = array( '~~' => '' );
                    $code = "<del>" . NoNull(str_replace(array_keys($stRepl), array_values($stRepl), $fn)) . "</del>";
                    $text = str_replace($fn, $code, $text);
                }
            }
        }

        /* Get the Markdown Formatted */
        $text = str_replace('\\', '&#92;', $text);
        $rVal = Markdown::defaultTransform($text, $isNote);
        for ( $i = 0; $i <= 5; $i++ ) {
            foreach ( $Excludes as $Item ) {
                $rVal = str_replace($Item, '', $rVal);
            }
        }

        /* Replace any Hashtags if they exist */
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
                         '</p> <p>'     => '</p><p>',       '</p></p>'           => '</p>',          '<p><p>'       => '<p>',

                         '<blockquote><br>' => '<blockquote>', '<br></blockquote>' => '</blockquote>', '<p><blockquote>' => '<blockquote>',
                         '<ul><br>' => '<ul>', '<br></ul>' => '</ul>', '<ol><br>' => '<ol>', '<br></ol>' => '</ol>',
                         '<li><br>' => '<li>', '</li><br>' => '</li>',
                         "&nbsp;" => ' ',

                         '</p>' => "</p>\n" . tabSpace(4),
                         '<ul>' => "<ul>\n",
                         '</ul>' => "</ul>\n" . tabSpace(4),
                         '<li>' => "\n" . tabSpace(5) . '<li>',
                         '</li></ul>' => "</li>\n" . tabSpace(4) . "</ul>",
                        );
        for ( $i = 0; $i < 10; $i++ ) {
            $rVal = str_replace(array_keys($forbid), array_values($forbid), $rVal);
        }

        /* Remove the excessive returns */
        for ( $i = 10; $i >= 2; $i-- ) {
            $rVal = str_ireplace(str_repeat("\n", $i), "\n", $rVal);
            $rVal = str_ireplace(str_repeat("\r", $i), '', $rVal);
        }

        $lines = explode("\n", $rVal);
        if ( is_array($lines) && count($lines) > 0 ) {
            $rVal = '';
            foreach ( $lines as $line ) {
                if ( mb_strlen(NoNull($line)) > 0 ) { $rVal .= $line . "\n"; }
            }
        }

        /* Return the properly formatted HTML */
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
        return false;
    }
}
?>