<?php

/**
 * @author Jason F. Irwin
 * @copyright 2014
 * 
 * This is the primary Uploader for Advantage
 */
define('BASE_DIR', dirname(__FILE__));
define('CONF_DIR', BASE_DIR . '/../conf');
define('CDN_DIR', BASE_DIR . '/../cdn');
define('LIB_DIR', BASE_DIR . '/../lib');
define('LOG_DIR', BASE_DIR . '/../logs');
define('SQL_DIR', BASE_DIR . '/../sql');
define('TMP_DIR', BASE_DIR . '/../tmp');
require_once(LIB_DIR . '/functions.php');
require_once(CONF_DIR . '/versions.php');
require_once(CONF_DIR . '/config.php');

//error_reporting(E_ALL);
error_reporting(E_ERROR | E_PARSE);
mb_internal_encoding("UTF-8");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Cache-Control, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS, POST");

go();

// Ensure The Bare Minimum Exists
function go() {
    $rVal = array( 'meta' => array( 'code' => 401,
	                                'more' => false,
	                               ),
	               'data' => false,
				  );

    if ( NoNull($_SERVER['REQUEST_METHOD']) == 'OPTIONS' ) {
	    $rVal = array( 'meta' => array( 'code' => 200,
    	                                'more' => false,
    	                               ),
    	               'data' => false,
    				  );
    } else {
        $Token = "";
        $gah = getallheaders();
        if ( is_array($gah) ) {
            $opts = array( 'authorisation', 'authorization' );
            foreach ( getallheaders() as $key=>$val ) {
                if ( in_array(strtolower($key), $opts) ) { $Token = NoNull($val); }
            }
        }

        if ( $Token == "" ) {
            $rVal['meta']['text'] = 'Invalid Token Identifier (1)';
        } else {
            $rVal = doHandleUpload( $Token );
        }
    }

    // Close the Persistent SQL Connection If Needs Be
    closePersistentSQLConn();

    // Return the JSON-Encoded Response
    echo json_encode($rVal);
    exit;
}

function getVariables() {
    $rVal = array();
    foreach( $_POST as $key=>$val ) {
        $rVal[ $key ] = NoNull($val);
    }

    foreach( $_GET as $key=>$val ) {
        if ( !array_key_exists($key, $rVal) ) { $rVal[ $key ] = NoNull($val); }
    }

    foreach( $_COOKIE as $key=>$val ) {
        if ( !array_key_exists($key, $rVal) ) { $rVal[ $key ] = NoNull($val); }
    }
    
    // Return the Array of Variables
    return $rVal;
}

