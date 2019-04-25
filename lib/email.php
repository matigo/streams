<?php

/**
 * @author Jason F. Irwin
 * @copyright 2016
 *
 * Class contains the rules and methods called for Email Functions
 */
require_once(LIB_DIR . '/functions.php');
require_once(LIB_DIR . '/class.phpmailer.php');

class Email {
    var $settings;
    var $strings;
    var $errors;

    function __construct( $Items ) {
        $this->settings = $Items;
        $this->strings = getLangDefaults($this->settings['_language_code']);
        $this->errors = array();
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    public function performAction() {
        $ReqType = NoNull(strtolower($this->settings['ReqType']));
        $rVal = false;

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }
        if ( hasAnonScope($this->settings['_account_scopes']) ) { return "You Do Not Have Permission to Access This Endpoint"; }

        // Perform the Action
        switch ( $ReqType ) {
            case 'get':
                $rVal = $this->_performGetAction();
                break;

            case 'post':
            case 'put':
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
        $Activity = strtolower(NoNull($this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case 'test':
                $rVal = $this->_sendWelcomeEmail();
                break;

            case '':
                $rVal = array( 'activity' => "[GET] /mail/$Activity" );
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performPostAction() {
        $Activity = NoNull(strtolower($this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case 'set':
            case '':
                $rVal = array( 'activity' => "[POST] /mail/$Activity" );
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _performDeleteAction() {
        $Activity = NoNull(strtolower($this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case '':
                $rVal = array( 'activity' => "[DELETE] /mail/$Activity" );
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    /***********************************************************************
     *  Public Functions
     ***********************************************************************/
    public function sendWelcomeMessage($SendTo) { return $this->_sendWelcomeEmail($SendTo); }
    public function sendNewAccountNotices( $AccountID ) { return $this->_sendNewAccountNotices( $AccountID ); }
    public function sendMail( $data ) { return $this->_sendEmail($data); }

    /***********************************************************************
     *  Private Functions
     ***********************************************************************/
    private function _sendWelcomeEmail($SendMsgTo = '' ) {
        $SendTo = NoNull($SendMsgTo, $this->settings['send_to']);
        if ( $SendTo == '' ) { return "No Send To Address Provided"; }
        $rVal = "Could Not Send Email";

        $Body = readResource(FLATS_DIR . '/templates/email.welcome.html');
        if ( $Body && $SendTo ) {
        	$SendFrom = MAIL_ADDRESS;
        	$SendName = "Berlitz Advantage";
        	$ReplyTo = MAIL_ADDRESS;

            $mail = new PHPMailer();
            $mail->IsSMTP();

            $mail->SMTPAuth   = ((MAIL_SMTPAUTH == 1) ? true : false);
            $mail->SMTPSecure = MAIL_SMTPSECURE;
            $mail->Host       = MAIL_MAILHOST;
            $mail->Port       = MAIL_MAILPORT;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_USERPASS;

            $mail->SetFrom(NoNull($SendFrom, $ReplyTo), NoNull($SendName, 'Berlitz Advantage'));
            $mail->Subject = "Welcome to Advantage!";
            $mail->AltBody = "Welcome to Advantage!\n\n" .
                             "This message would look much better in an HTML-compatible reader, but it's all good.\n\n" .
                             "Thank you for creating an account. We'll get it set up for you and send a message letting you know when it's ready for you to use.\n" .
                             "If you have any questions, just ask your manager or contact us directly and we'll get back to you ASAP.\n\n" .
                             "We hope you enjoy using the new Advantage.";
            $mail->MsgHTML( $Body );
            $mail->AddAddress( $SendTo );
            $mail->isHTML(true);

            if(!$mail->Send()) {
                $rVal = "Mailer Error: " . $mail->ErrorInfo;
            } else {
                $rVal = array( 'message' => $Body,
                               'sent_to' => $SendTo,
                               'success' => true );
            }
        }

        // Return the Response Array or an Unhappy String
        return $rVal;
    }

    private function _sendNewAccountNotices( $AccountID ) {
        if ( nullInt($AccountID) <= 0 ) { return false; }

        // Collect a List of Administrator Email Addresses and New Account Data
        $ReplStr = array( '[NEW_ID]' => nullInt($AccountID) );
        $sqlStr = readResource(SQL_DIR . '/system/getAdminEmails.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
        	$SendFrom = MAIL_ADDRESS;
        	$SendName = "Berlitz Advantage";
        	$ReplyTo = MAIL_ADDRESS;

            $mail = new PHPMailer();
            $mail->IsSMTP();

            $mail->SMTPAuth   = ((MAIL_SMTPAUTH == 1) ? true : false);
            $mail->SMTPSecure = MAIL_SMTPSECURE;
            $mail->Host       = MAIL_MAILHOST;
            $mail->Port       = MAIL_MAILPORT;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_USERPASS;

            $mail->SetFrom(NoNull($SendFrom, $ReplyTo), NoNull($SendName, 'Berlitz Advantage'));
            $mail->Subject = "New Member Notification";
            $mail->isHTML(true);

            foreach ( $rslt as $Row ) {
                $MailStr = array( '[PERSON_NAME]' => NoNull($Row['last_ro'], $Row['last_ka']) . ', ' . NoNull($Row['first_ro'], $Row['first_ka']),
                                  '[PERSON_MAIL]' => NoNull($Row['person_mail']),
                                 );
                $Body = readResource(FLATS_DIR . '/templates/email.new-account.html', $MailStr);

                $mail->AltBody = "New Account Registered!\n\n" .
                                 "This message would look much better in an HTML-compatible reader, but it's all good.\n\n" .
                                 "This is just a friendly note letting you know that a new account has been registered in Advantage for: " .
                                 NoNull($Row['last_ro'], $Row['last_ka']) . ", " . NoNull($Row['first_ro'], $Row['first_ka']) . " " .
                                 "(" . NoNull($Row['person_mail']) . ")\n\n" .
                                 "Please remember to set their COSMOS ID and scopes as soon as possible so that they may begin using the system.";
                $mail->MsgHTML( $Body );
                $mail->AddAddress( NoNull($Row['admin_email']) );
            }

            // Send the Emails
            if(!$mail->Send()) { writeNote("Mailer Error: " . $mail->ErrorInfo, true); }
            unset($mail);
        }
    }

    /**
     * Function sends an email Using the information in $data and a template file and returns a Boolean response
     */
    private function _sendEmail( $data ) {
        $SendTo = NoNull($data['send_to']);
        $mailHTML = NoNull($data['html']);
        $mailText = NoNull($data['text']);
        $rVal = false;

        if ( $SendTo != '' && NoNull($mailHTML, $mailText) != '' ) {
        	$SendFrom = NoNull($data['send_from'], MAIL_ADDRESS);
        	$SendName = NoNull($data['from_name'], APP_NAME);
        	$ReplyTo = NoNull($data['from_addr'], MAIL_ADDRESS);

            $mail = new PHPMailer();
            $mail->IsSMTP();

            $mail->SMTPAuth   = ((MAIL_SMTPAUTH == 1) ? true : false);
            $mail->SMTPSecure = MAIL_SMTPSECURE;
            $mail->Host       = MAIL_MAILHOST;
            $mail->Port       = MAIL_MAILPORT;
            $mail->Username   = MAIL_USERNAME;
            $mail->Password   = MAIL_USERPASS;

            $mail->CharSet = 'UTF-8';
            $mail->SetFrom($SendFrom, $SendName);
            $mail->Subject = NoNull($data['subject']);
            $mail->AltBody = $mailText;
            $mail->Body = $mailHTML;
            $mail->AddAddress( $SendTo );
            $mail->isHTML( true );

            // Is there a CC Address?
            if ( NoNull($data['send_cc']) != '' ) { $mail->AddCC(NoNull($data['send_cc']) ); }

            // Send the Message!
            $reqStart = getMicroTime();
            if( !$mail->Send() ) {
                writeNote("Mailer Error: " . $mail->ErrorInfo, true);
                $rVal = false;
            } else {
            	$rVal = true;
            }

            // Get the Timing
            $reqDone = getMicroTime();

        } else {
            writeNote("Mail Elements Incomplete!", true);
            writeNote("Send To: $SendTo", true);
            writeNote("HTML: $mailHTML", true);
            writeNote("Text: $mailText", true);
        }

        // If We're Here, Something Was Not Quite Right
        return $rVal;
    }

    /**
     *	Function Sends a Test Message to the Address Provided
     */
    private function _testEmail() {
    	$EmailDomain = $this->_readBaseDomainURL( $this->settings['HomeURL'] );
    	$data = array( 'testerEServ' => NoNull($this->settings['txtMailHost'], readSetting('core', 'EmailServ')),
    				   'testerEPort' => NoNull($this->settings['txtMailPort'], readSetting('core', 'EmailPort')),
    				   'testerEUser' => NoNull($this->settings['txtMailUser'], readSetting('core', 'EmailUser')),
    				   'testerEPass' => NoNull($this->settings['txtMailPass'], readSetting('core', 'EmailPass')),
    				   'testerESSL'  => NoNull($this->settings['cmbMailSSL'], readSetting('core', 'EmailSSL')),
    				   'testerMSG'	 => $this->messages['lblMailTestBody'],
    				   'testerEFrom' => NoNull($this->settings['txtMailReply'], "noteworthy@$EmailDomain"),
    				   'testerETo'	 => NoNull($this->settings['txtMailSendTo'], readSetting('core', 'EmailSendTo')),
    				   'testerEFNam' => "Noteworthy",
    				   );
	    $rVal = array( 'isGood'	 => 'N',
	    			   'Message' => $this->messages['lblUnknownErr'],
	    			  );

    	// Update the Class Settings with the Tester Settings
    	foreach ( $data as $Key=>$Val ) {
	    	$this->settings[$Key] = $Val;
    	}

    	// Send the Test Email
    	$rVal = $this->_sendEmail();

    	// Return the Response Array
    	return $rVal;
    }
}
?>