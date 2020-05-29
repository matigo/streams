<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to manage Location posts
 */
require_once(LIB_DIR . '/functions.php');

class Location {
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
        if ( strlen($Activity) == 36 ) { $Activity = 'item'; }

        switch ( $Activity ) {
            case 'post':
            case 'item':
            case '':
                return false;
                break;

            case 'markers':
            case 'events':
                return $this->_getMarkerList();
                break;

            default:

        }

        // If we're here, nothing was done
        return false;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( strlen($Activity) == 36 ) { $Activity = 'item'; }

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case 'post':
            case 'item':
            case '':
                return $this->_createItem();
                break;

            case 'marker':
            case 'event':
                return $this->_setMarker();
                break;

            default:
                // Do Nothing
        }

        // If we're here, nothing was done
        return false;
    }

    private function _performDeleteAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( strlen($Activity) == 36 ) { $Activity = 'item'; }

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case '':
                return array( 'delete' => $Activity );
                break;

            default:
                // Do Nothing
        }

        // If we're here, nothing was done
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

    /** ********************************************************************* *
     *  Public Functions
     ** ********************************************************************* */
    public function getMarkerList($guid) { return $this->_getMarkerList($guid); }

    /** ********************************************************************* *
     *  Item Functions
     ** ********************************************************************* */
    /**
     *  Function creates a new Item and returns an object
     */
    private function _createItem() {
        $validTypes = array('post.location');

        $Value = NoNull($this->settings['post_text'], NoNull($this->settings['text'], $this->settings['content']));
        if ( $Value == '' ) { $this->settings['post_text'] = 'Placeholder Text'; }
        $PostType = strtolower(NoNull($this->settings['post_type'], NoNull($this->settings['type'], 'post.draft')));
        if ( in_array(strtolower($PostType), $validTypes) === false ) { $PostType = 'post.location'; }
        $Privacy = strtolower(NoNull($this->settings['post_privacy'], $this->settings['privacy']));
        if ( $Privacy == '' ) { $Privacy = 'visibility.private'; }

        $this->settings['post_privacy'] = $Privacy;
        $this->settings['post_type'] = $PostType;

        require_once(LIB_DIR . '/posts.php');
        $post = new Posts($this->settings);
        $rslt = $post->writePost();
        unset($post);

        if ( is_array($rslt) ) {
            return $rslt;

        } else {
            $this->_setMetaMessage("Could not write Location Item", 400);
            return false;
        }
    }

    /**
     * Function returns a complete Item object
     */
    private function _getItem() {

    }

    /**
     *  Function returns a list of (visible) Item objects for a given account
     */
    private function _getItemList() {

    }

    /** ********************************************************************* *
     *  Item Marker Functions
     ** ********************************************************************* */
    /**
     *  Function adds a Marker record to an existing item
     */
    private function _setMarker() {
        $CleanGuid = NoNull($this->settings['post-guid'], $this->settings['guid']);
        $CleanNote = NoNull($this->settings['remark'], $this->settings['note']);
        $EventAt = NoNull($this->settings['marked_at'], $this->settings['event_at']);
        $Value = NoNull($this->settings['post_text'], NoNull($this->settings['text'], $this->settings['content']));

        $GeoLong = NoNull($this->settings['geo_longitude'], $this->settings['geo_long']);
        $GeoLat = NoNull($this->settings['geo_latitude'], $this->settings['geo_lat']);
        $GeoAlt = NoNull($this->settings['geo_altitude'], $this->settings['geo_alt']);

        /* Perform some basic validation on the Guid */
        if ( strlen($CleanGuid) != 36 ) {
            $this->_setMetaMessage("Invalid Item Guid Provided", 400);
            return false;
        }

        /* Ensure the MarkedAt date is valid */
        if ( strtotime($EventAt) === false ) { $EventAt = ''; }
        if ($EventAt != '') {
            $EventAt = $this->_convertTimeToUTC($EventAt);
        } else {
            $EventAt = date("Y-m-d H:i:s", time());
        }


        /* Ensure the Latitude & Longitude is Semi-Accurate */
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

        /* Collect the Parent.ids */
        $parent = $this->_getReplyToDetails($CleanGuid);

        /* Are We Setting a Marker or Writing a Post? */
        if ( strlen($Value) > 0 ) {
            $this->settings['post_text'] = $Value;
            $this->settings['geo_longitude'] = $GeoLong;
            $this->settings['geo_latitude'] = $GeoLat;
            $this->settings['geo_altitude'] = $GeoAlt;

            if ( $parent !== false ) {
                $this->settings['post_reply_to'] = NoNull($parent['url']);
            }
            return $this->_createItem();

        } else {
            /* Let's write the Geo-Record to the Referenced Post */
            if ( $parent !== false && nullInt($parent['post_id']) > 0 ) {
                $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                                  '[POST_ID]'    => nullInt($parent['post_id']),
                                  '[EVENT_AT]'   => sqlScrub($EventAt),
                                  '[LONGITUDE]'  => nullInt($GeoLong),
                                  '[LATITUDE]'   => nullInt($GeoLat),
                                  '[ALTITUDE]'   => nullInt($GeoAlt),
                                  '[NOTE]'       => sqlScrub($CleanNote),
                                );
                $sqlStr = readResource(SQL_DIR . '/location/setMarker.sql', $ReplStr);
                $rslt = doSQLExecute($sqlStr);

                /* Get the Updated Post Object */
                require_once(LIB_DIR . '/posts.php');
                $post = new Posts($this->settings);
                $rslt = $post->getPostsByIDs($parent['post_id']);
                unset($post);

                if ( is_array($rslt) ) { return $rslt; }

                /* If we're here, something's wrong */
                $this->_setMetaMessage("Unable to Record Marker on Post", 400);
                return false;
            }
        }

        /* If we're here, something's wrong */
        $this->_setMetaMessage("Ran Out of Things to Do", 400);
        return false;
    }

    /**
     *  Function removes a Marker record from an existing item
     */
    private function _deleteMarker() {

    }

    /**
     *  Function collects the Marker History for a Post and Returns an array or an unhappy boolean
     */
    private function _getMarkerList( $guid = '' ) {
        $CleanGuid = NoNull($guid, NoNull($this->settings['post-guid'], $this->settings['guid']));
        if ( strlen($CleanGuid) != 36 ) {
            if ( NoNull($guid) == '' ) { $this->_setMetaMessage("Invalid Location Guid Supplied", 400); }
            return false;
        }

        /* If we're here, we may have a valid Guid */
        $ReplStr = array( '[POST_GUID]' => sqlScrub($CleanGuid) );
        $sqlStr = readResource(SQL_DIR . '/location/getMarkerList.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();
            foreach ( $rslt as $Row ) {
                $note = NoNull($Row['value']);
                if ( $note == '' ) { $note = false; }

                $data[] = array( 'marked_at'    => date("Y-m-d\TH:i:s\Z", strtotime($Row['marked_at'])),
                                 'marked_unix'  => strtotime($Row['marked_at']),
                                 'longitude'    => nullInt($Row['longitude']),
                                 'latitude'     => nullInt($Row['latitude']),
                                 'altitude'     => ((nullInt($Row['altitude']) != 0) ? nullInt($Row['altitude']) : false),
                                 'map_url'      => NoNull($Row['map_url']),
                                 'note'         => $note
                                );
            }

            /* If we have data, let's return it */
            if ( count($data) > 0 ) { return $data; }
        }

        /* If we're here, there's nothing */
        if ( NoNull($guid) == '' ) { $this->_setMetaMessage("Invalid Location Guid Supplied", 400); }
        return false;
    }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function Collects the Post.id and Canonical URL for the post being replied/appended to
     */
    private function _getReplyToDetails( $guid ) {
        if ( strlen(NoNull($guid)) != 36 ) { return false; }

        $ReplStr = array( '[GUID]' => sqlScrub($guid) );
        $sqlStr = readResource(SQL_DIR . '/location/getReplyToDetails.sql', $ReplStr);
        $rslt = doSQLQuery( $sqlStr );
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                return array( 'post_id' => nullInt($Row['post_id']),
                              'url'     => NoNull($Row['reply_url']),
                             );
            }
        }

        /* If we're here, there's nothing */
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
}
?>