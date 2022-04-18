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
            case 'checkname':
            case 'chkname':
                return $this->_checkAvailability();
                break;

            case 'list':
                if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }
                return $this->_getAccountList();
                break;

            case 'histochart':
                return $this->_getHistoChart();
                break;

            case 'histogram':
                return $this->_getHistorgram();
                break;

            case 'blocked':
                return $this->_getRelations('is_blocked');
                break;

            case 'followers':
                return $this->_getRelations('follows_you');
                break;

            case 'following':
                return $this->_getRelations('follows');
                break;

            case 'muted':
                return $this->_getRelations('is_muted');
                break;

            case 'starred':
                return $this->_getRelations('is_starred');
                break;

            case 'preferences':
            case 'preference':
            case 'prefs':
                return $this->_getPreference();
                break;

            case 'persona':
            case 'person':
                return $this->_getPersonaProfile();
                break;

            case 'profile':
            case 'bio':
                return $this->_getPublicProfile();
                break;

            case 'posts':
                return $this->_getProfilePosts();
                break;

            case 'me':
                if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }
                return $this->_getProfile();
                break;

            case 'summary':
                if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }
                return $this->_getAccountSummary();
                break;

            default:
                // Do Nothing
        }

        // If We're Here, There Was No Matching Activity
        return false;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $excludes = array( 'create', 'forgot' );
        $rVal = false;

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in'] && in_array($Activity, $excludes) === false ) { return "You Need to Log In First"; }

        switch ( $Activity ) {
            case 'bio':
                $rVal = $this->_setPublicProfile();
                break;

            case 'create':
                $rVal = $this->_createAccount();
                break;

            case 'forgot':
                $rVal = $this->_forgotPassword();
                break;

            case 'preference':
                return $this->_setPreference();
                break;

            case 'profile':
            case 'me':
                $rVal = $this->_setProfile();
                break;

            case 'relations':
            case 'relation':
                $rVal = $this->_setRelation();
                break;

            case 'follow':
                $this->settings['follows'] = 'Y';
                $rVal = $this->_setRelation();
                break;

            case 'mute':
                $this->settings['muted'] = 'Y';
                $rVal = $this->_setRelation();
                break;

            case 'block':
                $this->settings['blocked'] = 'Y';
                $rVal = $this->_setRelation();
                break;

            case 'star':
                $this->settings['starred'] = 'Y';
                $rVal = $this->_setRelation();
                break;

            case 'details':
            case 'detail':
            case '':
                $this->_setAccountDetails();
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
            case 'relations':
            case 'relation':
                $rVal = $this->_setRelation();
                break;

            case 'follow':
                $this->settings['follows'] = 'N';
                $rVal = $this->_setRelation();
                break;

            case 'mute':
                $this->settings['muted'] = 'N';
                $rVal = $this->_setRelation();
                break;

            case 'block':
                $this->settings['blocked'] = 'N';
                $rVal = $this->_setRelation();
                break;

            case 'star':
                $this->settings['starred'] = 'N';
                $rVal = $this->_setRelation();
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
    public function getAccountInfo($AcctID) { return $this->_getAccountInfo($AcctID); }
    public function getPublicProfile() { return $this->_getPublicProfile(); }
    public function getPreference($Type) {
        $data = $this->_getPreference($Type);
        return $data['value'];
    }

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
                        $this->cache[$AcctID] = array( 'display_name'   => NoNull($Row['display_name']),
                                                       'avatar_url'     => NoNull($Row['avatar_url'], 'default.png'),
                                                       'type'           => NoNull($Row['type']),
                                                       'guid'           => NoNull($Row['account_guid']),
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

    /** ********************************************************************* *
     *  Account Creation
     ** ********************************************************************* */
    private function _createAccount() {
        if ( !defined('DEFAULT_LANG') ) { define('DEFAULT_LANG', 'en-us'); }
        if ( !defined('DEFAULT_DOMAIN') ) { define('DEFAULT_DOMAIN', ''); }
        if ( !defined('SHA_SALT') ) { define('SHA_SALT', ''); }
        if ( !defined('TIMEZONE') ) { define('TIMEZONE', ''); }

        return false;

        $CleanPass = NoNull($this->settings['pass'], $this->settings['password']);
        $CleanName = NoNull($this->settings['name'], $this->settings['persona']);
        $CleanMail = NoNull($this->settings['mail'], $this->settings['email']);
        $CleanTOS = NoNull($this->settings['terms'], $this->settings['tos']);
        $CleanDomain = NoNull($this->settings['domain'], DEFAULT_DOMAIN);
        $CleanLang = NoNull($this->settings['lang'], DEFAULT_LANG);
        $Redirect = NoNull($this->settings['redirect'], $this->settings['is_web']);

        /* Ensure there are no bad characters in the account name */
        $CleanName = NoNull(strip_tags(preg_replace("/[^a-zA-Z0-9]+/", '', $CleanName)));

        /* Now let's do some basic validation */
        if ( mb_strlen($CleanPass) <= 6 ) {
            $this->_setMetaMessage( "Password is too weak. Please choose a better one.", 400 );
            return false;
        }

        if ( mb_strlen($CleanName) < 2 ) {
            $this->_setMetaMessage( "Account name is too short. Please choose a longer one.", 400 );
            return false;
        }
        if ( mb_strlen($CleanName) > 40 ) {
            $this->_setMetaMessage( "Account name is too long. Please choose a shorter one.", 400 );
            return false;
        }

        if ( mb_strlen($CleanMail) <= 5 ) {
            $this->_setMetaMessage( "Email address is too short. Please enter a correct address.", 400 );
            return false;
        }

        if ( validateEmail($CleanMail) === false ) {
            $this->_setMetaMessage( "Email address does not appear correct. Please enter a correct address.", 400 );
            return false;
        }

        if ( YNBool($CleanTOS) === false ) {
            $this->_setMetaMessage( "Please read and accept the Terms of Service before creating an account.", 400 );
            return false;
        }

        // Ensure the Start of the Domain has a period
        if ( mb_substr($CleanDomain, 0, 1) != '.' ) {
            $CleanDomain = '.' . $CleanDomain;
        }

        // If we're here, we *might* be good. Create the account.
        $ReplStr = array( '[DOMAIN]' => sqlScrub($CleanDomain),
                          '[NAME]'   => sqlScrub($CleanName),
                          '[MAIL]'   => sqlScrub($CleanMail),
                          '[PASS]'   => sqlScrub($CleanPass),
                          '[LANG]'   => sqlScrub($CleanLang),
                          '[SALT]'   => sqlScrub(SHA_SALT),
                          '[ZONE]'   => sqlScrub(TIMEZONE),
                         );
        $sqlStr = prepSQLQuery( "CALL CreateAccount('[NAME]', '[PASS]', '[MAIL]', '[SALT]', '[DOMAIN]' );", $ReplStr );
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $ChannelId = false;
            $SiteUrl = false;
            $SiteId = false;
            $PaGuid = false;
            $AcctID = false;
            $Token = false;

            foreach ( $rslt as $Row ) {
                $SiteUrl = NoNull($Row['site_url']);
                $PaGuid = NoNull($Row['persona_guid']);
                $AcctID = nullInt($Row['account_id']);
            }

            // Create the Channel and Site
            if ( NoNull($SiteUrl) != '' && NoNull($PaGuid) != '' && nullInt($AcctID) > 0 ) {
                $ReplStr['[PERSONA_GUID]'] = sqlScrub($PaGuid);
                $ReplStr['[ACCOUNT_ID]'] = nullInt($AcctID);
                $ReplStr['[SITE_URL]'] = sqlScrub($SiteUrl);
                $ReplStr['[SITE_NAME]'] = sqlScrub( "$CleanName's Space");
                $ReplStr['[SITE_DESCR]'] = sqlScrub( "All About $CleanName");

                $sqlStr = prepSQLQuery( "CALL CreateSite([ACCOUNT_ID], '[PERSONA_GUID]', '[SITE_NAME]', '[SITE_DESCR]', '', '[SITE_URL]', 'visibility.public');", $ReplStr );
                $tslt = doSQLQuery($sqlStr);
                if ( is_array($tslt) ) {
                    foreach ( $tslt as $Row ) {
                        $ChannelId = nullInt($Row['channel_id']);
                        $SiteId = nullInt($Row['site_id']);
                    }
                }
            }

            // If CloudFlare is being used, configure the CNAME Record Accordingly
            if ( $AcctID > 0 ) {
                if ( !defined('CLOUDFLARE_API_KEY') ) { define('CLOUDFLARE_API_KEY', ''); }
                $zone = false;

                if ( NoNull(CLOUDFLARE_API_KEY) != '' ) {
                    require_once(LIB_DIR . '/system.php');
                    $sys = new System( $this->settings );
                    $zone = $sys->createCloudFlareZone( $SiteUrl );
                    unset($sys);
                }

                // Collect an Authentication Token and (if needs be) Redirect
                $sqlStr = prepSQLQuery( "CALL PerformDirectLogin([ACCOUNT_ID]);", $ReplStr );
                $tslt = doSQLQuery($sqlStr);
                if ( is_array($tslt) ) {
                    foreach ( $tslt as $Row ) {
                        $Token = TOKEN_PREFIX . intToAlpha($Row['token_id']) . '_' . NoNull($Row['token_guid']);
                    }
                }
            }
        }

        // What sort of return are we looking for?
        $url = NoNull($this->settings['HomeURL']) . '/welcome';
        switch ( strtolower($Redirect) ) {
            case 'web_redirect':
                if ( is_string($Token) ) {
                    $url .= '?token=' . $Token;
                } else {
                    $url = NoNull($this->settings['HomeURL']) . '/nodice';
                }
                redirectTo( $url, $this->settings );
                break;

            default:
                if ( is_string($Token) ) {
                    return array( 'token' => $Token,
                                  'url'   => NoNull($url),
                                 );
                } else {
                    $this->_setMetaMessage( "Could not create Account", 400 );
                    return false;
                }
        }

        // If We're Here, Something is Really Off
        return false;
    }

    private function _checkAvailability() {
        if ( !defined('DEFAULT_DOMAIN') ) { define('DEFAULT_DOMAIN', ''); }
        $CleanDomain = NoNull($this->settings['domain'], DEFAULT_DOMAIN);
        $CleanName = NoNull($this->settings['name'], $this->settings['persona']);

        if ( mb_strlen($CleanName) < 2 ) {
            $this->_setMetaMessage( "This Name is Too Short", 400 );
            return false;
        }

        if ( mb_strlen($CleanName) > 40 ) {
            $this->_setMetaMessage( "This Name is Too Long", 400 );
            return false;
        }

        if ( mb_strlen($CleanDomain) <= 3 || mb_strlen($CleanDomain) > 100 ) {
            $this->_setMetaMessage( "The Domain Name Appears Invalid", 400 );
            return false;
        }

        // Ensure the Start of the Domain has a period
        if ( mb_substr($CleanDomain, 0, 1) != '.' ) {
            $CleanDomain = '.' . $CleanDomain;
        }

        // Prepare the SQL Query
        $ReplStr = array( '[NAME]'   => sqlScrub($CleanName),
                          '[DOMAIN]' => sqlScrub($CleanDomain),
                         );
        $sqlStr = prepSQLQuery( "CALL CheckPersonaAvailable('[NAME]', '[DOMAIN]');", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                return array( 'name' => NoNull($Row['persona_name']),
                              'url'  => NoNull($Row['domain_url']),
                             );
            }
        }

        // If We're Here, Either Something's Wrong or the Requested Name in use
        return array();
    }

    /** ********************************************************************* *
     *  Password Management Functions
     ** ********************************************************************* */
    /**
     *  Function checks an email address is valid and sends an email to that address
     *      containing some links that allow them to sign into various 10C services.
     */
    private function _forgotPassword() {
        $CleanMail = NoNull($this->settings['email'], $this->settings['mail_addr']);
        $AccountID = 0;
        $TokenGuid = '';
        $TokenID = 0;
        $SocialUrl = '';
        $SiteUrl = '';
        $Name = '';

        $ReplStr = array( '[MAIL_ADDY]' => sqlScrub($CleanMail) );
        $sqlStr = readResource(SQL_DIR . '/account/getForgotDetails.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $AccountID = nullInt($Row['account_id']);
                $SiteUrl = NoNull($Row['site_url']);
                $Name = NoNull($Row['display_name'], NoNull($Row['first_name'], $Row['name']));
            }
        }

        // If we have a valid Account.id, Collect the Requisite Data to send a "Forgot Password" email
        if ( $AccountID > 0 ) {
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($AccountID) );
            $sqlStr = prepSQLQuery("CALL PerformDirectLogin([ACCOUNT_ID]);", $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $TokenGuid = NoNull($Row['token_guid']);
                    $TokenID = nullInt($Row['token_id']);
                }
            }
        }

        // Get the Social Site URL if We Have Valid Data
        $sqlStr = readResource(SQL_DIR . '/site/getSocialUrl.sql');
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $SocialUrl = NoNull($Row['social_url']);
            }
        }

        /* If we have a valid Token, then we should have all the other data, too */
        if ( $TokenID > 0 ) {
            // Get the HTML & PlainText Values for the Forgot Mail
            $ReplStr = array( '[ACCOUNT_NAME]' => NoNull($Name),
                              '[PRIMARY_URL]'  => NoNull($SiteUrl),
                              '[SOCIAL_URL]'   => NoNull($SocialUrl),
                              '[AUTH_TOKEN]'   => TOKEN_PREFIX . intToAlpha($TokenID) . '_' . NoNull($TokenGuid),
                             );
            $HtmlMsg = readResource(FLATS_DIR . '/templates/email.forgot.html', $ReplStr);
            $TextMsg = readResource(FLATS_DIR . '/templates/email.forgot.txt', $ReplStr);

            // Construct the Message
            $mailMsg = array( 'from_name' => '10Centuries',
                              'send_to'   => $CleanMail,
                              'subject'   => 'Forgot Your Password?',
                              'html'      => NoNull($HtmlMsg),
                              'text'      => NoNull($TextMsg),
                             );

            require_once(LIB_DIR . '/email.php');
            $mail = new Email($this->settings);
            $isOK = $mail->sendMail($mailMsg);
            unset($mail);
        }

        // Return an Empty Array, Regardless of whether the data is good or not (to prevent email cycling)
        return array();
    }

    /** ********************************************************************* *
     *  Persona Relations Management
     ** ********************************************************************* */
    /**
     *  Function returns a list of every Persona an Account has a Relation record with, including their own.
     */
    private function _getRelationsList() {
        $CleanGUID = NoNull($this->settings['PgSub1']);
        if ( strlen($CleanGUID) != 36 ) {
            $CleanGUID = NoNull($this->settings['persona_guid'], $this->settings['persona-guid']);
        }

        // Ensure the GUIDs are valid
        if ( strlen($CleanGUID) != 36 ) {
            $this->_setMetaMessage( "Invalid Persona GUID Supplied", 400 );
            return false;
        }

        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[PERSONA_GUID]' => sqlScrub($CleanGUID),
                         );
        $sqlStr = prepSQLQuery("CALL GetRelationsList([ACCOUNT_ID], '[PERSONA_GUID]');", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();
            foreach ( $rslt as $Row ) {
                $site = false;
                if ( NoNull($Row['site_url']) != '' ) {
                    $site = array( 'name' => NoNull($Row['site_name']),
                                   'url'  => NoNull($Row['site_url']),
                                  );
                }

                $data[] = array( 'guid'        => NoNull($post['persona_guid']),
                                 'as'          => '@' . NoNull($post['name']),
                                 'name'        => NoNull($post['display_name']),
                                 'avatar'      => NoNull($post['avatar_url']),
                                 'site'        => $site,

                                 'pin'         => NoNull($post['pin_type'], 'pin.none'),
                                 'you_follow'  => YNBool($post['follows']),
                                 'is_muted'    => YNBool($post['is_muted']),
                                 'is_starred'  => YNBool($post['is_starred']),
                                 'is_blocked'  => YNBool($post['is_blocked']),
                                 'is_you'      => YNBool($post['is_you']),

                                 'profile_url' => NoNull($post['profile_url']),
                                );
            }

            /* If we have data, return it */
            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There's Nothing ... which should never happen
        return array();
    }

    private function _setRelation() {
        $CleanGUID = NoNull($this->settings['persona_guid'], $this->settings['persona-guid']);
        $RelatedGUID = NoNull($this->settings['PgSub1']);
        $RefId = NoNull($this->settings['ref_id'], $this->settings['ref']);
        if ( strlen($RelatedGUID) != 36 ) {
            $RelatedGUID = NoNull($this->settings['related_guid'], $this->settings['related-guid']);
        }

        // Ensure the GUIDs are valid
        if ( strlen($CleanGUID) != 36 ) {
            $this->_setMetaMessage( "Invalid Persona GUID Supplied", 400 );
            return false;
        }
        if ( strlen($RelatedGUID) != 36 ) {
            $this->_setMetaMessage( "Invalid Related GUID Supplied", 400 );
            return false;
        }

        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[PERSONA_GUID]' => sqlScrub($CleanGUID),
                          '[RELATED_GUID]' => sqlScrub($RelatedGUID),

                          '[FOLLOWS]'      => sqlScrub($this->settings['follows']),
                          '[MUTED]'        => sqlScrub($this->settings['muted']),
                          '[BLOCKED]'      => sqlScrub($this->settings['blocked']),
                          '[STARRED]'      => sqlScrub($this->settings['starred']),
                          '[PINNED]'       => sqlScrub($this->settings['pin']),
                         );
        $sqlStr = prepSQLQuery("CALL SetPersonaRelation([ACCOUNT_ID], '[PERSONA_GUID]', '[RELATED_GUID]', '[FOLLOWS]', '[MUTED]', '[BLOCKED]', '[STARRED]', '[PINNED]');", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                return array( 'guid'         => NoNull($Row['related_guid']),
                              'ref_id'       => (($RefId != '') ? $RefId : false),

                              'follows'      => YNBool($Row['follows']),
                              'is_muted'     => YNBool($Row['is_muted']),
                              'is_blocked'   => YNBool($Row['is_blocked']),
                              'is_starred'   => YNBool($Row['is_starred']),
                              'pin_type'     => NoNull($Row['pin_type'], 'pin.none'),

                              'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                              'created_unix' => strtotime($Row['created_at']),
                              'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                              'updated_unix' => strtotime($Row['updated_at']),
                             );
            }
        }

        // If We're Here, We Couldn't Do Anything
        return false;
    }

    /** ********************************************************************* *
     *  Account Management
     ** ********************************************************************* */
    private function _setAccountDetails() {
        if ( !defined('SHA_SALT') ) { define('SHA_SALT', ''); }

        $CleanDisp = NoNull($this->settings['display-name'], $this->settings['display_name']);
        $FirstName = NoNull($this->settings['first-name'], $this->settings['first_name']);
        $LastName = NoNull($this->settings['last-name'], $this->settings['last_name']);
        $MailAddy = NoNull($this->settings['acct-mail'], $this->settings['acct_mail']);
        $AcctPass = NoNull($this->settings['acct-pass'], $this->settings['acct_pass']);

        $SendRemind = BoolYN(YNBool($this->settings['show-note'], $this->settings['show_note']));
        $ShowGeo = BoolYN(YNBool($this->settings['show-geo'], $this->settings['show_geo']));

        $AcctLang = NoNull($this->settings['acct-lang'], $this->settings['acct_lang']);
        $AcctZone = NoNull($this->settings['acct-zone'], $this->settings['acct_zone']);

        $ChanGuid = NoNull($this->settings['channel-guid'], $this->settings['channel_guid']);
        $WebReq = BoolYN(YNBool($this->settings['web-req'], $this->settings['web_req']));

        // Perform Some Basic Validation
        if ( $MailAddy != '' ) {
            if ( validateEmail($MailAddy) === false ) { $MailAddy = ''; }
        }

        // Do not let a password full of asterisks, as this is a default "dummy" value
        if ( $AcctPass != '' ) {
            for ( $i = 1; $i <= 100; $i++ ) {
                if ( $AcctPass == str_repeat('*', $i) ) { $AcctPass = ''; }
            }
        }

        // Ensure the Language is valid
        $valids = array('en-us', 'eo-us');
        $AcctLang = str_replace('lang.', '', $AcctLang);
        if ( in_array($AcctLang, $valids) === false ) { $AcctLang = DEFAULT_LANG; }

        // Begin the Update Process
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[TOKEN_GUID]'   => sqlScrub($this->settings['_token_guid']),
                          '[TOKEN_ID]'     => nullInt($this->settings['_token_id']),
                          '[CHANNEL_GUID]' => sqlScrub($ChanGuid),

                          '[ACCT_PASS]'    => sqlScrub($AcctPass),
                          '[SHA_SALT]'     => sqlScrub(SHA_SALT),

                          '[DISP_NAME]'    => sqlScrub($CleanDisp),
                          '[FIRST_NAME]'   => sqlScrub($FirstName),
                          '[LAST_NAME]'    => sqlScrub($LastName),
                          '[MAIL_ADDY]'    => sqlScrub($MailAddy),
                          '[ACCT_LANG]'    => sqlScrub($AcctLang),
                          '[ACCT_ZONE]'    => sqlScrub($AcctZone),
                          '[SEND_REMIND]'  => sqlScrub($SendRemind),
                          '[SHOW_GEO]'     => sqlScrub($ShowGeo),

                          '[WEB_REQ]'      => sqlScrub($WebReq),
                         );
        $sqlStr = prepSQLQuery("CALL AccountUpdate([ACCOUNT_ID], '[TOKEN_GUID]', [TOKEN_ID], '[CHANNEL_GUID]', " .
                                                  "'[DISP_NAME]', '[FIRST_NAME]', '[LAST_NAME]', " .
                                                  "'[MAIL_ADDY]', '[ACCT_LANG]', '[ACCT_ZONE]', " .
                                                  "'[SEND_REMIND]', '[SHOW_GEO]', " .
                                                  "'[ACCT_PASS]', '10c2015' );", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $errCode = NoNull($Row['error']);
            }
        }

        // Prep the Response
        if ( YNBool($WebReq) ) {
            // Determine the Return URL Path
            $url = $this->settings['HomeURL'] . '/account';
            if ( $errCode != '' ) { $url .= "?err=$errCode"; }
            redirectTo($url);

        } else {
            if ( $errCode != '' ) {
                $this->_setMetaMessage("Error: $errCode", 401);
                return false;
            } else {
                return array();
            }
        }
    }

    /** ********************************************************************* *
     *  Profile Management
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

        /* Check that the Avatar Type is Valid */
        $avatarTypes = array('gravatar', 'own');
        $CleanAvatar = NoNull($this->settings['avatar_type'], $this->settings['avatar-type']);
        if ( in_array($CleanAvatar, $avatarTypes) === false ) { $CleanAvatar = 'own'; }

        /* Check if there is an Avatar File Reference */
        $CleanAvatarFile = NoNull($this->settings['avatar_file'], $this->settings['avatar-file']);

        // Ensure We Have a GUID
        if ( strlen($CleanGUID) != 36 ) {
            $this->_setMetaMessage("Invalid Persona GUID Supplied", 400);
            return false;
        }

        // Collect the Data
        $ReplStr = array( '[PERSONA_GUID]' => sqlScrub($CleanGUID),
                          '[PERSONA_BIO]'  => sqlScrub($CleanBio),
                          '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[AVATAR_TYPE]'  => sqlScrub($CleanAvatar),
                          '[AVATAR_FILE]'  => sqlScrub($CleanAvatarFile),
                         );
        $sqlStr = prepSQLQuery("CALL SetPublicProfile([ACCOUNT_ID], '[PERSONA_GUID]', '[PERSONA_BIO]', '[AVATAR_TYPE]', '[AVATAR_FILE]');", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                if ( nullInt($Row['persona_id']) > 0 ) {
                    return $this->_getPublicProfile();
                }
            }
        }

        // If We're Here, We Couldn't Update the Public Profile
        $this->_setMetaMessage("Could Not Update Public Profile", 400);
        return false;
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
        $sqlStr = prepSQLQuery("CALL GetPublicProfile([ACCOUNT_ID], '[PERSONA_GUID]');", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $bio_html = NoNull($Row['persona_bio']);
                if ( $bio_html != '' ) {
                    require_once(LIB_DIR . '/posts.php');
                    $post = new Posts($this->settings);
                    $bio_html = $post->getMarkdownHTML($bio_html, 0, true, true);
                    unset($post);
                }

                $recent_at = false;
                if ( strtotime($Row['recent_at']) !== false ) {
                    $recent_at = strtotime($Row['recent_at']);
                }

                return array( 'guid'         => NoNull($Row['persona_guid']),
                              'timezone'     => Nonull($Row['timezone']),
                              'as'           => NoNull($Row['name']),
                              'name'         => NoNull(NoNull($Row['first_name']) . ' ' . NoNull($Row['last_name']), $Row['display_name']),
                              'avatar_url'   => NoNull($Row['avatar_url']),
                              'site_url'     => NoNull($Row['site_url']),
                              'bio'          => array( 'text' => NoNull($Row['persona_bio']),
                                                       'html' => $bio_html,
                                                      ),

                              'pin'          => NoNull($Row['pin_type'], 'pin.none'),
                              'you_follow'   => YNBool($Row['follows']),
                              'is_muted'     => YNBool($Row['is_muted']),
                              'is_starred'   => YNBool($Row['is_starred']),
                              'is_blocked'   => YNBool($Row['is_blocked']),
                              'is_you'       => YNBool($Row['is_you']),

                              'days'         => nullInt($Row['days']),
                              'recent_at'    => (($recent_at !== false) ? date("Y-m-d\TH:i:s\Z", $recent_at) : false),
                              'recent_unix'  => (($recent_at !== false) ? $recent_at : false),

                              'stats'        => array( 'articles'    => nullInt($Row['count_article']),
                                                       'bookmarks'   => nullInt($Row['count_bookmark']),
                                                       'locations'   => nullInt($Row['count_location']),
                                                       'quotations'  => nullInt($Row['count_quotation']),
                                                       'notes'       => nullInt($Row['count_note']),
                                                       'photos'      => nullInt($Row['count_photo']),
                                                      ),

                              'created_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                              'created_unix' => strtotime($Row['created_at']),
                              );
            }
        }

        // If We're Here, There Is No Persona
        return false;
    }

    /**
     *  Function Returns the Public Profile for a Given Persona
     */
    private function _getPersonaProfile() {
        $ScrubTags = array( 'h1>', 'h2>', 'h3>', 'h4>', 'h5>', 'h6>' );
        $CleanVal = '';
        $opts = ['persona_guid', 'guid', 'persona_name', 'persona', 'name', 'for'];
        foreach ( $opts as $opt ) {
            if ( array_key_exists($opt, $this->settings) ) {
                $val = filter_var(NoNull($this->settings[$opt]), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_LOW);
                if ( strpos($val, '@') !== false ) { $val = NoNull(str_replace(array('@'), '', $val)); }
                if ( $CleanVal == '' && strlen($val) > 0 ) { $CleanVal = $val; }
            }
        }

        $ReplStr = array( '[PERSONA]'    => sqlScrub($CleanVal),
                          '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                         );
        $sqlStr = prepSQLQuery("CALL GetPersonaProfile( '[PERSONA]', [ACCOUNT_ID] );", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $SiteUrl = NoNull($this->settings['HomeURL']);
            $avatar = $SiteUrl . '/avatars/default.png';
            $data = false;

            foreach ( $rslt as $Row ) {
                /* Ensure the Active Years are Accurate */
                $years = json_decode($Row['years_active'], true);
                if ( is_array($years) === false ) { $Row['years_active'] = ''; }

                /* Do we have a Biography? */
                $bio_html = NoNull($Row['bio']);
                if ( $bio_html != '' ) {
                    require_once(LIB_DIR . '/posts.php');
                    $post = new Posts($this->settings);
                    $bio_html = $post->getMarkdownHTML($bio_html, 0, true, true);
                    $bio_html = str_replace($ScrubTags, 'p>', $bio_html);
                    unset($post);
                }

                /* Ensure the Dates are Cromulent */
                $recent_at = false;
                $first_at = false;

                if ( strtotime($Row['recent_at']) !== false ) { $recent_at = strtotime($Row['recent_at']); }
                if ( strtotime($Row['first_at']) !== false ) { $first_at = strtotime($Row['first_at']); }

                /* Construct the Output Array */
                $data = array( 'guid'           => NoNull($Row['guid']),
                               'name'           => NoNull($Row['name']),
                               'last_name'      => NoNull($Row['last_name']),
                               'first_name'     => NoNull($Row['first_name']),
                               'display_name'   => NoNull($Row['display_name']),

                               'bio'            => array( 'html' => $bio_html,
                                                          'text' => NoNull($Row['bio']),
                                                         ),

                               'site_url'       => NoNull($Row['site_url']),
                               'avatar_url'     => NoNull($Row['avatar_url'], $avatar),

                               'created_at'     => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                               'created_unix'   => strtotime($Row['created_at']),
                               'first_at'       => (($first_at !== false) ? date("Y-m-d\TH:i:s\Z", $first_at) : false),
                               'first_unix'     => (($first_at !== false) ? $first_at : false),
                               'recent_at'      => (($recent_at !== false) ? date("Y-m-d\TH:i:s\Z", $recent_at) : false),
                               'recent_unix'    => (($recent_at !== false) ? $recent_at : false),

                               'counts'         => array( 'posts'       => nullInt($Row['posts']),
                                                          'notes'       => nullInt($Row['notes']),
                                                          'articles'    => nullInt($Row['articles']),
                                                          'bookmarks'   => nullInt($Row['bookmarks']),
                                                          'locations'   => nullInt($Row['locations']),
                                                          'quotations'  => nullInt($Row['quotations']),
                                                          'photos'      => nullInt($Row['photos']),

                                                          'following'   => nullInt($Row['following']),
                                                          'followers'   => nullInt($Row['followers']),
                                                         ),

                               'relationship'   => array( 'you_follow'  => YNBool($Row['you_follow']),
                                                          'you_muted'   => YNBool($Row['you_muted']),
                                                          'you_blocked' => YNBool($Row['you_blocked']),
                                                          'you_starred' => YNBool($Row['you_starred']),
                                                          'you_pinned'  => YNBool($Row['you_pinned']),
                                                          'follows_you' => YNBool($Row['follows_you']),

                                                          'is_you'      => ((nullInt($Row['account_id']) == nullInt($this->settings['_account_id']) ) ? true : false),
                                                         ),

                               'years_active'   => $years,
                              );
            }

            /* If we have data, let's return it */
            if ( is_array($data) && count($data) > 0 ) { return $data; }
        }

        /* If we're here, then there is no persona record that matches the search criteria */
        return false;
    }

    /**
     *  Function Collects the Usage for the last 53 weeks for use in a GitHub-like History Chart
     */
    private function _getHistoChart() {
        $opts = ['PgRoot', 'PgSub1', 'persona_guid', 'guid'];
        foreach ( $opts as $opt ) {
            $guid = NoNull($this->settings[ $opt ]);
            if ( $CleanGUID == '' && (strlen($guid) == 36 || NoNull($guid) == 'me') ) { $CleanGUID = $guid; }
        }
        if ( $CleanGUID == 'me' && NoNull($this->settings['_persona_guid']) != '' ) {
            $CleanGUID = $this->settings['_persona_guid'];
        }

        $ReplStr = array( '[PERSONA_GUID]' => sqlScrub($CleanGUID) );
        $sqlStr = prepSQLQuery( "CALL GetPublishHistoChart('[PERSONA_GUID]');", $ReplStr );
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $tbls = array();
            $data = array();
            $html = '';

            foreach ( $rslt as $Row ) {
                if ( array_key_exists($Row['dow'], $tbls) === false ) { $tbls[$Row['dow']] = ''; }
                $tbls[$Row['dow']] .= '<td class="hist" data-posts="' . nullInt($Row['posts']) . '" style="opacity: ' . nullInt($Row['opacity']) . '">&nbsp;</td>';

                $data[] = array( 'year'         => nullInt($Row['year']),
                                 'month'        => nullInt($Row['month']),
                                 'dow'          => nullInt($Row['dow']),
                                 'publish_on'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['date'])),
                                 'publish_unix' => strtotime($Row['date']),
                                 'posts'        => nullInt($Row['posts']),
                                );
            }

            foreach ( $tbls as $Row ) {
                $html .= '<tr>' . NoNull($Row) . '</tr>';
            }

            // If there is data, return it
            if ( count($data) > 0 ) {
                return array( 'html'   => $html,
                              'detail' => $data
                             );
            }
        }

        // If we're here, there's nothing
        return array();
    }

    /**
     *  Function Collects the Usage for the last 52 weeks for use in a Histogram
     */
    private function _getHistorgram() {
        $opts = ['PgRoot', 'PgSub1', 'persona_guid', 'guid'];
        foreach ( $opts as $opt ) {
            $guid = NoNull($this->settings[ $opt ]);
            if ( $CleanGUID == '' && (strlen($guid) == 36 || NoNull($guid) == 'me') ) { $CleanGUID = $guid; }
        }
        if ( $CleanGUID == 'me' && NoNull($this->settings['_persona_guid']) != '' ) {
            $CleanGUID = $this->settings['_persona_guid'];
        }

        $ReplStr = array( '[PERSONA_GUID]' => sqlScrub($CleanGUID) );
        $sqlStr = prepSQLQuery( "CALL GetPublishHistogram('[PERSONA_GUID]');", $ReplStr );
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $maxScore = 0;
            $data = array();

            foreach ( $rslt as $Row ) {
                $weekSum = nullInt($Row['articles']) + nullInt($Row['bookmarks']) +
                           nullInt($Row['locations']) + nullInt($Row['notes']) +
                           nullInt($Row['photos']) + nullInt($Row['quotations']);
                if ( $weekSum > $maxScore ) { $maxScore = $weekSum; }
                $data[] = array( 'year'       => nullInt($Row['year']),
                                 'week_no'    => nullInt($Row['week_no']),

                                 'articles'   => nullInt($Row['articles']),
                                 'bookmarks'  => nullInt($Row['bookmarks']),
                                 'locations'  => nullInt($Row['locations']),
                                 'notes'      => nullInt($Row['notes']),
                                 'photos'     => nullInt($Row['photos']),
                                 'quotations' => nullInt($Row['quotations']),

                                 'total'      => $weekSum,
                                );
            }

            // If we have data, return it
            if ( count($data) > 0 ) {
                return array( 'max_score' => $maxScore,
                              'history'   => $data,
                             );
            }
        }

        // If we're here, there is no data
        return false;
    }

    /**
     *  Function Collects a List of Personas that match the filter requirement
     *
     *  Note: This is returned for the current Account, not a Persona
     */
    private function _getRelations( $filter = 'follows' ) {
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']) );
        $sqlStr = readResource(SQL_DIR . '/account/getRelations.sql', $ReplStr );
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $cdnUrl = NoNull($this->settings['HomeURL']);
            $data = array();
            $idx = array();

            foreach ( $rslt as $Row ) {
                $id = nullInt($Row['id']);
                if ( in_array($id, $idx) === false ) {
                    $data[] = array( 'guid'         => NoNull($Row['persona_guid']),
                                     'name'         => '@' . NoNull($Row['name']),
                                     'last_name'    => NoNull($Row['last_name']),
                                     'first_name'   => NoNull($Row['first_name']),
                                     'display_name' => NoNull($Row['display_name']),
                                     'avatar_url'   => $cdnUrl . '/avatars/' . NoNull($Row['avatar_img']),
                                     'relations'    => array(),
                                    );
                    $idx[] = $id;
                }

                /* If the Record Matches the Filter, Add it */
                if ( YNBool($Row[ $filter ]) ) {
                    $cdx = array_search($id, $idx);
                    $data[$cdx]['relations'][] = array( 'guid'         => NoNull($Row['rel_guid']),
                                                        'name'         => '@' . NoNull($Row['rel_name']),
                                                        'last_name'    => NoNull($Row['rel_last_name']),
                                                        'first_name'   => NoNull($Row['rel_first_name']),
                                                        'display_name' => NoNull($Row['rel_display_name']),
                                                        'avatar_url'   => $cdnUrl . '/avatars/' . NoNull($Row['rel_avatar_img']),

                                                        'following'    => YNBool($Row['follows']),
                                                        'follows_you'  => YNBool($Row['follows_you']),
                                                        'is_muted'     => YNBool($Row['is_muted']),
                                                        'is_blocked'   => YNBool($Row['is_blocked']),
                                                        'is_starred'   => YNBool($Row['is_starred']),
                                                        'pin'          => NoNull($Row['pin_type'], 'pin.none'),

                                                        'last_at'      => ((NoNull($Row['last_at']) != '') ? date("Y-m-d\TH:i:s\Z", strtotime($Row['last_at'])) : false),
                                                        'last_unix'    => ((NoNull($Row['last_at']) != '') ? strtotime($Row['last_at']) : false),
                                                       );
                }
            }

            // If we have a head record, send it back
            if ( count($data) > 0 ) { return $data; }
        }

        // If we're here, the Account follows no accounts
        return array();
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

    /** ********************************************************************* *
     *  Preferences
     ** ********************************************************************* */
    /**
     *  Function Sets a Person's Preference and Returns a Preference Object
     */
    private function _setPreference() {
        $CleanValue = NoNull($this->settings['value']);
        $CleanType = NoNull($this->settings['type'], $this->settings['key']);

        if ( $CleanValue == '' ) {
            $this->_setMetaMessage("Invalid Value Passed", 400);
            return false;
        }
        if ( $CleanType == '' ) {
            $this->_setMetaMessage("Invalid Type Key Passed", 400);
            return false;
        }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[VALUE]'      => sqlScrub($CleanValue),
                          '[TYPE_KEY]'   => strtolower(sqlScrub($CleanType)),
                         );
        $sqlStr = readResource(SQL_DIR . '/account/setPreference.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);
        if ( $rslt ) { return $this->_getPreference(); }

        // Return the Preference Object or an Unhappy String
        $this->_setMetaMessage("Could Not Record Account Preference", 400);
        return false;
    }

    private function _getPreference( $type = '' ) {
        $CleanType = NoNull($type, NoNull($this->settings['type'], $this->settings['key']));
        if ( $CleanType == '' ) {
            $this->_setMetaMessage("Invalid Type Key Passed", 400);
            return false;
        }

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
            if ( count($data) > 0 ) { return (count($data) == 1) ? $data[0] : $data; }
        }

        // Return the Preference Object or an empty array
        return array();
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