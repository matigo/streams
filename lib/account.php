<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for Accounts
 */
require_once( LIB_DIR . '/functions.php');

class Account {
    var $settings;
    var $strings;
    var $cache;

    function __construct( $settings, $strings = false ) {
        $this->settings = $settings;
        $this->strings = ((is_array($strings)) ? $strings : getLangDefaults($this->settings['_language_code']));
        $this->cache = array();
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
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case 'list':
                if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }
                $rVal = $this->_getAccountList();
                break;

            case 'preferences':
            case 'preference':
            case 'prefs':
                $rVal = $this->_getPreference();
                break;

            case 'profile':
            case 'bio':
                $rVal = $this->_getPublicProfile();
                break;

            case 'posts':
                $rVal = $this->_getProfilePosts();
                break;

            case 'me':
                if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }
                $rVal = $this->_getProfile();
                break;

            case 'summary':
                if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }
                $rVal = $this->_getAccountSummary();
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

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case 'bio':
                $rVal = $this->_setPublicProfile();
                break;

            case 'create':
                $rVal = $this->_createAccountLocal();
                break;

            case 'profile':
            case 'me':
                $rVal = $this->_setProfile();
                break;

            case 'resetpassword':
            case 'resetpass':
                $rVal = $this->_resetPassword();
                break;

            case 'welcome':
                $rVal = $this->_setWelcomeDone();
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
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case '':
                $rVal = array( 'activity' => "[DELETE] /account/$Activity" );
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
    public function createAccount($data) { return $this->_createAccount($data); }
    public function getAccountInfo($AcctID) { return $this->_getAccountInfo($AcctID); }
    public function getPublicProfile() { return $this->_getPublicProfile(); }
    public function getPreference($Type) {
        $data = $this->_getPreference($Type);
        return $data['value'];
    }
    public function setAccountLanguage( $LangCd ) { return $this->_setAccountLanguage($LangCd); }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function Collects the Account Information if Required and Returns the Requested Info
     */
    private function _getAccountInfo( $AccountID ) {
        $CleanID = nullInt($AccountID);
        if ( $CleanID <= 0 ) { return false; }

        if ( !array_key_exists($CleanID, $this->cache) ) { $this->_readAccountInfo($AccountID); }
        return ( is_array($this->cache[$CleanID]) ) ? $this->cache[$CleanID] : false;
    }

    /**
     *  Function Populates the Cache Variable with Account Data for a Given Set of IDs
     */
    private function _readAccountInfo( $AccountIDs ) {
        $Accounts = explode(',', $AccountIDs);
        if ( is_array($Accounts) ) {
            $AcctList = array();

            foreach ( $Accounts as $id ) {
                $chkID = nullInt($id);
                if ( $chkID > 0 && !array_key_exists($chkID, $this->cache) ) { $AcctList[] = nullInt($id); }
            }

            // Get a List of Person Records
            if ( count($AcctList) > 0 ) {
                $ReplStr = array( '[ACCOUNT_IDS]' => implode(',', $AcctList) );
                $sqlStr = readResource(SQL_DIR . '/account/getAccountPersonInfo.sql', $ReplStr);
                $rslt = doSQLQuery($sqlStr);
                if ( is_array($rslt) ) {
                    foreach ( $rslt as $Row ) {
                        $AcctID = nullInt($Row['account_id']);
                        $lang_label = 'lbl_' . NoNull($Row['language_code']);
                        $this->cache[$AcctID] = array( 'id'             => $AcctID,
                                                       'display_name'   => NoNull($Row['display_name']),
                                                       'avatar_url'     => NoNull($Row['avatar_url'], 'default.png'),
                                                       'type'           => NoNull($Row['type']),
                                                       'is_you'         => YNBool(BoolYN($Row['account_id'] == $this->settings['_account_id'])),

                                                       'created_at'     => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                                                       'created_unix'   => strtotime($Row['created_at']),
                                                       'updated_at'     => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                                       'updated_unix'   => strtotime($Row['updated_at']),
                                                      );
                    }
                }
            }
        }
    }

    /**
     *  Function Acts as a Quick Language Setting Option
     */
    private function _setAccountLanguage( $LangCd ) {
        if ( NoNull($LangCd) == '' ) { return false; }

        // Construct the SQL Query
        $ReplStr = array( '[TOKEN_GUID]' => sqlScrub($this->settings['_token_guid']),
                          '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[TOKEN_ID]'   => nullInt($this->settings['_token_id']),
                          '[LANG_CD]'    => sqlScrub($LangCd),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/setAccountLanguage.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);
        return true;
    }

    /** ********************************************************************* *
     *  Local Account Creation
     ** ********************************************************************* */
    private function _createAccountLocal() {
        $has_scope = false;
        if ( $this->_hasScope('admin') || $this->_hasScope('level 1') || $this->_hasScope('level 2') || $this->_hasScope('manager') ) { $has_scope = true; }
        if ( $has_scope !== true ) { return "You Do Not Have Adequate Permissions For This Function"; }

        $CleanGender = strtoupper(NoNull($this->settings['gender'], 'M'));
        $CleanFirst = NoNull($this->settings['first_ro'], $this->settings['first_name']);
        $CleanLast = NoNull($this->settings['last_ro'], $this->settings['last_name']);
        $CleanFirstKa = NoNull($this->settings['first_ka']);
        $CleanLastKa = NoNull($this->settings['last_ka']);
        $CleanPrintRo = NoNull($this->settings['print_ro'], $this->settings['print-ro']);
        $CleanPrintKa = NoNull($this->settings['print_ka'], $this->settings['print-ka']);
        $CleanEmpNo = strtoupper(NoNull($this->settings['cosmos_id']));
        $CleanLC = nullInt($this->settings['base_lc'], $this->settings['school_id']);
        $CleanLang = NoNull($this->settings['language_code'], NoNull($this->settings['language'], 'en'));
        $CleanMail = NoNull($this->settings['email'], $this->settings['mail_addr']);
        $CleanAcctID = nullInt($this->settings['account_id'], $this->settings['acct_id']);
        $CleanPrsnID = nullInt($this->settings['person_id'], $this->settings['prsn_id']);

        $CleanLogin = strtolower(NoNull($this->settings['account_name']));
        $CleanPass = NoNull($this->settings['account_pass']);

        // Perform Some Basic Validation
        if ( $CleanFirst == '' ) { return "Invalid First Name Supplied"; }
        if ( $CleanLast == '' ) { return "Invalid Family Name Supplied"; }
        if ( $CleanLang == '' ) { return "Invalid Language Preference Supplied"; }
        if ( $CleanLC <= 0 ) { return "Invalid School Supplied"; }
        if ( $CleanLogin == '' ) { return "Invalid Login ID Supplied"; }
        if ( $CleanPass == '' ) { return "Invalid Password Supplied"; }
        if ( $CleanMail != '' ) {
            if ( validateEmail($CleanMail) === false ) { "Invalid Email Address Supplied"; }
        }

        // Correct Some Values if They Are Incorrect
        if ( !in_array($CleanGender, array('M','F')) ) { $CleanGender = 'M'; }

        $ReplStr = array( '[MY_ACCOUNT]' => nullInt($this->settings['_account_id']),
                          '[ACTION_BY]'  => nullInt($this->settings['_account_id']),
                          '[FIRST_RO]'   => sqlScrub($CleanFirst),
                          '[LAST_RO]'    => sqlScrub($CleanLast),
                          '[FIRST_KA]'   => sqlScrub($CleanFirstKa),
                          '[LAST_KA]'    => sqlScrub($CleanLastKa),
                          '[PRINT_RO]'   => sqlScrub($CleanPrintRo),
                          '[PRINT_KA]'   => sqlScrub($CleanPrintKa),
                          '[GENDER]'     => sqlScrub($CleanGender),
                          '[COSMOS_ID]'  => sqlScrub($CleanEmpNo),
                          '[SCHOOL_ID]'  => nullInt($CleanLC),
                          '[DISPNAME]'   => sqlScrub(NoNull($CleanFirst, $CleanFirstKa)),
                          '[MAILADDR]'   => sqlScrub($CleanMail),
                          '[MAIL_ADDR]'  => sqlScrub($CleanMail),
                          '[USERNAME]'   => sqlScrub($CleanLogin),
                          '[USERPASS]'   => sqlScrub($CleanPass),
                          '[LANG_CODE]'  => sqlScrub($CleanLang),
                          '[PERSON_ID]'  => nullInt($CleanPrsnID),
                          '[PERSONID]'   => nullInt($CleanPrsnID),
                          '[ACCOUNT_ID]' => nullInt($CleanAcctID),
                          '[ACCT_ID]'    => nullInt($CleanAcctID),

                          '[SAMLGUID]'   => '',
                          '[SAML_CHK]'   => getRandomString(36),
                          '[SHA_SALT]'   => sqlScrub(SHA_SALT),
                         );
        if ( $CleanAcctID <= 0 ) {
            $sqlStr = readResource(SQL_DIR . '/person/createPerson.sql', $ReplStr, true);
            $person_id = doSQLExecute($sqlStr);
            if ( $person_id > 0 ) {
                // Create the Account Record
                $ReplStr['[PERSONID]'] = nullInt($person_id);
                $sqlStr = readResource(SQL_DIR . '/account/createAccount.sql', $ReplStr);
                $NewAcctID = doSQLExecute($sqlStr);

                // Do Not Continue Without a Valid Account.ID Value
                if ( $NewAcctID <= 0 ) { return "Could Not Create Account Record"; }

                // Set the Basics
                $this->settings['account_id'] = $NewAcctID;
                $this->settings['acct_type'] = 'account.normal';

                // Set/Reset the Account-Specific Meta-Data
                $isOK = $this->_setAccountMetaForID($CleanAcctID, 'community.moderator', NoNull($this->settings['community-moderator'], 'N'));

                // Set the Account Status, Employee Records, and Scopes
                $isOK = $this->_setAccountPermissions();

                // Set/Reset the Account for Usage
                $ReplStr['[ACCOUNT_ID]'] = nullInt($NewAcctID);
                $sqlStr = readResource(SQL_DIR . '/auth/resetAccount.sql', $ReplStr) . SQL_SPLITTER .
                          readResource(SQL_DIR . '/account/setAccountPassHistory.sql', $ReplStr, true);
                $isOK = doSQLExecute($sqlStr);

                // Record the Event
                setActivityRecord( $this->settings['_account_id'], 'account.create', 'action.complete', $NewAcctID,
                                   "Created New Account [$NewAcctID] for Person [$person_id]" );

                // Return the Account Information
                if ( $NewAcctID > 0 ) { return $this->_getAccountInfo($NewAcctID); }

            } else {
                return "Could Not Create Person Record";
            }

        } else {
            // Update the Person Record
            $sqlStr = readResource(SQL_DIR . '/person/updatePerson.sql', $ReplStr);
            $isOK = doSQLExecute($sqlStr);

            // Set the Account Status, Employee Records, and Scopes
            $isOK = $this->_setAccountPermissions();

            // Set the Password if Applicable
            if ( $CleanPass != '' && substr_count($CleanPass, '*') < 6 ) {
                $sqlStr = readResource(SQL_DIR . '/account/setPassword.sql', $ReplStr, true) . SQL_SPLITTER .
                          readResource(SQL_DIR . '/auth/setReqPassChg.sql', $ReplStr, true) . SQL_SPLITTER .
                          readResource(SQL_DIR . '/account/setAccountPassHistory.sql', $ReplStr, true);
                $isOK = doSQLExecute($sqlStr);
            }

            // Set/Reset the Account-Specific Meta-Data
            $isOK = $this->_setAccountMetaForID($CleanAcctID, 'community.moderator', NoNull($this->settings['community-moderator'], 'N'));

            // Set/Reset the Account for Usage if Admin
            if ( $this->_hasScope('admin') ) {
                $sqlStr = readResource(SQL_DIR . '/auth/resetAccount.sql', $ReplStr);
                $isOK = doSQLExecute($sqlStr);
            }

            // Record the Event
            setActivityRecord( $this->settings['_account_id'], 'account.update', 'action.complete', $CleanAcctID,
                               "Updated Account [$CleanAcctID] for Person [$CleanPrsnID]" );

            // Return the Account Information
            return $this->_getAccountInfo($CleanAcctID);
        }

        // If We're Here, Something's Wrong
        return false;
    }

    /**
     *  Function Records a Specific Meta Item Against an Account.ID
     */
    private function _setAccountMetaForID( $AccountID, $Type, $Value ) {
        if ( nullInt($AccountID) <= 0 ) { return false; }
        if ( NoNull($Type) == '' ) { return false; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($AccountID),
                          '[META_VALUE]' => sqlScrub($Value),
                          '[META_TYPE]'  => sqlScrub($Type),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/setAccountMetaForID.sql', $ReplStr);
        $isOK = doSQLExecute($sqlStr);

        // If We're Here, It's Probably Good
        return true;
    }

    /**
     *  Function Returns a Base Summary of an Account Based on the Employee.GUID Passed
     */
    private function _getAccountSummary() {
        $CleanGUID = NoNull($this->settings['guid'], $this->settings['PgSub1']);
        if ( $CleanGUID == '' ) { return "Invalid GUID Supplied"; }

        // Construct the SQL Query and Perform the Query
        $ReplStr = array( '[EMPLOYEE_GUID]' => sqlScrub($CleanGUID),
                          '[LOCK_AFTER]'    => nullInt(ACCOUNT_LOCK, 30),
                          '[ACCOUNT_ID]'    => nullInt($this->settings['_account_id']),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/getAccountSummary.sql', $ReplStr, true);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = false;

            foreach ( $rslt as $Row ) {
                // Construct the Print Name Defaults
                $proDefault = CleanName(NoNull($Row['first_ro'], $Row['first_ka']));
                $pkaDefault = CleanName(NoNull($Row['last_ka'], $Row['last_ro']));
                if ( NoNull($proDefault) != '' && NoNull($Row['last_ro'], $Row['last_ka']) != '' ) { $proDefault .= ' '; }
                if ( NoNull($pkaDefault) != '' && NoNull($Row['first_ka'], $Row['first_ro']) != '' ) { $pkaDefault .= ' '; }
                $proDefault .= CleanName(NoNull($Row['last_ro'], $Row['last_ka']));
                $pkaDefault .= CleanName(NoNull($Row['first_ka'], $Row['first_ro']));

                // Mark the Account as Locked If Required
                if ( YNBool($Row['is_locked']) && NoNull($Row['type']) != 'account.expired' ) {
                    $sOK = $this->_setAccountExpired(nullInt($Row['account_id']));
                    $Row['type'] = 'account.expired';
                }

                // Construct the Output
                $lang_label = 'lbl_' . NoNull($Row['language_code']);
                $data = array( 'account_id' => nullInt($Row['account_id']),
                               'login'      => NoNull($Row['login']),
                               'email'      => NoNull($Row['email']),
                               'type'       => NoNull($Row['type']),
                               'is_locked'  => YNBool($Row['is_locked']),

                               'person'     => array( 'id'           => nullInt($Row['person_id']),
                                                      'last_ro'      => CleanName($Row['last_ro']),
                                                      'first_ro'     => CleanName($Row['first_ro']),
                                                      'last_ka'      => CleanName($Row['last_ka']),
                                                      'first_ka'     => CleanName($Row['first_ka']),
                                                      'gender'       => NoNull($Row['gender'], 'M'),
                                                      'display_name' => NoNull($Row['display_name']),
                                                      'guid'         => NoNull($Row['person_guid']),

                                                      'print_ro'     => NoNull($Row['print_ro'], $proDefault),
                                                      'print_ka'     => NoNull($Row['print_ka'], $pkaDefault),

                                                      'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['person_created_at'])),
                                                      'created_unix' => strtotime($Row['person_created_at']),
                                                      'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['person_updated_at'])),
                                                      'updated_unix' => strtotime($Row['person_updated_at']),
                                                     ),

                               'employee'   => array( 'id'        => nullInt($Row['employee_id']),
                                                      'guid'      => NoNull($Row['employee_guid']),
                                                      'cosmos_id' => NoNull($Row['employee_no']),
                                                      'is_active' => YNBool($Row['is_active']),

                                                      'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['employee_created_at'])),
                                                      'created_unix' => strtotime($Row['employee_created_at']),
                                                      'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['employee_updated_at'])),
                                                      'updated_unix' => strtotime($Row['employee_updated_at']),
                                                     ),

                               'scopes'     => explode(',', NoNull($Row['scopes'])),

                               'meta'       => array( 'community_moderator' => YNBool($Row['community_moderator']),
                                                     ),

                               'passwords'  => $this->_getAccountPassHistory($Row['account_id']),

                               'language'   => array( 'code' => NoNull($Row['language_code']),
                                                      'name' => NoNull($this->strings[$lang_label]),
                                                     ),

                               'school'     => array( 'lc_id'       => nullInt($Row['school_id']),
                                                      'description' => NoNull($this->strings['lblLC' . nullInt($Row['school_id'])]),
                                                     ),
                              );
            }

            // If We Have Data, Return It
            if ( is_array($data) ) { return $data; }
        }

        // If We're Here, We Have a Bad GUID
        return "Unrecognized GUID Supplied";
    }

    /**
     *  Function Marks an Account as Expired without Validation or Verification
     */
    private function _setAccountExpired( $AccountID ) {
        if ( nullInt($AccountID) <= 0 ) { return false; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[RECORD_ID]'  => nullInt($AccountID),
                         );
        $sqlStr = readResource(SQL_DIR . '/setAccountExpired.sql', $ReplStr);
        $isOK = doSQLExecute($sqlStr);

        // Return a Simple Boolean
        return true;
    }

    /**
     *  Function Collects the Password History for a Given Account.ID if a Person's Permissions are High Enough
     */
    private function _getAccountPassHistory( $AccountID ) {
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[RECORD_ID]'  => nullInt($AccountID),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/getAccountPassHistory.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();
            foreach ( $rslt as $Row ) {
                $data[] = array( 'is_reset'     => YNBool($Row['is_reset']),
                                 'request_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                                 'request_unix' => strtotime($Row['created_at']),
                                 'request_by'   => $this->_getAccountInfo($Row['created_by'])
                                );
            }

            // If We Have Data, Return It
            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, The Person Does Not Have Permission (or there is no Password History)
        return false;
    }

    /** ********************************************************************* *
     *  Profile
     ** ********************************************************************* */
    private function _setProfileLanguage() {
        $CleanLang = NoNull($this->settings['language_code'], $this->settings['language']);
        if ( $CleanLang == '' ) { return "Invalid Language Preference Supplied"; }

        // Update the Database
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[LANG_CODE]'  => sqlScrub($CleanLang),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/setLanguage.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // Return a Profile Object for the Current Account or an Unhappy String
        return $this->_getProfile();
    }

    /**
     *  Function Updates a Person's Profile Data
     */
    private function _setProfile() {
        $CleanName = NoNull(NoNull($this->settings['pref_name'], $this->settings['pref-name']), $this->settings['display_as']);
        $CleanLang = NoNull(NoNull($this->settings['pref_lang'], $this->settings['pref-lang']), $this->settings['language']);
        $CleanMail = NoNull(NoNull($this->settings['pref_mail'], $this->settings['pref-mail']), $this->settings['mail_addr']);
        $CleanTime = NoNull(NoNull($this->settings['pref_zone'], $this->settings['pref-zone']), $this->settings['timezone']);
        $CleanGUID = NoNull($this->settings['persona_guid'], $this->settings['persona-guid']);

        // Perform Some Basic Validation
        if ( mb_strlen($CleanGUID) != 36 ) {
            $this->_setMetaMessage("Invalid Persona GUID Supplied", 400);
            return false;
        }
        if ( $CleanMail != '' ) {
            if ( validateEmail($CleanMail) === false ) {
                $this->_setMetaMessage("Invalid Email Address Supplied", 400);
                return false;
            }
        }

        // Check for Values and Set Existing Values if Needs Be
        if ( NoNull($CleanLang) == '' ) { $CleanLang = NoNull($this->settings['_language_code']); }
        if ( NoNull($CleanTime) == '' ) { $CleanTime = NoNull($this->settings['_timezone']); }

        // Update the Database
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[PERSONA_GUID]' => sqlScrub($CleanGUID),

                          '[PREF_NAME]'    => sqlScrub($CleanName),
                          '[PREF_LANG]'    => sqlScrub($CleanLang),
                          '[PREF_MAIL]'    => sqlScrub($CleanMail),
                          '[PREF_TIME]'    => sqlScrub($CleanTime),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/setProfile.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // Return a Profile Object for the Current Account or an Unhappy String
        return $this->_getProfile();
    }

    private function _getProfile() {
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']) );
        $sqlStr = readResource(SQL_DIR . '/account/getProfile.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $strings = getLangDefaults($Row['language_code']);

                $rVal = array( 'guid'           => NoNull($Row['guid']),
                               'type'           => NoNull($Row['type']),
                               'timezone'       => Nonull($Row['timezone']),

                               'display_name'   => NoNull($Row['display_name']),
                               'mail_address'   => NoNull($Row['mail_address']),

                               'language'       => array( 'code' => NoNull($Row['language_code']),
                                                          'name' => NoNull($strings['lang_name'], $Row['language_name']),
                                                         ),
                               'personas'       => $this->_getAccountPersonas($Row['account_id']),
                               'bucket'         => array( 'storage'   => 0,
                                                          'available' => 0,
                                                          'files'     => 0,
                                                         ),

                               'created_at'     => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                               'created_unix'   => strtotime($Row['created_at']),
                               'updated_at'     => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                               'updated_unix'   => strtotime($Row['updated_at']),
                              );
            }
        }

        // Return the Profile Object or an Unhappy String
        return $rVal;
    }

    /**
     *  Function Records an Updated Public Profile for a Given Persona.guid Value
     */
    private function _setPublicProfile() {
        $CleanGUID = '';
        $opts = ['PgRoot', 'PgSub1', 'persona_guid', 'guid'];
        foreach ( $opts as $opt ) {
            $guid = NoNull($this->settings[ $opt ]);
            if ( $CleanGUID == '' && strlen($guid) == 36 ) { $CleanGUID = $guid; }
        }
        $CleanBio = NoNull($this->settings['bio_text'], $this->settings['persona_bio']);

        // Ensure We Have a GUID
        if ( strlen($CleanGUID) != 36 ) { return "Invalid Persona GUID Supplied"; }

        // Collect the Data
        $ReplStr = array( '[PERSONA_GUID]' => sqlScrub($CleanGUID),
                          '[PERSONA_BIO]'  => sqlScrub($CleanBio),
                          '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/setPublicProfile.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);
        if ( $rslt > 0 ) {
            return $this->_getPublicProfile();
        }

        // If We're Here, We Couldn't Update the Public Profile
        return "Could Not Update Public Profile";
    }

    /**
     *  Function Builds the Public Profile for a Given Persona.guid Value
     */
    private function _getPublicProfile() {
        $CleanGUID = '';
        $opts = ['PgRoot', 'PgSub1', 'persona_guid', 'guid'];
        foreach ( $opts as $opt ) {
            $guid = NoNull($this->settings[ $opt ]);
            if ( $CleanGUID == '' && strlen($guid) == 36 ) { $CleanGUID = $guid; }
        }

        // Ensure We Have a GUID
        if ( strlen($CleanGUID) != 36 ) { return "Invalid Persona GUID Supplied"; }

        // Collect the Data
        $ReplStr = array( '[PERSONA_GUID]' => sqlScrub($CleanGUID),
                          '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/getPublicProfile.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $bio_html = NoNull($Row['persona_bio']);
                if ( $bio_html != '' ) {
                    require_once(LIB_DIR . '/posts.php');
                    $post = new Posts($this->settings);
                    $bio_html = $post->getMarkdownHTML($bio_html, 0, false, true);
                    unset($post);
                }

                return array( 'guid'         => NoNull($Row['persona_guid']),
                              'timezone'     => Nonull($Row['timezone']),
                              'as'           => NoNull($Row['name']),
                              'name'         => NoNull(NoNull($Row['first_name']) . ' ' . NoNull($Row['last_name']), $Row['display_name']),
                              'avatar_url'   => NoNull($Row['site_url'] . '/avatars/' . $Row['avatar_img']),
                              'site_url'     => NoNull($Row['site_url']),
                              'bio'          => array( 'text' => NoNull($Row['persona_bio']),
                                                       'html' => $bio_html,
                                                      ),
                              'days'         => nullInt($Row['days']),
                              'is_you'       => YNBool($Row['is_you']),

                              'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                              'created_unix' => strtotime($Row['created_at']),
                              );
            }
        }

        // If We're Here, There Is No Persona
        return false;
    }

    /**
     *  Function Collects the Most Recent posts associated with a Persona
     */
    private function _getProfilePosts() {
        $CleanGUID = NoNull($this->settings['guid'], $this->settings['PgSub1']);
        if ( $CleanGUID == 'me' && NoNull($this->settings['_persona_guid']) != '' ) {
            $CleanGUID = $this->settings['_persona_guid'];
        }

        $this->settings['_for_guid'] = NoNull($CleanGUID);

        require_once(LIB_DIR . '/posts.php');
        $posts = new Posts($this->settings);
        $data = $posts->getPersonaPosts();
        unset($posts);

        // If we have data, return it
        if ( is_array($data) && count($data) > 0 ) { return $data; }

        // If We're Here, There's Nothing
        return array();
    }

    private function _getAccountPersonas( $AccountID = 0 ) {
        if ( nullInt($AccountID) <= 0 ) { return false; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']) );
        $sqlStr = readResource(SQL_DIR . '/account/getPersonas.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $cdnUrl = getCdnUrl();
            $data = false;

            foreach ( $rslt as $Row ) {
                $data = array( 'guid'           => NoNull($Row['guid']),

                               'display_name'   => NoNull($Row['display_name']),
                               'first_name'     => NoNull($Row['first_name']),
                               'last_name'      => NoNull($Row['last_name']),
                               'email'          => NoNull($Row['email']),

                               'avatar_url'     => "$cdnUrl/" . NoNull($Row['avatar_img']),
                               'is_active'      => YNBool($Row['is_active']),

                               'created_at'     => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                               'created_unix'   => strtotime($Row['created_at']),
                               'updated_at'     => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                               'updated_unix'   => strtotime($Row['updated_at']),
                              );
            }

            // If We Have Data, Return It
            if ( is_array($data) ) { return $data; }
        }

        // If We're Here, There Are No Personas
        return false;
    }

    /**
     *  Function Will Ideally Be Called Only Once By Each Person, Setting the Welcome Message as "Done"
     */
    private function _setWelcomeDone() {
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[PERSON_ID]'  => nullInt($this->settings['_person_id']),
                          '[TYPE]'       => 'system.welcome.done',
                          '[VALUE]'      => 'Y',
                         );
        $sqlStr = readResource(SQL_DIR . '/account/setAccountMeta.sql', $ReplStr, true);
        $isOK = doSQLExecute($sqlStr);

        // Return an Empty Array
        return array();
    }

    /** ********************************************************************* *
     *  Account Security Elements (Password & Lifespans)
     ** ********************************************************************* */
    private function _resetPassword() {
        $CleanGUID = NoNull($this->settings['guid'], $this->settings['PgSub1']);
        $CleanPass = NoNull($this->settings['account_pass']);

        // Verify We Have Minutes
        if ( $CleanPass == "" ) { return "Invalid Password Supplied"; }
        if ( mb_strlen($CleanPass) <= 5 ) { return "Password Is Too Short"; }
        if ( $CleanPass != '' && substr_count($CleanPass, '*') < 6 ) {
            // Create The SQL Query and Update
            $ReplStr = array( '[ACCOUNT_ID]'    => nullInt($this->settings['_account_id']),
                              '[EMPLOYEE_GUID]' => sqlScrub($CleanGUID),
                              '[USERPASS]'      => sqlScrub($CleanPass),
                              '[SHA_SALT]'      => sqlScrub(SHA_SALT),
                              '[SQL_SPLITTER]'  => sqlScrub(SQL_SPLITTER),
                             );
            $sqlStr = readResource(SQL_DIR . '/account/resetPassword.sql', $ReplStr, true);
            $isOK = doSQLExecute($sqlStr);

            // Record the Event
            setActivityRecord( $this->settings['_account_id'], 'account.update', 'action.complete', 0, "Updated Password for Employee [$CleanGUID]" );

            // Return an Empty Array if Successful
            if ( $isOK > 0 ) { return array(); }
        }

        // If We're Here, Nothing Was Done
        return false;
    }

    /** ********************************************************************* *
     *  Preferences
     ** ********************************************************************* */
    /**
     *  Function Sets a Person's Preference and Returns a Preference Object
     */
    private function _setPreference() {
        $CleanValue = NoNull($this->settings['value']);
        $CleanType = NoNull($this->settings['type']);
        $rVal = "Could Not Record Account Preference";

        if ( $CleanValue == '' ) { return "Invalid Value Passed"; }
        if ( $CleanType == '' ) { return "Invalid Type Passed"; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[VALUE]'      => sqlScrub($CleanValue),
                          '[TYPE_KEY]'   => strtolower(sqlScrub($CleanType)),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/setPreference.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);
        if ( $rslt ) { $rVal = $this->_getPreference(); }

        // Return the Preference Object or an Unhappy String
        return $rVal;
    }

    private function _getPreference( $type = '' ) {
        $CleanType = NoNull($type, $this->settings['type']);
        $rVal = '';

        if ( $CleanType == '' ) { return "Invalid Type Passed"; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[TYPE_KEY]'   => strtolower(sqlScrub($CleanType)),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/getPreference.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();
            foreach ( $rslt as $Row ) {
                $data[] = array( 'type'         => NoNull($Row['type']),
                                 'value'        => NoNull($Row['value']),

                                 'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                                 'created_unix' => strtotime($Row['created_at']),
                                 'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                 'updated_unix' => strtotime($Row['updated_at']),
                                );
            }

            // If We Have Data, Return it
            if ( count($data) > 0 ) { $rVal = (count($data) == 1) ? $data[0] : $data; }
        }

        // Return the Preference Object or an Unhappy String
        return $rVal;
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