function doHandleUpload( $Token ) {
    $StorageFree = disk_free_space("/");
    $AcctData = getCDNUploadData( $Token );
    $BaseDIR = CDN_PATH;
    $Vars = getVariables();

    $SaveDIR = date('Y/m/');
    if ( NoNull($Vars['location']) != '' ) {
        $BaseDIR = BASE_DIR;
        $SaveDIR = NoNull($Vars['location']) . '/';
    }
    
    // Ensure the Save Location Exists
    checkDIRExists("$BaseDIR/$SaveDIR");

    $rVal = array( 'meta' => array( 'code' => 401,
	                                'more' => false,
	                               ),
	               'data' => false,
				  );

    // Ensure We Have a Save Location
    if ( $SaveDIR == '' ) {
        $rVal['meta']['text'] = 'Invalid Save Location (1) [' . $SaveDIR . ']';
        return $rVal;
    }

    // Ensure the Token is Valid
    if ( $AcctData['account_id'] <= 0 ) {
        $rVal['meta']['text'] = 'Invalid Token Identifier (1)';
        return $rVal;
        exit;
    }

    // Collect the Name of the Files Object
    $items = array();
    foreach ( $_FILES as $Key=>$Value ) {
        $items[] = $Key;
    }

    if ( count($items) <= 0 ) {
        $rVal['meta']['text'] = "No Files Found";
        $rVal['meta']['code'] = 400;
        return $rVal;
        exit;
    }

    foreach ( $items as $FileID ) {
        $now = time();

        if ( $_FILES[ $FileID ]['size'] > (CDN_UPLOAD_LIMIT * 1024 * 1024) ) {
            $rVal['result'] = 'File Too Large (' . CDN_UPLOAD_LIMIT . 'MB Limit)';
            return $rVal;
            exit;
        }

        // Ensure We Have Enough Space in the Account's Bucket
        if ( $StorageFree < 0 ) { $StorageFree = 0; }
        if ( $_FILES[ $FileID ]['size'] > $StorageFree ) {
            $rVal['meta']['text'] = "Insufficient Storage Space Available. " . round(($StorageFree / 1024 / 1024), 0) . "MB Remaining";
            $rVal['meta']['code'] = 507;
            return $rVal;
            exit;
        }

        // If the MIME Type is Valid, Allow the Upload
        if ( isValidUploadType($_FILES[ $FileID ]['type']) ) {
            if ( isset($_FILES[ $FileID ]) ) {
                $filename = basename($_FILES[ $FileID ]['name']);
                $file_md5 = md5("$filename $now");
                $guid = substr($file_md5,  0, 8) . '-' .
                        substr($file_md5,  8, 4) . '-' .
                        substr($file_md5, 12, 4) . '-' .
                        substr($file_md5, 16, 4) . '-' .
                        substr($file_md5, 20, 12);
                $localname = "$guid." . getFileExtension($filename);
                $fullPath = $BaseDIR . "/$SaveDIR/" . strtolower($localname);
                $cdnPath = "$SaveDIR/" . strtolower($localname);
                $file_id = 0;
                $isGood = false;

                if ( NoNull($filename) != "" ) {
                    $isGood = move_uploaded_file($_FILES[ $FileID ]['tmp_name'], $fullPath);
                    $ReplStr = array( '[FILE_NAME]' => sqlScrub($filename),
                                      '[FILE_LOCL]' => sqlScrub($localname),
                                      '[FILE_SIZE]' => nullInt(filesize($fullPath)),
                                      '[FILE_HASH]' => sqlScrub(hash_file('sha256', $fullPath)),
                                      '[FILE_PATH]' => sqlScrub($BaseDIR . "/$SaveDIR"),
                                      '[FILE_TYPE]' => getFileExtension($fullPath),
                                      '[CREATEDAT]' => date("Y-m-d H:i:s", filectime($fullPath)),
                                      '[CREATEDBY]' => $AcctData['account_id'],
                                     );
                    $sqlStr = readResource(SQL_DIR . '/files/recordFileUpload.sql', $ReplStr);
                    $isOK = doSQLExecute($sqlStr);
                    if ( $isOK > 0 ) { $file_id = $isOK; }

                } else {
                    $rVal['result'] = 'Invalid File Name';
                    return $rVal;
                    exit;
                }

                // Build the Return Array
                if ( $isGood ) {
                    $rVal['meta']['code'] = 200;
                    $rVal['data'] = array( 'file' => array( 'id'    => nullInt($file_id),
                                                            'name'  => $filename,
                                                            'local' => strtolower($localname),
                                                            'size'  => nullInt($_FILES[ $FileID ]['size']),
                                                            'type'  => NoNull($_FILES[ $FileID ]['type']),
                                                           ),
                                           'url'  => ((HTTPS_ENABLED == 1) ? 'https' : 'http') . '://' . str_replace('//', '/', CDN_URL . '/' . $cdnPath),
                                          );

                    $rVal['isGood'] = 'Y';
                    $rVal['local'] = strtolower($localname);
                    $rVal['filename'] = $filename;
                    $rVal['cdnurl'] = '//' . str_replace('//', '/', CDN_URL . '/' . $cdnPath);
                    $rVal['file_id'] = nullInt($file_id);
                    $rVal['length'] = $_FILES[ $FileID ]['size'];
                    $rVal['type'] = $_FILES[ $FileID ]['type'];
                } else {
                    $rVal['result'] = 'Could Not Upload File. Please Try Again.';
                }
            }

        } else {
            $rVal['result'] = 'Invalid File Type (' . $_FILES[ $FileID ]['type'] . ')';
        }
        
        // Reutrn the Results
        return $rVal;
    }
}

?>