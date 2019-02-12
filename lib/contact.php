<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to manage Posts
 */
require_once(LIB_DIR . '/functions.php');

class Contact {
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

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) {
            $this->_setMetaMessage("You Need to Log In First", 401);
            return false;
        }

        switch ( $Activity ) {
            case '':
                $rVal = $this->_processMessage();
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
                $rVal = $this->_processMessage();
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

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) {
            $this->_setMetaMessage("You Need to Log In First", 401);
            return false;
        }

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
    private function _processMessage() {
        $CleanName = NoNull($this->settings['contact-name'], $this->settings['name']);
        $CleanMail = NoNull($this->settings['contact-mail'], $this->settings['mail']);
        $CleanMsg = NoNull($this->settings['contact-message'], $this->settings['message']);

        // Perform Some Basic Error Checking
        if ( strlen($CleanName) < 1 ) { return "No Name Provided"; }
        if ( strlen($CleanMail) < 3 ) { return "Invalid Email Address Provided"; }
        if ( strpos($CleanMail, '@') === false ) { return "Invalid Email Address Provided"; }
        if ( strpos($CleanMail, '.') === false ) { return "Invalid Email Address Provided"; }
        if ( strlen($CleanMsg) < 3 ) { return "No Message Provided"; }

        // Record the Data to the Database
        $ReplStr = array( '[NAME]'    => sqlScrub($CleanName),
                          '[MAIL]'    => sqlScrub($CleanMail),
                          '[MESSAGE]' => sqlScrub($CleanMsg),
                          '[SITE_ID]' => nullInt($this->settings['site_id']),
                         );
        $sqlStr = readResource(SQL_DIR . '/contact/setMessage.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // If a Message was Recorded, Send a message to the Site Owner
        if ( $rslt ) {
            $canSend = true;
            if ( defined('MAIL_MAILHOST') === false ) { $canSend = false; }
            if ( defined('MAIL_USERNAME') === false ) { $canSend = false; }
            if ( defined('MAIL_USERPASS') === false ) { $canSend = false; }
            if ( NoNull(MAIL_MAILHOST) == '' ) { $canSend = false; }
            if ( NoNull(MAIL_USERNAME) == '' ) { $canSend = false; }
            if ( NoNull(MAIL_USERPASS) == '' ) { $canSend = false; }

            // If We Have Passed _basic_ Validation, Send the Message
            if ( $canSend ) {

            }
        }

        // Redirect to a "Thank You" Page
        $OutUrl = $this->settings['HomeURL'] . '/thankyou';
        redirectTo($OutUrl);
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