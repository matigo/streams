<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called to manage files
 */
require_once( LIB_DIR . '/functions.php');

class Files {
    var $settings;
    var $strings;
    var $cache;

    function __construct( $settings, $strings = false ) {
        $this->settings = $settings;
        $this->strings = ((is_array($strings)) ? $strings : getLangDefaults($this->settings['_language_code']));
        $this->cache = array();

        /* Prep the Count */
        $this->_populateClass();
    }

    /**
     *  Function Populates the Initial Values Required by the Class
     */
    private function _populateClass() {
        if ( defined('CDN_POOL_SIZE') === false ) { define('CDN_POOL_SIZE', 1); }
        if ( nullInt($this->settings['_storage_total']) < 0 ) { $this->settings['_storage_total'] = (1024 * 1024) * nullInt(CDN_POOL_SIZE); }
        if ( nullInt($this->settings['_storage_files']) < 0 ) { $this->settings['_storage_files'] = 0; }
        if ( nullInt($this->settings['_storage_used']) < 0 ) { $this->settings['_storage_used'] = 0; }

        /* Determine the Amount of Storage that is Available */
        $this->settings['_storage_remain'] = nullInt($this->settings['_storage_total']) - nullInt($this->settings['_storage_used']);
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    public function performAction() {
        $ReqType = NoNull(strtolower($this->settings['ReqType']));
        $rVal = false;

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) {
            $this->_setMetaMessage("You Need to Log In First", 403);
            return false;
        }

        // Perform the Action
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
                // Do Nothing
        }

