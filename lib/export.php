<?php

/**
 * @author Jason F. Irwin
 *
 * Class contains the rules and methods called for Export Functions
 */
require_once(LIB_DIR . '/functions.php');

class Export {
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
        $ReqType = strtolower(NoNull($this->settings['ReqType'], 'GET'));

        /* Check the User Token is Valid */
        if ( !$this->settings['_logged_in']) {
            $this->_setMetaMessage("You Need to Log In First", 401);
            return false;
        }

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
                // Do Nothing
        }

        /* If we're here, there was nothing to do */
        return false;
    }

    private function _performGetAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));

        switch ( $Activity ) {
            case '':
                return false;
                break;

            default:
                return $this->_performExport();
        }

        /* If we're here, there was nothing to do */
        return false;
    }

    private function _performPostAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));
        $rVal = false;

        switch ( $Activity ) {
            case 'unlock':
                return $this->_unlockExport();
                break;

            default:
                // Do Nothing
        }

        /* If we're here, there was nothing to do */
        return false;
    }

    private function _performDeleteAction() {
        $Activity = strtolower(NoNull($this->settings['PgSub2'], $this->settings['PgSub1']));

        switch ( $Activity ) {
            case '':
                return false;
                break;

            default:
                // Do Nothing
        }

        /* If we're here, there was nothing to do */
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

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function marks an Account as being able to Export. This is done to reduce
     *      the chance of content scrapers are unable to trigger exports with
     *      spoofed credentials.
     */
    private function _unlockExport() {
        $CleanType = NoNull($this->settings['output']);

        // Generate a Key
        $ReplStr = array( '[TOKEN_ID]'  => nullInt($this->settings['_token_id']),
                          '[TOKEN_GUID]' => sqlScrub($this->settings['_token_guid']),
                         );
        $sqlStr = prepSQLQuery("CALL GetExportKey([TOKEN_ID], '[TOKEN_GUID]');", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                return array( 'unlock' => NoNull($Row['value']),
                              'output' => $CleanType,
                             );
            }
        }

        // If we're here, we cannot generate a key
        $this->_setMetaMessage( "Cannot generate an Export Unlock token", 401 );
        return array();
    }

    /**
     *  Function triggers an Export operation
     */
    private function _performExport() {
        $ExportType = NoNull($this->settings['PgSub1'], $this->settings['for']);
        $data = false;

        switch ( strtolower($ExportType) ) {
            case 'dayone':
                return $this->_exportForDayOne();
                break;

            case 'filesonly':
            case 'zip':
                return $this->_buildZipArchive();
                break;

            case 'json':
                break;

            case 'wordpress':
                return $this->_exportForWordPress();
                break;

            default:
                $this->_setMetaMessage( "Invalid Export Format Provided", 401 );
        }

        /* If We're Here, There's No Data to Export */
        return array();
    }

    /**
     *  Function returns a JSON object for import into Day One
     */
    private function _exportForDayOne() {
        $ExportCode = NoNull($this->settings['unlock']);
        $records = 0;
        $cnt = 0;

        if ( mb_strlen($ExportCode) <= 30 ) {
            $this->_setMetaMessage( "Invalid Export Unlock Code Provided", 401 );
            return array();
        }

        /* Prep the Output Array */
        $data = array( 'metadata' => array( 'version' => '1.0' ),
                       'entries' => array(),
                      );

        // Extract the Data
        $rslt = $this->_collectForDayOne( $ExportCode, $cnt );
        while ( is_array($rslt) && count($rslt) > 0 ) {
            foreach ( $rslt as $Row ) {
                $text = NoNull($Row['post_text']);
                if ( NoNull($Row['title']) != '' ) { $text = "# " . NoNull($Row['title']) . "\n\n" . $text; }

                $post = array( 'duration'     => 0,
                               'text'         => $this->_convertToMarkdown($text),
                               'sourceString' => '10centuries-' . NoNull($Row['guid']),
                               'starred'      => YNBool($Row['is_starred']),
                               'uuid'         => NoNull($Row['uuid']),
                               'creationDate' => date("Y-m-d\TH:i:s\Z", strtotime($Row['created_at'])),
                               'modifiedDate' => date("Y-m-d\TH:i:s\Z", strtotime($Row['updated_at'])),
                               'timezone'     => NoNull($Row['timezone']),
                              );

                /* Add the Tags (If Applicable) */
                if ( NoNull($Row['tags']) != '' ) {
                    $post['tags'] = explode(',', NoNull($Row['tags']));
                }

                /* Add the Geo-Location Data (If Applicable) */
                if ( NoNull($Row['latitude']) != '' ) {
                    $post['location'] = array( 'region' => array( 'center' => array( 'longitude' => nullInt($Row['longitude']),
                                                                                     'latitude'  => nullInt($Row['latitude']),
                                                                                    ),
                                                                  'radius' => 75
                                                                 ),
                                               'longitude' => nullInt($Row['longitude']),
                                               'latitude'  => nullInt($Row['latitude']),
                                              );

                    if ( NoNull($Row['altitude']) != '' ) {
                        $post['location']['region']['center']['altitude'] = nullInt($Row['altitude']);
                        $post['location']['altitude'] = nullInt($Row['altitude']);
                    }
                }

                /* Add the Post Object to the Output Object */
                $data['entries'][] = $post;
                $records++;
            }
            $cnt++;

            if ( $cnt < 100 ) {
                $rslt = $this->_collectForDayOne( $ExportCode, $cnt );

            } else {
                $rslt = false;
            }
        }

        /* Let's Save the Data Object */
        if ( $records > 0 ) {
            $outDIR = TMP_DIR . '/export/' . strtolower($ExportCode);
            $outFile = $outDIR . '/10Centuries.json';
            $zipFile = $outDIR . '/ExportForDayOne.zip';

            if ( checkDIRExists( TMP_DIR . '/export' ) && checkDIRExists( $outDIR ) ) {
                checkDIRExists( $outDIR . '/audio' );
                checkDIRExists( $outDIR . '/photos' );
                checkDIRExists( $outDIR . '/video' );

                $fh = fopen($outFile, 'w');
                fwrite($fh, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                fclose($fh);

                // Unset the Data Object (to free up memory)
                $data = false;

                // Zip the Folder
                $rootPath = realpath($outDIR);

                // Initialize archive object
                $zip = new ZipArchive();
                $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

                // Create recursive directory iterator
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY );
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($rootPath) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }

                // Zip archive will be created only after closing object
                $zip->close();
            }

            return array( 'records' => $records,
                          'url' => $this->settings['HomeURL'] . '/receive/' . strtolower($ExportCode),
                          'zip' => array( 'name' => NoNull(str_replace(array($outDIR, '/'), '', $zipFile)),
                                          'size' => filesize($zipFile),
                                         ),
                         );
        }

        /* There's No Data, Return an Empty Array */
        $this->_setMetaMessage( "Could not export data. Invalid Unlock Token provided.", 400 );
        return array();
    }

    /**
     *  Function queries the database for export files with a limit of 10K per request (for web server resoure reasons)
     *      If no records are found, an unhappy boolean is returned, which should end the <while> loop.
     */
    private function _collectForDayOne( $ExportCode, $start_at ) {
        $ReplStr = array( '[ACCOUNT_ID]'  => nullInt($this->settings['_account_id']),
                          '[EXPORT_CODE]' => sqlScrub($ExportCode),
                          '[START_AT]'    => nullInt($start_at),
                         );
        $sqlStr = prepSQLQuery("CALL GetExportForDayOne([ACCOUNT_ID], '[EXPORT_CODE]', [START_AT]);", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) && count($rslt) ) { return $rslt; }

        /* If We're Here, There's Nothing */
        return false;
    }

    /**
     *  Function receives a Content body and converts it to Markdown if required
     */
    private function _convertToMarkdown( $text ) {
        $ReplStr = array( '<p>' => '', '</p>' => "\n\n",
                          '<i>' => '*', '</i>' => '*', '<em>' => '*', '</em>' => '*',
                          '<b>' => '*', '</b>' => '*', '<strong>' => '*', '</strong>' => '*',
                          '<li>' => '* ', '</li>' => "\n",
                          '<ol>' => '', '<ul>' => '', '</ol>' => "\n", '</ul>' => "\n",
                         );
        $text = str_replace(array_keys($ReplStr), array_values($ReplStr), $text);

        // Handle the <a> tags
        preg_match_all('~<a .*?href=[\'"]+(.*?)[\'"]+.*?>(.*?)</a>~ims', $text, $result);
        if (!empty($result)) {
            foreach ( $result[0] as $Key=>$Link ) {
                $mdUrl = '[' . NoNull($result[2][$Key]) . '](' . NoNull($result[1][$Key]) . ')';
                $text = str_replace($Link, $mdUrl, $text);
            }
        }

        // Handle the <img> tags

        // Return the Cleaned Up Data
        return strip_tags($text);
    }

    /**
     *  Function collects all the files in the Account's directory and packages them into a .zip
     */
    private function _buildZipArchive() {
        $zipFile = CDN_PATH . '/' . intToAlpha($this->settings['_account_id']) . '/archive-' . time() . '.zip';
        $zip = new ZipArchive;
        $files = array();

        $Location = CDN_PATH . '/' . intToAlpha($this->settings['_account_id']);
        if ( file_exists($Location) ) {
            foreach ( glob($Location . "/*") as $filename ) {
                $files[] = $filename;
            }
        }

        if ( count($files) > 0 ) {
            if ( $zip->open($zipFile, ZipArchive::CREATE) ) {
                foreach ( $files as $src ) {
                    $ext = getFileExtension($src);
                    $type = getMimeFromExtension($ext);
                    $path = NoNull( substr(strrchr($type,'/'), 1) );
                    $name = NoNull( substr(strrchr($src,'/'), 1) );

                    // Add the file if this isn't a resized version of the original
                    if ( mb_strpos($name, '_thumb') === false && mb_strpos($name, '_medium') === false ) {
                        $zip->addFile($src, "$path/$name");
                    }
                }
                $zip->close();

                // Return the Data
                $cdnUrl = getCdnUrl();
                return array( 'url' => str_replace(CDN_PATH, $cdnUrl, $zipFile),
                              'size' => filesize($zipFile)
                             );
            }
        }

        // If we're here, it didn't work
        return false;
    }

    /** ********************************************************************* *
     *  WordPress Export Functions
     *
     *  Note: Export operations are expensive. An account cannot issue more than
     *        one at any given time. Export summaries should be cached for the
     *        current Site.version.
     ** ********************************************************************* */
    /**
     *  Function constructs the WordPress XML file and returns a cached export summary
     */
    private function _exportForWordPress() {
        $validTypes = array('post.article', 'post.note', 'post.quotation', 'post.bookmark', 'post.draft', 'post.page');
        $forceRestart = YNBool(NoNull($this->settings['restart'], $this->settings['force']));
        $CleanGuid = NoNull($this->settings['channel_guid'], $this->settings['_channel_guid']);
        $CleanSize = nullInt($this->settings['file_size'], $this->settings['size']);
        $CleanType = "'post.article'";
        $idx = 1;

        /* Determine which Post.type records should be included */
        $typeList = NoNull($this->settings['post_types'], $this->settings['types']) . ',';
        $filter = array();

        $tts = explode(',', $typeList);
        if ( count($tts) > 0 ) {
            foreach ( $tts as $tt ) {
                $tname = strtolower(NoNull($tt));
                if ( mb_strlen($tname) > 5 && in_array($tname, $validTypes) ) {
                    if ( in_array($tname, $filter) === false ) { $filter[] = "'" . $tname . "'"; }
                }
            }

            /* If we have valid items, set the CleanType variable */
            if ( is_array($filter) && count($filter) > 0 ) { $CleanType = implode(',', $filter); }
        }

        /* Perform some basic error checking */
        if ( mb_strlen($CleanGuid) != 36 ) {
            $this->_setMetaMessage("Invalid Channel GUID provided", 400);
            return false;
        }

        if ( $CleanSize > 50 ) { $CleanSize = 50; }
        if ( $CleanSize < 1 ) { $CleanSize = 25; }

        /* Ensure execution time is increased to 30 minutes to handle the effort */
        set_time_limit(1800);

        /* Prep some common variables */
        $fileKey = substr(md5(substr('00000000' . nullInt($this->settings['_account_id']), -8) . '-' . $CleanGuid . $CleanType . date('Y-m-d')), 0, 8);
        $fileLmt = (1024 * 1024) * $CleanSize;
        $fileIdx = 0;

        $fileName = 'wordpress-' . $fileKey . '-' . substr('000' . $fileIdx, -3) . '.xml';
        $cacheKey = md5($fileName);
        $SiteUrl = NoNull($this->settings['HomeURL']);
        $cdnPrefix = $cdnFile = CDN_PATH . '/export';
        $PostCount = 0;

        /* Construct the "Current Situation" array */
        $stats = getCacheObject($cacheKey);
        if ( is_array($stats) && count($stats) > 0 ) {
            $udts = nullInt($stats['udts']);
            $step = NoNull($stats['step']);
            $age = nullInt(time() - $udts);

            /* Has the Job Stalled? */
            if ( $udts > 1000 && $age > 120 ) {
                if ( $forceRestart ) {
                    unlink($cdnFile . '/' . $fileName);
                    $stats = false;
                    $step = '';

                } else {
                    $this->_setMetaMessage("The job has not progressed in $age seconds. If this job has stalled, force a restart.", 400);
                    return false;
                }
            }

            /* Is there a step already in progress? */
            if ( mb_strlen($step) > 3 ) {
                return array( 'url'   => false,
                              'file'  => false,
                              'type'  => false,
                              'bytes' => false,

                              'is_active' => true,
                              'message'   => "Current Step: $step",
                             );
            }
        }

        /* If this is a new task, ensure the array is built */
        if ( is_array($stats) === false ) {
            $stats = array( 'step' => 'Initialising ...',
                            'udts' => time(),
                           );
        }

        /* Determine the Cache Filename for the XML Output and check to see if a file already exists with this name */
        if ( checkDIRExists($cdnFile) ) {
            $cdnFile .= '/' . $fileName;

            if ( file_exists($cdnFile) ) {
                $mtime = filemtime($cdnFile);
                $bytes = filesize($cdnFile);

                /* If the file already exists, is large, and relatively new, return an output array */
                if ( nullInt($bytes) > 512 && nullInt($mtime) > 1000 && (time() - $mtime) < 900 ) {
                    $cdnUrl = getCdnUrl();

                    return array( 'url'   => $cdnUrl . '/export/' . $fileName,
                                  'file'  => $fileName,
                                  'type'  => getMimeFromExtension($cdnFile),
                                  'bytes' => filesize($cdnFile),

                                  'is_active' => false,
                                  'message'   => "",
                                 );
                }
            }
        }

        /* Build the basic query parameter array [Note: the NoNull for POST_TYPES is NOT a typo] */
        $ReplStr = array( '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[CHANNEL_GUID]' => sqlScrub($CleanGuid),
                          '[POST_TYPES]'   => NoNull($CleanType),
                          '[START_POS]'    => nullInt($pos),
                         );

        /* Determine the number of items that will be exported */
        $sqlStr = readResource(SQL_DIR . '/export/getChannelPostCount.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $PostCount = nullInt($Row['post_count']);
            }
        }

        /* If there are no posts, no point continuing */
        if ( $PostCount <= 0 ) {
            $this->_setMetaMessage("There are no Posts to export", 404);
            return false;
        }

        $stats = array( 'step' => 'Writing Preliminary XML data',
                        'udts' => time(),
                       );
        setCacheObject($cacheKey, $stats);

        /* Prep the Basic XML Replacement array */
        $xmlItems = array( '[GENERATOR]'     => GENERATOR . " (" . APP_VER . ")",
                           '[APP_NAME]'      => APP_NAME,
                           '[APP_VER]'       => APP_VER,
                           '[LANG_CD]'       => validateLanguage(NoNull($this->settings['_language_code'], $this->settings['DispLang'])),
                           '[GENERATED_AT]'  => date('Y-m-d H:i'),
                           '[GENERATED_XML]' => date("D, d M Y H:i:s O"),
                           '[YEAR]'          => date('Y'),
                           '[CHANNEL_GUID]'  => NoNull($CleanGuid),

                           '[SITE_URL]'      => $SiteUrl,
                           '[SITE_NAME]'     => '',
                           '[SITE_DESCR]'    => '',
                           '[SITE_KEYS]'     => '',
                           '[SITE_GUID]'     => '',
                           '[SITE_VERSION]'  => '',

                           '[AUTHOR_ID]'     => nullInt($this->settings['_account_id']),
                           '[DISPLAY_NAME]'  => NoNull($this->settings['_display_name']),
                           '[FIRST_NAME]'    => '',
                           '[LAST_NAME]'     => '',
                           '[AUTHOR_EMAIL]'  => NoNull($this->settings['_email']),
                          );

        $sqlStr = readResource(SQL_DIR . '/export/getChannelDetails.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $SiteUrl = ((YNBool($Row['https'])) ? 'https' : 'http') . '://' . NoNull($Row['site_url']);

                $xmlItems['[SITE_URL]']   = $SiteUrl;
                $xmlItems['[SITE_NAME]']  = NoNull($Row['site_name']);
                $xmlItems['[SITE_DESCR]'] = NoNull($Row['site_description']);
                $xmlItems['[SITE_KEYS]']  = NoNull($Row['site_keywords']);
                $xmlItems['[SITE_GUID]']  = NoNull($Row['site_guid']);
                $xmlItems['[SITE_VERSION]'] = nullInt($Row['site_version']);

                $xmlItems['[AUTHOR_ID]']    = intToAlpha($Row['author_id']);
                $xmlItems['[DISPLAY_NAME]'] = NoNull($Row['display_name'], $Row['account_displayname']);
                $xmlItems['[FIRST_NAME]']   = NoNull($Row['first_name'], $Row['account_firstname']);
                $xmlItems['[LAST_NAME]']    = NoNull($Row['last_name'], $Row['account_lastname']);
                $xmlItems['[AUTHOR_EMAIL]'] = NoNull($Row['email'], $Row['account_email']);
            }
        }

        $xmlOut = readResource(FLATS_DIR . '/templates/export.wordpress-header.xml', $xmlItems);

        /* Create the XML File and place the start of the WordPress data */
        $fh = fopen($cdnFile, 'w');
        fwrite($fh, $xmlOut);

        $stats = array( 'step' => 'Writing Post Tag data',
                        'udts' => time(),
                       );
        setCacheObject($cacheKey, $stats);

        /* Collect the Tags for the Channel */
        $sqlStr = readResource(SQL_DIR . '/export/getChannelTagList.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                $vv = array( '[TAG_IDX]'  => nullInt($idx),
                             '[TAG_KEY]'  => NoNull($Row['key']),
                             '[TAG_NAME]' => NoNull($Row['name']),
                             '[COUNTER]'  => nullInt($Row['posts']),
                            );

                $xmlOut = readResource(FLATS_DIR . '/templates/export.wordpress-tag.xml', $vv);
                if ( mb_strlen($xmlOut) > 3 ) { fwrite($fh, $xmlOut); }
                $idx++;
            }
        }

        /* Prep the Markdown Parser */
        require_once(LIB_DIR . '/posts.php');
        $post = new Posts($this->settings);

        /* Collect the Posts for the Channel in blocks of 1000 */
        $CleanStr = array( '</ol>' => "</ol>\r\n", '</ul>' => "</ul>\r\n", '</li>' => "</li>\r\n", '</p>' => "</p>\r\n" );
        $postIds = array();
        $hasPosts = true;
        $loops = 0;
        $cnt = 0;

        $stats['step'] = 'Writing Posts (0 of $PostCount)';
        $stats = array( 'step' => "Writing Posts (0 of $PostCount)",
                        'udts' => time(),
                       );
        setCacheObject($cacheKey, $stats);

        while ( $hasPosts ) {
            $sqlStr = readResource(SQL_DIR . '/export/getChannelPosts.sql', $ReplStr);
            writeNote($sqlStr, true);

            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) && count($rslt) > 0 ) {
                foreach ( $rslt as $Row ) {
                    $pid = nullInt($Row['post_id']);
                    if ( in_array($pid, $postIds) === false ) {
                        $postIds[] = $pid;
                        $cnt++;

                        $tags = '';
                        if ( mb_strlen(NoNull($Row['post_tags'])) > 0 ) {
                            $json = json_decode('[' . NoNull($Row['post_tags']) . ']', true);
                            if ( is_array($json) ) {
                                $template = '<category domain="post_tag" nicename="[KEY]"><![CDATA[[NAME]]]></category>';

                                foreach ( $json as $tag ) {
                                    $kk = array( '[NAME]' => NoNull($tag['name']),
                                                 '[KEY]'  => NoNull($tag['key']),
                                                );
                                    $tags .= tabSpace(3) . str_replace(array_keys($kk), array_values($kk), $template) . "\r\n";
                                }
                            }
                        }

                        /* Parse and Clean the Content */
                        $content = $post->getMarkdownHTML($Row['value'], $Row['post_id'], false, false);
                        $content = str_replace(array_keys($CleanStr), array_values($CleanStr), $content);

                        $vv = array( '[AUTHOR_ID]'  => intToAlpha($Row['author_id']),
                                     '[POST_ID]'    => nullInt($Row['post_id']),
                                     '[TITLE]'      => NoNull($Row['title']),
                                     '[CONTENT]'    => $content,
                                     '[EXCERPT]'    => strip_tags(NoNull($Row['excerpt'])),
                                     '[POST_URL]'   => $SiteUrl . NoNull($Row['canonical_url']),
                                     '[POST_GUID]'  => NoNull($Row['guid']),
                                     '[PRIVACY]'    => NoNull($Row['privacy_type']),
                                     '[PUBLISH_AT]' => date("D, d M Y H:i:s O", strtotime($Row['publish_at'])),
                                     '[POST_TYPE]'  => NoNull($Row['type']),
                                     '[POST_HASH]'  => NoNull($Row['hash']),
                                     '[CREATED_AT]' => date("Y-m-d H:i:s", strtotime($Row['created_at'])),
                                     '[UPDATED_AT]' => date("Y-m-d H:i:s", strtotime($Row['updated_at'])),
                                     '[POST_TAGS]'  => $tags,
                                    );
                        $xmlOut = readResource(FLATS_DIR . '/templates/export.wordpress-post.xml', $vv);
                        if ( mb_strlen($xmlOut) > 3 ) { fwrite($fh, $xmlOut); }
                    }

                    /* If the file is larger than X MB, break it up */
                    if ( filesize($cdnFile) > $fileLmt ) {
                        /* Close the output XML file */
                        $xmlOut = readResource(FLATS_DIR . '/templates/export.wordpress-footer.xml');
                        if ( mb_strlen($xmlOut) > 3 ) { fwrite($fh, $xmlOut); }
                        fclose($fh);

                        /* Determine the next file name */
                        $fileIdx++;
                        $fileName = 'wordpress-' . $fileKey . '-' . substr('000' . $fileIdx, -3) . '.xml';
                        $cdnFile = $cdnPrefix . '/' . $fileName;

                        /* Open a new file and populate the header */
                        $xmlOut = readResource(FLATS_DIR . '/templates/export.wordpress-header.xml', $xmlItems);

                        /* Create the XML File and place the start of the WordPress data */
                        $fh = fopen($cdnFile, 'w');
                        fwrite($fh, $xmlOut);
                    }
                }

                /* Set the Position */
                $ReplStr['[START_POS]'] = $cnt;

                /* Update the Step */
                $stats = array( 'step' => "Writing Posts ($cnt of $PostCount)",
                                'udts' => time(),
                               );
                setCacheObject($cacheKey, $stats);

            } else {
                $hasPosts = false;
            }

            /* Let's make sure we're not looping with one record 1000 times */
            writeNote("Loop: $loops | Row Count: " . count($rslt), true);

            /* Do not allow an infinite loop. 1-million items "ought to be enough for everyone" */
            $loops++;
            if ( $loops > 100 ) { $hasPosts = false; }
        }
        unset($post);

        writeNote("Exited Post Loop", true);

        /* Close the output XML file */
        $xmlOut = readResource(FLATS_DIR . '/templates/export.wordpress-footer.xml');
        if ( mb_strlen($xmlOut) > 3 ) { fwrite($fh, $xmlOut); }

        /* Close the file handler */
        fclose($fh);

        /* Clear the Cache Object */
        setCacheObject($cacheKey, array());

        /* Return the Output array or an unhappy boolean */
        if ( file_exists($cdnFile) && filesize($cdnFile) > 512 ) {
            $cdnUrl = getCdnUrl();

            return array( 'url'   => $cdnUrl . '/export/' . $fileName,
                          'file'  => $fileName,
                          'type'  => getMimeFromExtension($cdnFile),
                          'bytes' => filesize($cdnFile),

                          'is_active' => false,
                          'message'   => number_format($PostCount + $idx) . " items exported in " . number_format($fileIdx + 1) . " files.",
                         );
        } else {
            $this->_setMetaMessage("Could not create export file", 400);
            return false;
        }
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