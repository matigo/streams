<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to manage Posts
 */
require_once(LIB_DIR . '/functions.php');
require_once(LIB_DIR . '/markdown.php');
use \Michelf\Markdown;

class Posts {
    var $settings;
    var $strings;
    var $geo;

    function __construct( $settings, $strings = false ) {
        $this->settings = $settings;
        $this->strings = ((is_array($strings)) ? $strings : getLangDefaults($this->settings['_language_code']));
        $this->geo = false;
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
        if ( mb_strlen($Activity) == 36 ) { $Activity = 'read'; }
        $rVal = false;

        switch ( $Activity ) {
            case 'globals':
            case 'global':
                $rVal = $this->_getTLStream('global');
                break;

            case 'mentions':
            case 'mention':
                $rVal = $this->_getTLStream('mentions');
                break;

            case 'home':
                $rVal = $this->_getTLStream('home');
                break;

            case 'interactions':
            case 'interaction':
            case 'actions':
                $rVal = $this->_getTLStream('interact');
                break;

            case 'list':
            case '':
                $rVal = false;
                break;

            case 'read':
                $rVal = $this->_getPostByGUID();
                break;

            case 'thread':
                $rVal = $this->_getThreadByGUID();
                break;

            default:

        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( mb_strlen($Activity) == 36 ) { $Activity = 'edit'; }
        $rVal = false;

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case 'write':
            case 'edit':
            case '':
                $rVal = $this->_writePost();
                break;

            case 'pin':
                $rVal = $this->_setPostPin();
                break;

            case 'star':
                $rVal = $this->_setPostStar();
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performDeleteAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( mb_strlen($Activity) == 36 ) { $Activity = 'delete'; }
        $rVal = false;

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case 'delete':
            case '':
                $rVal = $this->_deletePost();
                break;

            case 'pin':
                $rVal = $this->_setPostPin();
                break;

            case 'star':
                $rVal = $this->_setPostStar();
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
        $PostGUID = strtolower(NoNull($this->settings['guid'], $this->settings['PgSub1']));
        $SimpleHtml = YNBool(NoNull($this->settings['simple']));

        if ( mb_strlen($PostGUID) != 36 ) { $this->_setMetaMessage("Invalid Thread Identifier Supplied (1)", 400); return false; }

        $ReplStr = array( '[POST_GUID]' => sqlScrub($PostGUID) );
        $sqlStr = readResource(SQL_DIR . '/posts/getThreadPostIDs.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $PostIDs = array();
            foreach ( $rslt as $Row ) {
                $PostIDs[] = nullInt($Row['post_id']);
            }

            if ( count($PostIDs) > 0 ) {
                $posts = $this->_getPostsByIDs(implode(',', $PostIDs));
                if ( is_array($posts) ) {
                    $data = array();
                    $reply_url = false;

                    foreach ( $posts as $post ) {
                        if ( $SimpleHtml === false ) {
                            $html = $this->_buildHTMLElement(array(), $post);
                            $ReplStr = array( '  ' => ' ', "\n <" => "\n<" );
                            for ( $i = 0; $i < 100; $i++ ) {
                                $html = str_replace(array_keys($ReplStr), array_values($ReplStr), $html);
                            }
                            $post['html'] = NoNull($html);
                        }

                        if ( $post['guid'] == $PostGUID ) { $reply_url = $post['reply_to']; }
                        $post['is_selected'] = (($post['guid'] == $PostGUID) ? true : false);
                        $post['is_reply_to'] = (($reply_url !== false && $reply_url == $post['canonical_url']) ? true : false);

                        $data[] = $post;
                    }

                    // Return the Data If We Have It
                    if ( count($data) > 0 ) { return $data; }
                }
            }
        }

        // If We're Here, the Post.guid Was Not Found (or is Inaccessible)
        $this->_setMetaMessage("Invalid Thread Identifier Supplied (2)", 400);
        return false;
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
                $ReplStr[ $u['name'] . '</' ]  = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span></';
                $ReplStr[ $u['name'] . '<br' ] = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span><br';
                $ReplStr[ $u['name'] . '<hr' ] = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span><hr';
                $ReplStr[ $u['name'] . '?' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>?';
                $ReplStr[ $u['name'] . '!' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>!';
                $ReplStr[ $u['name'] . '.' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>.';
                $ReplStr[ $u['name'] . ':' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>:';
                $ReplStr[ $u['name'] . ';' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>;';
                $ReplStr[ $u['name'] . ',' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>,';
                $ReplStr[ $u['name'] . ' ' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span> ';
                $ReplStr[ $u['name'] . ')' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>)';
                $ReplStr[ $u['name'] . "'" ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>'";
                $ReplStr[ $u['name'] . "’" ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>’";
                $ReplStr[ $u['name'] . '-' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>-';
                $ReplStr[ $u['name'] . '"' ]   = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . '</span>"';
                $ReplStr[ $u['name'] . "\n" ]  = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>\n";
                $ReplStr[ $u['name'] . "\r" ]  = '<span class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>\r";
                $ReplStr[ "/" . $u['name'] ]  = "/<span" . ' class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>";
                $ReplStr[ "\n" . $u['name'] ]  = "\n<span" . ' class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>";
                $ReplStr[ "\r" . $u['name'] ]  = "\r<span" . ' class="account" data-guid="' . $u['guid'] . '">' . $u['name'] . "</span>";
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

                                 'publish_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['publish_at'])),
                                 'publish_unix' => strtotime($Row['publish_at']),
                                 'expires_at'   => ((NoNull($Row['expires_at']) != '') ? date("Y-m-d\TH:i:s\Z", strtotime($Row['expires_at'])) : false),
                                 'expires_unix' => ((NoNull($Row['expires_at']) != '') ? strtotime($Row['expires_at']) : false),
                                 'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                 'updated_unix' => strtotime($Row['updated_at']),

                                 'meta'     => $poMeta,
                                 'tags'     => $poTags,
                                 'mentions' => $mentions,

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
                                 'client'   => array( 'guid'    => NoNull($Row['client_guid']),
                                                      'name'    => NoNull($Row['client_name']),
                                                      'logo'    => $cdnURL . NoNull($Row['client_logo_img']),
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
            $mids = array();

            // Collect the Mentions
            foreach ( $rslt as $Row ) {
                if ( YNBool($Row['has_mentions']) ) {
                    $mids[] = nullInt($Row['post_id']);
                }
            }
            if ( count($mids) > 0 ) {
                $pms = $this->_getPostMentions($mids);
            }

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
                if ( in_array(NoNull($Row['post_type']), array('post.article', 'post.quotation', 'post.bookmark')) ) { $IsNote = false; }
                $post_text = $this->_getMarkdownHTML($Row['value'], $Row['post_id'], $IsNote, true);
                $post_text = $this->_parsePostMentions($post_text);

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

                                 'publish_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['publish_at'])),
                                 'publish_unix' => strtotime($Row['publish_at']),
                                 'expires_at'   => ((NoNull($Row['expires_at']) != '') ? date("Y-m-d\TH:i:s\Z", strtotime($Row['expires_at'])) : false),
                                 'expires_unix' => ((NoNull($Row['expires_at']) != '') ? strtotime($Row['expires_at']) : false),
                                 'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                 'updated_unix' => strtotime($Row['updated_at']),

                                 'meta'     => $poMeta,
                                 'tags'     => $poTags,
                                 'mentions' => $mentions,

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
                                 'client'   => array( 'guid'    => NoNull($Row['client_guid']),
                                                      'name'    => NoNull($Row['client_name']),
                                                      'logo'    => $cdnURL . NoNull($Row['client_logo_img']),
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
            foreach ( $rslt as $Row ) {
                $post_id = nullInt($Row['post_id']);
            }
        }

        // If It's Good, Record the Meta Data & Collect the Post Object to Return
        if ( nullInt($data['post_id'], $post_id) >= 1 ) {
            // Reset the SQL String
            $sqlStr = '';

            // Record the MetaData for the Post
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

            // Record the Tags for the Post
            if ( NoNull($data['tags']) != '' ) {
                $tgs = explode(',', NoNull($data['tags']));
                $lst = '';
                foreach ( $tgs as $Value ) {
                    $pid = nullInt($data['post_id'], $rslt);
                    $Key = $this->_getSafeTagSlug(NoNull($Value));
                    $Val = sqlScrub($Value);

                    if ( $lst != '' ) { $lst .= ","; }
                    $lst .= "($pid, '$Key', '$Val')";
                }

                // Extract the Tags from Inside the Post Text
                $lst_hash = $this->_getTagsFromPost($data['value'], nullInt($data['post_id'], $rslt));
                if ( $lst_hash != '' ) { $lst .= ",$lst_hash"; }

                if ( NoNull($lst) != '' ) {
                    $ReplStr = array( '[POST_ID]'    => nullInt($data['post_id'], $post_id),
                                      '[VALUE_LIST]' => NoNull($lst)
                                     );
                    if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
                    $sqlStr .= readResource(SQL_DIR . '/posts/writePostTags.sql', $ReplStr);
                }
            }

            // Execute the Queries
            $isOK = doSQLExecute($sqlStr);

            // Send any Webmentions or Pingbacks (If Applicable) [Disabled for now 2020-01-12]
            // $this->_setPostPublishData(nullInt($data['post_id'], $post_id));

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

        // Ensure we Have the requisite GUIDs
        if ( mb_strlen($PersonaGUID) <= 30 ) { return false; }
        if ( mb_strlen($PostGUID) <= 30 ) { return false; }

        // Build and Run the SQL Query
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[POST_GUID]'    => sqlScrub($PostGUID),
                          '[PERSONA_GUID]' => sqlScrub($PersonaGUID),
                         );
        $sqlStr = readResource(SQL_DIR . '/posts/preparePostAction.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // If We Have a Result, It's Good
        if ( $rslt ) { return true; }
        return false;
    }

    private function _setPostPin() {
        $PersonaGUID = NoNull($this->settings['persona_guid'], $this->settings['_persona_guid']);
        $PostGUID = NoNull($this->settings['post_guid'], NoNull($this->settings['guid'], $this->settings['PgSub1']));
        $PinValue = NoNull($this->settings['pin_value'], $this->settings['value']);
        $ReqType = NoNull(strtolower($this->settings['ReqType']));

        // Ensure we Have the requisite GUIDs
        if ( mb_strlen($PersonaGUID) <= 30 ) { $this->_setMetaMessage("Invalid Persona GUID Supplied", 400); return false; }
        if ( mb_strlen($PostGUID) <= 30 ) { $this->_setMetaMessage("Invalid Post GUID Supplied", 400); return false; }
        $this->settings['guid'] = $PostGUID;
        $this->settings['ReqType'] = 'GET';

        // Ensure the Pin Value is Logical
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
     *  Function Records (or Resets) a Post Pin for a Post/Persona combination
     */
    private function _setPostStar() {
        $PersonaGUID = NoNull($this->settings['persona_guid']);
        $PostGUID = NoNull($this->settings['post_guid'], NoNull($this->settings['guid'], $this->settings['PgSub1']));
        $ReqType = NoNull(strtolower($this->settings['ReqType']));

        // Ensure we Have the requisite GUIDs
        if ( mb_strlen($PersonaGUID) <= 30 ) { $this->_setMetaMessage("Invalid Persona GUID Supplied", 400); return false; }
        if ( mb_strlen($PostGUID) <= 30 ) { $this->_setMetaMessage("Invalid Post GUID Supplied", 400); return false; }
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

    /** ********************************************************************* *
     *  MetaData Functions
     ** ********************************************************************* */
    /**
     *  Function Returns the MetaData for a Given Post.guid using mostly-consistent array structures.
     *      Dynamic keys can be used as well, so long as they're consistently applied.
     */
    private function _getPostMeta( $PostGUID ) {
        if ( mb_strlen(NoNull($PostGUID)) != 36 ) { return false; }

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

            // If there's a Geo Array, Check if a StaticMap can be Provided
            if ( array_key_exists('geo', $data) ) {
                if ( $data['geo']['longitude'] !== false && $data['geo']['latitude'] !== false ) {
                    $data['geo']['staticmap'] = NoNull($this->settings['HomeURL']) . '/api/geocode/staticmap/' . round($data['geo']['latitude'], 5) . '/' . round($data['geo']['longitude'], 5);
                }
            }

            // If we have data, return it.
            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There's No Meta
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
        $Value = NoNull($this->settings['post_text'], NoNull($this->settings['text'], $this->settings['content']));
        $CanonURL = NoNull($this->settings['canonical_url'], $this->settings['post_url']);
        $ReplyTo = NoNull($this->settings['post_reply_to'], $this->settings['reply_to']);
        $PostSlug = NoNull($this->settings['post_slug'], $this->settings['slug']);
        $PostType = NoNull($this->settings['post_type'], NoNull($this->settings['type'], 'post.draft'));
        $Privacy = NoNull($this->settings['post_privacy'], $this->settings['privacy']);
        $PublishAt = NoNull($this->settings['post_publish_at'], $this->settings['publish_at']);
        $ExpiresAt = NoNull($this->settings['post_expires_at'], $this->settings['expires_at']);
        $PostTags = NoNull($this->settings['post_tags'], $this->settings['tags']);
        $PostGUID = NoNull($this->settings['post_guid'], NoNull($this->settings['guid'], $this->settings['PgSub1']));
        $PostID = $this->_getPostIDFromGUID($PostGUID);

        // More Elements
        $ParentID = 0;
        $ThreadID = 0;
        $PublishUnix = strtotime($PublishAt);

        // Additional Meta
        $SourceURL = NoNull($this->settings['source_url'], $this->settings['source']);
        $SourceTitle = NoNull($this->settings['source_title']);

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

        // Check the Post Text for Additionals
        $hash_list = $this->_extractPostTags($Value);
        if ( $hash_list != '' ) {
            if ( $PostTags != '' ) { $PostTags .= ','; }
            $PostTags .= $hash_list;
        }

        // Token Definition
        $TokenGUID = '';
        $TokenID = 0;
        $isValid = true;

        // Get the Token Information
        if ( NoNull($this->settings['token']) != '' ) {
            $data = explode('_', NoNull($this->settings['token']));
            if ( count($data) == 3 ) {
                if ( $data[0] == str_replace('_', '', TOKEN_PREFIX) ) {
                    $TokenGUID = NoNull($data[2]);
                    $TokenID = alphaToInt($data[1]);
                }
            }
        }

        // Validate the Requisite Data
        if ( mb_strlen($ChannelGUID) != 36 ) { $this->_setMetaMessage("Invalid Channel GUID Supplied", 400); $isValid = false; }
        if ( mb_strlen($PersonaGUID) != 36 ) { $this->_setMetaMessage("Invalid Persona GUID Supplied", 400); $isValid = false; }
        if ( $PostType == '' ) { $PostType = 'post.draft'; }
        if ( $Privacy == '' ) { $Privacy = 'visibility.public'; }

        // Ensure the Dates are Set to UTC
        if ( strtotime($PublishAt) === false ) { $PublishAt = ''; }
        if ( strtotime($ExpiresAt) === false ) { $ExpiresAt = ''; }
        if ($PublishAt != '') { $PublishAt = $this->_convertTimeToUTC($PublishAt); }
        if ($ExpiresAt != '') { $ExpiresAt = $this->_convertTimeToUTC($ExpiresAt); }

        // Ensure the Expiration Is Valid (If It Exists)
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
                    $PostSlug = $this->_getSafeTagSlug($Title);

                    // Check if the Slug is Unique
                    $PostSlug = $this->_checkUniqueSlug($ChannelGUID, $PostGUID, $PostSlug);

                    // If the Slug is Not Empty, Set the Canonical URL Value
                    if ( $PostSlug != '' ) {
                        $SlugPrefix = '';
                        if ( nullInt($PublishUnix) >= strtotime('1975-01-01 00:00:00') ) {
                            $SlugPrefix = date('Y/m/d', $PublishUnix);
                        }
                        $CanonURL = NoNull('/' . NoNull($SlugPrefix, 'article') . "/$PostSlug");
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

        // If Something Is Wrong, Return an Unhappy Boolean
        if ( $isValid !== true ) { return false; }

        // Can we Identify a ParentID and a Thread Based on the ReplyTo? (If Applicable)
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

                      'title'         => $Title,
                      'value'         => $this->_cleanContent($Value),

                      'canonical_url' => $CanonURL,
                      'reply_to'      => $ReplyTo,

                      'slug'          => $PostSlug,
                      'type'          => $PostType,
                      'privacy'       => $Privacy,

                      'publish_at'    => $PublishAt,
                      'expires_at'    => $ExpiresAt,

                      'words'         => $UniqueWords,
                      'tags'          => $PostTags,
                      'meta'          => array( 'source_url'      => $SourceURL,
                                                'source_title'    => $SourceTitle,
                                                'geo_latitude'    => $GeoLat,
                                                'geo_longitude'   => $GeoLong,
                                                'geo_altitude'    => $GeoAlt,
                                                'geo_description' => $GeoFull,
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
        $ReplStr = array( '//@' => '// @', '<p>' => '', '</p>' => "\r\n", '<strong>' => '**', '</strong>' => '**', '<em>' => '*', '</em>' => '*',
                          '<p class="">' => '', 'ql-align-justify' => '', 'ql-align-center' => '', 'ql-align-right' => '',
                         );

        for ( $i = 0; $i < 5; $i++ ) {
            $text = NoNull(str_replace(array_keys($ReplStr), array_values($ReplStr), $text));
        }

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
        $CacheFile = 'popular-' . date('Ymdh');
        $html = '';

        // Check for a Cache File and Return It If Valid
        if ( defined('ENABLE_CACHING') === false ) { define('ENABLE_CACHING', 0); }
        if ( nullInt(ENABLE_CACHING) == 1 ) { $html = readCache($this->settings['site_id'], $CacheFile); }
        if ( $html !== false && $html != '' ) { return $html; }

        // If We're Here, Let's Construct the Popular Posts List
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

            // Save the File to Cache if Required
            if ( nullInt(ENABLE_CACHING) == 1 ) { saveCache($this->settings['site_id'], $CacheFile, $html); }

            // Return the List of Popular Posts
            return $html;
        }

        // If We're Here, There's Nothing.
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

        $rslt = getPaginationSets();
        if ( is_array($rslt) === false && in_array($PgRoot, $Excludes) === false ) {
            // Construct the SQL Query
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                              '[SITE_TOKEN]' => sqlScrub(NoNull($this->settings['site_token'])),
                              '[SITE_GUID]'  => sqlScrub($data['site_guid']),
                              '[CANON_URL]'  => sqlScrub($CanonURL),
                              '[PGROOT]'     => sqlScrub($PgRoot),
                              '[OBJECT]'     => sqlScrub($tObj),
                             );
            $sqlStr = prepSQLQuery("CALL GetSitePagination([ACCOUNT_ID], '[SITE_GUID]', '[SITE_TOKEN]', '[CANON_URL]', '[PGROOT]', '[OBJECT]', '');", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
        }

        // At this point, we should have data. Let's build some pagination
        if ( is_array($rslt) ) {
            setPaginationSets($rslt);
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
                    $el = $this->_buildHTMLElement($data, $post);
                    if ( $el != '' ) {
                        $postClass = NoNull($post['class']);
                        if ( $postClass != '' ) { $postClass .= ' '; }

                        // Determine the Template File
                        $flatFile = THEME_DIR . '/' . $data['location'] . '/flats/' . $post['type'] . '.html';

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
    private function _buildHTMLElement($data, $post) {
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
                    $tagLine .= '<li><a href="' . NoNull($tag['url']) . '" class="p-category">' . NoNull($tag['name']) . '</a></li>';
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
                              '[BANNER]'        => '',
                              '[TAGLINE]'       => NoNull($tagLine, $this->strings['lblNoTags']),
                              '[HOMEURL]'       => NoNull($this->settings['HomeURL']),
                              '[GEOTAG]'        => $geoLine,
                              '[THREAD]'        => $PostThread,
                              '[SOURCE_NETWORK]'=> NoNull($post['meta']['source']['network']),
                              '[SOURCE_ICON]'   => $SourceIcon,
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
        $sqlStr = readResource(SQL_DIR . '/posts/getPostPublishData.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            require_once( LIB_DIR . '/webmention.php' );
            $webm = new Webmention( $this->settings, $this->strings );
            $data = $webm->performAction();

            foreach ( $rslt as $Row ) {
                $PostText = $this->_getMarkdownHTML($Row['post_text'], $Row['post_id'], YNBool($Row['is_note']), true);
                $PostUrl = NoNull($Row['post_url']);

                // Send the Webmentions
                $data = $webm->sendMentions($PostUrl, $PostText);
            }
            unset($webm);
        }

        // Return a Happy Boolean
        return true;
    }

    /** ********************************************************************* *
     *  Timeline / Stream Functions
     ** ********************************************************************* */
    private function _processTimeline( $posts ) {
        if ( is_array($posts) ) {
            $default_avatar = $this->settings['HomeURL'] . '/avatars/default.png';
            $data = array();

            foreach ( $posts as $post ) {
                if ( YNBool($post['is_visible']) ) {
                    // Is there Meta-Data? If So, Grab It
                    $poMeta = false;
                    if ( YNBool($post['has_meta']) ) { $poMeta = $this->_getPostMeta($post['post_guid']); }
                    if ( NoNull($this->settings['nom']) != '' ) {
                        if ( is_array($poMeta) === false ) { $poMeta = array(); }
                        $poMeta['nom'] = NoNull($this->settings['nom']);
                    }

                    // Process any Tags
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

                    // Do We Have Mentions? Grab the List
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

                    // Prep the Post-Text
                    $IsNote = true;
                    if ( in_array(NoNull($Row['post_type']), array('post.article', 'post.quotation', 'post.bookmark')) ) { $IsNote = false; }
                    $post_text = $this->_getMarkdownHTML($post['value'], $post['post_id'], $IsNote, true);
                    if ( is_array($mentions) ) {
                        $post_text = $this->_parsePostMentions($post_text, $mentions);
                    }

                    $data[] = array( 'guid'     => NoNull($post['post_guid']),
                                     'type'     => NoNull($post['type']),
                                     'privacy'  => NoNull($post['privacy_type']),

                                     'canonical_url' => NoNull($post['canonical_url']),
                                     'reply_to'      => ((NoNull($post['reply_to']) == '') ? false : NoNull($post['reply_to'])),

                                     'title'    => ((NoNull($post['title']) == '') ? false : NoNull($post['title'])),
                                     'content'  => $post_text,
                                     'text'     => NoNull($post['value']),

                                     'meta'     => $poMeta,
                                     'tags'     => $poTags,
                                     'mentions' => $mentions,

                                     'persona'  => array( 'guid'        => NoNull($post['persona_guid']),
                                                          'as'          => '@' . NoNull($post['persona_name']),
                                                          'name'        => NoNull($post['display_name']),
                                                          'avatar'      => NoNull($post['avatar_url']),

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

                                     'publish_at'   => date("Y-m-d\TH:i:s\Z", strtotime($post['publish_at'])),
                                     'publish_unix' => strtotime($post['publish_at']),
                                     'expires_at'   => ((NoNull($post['expires_at']) == '') ? false : date("Y-m-d\TH:i:s\Z", strtotime($post['expires_at']))),
                                     'expires_unix' => ((NoNull($post['expires_at']) == '') ? false : strtotime($post['expires_at'])),
                                     'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($post['updated_at'])),
                                     'updated_unix' => strtotime($post['updated_at']),
                                    );
                }
            }

            // Set the "HasMore" Meta value
            $CleanCount = nullInt($this->settings['count'], 100);
            if ( $CleanCount > 250 ) { $CleanCount = 250; }
            if ( $CleanCount <= 0 ) { $CleanCount = 100; }
            if ( $CleanCount == count($data) ) { $this->settings['has_more'] = true; }

            // Return the Data If We Have Some
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

        // Get the Types Requested (Default is Social Posts Only)
        $validTypes = array( 'post.article', 'post.note', 'post.quotation', 'post.bookmark', 'post.location' );
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
            // If there are no results, and the Since/Until is set as 0, expand the criteria
            if ( nullInt($this->settings['since']) <= 0 ) {
                $this->settings['before'] = 0;
                $this->settings['since'] = 1;

                // Run the Query One More Time
                return $this->_getTLStream($path);
            }
        }

        // If We're Here, No Posts Could Be Retrieved
        return array();
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
            $out_str .= ($hash != '') ? str_ireplace($clean_word, '<a class="hash" href="[HOMEURL]/tag/' . strtolower($hash) . '" data-hash="' . strtolower($hash) . '">' . NoNull($clean_word) . '</a> ', $word)
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
                $name = NoNull($clean_word);

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

        // Check to See If We Have a Cached Version of the Feed
        $cache_file = $site['site_version'] . '-' . NoNull($format, 'xml') . NoNull($rtSuffix, '-feed');
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

        // If We're Here, Build the Feed
        $ReplStr = array( '[SITE_URL]'       => sqlScrub($site['HomeURL']),
                          '[SHOW_ARTICLE]'   => sqlScrub($ReqTypes['post.article']),
                          '[SHOW_BOOKMARK]'  => sqlScrub($ReqTypes['post.bookmark']),
                          '[SHOW_QUOTATION]' => sqlScrub($ReqTypes['post.quotation']),
                          '[SHOW_NOTE]'      => sqlScrub($ReqTypes['post.note']),
                          '[COUNT]'          => nullInt($site['RssLimit'], 100),
                         );
        $sqlStr = prepSQLQuery("CALL GetSyndicationContent('[SITE_URL]', '[SHOW_ARTICLE]', '[SHOW_BOOKMARK]', '[SHOW_QUOTATION]', '[SHOW_NOTE]', 'Y', [COUNT], 0);", $ReplStr);
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
                        foreach ( $encl as $att ) {
                            $item['attachments'][] = array( 'url'           => $SiteURL . NoNull($att['url']),
                                                            'mime_type'     => NoNull($att['type']),
                                                            'size_in_bytes' => nullInt($att['size']),
                                                           );
                        }
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

                $text = html_entity_decode(strip_tags(str_replace('</p>', "</p>\n\n", $html)));

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
                               '[EXPLICIT]'     => NoNull($post['explicit'], NoNull($site['rss_explicit'], 'Clean')),

                               '[ENCL_LINK]'    => (($encl !== false) ? $SiteURL . NoNull($encl['url']) : ''),
                               '[ENCL_NAME]'    => (($encl !== false) ? NoNull($encl['name']) : ''),
                               '[ENCL_SIZE]'    => (($encl !== false) ? nullInt($encl['size']) : ''),
                               '[ENCL_TIME]'    => (($encl !== false) ? NoNull($encl['time'], '00:00') : ''),
                               '[ENCL_TYPE]'    => (($encl !== false) ? NoNull($encl['type']) : ''),

                               '[POST_BANR]'    => NoNull($post['post_banner'], $site['rss_cover']),
                               '[POST_SUBS]'    => NoNull($post['post_subtitle']),
                               '[POST_SUMM]'    => NoNull($post['post_summary']),
                               '[POST_TYPE]'    => NoNull($post['post_type']),
                               '[POST_TEXT]'    => NoNull(str_replace(array_keys($inplace), array_values($inplace), $text)),
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
                         '<itunes:image href=""/>' => '', '<itunes:duration>00:00</itunes:duration>' => '', '<itunes:email></itunes:email>' => '',
                         '<itunes:name></itunes:name>' => '', '<itunes:author></itunes:author>' => '',
                         '<blockquote>  <p>' => '<blockquote><p>',
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
                    $data[] = array( 'name' => NoNull($Row['public_name']),
                                     'size' => nullInt($Row['bytes']),
                                     'type' => NoNull($Row['type']),
                                     'url'  => NoNull($Row['url']),
                                    );
                }

                // Return the Data
                if ( count($data) > 0 ) {
                    if ( $Limit == 1 ) { return $data[0]; }
                    return $data;
                }
            }
        }

        // If We're Here, There's Nothing
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
                           'account', 'accounts', 'contact', 'profile', 'settings', 'messages'
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
}
?>