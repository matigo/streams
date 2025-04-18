<?php

/**
 * @author Jason F. Irwin
 *
 * A General Module File with Core Functions that are Called Throughout the Application
 */

    /**
     * Function checks a value and returns a numeric value
     *  Note: Non-Numeric Values will return 0
     */
    function nullInt($number, $default = 0) {
        if ( is_bool($number) ) { $number = $default; }
        $number = filter_var($number, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $rVal = $default;

        if ( is_numeric($number) ) { $rVal = $number; }
        if ( $rVal == 0 && $default > 0 ) { $rVal = $default; }

        /* Return the Numeric Value */
        return floatval($rVal);
    }

    /**
     * Function checks a value and returns a string
     */
    function NoNull( $string, $default = "" ) {
        if ( is_bool($string) ) { $string = $default; }
        $ReplStr = array( '＃' => "#", urldecode('%EF%B8%8F') => ' ', '　' => ' ', );
        $rVal = $default;

        /* Pre-Process the String */
        $string = str_replace(array_keys($ReplStr), array_values($ReplStr), $string);

        /* Let's do some trimming and, if necessary, return defaults */
        if ( is_string($string) ) { $rVal = trim($string); }
        if ( $rVal == "" && $default != "" ) { $rVal = $default; }

        /* Return the String Value */
        return $rVal;
    }

    /**
     *  Function returns a Unix timestamp in a format that is prepped for API output
     *      or, if within 1000 seconds of Epoch, an unhappy boolean
     */
    function apiDate( $unixtime, $format = "Z" ) {
        $ts = nullInt($unixtime);
        if( $ts >= 0 && $ts <= 1000 ) { return false; }

        switch ( strtolower(NoNull($format)) ) {
            case 'unix':
            case 'int':
            case 'u':
                return $ts;
                break;

            default:
                /* Carry On */
        }

        /* Here is the default return string */
        return date("Y-m-d\TH:i:s\Z", $ts);
    }

    /**
     *  Function pads an integer with leading zeroes
     *
     *  Note: this is mainly used for internal directory naming
     */
    function paddNumber( $num, $length = 16 ) {
        if ( nullInt($length) > 64 ) { $length = 64; }
        if ( nullInt($length) <= 0 ) { $length = 16; }
        if ( nullInt($num) <= 0 ) { return ''; }

        $val = NoNull(substr(str_repeat('0', $length) . nullInt($num), ($length * -1)));
        if ( $val == str_repeat('0', $length) ) { return ''; }
        return $val;
    }

    /**
     *  Function returns an array of Unique words in a string (ideally for database insertion)
     */
    function UniqueWords( $string ) {
        if ( NoNull($string) == '' ) { return false; }
        $rVal = array();

        // Replace Some Characters
        $ReplStr = array( '‘' => "'", '’' => "'", '“' => '"', '”' => '"', '—' => ' ', '-' => ' ' );
        $text = str_replace(array_keys($ReplStr), array_values($ReplStr), $string);

        // Eliminate any Punctuation
        $punc = array( '!', '.', ',', '&', '?', '<', '>', '_', '(', ')', '*', '/', '$', '#', '%', '|', '\\', '-', '=', ';', ':', '~', '^', '`', '[', ']', '{', '}', '"', "'");
        $text = NoNull(str_replace($punc, ' ', html_entity_decode(strip_tags($text), ENT_COMPAT | ENT_HTML5 | ENT_QUOTES, 'UTF-8')));
        $uniques = array();
        $words = explode(' ', " $text ");
        foreach ( $words as $word ) {
            $word = strtolower(NoNull($word));
            if ( mb_strlen($word) > 1 && mb_strlen($word) <= 64 && in_array($word, $rVal) === false ) { $rVal[] = $word; }
        }

        // Return an Array of Unique Words (if we have at least one)
        if ( count($rVal) > 0 ) { return $rVal; }
        return false;
    }

    /**
     *  Function takes a URL, strips out the protocol and any suffix data, then builds a GUID representation so that
     *      it can be compared against other URLs for uniqueness. If the Url is invalid, an unhappy boolean is returned
     */
    function getGuidFromUrl( $url ) {
        $url = NoNull($url);
        if ( mb_strlen($url) <= 5 ) { return false; }

        if ( mb_substr($url, 0, 8) == 'https://' ) { $url = mb_substr($url, 8); }
        if ( mb_substr($url, 0, 7) == 'http://' ) { $url = mb_substr($url, 7); }
        $url = str_replace('//', '/', $url);
        $chkSlash = true;
        $cnt = 0;

        while ( $chkSlash ) {
            if ( $cnt >= 10 ) { return false; }
            if ( mb_substr($url, -1) == '/' ) {
                $url = mb_substr($url, 0, mb_strlen($url) - 1);
            } else {
                $chkSlash = false;
            }
            $cnt++;
        }

        // Construct a GUID for the Site Based on an MD5 of the "clean" URL
        $UrlGuid = substr(md5($url),  0,  8) . '-' .
                   substr(md5($url),  8,  4) . '-' .
                   substr(md5($url), 12,  4) . '-' .
                   substr(md5($url), 16,  4) . '-' .
                   substr(md5($url), 20, 12);

        // If the Url Guid Appears Valid, Return It. Otherwise, Unhappy Boolean
        if ( strlen($UrlGuid) == 36 ) { return $UrlGuid; }
        return false;
    }

    function isValidCronRequest( $sets, $valids ) {
        if ( defined('CRON_KEY') === false ) { define('CRON_KEY', ''); }
        if ( is_array($valids) === false ) { return false; }
        if ( is_array($sets) === false ) { return false; }

        if ( array_key_exists('key', $sets) && defined('CRON_KEY') ) {
            if ( mb_strlen(NoNull(CRON_KEY)) >= 20 && NoNull($sets['key']) == NoNull(CRON_KEY) ) {
                $route = NoNull($sets['PgRoot']) . '/' . NoNull($sets['PgSub1']);
                return in_array($route, $valids);
            }
        }
        return false;
    }

    /**
     *  Function returns Yes, No, or Clean based on the Key provided
     */
    function getExplicitValue( $value ) {
        $ReplStr = array( 'c' => 'Clean', 'clean' => 'Clean',
                          'y' => 'Yes', 'yes' => 'Yes',
                          'n' => 'No', 'no' => 'No',
                         );
        return NoNull($ReplStr[strtolower($value)]);
    }

    /**
     * Function Checks the Validity of a supplied URL and returns a cleaned string
     */
    function cleanURL( $URL ) {
        $rVal = ( preg_match('|^[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $URL) > 0 ) ? $URL : '';

        // Return the Cleaned URL Value
        return $rVal;
    }

    /**
     *  Function Records a Summarized RSS Feed to the local storage
     */
    function saveFeedObject( $feed ) {
        if ( is_array($feed) ) {
            if ( NoNull($feed['guid']) == '' ) { return; }
            $cacheFile = TMP_DIR . '/feeds/' . $feed['guid'] . '.data';
            if ( checkDIRExists( TMP_DIR . '/feeds' ) ) {
                $fh = fopen($cacheFile, 'w');
                fwrite($fh, json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                fclose($fh);
            }
        }
    }

    /**
     *  Function Reads a Feed's Cached JSON data based on the GUID passed
     *      If the data does not exist, an unhappy boolean is returned.
     */
    function readFeedObject( $feedGuid ) {
        if ( strlen(NoNull($feedGuid)) <> 36 ) { return false; }
        if ( checkDIRExists( TMP_DIR . '/feeds' ) ) {
            $cacheFile = TMP_DIR . '/feeds/' . $feedGuid . '.data';
            if ( file_exists( $cacheFile ) ) {
                $json = file_get_contents( $cacheFile );
                return json_decode($json);
            }
        }
        return false;
    }

    /**
     * Function Verifies if a Given TLD is in the List of Known TLDs and Returns a Boolean
     */
    function isValidTLD( $tld ) {
        $tld = NoNull(str_replace('.', '', strtolower($tld)));
        if ( $tld == '' ) { return false; }

        $cacheOK = reloadValidTLDs();
        $valids = array();

        // Load the Valid TLDs Array
        if ( checkDIRExists( TMP_DIR ) ) {
            $cacheFile = TMP_DIR . '/valid_tlds.data';
            if ( file_exists( $cacheFile ) ) {
                $data = file_get_contents( $cacheFile );
                $valids = unserialize($data);
            }
        }

        // Return a Boolean Response
        return in_array($tld, $valids);
    }

    /**
     * Function Builds the Valid TLDs Cache Array When Appropriate
     */
    function reloadValidTLDs() {
        $masterList = 'https://data.iana.org/TLD/tlds-alpha-by-domain.txt';
        $cacheFile = TMP_DIR . '/valid_tlds.data';
        $cacheLife = (60 * 60 * 24 * 30);               // 30 Days Life-Span
        $cacheAge = -1;

        $cacheAge = @filemtime($cacheFile);  // returns FALSE if file does not exist
        if (!$cacheAge or ((time() - $cacheAge) >= $cacheLife)) {
            $data = file_get_contents($masterList);
            if ( $data ) {
                $data = str_replace("\r", "\n", $data);
                $lines = explode("\n", $data);
                $valids = array();

                foreach ( $lines as $line ) {
                    // Only Process Lines that Do Not Have Hashes (Comments) and Dashes
                    if ( strpos($line, '#') === false && strpos($line, '-') === false && NoNull($line) != '' ) {
                        if ( NoNull($line) != '' ) { $valids[] = strtolower(NoNull($line)); }
                    }
                }

                if ( !in_array('local', $valids) ) { $valids[] = 'local'; }
                if ( !in_array('test', $valids) ) { $valids[] = 'test'; }
                if ( !in_array('dev', $valids) ) { $valids[] = 'dev'; }

                if ( checkDIRExists( TMP_DIR ) ) {
                    $fh = fopen($cacheFile, 'w');
                    fwrite($fh, serialize($valids));
                    fclose($fh);
                }
            }
        }

        // Return True
        return true;
    }

    /**
     * Function Returns a Boolean Response based on the Enumerated
     *  Value Passed
     */
    function YNBool( $val ) {
        $valids = array( 'true', 'yes', 'y', 't', 'on', '1', 1 );
        if ( is_bool($val) ) { return $val; }
        return in_array(strtolower($val), $valids);
    }

    /**
     * Function Returns a YN Value based on the Boolean Passed
     */
    function BoolYN( $val ) {
        if ( is_bool($val) ) { return ( $val ) ? 'Y' : 'N'; }
        $valids = array( 'true', 'yes', 'y', 't', 'on', '1', 1 );
        return ( in_array(strtolower($val), $valids) ) ? 'Y' : 'N';
    }

    /**
     *  Function Deletes all of the Files (Not Directories) in a Specified Location
     */
    function scrubDIR( $DIR ) {
        $FileName = "";
        $Excludes = array( 'rss.cache' );
        $rVal = false;
        $i = 0;

        if (is_dir($DIR)) {
            $objects = scandir($DIR);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    $FileName = $DIR . "/" . $object;
                    if ( filetype($FileName) != "dir" ) {
                        unlink($FileName);
                        $i++;
                    }
                }
            }
            reset($objects);
        }

        // If We've Deleted Some Files, then Set a Happy Return Boolean
        if ( $i > 0 ) { $rVal = true; }

        // Return a Boolean Value
        return $rVal;
    }

    /**
     * Function validates an Email Address and Returns a Boolean Response
     */
    function validateEmail( $email ) {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) { return true; }
        return false;
    }

    /**
     *  Function returns an array of Podcast Categories as defined in Apple's directory
     */
    function getPodcastCategories( $strings = false, $asOptions = false ) {
        $catFile = FLATS_DIR . '/resources/podcast_categories.json';
        $data = false;

        if ( file_exists($catFile) ) {
            if ( is_array($strings) === false || count($strings) <= 0 ) {
                $strings = getLangDefaults(DEFAULT_LANG);
            }

            $json = readResource($catFile, $strings);
            if ( $json !== false && $json != '' ) {
                $data = objectToArray(json_decode($json));
            }
        }

        // If we're just returning the array, do so
        if ( $asOptions === false && is_array($data) && count($data) > 0 ) { return $data; }

        // If we have data, build teh Options
        if ( is_array($data) && count($data) > 0 ) {
            $opts = '';
            foreach ( $data as $Key=>$cat ) {
                if ( NoNull($cat['title'], $Key) != '' ) {
                    if ( $opts != '' ) { $opts .= "\r\n"; }
                    $opts .= tabSpace(10) . '<option value="' . NoNull($Key) . '">' . NoNull($cat['title'], $Key) . '</option>';
                }
                if ( is_array($cat['subcategories']) ) {
                    foreach ( $cat['subcategories'] as $idx=>$sub ) {
                        if ( is_array($sub) ) {
                            foreach ( $sub as $kk=>$lbl ) {
                                if ( NoNull($lbl, $kk) != '' ) {
                                    if ( $opts != '' ) { $opts .= "\r\n"; }
                                    $opts .= tabSpace(10) . '<option value="' . NoNull($kk) . '">&mdash; ' . NoNull($lbl, $kk) . '</option>';
                                }
                            }
                        }
                    }
                }
            }

            // If we have a series of options, then return them
            if ( NoNull($opts) != '' ) { return $opts; }
        }

        // If we're here, there's nothing
        return false;
    }

    /**
     *  Function returns an array of time zones that are supported by PHP
     */
    function getTimeZoneList() {
        $zones = array( '(GMT-12:00) International Date Line West' => 'Pacific/Kwajalein',
                        '(GMT-11:00) Midway Island' => 'Pacific/Midway',
                        '(GMT-11:00) Samoa' => 'Pacific/Apia',
                        '(GMT-10:00) Hawaii' => 'Pacific/Honolulu',
                        '(GMT-09:00) Alaska' => 'America/Anchorage',
                        '(GMT-08:00) Pacific Time (US & Canada)' => 'America/Los_Angeles',
                        '(GMT-08:00) Tijuana' => 'America/Tijuana',
                        '(GMT-07:00) Arizona' => 'America/Phoenix',
                        '(GMT-07:00) Mountain Time (US & Canada)' => 'America/Denver',
                        '(GMT-07:00) Chihuahua' => 'America/Chihuahua',
                        '(GMT-07:00) Mazatlan' => 'America/Mazatlan',
                        '(GMT-06:00) Central Time (US & Canada)' => 'America/Chicago',
                        '(GMT-06:00) Central America' => 'America/Managua',
                        '(GMT-06:00) Mexico City' => 'America/Mexico_City',
                        '(GMT-06:00) Monterrey' => 'America/Monterrey',
                        '(GMT-06:00) Saskatchewan' => 'America/Regina',
                        '(GMT-05:00) Eastern Time (US & Canada)' => 'America/New_York',
                        '(GMT-05:00) Indiana (East)' => 'America/Indiana/Indianapolis',
                        '(GMT-05:00) Bogota' => 'America/Bogota',
                        '(GMT-05:00) Lima' => 'America/Lima',
                        '(GMT-05:00) Quito' => 'America/Bogota',
                        '(GMT-04:00) Atlantic Time (Canada)' => 'America/Halifax',
                        '(GMT-04:00) Caracas' => 'America/Caracas',
                        '(GMT-04:00) La Paz' => 'America/La_Paz',
                        '(GMT-04:00) Santiago' => 'America/Santiago',
                        '(GMT-03:30) Newfoundland' => 'America/St_Johns',
                        '(GMT-03:00) Brasilia' => 'America/Sao_Paulo',
                        '(GMT-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
                        '(GMT-03:00) Greenland' => 'America/Godthab',
                        '(GMT-02:00) Mid-Atlantic' => 'America/Noronha',
                        '(GMT-01:00) Azores' => 'Atlantic/Azores',
                        '(GMT-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
                        '(GMT) Universal Time' => 'UTC',
                        '(GMT) Casablanca' => 'Africa/Casablanca',
                        '(GMT) Lisbon' => 'Europe/Lisbon',
                        '(GMT) London' => 'Europe/London',
                        '(GMT) Monrovia' => 'Africa/Monrovia',
                        '(GMT+01:00) Amsterdam' => 'Europe/Amsterdam',
                        '(GMT+01:00) Belgrade' => 'Europe/Belgrade',
                        '(GMT+01:00) Berlin' => 'Europe/Berlin',
                        '(GMT+01:00) Bratislava' => 'Europe/Bratislava',
                        '(GMT+01:00) Brussels' => 'Europe/Brussels',
                        '(GMT+01:00) Budapest' => 'Europe/Budapest',
                        '(GMT+01:00) Copenhagen' => 'Europe/Copenhagen',
                        '(GMT+01:00) Ljubljana' => 'Europe/Ljubljana',
                        '(GMT+01:00) Madrid' => 'Europe/Madrid',
                        '(GMT+01:00) Paris' => 'Europe/Paris',
                        '(GMT+01:00) Prague' => 'Europe/Prague',
                        '(GMT+01:00) Rome' => 'Europe/Rome',
                        '(GMT+01:00) Sarajevo' => 'Europe/Sarajevo',
                        '(GMT+01:00) Skopje' => 'Europe/Skopje',
                        '(GMT+01:00) Stockholm' => 'Europe/Stockholm',
                        '(GMT+01:00) Vienna' => 'Europe/Vienna',
                        '(GMT+01:00) Warsaw' => 'Europe/Warsaw',
                        '(GMT+01:00) West Central Africa' => 'Africa/Lagos',
                        '(GMT+01:00) Zagreb' => 'Europe/Zagreb',
                        '(GMT+02:00) Athens' => 'Europe/Athens',
                        '(GMT+02:00) Bucharest' => 'Europe/Bucharest',
                        '(GMT+02:00) Cairo' => 'Africa/Cairo',
                        '(GMT+02:00) Harare' => 'Africa/Harare',
                        '(GMT+02:00) Helsinki' => 'Europe/Helsinki',
                        '(GMT+02:00) Istanbul' => 'Europe/Istanbul',
                        '(GMT+02:00) Jerusalem' => 'Asia/Jerusalem',
                        '(GMT+02:00) Kyev' => 'Europe/Kiev',
                        '(GMT+02:00) Minsk' => 'Europe/Minsk',
                        '(GMT+02:00) Pretoria' => 'Africa/Johannesburg',
                        '(GMT+02:00) Riga' => 'Europe/Riga',
                        '(GMT+02:00) Sofia' => 'Europe/Sofia',
                        '(GMT+02:00) Tallinn' => 'Europe/Tallinn',
                        '(GMT+02:00) Vilnius' => 'Europe/Vilnius',
                        '(GMT+03:00) Baghdad' => 'Asia/Baghdad',
                        '(GMT+03:00) Kuwait' => 'Asia/Kuwait',
                        '(GMT+03:00) Moscow' => 'Europe/Moscow',
                        '(GMT+03:00) Nairobi' => 'Africa/Nairobi',
                        '(GMT+03:00) Riyadh' => 'Asia/Riyadh',
                        '(GMT+03:00) St. Petersburg' => 'Europe/Moscow',
                        '(GMT+03:00) Volgograd' => 'Europe/Volgograd',
                        '(GMT+03:30) Tehran' => 'Asia/Tehran',
                        '(GMT+04:00) Abu Dhabi' => 'Asia/Muscat',
                        '(GMT+04:00) Baku' => 'Asia/Baku',
                        '(GMT+04:00) Muscat' => 'Asia/Muscat',
                        '(GMT+04:00) Tbilisi' => 'Asia/Tbilisi',
                        '(GMT+04:00) Yerevan' => 'Asia/Yerevan',
                        '(GMT+04:30) Kabul' => 'Asia/Kabul',
                        '(GMT+05:00) Ekaterinburg' => 'Asia/Yekaterinburg',
                        '(GMT+05:00) Islamabad' => 'Asia/Karachi',
                        '(GMT+05:00) Karachi' => 'Asia/Karachi',
                        '(GMT+05:00) Tashkent' => 'Asia/Tashkent',
                        '(GMT+05:30) Kolkata' => 'Asia/Kolkata',
                        '(GMT+05:45) Kathmandu' => 'Asia/Kathmandu',
                        '(GMT+06:00) Almaty' => 'Asia/Almaty',
                        '(GMT+06:00) Dhaka' => 'Asia/Dhaka',
                        '(GMT+06:00) Novosibirsk' => 'Asia/Novosibirsk',
                        '(GMT+06:00) Sri Jayawardenepura' => 'Asia/Colombo',
                        '(GMT+06:30) Rangoon' => 'Asia/Rangoon',
                        '(GMT+07:00) Bangkok' => 'Asia/Bangkok',
                        '(GMT+07:00) Jakarta' => 'Asia/Jakarta',
                        '(GMT+07:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
                        '(GMT+08:00) Beijing' => 'Asia/Beijing',
                        '(GMT+08:00) Chongqing' => 'Asia/Chongqing',
                        '(GMT+08:00) Hong Kong' => 'Asia/Hong_Kong',
                        '(GMT+08:00) Irkutsk' => 'Asia/Irkutsk',
                        '(GMT+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
                        '(GMT+08:00) Perth' => 'Australia/Perth',
                        '(GMT+08:00) Singapore' => 'Asia/Singapore',
                        '(GMT+08:00) Taipei' => 'Asia/Taipei',
                        '(GMT+08:00) Ulaan Bataar' => 'Asia/Irkutsk',
                        '(GMT+08:00) Urumqi' => 'Asia/Urumqi',
                        '(GMT+09:00) Seoul' => 'Asia/Seoul',
                        '(GMT+09:00) Tokyo' => 'Asia/Tokyo',
                        '(GMT+09:00) Yakutsk' => 'Asia/Yakutsk',
                        '(GMT+09:30) Adelaide' => 'Australia/Adelaide',
                        '(GMT+09:30) Darwin' => 'Australia/Darwin',
                        '(GMT+10:00) Brisbane' => 'Australia/Brisbane',
                        '(GMT+10:00) Canberra' => 'Australia/Sydney',
                        '(GMT+10:00) Guam' => 'Pacific/Guam',
                        '(GMT+10:00) Hobart' => 'Australia/Hobart',
                        '(GMT+10:00) Melbourne' => 'Australia/Melbourne',
                        '(GMT+10:00) Port Moresby' => 'Pacific/Port_Moresby',
                        '(GMT+10:00) Sydney' => 'Australia/Sydney',
                        '(GMT+10:00) Vladivostok' => 'Asia/Vladivostok',
                        '(GMT+11:00) Magadan' => 'Asia/Magadan',
                        '(GMT+12:00) Auckland' => 'Pacific/Auckland',
                        '(GMT+12:00) Fiji' => 'Pacific/Fiji',
                        '(GMT+12:00) Kamchatka' => 'Asia/Kamchatka',
                        '(GMT+12:00) Wellington' => 'Pacific/Auckland',
                        '(GMT+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
                    );

        /* Return the List of Time Zones */
        return $zones;
    }

    /**
     *  Function Returns the Current Time in the TimeZone specified
     */
    function getCurrentTimeInZone( $TimeZone = 'Asia/Tokyo', $CallTime = "" ) {
        date_default_timezone_set('utc');
        $rVal = date( 'Y-m-d H:i:00', time() );
        $dateFrom = $CallTime;
        if ( $CallTime == "" ) {
            $dateFrom = $rVal;
        } else {
            if ( is_numeric($CallTime) ) { $dateFrom = date( 'Y-m-d H:i:00', $CallTime ); }
        }

        $sqlStr = "SELECT DATE_FORMAT(CONVERT_TZ('$dateFrom', 'utc', '$TimeZone'), '%Y-%m-%d %H:%i:00') as `LocalTime`;";
        $rslt = doSQLQuery( $sqlStr );
        if ( is_array($rslt) ) {
            $rVal = $rslt[0]['LocalTime'];
        }

        // Return the Local Time
        return $rVal;
    }

    /**
     * Function returns the current MicroTime Value
     */
    function getMicroTime() {
        $time = microtime();
        $time = explode(' ', $time);
        $time = $time[1] + $time[0];

        // Return the Time
        return $time;
    }

    /**
     *  Function Generates an XML Element
     */
    function generateXML( $tag_in, $value_in = "", $attribute_in = "" ){
        $rVal = "";
        $attributes_out = "";
        if (is_array($attribute_in)){
            if (count($attribute_in) != 0){
                foreach($attribute_in as $k=>$v) {
                    $attributes_out .= " ".$k."=\"".$v."\"";
                }
            }
        }

        // Return the XML Tag
        return "<".$tag_in."".$attributes_out.((trim($value_in) == "") ? "/>" : ">".$value_in."</".$tag_in.">" );
    }

    function tabSpace( $num ) {
        $rVal = '';
        if ( $num <= 0 ) { return $rVal; }
        for ( $i = 0; $i < $num; $i++ ) { $rVal .= '    '; }

        // Return the Spaces
        return $rVal;
    }

    /**
     *
     */
    function arrayToXML( $array_in ) {
        $rVal = "";
        $attributes = array();

        foreach($array_in as $k=>$v) {
            if ($k[0] == "@"){
                // attribute...
                $attributes[str_replace("@","",$k)] = $v;
            } else {
                if (is_array($v)){
                    $rVal .= generateXML($k,arrayToXML($v),$attributes);
                    $attributes = array();
                } else if (is_bool($v)) {
                    $rVal .= generateXML($k,(($v==true)? "true" : "false"),$attributes);
                    $attributes = array();
                } else {
                    $rVal .= generateXML($k,$v,$attributes);
                    $attributes = array();
                }
            }
        }

        // Return the XML
        return $rVal;
    }

    // Eliminate the White Space and (Optionally) Style Information from a String
    function scrubWhiteSpace( $String, $ScrubStyles = false ) {
        $rVal = $String;

        $rVal = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $rVal));
        if ( $ScrubStyles ) {
            $rVal = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $rVal);
        }

        // Return the Trimmed String
        return NoNull($rVal);
    }

    /**
     * Function Returns the Amount of Time that has passed since $UnixTime
     */
    function getTimeSince( $UnixTime ) {
        $rVal = "";

        if ( $UnixTime > 0 ) {
            $time = time() - $UnixTime;

            $tokens = array (
                31536000 => 'year',
                2592000 => 'month',
                604800 => 'week',
                86400 => 'day',
                3600 => 'hour',
                60 => 'minute',
                1 => 'second'
            );

            foreach ($tokens as $unit => $text) {
                if ($time < $unit) continue;
                $numberOfUnits = floor($time / $unit);
                return $numberOfUnits . ' ' . $text . ( ($numberOfUnits > 1) ? 's' : '' );
            }
        }

        // Return the Appropriate Time String
        return $rVal;
    }

    /**
     * Function Returns the Number of Minutes Since $UnixTime
     */
    function getMinutesSince( $UnixTime ) {
        $rVal = 0;

        if ( $UnixTime > 0 ) {
            $time = time() - $UnixTime;
            if ($time > 60) { $rVal = floor($time / 60); }
        }

        // Return the Number of Minutes that have Passed
        return $rVal;
    }

    /**
     * Function Returns the Number of Minutes Since $UnixTime
     */
    function getMinutesUntil( $UnixTime ) {
        $rVal = 0;

        if ( $UnixTime > 0 ) {
            $time =  $UnixTime - time();
            if ($time > 60) { $rVal = floor($time / 60); }
        }

        // Return the Number of Minutes that have Passed
        return $rVal;
    }

    /**
     * Function Returns the a Cleaner Representation fo Data Size
     */
    function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Function returns a random string of X Length
     */
    function getRandomString( $Length = 10, $AsHex = false ) {
        $rVal = "";
        $nextChar = "";

        $chars = ( $AsHex ) ? '0123456789abcdef' : '0123456789abcdefghijklmnopqrstuvwxyz';
        for ($p = 0; $p < $Length; $p++) {
            $randBool = rand(1, 9);
            $nextChar = ( $randBool > 5 ) ? strtoupper( $chars[mt_rand(0, strlen($chars))] )
                                          : $chars[mt_rand(0, strlen($chars))];

            //Append the next character to the string
            $rVal .= $nextChar;
        }

        // Return the Random String
        return $rVal;
    }

    /**
     * Functions are Used in uksort() Operations
     */
    function arraySortAsc( $a, $b ) {
        if ($a == $b) return 0;
        return ($a > $b) ? -1 : 1;
    }

    function arraySortDesc( $a, $b ) {
        if ($a == $b) return 0;
        return ($a > $b) ? 1 : -1;
    }

    /**
     * Function Determines if String "Starts With" the supplied String
     */
    function startsWith($haystack, $needle) {
        return !strncmp($haystack, $needle, strlen($needle));
    }

    /**
     * Function Determines if String "Ends With" the supplied String
     */
    function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) { return true; }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Function Confirms a directory exists and makes one if it doesn't
     *      before returning a Boolean
     */
    function checkDIRExists( $DIR ){
        $rVal = true;
        if ( !file_exists($DIR) ) {
            $rVal = mkdir($DIR, 755, true);
            chmod($DIR, 0755);
        }

        // Return the Boolean
        return $rVal;
    }

    /**
     * Function Returns the Number of Files contained within a directory
     */
    function countDIRFiles( $DIR ) {
        $rVal = 0;

        // Only check if the directory exists (of course)
        if ( file_exists($DIR) ) {
            foreach ( glob($DIR . "/*.token") as $filename) {
                $rVal += 1;
            }
        }

        // Return the Number of Files
        return $rVal;
    }

    /**
     * Function returns an array from an Object
     */
    function objectToArray($d) {
        if (is_object($d)) {
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            return array_map(__FUNCTION__, $d);
        }
        else {
            return $d;
        }
    }

    /**
     *  Function Returns a Boolean Stating Whether a String is a Link or Not
     */
    function isValidURL( $text ) {
        if ( strpos($text, '.') > 0 && strpos($text, '.') < strlen($text) ) { return true; }
        return false;
    }

    /**
     *  Function Returns the Protocol (HTTP/HTTPS) Being Used
     *  Updated to resolve a problem when running behind a load balancer
     */
    function getServerProtocol() {
        if ( array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) === false ) { $_SERVER['HTTP_X_FORWARDED_PROTO'] = ''; }
        if ( array_key_exists('HTTP_CF_VISITOR', $_SERVER) === false ) { $_SERVER['HTTP_CF_VISITOR'] = ''; }
        if ( array_key_exists('REQUEST_SCHEME', $_SERVER) === false ) { $_SERVER['REQUEST_SCHEME'] = ''; }

        $rVal = strtolower(NoNull($_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_X_FORWARDED_PROTO']));
        if ( NoNull($_SERVER['HTTP_CF_VISITOR']) != '' ) {
            $cf_proto = json_decode($_SERVER['HTTP_CF_VISITOR']);
            $cf_array = objectToArray($cf_proto);
            if ( array_key_exists('scheme', $cf_array) ) { $rVal = strtolower($cf_array['scheme']); }
        }
        return strtolower(NoNull($rVal, 'http'));
    }

    /**
     * Function returns a person's IPv4 or IPv6 address
     */
    function getVisitorIPv4() {
        $opts = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        $max = 0;
        $ip = false;

        foreach ( $opts as $opt ) {
            if ( $ip === false && array_key_exists($opt, $_SERVER) ) {
                $ip = filter_var($_SERVER[$opt], FILTER_VALIDATE_IP);

                /* How long is the string? */
                if ( $ip === false ) {
                    $iplen = mb_strlen(NoNull($_SERVER[$opt]));
                    if ( $iplen > $max ) { $max = $iplen; }
                }
            }
        }

        if ( $ip === false ) { $ip = "Invalid IP ($max Characters)"; }

        /* Return the Visitor's IP Address */
        return NoNull($ip);
    }

    /**
     *  Function returns an array of IPv4 addresses that are within a given CIDR
     */
    function cidrToRange($cidr) {
        if ( strpos($cidr, '/') === false ) { $cidr .= '/32'; }
        $cidr = explode('/', $cidr);
        $out = array();

        /* Get the First IP address and the "run" */
        $base = ip2long($cidr[0]) & ((-1 << (32 - (int)$cidr[1])));
        $run = ip2long($range[0]) + pow(2, (32 - (int)$cidr[1])) - 1;

        /* Don't let the run get out of hand, as that'll kill the memory */
        if ( $run > 4096 ) { $run = 4096; }

        /* Construct an Array of Addresses */
        for ( $i = 0; $i <= $run; $i++ ) {
            $ip = long2ip($base + $i);
            if ( in_array($ip, $out) === false ) { $out[] = $ip; }
        }

        /* Return an array of valid IP addresses */
        return $out;
    }

    function getApiUrl() {
        if ( !defined('API_DOMAIN') ) { define('API_DOMAIN', ''); }

        $apiURL = NoNull(API_DOMAIN);
        if ( $apiURL == '' ) { $apiURL = NoNull($_SERVER['SERVER_NAME']) . '/api'; }

        $Protocol = getServerProtocol();
        return $Protocol . '://' . $apiURL;
    }

    function getCdnUrl() {
        if ( !defined('CDN_DOMAIN') ) { define('CDN_DOMAIN', ''); }

        $cdnURL = NoNull(CDN_DOMAIN);
        if ( $cdnURL == '' ) { $cdnURL = NoNull($_SERVER['SERVER_NAME']) . '/files'; }

        $Protocol = getServerProtocol();
        return $Protocol . '://' . $cdnURL;
    }

    function getPaginationSets() {
        if ( is_array($GLOBALS['pagination']) ) { return $GLOBALS['pagination']; }
        return false;
    }

    function setPaginationSets( $PageSet ) {
        if ( is_array($PageSet) ) { $GLOBALS['pagination'] = $PageSet; }
    }

    /**
     * Function scrubs a string to ensure it's safe to use in a URL
     */
    function sanitizeURL( $string, $excludeDot = true ) {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
                       "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
                       "â€”", "â€“", ",", "<", ">", "/", "?");
        $cleanFilter = "/[^a-zA-Z0-9-]/";
        if ( $excludeDot ) {
            array_push($strip, ".");
            $cleanFilter = "/[^a-zA-Z0-9-.]/";
        }
        $clean = NoNull(str_replace($strip, "", strip_tags($string)));
        $clean = preg_replace($cleanFilter, "", str_replace(' ', '-', $clean));

        // This isn't cool, but may be necessary for non-Latin Characters
        if ( NoNull($clean) == "" ) { $clean = urlencode($string); }

        //Return the Lower-Case URL
        return strtolower($clean);
    }

    /**
     *  Function Returns a Gravatar URL for a Given Email Address
     *  Note: Code based on source from https://en.gravatar.com/site/implement/images/php/
     */
    function getGravatarURL( $emailAddr, $size = 80, $default = 'mm', $rating = 'g', $img = false, $atts = array() ) {
        $rVal = "";

        if ( NoNull($emailAddr) != "" ) {
            $rVal = "//gravatar.com/avatar/" . md5( strtolower( NoNull($emailAddr) ) ) . "?s=$size&d=$default&r=$rating";
            if ( $img ) {
                $rVal = '<img src="' . $rVal . '"';
                foreach ( $atts as $key => $val ) {
                    $rVal .= ' ' . $key . '="' . $val . '"';
                }
                $rVal .= ' />';
            }
        }

        // Return the URL
        return $rVal;
    }

    /**
     * Function parses the HTTP Header to extract just the Response code
     */
    function checkHTTPResponse( $header ) {
        $rVal = 0;

        if(preg_match_all('!HTTP/1.1 ([0-9a-zA-Z]*) !', $header, $matches, PREG_SET_ORDER)) {
            foreach($matches as $match) {
                $rVal = nullInt( $match[1] );
            }
        }

        // Return the HTTP Response Code
        return $rVal;
    }

    /**
     * Function parses the HTTP Header into an array and returns the results.
     *
     * Note: HTTP Responses are not included in this array
     */
    function parseHTTPResponse( $header ) {
        $rVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));

        // Parse the Fields
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));

                if( isset($rVal[$match[1]]) ) {
                    $rVal[$match[1]] = array($rVal[$match[1]], $match[2]);
                } else {
                    $rVal[$match[1]] = trim($match[2]);
                }
            }
        }

        // Return the Array of Headers
        return $rVal;
    }

    function getCallbackURL( $extras = array() ) {
        $Excludes = array( 'PgRoot', 'PgSub1', 'PgSub2' );
        $rVal = (empty($_SERVER['HTTPS'])) ? "http://" : "https://";
        $rVal .= $_SERVER['SERVER_NAME'];
        $rVal .= ($_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443) ? "" : (":".$_SERVER['SERVER_PORT']);
        if ( NoNull($extras['PgRoot']) != "" ) { $rVal .= "/" . NoNull($extras['PgRoot']); }
        if ( NoNull($extras['PgSub1']) != "" ) { $rVal .= "/" . NoNull($extras['PgSub1']); }
        $rVal .= '/?action=callback';

        if ( count($extras) > 0 ) {
            foreach ( $extras as $Key=>$Value ) {
                if ( !in_array($Key, $Excludes) ) {
                    $rVal .= "&" . urlencode($Key) . "=" . urlencode($Value);
                }
            }
        }

        // Return the Appropritate Callback URL
        return $rVal;
    }

    // Function Validates a URL and, if necessary, follows Redirects to Obtain the Proper URL Domain
    function validateURLDomain( $url ) {
        if ( defined('VALIDATE_URLS') === false ) { define('VALIDATE_URLS', 0); }
        $url = NoNull($url);
        if ( $url == "" ) { return false; }
        $rVal = false;

        if ( YNBool(VALIDATE_URLS) ) {
            $url_pattern = '#(www\.|https?://)?[a-z0-9]+\.[a-z0-9]\S*#i';
            $okHead = array('HTTP/1.0 200 OK', 'HTTP/1.1 200 OK', 'HTTP/2.0 200 OK');
            $fixes = array( 'http//'  => "http://",         'http://http://'   => 'http://',
                            'https//' => "https://",        'https://https://' => 'https://',
                            ','       => '',                'http://https://'  => 'https://',
                           );
            $scrub = array('#', '?', '.', ':', ';');

            if ( mb_strpos($url, '.') !== false && mb_strpos($url, '.') <= (mb_strlen($url) - 1) && NoNull(str_ireplace('.', '', $url)) != '' &&
                 mb_strpos($url, '[') == false && mb_strpos($url, ']') == false ) {
                $clean_word = str_replace("\n", '', strip_tags($url));
                if ( in_array(substr($clean_word, -1), $scrub) ) { $clean_word = substr($clean_word, 0, -1); }

                $url = ((stripos($clean_word, 'http') === false ) ? "http://" : '') . $clean_word;
                $url = str_ireplace(array_keys($fixes), array_values($fixes), $url);
                $headers = false;

                // Ensure We Have a Valid URL Here
                $hdParts = explode('.', $url);
                if ( NoNull($hdParts[count($hdParts) - 1]) != '' ) { $headers = get_headers($url); }

                if ( is_array($headers) ) {
                    $rURL = $url;

                    // Do We Have a Redirect?
                    foreach ($headers as $Row) {
                        if ( mb_strpos(strtolower($Row), 'location') !== false ) {
                            $rURL = NoNull(str_ireplace('location:', '', strtolower($Row)));
                            break;
                        }
                        if ( in_array(NoNull(strtoupper($Row)), $okHead) ) { break; }
                    }

                    $host = parse_url($rURL, PHP_URL_HOST);
                    if ( $host != '' ) { $rVal = strtolower(str_ireplace('www.', '', $host)); }
                }
            }
        } else {
            $hparts = explode('.', parse_url($url, PHP_URL_HOST));
            $domain = '';
            $parts = 0;

            for ( $dd = 0; $dd < count($hparts); $dd++ ) {
                if ( NoNull($hparts[$dd]) != '' ) {
                    $domain = NoNull($hparts[$dd]);
                    $parts++;
                }
            }

            if ( $parts > 1 && isValidTLD($domain) ) {
                $host = parse_url($url, PHP_URL_HOST);
                if ( $host != '' ) { $rVal = strtolower(str_ireplace('www.', '', $host)); }
            }
        }

        // Reutrn the URL
        return $rVal;
    }

    /**
     * Function redirects a visitor to the specified URL
     */
    function redirectTo( $Url, $sets = false ) {
        $RefUrl = NoNull($_SERVER['REQUEST_SCHEME'], 'http') . '://' . NoNull($_SERVER['SERVER_NAME'], $_SERVER['HTTP_HOST']);
        $ReqURI = NoNull($_SERVER['REQUEST_URI'], '/');
        if ( in_array(strtolower($ReqURI), array('/', '')) === false ) {
            $ps = explode('/', strtolower($ReqURI));
            foreach ( $ps as $p ) {
                if ( NoNull($p) != '' ) { $RefUrl .= '/' . NoNull($p); }
            }
        }

        // Set the Redirect Status
        $status = 302;
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1');
        header($protocol . ' ' . nullInt($status) . ' ' . getHTTPCode($status) );

        // Record the Usage Statistic so that 302s are Counted
        if ( is_array($sets) ) { recordUsageStat($sets, 302, ''); }

        // If we have a Referral URL, Set It
        if ( NoNull($RefUrl) != '' ) { header( "Referer: $RefUrl" ); }

        // Set the Location header record
        header( "Location: $Url" );
        die;
    }

    /***********************************************************************
     *  Basic Data Returns
     ***********************************************************************/
    function getLicenceList() {
        $code = array( 'ccby', 'ccbysa', 'ccbynd', 'ccbync', 'ccbyncsa', 'ccbyncnd', 'cc0' );
        $rVal = array();

        foreach ( $code as $key ) {
            $rVal[] = array( 'code'  => NoNull(strtolower($key)),
                             'label' => NoNull('license-' . strtoupper($key)),
                            );
        }

        // Return a List of License Codes
        return $rVal;
    }

    function getTimeZones() {
        $sqlStr = readResource(SQL_DIR . '/system/getTZList.sql', array());
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();
            foreach ( $rslt as $Row ) {
                $data[] = array( 'zone_id'     => nullInt($Row['id']),
                                 'name'        => NoNull($Row['name']),
                                 'description' => NoNull(str_replace('_', ' ', $Row['description'])),
                                 'group'       => NoNull($Row['group']),
                                );
            }
            if ( count($data) > 0 ) { return $data; }
        }
        // If We're Here, We Have Nothing
        return false;
    }

    function getTimeZoneByID( $TZID ) {
        if ( nullInt($TZID) <= 0 ) { return ''; }
        $rVal = '';

        $ReplStr = array( '[TZ_ID]' => nullInt($TZID) );
        $sqlStr = readResource(SQL_DIR . '/system/getTZDescrFromID.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $rVal = NoNull($Row['description']);
            }
        }

        return $rVal;
    }

    /***********************************************************************
     *  File Handling Functions
     ***********************************************************************/
    /**
     *  Function Returns the File Extension of a Given File
     */
    function getFileExtension( $FileName ) {
        return NoNull( substr(strrchr($FileName,'.'), 1) );
    }

    /**
     *  Function Returns a Realistic Mime Type based on a File Extension
     */
    function getMimeFromExtension( $FileExt ) {
        $types = array( 'mp3' => 'audio/mp3',       'm4a' => 'audio/m4a',
                        'gif' => 'image/gif',       'jpg' => 'image/jpeg',      'jpeg' => 'image/jpeg', 'png'  => 'image/png',  'tiff' => 'image/tiff', 'bmp' => 'image/bmp',
                        'mov' => 'video/quicktime', 'qt'  => 'video/quicktime', 'mpg'  => 'video/mpeg', 'mpeg' => 'video/mpeg', 'mp4' => 'video/mp4',
                        'pdf' => 'application/pdf', 'xml' => 'application/xml',
                        'md'  => 'text/plain',      'txt' => 'text/plain',      'htm'  => 'text/html',  'html' => 'text/html',
                        );
        return array_key_exists($FileExt, $types) ? NoNull($types[$FileExt]) : 'application/plain';
    }

    /**
     *  Function Determines if the DataType Being Uploaded is Valid or Not
     */
    function isValidUploadType( $FileType ) {
        $valids = array( 'audio/mp3', 'audio/mp4', 'audio/m4a', 'audio/x-mp3', 'audio/x-mp4', 'audio/mpeg', 'audio/x-m4a',
                         'image/gif', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/tiff', 'image/bmp', 'image/x-windows-bmp',
                         'video/quicktime', 'video/mpeg', 'image/x-quicktime',
                         'application/pdf', 'application/x-pdf',
                         'application/x-bzip', 'application/x-bzip2', 'application/x-compressed', 'application/x-gzip', 'multipart/x-gzip',
                         'application/plain', 'text/plain', 'text/html',
                         'application/msword', 'application/mspowerpoint', 'application/powerpoint', 'application/vnd.ms-powerpoint', 'application/x-mspowerpoint',
                         'application/vnd.ms-excel', 'application/x-excel', 'application/excel',
                         'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        );
        $rVal = false;

        // Is the FileType in the Array?
        if ( in_array($FileType, $valids) ) {
            $rVal = true;
        } else {
            writeNote( "Invalid FileType: $FileType", true );
        }

        // Return the Boolean Response
        return $rVal;
    }

    function isResizableImage( $FileType ) {
        $valids = array( 'image/jpg', 'image/jpeg', 'image/png', 'image/tiff', 'image/bmp', 'image/x-windows-bmp' );

        // Return the Boolean Response
        return in_array($FileType, $valids);
    }

    /** ******************************************************************** *
     *  Cache Functions
     ** ******************************************************************** */
    global $redis_db;

    /**
     *  Function determines the "correct" directory for a Cache object
     */
    function getCacheFileName( $name ) {
        if ( defined('TMP_DIR') === false ) { return ''; }
        if ( strlen(NoNull($name)) < 3 ) { return ''; }
        $name = strtolower($name);

        /* Do we have a series of dashes? Use a subdirectory with the file name prefix */
        if ( substr_count($name, '-') >= 2 ) {
            $segs = explode('-', $name);
            $dir = NoNull($segs[0], $segs[1]);
            if ( mb_strlen($dir) >= 4 ) {
                if ( checkDIRExists(TMP_DIR . '/cache') ) {
                    if ( checkDIRExists(TMP_DIR . "/cache/$dir") ) {
                        $name = str_replace($dir . '-', $dir . '/', $name);
                    }
                }
            }
        }

        /* Return the full path and name or an empty string */
        if ( mb_strlen(NoNull($name)) >= 4 ) { return TMP_DIR . '/cache/' . $name . '.data'; }
        return '';
    }

    /**
     *  Function Records an array of information to a cache location
     */
    function setCacheObject( $keyName, $data ) {
        if ( strlen(NoNull($keyName)) < 3 ) { return false; }
        if ( defined('USE_REDIS') === false ) { define('USE_REDIS', 0); }

        /* Continue only if we have an array of data */
        if ( is_array($data) ) {
            /* If we have Redis configured, use that. Otherwise, write to a local file */
            if ( YNBool(USE_REDIS) ) {
                /* Ensure the basics are in place with defaults */
                if ( defined('REDIS_HOST') === false ) { define('REDIS_HOST', 'localhost'); }
                if ( defined('REDIS_PASS') === false ) { define('REDIS_PASS', ''); }
                if ( defined('REDIS_PORT') === false ) { define('REDIS_PORT', 6379); }
                if ( defined('REDIS_EXPY') === false ) { define('REDIS_EXPY', 7200); }

                /* Create a connection if we do not already have one */
                if ( !$redis_db ) {
                    $redis_db = new Redis();

                    try {
                        $redis_db->connect(REDIS_HOST, REDIS_PORT);
                        if ( mb_strlen(REDIS_PASS) > 0 ) {
                            $redis_db->auth(REDIS_PASS);
                        }

                    } catch (RedisException $ex) {
                        $err = $ex->getMessage();
                        writeNote( "Could not connect to Redis: $err", true );
                    }
                }

                /* If we have a connection to Redis, check the data */
                if ( $redis_db->isConnected() ) {
                    /* Determine the key */
                    $key = str_replace(array('/', '_'), '-', $keyName);

                    /* If we have a Key Prefix, Prepend it */
                    if ( defined('REDIS_PFIX') && mb_strlen(REDIS_PFIX) >= 3 ) { $key = REDIS_PFIX . $key; }

                    /* Set the counter */
                    $GLOBALS['Perf']['redis_sets'] = nullInt($GLOBALS['Perf']['redis_sets']);
                    $GLOBALS['Perf']['redis_sets']++;

                    /* Set the Values */
                    $redis_db->set($key, serialize($data));
                    $redis_db->expire($key, REDIS_EXPY);
                    return;
                }
            }

            /* If we're here, use the local cahce (including if Redis fails) */
            $cacheFile = getCacheFileName($keyName);
            if ( $cacheFile != '' && checkDIRExists( TMP_DIR . '/cache' ) ) {
                $fh = fopen($cacheFile, 'w');
                if ( is_bool($fh) === false ) {
                    /* Set the counter */
                    $GLOBALS['Perf']['cache_sets'] = nullInt($GLOBALS['Perf']['cache_sets']);
                    $GLOBALS['Perf']['cache_sets']++;

                    /* Write the data */
                    fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    fclose($fh);
                }
            }
        }
    }

    /**
     *  Function Reads cached data and returns it. If no data exists, an unhappy boolean is returned.
     */
    function getCacheObject( $keyName ) {
        if ( strlen(NoNull($keyName)) < 3 ) { return false; }
        if ( defined('USE_REDIS') === false ) { define('USE_REDIS', 0); }

        /* If we have Redis configured, use that. Otherwise, write to a local file */
        if ( YNBool(USE_REDIS) ) {
            /* Ensure the basics are in place with defaults */
            if ( defined('REDIS_HOST') === false ) { define('REDIS_HOST', 'localhost'); }
            if ( defined('REDIS_PASS') === false ) { define('REDIS_PASS', ''); }
            if ( defined('REDIS_PORT') === false ) { define('REDIS_PORT', 6379); }
            if ( defined('REDIS_EXPY') === false ) { define('REDIS_EXPY', 7200); }

            /* Create a connection if we do not already have one */
            if ( !$redis_db ) {
                $redis_db = new Redis();

                try {
                    $redis_db->connect(REDIS_HOST, REDIS_PORT);
                    if ( mb_strlen(REDIS_PASS) > 0 ) {
                        $redis_db->auth(REDIS_PASS);
                    }

                } catch (RedisException $ex) {
                    $err = $ex->getMessage();
                    writeNote( "Could not connect to Redis: $err", true );
                }
            }

            /* If we have a connection to Redis, check the data */
            if ( $redis_db->isConnected() ) {
                /* Determine the key */
                $key = str_replace(array('/', '_'), '-', $keyName);

                /* If we have a Key Prefix, Prepend it */
                if ( defined('REDIS_PFIX') && mb_strlen(REDIS_PFIX) >= 3 ) { $key = REDIS_PFIX . $key; }

                /* Read the Values */
                $data = $redis_db->get($key);
                if ( is_string($data) && mb_strlen($data) > 0 ) {
                    /* Set the counter */
                    $GLOBALS['Perf']['redis_gets'] = nullInt($GLOBALS['Perf']['redis_gets']);
                    $GLOBALS['Perf']['redis_gets']++;

                    /* Return the data if we have it */
                    return unserialize($data);
                }

                /* If we're here, there's nothing */
                return false;
            }
        }

        /* If we're here, use the local cache (including if Redis fails) */
        if ( checkDIRExists( TMP_DIR . '/cache' ) ) {
            $cacheFile = getCacheFileName($keyName);
            if ( file_exists( $cacheFile ) ) {
                $age = filemtime($cacheFile);
                if ( !$age or ((time() - $age) > CACHE_EXPY) ) { return false; }

                $json = file_get_contents( $cacheFile );
                if  ( is_string($json) && mb_strlen($json) > 0 ) {
                    /* Set the counter */
                    $GLOBALS['Perf']['cache_gets'] = nullInt($GLOBALS['Perf']['cache_gets']);
                    $GLOBALS['Perf']['cache_gets']++;

                    /* Return the data if we have it */
                    return json_decode($json, true);
                }
            }
        }

        /* If we're here, there's nothing */
        return false;
    }

    /**
     *  Function Records any sort of ephemeral data to $GLOBALS['cache']
     */
    function setGlobalObject( $key, $data ) {
        if ( strlen(NoNull($key)) < 3 ) { return; }
        if ( is_array($GLOBALS) === false ) { return; }
        if ( array_key_exists('cache', $GLOBALS) === false ) {
            $GLOBALS['cache'] = array();
        }

        /* Set the Cache Key->Value */
        $GLOBALS['cache'][$key] = $data;
    }

    /**
     *  Function Reads any sort of ephemeral data from $GLOBALS['cache']
     */
    function getGlobalObject( $key ) {
        if ( strlen(NoNull($key)) < 3 ) { return false; }
        if ( is_array($GLOBALS) && array_key_exists('cache', $GLOBALS) ) {
            if ( array_key_exists($key, $GLOBALS['cache']) ) {
                return $GLOBALS['cache'][$key];
            }
        }

        /* Return an unhappy boolean if nothing exists */
        return false;
    }

    /***********************************************************************
     *  Resource Functions
     ***********************************************************************/
    /**
     * Function reads a file from the file system, parses and replaces,
     *      minifies, then returns the data in a string
     */
    function readResource( $ResFile, $ReplaceList = array(), $Minify = false ) {
        $rVal = "";

        // Check to ensure the Resource Exists
        if ( file_exists($ResFile) ) {
            $rVal = file_get_contents( $ResFile, "r");
        }

        // If there are Items to Replace, Do So
        if ( count($ReplaceList) > 0 ) {
            $Search = array_keys( $ReplaceList );
            $Replace = array_values( $ReplaceList );

            // Perform the Search/Replace Actions
            $rVal = str_replace( $Search, $Replace, $rVal );
        }

        // Strip all the white space if required
        if ( $Minify ) {
            for ( $i = 0; $i < 5; $i++ ) {
                $rVal = str_replace(array("\r\n", "\r", "\n", "\t", '  '), ' ', $rVal);
            }
            $rVal = str_replace('> <', '><', $rVal);
        }

        // Return the Data
        return $rVal;
    }

    function getLabelValue( $String, $ReplaceList = array() ) {
        $rVal = $String;

        // If there are Items to Replace, Do So
        if ( count($ReplaceList) > 0 ) {
            $Search = array_keys( $ReplaceList );
            $Replace = array_values( $ReplaceList );

            // Perform the Search/Replace Actions
            $rVal = str_replace( $Search, $Replace, $rVal );
        }

        // Return the Appropriate String
        return $rVal;
    }

    /***********************************************************************
     * Language Functions
     ***********************************************************************/
    /**
     * Function returns an array containing the base language strings used within the application.
     *
     * Note: If the Language Requested does not exist, only the Application Default
     *       will be returned.
     *     : The Application Default is always loaded and values are replaced with the requested
     *       strings so long as they exist.
     */
    function getLangDefaults( $LangCd ) {
        if ( defined('DEFAULT_LANG') === false ) { define('DEFAULT_LANG', 'en-us'); }
        if ( defined('APP_VER') === false ) { define('APP_VER', '000'); }
        if ( mb_strlen($LangCd) < 2 ) { $LangCd = DEFAULT_LANG; }

        $CacheKey = 'language-' . strtolower($LangCd) . '-' . NoNull(APP_VER) . '-' . date('Y');
        $data = getCacheObject($CacheKey);

        /* If we do not have a cached set of strings, build a set */
        if ( is_array($data) === false ) {
            /* Determine the Language File */
            $LangFile = LANG_DIR . "/" . strtolower($LangCd) . ".json";

            /* If the requested language does not exist, grab the default */
            if ( file_exists($LangFile) === false ) { $LangFile = LANG_DIR . "/" . strtolower(DEFAULT_LANG) . ".json"; }
            if ( file_exists($LangFile) ) {
                $json = readResource($LangFile);
                $items = objectToArray(json_decode($json));

                if ( is_array($items) ) {
                    $ReplStr = array( '{version_id}' => APP_VER,
                                      '{year}'       => date('Y'),
                                     );
                    $data = array();

                    foreach ( $items as $Key=>$Value ) {
                        $data["$Key"] = str_replace(array_keys($ReplStr), array_values($ReplStr), NoNull($Value));
                    }

                    /* If the data is valid, cache it */
                    if ( is_array($data) && count($data) > 0 ) { setCacheObject($CacheKey, $data); }
                }
            }
        }

        /* Return an array of strings or an unhappy boolean */
        if ( is_array($data) && count($data) > 0 ) { return $data; }
        return false;
    }

    /**
     * Function returns a list of languages available for the Theme
     * Notes: * This list is defined by the files located in THEME_DIR/lang
     *        * The Theme's Language File is read rather than the application's
     *          language file because the a Theme cannot have languages not
     *          already supported by the app, but not the inverse.
     */
    function listThemeLangs() {
        if ( defined('LANG_DIR') === false ) { return false; }
        $rVal = array();

        if ( $handle = opendir( LANG_DIR ) ) {
            $excludes = array('.', '..');

            /* Check the Langauge Definitions */
            while ( false !== ($FileName = readdir($handle)) ) {
                if ( in_array($FileName, $excludes) === false ) {
                    $LangFile = LANG_DIR . "/" . $FileName;
                    $ctxt = readResource($LangFile);
                    $json = json_decode($ctxt, true);

                    if ( is_array($json) ) {
                        $rVal[] = array( 'locale'    => NoNull($json['lang_culture']),
                                         'iso-639-1' => strtolower(NoNull($json['iso-639-1'], mb_substr($json['lang_culture'], 0, 2))),
                                        );
                    }
                }
            }

            /* Close the Directory Handle */
            closedir($handle);
        }

        /* Return the Array of Valid Languages */
        return $rVal;
    }

    /**
     * Function checks a Language Code against the installed languages
     *      and returns a LangCd Response.
     *
     * Note: If the provided Language Code is invalid, the application's
     *       default language is returned.
     */
    function validateLanguage( $LangCd ) {
        $LangList = listThemeLangs();
        $prop = DEFAULT_LANG;

        if ( is_array($LangList) ) {
            foreach ( $LangList as $key=>$val ) {
                if ( mb_strtolower($val['locale']) == mb_strtolower($LangCd) ) { $prop = $val['locale']; }
            }
        }

        /* Return the Default Application Language */
        return mb_strtolower(NoNull($prop));
    }

    /** ******************************************************************** *
     *  MySQL Functions
     ** ******************************************************************** */
    global $mysql_db;
    global $pgsql_db;

    function doSQLQuery($sqlStr, $params = array(), $dbname = '') {
        if ( defined('DB_ENGINE') === false ) { define('DB_ENGINE', 'mysql'); }
        if ( defined('DB_NAME') === false ) { define('DB_NAME', ''); }

        switch ( strtolower(DB_ENGINE) ) {
            case 'pgsql':
                return doPgSQLQuery($sqlStr, $params, $dbname);
                break;

            default:
                return doMySQLQuery($sqlStr, $params, $dbname);
                break;
        }

        /* We should never be here */
        return false;
    }

    function doSQLExecute($sqlStr, $params = array(), $dbname = '') {
        if ( defined('DB_ENGINE') === false ) { define('DB_ENGINE', 'mysql'); }
        if ( defined('DB_NAME') === false ) { define('DB_NAME', ''); }

        switch ( strtolower(DB_ENGINE) ) {
            case 'pgsql':
                return doPgSQLQuery($sqlStr, $params, $dbname);
                break;

            default:
                return doMySQLExecute($sqlStr, $params, $dbname);
                break;
        }

        /* We should never be here */
        return false;
    }

    function doPgSQLQuery($sqlStr, $params = array(), $dbname = '') {
        /* Validate the Database Name */
        if ( NoNull($dbname) == '' && defined('DB_NAME') ) { $dbname = DB_NAME; }

        /* If We Have Nothing, Return Nothing */
        if ( NoNull($sqlStr) == '' ) { return false; }
        $hash = sha1($sqlStr);

        /* Check to see if this query has been run once before and, if so, return the cached result */
        $rVal = getGlobalObject($hash);
        if ( $rVal !== false ) { return $rVal; }

        $GLOBALS['Perf']['queries'] = nullInt($GLOBALS['Perf']['queries']);
        $GLOBALS['Perf']['queries']++;
        $qstart = getMicroTime();
        $result = false;

        if ( !$pgsql_db ) {
            $ReplStr = array( '[HOST]' => DB_HOST,
                              '[NAME]' => sqlScrub($dbname),
                              '[USER]' => sqlScrub(DB_USER),
                              '[PASS]' => sqlScrub(DB_PASS),
                              '[PORT]' => nullInt(DB_PORT, 5432)
                             );
            $connStr = prepSQLQuery("host=[HOST] port=[PORT] dbname=[NAME] user=[USER] password=[PASS] options='--client_encoding=UTF8'", $ReplStr);
            $pgsql_db = pg_connect($connStr);
            if ( !$pgsql_db || pg_last_error($pgsql_db) ) {
                writeNote("doPgSQLQuery Connection Error :: " . pg_last_error($pgsql_db), true);
                return false;
            }

            /* Set the Client Encoding */
            pg_set_client_encoding($pgsql_db, "UNICODE");
        }

        /* If we have a good connection, let's go */
        if ( $pgsql_db ) {
            /* If We're In Debug, Capture the SQL Query */
            if ( defined('DEBUG_ENABLED') ) {
                if ( YNBool(DEBUG_ENABLED) ) {
                    if ( array_key_exists('debug', $GLOBALS) === false ) {
                        $GLOBALS['debug'] = array();
                        $GLOBALS['debug']['queries'] = array();
                    }
                    $didx = COUNT($GLOBALS['debug']['queries']);
                    $GLOBALS['debug']['queries'][$didx] = array( 'query' => $sqlStr,
                                                                 'time'  => 0
                                                                );
                }
            }

            $result = pg_query_params($pgsql_db, $sqlStr, $params);
        }

        /* Parse the Result If We Have One */
        if ( $result ) {
            while ($row = pg_fetch_row($result)) {
                $rr = array();
                foreach ( $row as $k=>$val ) {
                    switch ( pg_field_type($result, $k) ) {
                        case 'boolean':
                        case 'bool':
                        case 'bit':
                            $val = BoolYN(YNBool($val));
                            break;

                        case 'timestampz':
                        case 'timestamp':
                        case 'timetz':
                            $val = strtotime($val);
                            break;

                        default:
                            /* Do Nothing */
                    }
                    $rr[pg_field_name($result, $k)] = $val;
                }

                $rVal[] = $rr;
            }

            /* Clear the Result from Memory */
            pg_free_result($result);

            // Record the Ops Time (if required)
            if ( defined('DEBUG_ENABLED') ) {
                if ( YNBool(DEBUG_ENABLED) ) {
                    $quntil = getMicroTime();
                    $ops = round(($quntil - $qstart), 6);
                    if ( $ops < 0 ) { $ops *= -1; }

                    $GLOBALS['debug']['queries'][$didx]['time'] = $ops;
                }
            }

            /* Save the Results into Memory */
            setGlobalObject($hash, $rVal);

        } else {
            setGlobalObject('sql_last_error', pg_last_error($pgsql_db));
            writeNote("doPgSQLQuery Error :: " . pg_last_error($pgsql_db), true );
            writeNote("doPgSQLQuery Query :: $sqlStr", true );
        }

        /* Return the Array of Details */
        return $rVal;
    }

    /**
     * Function Queries the Required Database and Returns the values as an array
     */
    function doMySQLQuery($sqlStr, $params = array(), $dbname = '') {
        /* Validate the Database Name */
        if ( NoNull($dbname) == '' && defined('DB_NAME') ) { $dbname = DB_NAME; }

        /* If We Have Nothing, Return Nothing */
        if ( NoNull($sqlStr) == '' ) { return false; }
        $hash = sha1($sqlStr);

        /* Check to see if this query has been run once before and, if so, return the cached result */
        $data = getGlobalObject($hash);
        if ( $data !== false ) { return $data; }

        $GLOBALS['Perf']['queries'] = nullInt($GLOBALS['Perf']['queries']);
        $GLOBALS['Perf']['queries']++;
        $qstart = getMicroTime();
        $result = false;
        $data = array();
        $didx = 0;
        $r = 0;

        /* Do Not Proceed If We Don't Have SQL Settings */
        if ( defined('DB_HOST') === false ) {
            writeNote("doMySQLQuery Connection Error :: No DB_HOST defined", true);
            return false;
        }

        /* Determine Which Database is Required, and Connect If We Don't Already Have a Connection */
        if ( !$mysql_db ) {
            $mysql_db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, $dbname);
            if ( !$mysql_db || mysqli_connect_errno() ) {
                writeNote("doMySQLQuery Connection Error :: " . mysqli_connect_error(), true);
                return false;
            }
            mysqli_set_charset($mysql_db, DB_CHARSET);
        }

        /* If We Have a Good Connection, Go! */
        if ( $mysql_db ) {
            /* If We're In Debug, Capture the SQL Query */
            if ( defined('DEBUG_ENABLED') ) {
                if ( YNBool(DEBUG_ENABLED) ) {
                    if ( array_key_exists('debug', $GLOBALS) === false ) {
                        $GLOBALS['debug'] = array();
                        $GLOBALS['debug']['queries'] = array();
                    }
                    $didx = COUNT($GLOBALS['debug']['queries']);
                    $GLOBALS['debug']['queries'][$didx] = array( 'query' => $sqlStr,
                                                                 'time'  => 0
                                                                );
                }
            }

            /* Try to run the query */
            try {
                $result = mysqli_query($mysql_db, $sqlStr);

            } catch (mysqli_sql_exception $e) {
                writeNote("doMySQLQuery Error :: " . mysqli_errno($mysql_db) . " | " . mysqli_error($mysql_db), true );
                writeNote("doMySQLQuery Query :: $sqlStr", true );
            }
        }

        /* Parse the Result If We Have One */
        if ( $result ) {
            $finfo = mysqli_fetch_fields($result);
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $arr_row = array();
                foreach ( $finfo as $col ) {
                    $name = NoNull($col->name);
                    if ( mb_substr($name, 0, 3) == 'is_' ) {
                        $arr_row[$name] = YNBool($row[$name]);
                    } else {
                        $arr_row[$name] = $row[$name];
                    }
                }
                $data[] = $arr_row;
            }

            /* Close the MySQL Connection */
            mysqli_free_result($result);

            /* Record the Ops Time (if required) */
            if ( defined('DEBUG_ENABLED') ) {
                if ( YNBool(DEBUG_ENABLED) ) {
                    $quntil = getMicroTime();
                    $ops = round(($quntil - $qstart), 6);
                    if ( $ops < 0 ) { $ops *= -1; }

                    $GLOBALS['debug']['queries'][$didx]['time'] = $ops;
                }
            }

            /* Save the Results into Memory */
            setGlobalObject($hash, $data);
        }

        /* Return the array of details or an unhappy boolean */
        if ( is_array($data) && count($data) > 0 ) { return $data; }
        return false;
    }

    /**
     * Function Executes a SQL String against the Required Database and Returns a boolean response.
     */
    function doMySQLExecute($sqlStr, $params = array(), $dbname = '') {
        if ( defined('SQL_SPLITTER') === false ) { define('SQL_SPLITTER', '[||]'); }
        $GLOBALS['Perf']['queries'] = nullInt($GLOBALS['Perf']['queries']);
        $sqlQueries = array();
        $rVal = -1;

        /* Do Not Proceed If We Don't Have SQL Settings */
        if ( defined('DB_HOST') === false ) {
            writeNote("doMySQLQuery Connection Error :: No DB_HOST defined", true);
            return false;
        }
        if ( NoNull($dbname) == '' && defined('DB_NAME') ) { $dbname = DB_NAME; }

        /* Strip Out The SQL Queries (If There Are Many) */
        if ( strpos($sqlStr, SQL_SPLITTER) > 0 ) {
            $sqlQueries = explode(SQL_SPLITTER, $sqlStr);
        } else {
            $sqlQueries[] = $sqlStr;
        }

        /* If We Don't Already Have a Connection to the Write Server, Make One */
        if ( !$mysql_db ) {
            $mysql_db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, $dbname);
            if ( !$mysql_db || mysqli_connect_errno() ) {
                writeNote("doSQLExecute Connection Error :: " . mysqli_connect_error(), true);
                return $rVal;
            }
            mysqli_set_charset($mysql_db, DB_CHARSET);
        }

        /* Execute Each Statement */
        if ( $mysql_db ) {
            foreach ( $sqlQueries as $sqlStatement ) {
                if ( NoNull($sqlStatement) != "" ) {
                    $GLOBALS['Perf']['queries']++;
                    if ( !mysqli_query($mysql_db, $sqlStatement) ) {
                        switch ( mysqli_errno($mysql_db) ) {
                            case 1213:
                                /* Deadlock Found. Retry. */
                                if ( !mysqli_query($mysql_db, $sqlStatement) ) { break; }

                            default:
                                writeNote("doMySQLExecute Error (WriteDB) :: " . mysqli_errno($mysql_db) . " | " . mysqli_error($mysql_db), true);
                                writeNote("doMySQLExecute Query :: $sqlStatement", true);
                        }
                    }
                }
            }

            /* Get the Insert ID or the Number of Affected Rows */
            $rVal = mysqli_insert_id( $mysql_db );
            if ( $rVal == 0 ) { $rVal = mysqli_affected_rows( $mysql_db ); }
        }

        /* Return the Insert ID or an Unhappy Integer */
        return $rVal;
    }

    /**
     *  Function Closes a Persistent SQL Connection If Exists
     */
    function closePersistentSQLConn() {
        if ( $mysql_db ) { mysqli_close($mysql_db); }
        if ( $pgsql_db ) { pg_close($pgsql_db); }
    }

    /**
     *  Function Returns a Completed SQL Statement based on the SQL String and Parameter Array Provided
     */
    function prepSQLQuery($sqlStr, $ReplStr = array(), $Minify = false) {
        $rVal = str_replace(array_keys($ReplStr), array_values($ReplStr), $sqlStr);
        if ( is_bool($Minify) !== true ) { $Minify = YNBool($Minify); }

        /* Strip all the white space if required */
        if ( $Minify ) {
            for ( $i = 0; $i < 5; $i++ ) {
                $rVal = str_replace(array("\r\n", "\r", "\n", "\t", '  '), ' ', $rVal);
            }
            $rVal = str_replace('> <', '><', $rVal);
        }

        /* Return the prepped SQL Query */
        return NoNull($rVal);
    }

    /**
     * Function returns a SQL-safe String
     */
    function sqlScrub( $str ) {
        if ( mb_strlen(NoNull($str)) <= 0 ) { return ''; }
        $rVal = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $str);

        if( is_array($str) ) { return array_map(__METHOD__, $str); }
        if(!empty($str) && is_string($str)) {
            $ReplStr = array( '\\' => '\\\\',   "\0" => '\\0',      "\n" => "\\n",
                              "\r" => "\\n",    "\t" => '\\t',      "'" => "''",
                              '"' => '\\"',     "\x1a" => '\\Z',
                             );
            $rVal = str_replace(array_keys($ReplStr), array_values($ReplStr), $str);
        }

        // Return the Scrubbed String
        return NoNull($rVal);
    }

    /***********************************************************************
     *                          Conversion Functions
     *
     *   The following code is used by Alpha<->Int Functions
     *
     ***********************************************************************/
    /**
     * Function returns the Character Table Required for Alpha->Int Conversions
     */
    function getChrTable() {
        return array('jNn7uY2ETd6JUOSVkAMyhCt3qw1WcpIv5P0LK4DfXFzbl8xemrB9RHGgoiQZsa',
                     '3tDL8pPwScIbnE0gsjvK2QxoVhrf17eG6yM4BJkOTXWzNduiFHZqAC9UmY5Ral',
                     'JyADsUFtkjzXqLG0SMb1egmhw8Q6cETpVfI5xdl42H9vROKYuNiWonPC73rBaZ',
                     '2ZTSUXQFPgK7nwOi0N5s8z1rjqC4E6VHkRypo3J9hdBImxAGltWeMvYfLuDbca',
                     '8NlPjJIHE7naFyewTqmdsK5YQhU9gp6WRXBVGouMDALtr0c324bzCSfOv1iZkx',
                     'OPwcLs1zy69KpNjm0hFGaEte5UIrfVBXZYQWv27S34MJHkTbdgDARlConqx8iu'
                    );
    }

    /**
     * Function converts an AlphaNumeric Value to an Integer based on the
     *      static characters passed.
     */
    function alphaToInt($alpha) {
        $chrTable = getChrTable();

        // Perform Some Basic Error Checking
        if (!$alpha) { return null; }
        if ( strlen($alpha) != 6 ) { return 0; }

        $radic = strlen($chrTable[0]);
        $offset = strpos($chrTable[0], $alpha[0]);
        if ($offset === false) return false;
        $value = 0;

        for ($i=1; $i < strlen($alpha); $i++) {
            if ($i >= count($chrTable)) break;

            $pos = (strpos($chrTable[$i], $alpha[$i]) + $radic - $offset) % $radic;
            if ($pos === false) return false;

            $value = $value * $radic + $pos;
        }

        $value = $value * $radic + $offset;

        // Return the Integer Value
        return $value;
    }

    /**
     * Function converts an Integer to an AlphaNumeric Value based on the
     *      static characters passed.
     */
    function intToAlpha($num) {
        if ( nullInt( $num ) <= 0 ) { return ""; }

        $chrTable = getChrTable();
        $digit = 5;
        $radic = strlen( $chrTable[0] );
        $alpha = '';

        $num2 = floor($num / $radic);
        $mod = $num - $num2 * $radic;
        $offset = $mod;

        for ($i=0; $i<$digit; $i++) {
            $mod = $num2 % $radic;
            $num2 = ($num2 - $mod) / $radic;

            $alpha = $chrTable[ $digit-$i ][ ($mod + $offset )% $radic ] . $alpha;
        }
        $alpha = $chrTable[0][ $offset ] . $alpha;

        // Return the AlphaNumeric Value
        return $alpha;
    }

    /***********************************************************************
     *  HTTP Asyncronous Calls
     ***********************************************************************/
    /**
     *  Function Calls a URL Asynchronously, and Returns Nothing
     *  Source: http://stackoverflow.com/questions/962915/how-do-i-make-an-asynchronous-get-request-in-php
     */
    function curlPostAsync( $url, $params ) {
        foreach ($params as $key => &$val) {
            if (is_array($val)) $val = implode(',', $val);
            $post_params[] = $key.'='.urlencode($val);
        }
        $post_string = implode('&', $post_params);
        $parts=parse_url($url);

        $fp = fsockopen($parts['host'], isset($parts['port'])?$parts['port']:80, $errno, $errstr, 30);

        $out = "POST ".$parts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out.= "Content-Length: ".strlen($post_string)."\r\n";
        $out.= "Connection: Close\r\n\r\n";
        if (isset($post_string)) $out.= $post_string;

        fwrite($fp, $out);
        fclose($fp);
    }


    /**
     *  Function Calls a URL Asynchronously, and Returns Nothing
     *  Source: http://codeissue.com/issues/i64e175d21ea182/how-to-make-asynchronous-http-calls-using-php
     */
    function httpPostAsync( $url, $paramstring, $method = 'get', $timeout = '30', $returnresponse = false ) {
        $method = strtoupper($method);
        $urlParts = parse_url($url);
        $fp = fsockopen($urlParts['host'],
                        isset( $urlParts['port'] ) ? $urlParts['port'] : 80,
                        $errno, $errstr, $timeout);
        $rVal = false;

        //If method="GET", add querystring parameters
        if ($method='GET')
            $urlParts['path'] .= '?'.$paramstring;

        $out = "$method ".$urlParts['path']." HTTP/1.1\r\n";
        $out.= "Host: ".$urlParts['host']."\r\n";
        $out.= "Connection: Close\r\n";

        //If method="POST", add post parameters in http request body
        if ($method='POST') {
            $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out.= "Content-Length: ".strlen($paramstring)."\r\n\r\n";
            $out.= $paramstring;
        }

        fwrite($fp, $out);

        //Wait for response and return back response only if $returnresponse=true
        if ( $returnresponse ) {
            $rVal = stream_get_contents($fp);
        } else {
            $rVal = true;
        }

        // Close the Connection
        fclose($fp);

        // Return the Result
        return $rVal;
    }

    /** ***************************************************************************************** *
     *  External JSON API Functions
     ** ***************************************************************************************** */
    /**
     *  Function Makes a Request to the CloudFlare API
     */
    function doCloudFlareRequest( $EndPoint, $apiKey, $cfMail, $Type = "GET", $Variables = array() ) {
        if ( defined('CLOUDFLARE_API_URL') === false ) { return false; }
        if ( NoNull($EndPoint) == "" ) { return false; }
        if ( NoNull($apiKey) == "" ) { return false; }
        if ( NoNull($cfMail) == "" ) { return false; }

        $url = str_replace('//', '/', CLOUDFLARE_API_URL . $EndPoint);

        // If We Have a GET Request and Variables, Build the HTTP Query
        if ( $Type == "GET" && is_array($Variables) && count($Variables) > 0 ) {
            $url .= '?' . http_build_query( $Variables );
        }

        $CurlHead = array( 'Content-Type: application/json',
                           'X-Auth-Email: ' . $cfMail,
                           'X-Auth-Key: ' . $apiKey,
                          );

        // Perform the CURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ( in_array($Type, array('POST', 'PUT')) ) {
            curl_setopt($ch, CURLOPT_HEADER, false);
            switch ( $Type ) {
                case 'POST':
                    curl_setopt($ch, CURLOPT_POST, true);
                    break;

                case 'PUT':
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                    break;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($Variables, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $CurlHead);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error ($ch);
        $result = curl_exec($ch);
        curl_close($ch);

        // Return the Data In the Proper Format If We Have It
        $json = json_decode($result);
        if ( is_object($json) ) {
            $rslt = objectToArray($json);

            if ( $ReturnFullSet ) {
                return $rslt;
            } else {
                if ( is_array($rslt['result']) && count($rslt['result']) > 0 ) {
                    return $rslt['result'];
                } else {
                    return $rslt;
                }
            }
        }

        // If We're Here, There's a Problem
        return false;
    }

    /***********************************************************************
     *  Resource Caching
     ***********************************************************************/
    function saveCache( $SiteID, $Name, $data ) {
        $TypeDIR = TMP_DIR . '/' . intToAlpha($SiteID);

        if ( checkDIRExists( TMP_DIR ) && checkDIRExists( $TypeDIR ) ) {
            $tmpFile = "$TypeDIR/$Name.data";
            $fh = fopen($tmpFile, 'w');
            fwrite($fh, serialize($data));
            fclose($fh);
        }

        // Return a Happy Boolean
        return true;
    }

    function readCache( $SiteID, $Name ) {
        $TypeDIR = TMP_DIR . '/' . intToAlpha($SiteID);
        $rVal = false;

        if ( checkDIRExists( TMP_DIR ) && checkDIRExists( $TypeDIR ) ) {
            $tmpFile = "$TypeDIR/$Name.data";
            if ( file_exists($tmpFile) ) {
                $age = filemtime($tmpFile);

                if ( !$age or ((time() - $age) > CACHE_EXPY) ) { return false; }
                $data = file_get_contents( $tmpFile );
                if ( NoNull($data) != '' ) { $rVal = unserialize($data); }
            }
        }

        // Return the Cached Data or an Unhappy Boolean
        return $rVal;
    }

    /***********************************************************************
     *  Server Identification Functions
     ***********************************************************************/
    function getServerGUID() {
        $fileName = CONF_DIR . "/server.inc";
        $rVal = '';

        if ( file_exists( $fileName ) ) {
            $data = file_get_contents( $fileName );
            $rVal = NoNull($data);
        }

        // If We Don't Have Data, Create a Server Record
        if ( $rVal == '' ) {
            $ReplStr = array( '[OS_NAME]'     => php_uname('s'),
                              '[OS_HOST]'     => php_uname('n'),
                              '[OS_RELEASE]'  => php_uname('r'),
                              '[OS_VERSION]'  => php_uname('v'),
                              '[OS_TYPE]'     => php_uname('m'),
                              '[SHA_SALT]'    => SHA_SALT,

                              '[SERVER_ADDR]' => sqlScrub(NoNull($_SERVER['SERVER_ADDR'], $_SERVER['LOCAL_ADDR'])),
                             );
            $sqlStr = readResource(SQL_DIR . '/system/createServerRecord.sql', $ReplStr);
            $rslt = doSQLExecute($sqlStr);

            // Get the GUID
            $sqlStr = readResource(SQL_DIR . '/system/getServerRecord.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $guid = '';

                foreach ( $rslt as $Row ) {
                    $guid = NoNull($Row['guid']);
                }

                // Write the Server GUID file
                if ( $guid != '' ) {
                    $fh = fopen($fileName, 'w');
                    fwrite($fh, $guid);
                    fclose($fh);

                    $rVal = $guid;
                }
            }
        }

        // Return the GUID
        return $rVal;
    }

    /***********************************************************************
     *  Stats Recording
     ***********************************************************************/
    function recordUsageStat( $data, $http_code, $message = '' ) {
        $precision = 6;
        $GLOBALS['Perf']['app_f'] = getMicroTime();
        $App = round(( $GLOBALS['Perf']['app_f'] - $GLOBALS['Perf']['app_s'] ), $precision);
        $SqlOps = nullInt( $GLOBALS['Perf']['queries'] ) + 1;
        $Referer = str_replace($data['HomeURL'], '', NoNull($_SERVER['HTTP_REFERER']));
        $Agent = parse_user_agent();
        $ip = getVisitorIPv4();

        // Set the Values and Run the SQL Query
        $ReplStr = array( '[SITE_ID]'    => nullInt($GLOBALS['site_id'], $data['site_id']),
                          '[TOKEN_ID]'   => nullInt($data['_token_id']),
                          '[HTTP_CODE]'  => nullInt($http_code),
                          '[REQ_TYPE]'   => sqlScrub($data['ReqType']),
                          '[REQ_URI]'    => sqlScrub($data['ReqURI']),
                          '[REFERER]'    => sqlScrub($Referer),
                          '[IP_ADDR]'    => sqlScrub($ip),
                          '[AGENT]'      => sqlScrub($_SERVER['HTTP_USER_AGENT']),
                          '[UAPLATFORM]' => sqlScrub($Agent['platform']),
                          '[UABROWSER]'  => sqlScrub($Agent['browser']),
                          '[UAVERSION]'  => sqlScrub($Agent['version']),
                          '[RUNTIME]'    => $App,
                          '[SQL_OPS]'    => $SqlOps,
                          '[MESSAGE]'    => sqlScrub($message),
                         );
        $sqlStr = readResource(SQL_DIR . '/system/setUsageStat.sql', $ReplStr, true);
        $isOK = doSQLExecute($sqlStr);

        if ( defined('DEBUG_ENABLED') ) {
            if ( DEBUG_ENABLED == 1 ) {
                if ( is_array($GLOBALS['debug']) ) {
                    $json = json_encode($GLOBALS['debug'], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
                    writeDebug($json, 'sqlops');
                }
            }
        }

        // Return the [UsageStats].[id] Value
        return $isOK;
    }

    /***********************************************************************
     *  Output Formatting Functions
     ***********************************************************************/
    /**
     *  Function formats the result in the appropriate format and returns the data
     */
    function formatResult( $data, $sets, $type = 'text/html', $status = 200, $meta = false, $more = false ) {
        $validTypes = array('application/json', 'application/octet-stream', 'application/rss+xml', 'application/xml', 'text/html');
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1');
        $szOrigin = NoNull($_SERVER['HTTP_ORIGIN'], '*');
        $appType = NoNull($type, 'text/html');

        if ( in_array($appType, $validTypes) === false ) {
            $appType = ($sets['Route'] == 'api') ? 'application/json' : 'text/html';
        }

        // If We Have an Array, Convert it to the Appropriate Format
        if ( is_array($data) || is_bool($data) ) {
            switch ( $appType ) {
                case 'application/json':
                    if ( is_bool($data) || (is_string($data) && strlen($data) <= 0) ) { $data = array(); }
                    $metaText = ((is_array($meta) && count($meta) > 0) ? $meta[count($meta) - 1] : false);
                    $metaList = array();
                    if ( $metaText !== false ) {
                        if ( is_array($meta) ) {
                            foreach ( $meta as $msg ) {
                                if ( $msg != $metaText && in_array($msg, $metaList) === false ) {
                                    if ( is_array($metaList) === false ) { $metaList = array(); }
                                    $metaList[] = $msg;
                                }
                            }
                        }
                    }
                    if ( is_array($metaList) === false ) { $metaList = false; }
                    if ( is_array($metaList) && count($metaList) <= 0 ) { $metaList = false; }

                    $json = array( 'meta' => array( 'code' => $status,
                                                    'text' => $metaText,
                                                    'list' => $metaList
                                                   ),
                                   'data' => $data
                                  );
                    if ( $more !== false ) { $json['meta']['more'] = YNBool($more); }
                    $data = json_encode($json, JSON_UNESCAPED_UNICODE);
                    break;

                default:
                    /* Do Nothing */
            }
        }

        // If This is a Pre-Flight Request, Ensure the Status is Valid
        if ( NoNull($_SERVER['REQUEST_METHOD']) == 'OPTIONS' ) { $status = 200; }

        // Return the Data in the Requested Format
        header($protocol . ' ' . nullInt($status) . ' ' . getHTTPCode($status) );
        header("Content-Type: " . $appType);
        header("Access-Control-Allow-Origin: $szOrigin");
        header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Credentials: true");
        header("P3P: CP=\"ALL IND DSP COR ADM CONo CUR CUSo IVAo IVDo PSA PSD TAI TELo OUR SAMo CNT COM INT NAV ONL PHY PRE PUR UNI\"");
        header("X-Perf-Stats: " . getRunTime('header'));
        header("X-SHA1-Hash: " . sha1( $data ));
        header("X-Content-Length: " . mb_strlen($data));

        // Record the Usage Statistic
        recordUsageStat( $sets, $status, ((is_array($meta) && count($meta) > 0) ? $meta[count($meta) - 1] : '') );

        // Close the Persistent SQL Connection If Needs Be
        closePersistentSQLConn();

        exit( $data );
    }

    /**
     *  Function Sends a resource to the browser if it exists
     */
    function sendResourceFile( $srcPath, $fileName, $mimeType, $sets ) {
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1');
        $szOrigin = NoNull($_SERVER['HTTP_ORIGIN'], '*');
        $status = 200;

        /* Determine whether the file should be downloaded or presented */
        $disposition = 'attachment';
        $group = strtok($mimeType, '/');
        if ( in_array($group, array('audio', 'image', 'video', 'text')) ) { $disposition = 'inline'; }

        /* If the file exists, return it */
        if ( file_exists($srcPath) ) {
            $name = basename($fileName);

            header($protocol . ' ' . nullInt($status) . ' ' . getHTTPCode($status) );
            header("Access-Control-Allow-Origin: $szOrigin");
            header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");
            header("Access-Control-Allow-Credentials: true");
            header("P3P: CP=\"ALL IND DSP COR ADM CONo CUR CUSo IVAo IVDo PSA PSD TAI TELo OUR SAMo CNT COM INT NAV ONL PHY PRE PUR UNI\"");
            header("Content-Type: $mimeType");
            header("Content-Disposition: $disposition; filename=$name");
            header("Content-Length: " . filesize($srcPath));
            readfile($srcPath);

        } else {
            return false;
        }

        /* Record the Usage Statistic and exit */
        recordUsageStat( $sets, $status );
        exit();
    }

    /**
     *  Function Sends a Zip file to the browser if it exists
     */
    function sendZipFile( $fileName ) {
        $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1');
        $status = 200;

        if ( file_exists($fileName) ) {
            $name = basename($fileName);

            header($protocol . ' ' . nullInt($status) . ' ' . getHTTPCode($status) );
            header("Access-Control-Allow-Origin: $szOrigin");
            header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");
            header("Access-Control-Allow-Credentials: true");
            header("P3P: CP=\"ALL IND DSP COR ADM CONo CUR CUSo IVAo IVDo PSA PSD TAI TELo OUR SAMo CNT COM INT NAV ONL PHY PRE PUR UNI\"");
            header("Content-Type: application/zip");
            header("Content-Disposition: attachment; filename=$name");
            header("Content-Length: " . filesize($fileName));
            readfile($fileName);

        } else {
            $status = 404;

            header($protocol . ' ' . nullInt($status) . ' ' . getHTTPCode($status) );
            header("Access-Control-Allow-Origin: $szOrigin");
            header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");
            header("Access-Control-Allow-Credentials: true");
            header("P3P: CP=\"ALL IND DSP COR ADM CONo CUR CUSo IVAo IVDo PSA PSD TAI TELo OUR SAMo CNT COM INT NAV ONL PHY PRE PUR UNI\"");
        }

        exit();
    }

    /**
     *  Function Returns the Appropriate HTTP Reponse Code
     */
    function getHTTPCode( $code ) {
        switch ( nullInt($code) ) {
            case 100: return 'Continue'; break;
            case 101: return 'Switching Protocols'; break;
            case 200: return 'OK'; break;
            case 201: return 'Created'; break;
            case 202: return 'Accepted'; break;
            case 203: return 'Non-Authoritative Information'; break;
            case 204: return 'No Content'; break;
            case 205: return 'Reset Content'; break;
            case 206: return 'Partial Content'; break;
            case 218: return 'This Is Fine'; break;
            case 300: return 'Multiple Choices'; break;
            case 301: return 'Moved Permanently'; break;
            case 302: return 'Moved Temporarily'; break;
            case 303: return 'See Other'; break;
            case 304: return 'Not Modified'; break;
            case 305: return 'Use Proxy'; break;
            case 400: return 'Bad Request'; break;
            case 401: return 'Unauthorized'; break;
            case 402: return 'Payment Required'; break;
            case 403: return 'Forbidden'; break;
            case 404: return 'Not Found'; break;
            case 405: return 'Method Not Allowed'; break;
            case 406: return 'Not Acceptable'; break;
            case 407: return 'Proxy Authentication Required'; break;
            case 408: return 'Request Time-out'; break;
            case 409: return 'Conflict'; break;
            case 410: return 'Gone'; break;
            case 411: return 'Length Required'; break;
            case 412: return 'Precondition Failed'; break;
            case 413: return 'Request Entity Too Large'; break;
            case 414: return 'Request-URI Too Large'; break;
            case 415: return 'Unsupported Media Type'; break;
            case 420: return 'Enhance Your Calm'; break;
            case 500: return 'Internal Server Error'; break;
            case 501: return 'Not Implemented'; break;
            case 502: return 'Bad Gateway'; break;
            case 503: return 'Service Unavailable'; break;
            case 504: return 'Gateway Time-out'; break;
            case 505: return 'HTTP Version not supported'; break;
            default:
                return 'Unknown HTTP Response';
        }
    }

    /**
     *  Function Returns the Run Time and Number of SQL Queries Performed to Fulfill Request
     */
    function getRunTime( $format = 'html' ) {
        if ( defined('USE_REDIS') === false ) { define('USE_REDIS', 0); }

        $precision = 6;
        $GLOBALS['Perf']['app_f'] = getMicroTime();
        $App = round(( $GLOBALS['Perf']['app_f'] - $GLOBALS['Perf']['app_s'] ), $precision);
        $SQL = nullInt( $GLOBALS['Perf']['queries'] );

        /* If the application ran in "no time", return a zero */
        if ( $GLOBALS['Perf']['app_f'] <= ($GLOBALS['Perf']['app_s'] + 0.0001) ) { $App = 0; }

        $cache_out = '';
        if ( YNBool(USE_REDIS) ) {
            $cache_out = nullInt($GLOBALS['Perf']['redis_sets']) . ' Redis Write' . ((nullInt($GLOBALS['Perf']['redis_sets']) != 1) ? 's' : '') . ' and ' .
                         nullInt($GLOBALS['Perf']['redis_gets']) . ' Redis Read' . ((nullInt($GLOBALS['Perf']['redis_gets']) != 1) ? 's' : '');
        } else {
            $cache_out = nullInt($GLOBALS['Perf']['cache_sets']) . ' Temp Write' . ((nullInt($GLOBALS['Perf']['cache_sets']) != 1) ? 's' : '') . ' and ' .
                         nullInt($GLOBALS['Perf']['cache_gets']) . ' Temp Read' . ((nullInt($GLOBALS['Perf']['cache_gets']) != 1) ? 's' : '');
        }

        $lblSecond = ( $App == 1 ) ? "Second" : "Seconds";
        $lblQuery  = ( $SQL == 1 ) ? "Query"  : "Queries";

        // Reutrn the Run Time String
        return ($format == 'html') ? "    <!-- Page generated in roughly: $App $lblSecond, with $SQL SQL $lblQuery, $cache_out -->" : "$App $lblSecond | $SQL SQL $lblQuery | " . $cache_out;
    }

    /***********************************************************************
     *  IP Filtering Functions
     ***********************************************************************/
    /**
     *  Function Writes the IP Record to a Flatfile Where It Can Be Imported and
     *      Analysed Later.
     */
    function writeIPRecord( $IPAddress ) {
        if ( IPFILTER_ENABLED != 0 ) {
            $UsrAgnt = sqlScrub($_SERVER['HTTP_USER_AGENT']);
            $SiteURL = sqlScrub($_SERVER['SERVER_NAME']);
            $ReqURI = sqlScrub($_SERVER['REQUEST_URI']);
            $v = get_browser(null, true);

            date_default_timezone_set('utc');
            $UnixTime = time();
            $yW = date('yW', $UnixTime);
            $log_file = "logs/filter-$yW.log";

            $fh = fopen($log_file, 'a');
            $stringData = "('$IPAddress', $UnixTime, '$SiteURL', '$ReqURI', " .
                           "'" . sqlScrub($v['parent']) . "', '" . sqlScrub($v['platform']) . "', '$UsrAgnt'),\n";
            fwrite($fh, $stringData);
            fclose($fh);
        }
    }

    /***********************************************************************
     *  Browser Agent Functions
     ***********************************************************************/
    function parse_user_agent() {
        $platform = null;
        $browser  = null;
        $version  = null;
        $empty = array( 'platform' => $platform, 'browser' => $browser, 'version' => $version );
        $u_agent = false;

        if( isset($_SERVER['HTTP_USER_AGENT']) ) { $u_agent = $_SERVER['HTTP_USER_AGENT']; } else { return $empty; }
        if( !$u_agent ) return $empty;

        if( preg_match('/\((.*?)\)/im', $u_agent, $parent_matches) ) {
            preg_match_all('/(?P<platform>BB\d+;|Android|CrOS|Tizen|iPhone|iPad|iPod|Linux|Macintosh|Windows(\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(New\ )?Nintendo\ (WiiU?|3?DS)|Xbox(\ One)?)
                    (?:\ [^;]*)?
                    (?:;|$)/imx', $parent_matches[1], $result, PREG_PATTERN_ORDER);
            $priority = array( 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'CrOS', 'X11' );
            $result['platform'] = array_unique($result['platform']);
            if( count($result['platform']) > 1 ) {
                if( $keys = array_intersect($priority, $result['platform']) ) {
                    $platform = reset($keys);
                } else {
                    $platform = $result['platform'][0];
                }
            } elseif( isset($result['platform'][0]) ) {
                $platform = $result['platform'][0];
            }
        }
        if( $platform == 'linux-gnu' || $platform == 'X11' ) {
            $platform = 'Linux';
        } elseif( $platform == 'CrOS' ) {
            $platform = 'Chrome OS';
        }
        preg_match_all('%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|Safari|MSIE|Trident|AppleWebKit|TizenBrowser|Chrome|
                    Vivaldi|IEMobile|Opera|OPR|Silk|Midori|Edge|CriOS|UCBrowser|
                    Baiduspider|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
                    Valve\ Steam\ Tenfoot|
                    NintendoBrowser|PLAYSTATION\ (\d|Vita)+)
                    (?:\)?;?)
                    (?:(?:[:/ ])(?P<version>[0-9A-Z.]+)|/(?:[A-Z]*))%ix',
            $u_agent, $result, PREG_PATTERN_ORDER);
        // If nothing matched, return null (to avoid undefined index errors)
        if( !isset($result['browser'][0]) || !isset($result['version'][0]) ) {
            if( preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)(/(?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result) ) {
                return array( 'platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null : null );
            }
            return $empty;
        }
        if( preg_match('/rv:(?P<version>[0-9A-Z.]+)/si', $u_agent, $rv_result) ) {
            $rv_result = $rv_result['version'];
        }
        $browser = $result['browser'][0];
        $version = $result['version'][0];
        $lowerBrowser = array_map('strtolower', $result['browser']);
        $find = function ( $search, &$key, &$value = null ) use ( $lowerBrowser ) {
            $search = (array)$search;
            foreach( $search as $val ) {
                $xkey = array_search(strtolower($val), $lowerBrowser);
                if( $xkey !== false ) {
                    $value = $val;
                    $key   = $xkey;
                    return true;
                }
            }
            return false;
        };
        $key = 0;
        $val = '';
        if( $browser == 'Iceweasel' ) {
            $browser = 'Firefox';
        } elseif( $find('Playstation Vita', $key) ) {
            $platform = 'PlayStation Vita';
            $browser  = 'Browser';
        } elseif( $find(array( 'Kindle Fire', 'Silk' ), $key, $val) ) {
            $browser  = $val == 'Silk' ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if( !($version = $result['version'][$key]) || !is_numeric($version[0]) ) {
                $version = $result['version'][array_search('Version', $result['browser'])];
            }
        } elseif( $find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS' ) {
            $browser = 'NintendoBrowser';
            $version = $result['version'][$key];
        } elseif( $find('Kindle', $key, $platform) ) {
            $browser = $result['browser'][$key];
            $version = $result['version'][$key];
        } elseif( $find('OPR', $key) ) {
            $browser = 'Opera Next';
            $version = $result['version'][$key];
        } elseif( $find('Opera', $key, $browser) ) {
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif( $find(array( 'IEMobile', 'Edge', 'Midori', 'Vivaldi', 'Valve Steam Tenfoot', 'Chrome' ), $key, $browser) ) {
            $version = $result['version'][$key];
        } elseif( $browser == 'MSIE' || ($rv_result && $find('Trident', $key)) ) {
            $browser = 'MSIE';
            $version = $rv_result ?: $result['version'][$key];
        } elseif( $find('UCBrowser', $key) ) {
            $browser = 'UC Browser';
            $version = $result['version'][$key];
        } elseif( $find('CriOS', $key) ) {
            $browser = 'Chrome';
            $version = $result['version'][$key];
        } elseif( $browser == 'AppleWebKit' ) {
            if( $platform == 'Android' && !($key = 0) ) {
                $browser = 'Android Browser';
            } elseif( strpos($platform, 'BB') === 0 ) {
                $browser  = 'BlackBerry Browser';
                $platform = 'BlackBerry';
            } elseif( $platform == 'BlackBerry' || $platform == 'PlayBook' ) {
                $browser = 'BlackBerry Browser';
            } else {
                $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
            }
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif( $pKey = preg_grep('/playstation \d/i', array_map('strtolower', $result['browser'])) ) {
            $pKey = reset($pKey);
            $platform = 'PlayStation ' . preg_replace('/[^\d]/i', '', $pKey);
            $browser  = 'NetFront';
        }
        return array( 'platform' => utf8_encode($platform) ?: null,
                      'browser'  => utf8_encode($browser) ?: null,
                      'version'  => utf8_encode($version) ?: null
                     );
    }

    /***********************************************************************
     *  Debug & Error Reporting Functions
     ***********************************************************************/
    /**
     * Function records a note to the File System when DEBUG_ENABLED > 0
     *      Note: Timezone is currently set to Asia/Tokyo, but this should
     *            be updated to follow the user's time zone.
     */
    function writeNote( $Message, $doOverride = false ) {
        if ( defined('DEBUG_ENABLED') === false ) { return; }
        if ( DEBUG_ENABLED != 0 || $doOverride === true ) {
            date_default_timezone_set(TIMEZONE);
            $ima = time();
            $yW = date('yW', $ima);
            $log_file = LOG_DIR . "/debug-$yW.log";

            $fh = fopen($log_file, 'a');
            $ima_str = date("F j, Y h:i:s A", $ima );
            $stringData = "[$ima_str] | Note: $Message \n";
            fwrite($fh, $stringData);
            fclose($fh);
        }
    }

    function writeDebug( $text, $prefix = 'debug' ) {
        if ( defined('DEBUG_ENABLED') === false ) { return; }
        if ( DEBUG_ENABLED != 0 ) {
            if ( defined('TIMEZONE') === false ) { define('TIMEZONE', 'UTC'); }

            date_default_timezone_set(TIMEZONE);
            $ima = time();
            $log_file = LOG_DIR . "/$prefix-$ima.log";

            $fh = fopen($log_file, 'a');
            $stringData = NoNull($text);
            fwrite($fh, $stringData);
            fclose($fh);
        }
    }

    /**
     * Function formats the Error Message for {Procedure} - Error and Returns it
     */
    function formatErrorMessage( $Location, $Message ) {
        writeNote( "{$Location} - $Message", false );
        return "$Message";
    }

?>