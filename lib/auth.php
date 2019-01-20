<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to Authorize Accounts and Verify Tokens
 */
require_once( LIB_DIR . '/functions.php');

class Auth {
    var $settings;
    var $cache;

    function __construct( $Items ) {
        $this->_populateClass( $Items );
    }

    /** ********************************************************************* *
     *  Population
     ** ********************************************************************* */
    /**
     *  Function Populates the Class Using a Token if Supplied
     */
    private function _populateClass( $Items = array() ) {
        $data = ( is_array($Items) ) ? $this->_getBaseArray( $Items ) : array();
        if ( !defined('PASSWORD_LIFE') ) { define('PASSWORD_LIFE', 36525); }
        if ( !defined('ACCOUNT_LOCK') ) { define('ACCOUNT_LOCK', 36525); }
        if ( !defined('TOKEN_PREFIX') ) { define('TOKEN_PREFIX', 'BSQAA_'); }
        if ( !defined('TOKEN_EXPY') ) { define('TOKEN_EXPY', 30); }

        // Set the Class Array Accordingly
        $this->settings = $data;
        $this->cache = false;
        unset($data);
    }

    /**
     *  Function Returns the Basic Array Used by the Authorization Class
     */
    private function _getBaseArray( $Items ) {
        $this->settings = array( 'HomeURL' => str_replace(array('https://', 'http://'), '', $Items['HomeURL']) );
        $ChannelGuid = NoNull($Items['channel_guid'], $Items['channel-guid']);
        $ClientGuid = NoNull($Items['client_guid'], $Items['client_id']);
        $Name = NoNull($Items['account_name'], $Items['acctname']);
        $Pass = NoNull($Items['account_pass'], $Items['acctpass']);
        $data = $this->_getTokenData($Items['token']);

        return array( 'is_valid'     => ((is_array($data)) ? $data['_logged_in'] : false),

                      'token'        => NoNull($Items['token']),
                      'account_name' => NoNull($Name),
                      'account_pass' => NoNull($Pass),
                      'client_guid'  => NoNull($ClientGuid),
                      'channel_guid' => NoNull($ChannelGuid),
                      'theme'        => 'default',

                      'HomeURL'      => str_replace(array('https://', 'http://'), '', $Items['HomeURL']),
                      'ReqType'      => $Items['ReqType'],
                      'PgRoot'       => $Items['PgRoot'],
                      'PgSub1'       => $Items['PgSub1'],
                     );
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
            case 'status':
                $rVal = $this->_checkTokenStatus();
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy String
        return $rVal;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case 'login':
            case '':
                $rVal = $this->_performLogin();
                break;

            case 'logout':
                $rVal = $this->_performLogout();
                break;

            case 'reset':
                $rVal = $this->_requestPassReset();
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy String
        return $rVal;
    }

    private function _performDeleteAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case '':
                $rVal = $this->_performLogout();
                break;

