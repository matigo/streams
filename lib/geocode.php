<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to manage Geocoding
 */
require_once(LIB_DIR . '/functions.php');

class Geocode {
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

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) {
            $this->_setMetaMessage("You Need to Log In First", 401);
            return false;
        }

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
            case 'rebuild':
            case 'build':
                $rVal = $this->_rebuildCityInformation();
                break;

            case '':
                $rVal = false;
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
                $rVal = false;
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
    public function getNameFromCoords( $latitude, $longitude ) { return $this->_getNameFromCoords( $latitude, $longitude); }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function Returns the Name of a Location based on the Latitude and Longitude Values.
     *  If a name is not found, or the matching score is too high, the coordinates are returned.
     */
    private function _getNameFromCoords( $latitude, $longitude ) {
        $CleanLong = nullInt($longitude);
        $CleanLat = nullInt($latitude);
        
        if ( $CleanLong == 0 && $CleanLat == 0 ) { return ''; }
        
        $ReplStr = array( '[COORD_LONG]' => $CleanLong,
                          '[COORD_LAT]'  => $CleanLat,
                         );
        $sqlStr = readResource(SQL_DIR . '/geocode/getLocationName.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $score = nullInt($Row['long_score']) + nullInt($Row['lat_score']);
                if ( $score <= 0.1 ) {
                    $names = array();
                    if ( NoNull($Row['alt_name'], $Row['place_name']) != '' && !in_array(NoNull($Row['alt_name'], $Row['place_name']), $names) ) { $names[] = NoNull($Row['alt_name'], $Row['place_name']); }
                    if ( NoNull($Row['area_name']) != '' && !in_array(NoNull($Row['area_name']), $names) ) { $names[] = NoNull($Row['area_name']); }
                    if ( NoNull($Row['state_name']) != '' && !in_array(NoNull($Row['state_name']), $names) ) { $names[] = NoNull($Row['state_name']); }
                    if ( NoNull($Row['country_name']) != '' && !in_array(NoNull($Row['country_name']), $names) ) { $names[] = NoNull($Row['country_name']); }

                    return implode(', ', $names);
                }
            }
        }

        // If We're Here, There Is No Match. Return the Coordinates.
        return round($latitude, 4) . ', ' . round($longitude, 4);
    }

    /** ********************************************************************* *
     *  Maintenance Functions
     ** ********************************************************************* */
    private function _getRemoteFile( $RemoteURL = '', $LocalFile = '' ) {
        if ( mb_strlen(NoNull($RemoteURL)) <= 10 ) { return false; }
        if ( mb_strlen(NoNull($LocalFile)) <= 10 ) { return false; }

        // Get the Remote File
        set_time_limit(0);
        $fp = fopen ($LocalFile, 'w+b');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_URL, $RemoteURL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        
        // Read the File to Storage
        curl_exec($ch);
        $rslt = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Return the Result
        return $rslt;
    }

    private function _rebuildTimezones() {
        $ReplStr = array( '&#39;' => "'", '&gt;' => '>', '&lt;' => '<' );
        $LocalFile = TMP_DIR . '/timezones.txt';
        $SourceURL = NoNull($this->settings['source_url'], "http://download.geonames.org/export/dump/timeZones.txt");
        if ( mb_strlen($SourceURL) <= 10 ) { $this->_setMetaMessage("Invalid URL Provided", 400); return false; }
        $rslt = $this->_getRemoteFile($SourceURL, $LocalFile);
        
        if ( $rslt == 200 ) {
            $handle = fopen($LocalFile, "r");
            if ($handle) {
                $sqlStr = '';
                $cnt = 0;
                while ( ($line = fgets($handle)) !== false ) {
                    $vals = explode("\t", $line);
                    if ( mb_strlen(NoNull($vals[0])) == 2 ) {
                        $code = sqlScrub($vals[0]);
                        $name = sqlScrub($vals[1]);
                        $gmt = nullInt($vals[2]);
                        $dst = nullInt($vals[3]);
                        $raw = nullInt($vals[4]);

                        if ( $sqlStr != '' ) { $sqlStr .= ','; }
                        $sqlStr .= "('$code', '$name', $gmt, $dst, $raw)";

                        $cnt++;
                    }
                }
                fclose($handle);
                
                $sqlStr = "INSERT INTO `tmpTimezone` (`country_code`, `name`, `gmt_offset`, `dst_offset`, `raw_offset`) " .
                          "VALUES $sqlStr";
                $isOK = doSQLExecute($sqlStr);
                
                return array( 'sql' => $isOK,
                              'cnt'  => $cnt
                             );

            } else {
                $this->_setMetaMessage("Could Not Rebuild Timezone Data", 400);
                return false;
            }
    
        } else {
            print_r( "Error: $rslt" );
        }

        // If We're Here, There Is No Summary (That We Know Of)
        return false;
    }
    
    private function _rebuildCountryInfo() {
        $ReplStr = array( '&#39;' => "'", '&gt;' => '>', '&lt;' => '<' );
        $LocalFile = TMP_DIR . '/timezones.txt';
        $SourceURL = NoNull($this->settings['source_url'], "http://download.geonames.org/export/dump/countryInfo.txt");
        if ( mb_strlen($SourceURL) <= 10 ) { $this->_setMetaMessage("Invalid URL Provided", 400); return false; }
        $rslt = $this->_getRemoteFile($SourceURL, $LocalFile);
        
        if ( $rslt == 200 ) {
            $handle = fopen($LocalFile, "r");
            if ($handle) {
                $sqlStr = '';
                $cnt = 0;
                while ( ($line = fgets($handle)) !== false ) {
                    $vals = explode("\t", $line);

                    if ( mb_strlen(NoNull($vals[0])) == 2 ) {
                        $code = sqlScrub($vals[0]);
                        $iso = sqlScrub($vals[1]);
                        $num = sqlScrub($vals[2]);
                        $name = sqlScrub($vals[4]);
                        $capital = sqlScrub($vals[5]);
                        $area = nullInt($vals[6]);
                        $pop = nullInt($vals[7]);
                        $cont = sqlScrub($vals[8]);
                        $tld = sqlScrub($vals[9]);
                        $curr_code = sqlScrub($vals[10]);
                        $curr_name = sqlScrub($vals[11]);
                        $gnid = nullInt($vals[16]);

                        if ( $sqlStr != '' ) { $sqlStr .= ','; }
                        $sqlStr .= "('$code', '$iso', '$num', '$name', '$capital', $area, $pop, '$cont', '$tld', '$curr_code', '$curr_name', $gnid)";

                        $cnt++;
                    }
                }
                fclose($handle);
                
                $sqlStr = "INSERT INTO `tmpCountry` (`code`, `iso3`, `iso_numeric`, `name`, `capital`, `area`, `population`, `continent`, `tld`, " . 
                                                    "`currency_code`, `currency_name`, `gnid`) " .
                          "VALUES $sqlStr";
                $isOK = doSQLExecute($sqlStr);
                
                return array( 'sql' => $isOK,
                              'cnt'  => $cnt
                             );

            } else {
                $this->_setMetaMessage("Could Not Rebuild Country Data", 400);
                return false;
            }
    
        } else {
            print_r( "Error: $rslt" );
        }

        // If We're Here, There Is No Summary (That We Know Of)
        return false;
    }

    private function _rebuildLanguageCodes() {
        $ReplStr = array( '&#39;' => "'", '&gt;' => '>', '&lt;' => '<' );
        $LocalFile = TMP_DIR . '/languagecodes.txt';
        $SourceURL = NoNull($this->settings['source_url'], "http://download.geonames.org/export/dump/iso-languagecodes.txt");
        if ( mb_strlen($SourceURL) <= 10 ) { $this->_setMetaMessage("Invalid URL Provided", 400); return false; }
        $rslt = $this->_getRemoteFile($SourceURL, $LocalFile);
        
        if ( $rslt == 200 ) {
            $handle = fopen($LocalFile, "r");
            if ($handle) {
                $sqlStr = '';
                $cnt = 0;
                while ( ($line = fgets($handle)) !== false ) {
                    $vals = explode("\t", $line);
                    if ( mb_strlen(NoNull($vals[0])) == 3 ) {
                        $name = sqlScrub(str_replace(array("\r", "\n"), '', $vals[3]));
                        $val1 = sqlScrub($vals[2]);
                        $val2 = sqlScrub($vals[1]);
                        $val3 = sqlScrub($vals[0]);

                        if ( $sqlStr != '' ) { $sqlStr .= ','; }
                        $sqlStr .= "('$name', '$val1', '$val2', '$val3')";

                        $cnt++;
                    }
                }
                fclose($handle);

                $sqlStr = "INSERT INTO `tmpLanguage` (`name`, `iso_639_1`, `iso_639_2`, `iso_639_3`) " .
                          "VALUES $sqlStr";
                $isOK = doSQLExecute($sqlStr);
                
                return array( 'sql' => $isOK,
                              'cnt' => $cnt
                             );

            } else {
                $this->_setMetaMessage("Could Not Rebuild Language Data", 400);
                return false;
            }
    
        } else {
            print_r( "Error: $rslt" );
        }

        // If We're Here, There Is No Summary (That We Know Of)
        return false;
    }

    private function _rebuildCityInformation() {
        $LocalFile = TMP_DIR . '/cities1000.txt';

        if ( file_exists($LocalFile) ) {
            $handle = fopen($LocalFile, "r");
            if ($handle) {
                $sqlStr = '';
                $cnt = 0;
                $tnt = 0;

                while ( ($line = fgets($handle)) !== false ) {
                    $vals = explode("\t", $line);
                    if ( nullInt($vals[0]) > 0 ) {
                        $gnid = nullInt($vals[0]);
                        $name = sqlScrub(str_replace(array("\r", "\n"), '', $vals[1]));
                        $latitude = nullInt($vals[4]);
                        $longitude = nullInt($vals[5]);
                        $lat_int = floor($latitude);
                        $long_int = floor($longitude);

                        $feat_class = sqlScrub($vals[6]);
                        $feat_code = sqlScrub($vals[7]);
                        $country = sqlScrub($vals[8]);
                        
                        $fips = sqlScrub($vals[10]);
                        $sec = sqlScrub($vals[11]);
                        $ter = sqlScrub($vals[12]);
                        $quad = sqlScrub($vals[13]);
                        
                        $pop = nullInt($vals[15]);
                        $elev = nullInt($vals[16]);
                        $timezone = sqlScrub($vals[17]);
                        $moddate = sqlScrub($vals[18]);

                        if ( $sqlStr != '' ) { $sqlStr .= ','; }
                        $sqlStr .= "($gnid, '$name', $latitude, $longitude, $lat_int, $long_int, '$feat_class', '$feat_code', '$country', " .
                                   "'$fips', '$sec', '$ter', '$quad', $pop, $elev, '$timezone', '$moddate')";
                        $cnt++;
                        $tnt++;
                        
                        if ( $tnt >= 2500 ) {
                            $sqlStr = "INSERT INTO `tmpGeoName` (`gnid`, `name`, `latitude`, `longitude`, `lat_int`, `long_int`, " .
                                                                "`feature_class`, `feature_code`, `country_code`, " .
                                                                "`fips_code`, `secondary`, `tertiary`, `quaternary`, " .
                                                                "`population`, `elevation`, `iana_id`, `updated_at`)" .
                                      "VALUES  $sqlStr";
                            $isOK = doSQLExecute($sqlStr);

                            // Reset the Variables
                            $sqlStr = '';
                            $tnt = 0;
                        }
                    }
                }
                fclose($handle);

                // Ensure the Last of the Items are Imported
                $sqlStr = "INSERT INTO `tmpGeoName` (`gnid`, `name`, `latitude`, `longitude`, `lat_int`, `long_int`, " .
                                                    "`feature_class`, `feature_code`, `country_code`, " .
                                                    "`fips_code`, `secondary`, `tertiary`, `quaternary`, " .
                                                    "`population`, `elevation`, `iana_id`, `updated_at`)" .
                          "VALUES  $sqlStr";
                $isOK = doSQLExecute($sqlStr);
                
                return array( 'sql' => $isOK,
                              'cnt' => $cnt
                             );
            }
        } else {
            $this->_setMetaMessage("Could Not Rebuild GeoNames Data", 400);
        }

        // If We're Here, There Is No Summary (That We Know Of)
        return false;
    }
    
    private function _rebuildGeoNames() {
        $LocalFile = TMP_DIR . '/allCountries.txt';

        if ( file_exists($LocalFile) ) {
            $handle = fopen($LocalFile, "r");
            if ($handle) {
                $sqlStr = '';
                $cnt = 0;
                $tnt = 0;

                while ( ($line = fgets($handle)) !== false ) {
                    $vals = explode("\t", $line);
                    if ( nullInt($vals[0]) > 0 ) {
                        $gnid = nullInt($vals[0]);
                        $name = sqlScrub(str_replace(array("\r", "\n"), '', $vals[1]));
                        $latitude = nullInt($vals[4]);
                        $longitude = nullInt($vals[5]);
                        $lat_int = floor($latitude);
                        $long_int = floor($longitude);
                        
                        $feat_class = sqlScrub($vals[6]);
                        $feat_code = sqlScrub($vals[7]);
                        $country = sqlScrub($vals[8]);
                        
                        $fips = sqlScrub($vals[10]);
                        $sec = sqlScrub($vals[11]);
                        $ter = sqlScrub($vals[12]);
                        $quad = sqlScrub($vals[13]);
                        
                        $pop = nullInt($vals[15]);
                        $elev = nullInt($vals[16]);
                        $timezone = sqlScrub($vals[17]);
                        $moddate = sqlScrub($vals[18]);

                        if ( $sqlStr != '' ) { $sqlStr .= ','; }
                        $sqlStr .= "($gnid, '$name', $latitude, $longitude, $lat_int, $long_int, '$feat_class', '$feat_code', '$country', " .
                                   "'$fips', '$sec', '$ter', '$quad', $pop, $elev, '$timezone', '$moddate')";
                        $cnt++;
                        $tnt++;
                        
                        if ( $tnt >= 2500 ) {
                            $sqlStr = "INSERT INTO `tmpGeoName` (`gnid`, `name`, `latitude`, `longitude`, `lat_int`, `long_int`, " .
                                                                "`feature_class`, `feature_code`, `country_code`, " .
                                                                "`fips_code`, `secondary`, `tertiary`, `quaternary`, " .
                                                                "`population`, `elevation`, `iana_id`, `updated_at`)" .
                                      "VALUES  $sqlStr";
                            $isOK = doSQLExecute($sqlStr);

                            // Reset the Variables
                            $sqlStr = '';
                            $tnt = 0;
                        }
                    }
                }
                fclose($handle);

                // Ensure the Last of the Items are Imported
                $sqlStr = "INSERT INTO `tmpGeoName` (`gnid`, `name`, `latitude`, `longitude`, `lat_int`, `long_int`, " .
                                                    "`feature_class`, `feature_code`, `country_code`, " .
                                                    "`fips_code`, `secondary`, `tertiary`, `quaternary`, " .
                                                    "`population`, `elevation`, `iana_id`, `updated_at`)" .
                          "VALUES  $sqlStr";
                $isOK = doSQLExecute($sqlStr);
                
                return array( 'sql' => $isOK,
                              'cnt' => $cnt
                             );
            }
        } else {
            $this->_setMetaMessage("Could Not Rebuild GeoNames Data", 400);
        }

        // If We're Here, There Is No Summary (That We Know Of)
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
    }
}
?>