<?php

/**
 * @author Jason F. Irwin
 * @copyright 2016
 * 
 * Class contains the rules and methods called for CDN File Handling
 */
require_once(CONF_DIR . '/config.php');
require_once(CONF_DIR . '/versions.php');
require_once(LIB_DIR . '/cookies.php');
require_once(LIB_DIR . '/functions.php');

class BlueCDN {
    var $settings;

    function __construct() {
        $sets = new cookies;
        $this->settings = $sets->cookies;
        unset( $sets );
    }

    /** ********************************************************************** *
     *  Public Functions
     ** ********************************************************************** */
    public function getFile() {
        if ( !$this->_getFile() ) {
            $html = readResource(FLATS_DIR . "/templates/404.html");
            $this->_returnHTML($html, 404);
        }
    }

    /** ********************************************************************** *
     *  Return Functions
     ** ********************************************************************** */
    /**
     *	Function formats the result in the appropriate format and returns the data
     */
    private function _returnHTML( $html, $Code = 200 ) {
        if ( nullInt($Code) > 0 ) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header($protocol . ' ' . nullInt($Code) . ' ' . $this->_getHTTPCode($Code) );
        }

        $szOrigin = NoNull($_SERVER['HTTP_ORIGIN'], '*');
        header("Content-Type: text/html; charset=UTF-8");
        header("Access-Control-Allow-Origin: $szOrigin");
        header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-NETWORK-ADDRESS, X-DEVICE-ID");
        header("Access-Control-Allow-Credentials: true");
        header("P3P: CP=\"ALL IND DSP COR ADM CONo CUR CUSo IVAo IVDo PSA PSD TAI TELo OUR SAMo CNT COM INT NAV ONL PHY PRE PUR UNI\"");
        header("X-SHA1-Hash: " . sha1($html));
        header("X-Content-Length: " . mb_strlen($html));
        exit( $html );
    }

    /**
     *  Function Returns the Appropriate HTTP Reponse Code
     */
    private function _getHTTPCode( $code ) {
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

    /** ********************************************************************** *
     *  Private Functions
     ** ********************************************************************** */
    private function _getFile() {
        $ResType = strtolower(NoNull($this->settings['PgRoot']));
        $ResFile = NoNull($this->settings['PgSub2'], $this->settings['PgSub1']);
        $VisitIP = getVisitorIPv4();

        // Do We Need to Perform a Resize?
        if ( $ResType == 'textbook' && mb_strpos($ResFile, '_medium') > 0 ) { $ResType = 'medium'; }
        if ( $ResType == 'textbook' && mb_strpos($ResFile, '_thumb') > 0 ) { $ResType = 'thumb'; }

        // Do Not Continue if the Resource Type is Unrecognised
        $Valids = array('avatar', 'check', 'resource', 'stats', 'thumb', 'medium');
        if ( !in_array($ResType, $Valids) ) { return false; }

        // If We Need to Check For Files, Do So
        switch ( strtolower($ResType) ) {
            case 'check':
                return $this->_checkForNewFiles();
                break;
            
            case 'stats':
            require_once(LIB_DIR . '/stats.php');
            $stat = new Stats();
            $data = $stat->collectServerData();
            unset($stat);

            return $data;
            break;
            
            default:
                /* Do Nothing */
        }

        // No Point Continuing Past Here Without a Valid File Thingie
        if ( NoNull($ResFile) == '' ) { return "Invalid Resource Request"; }
        
        // Split the Proper Hash and Type Values
        $fData = explode('.', $ResFile);
        if ( is_array($fData) ) {
            $FileHash = NoNull($fData[0]);
            $FileType = NoNull($fData[1]);
        } else {
            return "Invalid Resource Request";
        }

        // Check if the Requested File Exists
        $ReplStr = array( '[FILE_HASH]' => sqlScrub($FileHash),
                          '[FILE_TYPE]' => sqlScrub($FileType),
                          '[FILE_NAME]' => sqlScrub($ResFile),
                          '[RES_TYPE]'  => sqlScrub($ResType),
                          '[VISIT_IP]'  => sqlScrub($VisitIP),
                         );
        $sqlStr = readResource(SQL_DIR . '/files/getCDNResource.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $fPath = NoNull($Row['filepath']);
                $fName = NoNull($Row['filename']);
                $urlID = nullInt($Row['url_id']);

                // If We're Working with a Thumbnail, Check It Exists
                if ( in_array($ResType, array('thumb', 'medium')) ) {
                    $thumbPath = NoNull($Row['filepath']) . $ResFile;
                    $localPath = $fPath . $fName;
                    $fName = $ResFile;

                    // If the Source Resource Exists but the Thumb Does Not, Generate a Thumb
                    if ( file_exists($localPath) && file_exists($thumbPath) === false ) {
                        $FileMime = mime_content_type($localPath);
                        $FileExt = getFileExtension($localPath);
                        $PixWide = ($ResType == 'medium') ? 1024 : 512;

                        // If the File is of a Proper MimeType, Resize it to 512px Wide
                        if ( $this->_isResizableImage(NoNull($FileMime, $FileExt)) ) {
                            require_once(LIB_DIR . '/images.php');
                            $img = new Images();
                            $img->load($localPath);
                            $img->reduceToWidth($PixWide);
                            $isReduced = $img->is_reduced();
                            $isGood = $img->save($thumbPath);
                            unset($img);
                        }
                    }
                }

                $this->_sendFile($fPath, $fName, $urlID);
                return true;
            }
        }

        // If We're Here, It's No Good
        return false;
    }

    /**
     *  Function Sends the Requested File to the Visitor
     */
    private function _sendFile( $FilePath, $FileName, $ResourceID ) {
        $LocalFile = str_replace('//', '/', "$FilePath/$FileName");
        if ( file_exists($LocalFile) === false ) { return false; }

        $FileSize = filesize($LocalFile);
        $FileTime = date('r', filemtime($LocalFile));
        $FileMime = mime_content_type($LocalFile);
        $pos = 0;
        $end = $FileSize - 1;

        $fm = @fopen($LocalFile, 'rb');
        if (!$fm) {
            header ("HTTP/1.1 505 Internal server error");
            return false;
        }

        // Are We Continuing From a Set Location?
        if (isset($_SERVER['HTTP_RANGE'])) {
            if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                $pos = intval($matches[1]);
                if (!empty($matches[2])) { $end = intval($matches[2]); }
            }
        }

        // Start Constructing the Headers (200 or 206)
        if (isset($_SERVER['HTTP_RANGE'])) {
            header('HTTP/1.1 206 Partial Content');
        } else {
            header('HTTP/1.1 200 OK');
        }

        // Record the Download
        $txnID = $this->_recordAssetDownload( $ResourceID );

        // Set the File Headers
        $szOrigin = NoNull($_SERVER['HTTP_ORIGIN'], '*');
        header("Access-Control-Allow-Origin: $szOrigin");
        header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-NETWORK-ADDRESS, X-DEVICE-ID");
        header("Access-Control-Allow-Credentials: true");
        header("P3P: CP=\"ALL IND DSP COR ADM CONo CUR CUSo IVAo IVDo PSA PSD TAI TELo OUR SAMo CNT COM INT NAV ONL PHY PRE PUR UNI\"");
        header("Content-Type: $FileMime");
        header("Cache-Control: public, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        header("Accept-Ranges: bytes");
        header("Content-Length:" . (($end - $pos) + 1));
        if (isset($_SERVER['HTTP_RANGE'])) {
            header("Content-Range: bytes $pos-$end/$FileSize");
        }
        header("Content-Disposition: inline; filename=$FileName");
        header("Content-Transfer-Encoding: binary");
        header("Last-Modified: $FileTime");
        header("X-Content-Length: " . (($end - $pos) + 1));

        // Send the File From the Requested Location
        $sent = 0;
        $cur = $pos;
        fseek($fm, $pos, 0);

        while( !feof($fm) && $cur <= $end && (connection_status() == 0) ) {
            print fread($fm, min(1024 * 16, ($end - $cur) + 1));
            $sent += min(1024 * 16, ($end - $cur) + 1);
            $cur += 1024 * 16;
            flush();
            ob_flush();
        }

        // Close the File
        fclose($fp);
        exit;
    }
    
    private function _recordAssetDownload( $ResourceID ) {
        $rVal = false;

        $ReplStr = array( '[RESOURCE_ID]' => nullInt($ResourceID), );
        $sqlStr = readResource(SQL_DIR . '/files/setCDNResourceRead.sql', $ReplStr);
        $rVal = doSQLExecute($sqlStr);

        // Return the TXN.id Number
        return $rVal;
    }

    private function _recordAssetComplete( $txnID, $bytes ) {
        if ( nullInt($txnID) > 0 ) {
            $ReplStr = array( '[TXN_ID]' => nullInt($txnID),
                              '[BYTES]'  => nullInt($bytes),
                             );
            $sqlStr = readResource(SQL_DIR . '/files/setFileTXNDone.sql', $ReplStr);
            $rslt = doSQLExecute($sqlStr);
            $rVal = true;
        }
    }

    private function _checkForNewFiles( $ChkPath ) {
        $Path = str_ireplace('|', '/', NoNull($ChkPath, NoNull($this->settings['file_path'], $this->settings['path'])));
        if ( NoNull($ChkPath) == '' && $Path == '' ) { return "No Valid Path Supplied"; }
        $sqlStr = '';
        $itms = 0;
        $cnt = 0;

        if ( is_dir($Path) ) {
            $list = scandir($Path, SCANDIR_SORT_ASCENDING);
            if  ( is_array($list) ) {
                $ignore = array('.', '..');
                foreach ($list as $file) {
                    if ( !in_array($file, $ignore) ) {
                        if ( is_dir("$Path/$file") ) {
                            $cnt += $this->_checkForNewFiles("$Path/$file");
                        } else {
                            if ( is_file("$Path/$file") ) {
                                $ReplStr = array( '[FILE_NAME]' => sqlScrub($file),
                                                  '[FILE_SIZE]' => nullInt(filesize("$Path/$file")),
                                                  '[FILE_HASH]' => sqlScrub(hash_file('sha256', "$Path/$file")),
                                                  '[FILE_PATH]' => sqlScrub($Path),
                                                  '[FILE_TYPE]' => getFileExtension("$Path/$file"),
                                                  '[CREATEDAT]' => date("Y-m-d H:i:s", filectime("$Path/$file")),
                                                 );
                                if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
                                $sqlStr .= readResource(SQL_DIR . '/files/addCDNFileRecord.sql', $ReplStr);
                                $itms++;
                                $cnt++;

                                if ( $itms >= 250 ) {
                                    $isOK = doSQLExecute($sqlStr);
                                    $sqlStr = '';
                                    $itms = 0;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Record any of the Stragglers
        if ( $sqlStr != '' ) { $isOK = doSQLExecute($sqlStr); }
        
        // Return a Message or a Number
        if ( NoNull($ChkPath) != '' ) {
            return $cnt;
        } else {
            $html = "$cnt Files Referenced";
            header("Content-Type: text/html; charset=UTF-8");
            header("Content-Length: " . strlen($html));
            header("X-SHA1-Hash: " . sha1($html));
            exit( $html );
        }
    }

    /**
     *  Function Determines if the File is Resizable Based on it's MimeType Value and Returns a Boolean Response
     */
    private function _isResizableImage( $FileType ) {
        $valids = array( 'image/jpg', 'image/jpeg', 'image/gif', 'image/x-gif', 'image/png', 'image/tiff', 'image/bmp', 'image/x-windows-bmp',
                         'jpg', 'jpeg', 'gif', 'bmp', 'png'
                        );

        // Return the Boolean Response
        return in_array(strtolower($FileType), $valids);
    }
}
?>