            default:
                // Do Nothing
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    /** ********************************************************************* *
     *  Public Properties & Functions
     ** ********************************************************************* */
    public function isLoggedIn() { return BoolYN($this->settings['is_valid']); }
    public function getLoginToken( $AccountData ) { return $this->_getLoginToken($AccountData); }
    public function performLogout() { return $this->_performLogout(); }
    public function getTokenData( $Token ) { return $this->_getTokenData($Token); }

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
     *  Class Functions
     ** ********************************************************************* */
    /**
     *  Function Returns Any Data That Might Be Associated With a Token
     */
    private function _getTokenData( $Token = '' ) {
        // If We Have the Data, Return It
        if ( array_key_exists('token_data', $GLOBALS) ) { return $GLOBALS['token_data']; }

        // Verifiy We Have a Token Value and Split It Accordingly
        if ( NoNull($Token) == '' ) { return false; }
        $data = explode('_', $Token);
        if ( count($data) != 3 ) { return false; }

        // Get the Maximum Age of an Account's Password (28.25 years by default)
        $PassAge = 10000;
        if ( defined('PASSWORD_LIFE') ) { $PassAge = nullInt(PASSWORD_LIFE, 10000); }
        
        // Get the Home URL Address (For Site-Level Access)
        $HomeURL = NoNull($this->settings['HomeURL']);

        // If the Prefix Matches, Validate the Token Data
        if ( NoNull($data[0]) == str_replace('_', '', TOKEN_PREFIX) ) {
            $ReplStr = array( '[TOKEN_ID]'     => alphaToInt($data[1]),
                              '[TOKEN_GUID]'   => sqlScrub($data[2]),
                              '[PASSWORD_AGE]' => nullInt($PassAge, 10000),
                              '[LIFESPAN]'     => nullInt(TOKEN_EXPY),
                              '[HOMEURL]'      => sqlScrub($HomeURL),
                             );
            $sqlStr = readResource(SQL_DIR . '/auth/getTokenData.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $storage = 5368709120;

                    $rVal = array( '_account_id'     => nullInt($Row['account_id']),
                                   '_email'          => NoNull($Row['email']),
                                   '_display_name'   => NoNull($Row['display_name']),
                                   '_avatar_file'    => NoNull($Row['avatar_url'], 'default.png'),
                                   '_account_type'   => NoNull($Row['type']),
                                   '_persona_guid'   => NoNull($Row['default_persona']),
                                   '_access_level'   => NoNull($Row['access_level'], 'read'),
                                   '_language_code'  => NoNull($Row['language_code']),
                                   '_welcome_done'   => YNBool($Row['welcome_done']),
                                   '_theme'          => NoNull($Row['theme'], 'default'),
                                   '_timezone'       => NoNull($Row['timezone'], 'UTC'),
                                   '_storage_total'  => $storage,
                                   '_storage_used'   => 0,
                                   '_pass_change'    => YNBool($Row['password_change']),
                                   '_token_id'       => alphaToInt($data[1]),
                                   '_token_guid'     => NoNull($data[2]),
                                   '_logged_in'      => true,
                                  );

                    // Ensure the Password Change Requirement is Set If Required
                    if ( YNBool($Row['password_change']) ) { $this->_requirePasswordChange($Row['account_id']); }
                }
            }

            // Touch the Token Record to Mark it as Active
            $sqlStr = readResource(SQL_DIR . '/auth/touchToken.sql', $ReplStr);
            $isOK = doSQLExecute($sqlStr);
        }