        // Return an unhappy boolean if nothing was done
        return false;
    }

    private function _performGetAction() {
        $Activity = NoNull(strtolower($this->settings['PgSub1']));
        if ( nullInt($this->settings['PgSub1']) > 0 ) { $Activity = 'list'; }
        $rVal = false;

        switch ( $Activity ) {
            case 'item':
                return $this->_getFileByID();
                break;

            case 'list':
            case '':
                return $this->_getFilesList();
                break;

            default:
                // Do Nothing
        }

        // Return an unhappy boolean if nothing was done
        return false;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case 'avatar':
                return $this->_prepareAvatar();
                break;

            case 'upload':
            case '':
                return $this->_createNewFile();
                break;

            default:
                // Do Nothing
        }

        // Return an unhappy boolean if nothing was done
        return false;
    }

    private function _performDeleteAction() {
        $Activity = NoNull(strtolower($this->settings['PgSub1']));
        if ( nullInt($this->settings['PgSub1']) > 0 ) { $Activity = 'scrub'; }
        $rVal = false;

        switch ( $Activity ) {
            case 'scrub':
                return $this->_deleteFile();
                break;

            default:
                // Do Nothing
        }

        // Return an unhappy boolean if nothing was done
        return false;
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
    public function requestResource() { return $this->_requestResource(); }

    /** ********************************************************************* *
     *  Settings and Input Functions
     ** ********************************************************************* */
    /**
     *  Function returns a consistent array of values that can be used for file settings and preferences
     */
    private function _getInputValues() {
        $CleanGuid = NoNull($this->settings['file_guid'], $this->settings['file']);
        if ( mb_strlen($CleanGuid) != 36 ) { $CleanGuid = ''; }

        $CleanName = NoNull($this->settings['file_name'], $this->settings['name']);
        $CleanMime = NoNull($this->settings['file_mime'], $this->settings['mime']);

        /* Determine whether the file should be public or private */
        $validACL = array('private', 'public-read', 'public-read-write');
        $CleanAccess = strtolower(NoNull($this->settings['access'], $this->settings['acl']));
        if ( in_array($CleanAccess, $validACL) === false ) { $CleanAccess = 'private'; }

        /* Do we have a text-based expiration date? */
        $ExpiresAt = NoNull($this->settings['expires_at'], $this->settings['expires_on']);
        $setExpiresAt = '';
        if ( mb_strlen($ExpiresAt) >= 10 ) {
            $exp_unix = strtotime($ExpiresAt);
            if ( is_bool($exp_unix) === false) {
                $setExpiresAt = date('Y-m-d H:i:59', $exp_unix);
            }
        }

        /* Do we have a unix-based expiration date? This overrides any supplied text-based value */
        $ExpiresUnix = nullInt($this->settings['expires_unix']);
        if ( $ExpiresUnix > time() ) { $setExpiresAt = date('Y-m-d H:i:59', $ExpiresUnix); }

        /* Is there a password? If so, the access must be private */
        $CleanPassword = NoNull($this->settings['password'], $this->settings['pass']);
        if ( mb_strlen($CleanPassword) > 0 ) {
            $CleanPassword = hash('sha256', $CleanPassword, false);
            $CleanAccess = 'private';
        }

        /* Return an Array */
        return array( 'file_guid'  => ((mb_strlen($CleanGuid) == 36) ? $CleanGuid : ''),
                      'file_name'  => NoNull($CleanName),
                      'file_mime'  => NoNull($CleanMime),

                      'access'     => NoNull($CleanAccess, 'private'),
                      'expires_at' => NoNull($setExpiresAt),
                      'password'   => NoNull($CleanPassword),
                     );
    }

    /** ********************************************************************* *
     *  File Upload Functions
     ** ********************************************************************* */
    private function _prepareAvatar() {
        if ( !defined('CDN_UPLOAD_LIMIT') ) { define('CDN_UPLOAD_LIMIT', 5); }
        if ( !defined('USE_S3') ) { define('USE_S3', 0); }
        if ( $this->settings['_storage_remain'] < 0 ) { return "Insufficient Storage Remaining"; }
        $list = false;
        $errs = false;

        // Collect the Name of the Files Object
        $items = array();
        foreach ( $_FILES as $Key=>$Value ) {
            $items[] = $Key;
        }

        // Do Not Continue if there are No Files
        if ( count($items) <= 0 ) {
            $this->_setMetaMessage( "No Files Found", 400 );
            return false;
        }

        // Check to see if there are files and, if so, process them.
        if ( is_array($_FILES) ) {
            require_once(LIB_DIR . '/images.php');
            require_once(LIB_DIR . '/s3.php');
            $LocalName = '';

            // Determine the Avatar Location
            $AvatarDIR = BASE_DIR . '/avatars';
            checkDIRExists($AvatarDIR);

            // If We Should Use Amazon's S3, Activate the Class
            if ( USE_S3 == 1 ) {
                if ( !defined('AWS_REGION_NAME') ) { define('AWS_REGION_NAME', ''); }
                if ( !defined('AWS_BUCKET_NAME') ) { define('AWS_BUCKET_NAME', ''); }
                if ( !defined('AWS_ACCESS_KEY') ) { define('AWS_ACCESS_KEY', ''); }
                if ( !defined('AWS_SECRET_KEY') ) { define('AWS_SECRET_KEY', ''); }
                $s3 = new S3(AWS_ACCESS_KEY, AWS_SECRET_KEY, true, AWS_REGION_NAME);
            }

            foreach ( $items as $FileID ) {
                $FileName = NoNull(basename($_FILES[ $FileID ]['name']));
                $FileSize = nullInt($_FILES[ $FileID ]['size']);
                $FileType = NoNull($_FILES[ $FileID ]['type']);
                if ( NoNull($FileType) == '' ) {
                    $ext = $this->_getFileExtension($FileName, $FileType);
                    switch ( strtolower(NoNull($ext)) ) {
                        case 'jpeg':
                        case 'jpg':
                            $FileType = 'image/jpg';
                            break;

                        case 'gif':
                            $FileType = 'image/gif';
                            break;

                        case 'png':
                            $FileType = 'image/png';
                            break;
                    }
                }

                // Validate the File
                $ValidType = $this->_isValidUploadType($FileType, $this->_getFileExtension($FileName, $FileType));

                // Process the File if we have Space in the Bucket, otherwise Record a Size Error
                if ( $ValidType && $FileSize <= (CDN_UPLOAD_LIMIT * 1024 * 1024) && $FileSize <= nullInt($this->settings['_storage_remain']) ) {
                    $this->settings['_storage_remain'] -= $FileSize;
                    $now = time();

                    if ( isset($_FILES[ $FileID ]) ) {
                        $LocalName = md5("$FileName $now") . "." . $this->_getFileExtension($FileName, $FileType);
                        $fullPath = $AvatarDIR . '/' . strtolower($LocalName);

                        $cdnPath = 'avatars/' . strtolower($LocalName);
                        $isAnimated = false;
                        $isReduced = false;
                        $isGood = false;

                        if ( NoNull($FileName) != "" ) {
                            // Shrink the File If Needs Be
                            if ( $this->_isResizableImage($FileType) ) {
                                $isGood = move_uploaded_file($_FILES[ $FileID ]['tmp_name'], $fullPath);

                                // Upload the Original Image to S3 if Appropriate
                                if ( USE_S3 == 1 ) {
                                    $s3->putObject($s3->inputFile($fullPath, false), CDN_DOMAIN, $cdnPath, S3::ACL_PUBLIC_READ);
                                }

                                // Resize the Image to a Square
                                $img = new Images();
                                $img->load($fullPath, $FileType);
                                $img->makeSquare(450, 250);
                                $isGood = $img->save($fullPath);
                                unset($img);

                            } else {
                                $isGood = move_uploaded_file($_FILES[ $FileID ]['tmp_name'], $fullPath);
                            }

                            // Copy the Data to S3
                            if ( USE_S3 == 1 ) {
                                $s3->putObject($s3->inputFile($fullPath, false), CDN_DOMAIN, $cdnPath, S3::ACL_PUBLIC_READ);
                                unset($s3);
                            }

                        } else {
                            if ( is_array($errs) === false ) { $errs = array(); }
                            $errs[] = array( 'name'   => $FileName,
                                             'size'   => $FileSize,
                                             'type'   => strtolower($FileType),
                                             'reason' => "Bad File Name",
                                            );
                        }
                    }

                } else {
                    if ( is_array($errs) === false ) { $errs = array(); }
                    $errs[] = array( 'name'   => $FileName,
                                     'size'   => $FileSize,
                                     'type'   => strtolower($FileType),
                                     'reason' => (($ValidType) ? "Insufficient Storage Remaining" : "Unsupported File Type"),
                                    );
                }
            }

            $cdnUrl = '';

            // Get the Default URL for the Platform (Used for CDN Determination)
            $sqlStr = readResource(SQL_DIR . '/site/getDefaultUrl.sql');
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $cdnUrl = NoNull($Row['default_url']);
                }
            }

            // If the File Exists, Return a Happy array
            if ( file_exists($fullPath) ) {
                return array( 'cdn_url' => $cdnUrl . '/avatars/' . $LocalName,
                              'file'    => $LocalName,
                             );
            } else {
                $this->_setMetaMessage( "Could Not Process File", 400 );
                return false;
            }
        }

        // If We're Here, Nothing Was Found
        $this->_setMetaMessage( "No Files Found", 400 );
        return false;
    }

    /**
     *  Function Handles File Uploads for a Given Account
     */
    private function _createNewFile() {
        if ( !defined('CDN_UPLOAD_LIMIT') ) { define('CDN_UPLOAD_LIMIT', 5); }
        if ( !defined('AWS_REGION_NAME') ) { define('AWS_REGION_NAME', ''); }
        if ( !defined('AWS_BUCKET_NAME') ) { define('AWS_BUCKET_NAME', ''); }
        if ( !defined('AWS_ACCESS_KEY') ) { define('AWS_ACCESS_KEY', ''); }
        if ( !defined('AWS_SECRET_KEY') ) { define('AWS_SECRET_KEY', ''); }
        if ( !defined('CDN_PATH') ) { define('CDN_PATH', ''); }
        if ( !defined('USE_S3') ) { define('USE_S3', 0); }
        if ( $this->settings['_storage_remain'] < 0 ) { return "Insufficient Storage Remaining"; }
        $list = false;
        $errs = false;

        /* Collect the Name of the Files Object */
        $items = array();
        foreach ( $_FILES as $Key=>$Value ) {
            $items[] = $Key;
        }

        /* Do Not Continue if there are No Files */
        if ( count($items) <= 0 ) { return "No Files Found"; }

        /* Check to see if there are files and, if so, process them. */
        if ( is_array($_FILES) ) {
            require_once(LIB_DIR . '/images.php');

            /* If We Should Use Amazon's S3, Activate the Class */
            if ( YNBool(USE_S3) ) {
                if ( mb_strlen(NoNull(AWS_REGION_NAME)) < 10 ) {
                    $this->_setMetaMessage("Invalid AWS Region Name Provided", 401);
                    return false;
                }
                if ( mb_strlen(NoNull(AWS_BUCKET_NAME)) < 10 ) {
                    $this->_setMetaMessage("Invalid AWS Bucket Name Provided", 401);
                    return false;
                }
                if ( mb_strlen(NoNull(AWS_ACCESS_KEY)) < 20 ) {
                    $this->_setMetaMessage("Invalid AWS Access Key Provided", 401);
                    return false;
                }
                if ( mb_strlen(NoNull(AWS_SECRET_KEY)) < 36 ) {
                    $this->_setMetaMessage("Invalid AWS Secret Key Provided", 401);
                    return false;
                }

                /* If we're here, we should have a proper set of configuration data for the S3 class */
                require_once(LIB_DIR . '/s3.php');
                $s3 = new S3(AWS_ACCESS_KEY, AWS_SECRET_KEY, true, 's3.amazonaws.com', AWS_REGION_NAME);
            }

            foreach ( $items as $FileID ) {
                $FileName = NoNull(basename($_FILES[ $FileID ]['name']));
                $FileSha1 = sha1_file($_FILES[ $FileID ]['tmp_name']);
                $FileSize = nullInt($_FILES[ $FileID ]['size']);
                $FileType = NoNull($_FILES[ $FileID ]['type']);
                if ( NoNull($FileType) == '' ) {
                    $ext = $this->_getFileExtension($FileName, $FileType);
                    switch ( strtolower(NoNull($ext)) ) {
                        case 'jpeg':
                        case 'jpg':
                            $FileType = 'image/jpeg';
                            break;

                        case 'gif':
                            $FileType = 'image/gif';
                            break;

                        case 'png':
                            $FileType = 'image/png';
                            break;
                    }
                }

                /* Validate the File */
                $ValidType = $this->_isValidUploadType($FileType, $this->_getFileExtension($FileName, $FileType));

                /* Process the File if we have Space in the Bucket, otherwise Record a Size Error */
                if ( $ValidType && $FileSize <= (CDN_UPLOAD_LIMIT * 1024 * 1024) && $FileSize <= nullInt($this->settings['_storage_remain']) ) {
                    $this->settings['_storage_remain'] -= $FileSize;
                    $now = time();

                    if ( isset($_FILES[ $FileID ]) ) {
                        $LocalName = md5("$FileName $now") . "." . $this->_getFileExtension($FileName, $FileType);
                        $fullPath = CDN_PATH . '/' . intToAlpha($this->settings['_account_id']) . "/" . strtolower($LocalName);
                        checkDIRExists(CDN_PATH . '/' . intToAlpha($this->settings['_account_id']));

                        $cdnPath = intToAlpha($this->settings['_account_id']) . "/" . strtolower($LocalName);
                        $imgMeta = false;
                        $geoData = false;
                        $isAnimated = false;
                        $isReduced = false;
                        $isGood = false;

                        if ( NoNull($FileName) != "" ) {
                            // Shrink the File If Needs Be
                            if ( $this->_isResizableImage($FileType) ) {
                                $thumbName = md5("$FileName $now") . '_thumb.' . $this->_getFileExtension($FileName, $FileType);
                                $thumbPath = CDN_PATH . '/' . intToAlpha($this->settings['_account_id']) . "/" . strtolower($thumbName);

                                $propName = md5("$FileName $now") . '_medium.' . $this->_getFileExtension($FileName, $FileType);
                                $propPath = CDN_PATH . '/' . intToAlpha($this->settings['_account_id']) . "/" . strtolower($propName);

                                $origName = md5("$FileName $now") . '.' . $this->_getFileExtension($FileName, $FileType);
                                $origPath = CDN_PATH . '/' . intToAlpha($this->settings['_account_id']) . "/" . strtolower($origName);
                                move_uploaded_file($_FILES[ $FileID ]['tmp_name'], $origPath);

                                // Upload the Original Image to S3 if Appropriate
                                if ( USE_S3 == 1 ) {
                                    $s3Path = intToAlpha($this->settings['_account_id']) . strtolower("/$origName");
                                    $s3->putObject($s3->inputFile($origPath, false), CDN_DOMAIN, $s3Path, S3::ACL_PUBLIC_READ);
                                }

                                // Resize the Image
                                $img = new Images();
                                $img->load($origPath, $FileType);
                                $geoData = $img->getGeolocation();
                                $imgMeta = $img->getPhotoMeta();
                                $imgWidth = $img->getWidth();

                                $isAnimated = $img->is_animated();
                                if ( $isAnimated !== true ) {
                                    /* Is there a medium-sized image to create? */
                                    if ( $imgWidth > 960 ) {
                                        $img_mid = new Images();
                                        $img_mid->load($origPath, $FileType);
                                        $img_mid->reduceToWidth(960);

                                        $isGood = $img_mid->save($propPath);
                                        $hasProp = $img_mid->is_reduced();

                                        if ( USE_S3 == 1 ) {
                                            $s3Path = intToAlpha($this->settings['_account_id']) . strtolower("/$propName");
                                            $s3->putObject($s3->inputFile($propPath, false), CDN_DOMAIN, $s3Path, S3::ACL_PUBLIC_READ);
                                        }
                                        unset($img_mid);
                                    }

                                    /* Is there a thumbnail to create? */
                                    if ( $imgWidth > 480 ) {
                                        $img_thumb = new Images();
                                        $img_thumb->load($origPath, $FileType);
                                        $img_thumb->reduceToWidth(480);

                                        $isGood = $img_thumb->save($thumbPath);
                                        $hasThumb = $img_thumb->is_reduced();

                                        if ( USE_S3 == 1 ) {
                                            $s3Path = intToAlpha($this->settings['_account_id']) . strtolower("/$thumbName");
                                            $s3->putObject($s3->inputFile($thumbPath, false), CDN_DOMAIN, $s3Path, S3::ACL_PUBLIC_READ);
                                        }
                                        unset($img_thumb);
                                    }
                                }
                                unset($img);

                            } else {
                                $isGood = move_uploaded_file($_FILES[ $FileID ]['tmp_name'], $fullPath);
                            }

                            $rfu = $this->_recordFileUpload( strtolower($FileName), strtolower($LocalName), $FileSize, strtolower($FileType),
                                                             $FileSha1, $geoData, $imgMeta, $hasProp, $hasThumb, $isAnimated );
                            if ( is_array($rfu) && count($rfu) > 0 ) {
                                if ( is_array($list) === false ) { $list = array(); }
                                $list[] = $rfu;
                            } else {
                                if ( is_array($errs) === false ) { $errs = array(); }
                                $errs[] = array( 'name'   => $FileName,
                                                 'size'   => $FileSize,
                                                 'type'   => strtolower($FileType),
                                                 'reason' => "Could Not Record File Data",
                                                );
                            }

                            // Copy the Data to S3
                            if ( USE_S3 == 1 ) {
                                $s3->putObject($s3->inputFile($fullPath, false), CDN_DOMAIN, $cdnPath, S3::ACL_PUBLIC_READ);
                                unset($s3);
                            }

                        } else {
                            if ( is_array($errs) === false ) { $errs = array(); }
                            $errs[] = array( 'name'   => $FileName,
                                             'size'   => $FileSize,
                                             'type'   => strtolower($FileType),
                                             'reason' => "Bad File Name",
                                            );
                        }
                    }

                } else {
                    if ( is_array($errs) === false ) { $errs = array(); }
                    $errs[] = array( 'name'   => $FileName,
                                     'size'   => $FileSize,
                                     'type'   => strtolower($FileType),
                                     'reason' => (($ValidType) ? "Insufficient Storage Remaining" : "Unsupported File Type"),
                                    );
                }
            }

            // Reload the Bucket Data
            $this->_populateClass();

            // Return a Files Object Array
            return array( 'files'  => $list,
                          'bucket' => array( 'files' => $this->settings['_storage_used'],
                                             'limit' => $this->settings['_storage_total'],
                                             'used'  => $this->settings['_storage_used'],
                                            ),
                          'errors' => $errs,
                         );
        }

        // If We're Here, Nothing Was Found
        $this->_setMetaMessage( "No Files Found", 400 );
        return false;
    }

    /**
     *  Function Records the File and its Metadata to the Database and returns the appropriate Files object
     */
    private function _recordFileUpload( $FileName, $LocalName, $FileSize, $FileType, $FileHash, $geoData = false, $imgMeta = false, $hasProp, $isReduced = false, $isAnimated = false ) {
        if ( $Animated !== true ) { $Animated = false; }
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[FILENAME]'   => sqlScrub($FileName),
                          '[FILELOCAL]'  => sqlScrub($LocalName),
                          '[FILEHASH]'   => sqlScrub($FileHash),
                          '[FILESIZE]'   => nullInt($FileSize),
                          '[FILEPATH]'   => sqlScrub('/' . intToAlpha($this->settings['_account_id']) . '/'),
                          '[FILETYPE]'   => sqlScrub($FileType),
                          '[IS_REDUCED]' => BoolYN($isReduced),
                          '[ANIMATED]'   => BoolYN($Animated),
                         );
        $sqlStr = readResource(SQL_DIR . '/files/recordFileUpload.sql', $ReplStr);
        $FileID = doSQLExecute($sqlStr);

        // Record the MetaData If It Exists
        if ( $FileID > 0 ) {
            $sqlStr = '';

            // Collect any Geo Meta We May Have
            if ( is_array($geoData) ) {
                foreach ( $geoData as $Key=>$Value ) {
                    if ( $Value !== false ) {
                        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                                          '[FILE_ID]'    => nullInt($FileID),
                                          '[KEY]'        => 'geo.' . sqlScrub($Key),
                                          '[VALUE]'      => sqlScrub($Value),
                                         );
                        if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
                        $sqlStr .= readResource(SQL_DIR . '/files/setFileMeta.sql', $ReplStr);
                    }
                }
            }

            // Collect any Image Meta We May Have
            if ( is_array($imgMeta) ) {
                foreach ( $imgMeta as $Key=>$Value ) {
                    if ( $Value !== false ) {
                        if ( strpos($Key, 'image.') === false ) { $Key = "image.$Key"; }

                        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                                          '[FILE_ID]'    => nullInt($FileID),
                                          '[KEY]'        => sqlScrub($Key),
                                          '[VALUE]'      => sqlScrub($Value),
                                         );
                        if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
                        $sqlStr .= readResource(SQL_DIR . '/files/setFileMeta.sql', $ReplStr);
                    }
                }
            }

            // Has the File Been Reduced in Size?
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                              '[FILE_ID]'    => nullInt($FileID),
                              '[KEY]'        => 'image.has_medium',
                              '[VALUE]'      => BoolYN($hasProp),
                             );
            if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
            $sqlStr .= readResource(SQL_DIR . '/files/setFileMeta.sql', $ReplStr);

            // Do We Have a Thumbnail-sized Image as well?
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                              '[FILE_ID]'    => nullInt($FileID),
                              '[KEY]'        => 'image.has_thumb',
                              '[VALUE]'      => BoolYN($isReduced),
                             );
            if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
            $sqlStr .= readResource(SQL_DIR . '/files/setFileMeta.sql', $ReplStr);

            // Write the Data to the Database If Applicable
            if ( $sqlStr != '' ) { $isOK = doSQLExecute($sqlStr); }

            // Set the file_id Value and Return a Files Object
            return $this->_getFileByID($FileID);
        }

        // If We're Here, The Write Failed
        return false;
    }

    /** ********************************************************************* *
     *  File Request Functions
     ** ********************************************************************* */
    /**
     *  Function checks to see if the requested resource is valid and accessible to the requestor. If the
     *      resource is not available, an unhappy boolean is returned to trigger a 404.
     *
     *  Note: 404's are preferrable to 403's, as it will (theoretically) reduce access attempts
     */
    private function _requestResource() {
        if ( !defined('CDN_PATH') ) { define('CDN_PATH', ''); }
        if ( !defined('USE_S3') ) { define('USE_S3', 0); }
        $inputs = $this->_getInputValues();
        $isValid = false;
        $srcName = '';
        $srcMime = '';

        /* Determine the Location */
        $Location = NoNull($this->settings['PgRoot']);

        /* Determine the File Name */
        $FileName = '';
        for ( $i = 9; $i >= 1; $i-- ) {
            if ( mb_strlen($FileName) <= 0 ) {
                $FileName = NoNull($this->settings['PgSub' . $i]);
            }
        }
        $BaseName = str_replace(array('_original', '_medium', '_small', '_thumb'), '', $FileName);

        /* Determine the Owner */
        $OwnerId = alphaToInt($Location);
        if ( $OwnerId < 0 ) { $OwnerId = 0; }

        /* Perform some basic validation */
        if ( mb_strlen($FileName) < 3 ) { return false; }
        if ( mb_strlen($Location) < 6 ) { return false; }
        if ( $OwnerId <= 0 ) { return false; }

        /* Check to see if the file is accessible to the current account holder */
        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[PASSWORD]'   => sqlScrub($inputs['password']),
                          '[FILE_NAME]'  => sqlScrub($BaseName),
                          '[LOCATION]'   => sqlScrub($Location),
                          '[OWNER]'      => nullInt($OwnerId)
                         );
        $sqlStr = readResource(SQL_DIR . '/files/checkResourceAccess.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                if ( YNBool($Row['can_access']) ) {
                    $srcName = NoNull($Row['public_name']);
                    $srcMime = NoNull($Row['mimetype']);
                    $isValid = true;
                }
            }
        }

        /* If we cannot continue, let's exit */
        if ( $isValid === false ) { return false; }

        /* If we're here, we have access. Check that the resource exists on the web server. If it doesn't collect it from the bucket (if applicable) */
        $srcPath = CDN_PATH . "/$Location/$FileName";
        if ( file_exists($srcPath) === false ) {
            if ( YNBool(USE_S3) ) {
                $rsp = $this->_getFile($srcPath);
                if ( $rsp !== true ) { return false; }
            }
        }

        /* Send the Resource File (if exists) */
        if ( file_exists($srcPath) ) {
            return sendResourceFile( $srcPath, $srcName, $srcMime, $this->settings );
        } else {
            return false;
        }
    }

    /**
     *  Function retrieves a file from the S3 bucket and returns a boolean response
     */
    private function _getFile( $srcPath ) {
        if ( !defined('AWS_REGION_NAME') ) { define('AWS_REGION_NAME', ''); }
        if ( !defined('AWS_BUCKET_NAME') ) { define('AWS_BUCKET_NAME', ''); }
        if ( !defined('AWS_ACCESS_KEY') ) { define('AWS_ACCESS_KEY', ''); }
        if ( !defined('AWS_SECRET_KEY') ) { define('AWS_SECRET_KEY', ''); }
        if ( !defined('CDN_PATH') ) { define('CDN_PATH', ''); }
        if ( !defined('USE_S3') ) { define('USE_S3', 0); }

        if ( YNBool(USE_S3) ) {
            if ( mb_strlen(NoNull(AWS_REGION_NAME)) < 10 ) {
                $this->_setMetaMessage("Invalid AWS Region Name Provided", 401);
                return false;
            }
            if ( mb_strlen(NoNull(AWS_BUCKET_NAME)) < 10 ) {
                $this->_setMetaMessage("Invalid AWS Bucket Name Provided", 401);
                return false;
            }
            if ( mb_strlen(NoNull(AWS_ACCESS_KEY)) < 20 ) {
                $this->_setMetaMessage("Invalid AWS Access Key Provided", 401);
                return false;
            }
            if ( mb_strlen(NoNull(AWS_SECRET_KEY)) < 36 ) {
                $this->_setMetaMessage("Invalid AWS Secret Key Provided", 401);
                return false;
            }

            $s3Path = str_replace(CDN_PATH . '/', '', $srcPath);

            /* If we're here, we should have a proper set of configuration data for the S3 class */
            require_once(LIB_DIR . '/s3.php');
            $s3 = new S3(AWS_ACCESS_KEY, AWS_SECRET_KEY, true, 's3.amazonaws.com', AWS_REGION_NAME);

            $rsp = $s3->getObject(AWS_BUCKET_NAME, $s3Path, $srcPath);
            if ( is_object($rsp) ) { $rsp = objectToArray($rsp); }

            /* If we have a success code and the file exists, return a happy boolean */
            if ( file_exists($srcPath) && filesize($srcPath) > 0 ) { return true; }
        }

        /* If we're here, nothing was done */
        return false;
    }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function returns an Array of Files Objects
     */
    private function _getFilesList() {
        $PageID = nullInt($this->settings['page']) - 1;
        if ( $PageID < 0 ) { $PageID = 0; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[FILE_ID]'    => nullInt($this->settings['file_id'], $this->settings['PgSub1']),
                          '[PAGE]'       => ($PageID * 50),
                         );

        $sqlStr = readResource(SQL_DIR . '/files/getFilesList.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $cdn_prefix = '//' . CDN_DOMAIN . '/' . intToAlpha($this->settings['_account_id']) . '/';
            $data = array();
            foreach ( $rslt as $Row ) {
                $is_deleted = YNBool($Row['is_deleted']);

                /* If the file does not exist in the file system, report it as deleted ... which it probably is */
                if ( $is_deleted === false ) {
                    $filename = CDN_PATH . NoNull($Row['hash']);
                    if ( file_exists($filename) === false ) { $is_deleted = true; }
                }

                /* Construct the Array */
                $data[] = array( 'file' => array( 'id'         => nullInt($Row['id']),
                                                  'name'       => (($is_deleted) ? false : NoNull($Row['name'])),
                                                  'size'       => (($is_deleted) ? false : nullInt($Row['size'])),
                                                  'mime'       => (($is_deleted) ? false : NoNull($Row['type'])),
                                                  'is_deleted' => $is_deleted,
                                                 ),

                                 'in_post'       => (($is_deleted) ? false : ((nullInt($Row['posts'])) ? true : false)),
                                 'in_meta'       => (($is_deleted) ? false : ((nullInt($Row['in_meta'])) ? true : false)),
                                 'is_avatar'     => (($is_deleted) ? false : ((nullInt($Row['is_avatar'])) ? true : false)),
                                 'has_metadata'  => (($is_deleted) ? false : YNBool($Row['has_meta'])),

                                 'cdn_url'       => (($is_deleted) ? false : $cdn_prefix . NoNull($Row['hash'])),

                                 'uploaded_at'   => (($is_deleted) ? false : NoNull($Row['uploaded_at'])),
                                 'uploaded_unix' => (($is_deleted) ? false : strtotime($Row['uploaded_at'])),
                                 'updated_at'    => NoNull($Row['updated_at']),
                                 'updated_unix'  => strtotime($Row['updated_at']),
                                );
            }

            // If We Have Data, Return It
            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There's Nothing
        return array();
    }

    /**
     *  Function Returns a File Object for a Given ID, or an Unhappy Boolean
     */
    private function _getFileByID( $FileID = 0 ) {
        $CleanID = nullInt($FileID, $this->settings['file_id']);
        if ( $CleanID <= 0 ) { return false; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[FILE_ID]'    => nullInt($CleanID),
                         );
        $sqlStr = readResource(SQL_DIR . '/files/getFileData.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $cdn_prefix = getCdnUrl();
            $data = false;
            $meta = false;

            foreach ( $rslt as $Row ) {
                $is_deleted = YNBool($Row['is_deleted']);

                /* If the file does not exist in the file system, report it as deleted ... which it probably is */
                if ( $is_deleted === false ) {
                    $filename = CDN_PATH . NoNull($Row['cdn_path']);
                    if ( file_exists($filename) === false ) { $is_deleted = true; }
                }

                if ( $data === false ) {
                    $data = array( 'id'         => nullInt($Row['file_id']),
                                   'name'       => (($is_deleted) ? false : NoNull($Row['public_name'])),
                                   'size'       => (($is_deleted) ? false : nullInt($Row['bytes'])),
                                   'type'       => (($is_deleted) ? false : NoNull($Row['type'])),
                                   'hash'       => (($is_deleted) ? false : NoNull($Row['hash'])),
                                   'guid'       => (($is_deleted) ? false : NoNull($Row['guid'])),

                                   'cdn_url'    => (($is_deleted) ? false : $cdn_prefix . NoNull($Row['cdn_path'])),
                                   'medium'     => false,
                                   'thumb'      => false,
                                   'meta'       => false,
                                   'is_image'   => YNBool($Row['is_image']),

                                   'created_at'   => (($is_deleted) ? false : date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at']))),
                                   'created_unix' => (($is_deleted) ? false : strtotime($Row['created_at'])),
                                   'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                   'updated_unix' => strtotime($Row['updated_at']),
                                  );
                }

                /* Add the Meta Value if Applicable */
                if ( $is_deleted === false && NoNull($Row['key']) != '' && YNBool($Row['is_visible']) ) {
                    if ( is_array($meta) === false ) { $meta = array(); }

                    $Key = NoNull($Row['key']);
                    $Val = (is_numeric($Row['value'])) ? nullInt($Row['value']) : NoNull($Row['value']);
                    if ( is_string($Val) && in_array($Val, array('N', 'Y')) ) { $Val = YNBool($Val); }

                    if ( strpos($Key, '.') ) {
                        $kk = explode('.', $Key);
                        if ( array_key_exists($kk[0], $meta) === false ) { $meta[$kk[0]] = array(); }
                        $meta[$kk[0]][$kk[1]] = $Val;

                        /* Do We Have Smaller Versions of the File? */
                        if ( in_array($kk[1], array('has_medium', 'has_thumb')) && $Val === true ) {
                            $localFile = NoNull($Row['local_name']);
                            $ext = getFileExtension($localFile);
                            $propFile = str_replace('.' . $ext, '', $localFile);

                            switch ( $kk[1] ) {
                                case 'has_medium':
                                    $data['medium'] = $cdn_prefix . str_replace($propFile, $propFile . '_medium', $Row['cdn_path']);
                                    break;

                                case 'has_thumb':
                                    $data['thumb'] = $cdn_prefix . str_replace($propFile, $propFile . '_thumb', $Row['cdn_path']);
                                    break;

                                default:
                                    /* Do Nothing */
                            }
                        }

                    } else {
                        $meta[$Key] = $Val;
                    }
                }
            }

            /* Add the Meta to the Object if it's Applicable */
            if ( is_array($meta) ) { $data['meta'] = $meta; }

            /* Do We Need to add a Specific Data Thumbnail? */
            if ( $data['is_image'] === false ) {
                $data['medium'] = $this->settings['HomeURL'] . '/images/file_binary.png';
                $data['thumb'] = $this->settings['HomeURL'] . '/images/file_binary.png';

            }

            /* Return the File Object */
            return $data;
        }

        /* If We're Here, There's No File */
        return false;
    }

    /**
     *  Function Returns a File Object for a Given ID, or an Unhappy Boolean
     */
    private function _getFileByGUID( $FileGuid = '' ) {
        $CleanGUID = nullInt($FileGuid, NoNull($this->settings['file_guid'], $this->settings['guid']));
        if ( mb_strlen($CleanGUID) != 36 ) {
            $this->_setMetaMessage("Invalid File.Guid Provided", 400);
            return false;
        }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[FILE_GUID]'  => sqlScrub($CleanGUID),
                         );
        $sqlStr = readResource(SQL_DIR . '/files/getFileDataByGUID.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $cdn_prefix = getCdnUrl();
            $data = false;
            $meta = false;

            foreach ( $rslt as $Row ) {
                $is_deleted = YNBool($Row['is_deleted']);

                /* If the file does not exist in the file system, report it as deleted ... which it probably is */
                if ( $is_deleted === false ) {
                    $filename = CDN_PATH . NoNull($Row['cdn_path']);
                    if ( file_exists($filename) === false ) { $is_deleted = true; }
                }

                if ( $data === false ) {
                    $data = array( 'guid'       => (($is_deleted) ? false : NoNull($Row['guid'])),
                                   'name'       => (($is_deleted) ? false : NoNull($Row['public_name'])),
                                   'size'       => (($is_deleted) ? false : nullInt($Row['bytes'])),
                                   'type'       => (($is_deleted) ? false : NoNull($Row['type'])),
                                   'hash'       => (($is_deleted) ? false : NoNull($Row['hash'])),

                                   'cdn_url'    => (($is_deleted) ? false : $cdn_prefix . NoNull($Row['cdn_path'])),
                                   'medium'     => false,
                                   'thumb'      => false,
                                   'meta'       => false,
                                   'is_image'   => YNBool($Row['is_image']),

                                   'created_at'   => (($is_deleted) ? false : date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at']))),
                                   'created_unix' => (($is_deleted) ? false : strtotime($Row['created_at'])),
                                   'updated_at'   => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                                   'updated_unix' => strtotime($Row['updated_at']),
                                  );
                }

                /* Add the Meta Value if Applicable */
                if ( $is_deleted === false && NoNull($Row['key']) != '' && YNBool($Row['is_visible']) ) {
                    if ( is_array($meta) === false ) { $meta = array(); }

                    $Key = NoNull($Row['key']);
                    $Val = (is_numeric($Row['value'])) ? nullInt($Row['value']) : NoNull($Row['value']);
                    if ( is_string($Val) && in_array($Val, array('N', 'Y')) ) { $Val = YNBool($Val); }

                    if ( strpos($Key, '.') ) {
                        $kk = explode('.', $Key);
                        if ( array_key_exists($kk[0], $meta) === false ) { $meta[$kk[0]] = array(); }
                        $meta[$kk[0]][$kk[1]] = $Val;

                        /* Do We Have Smaller Versions of the File? */
                        if ( in_array($kk[1], array('has_medium', 'has_thumb')) && $Val === true ) {
                            $localFile = NoNull($Row['local_name']);
                            $ext = getFileExtension($localFile);
                            $propFile = str_replace('.' . $ext, '', $localFile);

                            switch ( $kk[1] ) {
                                case 'has_medium':
                                    $data['medium'] = $cdn_prefix . str_replace($propFile, $propFile . '_medium', $Row['cdn_path']);
                                    break;

                                case 'has_thumb':
                                    $data['thumb'] = $cdn_prefix . str_replace($propFile, $propFile . '_thumb', $Row['cdn_path']);
                                    break;

                                default:
                                    /* Do Nothing */
                            }
                        }

                    } else {
                        $meta[$Key] = $Val;
                    }
                }
            }

            /* Add the Meta to the Object if it's Applicable */
            if ( is_array($meta) ) { $data['meta'] = $meta; }

            /* Do We Need to add a Specific Data Thumbnail? */
            if ( $data['is_image'] === false ) {
                $data['medium'] = $this->settings['HomeURL'] . '/images/file_binary.png';
                $data['thumb'] = $this->settings['HomeURL'] . '/images/file_binary.png';

            }

            /* Return the File Object */
            return $data;
        }

        /* If We're Here, There's No File */
        return false;
    }

    /**
     *  Function Marks a File as Deleted After Scrubbing it from S3
     */
    private function _deleteFile() {
        $FileID = nullInt($this->settings['file_id'], $this->settings['PgSub1']);
        if ( $FileID <= 0 ) { return false; }
        $isGood = false;
        $rVal = "Could Not Delete File.";

        // Confirm Ownership of the File
        $data = $this->_getFilesList();
        if ( is_array($data) === false ) { return "You Do Not Own This File."; }

        // Remove the File from S3 If It Exists
        if ( USE_S3 == 1 ) {
            $cdnPath = str_replace('//' . CDN_DOMAIN . '/', '', $data[0]['cdn_url']);
            if ( $cdnPath != '' ) {
                require_once(LIB_DIR . '/s3.php');

                $s3 = new S3(AWS_ACCESS_KEY, AWS_SECRET_KEY, true, AWS_REGION_NAME);
                $isGood = $s3->deleteObject(CDN_URL, $cdnPath);
                unset($s3);
            }
        }

        // If the File Deletion Was Successful, Update the Database
        if ( $isGood ) {
            $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                              '[FILE_ID]'    => $FileID,
                             );
            $sqlStr = readResource(SQL_DIR . '/files/deleteFileByID.sql', $ReplStr);
            $rslt = doSQLExecute($sqlStr);

            // Collect the File Data One Last Time
            $rVal = $this->_getFilesList();

        } else {
            return "Could Not Remove File from CDN. Please Contact Support.";
        }

        // Return a Happy Array of Data or an Unhappy Message
        return $rVal;
    }


    /** ********************************************************************* *
     *  Internal Functions
     ** ********************************************************************* */
    /**
     *  Function Returns the File Extension of a Given File
     */
    private function _getFileExtension( $FileName, $FileType ) {
        $ext = NoNull(substr(strrchr($FileName,'.'), 1));
        if ( $ext == '' ) {
            switch ( strtolower($FileType) ) {
                case 'audio/x-mp3':
                case 'audio/mp3':
                    $ext = 'mp3';
                    break;

                case 'audio/x-mp4':
                case 'audio/mp4':
                case 'video/mp4':
                    $ext = 'mp4';
                    break;

                case 'audio/x-m4a':
                case 'audio/m4a':
                case 'video/m4a':
                    $ext = 'm4a';
                    break;

                case 'video/m4v':
                    $ext = 'm4v';
                    break;

                case 'audio/mpeg':
                    $ext = 'mpeg';
                    break;

                case 'image/jpeg':
                case 'image/jpg':
                    $ext = 'jpeg';
                    break;

                case 'image/x-windows-bmp':
                case 'image/bmp':
                    $ext = 'bmp';
                    break;

                case 'image/gif':
                    $ext = 'gif';
                    break;

                case 'image/png':
                    $ext = 'png';
                    break;

                case 'image/tiff':
                    $ext = 'tiff';
                    break;

                case 'image/webp':
                    $ext = 'webp';
                    break;

                case 'video/quicktime':
                    $ext = 'mov';
                    break;

                case 'application/x-mpegurl':
                    $ext = 'm3u8';
                    break;

                case 'video/mp2t':
                    $ext = 'ts';
                    break;
            }
        }

        // Return the File Extension
        return $ext;
    }

    /**
     *  Function Determines if the DataType Being Uploaded is Valid or Not
     */
    private function _isValidUploadType( $FileType, $Extension ) {
        $valids = array( 'audio/mp3', 'audio/mp4', 'audio/m4a', 'audio/x-mp3', 'audio/x-mp4', 'audio/mpeg', 'audio/x-m4a',
                         'image/gif', 'image/x-gif', 'image/jpg', 'image/jpeg', 'image/png', 'image/tiff', 'image/bmp', 'image/x-windows-bmp', 'image/webp',
                         'video/quicktime', 'video/m4a', 'video/m4v', 'video/mp4', 'application/x-mpegurl', 'video/mp2t'
                        );

        // Is the FileType in the Array?
        if ( in_array(strtolower($FileType), $valids) ) {
            return true;
        } else {
            if ( NoNull($FileType) == '' && NoNull($Extension) != '' ) {
                if ( in_array(strtolower(NoNull($Extension)), array('jpg', 'jpeg', 'gif', 'png')) ) { return true; }
            }
            writeNote( "Invalid FileType: $FileType", true );
        }

        // If We're Here, No Dice
        return false;
    }

    private function _isResizableImage( $FileType ) {
        $valids = array( 'image/jpg', 'image/jpeg', 'image/gif', 'image/x-gif', 'image/png', 'image/tiff', 'image/bmp', 'image/x-windows-bmp' );

        // Return the Boolean Response
        return in_array(strtolower($FileType), $valids);
    }

    /**
     *  Function Returns a Boolean Stating Whether a Gif is Animated or Not
     */
    private function _isGifImage( $FileType ) {
        $valids = array( 'image/gif', 'image/x-gif' );

        // Return the Boolean Response
        return in_array(strtolower($FileType), $valids);
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