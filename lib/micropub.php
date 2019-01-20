<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for Micropub Functions
 */
require_once(LIB_DIR . '/functions.php');

class Micropub {
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
            case 'status':
                $rVal = false;
                break;

            default:
                $rVal = $this->_performGenericGET();
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case 'status':
                $rVal = false;
                break;

            default:
                $rVal = $this->_performGenericPOST();
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
    private function _performGenericGET() {
        $data = false;

        // If We're Being Asked for Config Information, Provide It
        if ( strtolower(NoNull($this->settings['q'])) == 'config' ) {
            $storage = nullInt($this->settings['_storage_total']);
            $used = nullInt($this->settings['_storage_used']);

            $data = array( 'media-endpoint' => getApiUrl() . '/files',
                           'api-location'   => getApiUrl(),
                           'cdn-location'   => getCdnUrl(),
                           'storage'        => array( 'total' => $storage,
                                                      'free'  => ($storage - $used),
                                                      'used'  => $used
                                                     ),
                          );
        }
        
        // Return an Array of Information, or an Unhappy Boolean
        return $data;
    }
    
    private function _performGenericPOST() {
        $data = false;

        // If We're Being Asked to Create a Post, Let's Do So
        if ( array_key_exists('h', $this->settings) ) {
            switch ( strtolower(NoNull($this->settings['h'])) ) {
                case 'entry':
                    $data = $this->settings;
                    break;

                default:
                    // Do Nothing
            }
        }

        // Return an Array of Information, or an Unhappy Boolean
        return $data;
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