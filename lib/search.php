<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to search Channels
 */
require_once( LIB_DIR . '/functions.php');

class Search {
    var $settings;

    function __construct( $Items ) {
        $this->settings = $Items;
        $this->_populateClass();
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    public function performAction() {
        $ReqType = NoNull(strtolower($this->settings['ReqType']));
        $rVal = false;

        // Perform the Action if permissions exist
        if ( $this->settings['_can_access'] ) {
            switch ( $ReqType ) {
                case 'get':
                    $rVal = $this->_performGetAction();
                    break;

                case 'post':
                    $rVal = $this->_performPostAction();
                    break;

                default:
                    // Do Nothing
            }

        } else {
            $this->_setMetaMessage('You do not have permission to search this channel.', 403);
        }

        // Return The Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performGetAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( nullInt($this->settings['PgSub1']) > 0 ) { $Activity = 'list'; }
        $rVal = false;

        switch ( $Activity ) {
            case 'list':
            case '':
                $rVal = $this->_getSearchResult();
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
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
    private function _getHighlights( $text, $uniques ) {
        $text = strip_tags($text);
        $words = explode(' ', " $text ");
        $minID = false;
        $chars = nullInt($this->settings['_summary_chars'], 50);
        $cnt = 0;

        // Find the first unique
        foreach ( $words as $word ) {
            $word = NoNull(strtolower($word));
            if ( $word != '' ) {
                foreach ( $uniques as $uq ) {
                    if ( mb_strpos($word, $uq) !== false ) {
                        if ( $minID === false ) { $minID = $cnt; }
                        $key = mb_substr($words[$cnt], mb_strpos($word, $uq), mb_strlen($uq));
                        $words[$cnt] = str_ireplace($key, '<span class="highlight">' . $key . '</span>', $words[$cnt]);
                        break;
                    }
                }
            }
            $cnt++;
        }

        // Construct the Output
        $minID -= 10;
        $maxID = $minID + $chars;
        if ( $maxID > count($words) ) {
            $maxID = count($words);
            $minID = $maxID - $chars;
        }
        if ( $minID < 0 ) { $minID = 0; }

        $out = '';
        for ( $i = $minID; $i < $maxID; $i++ ) {
            $out .= $words[$i] . ' ';
        }

        if ( NoNull($out) != '' ) {
            if ( $minID > 0 ) { $out = '… ' . NoNull($out); }
            if ( $maxID < count($words) ) { $out = NoNull($out) . ' …'; }
        }
        $ReplStr = array( '[RNRN]' => '', '  ' => ' ', '… …' => '…' );
        $out = str_replace(array_keys($ReplStr), array_values($ReplStr), '<p>' . NoNull($out) . '</p>');

        $ReplStr = array( '[RNRN]' => "\n\n", "\n\n \n\n " => "\n\n", "\n\n " => "\n\n", "\n\n" => '</p><p>', '<p></p>' => '', '  ' => ' ' );
        $txt = str_replace(array_keys($ReplStr), array_values($ReplStr), '<p>' . NoNull(implode(' ', $words)) . '</p>');

        // Return the String
        return array( 'summary' => NoNull($out),
                      'simple'  => NoNull($txt),
                      'more'    => (count($words) > $chars),
                     );
    }

    /**
     *  Function is the primary Search method across the Application
     */
    private function _getSearchResult() {
        $excludes = array( 'the', 'and', 'or' );
        $SearchFor = NoNull($this->settings['for'], $this->settings['search_for']);
        $InclFull = YNBool(NoNull($this->settings['incl_html'], $this->settings['incl_full']));
        $Count = nullInt($this->settings['results'], $this->settings['count']);
        $Uniques = array();

        if ( strlen($SearchFor) <= 0 ) {
            $this->_setMetaMessage("Please enter some search criteria", 400);
            return false;
        }

        $CleanScores = '';
        $words = explode(' ', strtolower($SearchFor));
        if ( count($words) > 0 ) {
            foreach ( $words as $word ) {
                $word = NoNull($word);
                if ( strlen($word) > 1 && in_array($word, $excludes) === false && in_array($word, $Uniques) === false ) {
                    $Uniques[] = $word;
                    $CleanScores .= tabSpace(9)  . "   IFNULL(ROUND((CHAR_LENGTH(LOWER(CONCAT(IFNULL(po.`title`, ''), ' ', po.`value`, ' ', po.`canonical_url`, ' ', IFNULL(geo.`value`, ''), ' ', IFNULL(tags.`tags`, '')))) -\n" .
                                    tabSpace(13) . " CHAR_LENGTH(REPLACE(LOWER(CONCAT(IFNULL(po.`title`, ''), ' ', po.`value`, ' ', po.`canonical_url`, ' ', IFNULL(geo.`value`, ''), ' ', IFNULL(tags.`tags`, ''))), '" . sqlScrub($word) . "', ''))) / CHAR_LENGTH('" . sqlScrub($word) . "')), 0) +\n";
                }
            }
        }

        // Ensure We Still Have Some Search Criteria
        if ( count($Uniques) <= 0 ) {
            $this->_setMetaMessage("Please enter some more specific search criteria", 400);
            return false;
        }
        if ( $Count <= 0 ) { $Count = 50; }
        if ( $Count > 100 ) { $Count = 100; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[CHANNEL_ID]' => nullInt($this->settings['_channel_id']),
                          '[SITE_TOKEN]' => sqlScrub(NoNull($this->settings['site_token'])),
                          '[SCORING]'    => $CleanScores,
                          '[COUNT]'      => nullInt($Count, 50),
                         );
        $sqlStr = readResource(SQL_DIR . '/search/getSearchResult.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $CleanStr = array( '[RNRN]' => '', '</p> <p>' => '</p><p>', '</p> ' => '</p>', '  ' => ' ' );
            $HighLights = array();
            foreach ( $Uniques as $word ) {
                $HighLights[$word] = '<span class="highlight">' . $word . '</span>';
            }

            require_once(LIB_DIR . '/posts.php');
            $post = new Posts($this->settings);

            $data = array();
            foreach ( $rslt as $Row ) {
                $html = $post->getMarkdownHTML($Row['value'], nullInt($Row['post_id']), false, true);
                $html = str_replace('</p>', "</p>[RNRN]", $html);
                $snip = $this->_getHighlights($html, $Uniques);

                // So Long As We Have Text, Return the Object
                if ( NoNull($snip['simple']) != '' ) {
                    // Include the Text and rendered HTML if Requested
                    if ( $InclFull ) {
                        $snip['text'] = str_ireplace(array_keys($CleanStr), array_values($CleanStr), strip_tags($html));
                        $snip['html'] = str_ireplace(array_keys($CleanStr), array_values($CleanStr), $html);
                    }

                    $data[] = array( 'guid'     => NoNull($Row['guid']),
                                     'title'    => ((NoNull($Row['title']) != '') ? NoNull($Row['title']) : false),
                                     'content'  => $snip,

                                     'url'      => NoNull($Row['canonical_url']),
                                     'privacy'  => NoNull($Row['privacy_type']),
                                     'type'     => NoNull($Row['type']),
                                     'score'    => nullInt($Row['score']),

                                     'publish_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['publish_at'])),
                                     'publish_unix' => strtotime($Row['publish_at']),
                                     'expires_at'   => ((NoNull($Row['expires_at']) != '') ? date("Y-m-d\TH:i:s\Z", strtotime($Row['expires_at'])) : false),
                                     'expires_unix' => ((NoNull($Row['expires_at']) != '') ? strtotime($Row['expires_at']) : false),

                                     'author'   => array( 'name'         => NoNull($Row['name']),
                                                          'display_name' => NoNull($Row['display_name']),
                                                          'avatar'       => NoNull($Row['avatar_url']),
                                                         ),
                                    );
                }
            }
            unset($post);

            // If We Have Data, Return It
            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There's Nothing
        return array();
    }

    /** ********************************************************************* *
     *  Internal Functions
     ** ********************************************************************* */
    /**
     *  Function Prepares the Class for Use
     */
    private function _populateClass() {
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[SITE_TOKEN]' => sqlScrub(NoNull($this->settings['site_token'])),
                          '[SITE_ID]'    => nullInt($this->settings['site_id']),
                         );
        $sqlStr = readResource(SQL_DIR . '/search/getSearchPermissions.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $this->settings['_channel_type'] = NoNull($Row['channel_type']);
                $this->settings['_privacy_type'] = NoNull($Row['privacy_type']);
                $this->settings['_channel_id'] = nullInt($Row['channel_id']);
                $this->settings['_can_access'] = YNBool($Row['can_access']);

                $this->settings['_summary_chars'] = 75;
            }
        }
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