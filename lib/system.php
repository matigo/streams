<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for System Operations
 */
require_once( LIB_DIR . '/functions.php');

class System {
    var $settings;

    function __construct( $Items ) {
        $this->settings = $Items;
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
            case 'dbcheck':
            case 'dbhash':
                $rVal = $this->_checkDBVersion();
                break;

            case 'verifyips':
                $rVal = $this->_verifyServerIPs();
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
            case '':
                $rVal = array( 'activity' => "[POST] /system/$Activity" );
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
                $rVal = array( 'activity' => "[DELETE] /system/$Activity" );
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
     *  Function Collects the Server Hash and Reports Whether It's Valid or Not
     */
    private function _checkDBVersion() {
        if ( $this->settings['_account_id'] != 1 ) { return "Only System Account Can Access This API Endpoint."; }

        $dbNames = array(NoNull(DB_WRITE_NAME));
        if ( NoNull(DB_WRITE_NAME) != NoNull(DB_READ_NAME) ) { $dbNames[] = NoNull(DB_READ_NAME); }
        $data = false;

        foreach ( $dbNames as $Key=>$dbName ) {
            $ReplStr = array( '[DB_NAME]' => sqlScrub($dbName) );
            $sqlStr = readResource(SQL_DIR . '/system/chkDBVersion.sql', $ReplStr);

            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                if ( $data === false ) { $data = array(); }
                foreach ( $rslt as $Row ) {
                    $full_hash = NoNull(nullInt($Row['tables']) . '.' . nullInt($Row['columns']) . '|' . NoNull($Row['hash']));
                    $data[] = array( 'db_name'  => NoNull($dbName),
                                     'db_size'  => NoNull(nullInt($Row['tables']) . '.' . nullInt($Row['columns'])),
                                     'db_hash'  => NoNull($Row['hash']),
                                     'is_valid' => (( NoNull($full_hash) == NoNull(SQL_HASH) ) ? true : false),
                                    );
                }
            }
        }

        // Return the Array or an Unhappy Boolean
        return ( is_array($data) ) ? $data : false;
    }

    /**
     *  Function Checks If There Are Updates to Perform
     */
    private function _checkForUpdates() {

    }

    /**
     *  Function Checks the Server's IP Addresses and Updates CloudFlare DNS Settings Where Appropriate
     */
    private function _verifyServerIPs() {
        if ( defined('CLOUDFLARE_API_KEY') === false ) { return false; }
        if ( strlen(CLOUDFLARE_API_KEY) <= 35 ) { return false; }

        // Collect the IP Addresses
        $ipv4 = $this->_queryURL('https://ipv4bot.whatismyipaddress.com');
        $ipv6 = $this->_queryURL('https://ipv6bot.whatismyipaddress.com');
        $has_change = false;

        // Validate the Data
        if ( filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false ) { $ipv4 = ''; }
        if ( filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false ) { $ipv6 = ''; }

        // Compare the Values to the Previous IP Addresses
        $sqlStr = readResource(SQL_DIR . '/system/getRecentServerIPs.sql');
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                // This Next Bit of Silliness Seems to Happen when One of the IP Requests Is Returned Empty
                if ( NoNull($ipv4) == '' ) { $ipv4 = NoNull($Row['ipv4']); }
                if ( NoNull($ipv6) == '' ) { $ipv6 = NoNull($Row['ipv6']); }

                // Now Do a Comparison
                if ( NoNull($Row['ipv4']) != $ipv4 ) { $has_change = true; }
                if ( NoNull($Row['ipv6']) != $ipv6 ) { $has_change = true; }
            }