        // Set the Cache and Return an Array of Data or an Unhappy Boolean
        $GLOBALS['token_data'] = $rVal;
        return $rVal;
    }

    /**
     *  Function creates a Token Record in the Database and Returns an AlphaNumeric Key
     */
    private function _getLoginToken( $data ) {
        $ClientGuid = NoNull($this->settings['client_guid']);

        // If We Do Not Have a Valid Client.guid Value, Do Not Continue
        if ( strlen($ClientGuid) != 36 ) {
            $this->_setMetaMessage( "Invalid Client GUID Supplied", 400 );
            return false;
        }

        // Parse the Array and get a Token
        if ( is_array($data) ) {
            $ReplStr = array( '[ACCOUNT_ID]'  => nullInt($data['account_id']),
                              '[CLIENT_GUID]' => sqlScrub($ClientGuid),
                              '[LIFESPAN]'    => nullInt(TOKEN_EXPY),
                             );
            $sqlStr = readResource(SQL_DIR . '/auth/getLoginToken.sql', $ReplStr);
            $rslt = doSQLExecute($sqlStr);

            // If We Have a Token.ID Value, Grab the API Token Value
            if ( $rslt > 0 ) {
                $ReplStr['[TOKEN_ID]'] = $rslt;
                $sqlStr = readResource(SQL_DIR . '/auth/getTokenByID.sql', $ReplStr);
                $tslt = doSQLQuery($sqlStr);
                if ( is_array($tslt) ) {
                    foreach ($tslt as $Row) {
                        return TOKEN_PREFIX . intToAlpha($Row['token_id']) . '_' . NoNull($Row['guid']);
                    }
                }
            }
        }

        // If We're Here, There Is No Valid Token
        return false;
    }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function Attempts to Log a User In with X-Auth (Username/Password Combination)
     *      and returns a Token or Unhappy Boolean
     */
    private function _performLogin() {
        $ChanGUID = sqlScrub($this->settings['channel_guid']);
        $AcctName = sqlScrub($this->settings['account_name']);
        $AcctPass = sqlScrub($this->settings['account_pass']);
        $LangCd = DEFAULT_LANG;
        $Token = false;
        $rVal = false;

        // Ensure We Have the Data, and Check the Database
        if ( $AcctName != "" && $AcctPass != "" && $AcctName != $AcctPass ) {
            $ReplStr = array( '[CHANNEL_GUID]' => sqlScrub($ChanGUID),
                              '[USERADDR]'     => $AcctName,
                              '[USERPASS]'     => $AcctPass,
                              '[SHA_SALT]'     => SHA_SALT,
                             );
            $DaysInactive = 0;
            $sqlStr = readResource(SQL_DIR . '/auth/performLogin.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $data = false;
                foreach ( $rslt as $Row ) {
                    $LangCd = NoNull($Row['language_code'], DEFAULT_LANG);
                    $DaysInactive = nullInt($Row['last_activity']);

                    $data = array( 'account_id'    => nullInt($Row['account_id']),
                                   'type'          => NoNull($Row['type']),
                                  );

                    // Ensure the Account.Type Value is Set If Expired
                    if ( $DaysInactive > ACCOUNT_LOCK ) { $this->_expireAccount($Row['account_id']); }
                }

                $Token = $this->_getLoginToken( $data );
            }
        }

        // Return the Token String or an Unhappy Array
        if ( is_string($Token) ) {
            return array( 'token'   => $Token,
                          'lang_cd' => strtolower($LangCd),
                         );
        } else {
            $this->_setMetaMessage("Unrecognised Credentials", 400);
            return array();
        }
    }

    /**
     *  Function Sets the "Require Password Change" Account.Meta Value for a Given Account
     *  Note: Guest and Admin Accounts never require this
     */
    private function _requirePasswordChange($AccountID) {
        if ( nullInt($AccountID) > 1 ) {
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($AccountID) );
            $sqlStr = readResource(SQL_DIR . '/auth/setReqPassChg.sql', $ReplStr, true);
            $isOK = doSQLExecute($sqlStr);
        }
    }

    /**
     *  Function Marks an Account as Expired
     */
    private function _expireAccount($AccountID) {
        if ( nullInt($AccountID) > 1 ) {
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($AccountID) );
            $sqlStr = readResource(SQL_DIR . '/auth/setExpired.sql', $ReplStr, true);
            $isOK = doSQLExecute($sqlStr);
        }
    }

    /**
     *  Function Marks a Token Record as isDeleted = 'Y'
     */
    private function _performLogout() {
        $Token = NoNull($this->settings['token']);
        $rVal = false;
        if ( $Token != '' ) {
            $data = explode('_', $Token);
            if ( $data[0] == str_replace('_', '', TOKEN_PREFIX) ) {
                $ReplStr = array( '[TOKEN_ID]'   => alphaToInt($data[1]),
                                  '[TOKEN_GUID]' => sqlScrub($data[2]),
                                 );
                $sqlStr = readResource(SQL_DIR . '/auth/performLogout.sql', $ReplStr);
                $rslt = doSQLExecute($sqlStr);
                $rVal = $this->_checkTokenStatus();
            }
        }

        // Return the Reponse or an Unhappy Array
        if ( is_array($rVal) ) {
            return $rVal;
        } else {
            $this->_setMetaMessage("Unrecognised Credentials", 400);
            return array();
        }
    }

    private function _checkTokenStatus() {
        $Token = NoNull($this->settings['token']);

        if ( mb_strlen($Token) >= 50 ) {
            $data = explode('_', $Token);
            if ( $data[0] == str_replace('_', '', TOKEN_PREFIX) ) {
                $ReplStr = array( '[TOKEN_ID]'   => alphaToInt($data[1]),
                                  '[TOKEN_GUID]' => sqlScrub($data[2]),
                                  '[LIFESPAN]'   => nullInt(TOKEN_EXPY),
                                 );
                $sqlStr = readResource(SQL_DIR . '/auth/chkToken.sql', $ReplStr);
                $rslt = doSQLQuery($sqlStr);
                if ( is_array($rslt) ) {
                    require_once(LIB_DIR . '/account.php');
                    $acct = new Account(array());
                    foreach ( $rslt as $Row ) {
                        $isActive = !YNBool($Row['is_deleted']);
                        $rVal = array( 'account'      => (($isActive) ? $acct->getAccountInfo($Row['account_id']) : false),
                                       'distributors' => $this->_getValidChannels($Row['account_id']),
                                       'is_active'    => $isActive,
                                       'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                       'updated_unix' => strtotime($Row['updated_at']),
                                      );
                    }
                    unset($acct);

                    // If We Have Data, Return It
                    if ( is_array($rVal) ) { return $rVal; }
                }
            }
        }

        // If We're Here, the Token is Invalid (or Expired)
        $this->_setMetaMessage("Invalid or Expired Token Supplied", 400);
        return array();
    }
    
    /**
     *  Function Returns an Array Containing the Personas and Channels an Account can Write To
     */
    private function _getValidChannels( $AccountID ) {
        if ( nullInt($AccountID) <= 0 ) { return false; }
        
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($AccountID) );
        $sqlStr = readResource(SQL_DIR . '/auth/getValidChannels.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();
            $pids = array();
            $sets = array();
            
            foreach ( $rslt as $Row ) {
                if ( in_array(nullInt($Row['persona_id']), $pids) === false ) {
                    $sets[nullInt($Row['persona_id'])] = array( 'guid'      => NoNull($Row['guid']),
                                                                'name'      => NoNull($Row['name']),
                                                                'display'   => NoNull($Row['display_name']),
                                                                'avatar'    => NoNull($Row['site_url']) . '/avatars/' . NoNull($Row['avatar_img']),
                                                                'is_active' => YNBool($Row['is_active']),
                                                                'channels'  => array(),
                                                               );
                    $pids[] = nullInt($Row['persona_id']);
                }

                $sets[nullInt($Row['persona_id'])]['channels'][] = array( 'channel_guid' => NoNull($Row['channel_guid']),
                                                                          'site_guid'    => NoNull($Row['site_guid']),
                                                                          'site_name'    => NoNull($Row['site_name']),
                                                                          'url'          => NoNull($Row['site_url']),
                                                                         );
            }

            // Assemble the Proper Return Array
            foreach ( $pids as $id ) {
                $data[] = $sets[$id];
            }

            // Return the Data
            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There's Nothing
        return false;
    }

    /**
     *  Record a Password Reset Request (Used by the Portal)
     */
    private function _requestPassReset() {
        $AcctFilter = NoNull($this->settings['account_name'], $this->settings['acct-name']);

        // Ensure We Have a Minimum Amount of Criteria
        if ( $AcctFilter == '' ) {
            $this->_setMetaMessage("Please Enter a Valid Account ID", 400);
            return array();
        }

        // Record the Data if a Match is Found
        $ReplStr = array( '[ACCOUNT_FILTER]' => sqlScrub($AcctFilter),
                         );
        $sqlStr = readResource(SQL_DIR . '/auth/setPassResetReq.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // If the Request was Recorded, Return a Simple Array
        if ( nullInt($rslt) > 0 ) {
            return array( 'status' => 'requested' );
        } else {
            return false;
        }
    }

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