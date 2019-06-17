<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for Webhook Handling
 */
require_once(CONF_DIR . '/versions.php');
require_once(CONF_DIR . '/config.php');
require_once( LIB_DIR . '/functions.php');
require_once( LIB_DIR . '/cookies.php');
require_once( LIB_DIR . '/site.php');

class Route extends Streams {
    var $settings;
    var $strings;
    var $site;

    function __construct( $settings, $strings ) {
        $this->settings = $settings;
        $this->strings = $strings;

        $this->site = new Site($this->settings);
    }

    /** ********************************************************************** *
     *  Public Functions
     ** ********************************************************************** */

    /**
     * Function performs the requested Method Activity and Returns the Results
     *      in an array.
     */
    public function getResponseData() {
        $Action = strtolower(NoNull($this->settings['PgRoot']));
        $data = false;

        // Let's use some Debug to Understand the Webhooks Coming In
        writeNote("WebHook Initiated!", true);
        foreach ( $this->settings as $Key=>$Value ) {
            writeNote("[$Key] => $Value", true);
        }

        // Determine How to Proceed
        switch ( $Action ) {
            case 'paypal':
                $data = $this->_processPayPalTransaction();
                break;

            default:
                /* Do Nothing */
        }

        // Return the Array
        return $this->_returnData($data);
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

    /**
     *  Function Returns the "HasMore" Meta Value if it Exists
     */
    public function getHasMore() {
        if ( array_key_exists('more', $this->settings) ) {
            return BoolYN(YNBool($this->settings['more']));
        } else {
            return false;
        }
    }

    /** ********************************************************************** *
     *  Paypal Functions
     ** ********************************************************************** */
    private function _processPayPalTransaction() {
        $basics = array( 'transaction_subject', 'txn_type', 'payment_type', 'txn_id', 'payer_id',
                         'subscr_id', 'last_name', 'first_name', 'payer_email', 'payer_status',
                         'residence_country', 'item_name', 'payment_gross', 'mc_currency',
                         'ipn_track_id' );
        $cnt = 0;

        /* Check to see that we have enough data */
        foreach ( $basics as $key ) {
            if ( NoNull($this->settings[$key]) == '' ) { $cnt++; }
        }
        if ( $cnt > 4 ) {
            $this->_setMetaMessage( "Incomplete Hook Received", 400 );
            return false;
        }

        // Get the Site Data and Transaction Date/Time
        $siteData = $this->site->getSiteData();
        $PayDate = date("Y-m-d H:i:s");

        // Record the Transaction to the PayPal Table
        if ( array_key_exists('payment_date', $this->settings) ) {
            $PayDate = date("Y-m-d H:i:s", strtotime($this->settings['payment_date']));
        }

        $ReplStr = array( '[SUBJECT]'       => sqlScrub(NoNull($this->settings['transaction_subject'])),
                          '[PAYDATE]'       => sqlScrub($PayDate),
                          '[TXNTYPE]'       => sqlScrub(NoNull($this->settings['txn_type'])),
                          '[PAYSTATUS]'     => sqlScrub(NoNull($this->settings['payment_status'])),
                          '[TRACK_ID]'      => sqlScrub(NoNull($this->settings['ipn_track_id'])),
                          '[SUBSCRID]'      => sqlScrub(NoNull($this->settings['subscr_id'])),
                          '[FIRSTNAME]'     => sqlScrub(NoNull($this->settings['first_name'])),
                          '[LASTNAME]'      => sqlScrub(NoNull($this->settings['last_name'])),
                          '[PAYER_ID]'      => sqlScrub(NoNull($this->settings['payer_id'])),
                          '[PAYER_MAIL]'    => sqlScrub(NoNull($this->settings['payer_email'])),
                          '[PAYER_STATUS]'  => sqlScrub(NoNull($this->settings['payer_status'])),
                          '[PAYER_COUNTRY]' => sqlScrub(NoNull($this->settings['residence_country'])),
                          '[VERIFYSIGN]'    => sqlScrub(NoNull($this->settings['verify_sign'])),
                          '[TXN_ID]'        => sqlScrub(NoNull($this->settings['txn_id'])),
                          '[PAYGROSS]'      => nullInt($this->settings['payment_gross'], $this->settings['amount3']),
                          '[PAYFEE]'        => nullInt($this->settings['payment_fee']),
                          '[MC_FEE]'        => nullInt($this->settings['mc_fee']),
                          '[MC_GROSS]'      => nullInt($this->settings['mc_gross']),
                          '[RECURRING]'     => BoolYN(YNBool($this->settings['recurring'])),
                          '[SITE_ID]'       => nullInt($siteData['site_id']),
                         );
        $sqlStr = prepSQLQuery("CALL SetPayPalTXN('[SUBJECT]', '[PAYDATE]', '[TXNTYPE]', '[PAYSTATUS]', '[TRACK_ID]', " .
                                                 "'[SUBSCRID]', '[FIRSTNAME]', '[LASTNAME]', '[PAYER_ID]', '[PAYER_MAIL]', " .
                                                 "'[PAYER_STATUS]', '[PAYER_COUNTRY]', '[VERIFYSIGN]', '[TXN_ID]', " .
                                                 " [PAYGROSS], [PAYFEE], [MC_FEE], [MC_GROSS], '[RECURRING]', [SITE_ID]);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                return array( 'transaction.id'   => nullInt($Row['txn_id']),
                              'transaction.guid' => NoNull($Row['guid']),
                             );
            }
        }

        // If We're Here, Nothing Was Done
        return false;
    }


    /** ********************************************************************** *
     *  Class Functions
     ** ********************************************************************** */
    /**
     *  Function Sets a Message in the Meta Field
     */
    private function _setMetaMessage( $msg, $code = 0 ) {
        if ( is_array($this->settings['errors']) === false ) { $this->settings['errors'] = array(); }
        if ( NoNull($msg) != '' ) { $this->settings['errors'][] = NoNull($msg); }
        if ( $code > 0 && nullInt($this->settings['status']) == 0 ) { $this->settings['status'] = nullInt($code); }
    }

    /**
     *  Function Returns a Formatted Array
     */
    private function _returnData( $data ) {
        // If the Data Is Not An Array, Handle The Issue
        if ( $data === false ) { $this->_setMetaMessage( "Invalid Webhook Action", 400 ); }

        // Return the Data (or an Unhappy Boolean)
        return $data;
    }
}
?>