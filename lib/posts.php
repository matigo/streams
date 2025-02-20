<?php

/**
 * Class contains the rules and methods called to manage Posts
 */
require_once(LIB_DIR . '/functions.php');
require_once(LIB_DIR . '/markdown.php');
use \Michelf\Markdown;

class Posts {
    var $settings;
    var $strings;
    var $markers;
    var $geo;

    function __construct( $settings, $strings = false ) {
        $this->settings = $settings;
        $this->strings = ((is_array($strings)) ? $strings : getLangDefaults($this->settings['_language_code']));
        $this->markers = false;
        $this->geo = false;
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    public function performAction() {
        $ReqType = NoNull(strtolower($this->settings['ReqType']));

        // Perform the Action
        switch ( $ReqType ) {
            case 'get':
                return $this->_performGetAction();
                break;

            case 'post':
                return $this->_performPostAction();
                break;

            case 'delete':
                return $this->_performDeleteAction();
                break;

            default:
                // Do Nothing
        }

        // If we're here, there's nothing to return
        return false;
    }

    private function _performGetAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( mb_strlen($Activity) == 36 ) { $Activity = 'read'; }

        switch ( $Activity ) {
            case 'globals':
            case 'global':
                return $this->_getTLStream('global');
                break;

            case 'mentions':
            case 'mention':
                return $this->_getTLStream('mentions');
                break;

            case 'home':
                return $this->_getTLStream('home');
                break;

            case 'interactions':
            case 'interaction':
            case 'actions':
                return $this->_getTLStream('interact');
                break;

            case 'hashes':
            case 'hash':
                return $this->_getWordHistory();
                break;

            case 'archives':
            case 'archive':
            case 'library':
            case 'list':
            case '':
                return $this->_getPostList();
                break;

            case 'read':
                return  $this->_getPostByGUID();
                break;

            case 'readmore':
            case 'readnext':
                return $this->_getReadMore();
                break;

            case 'thread':
                return $this->_getThreadByGUID();
                break;

            default:

        }

        // If we're here, there's nothing to return
        return false;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( mb_strlen($Activity) == 36 ) { $Activity = 'edit'; }

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case 'write':
            case 'edit':
            case '':
                return $this->_writePost();
                break;

            case 'pin':
                return $this->_setPostPin();
                break;

            case 'points':
            case 'point':
                return $this->_setPostPoints();
                break;

            case 'star':
                return $this->_setPostStar();
                break;

            default:
                // Do Nothing
        }

