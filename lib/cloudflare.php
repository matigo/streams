<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to manage Cloudflare operations
 */
require_once( LIB_DIR . '/functions.php');

class Cloudflare {
    var $settings;
    var $strings;

    function __construct( $settings, $strings = false ) {
        if ( is_array($settings) && count($settings) > 0 ) { $this->settings = $settings; }
        if ( is_array($strings) && count($strings) > 0 ) { $this->strings = $strings; }
        if ( is_array($this->strings) === false ) {
            $this->strings = getLangDefaults($this->settings['_language_code']);
        }

        /* Ensure the constants are in place */
        if ( defined('CLOUDFLARE_SITEDNSTYPE') === false ) { define('CLOUDFLARE_SITEDNSTYPE', ''); }
        if ( defined('CLOUDFLARE_SITEDNSVAL') === false ) { define('CLOUDFLARE_SITEDNSVAL', ''); }
        if ( defined('CLOUDFLARE_API_KEY') === false ) { define('CLOUDFLARE_API_KEY', ''); }
        if ( defined('CLOUDFLARE_ACCOUNT') === false ) { define('CLOUDFLARE_ACCOUNT', ''); }
        if ( defined('CLOUDFLARE_API_URL') === false ) { define('CLOUDFLARE_API_URL', ''); }
        if ( defined('CLOUDFLARE_EMAIL') === false ) { define('CLOUDFLARE_EMAIL', ''); }
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    public function performAction() {
        $ReqType = NoNull(strtolower($this->settings['ReqType']));

        /* Check the User Token is Valid */
        if ( YNBool($this->settings['_logged_in']) === false ) { return $this->_setMetaMessage("You Need to Log In First", 401); }
        if ( in_array( NoNull($this->settings['_account_type']), array('account.admin', 'account.system')) === false ) {
            return $this->_setMetaMessage("You do not have adequate permission to access this API endpoint", 403);
        }

        /* Ensure the minimum configuration settings are in place */
        if ( $this->_validateRequirements() === false ) { return false; }

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

        /* If we're here, nothing was done */
        return $this->_setMetaMessage("Unrecognised request type", 400);
    }

    private function _performGetAction() {
        $Activity = NoNull(strtolower($this->settings['PgSub1']));

        switch ( $Activity ) {
            case 'verify':
            case 'check':
                return $this->_verifyDNS();
                break;

            case 'test':
                return $this->_testClass();
                break;

            default:
                // Do Nothing
        }

        /* If we're here, nothing was done */
        return $this->_setMetaMessage("There is nothing to do", 400);
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));

        switch ( $Activity ) {
            default:
                // Do Nothing
        }

