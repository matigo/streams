<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for Locker Functions
 */
require_once(LIB_DIR . '/functions.php');

class Locker {
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

        switch ( $Activity ) {
            case 'decode':
            case '':
                $rVal = $this->_getLockerItem();
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
            case 'create':
                $rVal = $this->_createLockerItem();
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
    /**
     *  Function Encrypts a String with a given Password and returns a URL.
     */
    private function _createLockerItem() {
        $SiteURL = str_replace(array('https://', 'http://'), '', $this->settings['HomeURL']);
        $CleanText = NoNull($this->settings['content'], $this->settings['text']);
        $Password = NoNull($this->settings['password'], $this->settings['pass']);
        $ExpyMins = nullInt($this->settings['expire_mins']);
        $Cipher = "AES-128-CBC";

        // Perform Some Sanity Checks
        if ( $CleanText == '' ) {
            $this->_setMetaMessage("No Text Provided to Encrypt.", 400);
            return false;
        }
        if ( strlen($Password) <= 5 ) {
            $this->_setMetaMessage("Invalid Password Provided", 400);
            return false;
        }
        if ( $ExpyMins < 0 ) { $ExpyMins = 0; }

        // Encrypt the Data
        $opts = array( 'cost' => 12,
                       'salt' => random_bytes(22),
                      );
        $Hash = password_hash($Password, PASSWORD_BCRYPT, $opts);
        $Enc = false;
        $iv = false;

        if ( in_array($Cipher, openssl_get_cipher_methods()) ) {
            $ivlen = openssl_cipher_iv_length($Cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $ciphertext_raw = openssl_encrypt($CleanText, $Cipher, $Hash, OPENSSL_RAW_DATA, $iv);
            $hmac = hash_hmac('sha256', $ciphertext_raw, $Hash, $as_binary=true);
            $Enc = base64_encode( $iv.$hmac.$ciphertext_raw );
        }

        // Prepare the SQL Insert
        $ReplStr = array( '[CONTENT]'   => sqlScrub($Enc),
                          '[CIPHER]'    => sqlScrub($Cipher),
                          '[IV]'        => sqlScrub($this->_strtohex($iv)),
                          '[PASSHASH]'  => sqlScrub($Hash),
                          '[EXPY_MINS]' => $ExpyMins,
                          '[SITE_URL]'  => sqlScrub($SiteURL),
                         );
        $sqlStr = readResource(SQL_DIR . '/locker/createLockerItem.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);
        if ( $rslt > 0 ) {
            $ReplStr['[POST_ID]'] = $rslt;

            // Write the Meta Data
            $sqlStr = readResource(SQL_DIR . '/locker/setLockerMeta.sql', $ReplStr);
            $isOK = doSQLExecute($sqlStr);

            // If Everything Is Good, Return a Basic Set of Data
            if ( $isOK ) {
                $data = $this->_getLockerItemSummary($rslt);
                if ( is_array($data) ) { return $data; }
            }
        }

        // If We're Here, There's Nothing Valid to Return
        return false;
    }

    private function _getLockerItem() {
        $SiteURL = str_replace(array('https://', 'http://'), '', $this->settings['HomeURL']);
        $CleanGUID = NoNull($this->settings['guid'], $this->settings['PgSub1']);
        $Password = NoNull($this->settings['password'], $this->settings['pass']);

        // Perform a Sanity Check
        if ( strlen($CleanGUID) != 36 ) {
            $this->_setMetaMessage("Invalid Locker ID Provided", 400);
            return false;
        }
        if ( strlen($Password) <= 5 ) {
            $this->_setMetaMessage("Invalid Password Provided", 400);
            return false;
        }

        // Collect the Data from the Database
        $ReplStr = array( '[RECORD_GUID]' => sqlScrub($CleanGUID),
                          '[SITE_URL]'    => sqlScrub($SiteURL),
                         );
        $sqlStr = readResource(SQL_DIR . '/locker/getLockerItem.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                if ( password_verify($Password, $Row['passhash']) ) {
                    $Cipher = NoNull($Row['cipher'], "AES-128-CBC");
                    $Hash = NoNull($Row['passhash']);
                    $text = base64_decode(NoNull($Row['value']));

                    $ivlen = openssl_cipher_iv_length($Cipher);
                    $iv = substr($text, 0, $ivlen);
    
                    $hmac = substr($text, $ivlen, $sha2len=32);
                    $ciphertext_raw = substr($text, $ivlen+$sha2len);
                    $out = openssl_decrypt($ciphertext_raw, $Cipher, $Hash, OPENSSL_RAW_DATA, $iv);

                    // If We Have a Valid Value, Return It
                    if ( $out !== false ) {
                        if ( NoNull($out) != '' ) { return array( 'decoded' => NoNull($out) ); }
                    }
                }

                // If We're Here, the Password was Bad
                $this->_setMetaMessage("Invalid Password", 400);
                return false;
            }
        }

        // If We're Here, There's Nothing
        $this->_setMetaMessage("No Locker Item Found", 400);
        return false;
    }
    
    /**
     *  Function Returns an Array containing a Basic Summary of a Locker Item
     */
    private function _getLockerItemSummary( $lock_id ) {
        $SiteURL = str_replace(array('https://', 'http://'), '', $this->settings['HomeURL']);
        $CleanGUID = NoNull($this->settings['PgSub1'], $this->settings['PgRoot']);

        // Query the Database
        $ReplStr = array( '[SITE_URL]' => sqlScrub($SiteURL),
                          '[ITEMGUID]' => sqlScrub($CleanGUID),
                          '[ITEM_IDX]' => nullInt($lock_id),
                         );
        $sqlStr = readResource(SQL_DIR . '/locker/getLockerItemSummary.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = false;
            foreach ( $rslt as $Row ) {
                $expy_at = false;
                if ( NoNull($Row['expires_at']) != '' ) { $expy_at = strtotime($Row['expires_at']); }

                $data = array( 'id'           => intToAlpha($Row['locker_id']),
                               'guid'         => NoNull($Row['guid']),
                               'publish_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['publish_at'])),
                               'publish_unix' => strtotime($Row['publish_at']),
                               'expires_at'   => (($expy_at !== false) ? date("Y-m-d\TH:i:s\Z", $expy_at) : false),
                               'expires_unix' => (($expy_at !== false) ? $expy_at : false),
                              );
            }

            // If We Have Data, Return It
            if ( is_array($data) ) { return $data; }
        }

        // If We're Here, There's Nothing
        return false;
    }

    /**
     *  Function Decrypts a Locker Item and Returns the Object or an Unhappy Boolean
     */
    private function _openLockerItem() {
        $CleanAlpha = NoNull($this->settings['PgSub1'], $this->settings['PgRoot']);
        $idx = alphaToInt($CleanAlpha);
        if ( $idx > 0 ) {
            
        }

        // If We're Here, There's Nothing Valid to Return
        return false;
    }

    /**
     *  Function Converts a String to a Hexidecial String
     */    
    private function _strtohex( $x ) {
        $rVal = '';

        foreach (str_split($x) as $c) {
            $rVal .= sprintf("%02X", ord($c));
        }

        return($rVal);
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