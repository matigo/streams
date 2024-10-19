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

        /* Perform the Action */
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
                /* Do Nothing */
        }

        /* If we're here, an unrecognized request type was submitted */
        return false;
    }

    private function _performGetAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));

        /* Check the User Token is Valid */
        if ( YNBool($this->settings['_logged_in']) !== true ) { return $this->_setMetaMessage("You Need to Log In First", 401); }

        switch ( $Activity ) {
            case 'count':
                return $this->_getMessageCount();
                break;

            case 'list':
            case '':
                return $this->_getMessageList();
                break;

            case 'trigger':
                return $this->_triggerEmailNotifications();
                break;

            default:
                // Do Nothing
        }

        /* If We're Here, It's No Good. Return an Unhappy Boolean. */
        return false;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));

        switch ( $Activity ) {
            case 'submit':
            case 'send':
            case 'set':
            case '':
                return $this->_processMessage();
                break;

            default:
                // Do Nothing
        }

        /* Return the Array of Data or an Unhappy Boolean */
        return false;
    }

    private function _performDeleteAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));

        /* Check the User Token is Valid */
        if ( YNBool($this->settings['_logged_in']) !== true ) { return $this->_setMetaMessage("You Need to Log In First", 401); }

        /* If We Have a GUID, We're Deleting a Message */
        if ( mb_strlen($Activity) == 36 ) { $Activity = 'scrub'; }

        switch ( $Activity ) {
            case 'scrub':
            case '':
                return $this->_deleteMessage();
                break;

            default:
                // Do Nothing
        }

        /* If we here, nothing was done. Return an Unhappy Boolean. */
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
    public function getMessageCount() { return $this->_getMessageCount(); }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function returns a single array record containing the number of Unread Contact Messages
     *      that might exist for an Account.
     */
    private function _getMessageCount() {
        $TokenGUID = NoNull($this->settings['_token_guid']);
        $TokenID = nullInt($this->settings['_token_id']);

        if ( $TokenID > 0 ) {
            $ReplStr = array( '[TOKEN_ID]'     => nullInt($TokenID),
                              '[TOKEN_GUID]'   => sqlScrub($TokenGUID),
                             );
            $sqlStr = prepSQLQuery("CALL GetMessages([TOKEN_ID], '[TOKEN_GUID]', 'Y');", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    return array( 'my_home' => NoNull($Row['my_home']),
                                  'unread'  => nullInt($Row['unread']),
                                 );
                }
            }
        }

        /* If We're Here, There's Nothing */
        return false;
    }

    /**
     *  Function returns an array containing any Contact Messages that might exist for an Account
     */
    private function _getMessageList() {
        $TokenGUID = NoNull($this->settings['_token_guid']);
        $TokenID = nullInt($this->settings['_token_id']);

        if ( $TokenID > 0 ) {
            $ReplStr = array( '[TOKEN_ID]'     => nullInt($TokenID),
                              '[TOKEN_GUID]'   => sqlScrub($TokenGUID),
                             );
            $sqlStr = prepSQLQuery("CALL GetMessages([TOKEN_ID], '[TOKEN_GUID]', 'N');", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $data = array();

                require_once(LIB_DIR . '/posts.php');
                $post = new Posts($this->settings);

                foreach ( $rslt as $Row ) {
                    $data[] = array( 'from_url' => NoNull($Row['url']),
                                     'name'     => NoNull($Row['name']),
                                     'email'    => NoNull($Row['mail']),
                                     'subject'  => NoNull($Row['subject']),
                                     'message'  => array( 'html' => $post->getMarkdownHTML($Row['message'], 0, false, true),
                                                          'text' => NoNull($Row['message']),
                                                         ),

                                     'guid'     => NoNull($Row['guid']),
                                     'is_read'  => YNBool($Row['is_read']),
                                     'is_spam'  => YNBool($Row['is_spam']),

                                     'created_at'   => apiDate($Row['created_unix'], 'Z'),
                                     'created_unix' => apiDate($Row['created_unix'], 'U'),
                                     'updated_at'   => apiDate($Row['updated_unix'], 'Z'),
                                     'updated_unix' => apiDate($Row['updated_unix'], 'U'),
                                    );
                }
                unset($post);

                /* If we have data, Return it */
                if ( count($data) > 0 ) { return $data; }
            }
        }

        /* If We're Here, There's Nothing */
        return false;
    }

    /**
     *  Function records a Message to the database
     */
    private function _processMessage() {
        $CleanName = NoNull($this->settings['contact-name'], $this->settings['name']);
        $CleanMail = NoNull($this->settings['contact-mail'], $this->settings['mail']);
        $CleanSubj = NoNull($this->settings['contact-subject'], $this->settings['subject']);
        $CleanMsg = NoNull($this->settings['contact-message'], $this->settings['message']);
        $CleanChk = nullInt($this->settings['contact-validate'], $this->settings['validate']);
        $CleanNonce = NoNull($this->settings['contact-nonce'], $this->settings['nonce']);
        $isWebReq = YNBool(NoNull($this->settings['redirect'], 'Y'));

        /* Remove any HTML That Might Exist */
        $CleanName = strip_tags($CleanName);
        $CleanMail = strip_tags($CleanMail);
        $CleanSubj = strip_tags($CleanSubj);
        $CleanMsg = strip_tags($CleanMsg);

        /* Perform Some Basic Error Checking */
        if ( mb_strlen($CleanName) < 1 ) { return $this->_setMetaMessage("No Name Provided", 400); }
        if ( mb_strlen($CleanMsg) < 3 ) { return $this->_setMetaMessage("No Message Provided", 400); }

        /* If we have an email address, confirm it's potentially valid */
        if ( mb_strlen($CleanMail) > 0 ) {
            if ( mb_strlen($CleanMail) < 3 ) { return $this->_setMetaMessage("Invalid Email Address Provided", 400); }
            if ( strpos($CleanMail, '@') === false ) { return $this->_setMetaMessage("Invalid Email Address Provided", 400); }
            if ( strpos($CleanMail, '.') === false ) { return $this->_setMetaMessage("Invalid Email Address Provided", 400); }
            if ( validateEmail($CleanMail) === false ) { return $this->_setMetaMessage("Invalid Email Address Provided", 400); }
        }

        /* Construct the Expected Nonce Value */
        $ExpectedNonce = md5(NoNull($this->settings['HomeURL']) . NoNull($this->settings['_address']));

        /* Record the Data to the Database */
        $ReplStr = array( '[NAME]'        => sqlScrub($CleanName),
                          '[MAIL]'        => sqlScrub($CleanMail),
                          '[SUBJECT]'     => sqlScrub($CleanSubj),
                          '[MESSAGE]'     => sqlScrub($CleanMsg),
                          '[SITE_ID]'     => nullInt($this->settings['site_id']),

                          '[NONCE_MATCH]' => BoolYN($CleanNonce == $ExpectedNonce),
                          '[NONCE]'       => sqlScrub($CleanNonce),
                          '[FROM_IP]'     => getVisitorIPv4(),
                          '[AGENT]'       => sqlScrub($_SERVER['HTTP_USER_AGENT']),
                         );
        $sqlStr = readResource(SQL_DIR . '/contact/setMessage.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        /* If a Message was Recorded, Send a message to the Site Owner */
        if ( $rslt ) {
            $canSend = true;
            if ( defined('MAIL_MAILHOST') === false ) { $canSend = false; }
            if ( defined('MAIL_USERNAME') === false ) { $canSend = false; }
            if ( defined('MAIL_USERPASS') === false ) { $canSend = false; }
            if ( NoNull(MAIL_MAILHOST) == '' ) { $canSend = false; }
            if ( NoNull(MAIL_USERNAME) == '' ) { $canSend = false; }
            if ( NoNull(MAIL_USERPASS) == '' ) { $canSend = false; }

            /* If We Have Passed _basic_ Validation, Send the Message */
            if ( $canSend ) { $this->_sendEmailNotification(); }
        }

        /* Return a JSON object or redirect to a "Thank You" Page */
        if ( $isWebReq ) {
            $OutUrl = $this->settings['HomeURL'] . '/thankyou';
            redirectTo($OutUrl, $this->settings);

        } else {
            return array( 'name'    => $CleanName,
                          'mail'    => $CleanMail,
                          'subject' => $CleanSubj,
                          'message' => $CleanMsg,
                          'is_sent' => true,

                          'nonce'   => array( 'value'    => $CleanNonce,
                                              'is_valid' => YNBool(BoolYN($CleanNonce == $ExpectedNonce))
                                             ),
                         );
        }
    }

    private function _sendEmailNotification() {
        $ReplStr = array( '[SITE_ID]' => nullInt($this->settings['site_id']) );
        $sqlStr = readResource(SQL_DIR . '/contact/getEmailNotifications.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            require_once(LIB_DIR . '/posts.php');
            require_once(LIB_DIR . '/email.php');
            $post = new Posts($this->settings);
            $mail = new Email($this->settings);
            $cnt = 0;

            foreach ( $rslt as $Row ) {
                $ReplStr = array( '[RECD_URL]' => NoNull($Row['url']),
                                  '[SITE_URL]' => NoNull($Row['site_url']),
                                  '[ACCOUNT_NAME]' => NoNull($Row['account_name']),

                                  '[FROM_NAME]' => NoNull($Row['name']),
                                  '[FROM_MAIL]' => NoNull($Row['mail']),

                                  '[SUBJECT]'   => NoNull($Row['subject']),
                                  '[MESSAGE]'   => $post->getMarkdownHTML($Row['message'], 0, false, true),
                                  '[PLAINTXT]'  => NoNull($Row['message']),
                                  '[GUID]'      => NoNull($Row['guid']),
                                  '[RECD_AT]'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                                  '[RECD_UNIX]' => strtotime($Row['created_at']),
                                 );
                $msgHTML = readResource(FLATS_DIR . '/templates/email.contact.html', $ReplStr);
                $msgText = readResource(FLATS_DIR . '/templates/email.contact.txt', $ReplStr);

                // Construct the Mailer Array
                $BaseSubject = "You've received a message via " . NoNull($Row['url']);
                $msg = array( 'send_to' => NoNull($Row['account_mail']),
                              'send_cc' => '',
                              'subject' => NoNull($Row['subject'], $BaseSubject),
                              'html'    => $msgHTML,
                              'text'    => $msgText,

                              'from_addr' => NoNull($Row['mail']),
                              'from_name' => NoNull($Row['name'], NoNull($Row['url'])),
                             );

                $isOK = $mail->sendMail($msg);

                // Mark the Message as Sent
                $ReplStr = array( '[MSG_GUID]' => NoNull($Row['guid']),
                                  '[SITE_ID]'  => nullInt($this->settings['site_id']),
                                 );
                $sqlStr = readResource(SQL_DIR . '/contact/setMessageMailed.sql', $ReplStr);
                $upd = doSQLExecute($sqlStr);

                // Increment the Counter
                $cnt++;
            }
            unset($post);
            unset($mail);

            // Return the Number of Mails Attempted
            return $cnt;
        }

        // If We're Here, No Messages Were Sent
        return 0;
    }

    /**
     *  Function Confirms that a _site_id is set and triggers the _sendEmailNotification() function
     */
    private function _triggerEmailNotifications() {
        if ( nullInt($this->settings['site_id']) > 0 ) {
            $rslt = $this->_sendEmailNotification();
            return array( 'sent' => $rslt );
        }

        /* If We're Here, It's Not Good */
        return $this->_setMetaMessage("There is no Site Record specified for the Token", 400);
    }

    /**
     *  Function Marks a Message as Deleted
     */
    private function _deleteMessage() {
        $CleanGuid = NoNull($this->settings['PgSub2'], $this->settings['PgSub1']);
        if ( mb_strlen($CleanGuid) == 36 ) {
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                              '[MSG_GUID]'   => sqlScrub($CleanGuid),
                              '[SITE_ID]'    => nullInt($this->settings['site_id']),
                             );
            $sqlStr = readResource(SQL_DIR . '/contact/setDeleted.sql', $ReplStr);
            $isOK = doSQLExecute($sqlStr);

            /* Return an Updated Message List */
            return $this->_getMessageList();
        }

        /* If We're Here, the GUID is Bad */
        return $this->_setMetaMessage("Invalid Message GUID Supplied", 400);
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