            // If There Are No Records, We Have Changes
            if ( count($rslt) <= 0 ) { $has_change = true; }
        }

        // If There's a Change, Record the Change and Start the DNS Update Process
        if ( $has_change ) {
            // Update the CloudFlare Data
            $data = $this->_updateCloudFlareDNS(CLOUDFLARE_API_KEY, 'jason@j2fi.net', $ipv4, $ipv6);

            // Update the ServerIP Values
            $ReplStr = array( '[IPV4_ADDR]' => sqlScrub($ipv4),
                              '[IPV6_ADDR]' => sqlScrub($ipv6),
                             );
            $sqlStr = readResource(SQL_DIR . '/system/setServerIPs.sql', $ReplStr);
            $isOK = doSQLExecute($sqlStr);

            // If We Have Data, Return It
            if ( is_array($data) && count($data) > 0 ) {
                return array( 'IPv4'    => $ipv4,
                              'IPv6'    => $ipv6,
                              'changes' => $data
                             );
            }
        }

        // If We're Here, There's Nothing to Do
        return array( 'IPv4' => $ipv4,
                      'IPv6' => $ipv6
                     );
    }

    /**
     *  Update the CloudFlare DNS Records based on the Global Key provided
     */
    private function _updateCloudFlareDNS( $GlobalKey, $cfMail, $ipv4, $ipv6 ) {
        if ( strlen(NoNull($GlobalKey)) <= 35 ) { return false; }
        if ( strlen(NoNull($cfMail)) <= 5 ) { return false; }
        $result = array();
        $names = array();
        $ips = array();

        // Collect the Entire IP History List
        $sqlStr = readResource(SQL_DIR . '/system/getCompleteServerIPs.sql');
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                if ( NoNull($Row['ipv4']) != '' ) { $ips[] = NoNull($Row['ipv4']); }
                if ( NoNull($Row['ipv6']) != '' ) { $ips[] = NoNull($Row['ipv6']); }
            }
        }

        // Query the CloudFlare API for the Zone List
        $params = array( 'type'     => 'A,AAAA',
                         'per_page' => 100,
                        );
        $data = doCloudFlareRequest('/zones', $GlobalKey, $cfMail, 'GET', $params);
        if ( is_array($data) ) {
            foreach ( $data as $Row ) {
                if ( NoNull($Row['status']) == 'active' ) {
                    // Collect the DNS Records for the Zone
                    $endpoint = '/zones/' . NoNull($Row['id']) . '/dns_records';
                    $zone = doCloudFlareRequest($endpoint, $GlobalKey, $cfMail, 'GET', $params);
                    if ( is_array($zone) ) {
                        // First collect a list of domain names where a match is found
                        foreach ( $zone as $zRow ) {
                            $ip = NoNull($zRow['content']);
                            if ( in_array($ip, $ips) ) {
                                if ( in_array(NoNull($zRow['name']), $names) === false ) { $names[] = NoNull($zRow['name']); }
                            }
                        }

                        // Run through the Names and update the DNS records
                        foreach ( $names as $name ) {
                            // Set the IPv4 Record If One Exists
                            if ( NoNull($ipv4) != '' ) {
                                foreach ( $zone as $zRow ) {
                                    if ( NoNull($zRow['name']) == $name && NoNull($zRow['type']) == 'A' && $zRow['locked'] === false ) {
                                        $ip = NoNull($zRow['content']);
                                        if ( $ip != $ipv4 ) {
                                            $putat = $endpoint . '/' . NoNull($zRow['id']);
                                            $pobj = array( 'type'    => NoNull($zRow['type']),
                                                           'name'    => NoNull($zRow['name']),
                                                           'content' => NoNull($ipv4),
                                                           'ttl'     => nullInt($zRow['ttl']),
                                                           'proxied' => YNBool(BoolYN($zRow['proxied'])),
                                                          );
                                            $pOK = doCloudFlareRequest($putat, $GlobalKey, $cfMail, 'PUT', $pobj);
                                            if ( is_array($pOK) ) {
                                                $result[] = array( 'name'    => NoNull($pOK['name']),
                                                                   'type'    => NoNull($pOK['type']),
                                                                   'value'   => NoNull($pOK['content']),
                                                                   'success' => true
                                                                  );
                                            } else {
                                                $result[] = array( 'name'    => NoNull($zRow['name']),
                                                                   'type'    => NoNull($zRow['type']),
                                                                   'success' => false
                                                                  );
                                            }
                                        }
                                    }
                                }
                            }

                            // Set the IPv6 Record If One Exists
                            if ( NoNull($ipv6) != '' ) {
                                foreach ( $zone as $zRow ) {
                                    if ( NoNull($zRow['name']) == $name && NoNull($zRow['type']) == 'AAAA' && $zRow['locked'] === false ) {
                                        $ip = NoNull($zRow['content']);
                                        if ( $ip != $ipv6 ) {
                                            $putat = $endpoint . '/' . NoNull($zRow['id']);
                                            $pobj = array( 'type'    => NoNull($zRow['type']),
                                                           'name'    => NoNull($zRow['name']),
                                                           'content' => NoNull($ipv6),
                                                           'ttl'     => nullInt($zRow['ttl']),
                                                           'proxied' => YNBool(BoolYN($zRow['proxied'])),
                                                          );
                                            $pOK = doCloudFlareRequest($putat, $GlobalKey, $cfMail, 'PUT', $pobj);
                                            if ( is_array($pOK) ) {
                                                $result[] = array( 'name'    => NoNull($pOK['name']),
                                                                   'type'    => NoNull($pOK['type']),
                                                                   'value'   => NoNull($pOK['content']),
                                                                   'success' => true
                                                                  );
                                            } else {
                                                $result[] = array( 'name'    => NoNull($zRow['name']),
                                                                   'type'    => NoNull($zRow['type']),
                                                                   'success' => false
                                                                  );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // Write the Final Results to the Log
        $ima = time();
        $yW = date('yW', $ima);
        $log_file = LOG_DIR . "/cloudflare-$yW.log";
        $fh = fopen($log_file, 'a');
        $ima_str = date("F j, Y h:i:s A", $ima );
        $stringData = "--------------------------------------------------------------------------------\n" .
                      "Record At: $ima_str \n" .
                      json_encode($result, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n" .
                      "--------------------------------------------------------------------------------\n";
        fwrite($fh, $stringData);
        fclose($fh);

        // Return an Array Outlining the Records Updated
        return $result;
    }

    private function _queryURL( $url ) {
        if ( NoNull($url) == '' || filter_var($url, FILTER_VALIDATE_URL) === false ) { return ''; }

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        $rVal = curl_exec($c);

        if ( curl_error($c) ) { return ''; }

        $status = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);

        if ( $status == 200 ) { return $rVal; }
        return '';
    }

    /**
     *  Function Determines if the Account Has an Appropriate Scope and Returns a Boolean Response
     */
    private function _hasScope( $Scope ) {
        return ( mb_strpos(' ' . $this->settings['_account_scopes'], $Scope) ) ? true : false;
    }
}
?>