        /* If we're here, nothing was done */
        return $this->_setMetaMessage("There is nothing to do", 400);
    }

    private function _performDeleteAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        if ( nullInt($this->settings['PgSub1']) > 0 ) { $Activity = 'scrub'; }

        switch ( $Activity ) {
            default:
                // Do Nothing
        }

        /* If we're here, nothing was done */
        return $this->_setMetaMessage("There is nothing to do", 400);
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
     *  Endpoint Functions
     ** ********************************************************************* */
    /**
     *  Function validates the DNS records with the Public IP address of the server
     */
    private function _verifyDNS() {
        $ipv6 = strtolower(NoNull(file_get_contents('https://api64.ipify.org/?format=text')));
        $ipv4 = strtolower(NoNull(file_get_contents('https://api.ipify.org/?format=text')));
        $data = false;

        /* Validate the Data */
        if ( filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false ) { $ipv4 = ''; }
        if ( filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false ) { $ipv6 = ''; }
        if ( mb_strlen($ipv4) < 7 && mb_strlen($ipv6) < 9 ) { return $this->_setMetaMessage("Could not determine any Public IP address", 400); }

        /* Check the IPs against the existing records */
        $sqlStr = readResource(SQL_DIR . '/system/getRecentServerIPs.sql');
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            if ( mb_strlen($ipv4) > 0 ) {
                if ( is_array($data) === false ) { $data = array(); }
                $data['ipv4'] = array( 'address' => $ipv4,
                                       'matched' => false,
                                       'updated' => false,
                                      );
            }

            if ( mb_strlen($ipv6) > 0 ) {
                if ( is_array($data) === false ) { $data = array(); }
                $data['ipv6'] = array( 'address' => $ipv6,
                                       'matched' => false,
                                       'updated' => false,
                                      );
            }

            foreach ( $rslt as $Row ) {
                $Row['ipv4'] = strtolower(NoNull($Row['ipv4']));
                $Row['ipv6'] = strtolower(NoNull($Row['ipv6']));
                if ( filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false ) { $Row['ipv4'] = ''; }
                if ( filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false ) { $Row['ipv6'] = ''; }

                /* If IPv4 is valid and different, let's update the record */
                if ( mb_strlen(NoNull($ipv4)) >= 7  ) {
                    $data['ipv4']['matched'] = ($Row['ipv4'] == $ipv4);

                    if ( $Row['ipv4'] != $ipv4 ) {
                        $sOK = $this->_setIP('A', $ipv4);
                        $data['ipv4']['updated'] = $sOK;

                        if ( $sOK === false ) { writeNote("_verifyDNS() -> Could not update A Record to $ipv4", true); }
                    }
                }

                /* If IPv6 is valid and different, let's update the record */
                if ( mb_strlen(NoNull($ipv6)) >= 7  ) {
                    $data['ipv6']['matched'] = ($Row['ipv6'] == $ipv6);

                    if ( $Row['ipv6'] != $ipv6 ) {
                        $sOK = $this->_setIP('AAAA', $ipv6);
                        $data['ipv6']['updated'] = $sOK;

                        if ( $sOK === false ) { writeNote("_verifyDNS() -> Could not update AAAA Record to $ipv6", true); }
                    }
                }

                /* Record any update that might exist to the database */
                $ReplStr = array( '[IPV4_ADDR]' => sqlScrub($ipv4),
                                  '[IPV6_ADDR]' => sqlScrub($ipv6),
                                 );
                $sqlStr = readResource(SQL_DIR . '/system/setServerIPs.sql', $ReplStr);
                $sOK = doSQLExecute($sqlStr);
            }
        }

        /* Return the array of what's been done */
        return $data;
    }

    /**
     *  Function validates the defined authentication token with the Cloudflare service
     */
    private function _testClass() {
        if ( mb_strlen(CLOUDFLARE_ACCOUNT) <= 20 ) { return $this->_setMetaMessage("Invalid Cloudflare Account ID defined", 400); }
        $endpoint = '/accounts/' . CLOUDFLARE_ACCOUNT . '/tokens/verify';

        /* Test the authentication token with Cloudflare */
        $data = $this->_doCloudFlareRequest($endpoint);
        if ( is_array($data) ) {
            if ( array_key_exists('status', $data) ) {
                if ( strtolower(NoNull($data['status'])) == 'active' ) { return $data; }
            }
        }

        /* If We're Here, Nothing Was Done */
        return $this->_setMetaMessage("Could not verify Cloudflare authentication token", 400);
    }

    /** ********************************************************************* *
     *  Cloudflare Functions
     ** ********************************************************************* */
    /**
     *  Function updates a Zone's IPv4 value if different from what is provided
     */
    function _setIP( $record, $ip ) {
        $record = strtoupper($record);
        $zoneid = $this->_getZoneId();
        $ip = strtolower($ip);

        switch ( $record ) {
            case 'AAAA':
                if ( filter_var(NoNull($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false ) { return false; }
                break;

            case 'A':
                if ( filter_var(NoNull($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false ) { return false; }
                break;

            default:
                return false;
        }
        if ( mb_strlen(NoNull($zoneid)) < 32 ) { return false; }

        /* Collect the Zone-specific DNS records */
        $endpoint = '/zones/' . $zoneid . '/dns_records';
        $params = array( 'type'     => $record,
                         'per_page' => 100
                        );
        $data = $this->_doCloudFlareRequest($endpoint, 'GET', $params);
        if ( is_array($data) ) {
            foreach ( $data as $Row ) {
                if( strtolower($Row['name']) == strtolower(CLOUDFLARE_SITEDNSVAL) && NoNull($Row['type']) == $record ) {
                    $comm = 'Automated Update on ' . date('Y-m-d H:i:s');
                    $id = NoNull($Row['id']);

                    /* So long as the IP address is not different, let's update it */
                    if ( strtolower(NoNull($Row['content'])) != $ip && mb_strlen($id) >= 32 ) {
                        $uppoint = $endpoint . '/' . $id;
                        $updata = array( 'type'    => NoNull($Row['type']),
                                         'name'    => NoNull($Row['name']),
                                         'content' => NoNull($ip),
                                         'comment' => NoNull($comm),
                                         'ttl'     => nullInt($Row['ttl']),
                                         'proxied' => YNBool(BoolYN($Row['proxied'])),
                                      );
                        $rslt = $this->_doCloudFlareRequest($uppoint, 'PUT', $updata);
                        if ( is_array($rslt) ) {
                            if ( strtolower(NoNull($Row['content'])) == $ip ) { return true; }
                        }
                    }
                }
            }
        }

        /* If we're here, the record could not be updated */
        return false;
    }

    /**
     *  Function returns the Cloudflare Zone ID associated with the current account
     */
    function _getZoneId() {
        $CacheKey = 'cf-zone';

        $zoneid = getGlobalObject($CacheKey);
        if ( mb_strlen(NoNull($zoneid)) < 32 ) {
            $params = array( 'per_page' => 100 );
            $rslt = $this->_doCloudFlareRequest('/zones', 'GET', $params);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    if ( mb_strlen(NoNull($Row['id'])) >= 32 ) { $zoneid = NoNull($Row['id']); }
                }
            }

            /* Save the Zone.id so that we do not need to query more often than necessary */
            if ( is_string($zoneid) && mb_strlen(NoNull($zoneid)) >= 32 ) { setGlobalObject($cacheKey, $zoneid); }
        }

        /* If we have an ID, let's return it */
        if ( mb_strlen(NoNull($zoneid)) >= 32 ) { return $zoneid; }
        return false;
    }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function confirms that the basic requirements are in place to connect and use the Cloudflare API
     */
    function _validateRequirements() {
        if ( mb_strlen(CLOUDFLARE_API_URL) <= 6 ) { return $this->_setMetaMessage("Invalid Cloudflare API URL defined", 400); }
        if ( mb_strlen(CLOUDFLARE_API_KEY) <= 6 ) { return $this->_setMetaMessage("Invalid Cloudflare API Key defined", 400); }
        if ( mb_strlen(CLOUDFLARE_ACCOUNT) <= 6 ) { return $this->_setMetaMessage("Invalid Cloudflare Account ID defined", 400); }
        if ( mb_strlen(CLOUDFLARE_EMAIL) <= 6 ) { return $this->_setMetaMessage("Invalid Cloudflare Account Email defined", 400); }

        /* If we're here, we have the minimum requirements */
        return true;
    }

    /**
     *  Function performs an HTTP request against the Cloudflare API
     */
    function _doCloudFlareRequest( $endpoint, $reqtype = "GET", $vars = false ) {
        if ( mb_strlen(NoNull($endpoint)) <= 3 ) { return false; }
        $reqtype = strtoupper($reqtype);
        $url = CLOUDFLARE_API_URL . $endpoint;

        /* If We Have a GET Request and Variables, Build the HTTP Query */
        if ( $reqtype == "GET" && is_array($vars) && count($vars) > 0 ) { $url .= '?' . http_build_query( $vars ); }

        $CurlHead = array( 'Content-Type: application/json',
                           'Authorization: Bearer ' . CLOUDFLARE_API_KEY,
                          );

        /* Perform the CURL */
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ( in_array($reqtype, array('PATCH', 'POST', 'PUT')) ) {
            curl_setopt($ch, CURLOPT_HEADER, false);
            switch ( $reqtype ) {
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, true);
                    break;

                case 'PATCH':
                case 'PUT':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($reqtype));
                    break;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($vars, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $CurlHead);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error ($ch);
        $result = curl_exec($ch);
        curl_close($ch);

        /* Return the Data In the Proper Format If We Have It */
        $json = json_decode($result);
        if ( is_object($json) ) {
            $data = objectToArray($json);

            if ( is_array($data) && count($data) > 0 ) {
                if ( array_key_exists('errors', $data) ) {
                    if ( is_array($data['errors']) && count($data['errors']) > 0 ) {
                        foreach ( $data['errors'] as $err ) {
                            writeNote("CloudFlare Error [" . nullInt($err['code']) . "] - " . NoNull($err['message']), true);
                            writeNote("For: [" . strtoupper($reqtype) . "] $endpoint", true);
                        }
                    }
                }
                if ( array_key_exists('result', $data) ) { return $data['result']; }
            }
        }

        /* If We're Here, There's a Problem */
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
        return false;
    }
}
?>