        // If we're here, there's nothing to return
        return false;
    }

    private function _performDeleteAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( mb_strlen($Activity) == 36 ) { $Activity = 'delete'; }

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case 'delete':
            case '':
                return $this->_deletePost();
                break;

            case 'pin':
                return $this->_setPostPin();
                break;

            case 'points':
            case 'point':
                return $this->_setPostPoints();
                break;

            case 'star':
                return $this->_setPostStar();
                break;

            default:
                // Do Nothing
        }

        // If we're here, there's nothing to return
        return false;
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
    public function getPageJSON( $data ) { return $this->_getPageJSON($data); }
    public function getMarkdownHTML( $text, $post_id, $isNote, $showLinkURL ) { return $this->_getMarkdownHTML( $text, $post_id, $isNote, $showLinkURL); }
    public function getPersonaPosts() { return $this->_getTLStream('persona'); }
    public function getPopularPosts() { return $this->_getPopularPosts(); }
    public function getRSSFeed($site, $format) { return $this->_getRSSFeed($site, $format); }

    public function getPostsByIDs( $ids ) { return $this->_getPostsByIDs($ids); }
    public function writePost() { return $this->_writePost(); }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function returns a Post based on the Post.GUID Supplied or an Unhappy Boolean
     */
    private function _getPostByGUID() {
        $PostGUID = strtolower(NoNull($this->settings['guid'], $this->settings['PgSub1']));
        if ( mb_strlen($PostGUID) != 36 ) { $this->_setMetaMessage("Invalid Post Identifier Supplied (1)", 400); return false; }

        $PostID = $this->_getPostIDFromGUID($PostGUID);
        if ( $PostID > 0 ) { return $this->_getPostsByIDs($PostID); }

        // If We're Here, the Post.guid Was Not Found (or is Inaccessible)
        $this->_setMetaMessage("Invalid Post Identifier Supplied (2)", 400);
        return false;
    }

    /**
     *  Function returns a Collection of Posts based on the Post.GUID of a Single Post in a Thread Supplied or an Unhappy Boolean
     */
    private function _getThreadByGUID() {
        $SimpleHtml = YNBool(NoNull($this->settings['simple']));
        $PostGuid = NoNull($this->settings['guid'], $this->settings['PgSub1']);

        /* Get the Post.Guid */
        $guids = array('post_guid', 'post', 'guid', 'PgSub1');
        foreach ( $guids as $key ) {
            if ( mb_strlen($PostGuid) != 36 ) { $PostGuid = NoNull($this->settings[$key]); }
        }

        /* Ensure we have a valid Post.guid */
        if ( mb_strlen($PostGuid) != 36 ) { return $this->_setMetaMessage("Invalid Post Identifier Supplied (1)", 400); }

        /* Get the Types Requested (Default is Everything) */
        $validTypes = array( 'post.article', 'post.note', 'post.quotation', 'post.bookmark', 'post.location', 'post.photo' );
        $CleanTypes = '';
        $rTypes = explode(',', NoNull($this->settings['types'], $this->settings['post_types']));
        if ( is_array($rTypes) ) {
            foreach ( $rTypes as $rType ) {
                $rType = strtolower($rType);
                if ( in_array($rType, $validTypes) ) {
                    if ( $CleanTypes != '' ) { $CleanTypes .= ','; }
                    $CleanTypes .=  sqlScrub($rType);
                }
            }
        } else {
            if ( is_string($rTypes) ) {
                $rType = strtolower($rTypes);
                if ( in_array($rType, $validTypes) ) {
                    if ( $CleanTypes != '' ) { $CleanTypes .= ','; }
                    $CleanTypes .= sqlScrub($rType);
                }
            }
        }
        if ( $CleanTypes == '' ) { $CleanTypes = "post.article,post.note,post.quotation,post.bookmark,post.location,post.photo"; }

        /* Get the Time Range */
        $SinceUnix = nullInt($this->settings['since']);
        $UntilUnix = nullInt($this->settings['until']);

        /* How Many Posts? */
        $CleanCount = nullInt($this->settings['count'], 250);
        if ( $CleanCount > 250 ) { $CleanCount = 250; }
        if ( $CleanCount <= 0 ) { $CleanCount = 100; }
        $CleanCount++;

        // Get the Posts
        $ReplStr = array( '[ACCOUNT_ID]'  => nullInt($this->settings['_account_id']),
                          '[SINCE_UNIX]'  => nullInt($SinceUnix),
                          '[UNTIL_UNIX]'  => nullInt($UntilUnix),
                          '[POST_TYPES]'  => NoNull($CleanTypes),
                          '[THREAD_GUID]' => sqlScrub($PostGuid),
                          '[COUNT]'       => nullInt($CleanCount),
                         );
        $sqlStr = prepSQLQuery("CALL GetThreadPosts([ACCOUNT_ID], '[THREAD_GUID]', '[POST_TYPES]', [SINCE_UNIX], [UNTIL_UNIX], [COUNT]);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            return $this->_processTimeline($rslt);
        }

        /* If We're Here, the Post.guid Was Not Found (or is Inaccessible) */
        return $this->_setMetaMessage("Invalid Thread Identifier Supplied (2)", 400);
    }

    /**
     *  Function Returns a Channel GUID for a Given PostGUID. If none is found, an unhappy boolean is returned.
     */
    private function _getPostChannel( $PostGUID ) {
        if ( mb_strlen(NoNull($PostGUID)) <= 30 ) { return false; }

        // Build the SQL Query and execute it
        $ReplStr = array( '[POST_GUID]' => sqlScrub($PostGUID) );
        $sqlStr = readResource(SQL_DIR . '/posts/getPostChannel.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                return NoNull($Row['channel_guid']);
            }
        }

        // If We're Here, There Is No Matching Post
        return false;
    }

    private function _getWebMentions( $post_id ) {
        if ( nullInt($post_id) <= 0 ) { return false; }

        $ReplStr = array( '[POST_ID]' => nullInt($post_id) );
        $sqlStr = readResource(SQL_DIR . '/posts/getPostWebMentions.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();

            foreach ( $rslt as $Row ) {
                $data[] = array( 'url'          => NoNull($Row['url']),
                                 'avatar_url'   => NoNull($Row['avatar_url']),
                                 'author'       => NoNull($Row['author']),
                                 'comment'      => NoNull($Row['comment']),
                                 'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                                 'created_unix' => strtotime($Row['created_at']),
                                 'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                 'updated_unix' => strtotime($Row['updated_at']),
                                );
            }

            // If we have data, return it
            if ( count($data) > 0 ) { return $data; }
        }

        // If we're here, there's nothing
        return false;
    }

    private function _getPostMentions( $post_ids ) {
        $list = array();

        // If We've Received a List, Split it Out
        if ( is_array($post_ids) ) {
            foreach ( $post_ids as $id ) {
                $list[] = nullInt($id);
            }
        }

        // If a Single Post is Being Requested, Check to See If It's in Memory
        if ( nullInt($post_ids) > 0 ) {
            if ( is_array($this->settings["post-$post_ids"]) ) {
                return $this->settings["post-$post_ids"];
            }
            $list[] = nullInt($post_ids);
        }
        if ( count($list) <= 0 ) { return false; }

        $ReplStr = array( '[POST_IDS]'   => sqlScrub(implode(',', $list)),
                          '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/getPostMentions.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            if ( array_key_exists('personas', $this->settings) === false ) {
                $this->settings['personas'] = array();
                $this->settings['pa_guids'] = array();
            }

            foreach ( $rslt as $Row ) {
                $pid = nullInt($Row['post_id']);
                if ( is_array($this->settings["post-$pid"]) === false ) {
                    $this->settings["post-$pid"] = array();
                }

                if ( in_array(NoNull($Row['name']), $this->settings['personas']) === false ) {
                    $this->settings['personas'][] = NoNull($Row['name']);
                    $this->settings['pa_guids'][] = array( 'guid' => NoNull($Row['guid']),
                                                           'name' => '@' . NoNull($Row['name']),
                                                          );
                }

                // Write the Record to the Cache
                $this->settings["post-$pid"][] = array( 'guid'   => NoNull($Row['guid']),
                                                        'as'     => '@' . NoNull($Row['name']),
                                                        'is_you' => YNBool($Row['is_you']),
                                                       );
            }

            // If We Have Data, Return It
            if ( is_array($post_ids) ) {
                return count($rslt);

            } else {
                if ( is_array($this->settings["post-$post_ids"]) ) {
                    return $this->settings["post-$post_ids"];
                }
            }
        }

        // If We're Here, There's Nothing
        return false;
    }

    private function _parsePostMentions( $text, $mentions = false ) {
        if ( is_array($mentions) === false ){ $mentions = $this->settings['pa_guids']; }
        if ( is_array($mentions) === false ) { return $text; };

        $ReplStr = array();
        if ( is_array($mentions) ) {
            foreach ( $mentions as $u ) {
                $plain = NoNull(str_replace('@', '', $u['name']));

                $ReplStr[ $u['name'] . '</' ]  = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span></';
                $ReplStr[ $u['name'] . '<br' ] = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span><br';
                $ReplStr[ $u['name'] . '<hr' ] = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span><hr';
                $ReplStr[ $u['name'] . '?' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>?';
                $ReplStr[ $u['name'] . '!' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>!';
                $ReplStr[ $u['name'] . '.' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>.';
                $ReplStr[ $u['name'] . ':' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>:';
                $ReplStr[ $u['name'] . ';' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>;';
                $ReplStr[ $u['name'] . ',' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>,';
                $ReplStr[ $u['name'] . ' ' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span> ';
                $ReplStr[ $u['name'] . ')' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>)';
                $ReplStr[ $u['name'] . "'" ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>'";
                $ReplStr[ $u['name'] . "’" ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>’";
                $ReplStr[ $u['name'] . '-' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>-';
                $ReplStr[ $u['name'] . '"' ]   = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>"';
                $ReplStr[ $u['name'] . "\n" ]  = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>\n";
                $ReplStr[ $u['name'] . "\r" ]  = '<span class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>\r";
                $ReplStr[ "/" . $u['name'] ]  = "/<span" . ' class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>";
                $ReplStr[ "\n" . $u['name'] ]  = "\n<span" . ' class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>";
                $ReplStr[ "\r" . $u['name'] ]  = "\r<span" . ' class="account" data-nick="' . $plain . '" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>";
            }
        }

        // Parse and Return the Text
        $rVal = NoNull(str_ireplace(array_keys($ReplStr), array_values($ReplStr), " $text "));
        return NoNull($rVal, $text);
    }

    /**
     *  Function Parses a Post Result Set (from ad hoc queries and stored procedures) into a Consistent Format
     */
    private function _parsePostResultSet( $rslt ) {
        if ( is_array($rslt) && count($rslt) > 0 ) {
            $data = array();
            $mids = array();

            // Collect the Mentions
            foreach ( $rslt as $Row ) {
                if ( YNBool($Row['has_mentions']) ) { $mids[] = nullInt($Row['post_id']); }
            }
            if ( count($mids) > 0 ) { $pms = $this->_getPostMentions($mids); }

            // Now Let's Parse the Posts
            foreach ( $rslt as $Row ) {
                $siteURL = ((YNBool($Row['https'])) ? 'https' : 'http') . '://' . NoNull($Row['site_url']);
                $cdnURL = $siteURL . '/images/';
                $poMeta = false;
                if ( YNBool($Row['has_meta']) ) { $poMeta = $this->_getPostMeta($Row['post_guid']); }
                if ( NoNull($this->settings['nom']) != '' ) {
                    if ( is_array($poMeta) === false ) { $poMeta = array(); }
                    $poMeta['nom'] = NoNull($this->settings['nom']);
                }
                $poTags = false;
                if ( NoNull($Row['post_tags']) != '' ) {
                    $poTags = array();
                    $tgs = explode(',', NoNull($Row['post_tags']));
                    foreach ( $tgs as $tag ) {
                        $key = $this->_getSafeTagSlug(NoNull($tag));

                        $poTags[] = array( 'url'  => $siteURL . '/tag/' . $key,
                                           'name' => NoNull($tag),
                                          );
                    }
                }

                // Do We Have Mentions? Grab the List
                $mentions = false;
                if ( YNBool($Row['has_mentions']) ) {
                    $mentions = $this->_getPostMentions($Row['post_id']);
                }

                // Determine Which HTML Classes Can Be Applied to the Record
                $pguid = NoNull($this->settings['PgSub1'], $this->settings['guid']);
                if ( mb_strlen($pguid) != 36 ) { $pguid = ''; }
                $pclass = array();
                if ( NoNull($Row['canonical_url']) == NoNull($this->settings['ReqURI']) || NoNull($Row['post_guid']) == $pguid || count($rslt) == 1 ) { $pclass[] = 'h-entry'; }
                if ( NoNull($Row['reply_to']) != '' ) { $pclass[] = 'p-in-reply-to'; }

                $IsNote = true;
                if ( in_array(NoNull($Row['post_type']), array('post.article', 'post.quotation', 'post.bookmark', 'post.location', 'post.page')) ) { $IsNote = false; }
                $post_text = $this->_getMarkdownHTML($Row['value'], $Row['post_id'], $IsNote, true);
                $post_text = $this->_parsePostMentions($post_text);

                $webMentions = false;
                if ( count($rslt) == 1 && nullInt($Row['web_mentions']) > 0 ) {
                    $webMentions = $this->_getWebMentions($Row['post_id']);
                }

                $data[] = array( 'guid'     => NoNull($Row['post_guid']),
                                 'type'     => NoNull($Row['post_type']),
                                 'thread'   => ((NoNull($Row['thread_guid']) != '') ? array( 'guid' => NoNull($Row['thread_guid']), 'count' => nullInt($Row['thread_posts']) ) : false),
                                 'privacy'  => NoNull($Row['privacy_type']),
                                 'persona'  => array( 'guid'    => NoNull($Row['persona_guid']),
                                                      'as'      => '@' . NoNull($Row['persona_name']),
                                                      'name'    => NoNull($Row['display_name']),
                                                      'avatar'  => $siteURL . '/avatars/' . NoNull($Row['avatar_img'], 'default.png'),
                                                      'follow'  => array( 'url' => $siteURL . '/feeds/' . NoNull($Row['persona_name']) . '.json',
                                                                          'rss' => $siteURL . '/feeds/' . NoNull($Row['persona_name']) . '.xml',
                                                                         ),
                                                      'is_active'    => YNBool($Row['persona_active']),
                                                      'is_you'       => ((nullInt($Row['created_by']) == nullInt($this->settings['_account_id'])) ? true : false),
                                                      'profile_url'  => $siteURL . '/profile/' . NoNull($Row['persona_name']),

                                                      'created_at'   => apiDate($Row['persona_created_unix'], 'Z'),
                                                      'created_unix' => apiDate($Row['persona_created_unix'], 'U'),
                                                      'updated_at'   => apiDate($Row['persona_updated_unix'], 'Z'),
                                                      'updated_unix' => apiDate($Row['persona_updated_unix'], 'U'),
                                                     ),

                                 'title'    => ((NoNull($Row['title']) == '') ? false : NoNull($Row['title'])),
                                 'content'  => str_replace('[HOMEURL]', $siteURL, $post_text),
                                 'text'     => NoNull($Row['value']),
                                 'rtl'      => $this->_isRTL(NoNull($Row['value'])),

                                 'publish_at'   => apiDate($Row['publish_unix'], 'Z'),
                                 'publish_unix' => apiDate($Row['publish_unix'], 'U'),
                                 'expires_at'   => apiDate($Row['expires_unix'], 'Z'),
                                 'expires_unix' => apiDate($Row['expires_unix'], 'U'),
                                 'updated_at'   => apiDate($Row['updated_unix'], 'Z'),
                                 'updated_unix' => apiDate($Row['updated_unix'], 'U'),

                                 'meta'     => $poMeta,
                                 'tags'     => $poTags,
                                 'mentions' => $mentions,

                                 'web_mentions'     => $webMentions,

                                 'canonical_url'    => $siteURL . NoNull($Row['canonical_url']),
                                 'slug'             => NoNull($Row['slug']),
                                 'reply_to'         => ((NoNull($Row['reply_to']) == '') ? false : NoNull($Row['reply_to'])),
                                 'class'            => ((count($pclass) > 0) ? implode(' ', $pclass) : ''),
                                 'attributes'       => array( 'pin'     => NoNull($Row['pin_type'], 'pin.none'),
                                                              'starred' => YNBool($Row['is_starred']),
                                                              'muted'   => YNBool($Row['is_muted']),
                                                              'points'  => nullInt($Row['points']),
                                                             ),

                                 'channel'  => array( 'guid'    => NoNull($Row['channel_guid']),
                                                      'name'    => NoNull($Row['channel_name']),
                                                      'type'    => NoNull($Row['channel_type']),
                                                      'privacy' => NoNull($Row['channel_privacy_type']),

                                                      'created_at'   => apiDate($Row['channel_created_unix'], 'Z'),
                                                      'created_unix' => apiDate($Row['channel_created_unix'], 'U'),
                                                      'updated_at'   => apiDate($Row['channel_updated_unix'], 'Z'),
                                                      'updated_unix' => apiDate($Row['channel_updated_unix'], 'U'),
                                                     ),
                                 'site'     => array( 'guid'        => NoNull($Row['site_guid']),
                                                      'name'        => NoNull($Row['site_name']),
                                                      'description' => NoNull($Row['site_description']),
                                                      'keywords'    => NoNull($Row['site_keywords']),
                                                      'url'         => $siteURL
                                                     ),

                                 'can_edit' => ((nullInt($Row['created_by']) == nullInt($this->settings['_account_id'])) ? true : false),
                                );
            }

            // If We Have Data, Return It
            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There Are No Posts. Return an Empty Array.
        return array();
    }

    /**
     *  Function Returns the Posts Requested
     */
    private function _getPostsByIDs( $PostIDs = false ) {
        $ids = array();
        if ( is_bool($PostIDs) ) { $this->_setMetaMessage("Invalid Post IDs Supplied", 400); return false; }
        if ( is_string($PostIDs) ) {
            $list = explode(',', $PostIDs);
            foreach ( $list as $id ) {
                if ( in_array($id, $ids) === false && nullInt($id) > 0 ) { $ids[] = nullInt($id); }
            }
        }
        if ( is_array($PostIDs) ) {
            foreach ( $PostIDs as $id ) {
                if ( in_array($id, $ids) === false && nullInt($id) > 0 ) { $ids[] = nullInt($id); }
            }
        }
        if ( count($ids) <= 0 && is_numeric($PostIDs) ) { $ids[] = nullInt($PostIDs); }

        // If There Are Zero IDs in the Array, We Were Given Non-Numerics
        if ( count($ids) <= 0 ) { $this->_setMetaMessage("Invalid Post IDs Supplied", 400); return false; }

        // Glue the Post IDs back Together
        $posts = implode(',', $ids);

        // Get the Persona.GUID (if applicable)
        $PersonaGUID = NoNull($this->settings['persona_guid'], NoNull($this->settings['persona-guid'], $this->settings['_persona_guid']));

        // Construct the Replacement Array and Run the Query
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[PERSONA_GUID]' => sqlScrub($PersonaGUID),
                          '[SITE_TOKEN]'   => sqlScrub(NoNull($this->settings['site_token'])),
                          '[POST_IDS]'     => sqlScrub($posts),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/getPostsByIDs.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();

            // Now Let's Parse the Posts
            foreach ( $rslt as $Row ) {
                $siteURL = ((YNBool($Row['https'])) ? 'https' : 'http') . '://' . NoNull($Row['site_url']);
                $cdnURL = $siteURL . '/images/';
                $poMeta = false;
                if ( YNBool($Row['has_meta']) ) { $poMeta = $this->_getPostMeta($Row['post_guid']); }
                if ( NoNull($this->settings['nom']) != '' ) {
                    if ( is_array($poMeta) === false ) { $poMeta = array(); }
                    $poMeta['nom'] = NoNull($this->settings['nom']);
                }
                $poTags = false;
                if ( NoNull($Row['post_tags']) != '' ) {
                    $poTags = array();
                    $tgs = explode(',', NoNull($Row['post_tags']));
                    foreach ( $tgs as $tag ) {
                        $key = $this->_getSafeTagSlug(NoNull($tag));

                        $poTags[] = array( 'url'  => $siteURL . '/tag/' . $key,
                                           'name' => NoNull($tag),
                                          );
                    }
                }

                // Do We Have Mentions? Grab the List
                $mentions = false;
                if ( NoNull($Row['mentions']) != '' ) {
                    $json = json_decode('[' . $Row['mentions'] . ']');
                    $jArr = objectToArray($json);
                    if ( is_array($jArr) && count($jArr) > 0 ) {
                        $mentions = array();
                        foreach ( $jArr as $pa ) {
                            $mentions[] = array( 'guid'   => NoNull($pa['guid']),
                                                 'as'     => NoNull($pa['as']),
                                                 'name'   => NoNull($pa['as']),
                                                 'is_you' => YNBool($pa['is_you']),
                                                );
                        }
                    }
                }

                // Do We Have Geo-Markers? Grab the History
                $markers = false;
                if ( YNBool($Row['has_markers']) ) {
                    $markers = $this->_getPostMarkers($Row['post_guid']);
                    if ( is_array($markers) ) {
                        $poMeta['markers'] = $markers;
                    }
                }

                // Determine Which HTML Classes Can Be Applied to the Record
                $pguid = NoNull($this->settings['PgSub1'], $this->settings['guid']);
                if ( mb_strlen($pguid) != 36 ) { $pguid = ''; }
                $pclass = array();
                if ( NoNull($Row['canonical_url']) == NoNull($this->settings['ReqURI']) || NoNull($Row['post_guid']) == $pguid || count($rslt) == 1 ) { $pclass[] = 'h-entry'; }
                if ( NoNull($Row['reply_to']) != '' ) { $pclass[] = 'p-in-reply-to'; }

                $IsNote = true;
                if ( in_array(NoNull($Row['post_type']), array('post.article', 'post.quotation', 'post.bookmark')) ) { $IsNote = false; }
                $post_text = $this->_getMarkdownHTML($Row['value'], $Row['post_id'], $IsNote, true);
                $post_text = $this->_parsePostMentions($post_text, $mentions);

                $data[] = array( 'guid'     => NoNull($Row['post_guid']),
                                 'type'     => NoNull($Row['post_type']),
                                 'thread'   => ((NoNull($Row['thread_guid']) != '') ? array( 'guid' => NoNull($Row['thread_guid']), 'count' => nullInt($Row['thread_posts']) ) : false),
                                 'privacy'  => NoNull($Row['privacy_type']),
                                 'persona'  => array( 'guid'    => NoNull($Row['persona_guid']),
                                                      'as'      => '@' . NoNull($Row['persona_name']),
                                                      'name'    => NoNull($Row['display_name']),
                                                      'avatar'  => $siteURL . '/avatars/' . NoNull($Row['avatar_img'], 'default.png'),
                                                      'follow'  => array( 'url' => $siteURL . '/feeds/' . NoNull($Row['persona_name']) . '.json',
                                                                          'rss' => $siteURL . '/feeds/' . NoNull($Row['persona_name']) . '.xml',
                                                                         ),
                                                      'is_active'    => YNBool($Row['persona_active']),
                                                      'is_you'       => ((nullInt($Row['created_by']) == nullInt($this->settings['_account_id'])) ? true : false),
                                                      'profile_url'  => $siteURL . '/profile/' . NoNull($Row['persona_name']),

                                                      'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['persona_created_at'])),
                                                      'created_unix' => strtotime($Row['persona_created_at']),
                                                      'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['persona_updated_at'])),
                                                      'updated_unix' => strtotime($Row['persona_updated_at']),
                                                     ),

                                 'title'    => ((NoNull($Row['title']) == '') ? false : NoNull($Row['title'])),
                                 'content'  => str_replace('[HOMEURL]', $siteURL, $post_text),
                                 'text'     => NoNull($Row['value']),
                                 'rtl'      => $this->_isRTL(NoNull($Row['value'])),

                                 'publish_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['publish_at'])),
                                 'publish_unix' => strtotime($Row['publish_at']),
                                 'expires_at'   => ((NoNull($Row['expires_at']) != '') ? date("Y-m-d\TH:i:s\Z", strtotime($Row['expires_at'])) : false),
                                 'expires_unix' => ((NoNull($Row['expires_at']) != '') ? strtotime($Row['expires_at']) : false),
                                 'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                 'updated_unix' => strtotime($Row['updated_at']),

                                 'meta'       => $poMeta,
                                 'tags'       => $poTags,
                                 'mentions'   => $mentions,
                                 'points'     => nullInt($Row['total_points']),
                                 'has_thread' => ((nullInt($Row['thread_posts']) > 1) ? true : false),

                                 'canonical_url'    => $siteURL . NoNull($Row['canonical_url']),
                                 'slug'             => NoNull($Row['slug']),
                                 'reply_to'         => ((NoNull($Row['reply_to']) == '') ? false : NoNull($Row['reply_to'])),
                                 'class'            => ((count($pclass) > 0) ? implode(' ', $pclass) : ''),
                                 'attributes'       => array( 'pin'     => NoNull($Row['pin_type'], 'pin.none'),
                                                              'starred' => YNBool($Row['is_starred']),
                                                              'muted'   => YNBool($Row['is_muted']),
                                                              'points'  => nullInt($Row['points']),
                                                             ),

                                 'channel'  => array( 'guid'    => NoNull($Row['channel_guid']),
                                                      'name'    => NoNull($Row['channel_name']),
                                                      'type'    => NoNull($Row['channel_type']),
                                                      'privacy' => NoNull($Row['channel_privacy_type']),

                                                      'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['channel_created_at'])),
                                                      'created_unix' => strtotime($Row['channel_created_at']),
                                                      'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['channel_updated_at'])),
                                                      'updated_unix' => strtotime($Row['channel_updated_at']),
                                                     ),
                                 'site'     => array( 'guid'        => NoNull($Row['site_guid']),
                                                      'name'        => NoNull($Row['site_name']),
                                                      'description' => NoNull($Row['site_description']),
                                                      'keywords'    => NoNull($Row['site_keywords']),
                                                      'url'         => $siteURL
                                                     ),

                                 'can_edit' => ((nullInt($Row['created_by']) == nullInt($this->settings['_account_id'])) ? true : false),
                                );
            }

            // If We Have Data, Return It
            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There Are No Posts. Return an Empty Array.
        return array();
    }

    /**
     *  Function Writes or Updates a Post Object
     */
    private function _writePost() {
        $data = $this->_validateWritePostData();
        if ( is_array($data) === false ) { return false; }
        $post_id = 0;

        // Prep the Replacement Array and Execute the INSERT or UPDATE
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[CHANNEL_GUID]' => sqlScrub($data['channel_guid']),
                          '[PERSONA_GUID]' => sqlScrub($data['persona_guid']),
                          '[TOKEN_GUID]'   => sqlScrub($data['token_guid']),
                          '[TOKEN_ID]'     => nullInt($data['token_id']),

                          '[TITLE]'        => sqlScrub($data['title']),
                          '[VALUE]'        => sqlScrub($data['value']),
                          '[WORDS]'        => sqlScrub($data['words']),

                          '[CANON_URL]'    => sqlScrub($data['canonical_url']),
                          '[REPLY_TO]'     => sqlScrub($data['reply_to']),

                          '[POST_SLUG]'    => sqlScrub($data['slug']),
                          '[POST_TYPE]'    => sqlScrub($data['type']),
                          '[PRIVACY]'      => sqlScrub($data['privacy']),
                          '[PUBLISH_AT]'   => sqlScrub($data['publish_at']),
                          '[EXPIRES_AT]'   => sqlScrub($data['expires_at']),

                          '[THREAD_ID]'    => nullInt($data['thread_id']),
                          '[PARENT_ID]'    => nullInt($data['parent_id']),
                          '[POST_ID]'      => nullInt($data['post_id']),
                         );
        $sqlStr = prepSQLQuery("CALL WritePost([ACCOUNT_ID], '[CHANNEL_GUID]', '[PERSONA_GUID]', '[TOKEN_GUID]', [TOKEN_ID], " .
                                             "'[TITLE]', '[VALUE]', '[WORDS]', '[CANON_URL]', '[REPLY_TO]', " .
                                             "'[POST_SLUG]', '[POST_TYPE]', '[PRIVACY]', '[PUBLISH_AT]', '[EXPIRES_AT]', " .
                                             " [THREAD_ID], [PARENT_ID], [POST_ID]);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $has_published = false;
            $days_since = 0;

            foreach ( $rslt as $Row ) {
                $post_id = nullInt($Row['post_id']);
                $has_published = YNBool($Row['has_published']);
                $days_since = nullInt($Row['days_since']);
            }

            /* Do we need to trigger WelcomeBot to say something? */
            if ( $has_published === false ) {
                $sqlStr = prepSQLQuery("CALL SendWelcomeBotMsg([ACCOUNT_ID], 'Welcome to 10Centuries, @{name}!');", $ReplStr );
                $tslt = doSQLQuery($sqlStr);
            } else {
                if ( $days_since > 90 ) {
                    $ReplStr['[DAYS_SINCE]'] = number_format($days_since);
                    $sqlStr = prepSQLQuery("CALL SendWelcomeBotMsg([ACCOUNT_ID], 'Welcome back to 10Centuries, @{name}! [DAYS_SINCE] days have passed since your last post.');", $ReplStr );
                    $tslt = doSQLQuery($sqlStr);
                }
            }
        }

        // If It's Good, Record the Meta Data & Collect the Post Object to Return
        if ( nullInt($data['post_id'], $post_id) >= 1 ) {
            $sqlStr = '';

            /* Record the MetaData for the Post */
            foreach ( $data['meta'] as $Key=>$Value ) {
                if ( NoNull($Value) != '' ) {
                    $ReplStr = array( '[POST_ID]' => nullInt($data['post_id'], $post_id),
                                      '[VALUE]'   => sqlScrub($Value),
                                      '[KEY]'     => sqlScrub($Key),
                                     );
                    if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
                    $sqlStr .= readResource(SQL_DIR . '/posts/writePostMeta.sql', $ReplStr);
                }
            }

            /* Record the Tags for the Post */
            if ( mb_strlen(NoNull($data['tags'])) > 0 ) {
                $tgs = explode(',', NoNull($data['tags']));
                $lst = '';
                foreach ( $tgs as $Value ) {
                    $pid = nullInt($data['post_id'], $post_id);
                    $Key = $this->_getSafeTagSlug(NoNull($Value));
                    $Val = sqlScrub($Value);

                    if ( $lst != '' ) { $lst .= ","; }
                    $lst .= "($pid, '$Key', '$Val')";
                }

                // Extract the Tags from Inside the Post Text
                $lst_hash = $this->_getTagsFromPost($data['value'], nullInt($data['post_id'], $post_id));
                if ( $lst_hash != '' ) { $lst .= ",$lst_hash"; }

                if ( NoNull($lst) != '' ) {
                    $ReplStr = array( '[POST_ID]'    => nullInt($data['post_id'], $post_id),
                                      '[VALUE_LIST]' => NoNull($lst)
                                     );
                    if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
                    $sqlStr .= readResource(SQL_DIR . '/posts/writePostTags.sql', $ReplStr);
                }
            }

            /* Execute the Queries */
            $isOK = doSQLExecute($sqlStr);

            // Send any Webmentions or Pingbacks (If Applicable)
            $this->_setPostPublishData(nullInt($data['post_id'], $post_id));

            // Collect the Post Object
            return $this->_getPostsByIDs(nullInt($data['post_id'], $post_id));
        }

        // If We're Here, There's a Problem
        $this->_setMetaMessage("Could Not Write Post to Database", 400);
        return false;
    }

    private function _preparePostAction() {
        $PersonaGUID = NoNull($this->settings['persona_guid']);
        $PostGUID = NoNull($this->settings['post_guid'], NoNull($this->settings['guid'], $this->settings['PgSub1']));

        /* Ensure we Have the requisite GUIDs */
        if ( mb_strlen($PersonaGUID) <= 30 ) { return false; }
        if ( mb_strlen($PostGUID) <= 30 ) { return false; }

        /* Build and Run the SQL Query */
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[POST_GUID]'    => sqlScrub($PostGUID),
                          '[PERSONA_GUID]' => sqlScrub($PersonaGUID),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/preparePostAction.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        /* If We Have a Result, It's Good */
        if ( $rslt ) { return true; }
        return false;
    }

    private function _setPostPin() {
        $PersonaGUID = NoNull($this->settings['persona_guid'], $this->settings['_persona_guid']);
        $PostGUID = NoNull($this->settings['post_guid'], NoNull($this->settings['guid'], $this->settings['PgSub1']));
        $PinValue = NoNull($this->settings['pin_value'], $this->settings['value']);
        $ReqType = NoNull(strtolower($this->settings['ReqType']));

        /* Ensure we Have the requisite GUIDs */
        if ( mb_strlen($PersonaGUID) <= 30 ) { return $this->_setMetaMessage("Invalid Persona GUID Supplied", 400); }
        if ( mb_strlen($PostGUID) <= 30 ) { return $this->_setMetaMessage("Invalid Post GUID Supplied", 400); }
        $this->settings['guid'] = $PostGUID;
        $this->settings['ReqType'] = 'GET';

        /* Ensure the Pin Value is Logical */
        $PinValue = str_replace('pin.pin.', 'pin.', "pin.$PinValue");

        // Prep the Action Record (if applicable)
        $sOK = $this->_preparePostAction();

        // Build and Run the SQL Query
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[POST_GUID]'    => sqlScrub($PostGUID),
                          '[PERSONA_GUID]' => sqlScrub($PersonaGUID),
                          '[IS_POST]'      => sqlScrub(BoolYN(($ReqType == 'post'))),
                          '[VALUE]'        => sqlScrub($PinValue),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/setPostPin.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // Return the Updated Post
        return $this->_getPostByGUID();
    }

    /**
     *  Function Records (or Resets) a Post Star for a Post/Persona combination
     */
    private function _setPostStar() {
        $PersonaGUID = NoNull($this->settings['persona_guid']);
        $PostGUID = NoNull($this->settings['post_guid'], NoNull($this->settings['guid'], $this->settings['PgSub1']));
        $ReqType = NoNull(strtolower($this->settings['ReqType']));

        /* Ensure we Have the requisite GUIDs */
        if ( mb_strlen($PersonaGUID) <= 30 ) { return $this->_setMetaMessage("Invalid Persona GUID Supplied", 400); }
        if ( mb_strlen($PostGUID) <= 30 ) { return $this->_setMetaMessage("Invalid Post GUID Supplied", 400); }
        $this->settings['guid'] = $PostGUID;
        $this->settings['ReqType'] = 'GET';

        // Prep the Action Record (if applicable)
        $sOK = $this->_preparePostAction();

        // Build and Run the SQL Query
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[POST_GUID]'    => sqlScrub($PostGUID),
                          '[PERSONA_GUID]' => sqlScrub($PersonaGUID),
                          '[VALUE]'        => sqlScrub(BoolYN(($ReqType == 'post'))),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/setPostStar.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // Return the Updated Post
        return $this->_getPostByGUID();
    }

    /**
     *  Function records (or Resets) as Post Point for a Post/Persona combination
     */
    private function _setPostPoints() {
        $PersonaGUID = NoNull($this->settings['persona_guid']);
        $PostGUID = NoNull($this->settings['post_guid'], NoNull($this->settings['guid'], $this->settings['PgSub1']));
        $ReqType = NoNull(strtolower($this->settings['ReqType']));
        $Points = nullInt($this->settings['points'], $this->settings['point']);

        /* Ensure we Have the requisite GUIDs */
        if ( mb_strlen($PersonaGUID) <= 30 ) { return $this->_setMetaMessage("Invalid Persona GUID Supplied", 400); }
        if ( mb_strlen($PostGUID) <= 30 ) { return $this->_setMetaMessage("Invalid Post GUID Supplied", 400); }
        $this->settings['guid'] = $PostGUID;
        $this->settings['ReqType'] = 'GET';

        // Verify the Points value is correct
        if ( $ReqType == 'delete' ) { $Points = 0; }
        if ( $Points > 1 ) { $Points = 1; }
        if ( $Points < 0 ) { $Points = 0; }

        // Prep the Action Record (if applicable)
        $sOK = $this->_preparePostAction();

        // Build and Run the SQL Query
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[POST_GUID]'    => sqlScrub($PostGUID),
                          '[PERSONA_GUID]' => sqlScrub($PersonaGUID),
                          '[VALUE]'        => nullInt($Points),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/setPostPoints.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // Return the Updated Post
        return $this->_getPostByGUID();
    }

    /** ********************************************************************* *
     *  MetaData Functions
     ** ********************************************************************* */
    /**
     *  Function Returns the MetaData for a Given Post.guid using mostly-consistent array structures.
     *      Dynamic keys can be used as well, so long as they're consistently applied.
     */
    private function _getPostMeta( $PostGUID ) {
        if ( mb_strlen(NoNull($PostGUID)) != 36 ) { return false; }
        $CacheKey = 'post-meta-' . $PostGUID . '-' . paddNumber($this->settings['_account_id']);

        $data = getCacheObject($CacheKey);
        if ( is_array($data) === false ) {
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                              '[POST_GUID]'  => sqlScrub($PostGUID),
                             );
            $sqlStr = readResource(SQL_DIR . '/posts/getPostMeta.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $data = array();
                foreach ( $rslt as $Row ) {
                    if ( YNBool($Row['is_visible']) ) {
                        $block = explode('_', $Row['key']);
                        if ( is_array($data[$block[0]]) === false ) {
                            $data[$block[0]] = $this->_getPostMetaArray($block[0]);
                        }
                        $data[$block[0]][$block[1]] = (is_numeric($Row['value']) ? nullInt($Row['value']) : NoNull($Row['value']));
                    }
                }

                /* If there's a Geo Array, Check if a StaticMap can be Provided */
                if ( array_key_exists('geo', $data) ) {
                    if ( $data['geo']['longitude'] !== false && $data['geo']['latitude'] !== false ) {
                        $data['geo']['staticmap'] = NoNull($this->settings['HomeURL']) . '/api/geocode/staticmap/' . round($data['geo']['latitude'], 5) . '/' . round($data['geo']['longitude'], 5);
                    }
                }

                /* If there's an Episode Array, Ensure the File value is prefixed with the CDN */
                if ( array_key_exists('episode', $data) ) {
                    $file = NoNull($data['episode']['file']);
                    $ext = getFileExtension($file);
                    $data['episode']['mime'] = getMimeFromExtension($ext);

                    if ( $file != '' && strpos($file, '//') === false ) {
                        $cdnUrl = getCdnUrl();
                        $data['episode']['file'] = $cdnUrl . $file;
                    }
                }

                /* If we have data, save it */
                if ( is_array($data) && count($data) > 0 ) { setCacheObject($CacheKey, $data); }
            }
        }

        /* If we have data, return it. Otherwise, unhappy boolean */
        if ( is_array($data) && count($data) > 0 ) { return $data; }
        return false;
    }

    private function _getPostMetaArray( $KeyPrefix ) {
        $CleanKey = strtolower($KeyPrefix);
        switch ( $CleanKey ) {
            case 'geo':
                return array( 'longitude'   => false,
                              'latitude'    => false,
                              'altitude'    => false,
                              'description' => false,
                              'staticmap'   => false
                             );
                break;

            case 'source':
                return array( 'url'     => false,
                              'title'   => false,
                              'summary' => false,
                              'author'  => false
                             );
                break;

            default:
                return array();
        }
    }

    /**
     *  Function Collects a List of Geo-Markers for a Post or an Unhappy Boolean
     */
    private function _getPostMarkers( $PostGuid ) {
        if ( strlen(NoNull($PostGuid)) != 36 ) { return false; }

        /* Load the Location Class if Required */
        if ( $this->markers === false ) {
            require_once(LIB_DIR . '/location.php');
            $this->markers = new Location( $this->settings );
        }

        $rslt = $this->markers->getMarkerList( $PostGuid );
        if ( is_array($rslt) ) { return $rslt; }
        return false;
    }

    /**
     *  Function Looks for Hashtags in a Post and Returns a Comma-Separated List
     */
    private function _extractPostTags( $Text ) {
        $rVal = strip_tags($Text);
        $words = explode(' ', " $rVal ");
        $hh = array();

        foreach ( $words as $word ) {
            $clean_word = NoNull(strip_tags($word));
            $hash = '';

            if ( NoNull(substr($clean_word, 0, 1)) == '#' ) {
                $hash_scrub = array('#', '?', '.', ',', '!', '<', '>');
                $hash = NoNull(str_replace($hash_scrub, '', $clean_word));

                if ($hash != '' && in_array($hash, $hh) === false ) { $hh[] = $hash; }
            }
        }

        // Return the List of Hashes
        return implode(',', $hh);
    }

    /**
     *  Function Gets the Post.id Value based on the GUID supplied.
     *  Notes:
     *      * If the account does not have Read permissions to the channel, then the Post.id Value cannot be returned
     *      * If the Post has a higher Privacy setting, the Account requesting the Post must have Read permissions
     *      * If the HTTP Request Type is an Edit action, the Account must own the Persona the Post was Saved under
     */
    private function _getPostIDFromGUID( $Guid ) {
        if ( mb_strlen(NoNull($Guid)) != 36 ) { return 0; }
        $edits = array('post', 'put', 'delete');

        // Construct the SQL Query
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[POST_GUID]'    => sqlScrub($Guid),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/getPostIDFromGuid.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $ReqType = NoNull(strtolower($this->settings['ReqType']));
            foreach ( $rslt as $Row ) {
                if ( YNBool($Row['can_read']) && nullInt($Row['post_id']) > 0 ) {
                    if ( in_array($ReqType, $edits) && YNBool($Row['can_write']) === false ) { return 0; }
                    return nullInt($Row['post_id']);
                }
            }
        }

        // If We're Here, No Post Was Found. Return Zero.
        return 0;
    }

    /**
     *  Function Deletes a Post and all of its Related Details from the Database
     */
    private function _deletePost() {
        $PostGUID = NoNull($this->settings['PgSub1'], $this->settings['post_guid']);
        $ChannelGUID = false;
        $isOK = false;

        // Ensure we Have a GUID
        if ( mb_strlen($PostGUID) < 30 ) { $this->_setMetaMessage("Invalid Post GUID Supplied", 400); return false; }

        // Remove the Record from the Database
        $TokenGUID = NoNull($this->settings['_token_guid']);
        $TokenID = nullInt($this->settings['_token_id']);

        if ( $TokenID > 0 ) {
            $ReplStr = array( '[POST_GUID]'  => sqlScrub($PostGUID),
                              '[TOKEN_ID]'   => nullInt($TokenID),
                              '[TOKEN_GUID]' => sqlScrub($TokenGUID),
                             );
            $sqlStr = prepSQLQuery("CALL DeletePost([TOKEN_ID], '[TOKEN_GUID]', '[POST_GUID]');", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $ChannelGUID = NoNull($Row['channel_guid']);
                    $rslt = array( 'post_id' => nullInt($Row['post_id']) );
                }
            }
        }

        // Return an Array of Data
        return array( 'post_guid'    => $PostGUID,
                      'channel_guid' => $ChannelGUID,

                      'result' => $rslt,
                      'sok'    => $isOK,
                     );
    }

    /** ********************************************************************* *
     *  Data Validation Functions
     ** ********************************************************************* */
    /**
     *  Function Determines if the Variable Set Supplied is Valid for the Requirements of a Given Type
     *      and Returns an Array of Information or a Single, Unhappy Boolean
     */
    private function _validateWritePostData() {
        $ChannelGUID = NoNull($this->settings['channel_guid'], $this->settings['_channel_guid']);
        $PersonaGUID = NoNull($this->settings['persona_guid'], $this->settings['_persona_guid']);
        $Title = NoNull($this->settings['post_title'], $this->settings['title']);
        $CanonURL = NoNull($this->settings['canonical_url'], $this->settings['post_url']);
        $ReplyTo = NoNull($this->settings['post_reply_to'], $this->settings['reply_to']);
        $PostAuthor = NoNull($this->settings['post_author'], $this->settings['author']);
        $PostSlug = NoNull($this->settings['post_slug'], $this->settings['slug']);
        $PostType = NoNull($this->settings['post_type'], NoNull($this->settings['type'], 'post.draft'));
        $PostSummary = NoNull($this->settings['post_summary'], $this->settings['summary']);
        $Privacy = NoNull($this->settings['post_privacy'], $this->settings['privacy']);
        $PublishUnix = nullInt($this->settings['post_publish_unix'], $this->settings['publish_unix']);
        $PublishAt = NoNull($this->settings['post_publish_at'], $this->settings['publish_at']);
        $ExpiresAt = NoNull($this->settings['post_expires_at'], $this->settings['expires_at']);
        $PostTags = NoNull($this->settings['post_tags'], $this->settings['tags']);
        $PostGUID = NoNull($this->settings['post_guid'], NoNull($this->settings['guid'], $this->settings['PgSub1']));
        $PostID = $this->_getPostIDFromGUID($PostGUID);

        /* Ensure we have the post text */
        $opts = array( 'post_text', 'text', 'post_comment', 'content', 'post_comment', 'comment', 'value');
        $Value = '';
        foreach ( $opts as $opt ) {
            if ( array_key_exists($opt, $this->settings) ) {
                if ( mb_strlen($Value) <= 0 ) { $Value = NoNull($this->settings[$opt]); }
            }
        }

        /* More Elements */
        $ParentID = 0;
        $ThreadID = 0;

        /* Additional Meta */
        $SourceURL = NoNull($this->settings['source_url'], NoNull($this->settings['src_url'], $this->settings['source']));
        $SourceTitle = NoNull($this->settings['source_title'], $this->settings['src_title']);

        /* Clean up the Post Content a bit */
        $ReplStr = array( "\n\n\n\n" => "\n\n" );
        for ( $i = 0; $i < 5; $i++ ) {
            $Value = str_replace(array_keys($ReplStr), array_values($ReplStr), $Value);
        }
        $Value = NoNull($Value);

        /* Grab the Unique Words in the Post */
        $UniqueWords = '';
        $uWords = UniqueWords($Value);
        if ( is_array($uWords) && count($uWords) > 0 ) {
            $UniqueWords = implode(',', $uWords);
        }

        $GeoLong = NoNull($this->settings['geo_longitude'], $this->settings['geo_long']);
        $GeoLat = NoNull($this->settings['geo_latitude'], $this->settings['geo_lat']);
        $GeoAlt = NoNull($this->settings['geo_altitude'], $this->settings['geo_alt']);

        // Ensure the Latitude & Longitude is Semi-Accurate
        if ( nullInt($GeoLong) != 0 ) {
            $GeoLong = nullInt($GeoLong);
            if ( $GeoLong > 180 || $GeoLong < -180 ) { $GeoLong = ''; }
        }
        if ( nullInt($GeoLat) != 0 ) {
            $GeoLat = nullInt($GeoLat);
            if ( $GeoLat > 90 || $GeoLat < -90 ) { $GeoLat = ''; }
        }

        $GeoFull = NoNull($this->settings['post_geo'], $this->settings['geo']);
        if ( $GeoFull != '' ) {
            $coords = explode(',', $GeoFull);
            if ( nullInt($coords[0]) != 0 && nullInt($coords[1]) != 0 ) {
                $GeoLat = nullInt($coords[0]);
                $GeoLong = nullInt($coords[1]);
                if ( nullInt($coords[2]) != 0 ) { $GeoAlt = nullInt($coords[2]); }
            }

            if ( nullInt($GeoLong) != 0 && nullInt($GeoLat) == 0 || nullInt($GeoLong) == 0 && nullInt($GeoLat) != 0 ) {
                $GeoLong = '';
                $GeoLat = '';
                $GeoAlt = '';
            }
            if ( nullInt($GeoLong) != 0 && nullInt($GeoLat) != 0 ) { $GeoFull = ''; }
        }

        /* Is this a podcast? */
        $AudioExplicit = NoNull($this->settings['explicit'], $this->settings['audio_explicit']);
        $AudioSummary = NoNull($this->settings['episode_summary'], $this->settings['audio_summary']);
        $AudioEpNo = NoNull($this->settings['episode_number'], $this->settings['audiofile_epno']);
        $AudioTime = NoNull($this->settings['episode_time'], $this->settings['audiofile_time']);
        $AudioFile = NoNull($this->settings['episode_url'], $this->settings['audiofile_url']);

        if ( $AudioFile !== '' ) {
            if ( in_array(strtolower($AudioExplicit), array('c', 'clean', 'n', 'no', 'y', 'yes')) === false ) { $AudioExplicit = ''; }

            $cdnUrl = getCdnUrl();
            $AudioFile = str_replace($cdnUrl, '', $AudioFile);

            if ( strpos($AudioTime, ':') !== false ) {
                $timeChk = array_reverse(explode(':', '00:00:00' . $AudioTime));
                $AudioTime = '';
                $aCnt = 0;
                foreach ( $timeChk as $tc ) {
                    if ( nullInt($tc) >= 0 ) {
                        if ( $aCnt < 3 ) {
                            if ( $AudioTime != '' ) { $AudioTime = ':' . $AudioTime; }
                            $AudioTime = substr('00' . nullInt($tc), -2)  . $AudioTime;
                            $aCnt++;
                        }
                    }
                }

            } else {
                $AudioTime = '';
            }

        } else {
            $AudioExplicit = '';
            $AudioSummary = '';
            $AudioTime = '';
            $AudioEpNo = '';
        }

        /* Check the Post Text for Additionals */
        $hash_list = $this->_extractPostTags($Value);
        if ( $hash_list != '' ) {
            if ( $PostTags != '' ) { $PostTags .= ','; }
            $PostTags .= $hash_list;
        }

        /* Token Definition */
        $TokenGUID = '';
        $TokenID = 0;
        $isValid = true;

        /* Get the Token Information */
        if ( NoNull($this->settings['token']) != '' ) {
            $data = explode('_', NoNull($this->settings['token']));
            if ( count($data) == 3 ) {
                if ( $data[0] == str_replace('_', '', TOKEN_PREFIX) ) {
                    $TokenGUID = NoNull($data[2]);
                    $TokenID = alphaToInt($data[1]);
                }
            }
        }

        /* Validate the Requisite Data */
        if ( mb_strlen($ChannelGUID) != 36 ) { $this->_setMetaMessage("Invalid Channel GUID Supplied", 400); $isValid = false; }
        if ( mb_strlen($PersonaGUID) != 36 ) { $this->_setMetaMessage("Invalid Persona GUID Supplied", 400); $isValid = false; }
        if ( $PostType == '' ) { $PostType = 'post.draft'; }
        if ( $Privacy == '' ) { $Privacy = 'visibility.public'; }

        /* Ensure the Dates are Set to UTC */
        if ( strtotime($PublishAt) === false ) { $PublishAt = ''; }
        if ( strtotime($ExpiresAt) === false ) { $ExpiresAt = ''; }
        if ( $PublishAt != '' ) { $PublishAt = $this->_convertTimeToUTC($PublishAt); }
        if ( $ExpiresAt != '' ) { $ExpiresAt = $this->_convertTimeToUTC($ExpiresAt); }
        if ( $PublishAt == '' && $PublishUnix > 1000 ) { $PublishAt = date("Y-m-d H:i:s", $PublishUnix); }
        if ( $PublishAt != '' && $PublishUnix <= 1000 ) { $PublishUnix = strtotime($PublishAt); }

        /* Ensure the Expiration Is Valid (If It Exists) */
        if ( strtotime($ExpiresAt) !== false && strtotime($ExpiresAt) > time() ) {
            if ( strtotime($ExpiresAt) < strtotime($PublishAt) ) {
                $this->_setMetaMessage("The Post Object Cannot Expire Before it is Published", 400); $isValid = false;
            }
        } else {
            $ExpiresAt = '';
        }

        switch ( strtolower($PostType) ) {
            case 'post.quotation':
                if ( mb_strlen($Value) <= 0 ) { $this->_setMetaMessage("Please Supply Some Text", 400); $isValid = false; }
                if ( mb_strlen($SourceURL) <= 0 ) { $this->_setMetaMessage("Please Supply a Source URL", 400); $isValid = false; }
                break;

            case 'post.bookmark':
                if ( mb_strlen($SourceURL) <= 0 ) { $this->_setMetaMessage("Please Supply a Source URL", 400); $isValid = false; }
                break;

            case 'post.article':
                if ( $PostSlug == '' ) {
                    $SafeSlug = $this->_getSafeTagSlug($Title);

                    /* Check if the Slug is Unique */
                    $PostSlug = $this->_checkUniqueSlug($ChannelGUID, $PostGUID, $SafeSlug);

                    /* If the Slug is Not Empty, Set the Canonical URL Value */
                    if ( $PostSlug != '' ) {
                        $SlugPrefix = '';
                        if ( nullInt($PublishUnix) >= strtotime('1975-01-01 00:00:00') ) {
                            $SlugPrefix = date('Y/m/d', $PublishUnix);
                        }

                        $SafeURL = NoNull('/' . NoNull($SlugPrefix, 'article') . "/$SafeSlug");
                        $CanonURL = $this->_checkUniqueCanonUrl($ChannelGUID, $PostGUID, $SafeURL);
                        if ( $CanonURL == '' ) { $CanonURL = NoNull('/' . NoNull($SlugPrefix, 'article') . "/$PostSlug"); }
                    }
                }
                if ( mb_strlen($Value) <= 0 ) { $this->_setMetaMessage("Please Supply Some Text", 400); $isValid = false; }
                break;

            case 'post.page':
                if ( $PostSlug == '' ) {
                    $PostSlug = $this->_getSafeTagSlug($Title);

                    // Check if the Slug is Unique
                    $PostSlug = $this->_checkUniqueSlug($ChannelGUID, $PostGUID, $PostSlug);

                    // If the Slug is Not Empty, Set the Canonical URL Value
                    if ( $PostSlug != '' ) { $CanonURL = NoNull("/$PostSlug", "/$PostGUID"); }
                }
                if ( mb_strlen($Value) <= 0 ) { $this->_setMetaMessage("Please Supply Some Text", 400); $isValid = false; }
                break;

            case 'post.location':
                if ( nullInt($GeoLong) == 0 && nullInt($GetLat) == 0 ) {
                    $this->_setMetaMessage("Please Provide an accurate Latitude & Longtitude.", 400); $isValid = false;
                }
                break;

            case 'post.draft':
            case 'post.note':
                if ( mb_strlen($Value) <= 0 ) { $this->_setMetaMessage("Please Supply Some Text", 400); $isValid = false; }
                break;

            default:
                $this->_setMetaMessage("Unknown Post Type: $PostType", 400);
                $isValid = false;
        }

        /* If Something Is Wrong, Return an Unhappy Boolean */
        if ( $isValid !== true ) { return false; }

        /* Can we Identify a ParentID and a Thread Based on the ReplyTo? (If Applicable) */
        if ( mb_strlen($ReplyTo) >= 10 ) {
            $guid = '';
            if ( strpos($ReplyTo, '/') >= 0 ) {
                $ups = explode('/', $ReplyTo);
                for ( $i = (count($ups) - 1); $i >= 0; $i-- ) {
                    if ( mb_strlen(NoNull($ups[$i])) == 36 ) { $guid = NoNull($ups[$i]); }
                }

            } else {
                if ( mb_strlen($ReplyTo) == 36 ) { $guid = $ReplyTo; }
            }

            // If We Have a GUID, Let's Check If It's a 10C Object
            $ReplStr = array( '[POST_GUID]' => sqlScrub($guid) );
            $sqlStr = readResource(SQL_DIR . '/posts/chkPostParent.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $ParentID = nullInt($Row['parent_id']);
                    $ThreadID = nullInt($Row['thread_id']);
                    $ReplyTo = NoNull($Row['post_url']);
                }
            }
        }

        // If We're Here, Build the Return Array
        return array( 'channel_guid'  => $ChannelGUID,
                      'persona_guid'  => $PersonaGUID,
                      'token_guid'    => $TokenGUID,
                      'token_id'      => $TokenID,

                      'title'         => strip_tags($Title),
                      'value'         => $this->_cleanContent($Value),

                      'canonical_url' => strip_tags($CanonURL),
                      'reply_to'      => strip_tags($ReplyTo),

                      'slug'          => $PostSlug,
                      'type'          => $PostType,
                      'privacy'       => $Privacy,

                      'publish_at'    => $PublishAt,
                      'expires_at'    => $ExpiresAt,

                      'words'         => $UniqueWords,
                      'tags'          => $PostTags,
                      'meta'          => array( 'source_url'       => strip_tags($SourceURL),
                                                'source_title'     => strip_tags($SourceTitle),
                                                'geo_latitude'     => strip_tags($GeoLat),
                                                'geo_longitude'    => strip_tags($GeoLong),
                                                'geo_altitude'     => strip_tags($GeoAlt),
                                                'geo_description'  => strip_tags($GeoFull),

                                                'post_summary'     => strip_tags($PostSummary),
                                                'post_author'      => strip_tags($PostAuthor),

                                                'episode_explicit' => strip_tags($AudioExplicit),
                                                'episode_summary'  => $this->_cleanContent($AudioSummary),
                                                'episode_number'   => strip_tags($AudioEpNo),
                                                'episode_file'     => strip_tags($AudioFile),
                                                'episode_time'     => strip_tags($AudioTime),
                                               ),

                      'thread_id'     => $ThreadID,
                      'parent_id'     => $ParentID,
                      'post_id'       => $PostID,
                     );
    }

    /**
     *  Function Tries Like Heck to Sanitize the Content of a Post to Fit Expectations
     */
    private function _cleanContent( $text ) {
        $ReplStr = array( '//@' => '// @', '<p>' => '', '</p>' => "\r\n",
                          '<strong>' => '**', '</strong>' => '**', '<b>' => '**', '</b>' => '**', '<em>' => '*', '</em>' => '*',
                         );

        for ( $i = 0; $i < 5; $i++ ) {
            $text = NoNull(str_replace(array_keys($ReplStr), array_values($ReplStr), $text));
        }

        /* Remove Inline Styling */
        $text = preg_replace('/(<[^>]*) style=("[^"]+"|\'[^\']+\')([^>]*>)/i', '$1$3', $text);

        /* Try to Handle Inline HTML */
        $text = NoNull(str_replace('<', '&lt;', $text));
        $ReplStr = array( '&lt;section' => '<section', '&lt;iframe' => '<iframe',
                          '&lt;str' => '<str', '&lt;del' => '<del', '&lt;pre' => '<pre',
                          '&lt;h1' => '<h1', '&lt;h2' => '<h2', '&lt;h3' => '<h3',
                          '&lt;h4' => '<h4', '&lt;h5' => '<h5', '&lt;h6' => '<h6',
                          '&lt;ol' => '<ol', '&lt;ul' => '<ul', '&lt;li' => '<li',
                          '&lt;b' => '<b', '&lt;i' => '<i', '&lt;u' => '<u',
                          '&lt;kbd' => '<kbd', '&lt;/kbd' => '</kbd', '<kbd> ' => '&lt;kbd> ',
                          '`<kbd>`' => '`&lt;kbd>`', '`<kbd></kbd>`' => '`&lt;kbd>&lt;/kbd>`',
                         );
        $text = NoNull(str_replace(array_keys($ReplStr), array_values($ReplStr), $text));

        // Return the Scrubbed Text
        return $text;
    }

    /** ********************************************************************* *
     *  Web-Presentation Functions
     ** ********************************************************************* */
    /**
     *  Function Returns a Tag Key Should One Be Requested
     */
    private function _getTagKey() {
        $valids = array('tag');
        if ( in_array(strtolower(NoNull($this->settings['PgRoot'])), $valids) ) {
            return NoNull($this->settings['PgSub1']);
        }
        return '';
    }

    /**
     *  Function Determines the Current Page URL
     */
    private function _getCanonicalURL() {
        if ( NoNull($this->settings['PgRoot']) == '' ) { return ''; }

        $rVal = '/' . NoNull($this->settings['PgRoot']);
        for ( $i = 1; $i <= 9; $i++ ) {
            if ( NoNull($this->settings['PgSub' . $i]) != '' ) {
                $rVal .= '/' . NoNull($this->settings['PgSub' . $i]);
            } else {
                return $rVal;
            }
        }

        // Return the Canonical URL
        return $rVal;
    }
    private function _getPageNumber() {
        $Page = (nullInt($this->settings['page'], 1) - 1);
        if ( $Page <= 0 ) {
            if ( NoNull($this->settings['PgRoot']) == 'page' ) {
                $Page = (nullInt($this->settings['PgSub1'], 1) - 1);
            }
        }

        // Return the Requested Page Number
        return $Page;
    }

    /**
     *  Function Returns an HTML List of Popular Posts
     *  Note: this caches data for 60 minutes before refreshing
     */
    private function _getPopularPosts() {
        $CacheKey = 'popular-' . paddNumber($this->settings['site_id']) . '.' . nullInt($this->settings['pops_count'], 9) . '-' . date('Ymdh');
        $html = '';

        $data = getCacheObject($CacheKey);
        if ( is_array($data) && mb_strlen($data['html']) > 10 ) { return $data['html']; }

        /* If we're here, we need to construct the Popular Posts listing */
        $ReplStr = array( '[SITE_ID]' => nullInt($this->settings['site_id']),
                          '[COUNT]'   => nullInt($this->settings['pops_count'], 9),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/getPopularPosts.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $SiteUrl = NoNull($this->settings['HomeURL']);

            foreach ( $rslt as $Row ) {
                $PublishAt = date("Y-m-d\TH:i:s\Z", strtotime($Row['publish_at']));

                if ( $html != '' ) { $html .= "\r\n"; }
                $html .= tabSpace(4) . '<div class="col-lg-4 col-md-4 col-sm-4">' . "\r\n" .
                         tabSpace(5) . '<div class="page-footer__recent-post">' . "\r\n" .
                         tabSpace(6) . '<a href="' . $SiteUrl . NoNull($Row['canonical_url']) . '" data-views="' . nullInt($Row['hits']) . '">' . NoNull($Row['title'], $Row['type']) . '</a>' . "\r\n" .
                         tabSpace(6) . '<div class="page-footer__recent-post-date">' . "\r\n" .
                         tabSpace(7) . '<span class="dt-published" datetime="' . $PublishAt . '" data-dateunix="' . strtotime($Row['publish_at']) . '">' . NoNull($PublishAt, $Row['publish_at']) . '</span>' . "\r\n" .
                         tabSpace(6) . '</div>' . "\r\n" .
                         tabSpace(5) . '</div>' . "\r\n" .
                         tabSpace(4) . '</div>';
            }

            /* Save the HTML to the cache if it appears valid and return it */
            if ( mb_strlen($html) > 10 ) {
                setCacheObject($CacheKey, array('html' => $html));
                return $html;
            }
        }

        /* If we're here, there's nothing. */
        return '';
    }

    /**
     *  Function Determines the Pagination for the Page
     */
    private function _getPagePagination( $data ) {
        $Excludes = array('write', 'settings', 'account');
        $CanonURL = $this->_getCanonicalURL();
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        $Count = nullInt($this->settings['count'], 10);
        $Page = $this->_getPageNumber();
        $Page++;
        $html = '';

        // If We're Writing a New Post, Return a Different Set of Data
        if ( NoNull($this->settings['PgRoot']) == 'new' && NoNull($this->settings['PgSub1']) == '' ) {
            return '';
        }

        // Determine the Name of the Cache File (if Required)
        $CacheFile = substr(str_replace('/', '-', NoNull($CanonURL, '/home')), 1) . '-' . $Page . '-' . NoNull($data['site_version'], 'ver0');
        $CacheFile = str_replace('--', '-', $CacheFile);

        if ( defined('ENABLE_CACHING') === false ) { define('ENABLE_CACHING', 0); }
        if ( nullInt(ENABLE_CACHING) == 1 ) { $html = readCache($this->settings['site_id'], $CacheFile); }
        if ( $html !== false && $html != '' ) { return $html; }
        $tObj = strtolower(str_replace('/', '', $CanonURL));
        $rslt = false;

        /* Collect the Pagination Info */
        if ( in_array($PgRoot, $Excludes) === false ) {
            $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                              '[SITE_TOKEN]'   => sqlScrub(NoNull($this->settings['site_token'])),
                              '[SITE_GUID]'    => sqlScrub($data['site_guid']),
                              '[CANON_URL]'    => sqlScrub($CanonURL),
                              '[PGROOT]'       => sqlScrub($PgRoot),
                              '[OBJECT]'       => sqlScrub($tObj),
                              '[PGSUB1]'       => sqlScrub($this->settings['PgSub1']),
                              '[SITE_VERSION]' => nullInt($data['updated_unix']),
                              '[APP_VERSION]'  => sqlScrub(APP_VER),
                             );
            $cacheFile = 'site-' . substr('00000000' . $this->settings['site_id'], -8) . '-' . sha1(serialize($ReplStr));
            $rslt = getCacheObject($cacheFile);
            if ( is_array($rslt) === false ) {
                $sqlStr = prepSQLQuery("CALL GetSitePagination([ACCOUNT_ID], '[SITE_GUID]', '[SITE_TOKEN]', '[CANON_URL]', '[PGROOT]', '[OBJECT]', '[PGSUB1]');", $ReplStr);
                $rslt = doSQLQuery($sqlStr);
                setCacheObject($cacheFile, $rslt);
            }
        }

        // At this point, we should have data. Let's build some pagination
        if ( is_array($rslt) ) {
            $max = 0;
            $cnt = 0;

            foreach ( $rslt as $Row ) {
                if ( YNBool($Row['is_exact']) === false ) {
                    $cnt = nullInt($Row['post_count']);
                }
            }
            while ( $cnt > 0 ) {
                $cnt -= $Count;
                if ( $cnt > 0 ) { $max++; }
            }
            $max++;

            // If the Maximum Page is Greater Than 1, Build a Pagination Matrix
            if ( $max > 1 ) {
                $SiteUrl = NoNull($this->settings['HomeURL']);
                if ( $PgRoot == $tObj ) { $SiteUrl .= "/$PgRoot"; }
                if ( $Page <= 0 ) { $Page = 1; }
                $cnt = 1;

                if ( $Page > 1 ) {
                    if ( $html != '' ) { $html .= "\r\n"; }
                    $html .= tabSpace(6) .
                             '<li class="blog-pagination__item">' .
                                '<a href="' . $SiteUrl . '?page=' . ($Page - 1) . '"><i class="fa fa-backward"></i></a>' .
                             '</li>';
                }

                $min_idx = ($Page - 4);
                if ( $min_idx < 1 ) { $min_idx = 1; }
                if ( $Page > 7 ) { $min_idx - 4; }
                $max_idx = ($min_idx + 8);
                $min_dot = false;
                $max_dot = false;

                while ( $cnt <= $max ) {
                    if ( ($cnt >= $min_idx && $cnt <= $max_idx) || $cnt == 1 || $cnt == $max ) {
                        if ( $html != '' ) { $html .= "\r\n"; }
                        if ( $cnt == $Page ) {
                            $html .= tabSpace(6) .
                                     '<li class="blog-pagination__item blog-pagination__item--active">' .
                                        '<a>' . number_format($cnt, 0) . '</a>' .
                                     '</li>';

                        } else {
                            $html .= tabSpace(6) .
                                     '<li class="blog-pagination__item">' .
                                        '<a href="' . $SiteUrl . (($cnt > 1) ? '?page=' . $cnt : '') . '">' . number_format($cnt, 0) . '</a>' .
                                     '</li>';
                        }
                    }
                    if ( $Page > 6 && $cnt < $min_idx && $min_idx > 1 && $min_dot === false ) {
                        if ( $html != '' ) { $html .= "\r\n"; }
                        $html .= tabSpace(6) .
                                 '<li class="blog-pagination__item">' .
                                    '<a><i class="fa fa-ellipsis-h"></i></a>' .
                                 '</li>';
                        $min_dot = true;
                    }
                    if ( $cnt > $max_idx && $max_idx < $max && $max_dot === false ) {
                        if ( $html != '' ) { $html .= "\r\n"; }
                        $html .= tabSpace(6) .
                                 '<li class="blog-pagination__item">' .
                                    '<a><i class="fa fa-ellipsis-h"></i></a>' .
                                 '</li>';
                        $max_dot = true;
                    }
                    $cnt++;
                }

                if ( $Page < $max ) {
                    if ( $html != '' ) { $html .= "\r\n"; }
                    $html .= tabSpace(6) .
                             '<li class="blog-pagination__item">' .
                                '<a href="' . $SiteUrl . '?page=' . ($Page + 1) . '"><i class="fa fa-forward"></i></a>' .
                             '</li>';
                }

                // Format the Complete HTML
                $html = "\r\n" .
                        tabSpace(4) . '<nav class="blog-pagination">' . "\r\n" .
                        tabSpace(5) . '<ul class="blog-pagination__items">' . "\r\n" .
                        $html . "\r\n" .
                        tabSpace(5) . '</ul>' . "\r\n" .
                        tabSpace(4) . '</nav>';

                // Save the File to Cache if Required
                if ( nullInt(ENABLE_CACHING) == 1 ) { saveCache($this->settings['site_id'], $CacheFile, $html); }

                // Return the HTML
                return $html;
            }
        }

        // If We're Here, There's No Pagination Required
        return '';
    }

    private function _getPageJSON( $data ) {
        $Excludes = array( 'account', 'settings', 'write', 'new' );
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        $Count = nullInt($this->settings['count'], 10);
        $Page = $this->_getPageNumber() * $Count;
        $CanonURL = $this->_getCanonicalURL();
        $TagKey = $this->_getTagKey();
        $tObj = strtolower(str_replace('/', '', $CanonURL));

        // If We're Writing a New Post, Return a Different Set of Data
        if ( in_array($PgRoot, $Excludes) && NoNull($this->settings['PgSub1']) == '' ) { return ''; }

        if ( $Count > 75 ) { $Count = 75; }
        if ( $Count <= 0 ) { $Count = 10; }
        if ( $Page > 10000 ) { $Page = 10000; }
        if ( $page < 0 ) { $Page = 0; }

        // Construct the SQL Query
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[SITE_TOKEN]' => sqlScrub(NoNull($this->settings['site_token'])),
                          '[SITE_GUID]'  => sqlScrub($data['site_guid']),
                          '[CANON_URL]'  => sqlScrub($CanonURL),
                          '[TAG_KEY]'    => sqlScrub($TagKey),
                          '[OBJECT]'     => sqlScrub($tObj),
                          '[COUNT]'      => nullInt($Count),
                          '[PAGE]'       => nullInt($Page),
                         );

        $sqlStr = prepSQLQuery("CALL GetPagePosts([ACCOUNT_ID], '[SITE_GUID]', '[CANON_URL]', '[OBJECT]', '[TAG_KEY]', '[SITE_TOKEN]', [COUNT], [PAGE]);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $posts = $this->_parsePostResultSet($rslt);
            if ( is_array($posts) ) { return $posts; }
        }

        // If We're Here, There's Nothing
        return array();
    }

    private function _getPageHTML( $data ) {
        $Excludes = array( 'account', 'settings', 'write', 'new' );
        $PgRoot = strtolower(NoNull($this->settings['PgRoot']));
        $Count = nullInt($this->settings['count'], 10);
        $Page = $this->_getPageNumber();
        $CanonURL = $this->_getCanonicalURL();
        $TagKey = $this->_getTagKey();
        $tObj = strtolower(str_replace('/', '', $CanonURL));

        // If We're Writing a New Post, Return a Different Set of Data
        if ( in_array($PgRoot, $Excludes) && NoNull($this->settings['PgSub1']) == '' ) { return ''; }

        if ( $Count > 75 ) { $Count = 75; }
        if ( $Count <= 0 ) { $Count = 10; }
        if ( $Page > 50000 ) { $Page = 50000; }
        if ( $page < 0 ) { $Page = 0; }

        // Ensure the Page is set to MySQL's Preference
        $Page = $Page * $Count;

        // Construct the SQL Query
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[SITE_TOKEN]' => sqlScrub(NoNull($this->settings['site_token'])),
                          '[SITE_GUID]'  => sqlScrub($data['site_guid']),
                          '[CANON_URL]'  => sqlScrub($CanonURL),
                          '[TAG_KEY]'    => sqlScrub($TagKey),
                          '[OBJECT]'     => sqlScrub($tObj),
                          '[COUNT]'      => nullInt($Count),
                          '[PAGE]'       => nullInt($Page),
                         );

        // If We're Returning a List of Archives, Do That
        if ( in_array(NoNull($this->settings['PgRoot']), array('archive', 'archives')) ) {
            return $this->_getSiteArchives( $data['site_guid'] );
        }

        $sqlStr = prepSQLQuery("CALL GetPagePosts([ACCOUNT_ID], '[SITE_GUID]', '[CANON_URL]', '[OBJECT]', '[TAG_KEY]', '[SITE_TOKEN]', [COUNT], [PAGE]);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $posts = $this->_parsePostResultSet($rslt);

            if ( is_array($posts) && count($posts) > 0 ) {
                $html = '';

                foreach ( $posts as $post ) {
                    $single = (count($posts) == 1) ? true : false;
                    $el = $this->_buildHTMLElement($data, $post, $single);
                    if ( $el != '' ) {
                        $postClass = NoNull($post['class']);
                        if ( $postClass != '' ) { $postClass .= ' '; }

                        // Determine the Template File
                        $flatFile = THEME_DIR . '/' . $data['location'] . '/flats/' . $post['type'] . '.html';

                        /* Is there something that should appear in the page description? */
                        if ( count($posts) == 1 ) {
                            $PostSummary = '';
                            if ( array_key_exists('meta', $post) && is_array($post['meta']) ) {
                                if ( array_key_exists('post', $post['meta']) && is_array($post['meta']['post']) ) {
                                    $PostSummary = NoNull($post['meta']['post']['summary']);
                                    if ( $PostSummary != '' ) { $GLOBALS['post_summary'] = $PostSummary; }
                                }
                            }
                        }

                        if ( !file_exists($flatFile) ) {
                            $postClass .= 'post-entry post ' . str_replace('.', '-', $post['type']);
                            $el = tabSpace(4) . '<li class="' . $postClass  . '" data-guid="' . NoNull($post['guid']) . '" data-pin="' . NoNull($post['attributes']['pin']) . '" data-starred="' . BoolYN($post['attributes']['starred']) . '" data-owner="' . BoolYN($post['can_edit']) . '">' . "\r\n" .
                                                "$el\r\n" .
                                  tabSpace(4) . '</li>';
                        }
                        if ( $html != '' ) { $html .= "\r\n"; }
                        $html .= $el;
                    }
                }

                // Add the Pagination (If Required)
                if ( $html != '' ) { $html .= $this->_getPagePagination($data); }

                // If There are No Posts, Show a Friendly Message
                if ( NoNull($html) == '' ) { $html = "There Is No HTML Here"; }

                // Return the Completed HTML
                return $html;
            }
        }

        $ReplStr = array();
        foreach ( $this->strings as $Key=>$Value ) {
            $ReplStr["[$Key]"] = $Value;
        }

        // If We're Here, There's Nothing to Show (Present Welcome Page or 404)
        $ReqPage = ($data['has_content']) ? 'page-404.html' : 'post.welcome.html';
        if ( $data['has_content'] ) { $this->settings['status'] = 404; }
        $flatFile = THEME_DIR . '/' . $data['location'] . '/flats/' . $ReqPage;
        if ( !file_exists($flatFile) ) { $flatFile = FLATS_DIR . '/templates/' . $ReqPage; }
        return readResource($flatFile, $ReplStr);
    }

    private function _getSiteArchives( $SiteGuid ) {
        if ( mb_strlen(NoNull($SiteGuid)) != 36 ) { return ''; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[SITE_TOKEN]' => sqlScrub(NoNull($this->settings['site_token'])),
                          '[SITE_GUID]'  => sqlScrub($SiteGuid),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/getArchives.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $SiteUrl = NoNull($this->settings['HomeURL']);
            $cnt = array();
            $html = '';

            foreach ( $rslt as $Row ) {
                $icon = '';
                $priv = '';
                $type = strtolower(NoNull($Row['type']));

                if ( array_key_exists($type, $cnt) === false ) { $cnt[$type] = 0; }
                $cnt[$type]++;

                switch ( $type ) {
                    case 'post.quotation':
                        $icon = '<i class="fa fa-quote-left"></i> ';
                        break;

                    case 'post.bookmark':
                        $icon = '<i class="fa fa-bookmark"></i> ';
                        break;

                    default:
                        $icon = '<i class="fa fa-newspaper-o"></i> ';
                }

                $meta = ' data-guid="' . NoNull($Row['guid']) . '"' .
                        ' data-type="' . NoNull($Row['type']) . '"' .
                        ' data-dateunix="' . strtotime($Row['publish_at']) . '"' .
                        ' data-privacy="' . NoNull($Row['privacy_type']) . '"' .
                        ' data-tags="' . NoNull($Row['tag_list']) . '"' .
                        ' data-cnt="' . $cnt[$type] . '"';

                $tscl = '';
                if ( NoNull($Row['title']) == '' ) {
                    $tscl = ' class="date-title" data-dateunix="' . strtotime($Row['publish_at']) . '"';
                }

                $html = tabSpace(8) . '<li class="archive-item"' . $meta . '>' . "\r\n" .
                        tabSpace(9) . '<span class="archive-num">' . number_format($cnt[$type]) . '</span> ' .
                                      $icon .
                                      '<span class="archive-title">' .
                                        '<a href="' . $SiteUrl . NoNull($Row['canonical_url']) . '"' . $tscl . ' title="">' .
                                            NoNull($Row['title'], date("Y-m-d\TH:i:s\Z", strtotime($Row['publish_at']))) .
                                        '</a>' .
                                      '</span>' . "\r\n" .
                        tabSpace(8) . '</li>' . "\r\n" . $html;
            }

            if ( $html != '' ) { $html = tabSpace(7) . '<ul class="archive-list">' . "\r\n" .
                                         $html .
                                         tabSpace(7) . "</ul>";
                                }

            // Return the Formatted HTML
            return $html;
        }

        // If We're Here, There's Nothing
        return '';
    }

    /**
     *  Function Constructs a Standardized HTML Element and Returns the Object or an Empty String
     */
    private function _buildHTMLElement($data, $post, $single = false) {
        if ( is_array($post) ) {
            // Check to See If We Have a Cached Version of the Post
            $cache_file = md5($data['site_version'] . '-' . NoNull(APP_VER . CSS_VER) . '-' .
                              nullInt($this->settings['_account_id']) . '-' . $this->settings['ReqURI'] . '-' . NoNull($post['guid']));
            if ( nullInt(ENABLE_CACHING) == 1 ) {
                $html = readCache($data['site_id'], $cache_file);
                if ( $html !== false ) { return $html; }
            }

            // If We're Here, We Need to Build an HTML String and Cache It
            $tagLine = '';
            if ( is_array($post['tags']) ) {
                foreach ( $post['tags'] as $tag ) {
                    $tagLine .= '<li class="post-tag">' . NoNull($tag['name']) . '</li>';
                }
            }
            $geoLine = '';
            if ( YNBool(BoolYN($data['show_geo'])) ) {
                if ( is_array($post['meta']) ) {
                    if ( is_array($post['meta']['geo']) ) {
                        if ( $this->geo === false ) {
                            require_once(LIB_DIR . '/geocode.php');
                            $this->geo = new Geocode($this->settings, $this->strings);
                        }

                        $coords = round(nullInt($post['meta']['geo']['latitude']), 5) . ',' . round(nullInt($post['meta']['geo']['longitude']), 5);
                        $label = $this->geo->getNameFromCoords($post['meta']['geo']['latitude'], $post['meta']['geo']['longitude'], "");

                        $geoLine = "\r\n" .
                                   tabSpace(6) . '<div class="metaline location pad" data-value="' . $coords . '">' . "\r\n" .
                                   tabSpace(7) . '<i class="fas fa-map-pin"></i> <small>' . $label . "</small>\r\n" .
                                   tabSpace(6) . '</div>';
                    }
                }
            }

            // Do we have an Audio Element?
            $audio = '';
            if ( is_array($post['meta']) ) {
                if ( is_array($post['meta']['episode']) ) {
                    $fileUrl = NoNull($post['meta']['episode']['file']);
                    if ( $fileUrl != '' ) {
                        $audio = "\r\n" .
                                 tabSpace(7) . '<div class="metaline audio pad" data-file="' . $fileUrl . '">' . "\r\n" .
                                 tabSpace(8) . '<audio class="audioplayer" preload="auto" controlslist="nodownload">' . "\r\n" .
                                 tabSpace(9) . '<source type="' . NoNull($post['meta']['episode']['mime'], 'audio/mp3') . '" src="' . $fileUrl . '">' . "\r\n" .
                                 tabSpace(9) . 'Your browser does not support HTML5 audio, but you can still <a target="_blank" href="' . $fileUrl . '" title="">download the file</a>.' . "\r\n" .
                                 tabSpace(8) . '</audio>' . "\r\n" .
                                 tabSpace(7) . '</div>';

                    }
                }
            }

            $ReplyHTML = '';
            $postClass = NoNull($post['class']);
            if ( $postClass != '' ) { $postClass .= ' '; }
            if ( NoNull($post['reply_to']) != '' ) {
                $replyUrl = parse_url($post['reply_to'], PHP_URL_HOST);
                $ReplyHTML = '<p class="in-reply-to reply-pointer"><i class="fab fa-replyd"></i> <a target="_blank" href="' . NoNull($post['reply_to']) . '" class="p-name u-url">' . NoNull($replyUrl, $post['reply_to']) . '</a>.';
            }

            $PostThread = '';
            if ( is_array($post['thread']) ) {
                if ( nullInt($post['thread']['count']) > 1 ) {
                    $PostThread = ' data-thread-guid="' . NoNull($post['thread']['guid']) . '" data-thread-count="' . nullInt($post['thread']['count']) . '"';
                }
            }

            $SourceIcon = '';
            if ( array_key_exists('meta', $post) && is_array($post['meta']) ) {
                if ( array_key_exists('source', $post['meta']) && is_array($post['meta']['source']) ) {
                    $ico = strtolower(NoNull($post['meta']['source']['network']));
                    if ( $ico == 'App.Net' ) { $ico = 'adn'; }
                    if ( in_array($ico, array('adn', 'twitter')) ) {
                        $SourceIcon = '<i class="fa fa-' . strtolower(NoNull($post['meta']['source']['network'])) . '"></i>';
                    }
                }
            }

            $WebMentions = '';
            if ( array_key_exists('web_mentions', $post) && is_array($post['web_mentions']) ) {
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

            $ReplStr = array( '[POST_GUID]'     => NoNull($post['guid']),
                              '[POST_TYPE]'     => NoNull($post['type']),
                              '[POST_CLASS]'    => $postClass,
                              '[AUTHOR_NAME]'       => NoNull($post['persona']['name']),
                              '[AUTHOR_GUID]'       => NoNull($post['persona']['guid']),
                              '[AUTHOR_PERSONA]'    => NoNull($post['persona']['display_name'], $post['persona']['as']),
                              '[AUTHOR_PROFILE]'    => NoNull($post['persona']['profile_url']),
                              '[AUTHOR_AVATAR]'     => NoNull($post['persona']['avatar']),
                              '[AUTHOR_FOLLOW_URL]' => NoNull($post['persona']['follow']['url']),
                              '[AUTHOR_FOLLOW_RSS]' => NoNull($post['persona']['follow']['rss']),
                              '[TITLE]'         => NoNull($post['title']),
                              '[CONTENT]'       => NoNull($post['content']) . NoNull($ReplyHTML),
                              '[IS_RTL]'        => (($this->_isRTL(NoNull($post['text']))) ? ' rtl' : ''),
                              '[BANNER]'        => '',
                              '[TAGLINE]'       => NoNull($tagLine),
                              '[HOMEURL]'       => NoNull($this->settings['HomeURL']),
                              '[GEOTAG]'        => $geoLine,
                              '[AUDIO]'         => $audio,
                              '[THREAD]'        => $PostThread,
                              '[IS_SINGLE]'     => (($single) ? 'Y' : 'N'),
                              '[SOURCE_NETWORK]'=> NoNull($post['meta']['source']['network']),
                              '[SOURCE_ICON]'   => $SourceIcon,
                              '[WEBMENTIONS]'   => $WebMentions,
                              '[PUBLISH_AT]'    => NoNull($post['publish_at']),
                              '[PUBLISH_UNIX]'  => nullInt($post['publish_unix']),
                              '[UPDATED_AT]'    => NoNull($post['updated_at']),
                              '[UPDATED_UNIX]'  => nullInt($post['updated_unix']),
                              '[CANONICAL]'     => NoNull($post['canonical_url']),
                              '[POST_SLUG]'     => NoNull($post['slug']),
                              '[REPLY_TO]'      => NoNull($post['reply_to']),
                              '[POST_STARRED]'  => BoolYN($post['attributes']['starred']),
                              '[CAN_EDIT]'      => BoolYN($post['can_edit']),
                             );

            switch ( $post['type'] ) {
                case 'post.quotation':
                case 'post.bookmark':
                    $ReplStr['[SOURCE_TITLE]'] = NoNull($post['meta']['source']['title'], $post['meta']['source']['url']);
                    $ReplStr['[SOURCE_URL]'] = NoNull($post['meta']['source']['url']);
                    $ReplStr['[SOURCE_DOMAIN]'] = parse_url($post['meta']['source']['url'], PHP_URL_HOST);
                    $ReplStr['[SOURCE_SUMMARY]'] = NoNull($post['meta']['source']['summary']);
                    $ReplStr['[SOURCE_AUTHOR]'] = NoNull($post['meta']['source']['author']);
                    break;

                default:
                    /* Do Nothing */
            }

            // Add the Theme Language Text
            if ( is_array($this->strings) ) {
                foreach ( $this->strings as $key=>$val ) {
                    if ( in_array($key, $ReplStr) === false ) { $ReplStr["[$key]"] = $val; }
                }
            }

            // Determine the Template File
            $flatFile = THEME_DIR . '/' . $data['location'] . '/flats/' . $post['type'] . '.html';
            if ( !file_exists($flatFile) ) { $flatFile = FLATS_DIR . '/templates/' . $post['type'] . '.html'; }

            // Generate the HTML
            $html = readResource($flatFile, $ReplStr);

            // Save the File to Cache if Required
            if ( nullInt(ENABLE_CACHING) == 1 ) { saveCache($data['site_id'], $cache_file, $html); }

            // Return the HTML Element
            return $html;
        }

        // If We're Here, Something Is Wrong
        return '';
    }

    /** ********************************************************************* *
     *  Post-Publish Functions
     ** ********************************************************************* */
    /**
     *  Get Posts that Need to have Post-Publishing Tasks Performed
     */
    private function _setPostPublishData( $PostID = 0 ) {
        if ( $PostID <= 0 ) { return false; }

        $ReplStr = array( '[POST_ID]' => nullInt($PostID) );
        $sqlStr = readResource(SQL_DIR . '/posts/getPostWebMentionToSend.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) && count($rslt) > 0 ) {
            require_once( LIB_DIR . '/webmention.php' );
            $webm = new Webmention( $this->settings, $this->strings );

            // Send a Mention (There is either 0 or 1)
            foreach ( $rslt as $Row ) {
                $data = $webm->sendMentions(NoNull($Row['source_url']), NoNull($Row['target_url']));
            }
            unset($webm);
        }

        // Return a Happy Boolean
        return true;
    }

    /** ********************************************************************* *
     *  Additional Readability Functions
     ** ********************************************************************* */
    /**
     *  Function returns a short list of posts of a given type to represent previous, next, and random.
     */
    private function _getReadMore() {
        $CleanGuid = NoNull($this->settings['post_guid'], $this->settings['guid']);

        /* Perform some basic validation */
        if ( mb_strlen($CleanGuid) != 36 ) { return $this->_setMetaMessage("Invalid Post Identifier Supplied", 400); }

        /* Collect the data */
        $ReplStr = array( '[POST_GUID]' => sqlScrub($CleanGuid) );
        $sqlStr = prepSQLQuery( "CALL GetReadNextList('[POST_GUID]');", $ReplStr );
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();

            foreach ( $rslt as $Row ) {
                $key = strtolower(NoNull($Row['key']));
                if ( array_key_exists($key, $data) === false && mb_strlen(NoNull($Row['guid'])) == 36 ) {
                    $data[$key] = array( 'guid'  => NoNull($Row['guid']),
                                         'type'  => NoNull($Row['type']),
                                         'title' => NoNull($Row['title']),
                                         'url'   => NoNull($Row['canonical_url']),
                                         'idx'   => nullInt($Row['post_num']),

                                         'publish_at'   => apiDate($Row['publish_unix'], 'Z'),
                                         'publish_unix' => apiDate($Row['publish_unix'], 'U'),
                                        );


                }
            }

            /* If we have something that looks valid, let's return it */
            if ( is_array($data) && count($data) > 0 ) { return $data; }
        }

        /* If we're here, there's nothing to return. Either because every post is private, or because there are no matching posts */
        return $this->_setMetaMessage("No matching posts found", 404);
    }

    /** ********************************************************************* *
     *  Timeline / Stream Functions
     ** ********************************************************************* */
    private function _processTimeline( $posts ) {
        $PostLimit = nullInt($this->settings['count'], 100);
        if ( $PostLimit > 250 ) { $PostLimit = 250; }
        if ( $PostLimit <= 0 ) { $PostLimit = 100; }

        if ( is_array($posts) ) {
            $default_avatar = $this->settings['HomeURL'] . '/avatars/default.png';
            $data = array();

            foreach ( $posts as $post ) {
                /* Only show posts that are permissible */
                if ( YNBool($post['is_visible']) ) {
                    /* So long as we're not beyond the post limit, add the record to the array */
                    if ( count($data) < $PostLimit ) {

                        /* Determine the Record Cache Index */
                        $CacheFile = 'posts-' . substr('00000000' . nullInt($this->settings['_account_id']), -8) . '-' . sha1(NoNull($post['post_guid']) . nullInt($post['post_version']));
                        $pd = getCacheObject($CacheFile);

                        /* Build the Post Object if it is not already cached */
                        if ( is_array($pd) === false ) {
                            /* Is there Meta-Data? If So, Grab It */
                            $poMeta = false;
                            if ( YNBool($post['has_meta']) ) { $poMeta = $this->_getPostMeta($post['post_guid']); }
                            if ( NoNull($this->settings['nom']) != '' ) {
                                if ( is_array($poMeta) === false ) { $poMeta = array(); }
                                $poMeta['nom'] = NoNull($this->settings['nom']);
                            }

                            /* Process any Tags */
                            $poTags = false;
                            if ( NoNull($post['post_tags']) != '' ) {
                                $poTags = array();
                                $tgs = explode(',', NoNull($post['post_tags']));
                                foreach ( $tgs as $tag ) {
                                    $key = $this->_getSafeTagSlug(NoNull($tag));
                                    $poTags[] = array( 'url'  => NoNull($post['site_url']) . '/tag/' . $key,
                                                       'name' => NoNull($tag),
                                                      );
                                }
                            }

                            /* Do We Have Mentions? Grab the List */
                            $mentions = false;
                            if ( NoNull($post['mentions']) != '' ) {
                                $json = json_decode('[' . $post['mentions'] . ']');
                                $jArr = objectToArray($json);
                                if ( is_array($jArr) && count($jArr) > 0 ) {
                                    $mentions = array();
                                    foreach ( $jArr as $pa ) {
                                        $mentions[] = array( 'guid'   => NoNull($pa['guid']),
                                                             'name'   => NoNull($pa['as']),
                                                             'is_you' => YNBool($pa['is_you']),
                                                            );
                                    }
                                }
                            }

                            /* Do We Have Geo-Markers? Grab the History */
                            $markers = false;
                            if ( YNBool($Row['has_markers']) ) {
                                $markers = $this->_getPostMarkers($Row['post_guid']);
                                if ( is_array($markers) ) {
                                    $poMeta['markers'] = $markers;
                                }
                            }

                            /* Prep the Post-Text */
                            $IsNote = true;
                            if ( in_array(NoNull($Row['post_type']), array('post.article', 'post.quotation', 'post.bookmark')) ) { $IsNote = false; }
                            $post_text = $this->_getMarkdownHTML($post['value'], $post['post_id'], $IsNote, true);
                            if ( is_array($mentions) ) {
                                $post_text = $this->_parsePostMentions($post_text, $mentions);
                            }

                            /* Construct the Core array */
                            $pd = array( 'guid'     => NoNull($post['post_guid']),
                                         'type'     => NoNull($post['type']),
                                         'privacy'  => NoNull($post['privacy_type']),

                                         'canonical_url' => NoNull($post['canonical_url']),
                                         'reply_to'      => ((NoNull($post['reply_to']) == '') ? false : NoNull($post['reply_to'])),
                                         'has_thread'    => ((nullInt($post['thread_length']) > 0) ? true : false),

                                         'title'    => ((NoNull($post['title']) == '') ? false : NoNull($post['title'])),
                                         'content'  => $post_text,
                                         'text'     => NoNull($post['value']),
                                         'rtl'      => $this->_isRTL(NoNull($post['value'])),

                                         'meta'     => $poMeta,
                                         'tags'     => $poTags,
                                         'mentions' => $mentions,
                                         'points'   => nullInt($post['total_points']),

                                         'persona'  => array( 'guid'        => NoNull($post['persona_guid']),
                                                              'as'          => '@' . NoNull($post['persona_name']),
                                                              'name'        => NoNull($post['display_name']),
                                                              'avatar'      => NoNull($post['avatar_url'], $default_avatar),

                                                              'pin'         => NoNull($post['persona_pin'], 'pin.none'),
                                                              'you_follow'  => YNBool($post['persona_follow']),
                                                              'is_muted'    => YNBool($post['persona_muted']),
                                                              'is_starred'  => YNBool($post['persona_starred']),
                                                              'is_blocked'  => YNBool($post['persona_blocked']),
                                                              'is_you'      => YNBool($post['is_you']),

                                                              'profile_url' => NoNull($post['profile_url']),
                                                             ),

                                         'attributes'       => array( 'pin'     => NoNull($post['pin_type'], 'pin.none'),
                                                                      'starred' => YNBool($post['is_starred']),
                                                                      'muted'   => YNBool($post['is_muted']),
                                                                      'points'  => nullInt($post['points']),
                                                                     ),

                                         'publish_at'   => apiDate($post['publish_unix'], 'Z'),
                                         'publish_unix' => apiDate($post['publish_unix'], 'U'),
                                         'expires_at'   => apiDate($post['expires_unix'], 'Z'),
                                         'expires_unix' => apiDate($post['expires_unix'], 'U'),
                                         'updated_at'   => apiDate($post['updated_unix'], 'Z'),
                                         'updated_unix' => apiDate($post['updated_unix'], 'U'),
                                        );

                            /* Save the array to the cache */
                            setCacheObject($CacheFile, $pd);
                        }

                        /* Add the Post Data to the output array */
                        if ( is_array($pd) ) { $data[] = $pd; }

                    } else {
                        /* If the array count is greater than the limit, then we clearly have more */
                        $this->settings['has_more'] = true;
                    }
                }
            }

            /* Return the Data If We Have Some */
            if ( is_array($data) && count($data) > 0 ) { return $data; }
        }

        // If We're Here, There Are No Posts
        return array();
    }

    /**
     *  Function Collects the Specified Timeline and Returns a Happy Array
     */
    private function _getTLStream( $path = 'global' ) {
        $path = NoNull(strtolower($path), 'global');
        $validTLs = array( 'global', 'mentions', 'home', 'persona', 'interact' );
        if ( in_array($path, $validTLs) === false ) { $this->_setMetaMessage("Invalid Timeline Path Requested", 400); return false; }

        /* Get the Types Requested (Default is Social Posts Only) */
        $validTypes = array( 'post.article', 'post.note', 'post.quotation', 'post.bookmark', 'post.location', 'post.photo' );
        $CleanTypes = '';
        $rTypes = explode(',', NoNull($this->settings['types'], $this->settings['post_types']));
        if ( is_array($rTypes) ) {
            foreach ( $rTypes as $rType ) {
                $rType = strtolower($rType);
                if ( in_array($rType, $validTypes) ) {
                    if ( $CleanTypes != '' ) { $CleanTypes .= ','; }
                    $CleanTypes .=  sqlScrub($rType);
                }
            }
        } else {
            if ( is_string($rTypes) ) {
                $rType = strtolower($rTypes);
                if ( in_array($rType, $validTypes) ) {
                    if ( $CleanTypes != '' ) { $CleanTypes .= ','; }
                    $CleanTypes .= sqlScrub($rType);
                }
            }
        }
        if ( $CleanTypes == '' ) { $CleanTypes = "post.note"; }

        // Get the Time Range
        $SinceUnix = nullInt($this->settings['since']);
        $UntilUnix = nullInt($this->settings['until']);

        // Is this for a Persona?
        $PersonaGUID = NoNull($this->settings['_for_guid'], $this->settings['_persona_guid']);

        // How Many Posts?
        $CleanCount = nullInt($this->settings['count'], 100);
        if ( $CleanCount > 250 ) { $CleanCount = 250; }
        if ( $CleanCount <= 0 ) { $CleanCount = 100; }
        $CleanCount++;

        // Get the Posts
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[SINCE_UNIX]'   => nullInt($SinceUnix),
                          '[UNTIL_UNIX]'   => nullInt($UntilUnix),
                          '[POST_TYPES]'   => NoNull($CleanTypes),
                          '[PERSONA_GUID]' => sqlScrub($PersonaGUID),
                          '[COUNT]'        => nullInt($CleanCount),
                         );
        $sqlStr = prepSQLQuery("CALL Get" . ucfirst($path) . "Timeline([ACCOUNT_ID], '[PERSONA_GUID]', '[POST_TYPES]', [SINCE_UNIX], [UNTIL_UNIX], [COUNT]);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);

        if ( is_array($rslt) ) {
            return $this->_processTimeline($rslt);

        } else {
            /* If there are no results, and the Since/Until is set as 0, expand the criteria */
            if ( nullInt($this->settings['since']) <= 0 && nullInt($this->settings['until']) <= 0 ) {
                $this->settings['until'] = 0;
                $this->settings['since'] = 1;

                // Run the Query One More Time
                return $this->_getTLStream($path);
            }
        }

        /* If We're Here, No Posts Could Be Retrieved */
        return array();
    }


    /**
     *  Function returns a list of posts for a given Channel. If a Post.guid is also supplied, then the
     *      thread the post belongs to is returned in its entirety.
     */
    private function _getPostList() {
        $ChannelGuid = NoNull($this->settings['channel_guid'], $this->settings['channel']);
        $PostGuid = NoNull($this->settings['post_guid'], NoNull($this->settings['post'], $this->settings['guid']));

        /* Perform some basic error checking */
        if ( mb_strlen($ChannelGuid) != 36 ) { return $this->_setMetaMessage("Invalid Channel identifier supplied", 400); }
        if ( mb_strlen($PostGuid) > 0 && mb_strlen($PostGuid) != 36 ) { return $this->_setMetaMessage("Invalid Post identifier supplied", 400); }

        /* Let's collect some data */
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[CHANNEL_GUID]' => sqlScrub($ChannelGuid),
                          '[POST_GUID]'    => sqlScrub($PostGuid),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/getPostList.sql', $ReplStr);
        if ( mb_strlen($PostGuid) == 36 ) { $sqlStr = readResource(SQL_DIR . '/posts/getPostReplies.sql', $ReplStr); }
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();

            foreach ( $rslt as $Row ) {
                if ( YNBool($Row['is_visible']) ) {
                    $content = false;

                    /* Do we have content to present? */
                    $text = NoNull($Row['value'], $Row['post_text']);
                    if ( mb_strlen($text) > 0 ) {
                        $content = array( 'html' => $this->_getMarkdownHTML($text, $Row['post_id'], true),
                                          'text' => $text,
                                         );
                    }

                    /* Build the output array */
                    $data[] = array( 'guid'    => NoNull($Row['post_guid']),
                                     'type'    => NoNull($Row['post_type']),
                                     'number'  => nullInt($Row['post_num']),

                                     'title'   => ((mb_strlen(NoNull($Row['title'])) > 0) ? NoNull($Row['title']) : false),
                                     'content' => $content,
                                     'url'     => NoNull($Row['site_url']) . NoNull($Row['canonical_url']),

                                     'publish_at'   => apiDate($Row['publish_unix'], 'Z'),
                                     'publish_unix' => apiDate($Row['publish_unix'], 'U'),
                                     'expires_at'   => apiDate($Row['expires_unix'], 'Z'),
                                     'expires_unix' => apiDate($Row['expires_unix'], 'U'),

                                     'author' => array( 'guid'       => NoNull($Row['persona_guid']),
                                                        'display_as' => NoNull($Row['name'], $Row['display_name']),
                                                        'last_name'  => NoNull($Row['last_name']),
                                                        'first_name' => NoNull($Row['first_name']),
                                                        'avatar_url' => NoNull($Row['site_url']) . '/avatars/' . NoNull($Row['avatar_img'], 'default.png'),
                                                        'is_active'  => YNBool($Row['persona_active']),

                                                        'created_at'   => apiDate($Row['persona_created_unix'], 'Z'),
                                                        'created_unix' => apiDate($Row['persona_created_unix'], 'U'),
                                                       ),

                                     'slug'    => NoNull($Row['slug']),
                                     'privacy' => NoNull($Row['privacy_type']),
                                     'hash'    => NoNull($Row['hash']),

                                     'created_at'   => apiDate($Row['created_unix'], 'Z'),
                                     'created_unix' => apiDate($Row['created_unix'], 'U'),
                                     'updated_at'   => apiDate($Row['updated_unix'], 'Z'),
                                     'updated_unix' => apiDate($Row['updated_unix'], 'U'),
                                    );

                }
            }

            /* If we have data, let's return it */
            if ( is_array($data) && count($data) > 0 ) { return $data; }
        }

        /* If we're here, nothing was found */
        return $this->_setMetaMessage("No visible posts found with the lookup criteria", 404);
    }

    /** ********************************************************************* *
     *  Markdown Formatting Functions
     ** ********************************************************************* */
    /**
     *  Function Converts a Text String to HTML Via Markdown
     */
    private function _getMarkdownHTML( $text, $post_id, $isNote = false, $showLinkURL = false ) {
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

        /* Handle Underlines */
        if (preg_match_all('/\_(.+?)\_/s', $text, $matches)) {
            foreach($matches[0] as $fn) {
                $errs = array("\n", "\r", '_ ', ' _');
                $zz = false;
                $fn = NoNull($fn);

                /* Check to see if the string contains disqualifiers */
                foreach ( $errs as $err ) {
                    if ( $zz === false ) { $zz = mb_strpos($fn, $err); }
                }

                /* If we're good, let's transform */
                if ( $zz === false ) {
                    $stRepl = array( '_' => '' );
                    $code = "<u>" . NoNull(str_replace(array_keys($stRepl), array_values($stRepl), $fn)) . "</u>";
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

    /**
     *  Function Reads Through the Post Text and Pulls out Hashtags as Tags
     */
    function _getTagsFromPost( $text, $post_id ) {
        $text = strip_tags($text);
        $words = explode(' ', " $text ");
        $tags = array();
        $lst = '';

        foreach ( $words as $word ) {
            $clean_word = NoNull($word);
            $hash = '';

            if ( NoNull(substr($clean_word, 0, 1)) == '#' ) {
                $hash_scrub = array('#', '?', '.', ',', '!');
                $hash = NoNull(str_replace($hash_scrub, '', $clean_word));

                if ($hash != '' && in_array($hash, $tags) === false ) {
                    $Key = $this->_getSafeTagSlug(NoNull($hash));
                    $Val = sqlScrub($hash);

                    if ( $lst != '' ) { $lst .= ","; }
                    $lst .= "($post_id, '$Key', '$Val')";
                }
            }
        }

        // Return the VALUES list
        return $lst;
    }

    /**
     *  Function Reads Through the Post Text and Pulls out Mentions
     */
    function _getMentionsFromPost( $text, $post_id ) {
        $text = strip_tags($text);
        $words = explode(' ', " $text ");
        $pnames = array();
        $lst = '';

        foreach ( $words as $word ) {
            $invalids = array('#', "\r", "\t", "\n", '//', '/', '</');
            $clean_word = strtolower(NoNull(str_replace($invalids, '', $word)));
            $name = '';

            if ( NoNull(substr($clean_word, 0, 1)) == '@' ) {
                $name_scrub = array('@', '#', "\r", "\t", "\n", '//', '/', '</', '<br>');
                $name = NoNull(strip_tags($clean_word));

                for ( $i = 0; $i < (count($name_scrub) - 1); $i++ ) {
                    $name = NoNull(str_replace($name_scrub, '', $name));
                }

                // Handle Contractions and trailing thingies ...
                $name_scrub = array('?', '.', ',', '!', '/', "'", '<', '>');
                foreach ( $name_scrub as $char ) {
                    if ( mb_strpos($name, $char) ) {
                        $name = NoNull(substr($name, 0, mb_strpos($name, $char)));
                    }
                }

                if ($name != '' && in_array($name, $pnames) === false ) {
                    $pid = $this->_getPersonaIDFromName($name);
                    if ( $pid !== false && $pid > 0 ) {
                        if ( $lst != '' ) { $lst .= ","; }
                        $lst .= "($post_id, $pid)";

                        $pnames[] = $name;
                    }
                }
            }
        }

        // Return the VALUES list
        return $lst;
    }

    function _getPersonaIDFromName( $name ) {
        if ( array_key_exists('personas', $this->settings) === false ) {
            $sqlStr = readResource(SQL_DIR . '/posts/getPersonaList.sql');
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $this->settings['personas'] = array();

                foreach ( $rslt as $Row ) {
                    $this->settings['personas'][ NoNull($Row['name']) ] = nullInt($Row['id']);
                }
            }
        }

        // If We Have the Persona Name, Return the ID. Send an Unhappy Boolean Otherwise.
        if ( is_array($this->settings['personas']) ) {
            if ( array_key_exists($name, $this->settings['personas']) ) { return $this->settings['personas'][$name]; }
        }
        return false;
    }

    /** ********************************************************************* *
     *  RSS (XML/JSON) Functions
     ** ********************************************************************* */
    /**
     *  Function Constructs an XML or JSON RSS Feed for a given Site. If one
     *      is cached and still valid, then it will be returned.
     */
    private function _getRSSFeed( $site, $format = 'xml' ) {
        // Do We Have a Special Request Type?
        $ReqTypes = array( 'post.article'   => '-',
                           'post.bookmark'  => '-',
                           'post.quotation' => '-',
                           'post.note'      => '-',
                          );
        $hasOption = false;
        $rtSuffix = '';
        if ( array_key_exists('rss_filter_on', $this->settings) ) {
            $ReqTypes['post.article'] = BoolYN(in_array('article', $this->settings['rss_filter_on']));
            $ReqTypes['post.bookmark'] = BoolYN(in_array('bookmark', $this->settings['rss_filter_on']));
            $ReqTypes['post.quotation'] = BoolYN(in_array('quotation', $this->settings['rss_filter_on']));
            $ReqTypes['post.note'] = BoolYN(in_array('note', $this->settings['rss_filter_on']));

            $rtSuffix = '-';
            foreach ( $ReqTypes as $Key=>$Value ) {
                if ( YNBool($Value) ) { $hasOption = YNBool($Value); }
                $rtSuffix .= $Value;
            }
        }

        // To ensure the cached self-reference URL is correct, grab the ReqURI suffix
        $dotExt = getFileExtension($this->settings['ReqURI']);
        if ( $dotExt != '' ) { $dotExt = "." . $dotExt; }

        $rssCnt = nullInt($site['rss_limit'], 15);
        if ( $rssCnt <= 0 ) { $rssCnt = 15; }

        // Check to See If We Have a Cached Version of the Feed
        $cache_file = $site['site_version'] . '-' . substr('0000' . $rssCnt, -4) . '-' . NoNull($format, 'xml') . NoNull($rtSuffix, '-feed') . NoNull($dotExt);
        $rVal = '';

        // If there is no option, decide what sort of feed to provide
        if ( $hasOption === false ) {
            $PgRoot = NoNull($this->settings['PgRoot']);
            if ( strpos($PgRoot, '.') > 0 ) {
                $Parts = explode('.', $PgRoot);
                $PgRoot = '';

                foreach ( $Parts as $Part ) {
                    if ( NoNull($Part) != '' && $PgRoot == '' ) { $PgRoot = NoNull($Part); }
                }
            }

            if ( in_array(strtolower($PgRoot), array('notes', 'social', 'socials')) ) {
                $ReqTypes['post.note'] = 'Y';
            } else {
                $ReqTypes['post.article'] = 'Y';
            }
        }

        if ( nullInt(ENABLE_CACHING) == 1 ) {
            $rVal = readCache($site['site_id'], $cache_file);
            if ( $rVal !== false ) { return $rVal; }
        }

        $AudioOnly = in_array($this->settings['PgRoot'], array('podcast.xml', 'podcast.json', 'podcast.rss', 'podcast'));

        // If We're Here, Build the Feed
        $ReplStr = array( '[SITE_URL]'       => sqlScrub($site['HomeURL']),
                          '[SHOW_ARTICLE]'   => sqlScrub($ReqTypes['post.article']),
                          '[SHOW_BOOKMARK]'  => sqlScrub($ReqTypes['post.bookmark']),
                          '[SHOW_QUOTATION]' => sqlScrub($ReqTypes['post.quotation']),
                          '[SHOW_NOTE]'      => sqlScrub($ReqTypes['post.note']),
                          '[AUDIO_ONLY]'     => BoolYN($AudioOnly),
                          '[COUNT]'          => nullInt($site['rss_limit'], 15),
                         );
        $sqlStr = prepSQLQuery("CALL GetSyndicationContent('[SITE_URL]', '[SHOW_ARTICLE]', '[SHOW_BOOKMARK]', '[SHOW_QUOTATION]', '[SHOW_NOTE]', 'Y', '[AUDIO_ONLY]', [COUNT], 0);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            // If the request is JSON, supply it as such. Otherwise, XML as default
            if ( $format == 'json' ) {
                $rVal = $this->_buildJSONFeed($site, $rslt);

            } else {
                $rVal = $this->_buildXMLFeed($site, $rslt);
            }
        }

        if ( nullInt(ENABLE_CACHING) == 1 ) { saveCache($site['site_id'], $cache_file, $rVal); }

        // Return the Constructed Data
        return $rVal;
    }

    /**
     *  Function builds a JSON body based on the results from _getRSSFeed
     */
    private function _buildJSONFeed( $site, $data ) {
        $SiteURL = NoNull($site['protocol'], 'http') . '://' . NoNull($site['HomeURL']);
        $cdnUrl = getCdnUrl();

        $json = array( 'version'        =>  "https://jsonfeed.org/version/1",
                       'title'          => NoNull($site['name']),
                       'home_page_url'  => $SiteUrl,
                       'feed_url'       => $SiteURL . NoNull($this->settings['ReqURI'], '/feed.json'),
                       'description'    => NoNull($site['summary'], $site['description']),
                       'favicon'        => '',
                       'icon'           => '',

                       'items'          => array()
                      );

        // Build the Items
        if ( is_array($data) ) {
            $inplace = array( '[HOMEURL]' => $SiteURL, '’' => "'", '‘' => "'", '“' => '"', '”' => '"',
                              "â" => '–', "" => '–', "" => '', "" => '',
                              "\n\n " => "\n\n", );
            $fixes = array( 'src="//cdn.10centuries.org/' => 'src="https://cdn.10centuries.org/',
                            "'" => "&apos;",    '""' => '"',    "â€™" => "&apos;",  "’" => "&apos;",
                            '</p> <p>' => '</p><p>',    'target="_blank"' => '',
                           );

            foreach ( $data as $post ) {
                $post['post_text'] = NoNull(str_replace(array_keys($inplace), array_values($inplace), $post['post_text']));
                $html = $this->_getMarkdownHTML($post['post_text'], $post['post_id'], false, true);
                $html = str_replace(array_keys($fixes), array_values($fixes), $html);
                $html = strip_tags($html, '<blockquote><p><a><strong><em><img><code><pre><sup><ol><ul><li>');

                $item = array( 'id'     => NoNull($post['post_guid']),
                               'title'  => NoNull(str_replace(array_keys($inplace), array_values($inplace), $post['post_title'])),
                               'content_text'   => NoNull($post['post_text']),
                               'content_html'   => NoNull($html),
                               'url'            => NoNull($post['post_url']),
                               'external_url'   => NoNull($post['source_url']),
                               'summary'        => NoNull($post['summary']),
                               'banner_image'   => NoNull($post['banner_image']),
                               'date_published' => date("c", strtotime($post['publish_at'])),
                               'date_modified'  => date("c", strtotime($post['updated_at'])),
                               'author'         => array( 'name'   => NoNull($post['display_name'], $post['handle']),
                                                          'url'    => $SiteURL,
                                                          'avatar' => NoNull($post['avatar_url']),
                                                         ),
                               'attachments'    => array(),
                              );

                // If We Have Attachments, Ensure They're Set
                if ( YNBool($post['has_audio']) ) {
                    $encl = $this->_getPostEnclosure($post['post_id'], 100);
                    if ( is_array($encl) ) {
                        $item['attachments'][] = array( 'url'           => $cdnUrl . NoNull($encl['url']),
                                                        'mime_type'     => NoNull($encl['type']),
                                                        'size_in_bytes' => nullInt($encl['size']),
                                                       );
                    }
                }

                // Ensure the Icon Exists
                if ( $json['favicon'] == '' ) { $json['favicon'] = NoNull($post['avatar_url']); }
                if ( $json['icon'] == '' ) { $json['icon'] = NoNull($post['avatar_url']); }

                // Remove the Unnecessary Elements
                if ( count($item['attachments']) <= 0 ) { unset($item['attachments']); }
                if ( $item['banner_image'] == '' ) { unset($item['banner_image']); }
                if ( $item['external_url'] == '' ) { unset($item['external_url']); }
                if ( $item['author']['name'] == '' ) { unset($item['author']); }
                if ( $item['summary'] == '' ) { unset($item['summary']); }
                if ( $item['title'] == '' ) { unset($item['title']); }

                // Add the Array to the Output
                $json['items'][] = $item;
            }
        }

        // Return the Completed Object as JSON
        return json_encode($json, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    }

    /**
     *  Function builds an XML body based on the results from _getRSSFeed
     */
    private function _buildXMLFeed( $site, $data ) {
        $SiteURL = NoNull($site['protocol'], 'http') . '://' . NoNull($site['HomeURL']);
        $cdnUrl = getCdnUrl();

        $ReplStr = array( '[SITENAME]'  => NoNull($site['name']),
                          '[HOMEURL]'   => $SiteURL,
                          '[SUBTITLE]'  => NoNull($site['description']),
                          '[SUMMARY]'   => NoNull($site['summary'], $site['description']),
                          '[BUILD_DTS]' => date("D, d M Y H:i:s O", strtotime($site['updated_at'])),
                          '[SITELANG]'  => 'EN',
                          '[LICENSE]'   => NoNull($site['license']),
                          '[GENERATOR]' => APP_NAME . ' (' . APP_VER . ')',

                          '[RSS_URL]'   => $SiteURL . NoNull($this->settings['ReqURI'], '/rss'),
                          '[RSS_COVER]' => NoNull($site['rss_cover'], ''),
                          '[RSS_AUTHOR]'=> NoNull($site['rss_author'], ''),
                          '[EXPLICIT]'  => NoNull($site['rss_explicit'], 'Clean'),
                          '[MAILADDR]'  => NoNull($site['rss_mailaddr'], ''),
                          '[RSS_TOPIC]' => '',
                          '[RSS_ITEMS]' => '',
                         );

        // Build the RSS Items
        if ( is_array($data) ) {
            $inplace = array( '[HOMEURL]' => $SiteURL, '’' => "'", '‘' => "'", '“' => '"', '”' => '"',
                              "â" => '–', "" => '–', "" => '', "" => '',
                              "\n\n " => "\n\n", );
            $fixes = array( 'src="//cdn.10centuries.org/' => 'src="https://cdn.10centuries.org/',
                            "'" => "&apos;", "â€™" => "&apos;", "’" => "&apos;",
                            '</p> <p>' => '</p><p>', 'target="_blank"' => '', '""' => '"',
                           );
            $items = '';

            foreach ( $data as $post ) {
                $post['post_text'] = NoNull(str_replace(array_keys($inplace), array_values($inplace), $post['post_text']));
                $html = $this->_getMarkdownHTML($post['post_text'], $post['post_id'], false, false);
                $html = str_replace(array_keys($fixes), array_values($fixes), $html);
                $html = strip_tags($html, '<blockquote><p><a><strong><em><img><code><pre><sup><ol><ul><li>');

                $text = html_entity_decode(strip_tags(str_replace('</p>', "</p>\r\n\r\n", $html)));

                $encl = false;
                if ( YNBool($post['has_audio']) ) {
                    $encl = $this->_getPostEnclosure($post['post_id']);
                }

                $rObj = array( '[TITLE]'        => NoNull(str_replace(array_keys($inplace), array_values($inplace), $post['post_title'])),
                               '[POST_URL]'     => NoNull($post['source_url'], $post['post_url']),
                               '[POST_GUID]'    => NoNull($post['post_guid']),
                               '[POST_DATE]'    => date("D, d M Y H:i:s O", strtotime($post['publish_at'])),
                               '[POST_UTC]'     => date("c", strtotime($post['publish_at'])),
                               '[AUTHOR_NAME]'  => NoNull($post['display_name'], $post['handle']),
                               '[AVATAR_URL]'   => NoNull($post['avatar_url']),
                               '[EXPLICIT]'     => getExplicitValue(NoNull($post['explicit'], NoNull($site['rss_explicit'], 'Clean'))),

                               '[ENCL_LINK]'    => (($encl !== false) ? $cdnUrl . NoNull($encl['url']) : ''),
                               '[ENCL_NAME]'    => (($encl !== false) ? NoNull($encl['name']) : ''),
                               '[ENCL_SIZE]'    => (($encl !== false) ? nullInt($encl['size']) : ''),
                               '[ENCL_TIME]'    => (($encl !== false) ? NoNull($encl['time'], '00:00:00') : ''),
                               '[ENCL_TYPE]'    => (($encl !== false) ? NoNull($encl['type']) : ''),
                               '[ENCL_EPNO]'    => (($encl !== false) ? NoNull($encl['number']) : ''),

                               '[POST_BANR]'    => NoNull($post['post_banner'], $site['rss_cover']),
                               '[POST_SUBS]'    => NoNull($post['post_subtitle']),
                               '[POST_SUMM]'    => NoNull($encl['summary'], $post['post_summary']),
                               '[POST_TYPE]'    => NoNull($post['post_type']),
                               '[POST_TEXT]'    => NoNull(str_replace(array_keys($inplace), array_values($inplace), NoNull($encl['summary'], $text))),
                               '[POST_HTML]'    => NoNull($html),
                              );

                // Ensure a Cover Image Exists (Using the Avatar If Required)
                if ( $ReplStr['[RSS_COVER]'] == '' ) { $ReplStr['[RSS_COVER]'] = NoNull($post['avatar_url']); }

                $itemType = 'item.basic';
                if ( YNBool($post['has_audio']) ) { $itemType = 'item.audio'; }
                if ( $post['post_type'] == 'post.note' ) { $itemType = 'item.note'; }

                if ( $items != '' ) { $items .= "\r\n"; }
                $items .= readResource(FLATS_DIR . '/templates/feed.' . $itemType . '.xml', $rObj);
            }

            // Write the Items to the Complete Array
            $ReplStr['[RSS_ITEMS]'] = str_replace(array_keys($ReplStr), array_values($ReplStr), $items);
        }

        // Construct the Completed XML Object
        $xmlOut = readResource(FLATS_DIR . '/templates/feed.main.xml', $ReplStr);

        // Clean Up the Issues
        $forbid = array( '<title></title>' => '', '<itunes:subtitle></itunes:subtitle>' => '', '<itunes:summary></itunes:summary>' => '',
                         '<itunes:image href=""/>' => '', '<itunes:email></itunes:email>' => '', '<itunes:order></itunes:order>' => '',
                         '<itunes:duration>00:00:00</itunes:duration>' => '', '<itunes:duration>00:00</itunes:duration>' => '',
                         '<itunes:name></itunes:name>' => '', '<itunes:author></itunes:author>' => '',
                         '<itunes:explicit></itunes:explicit>' => '',
                         '<blockquote>  <p>' => '<blockquote><p>', '<a  href=' => '<a href=',
                        );
        $xmlOut = str_replace(array_keys($forbid), array_values($forbid), $xmlOut);

        $pattern = "#<\s*?itunes:owner\b[^>]*>(.*?)</itunes:owner\b[^>]*>#s";
        preg_match($pattern, $xmlOut, $matches);
        if ( is_array($matches) && count($matches) > 0 ) {
            if ( NoNull($matches[1]) == '' ) {
                $xmlOut = str_replace($matches[0], '', $xmlOut);
            }
        }

        $lines = explode("\n", $xmlOut);
        if ( is_array($lines) ) {
            $xmlOut = '';
            foreach( $lines as $line ) {
                if ( NoNull($line) != '' ) {
                    if ( $xmlOut != '' ) { $xmlOut .= "\n"; }
                    $xmlOut .= rtrim($line);
                }
            }
        }

        // Return the Completed XML Object
        return NoNull($xmlOut);
    }

    /**
     *  Function Returns a Completed <enclosure> Element for the XML RSS Feed
     */
    private function _getPostEnclosure( $PostID, $Limit = 1 ) {
        if ( nullInt($PostID) > 0 ) {
            $ReplStr = array( '[POST_ID]' => nullInt($PostID),
                              '[COUNT]'   => nullInt($Limit, 1),
                             );
            $sqlStr = readResource(SQL_DIR . '/posts/getPostEnclosure.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $data = array();
                foreach ( $rslt as $Row ) {
                    $fileName = CDN_PATH . NoNull($Row['episode_file']);
                    if ( file_exists($fileName) ) {
                        $ext = getFileExtension($Row['episode_file']);
                        $name = NoNull($Row['episode_file']);
                        if ( strpos($name, '/') !== false ) {
                            $nn = explode('/', $name);
                            if ( NoNull($nn[count($nn) - 1]) != '' ) {
                                $name = NoNull($nn[count($nn) - 1]);
                            }
                        }

                        return array( 'name'    => NoNull($name),
                                      'size'    => filesize($fileName),
                                      'type'    => getMimeFromExtension($ext),
                                      'url'     => NoNull($Row['episode_file']),
                                      'summary' => NoNull($Row['episode_summary']),
                                      'number'  => NoNull($Row['episode_number']),
                                      'time'    => NoNull($Row['episode_time'])
                                     );
                    }
                }
            }
        }

        // If We're Here, There's Nothing
        return false;
    }

    /** ********************************************************************* *
     *  Hash & Word Lookup Functions
     ** ********************************************************************* */
    private function _getWordHistory() {
        $excludes = array( '#' );
        $word = NoNull($this->settings['word'], $this->settings['hash']);
        $word = strip_tags(str_replace($excludes, '', $word));

        if ( mb_strlen($word) < 1 ) { return "Please provide a word to look for"; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[WORD]'       => sqlScrub($word),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/getWordHistory.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = false;

            foreach ( $rslt as $Row ) {
                $data = array( 'word'       => NoNull($Row['word']),
                               'instances'  => nullInt($Row['instances']),
                               'yours'      => nullInt($Row['yours']),
                               'first_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['first_at'])),
                               'first_unix' => strtotime($Row['first_at']),
                               'until_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['recent_at'])),
                               'until_unix' => strtotime($Row['recent_at']),
                              );
            }

            /* If we have data, let's return it */
            if ( is_array($data) && count($data) > 0 ) { return $data; }
        }

        /* If we're here, we could not collect the Word history. Return an Empty Array. */
        return array();
    }

    /** ********************************************************************* *
     *  Class Functions
     ** ********************************************************************* */
    /**
     *  Function Converts a Time from the Account's Current Timezone to UTC
     */
    private function _convertTimeToUTC( $DateString ) {
        $offset = nullInt($this->settings['_timezone']) * 3600;
        $dts = strtotime($DateString);

        return date("Y-m-d H:i:s", $dts + $offset);
    }

    /**
     *  Function Converts a Tag Name to a Valid Slug
     */
    private function _getSafeTagSlug( $TagName ) {
        $ReplStr = array( ' ' => '-', '--' => '-' );
        $dash = '-';
        $tag = strtolower(trim(preg_replace('/[\s-]+/', $dash, preg_replace('/[^A-Za-z0-9-]+/', $dash, preg_replace('/[&]/', 'and', preg_replace('/[\']/', '', iconv('UTF-8', 'ASCII//TRANSLIT', NoNull($TagName)))))), $dash));

        for ( $i = 0; $i < 10; $i++ ) {
            $tag = str_replace(array_keys($ReplStr), array_values($ReplStr), $tag);
            if ( mb_substr($tag, 0, 1) == '-' ) { $tag = mb_substr($tag, 1); }
        }
        return NoNull($tag, strtolower(str_replace(array_keys($ReplStr), array_values($ReplStr), $TagName)));
    }

    /**
     *  Function Checks that a Post Slug is Unique and Valid
     */
    private function _checkUniqueSlug( $ChannelGUID, $PostGUID, $PostSlug ) {
        $Excludes = array( 'feeds', 'images', 'api', 'cdn', 'note', 'article', 'bookmark', 'quotation', 'location', 'archive',
                           'account', 'accounts', 'contact', 'profile', 'settings', 'messages', 'file', 'files',
                          );

        // Ensure the PostSlug is not ending in a dash or contains multiple dashes in a row
        if ( strpos($PostSlug, '-') >= 0 ) {
            $blks = explode('-', $PostSlug);
            $PostSlug = '';
            foreach ( $blks as $blk ) {
                if ( NoNull($blk) != '' ) {
                    if ( $PostSlug != '' ) { $PostSlug .= '-'; }
                    $PostSlug .= NoNull($blk);
                }
            }
        }

        // Check the Slug against the Database
        if ( in_array($TrySlug, $Excludes) === false ) {
            $ReplStr = array( '[CHANNEL_GUID]' => sqlScrub($ChannelGUID),
                              '[POST_GUID]'    => sqlScrub($PostGUID),
                              '[POST_SLUG]'    => sqlScrub($PostSlug),
                             );
            $sqlStr = prepSQLQuery("CALL CheckUniqueSlug('[CHANNEL_GUID]', '[POST_GUID]', '[POST_SLUG]');", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    return NoNull($Row['slug']);
                }
            }
        }

        // If We're Here, Then Something's Off. Return an Empty String to Force a GUID
        return '';
    }

    /**
     *  Function Checks that a Canonical URL is Unique and Valid
     */
    private function _checkUniqueCanonUrl( $ChannelGUID, $PostGUID, $PostUrl ) {
        $Excludes = array( 'feeds', 'images', 'api', 'cdn', 'note', 'article', 'bookmark', 'quotation', 'location', 'archive',
                           'account', 'accounts', 'contact', 'profile', 'settings', 'messages', 'file', 'files',
                          );

        // Ensure the PostUrl is not ending in a dash or contains multiple dashes in a row
        if ( strpos($PostUrl, '-') >= 0 ) {
            $blks = explode('-', $PostUrl);
            $PostUrl = '';
            foreach ( $blks as $blk ) {
                if ( NoNull($blk) != '' ) {
                    if ( $PostUrl != '' ) { $PostUrl .= '-'; }
                    $PostUrl .= NoNull($blk);
                }
            }
        }

        // Check the Slug against the Database
        if ( in_array($TrySlug, $Excludes) === false ) {
            $ReplStr = array( '[CHANNEL_GUID]' => sqlScrub($ChannelGUID),
                              '[POST_GUID]'    => sqlScrub($PostGUID),
                              '[POST_URL]'     => sqlScrub($PostUrl),
                             );
            $sqlStr = prepSQLQuery("CALL CheckUniqueCanonUrl('[CHANNEL_GUID]', '[POST_GUID]', '[POST_URL]');", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    return NoNull($Row['url']);
                }
            }
        }

        // If We're Here, Then Something's Off. Return an Empty String to Force a GUID
        return '';
    }

    /**
     *  Function is used by the isRTL() function to determine text direction
     */
    private function _uniord($u) {
        $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
        $k1 = ord(substr($k, 0, 1));
        $k2 = ord(substr($k, 1, 1));
        return $k2 * 256 + $k1;
    }

    /**
     *  Function determines if a string should be LTR or RTL
     */
    private function _isRTL($str) {
        if( mb_detect_encoding($str) !== 'UTF-8' ) {
            $str = mb_convert_encoding($str,mb_detect_encoding($str),'UTF-8');
        }

        // Check for Hebrew Characters
        $chk = preg_match("/\p{Hebrew}/u", $str);
        if ( (is_bool($chk) && $chk) || $chk == 1 ) {
            if ( hebrev($str) == $str ) { return true; }
        }

        // Check for Urdu Characters
        $chk = preg_match("/\p{Urdu}/u", $str);
        if ( (is_bool($chk) && $chk) || $chk == 1 ) { return true; }

        // Check for Persian Characters
        $chk = preg_match("/\p{Persian}/u", $str);
        if ( (is_bool($chk) && $chk) || $chk == 1 ) { return true; }

        // Check for Arabic Characters
        preg_match_all('/.|\n/u', $str, $matches);
        $chars = $matches[0];
        $arabic_count = 0;
        $latin_count = 0;
        $total_count = 0;
        foreach($chars as $char) {
            $pos = $this->_uniord($char);

            if($pos >= 1536 && $pos <= 1791) {
                $arabic_count++;
            } else if($pos > 123 && $pos < 123) {
                $latin_count++;
            }
            $total_count++;
        }

        // If we have 60% or more Arabic characters, it's RTL
        if ( ($arabic_count/$total_count) > 0.6 ) { return true; }
        return false;
    }

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