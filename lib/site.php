<?php

/**
 * @author Jason F. Irwin
 * @copyright 2015
 *
 * Class contains the rules and methods called for Site Settings & Creation
 */
require_once( LIB_DIR . '/functions.php');

class Site {
    var $settings;
    var $cache;

    function __construct( $Items ) {
        $this->settings = $Items;
        $this->cache = array();
    }

    /** ********************************************************************* *
     *  Perform Action Blocks
     ** ********************************************************************* */
    public function performAction() {
        $ReqType = NoNull(strtolower($this->settings['ReqType']));
        $rVal = false;

        // Check the User Token is Valid
        if ( !$this->settings['_logged_in']) { return "You Need to Log In First"; }

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
            case 'info':
                $rVal = $this->_getSiteDataByID();
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
                return $this->_createSite();
                break;

            case 'syndication':
            case 'rss':
                return $this->_setSyndicationData();
                break;

            case 'set':
            case '':
                return $this->_setSiteData();
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
                $rVal = array( 'activity' => "[DELETE] /site/$Activity" );
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
        return NoNull($this->settings['type'], 'text/html');
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
        return is_array(NoNull($this->settings['errors'])) ? $this->settings['errors'] : false;
    }

    /** ********************************************************************* *
     *  Public Functions
     ** ********************************************************************* */
    public function createSite() { return $this->_createSite(); }
    public function getSiteData() { return $this->_getSiteData(); }
    public function getSiteDataByGUID($Guid) { return $this->_getSiteDataByGUID($Guid); }
    public function getCacheFolder() { return $this->_getCacheFolder(); }
    public function getChannelID() { return $this->_getChannelID(); }

    /** ********************************************************************* *
     *  Private Functions
     ** ********************************************************************* */
    /**
     *  Function Returns the Cache Folder for a Given Channel
     */
    private function _getCacheFolder() {
        $SiteID = nullInt($this->settings['site_id']);
        $rVal = false;

        if ( $SiteID <= 0 ) {
            $SiteURL = sqlScrub( NoNull($this->settings['site_url'],$_SERVER['SERVER_NAME']) );
            $ReplStr = array( '[SITE_URL]' => strtolower($SiteURL) );
            $sqlStr = readResource(SQL_DIR . '/site/getCacheFolder.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                foreach ( $rslt as $Row ) {
                    $this->settings['channel_id'] = nullInt($Row['channel_id']);
                    $this->settings['site_id'] = nullInt($Row['site_id']);
                }
            }
        }

        // Construct the Cache Folder Name
        if ( nullInt($this->settings['site_id']) > 0 ) {
            $rVal = intToAlpha($this->settings['site_id']);
        }

        // Return the Cache Folder or an Unhappy Boolean
        return $rVal;
    }

    /**
     *  Function Collects the Site Data and Returns an Array
     */
    private function _getSiteData() {
        $SitePass = NoNull($this->settings['site_pass'], $this->settings['site-pass']);
        $SiteURL = sqlScrub( NoNull($this->settings['site_url'],$_SERVER['SERVER_NAME']) );
        $cdnUrl = getCdnUrl();
        if ( is_array($this->cache[strtolower($SiteURL)]) ) { return $this->cache[strtolower($SiteURL)]; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[SITE_TOKEN]' => sqlScrub(mb_substr(NoNull($this->settings['site_token']), 0, 256)),
                          '[SITE_PASS]'  => sqlScrub(mb_substr($SitePass, 0, 512)),
                          '[SITE_URL]'   => strtolower($SiteURL),
                          '[REQ_URI]'    => sqlScrub(mb_substr(NoNull($this->settings['ReqURI'], '/'), 0, 512)),
                         );
        $sqlStr = prepSQLQuery("CALL GetSiteData([ACCOUNT_ID], '[SITE_URL]', '[REQ_URI]', '[SITE_TOKEN]', '[SITE_PASS]');", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                if ( NoNull($Row['site_token']) != '' && $SitePass != '' ) {
                    $LifeSpan = time() + COOKIE_EXPY;
                    setcookie( 'site_token', NoNull($Row['site_token']), $LifeSpan, "/", NoNull(strtolower($_SERVER['SERVER_NAME'])) );
                }

                $cver = NoNull($Row['site_version']) . '-' .
                        NoNull($Row['can_edit'], 'N') . NoNull($Row['site_locked'], 'N') . '-' .
                        NoNull($Row['theme'], 'default') . NoNull($Row['font_family'], 'default') . NoNull($Row['font_size'], 'default') . '-' .
                        NoNull($Row['show_geo'], 'N') . NoNull($Row['show_article'], 'N') . NoNull($Row['show_bookmark'], 'N') .
                        NoNull($Row['show_location'], 'N') . NoNull($Row['show_note'], 'N') . NoNull($Row['show_photo'], 'N') .
                        NoNull($Row['show_quotation'], 'N') . '-' .
                        NoNull($this->settings['_language_code'], $this->settings['DispLang']);

                $coverUrl = '';
                if ( NoNull($Row['cover_img']) != '' ) {
                    $coverUrl = $cdnUrl . NoNull($Row['cover_img']);
                }

                $this->cache[strtolower($SiteURL)] = array( 'HomeURL'         => NoNull($Row['site_url']),
                                                            'api_url'         => getApiUrl(),
                                                            'cdn_url'         => getCdnUrl(),

                                                            'name'            => NoNull($Row['site_name']),
                                                            'description'     => NoNull($Row['description']),
                                                            'keywords'        => NoNull($Row['keywords']),
                                                            'summary'         => NoNull($Row['summary']),
                                                            'location'        => NoNull($Row['theme']),
                                                            'color'           => NoNull($Row['site_color'], 'auto'),
                                                            'font-family'     => NoNull($Row['font_family']),
                                                            'font-size'       => NoNull($Row['font_size']),
                                                            'license'         => NoNull($Row['license'], 'CC BY-NC-ND'),
                                                            'is_default'      => YNBool($Row['is_default']),
                                                            'site_id'         => nullInt($Row['site_id']),
                                                            'site_guid'       => NoNull($Row['site_guid']),
                                                            'site_icon'       => NoNull($Row['site_icon']),
                                                            'site_version'    => NoNull($cver),
                                                            'updated_at'      => date("Y-m-d\TH:i:s\Z", strtotime($Row['site_updated_at'])),
                                                            'updated_unix'    => strtotime($Row['site_updated_at']),
                                                            'has_content'     => YNBool($Row['has_content']),
                                                            'cacheable'       => YNBool(NoNull($Row['cacheable'], 'Y')),

                                                            'can_edit'        => YNBool($Row['can_edit']),
                                                            'show_global'     => YNBool(NoNull($Row['show_global'], 'Y')),
                                                            'show_geo'        => YNBool($Row['show_geo']),
                                                            'show_note'       => YNBool($Row['show_note']),
                                                            'show_article'    => YNBool($Row['show_article']),
                                                            'show_bookmark'   => YNBool($Row['show_bookmark']),
                                                            'show_location'   => YNBool($Row['show_location']),
                                                            'show_photo'      => YNBool($Row['show_photo']),
                                                            'show_quotation'  => YNBool($Row['show_quotation']),

                                                            'page_title'      => NoNull($Row['page_title']),
                                                            'page_type'       => str_replace('post.', '', NoNull($Row['page_type'], 'website')),

                                                            'rss_explicit'    => getExplicitValue($Row['explicit']),
                                                            'rss_cover'       => $coverUrl,
                                                            'rss_author'      => NoNull($Row['rss_author']),
                                                            'rss_mailaddr'    => '',
                                                            'rss_limit'       => nullInt($Row['rss_items']),

                                                            'category_1'      => NoNull($Row['rss_cat_1']),
                                                            'category_1sub'   => NoNull($Row['rss_sub_1']),
                                                            'category_2'      => NoNull($Row['rss_cat_2']),
                                                            'category_2sub'   => NoNull($Row['rss_sub_2']),
                                                            'category_3'      => NoNull($Row['rss_cat_3']),
                                                            'category_3sub'   => NoNull($Row['rss_sub_3']),

                                                            'channel_guid'    => NoNull($Row['channel_guid']),
                                                            'channel_name'    => NoNull($Row['channel_name']),
                                                            'channel_privacy' => NoNull($Row['channel_privacy']),
                                                            'client_guid'     => NoNull($Row['client_guid']),
                                                            'personas'        => $this->_getChannelWritePersonas($Row['channel_id']),

                                                            'protocol'        => (YNBool($Row['https'])) ? 'https' : 'http',
                                                            'https'           => YNBool($Row['https']),
                                                            'do_redirect'     => YNBool($Row['do_redirect']),
                                                            'site_locked'     => YNBool($Row['site_locked']),
                                                           );
            }
        }

        // Return the Site Data
        return $this->cache[strtolower($SiteURL)];
    }

    /**
     *  Function Determines the Site By Channel GUID and Returns the Site Data by ID
     */
    private function _getSiteDataByGUID( $Guid ) {
        $ReplStr = array( '[CHANNEL_GUID]' => sqlScrub($Guid),
                          '[ACCOUNT_ID]'   => nullInt($this->settings['account_id']),
                         );
        $rVal = false;

        if ( $ReplStr['[CHANNEL_GUID]'] != '' && $ReplStr['[ACCOUNT_ID]'] > 0 ) {
            $sqlStr = readResource(SQL_DIR . '/site/getSiteByGUID.sql', $ReplStr);
            $rslt = doSQLQuery($sqlStr);
            if ( is_array($rslt) ) {
                $SiteID = nullInt($rslt[0]['site_id']);
                if ( $SiteID > 0 ) {
                    $this->settings['site_id'] = $SiteID;
                    $rVal = $this->_getSiteDataByID();
                }
            }
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    private function _getSiteDataByID() {
        $SiteID = nullInt($this->settings['site_id'], $this->settings['PgSub1']);
        if ( $SiteID <= 0 ) { return false; }
        $rVal = false;

        $ReplStr = array( '[SITE_ID]' => $SiteID );
        $sqlStr = readResource(SQL_DIR . '/site/getSiteByID.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = false;

            foreach ( $rslt as $Row ) {
                $data = array( 'id'     => nullInt($Row['site_id']),
                               'url'    => NoNull($Row['url']),

                               'custom_url' => NoNull($Row['custom_url']),
                               'license'    => NoNull($Row['license']),
                               'name'       => NoNull($Row['name']),
                               'banner'     => NoNull($Row['banner']),
                               'keywords'   => NoNull($Row['keywords']),

                               'description' => NoNull($Row['description']),
                               'https'       => YNBool($Row['https']),
                               'theme'       => NoNull($Row['theme']),
                               'site_mail'   => NoNull($Row['site_mail']),
                               'created_at'  => NoNull($Row['created_at']),
                               'created_unix'=> strtotime($Row['created_at']),
                               'account'     => false,
                               'channel'     => array( 'id'           => nullInt($Row['channel_id']),
                                                       'name'         => NoNull($Row['channel_name']),
                                                       'type'         => NoNull($Row['channel_type']),
                                                       'privacy'      => NoNull($Row['channel_privacy']),
                                                       'guid'         => NoNull($Row['channel_guid']),
                                                       'created_at'   => NoNull($Row['channel_created_at']),
                                                       'created_unix' => strtotime($Row['channel_created_at']),
                                                      ),
                              );
            }

            // Set the Return Value If We Have Data
            if ( is_array($data) ) { $rVal = $data; }
            unset($data);
        }

        // Return the Array of Data or an Unhappy Boolean
        return $rVal;
    }

    /**
     *  Function Returns an Array of Personas that can Publish to a Given Channel
     */
    private function _getChannelWritePersonas( $ChannelID ) {
        if ( nullInt($this->settings['_account_id']) <= 0 ) { return false; }
        if ( nullInt($ChannelID) <= 0 ) { return false; }

        $ReplStr = array( '[ACCOUNT_ID]' => nullInt($this->settings['_account_id']),
                          '[CHANNEL_ID]' => nullInt($ChannelID),
                         );
        $sqlStr = readResource(SQL_DIR . '/site/getChannelWritePersonas.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $data = array();

            foreach ( $rslt as $Row ) {
                $data[] = array( 'name'       => NoNull($Row['name']),
                                 'guid'       => NoNull($Row['guid']),
                                 'avatar_img' => $this->settings['HomeURL'] . '/avatars/' . NoNull($Row['avatar_img'], 'default.png'),
                                );
            }

            if ( count($data) > 0 ) { return $data; }
        }

        // If We're Here, There are None
        return false;
    }

    /**
     *  Function Records Site, SiteMeta, and Channel Data. Then Returns the getSiteDataByID Array
     */
    private function _setSiteData() {
        // Perform Some Basic Error Checking
        if ( NoNull($this->settings['channel_guid'], $this->settings['channel-guid']) == '' ) { $this->_setMetaMessage("Invalid Channel GUID Supplied", 400); }
        if ( NoNull($this->settings['site_name'], $this->settings['site-name']) == '' ) { $this->_setMetaMessage("Invalid Site Name Supplied", 400); }
        $isWebReq = YNBool(NoNull($this->settings['web-req'], $this->settings['webreq']));

        $Visibility = 'visibility.public';
        $SitePass = '';
        if ( YNBool(NoNull($this->settings['site_locked'], $this->settings['site-locked'])) ) {
            $Visibility = 'visibility.password';

            $SitePass = NoNull($this->settings['site_pass'], $this->settings['site-pass']);
            if ( mb_strlen($SitePass) <= 6 ) {
                $this->_setMetaMessage("Supplied Site Password is Far Too Weak", 400);
                return false;
            }
            if ( $SitePass == str_repeat('*', 12) ) { $SitePass = ''; }
        }

        // Determine if the Theme is valid
        $validThemes = array( 'anri', 'resume', 'default', 'gtd' );
        $siteTheme = strtolower(NoNull($this->settings['site_theme'], $this->settings['site-theme']));
        if ( in_array($siteTheme, $validThemes) === false ) {
            $siteTheme = 'anri';
        }

        // Determine if the Font Family is valid
        $validFontFam = array( 'font-family.auto', 'font-family.lato', 'font-family.librebaskerville', 'font-family.open-sans', 'font-family.ubuntu', 'font-family.quicksand' );
        $fontFamily = strtolower(NoNull($this->settings['font_family'], $this->settings['font-family']));
        if ( in_array($fontFamily, $validFontFam) === false ) {
            $fontFamily = 'font-family.auto';
        }
        $fontFamily = str_replace('font-family.', '', $fontFamily);

        // Determine if the Font Size is valid
        $validFontSize = array( 'font-size.xs', 'font-size.sm', 'font-size.md', 'font-size.lg', 'font-size.xl', 'font-size.xx' );
        $fontSize = strtolower(NoNull($this->settings['font_size'], $this->settings['font-size']));
        if ( in_array($fontSize, $validFontSize) === false ) {
            $fontSize = 'font-size.md';
        }
        $fontSize = str_replace('font-size.', '', $fontSize);

        // Determine if the Dark theme is enabled, disabled, or auto
        $validColour = array( 'theme.auto', 'theme.dark', 'theme.light' );
        $ColourTheme = strtolower(NoNull($this->settings['site_color'], $this->settings['site-color']));
        if ( in_array($ColourTheme, $validColour) === false ) {
            $ColourTheme = 'theme.auto';
        }
        $ColourTheme = str_replace('theme.', '', $ColourTheme);

        // Get a Site.ID Value
        $ReplStr = array( '[CHANNEL_GUID]' => sqlScrub(NoNull($this->settings['channel_guid'], $this->settings['channel-guid'])),
                          '[ACCOUNT_ID]'   => nullInt($this->settings['_account_id']),
                          '[SITE_NAME]'    => sqlScrub(NoNull($this->settings['site_name'], $this->settings['site-name'])),
                          '[SITE_DESCR]'   => sqlScrub(NoNull($this->settings['site_descr'], $this->settings['site-descr'])),
                          '[SITE_KEYS]'    => sqlScrub(NoNull($this->settings['site_keys'], $this->settings['site-keys'])),
                          '[SITE_THEME]'   => sqlScrub($siteTheme),
                          '[SITE_COLOR]'   => sqlScrub($ColourTheme),
                          '[SITE_FFAMILY]' => sqlScrub($fontFamily),
                          '[SITE_FSIZE]'   => sqlScrub($fontSize),
                          '[PRIVACY]'      => sqlScrub($Visibility),
                          '[SITE_PASS]'    => sqlScrub($SitePass),

                          '[SHOW_GLOBAL]'  => BoolYN(YNBool(NoNull($this->settings['show_global'], NoNull($this->settings['show-global'], 'Y')))),
                          '[SHOW_GEO]'     => BoolYN(YNBool(NoNull($this->settings['show_geo'], $this->settings['show-geo']))),
                          '[SHOW_NOTE]'    => BoolYN(YNBool(NoNull($this->settings['show_note'], $this->settings['show-note']))),
                          '[SHOW_BLOG]'    => BoolYN(YNBool(NoNull($this->settings['show_article'], $this->settings['show-article']))),
                          '[SHOW_BKMK]'    => BoolYN(YNBool(NoNull($this->settings['show_bookmark'], $this->settings['show-bookmark']))),
                          '[SHOW_LOCS]'    => BoolYN(YNBool(NoNull($this->settings['show_location'], $this->settings['show-location']))),
                          '[SHOW_QUOT]'    => BoolYN(YNBool(NoNull($this->settings['show_quotation'], $this->settings['show-quotation']))),
                          '[SHOW_PHOT]'    => BoolYN(YNBool(NoNull($this->settings['show_photo'], $this->settings['show-photo']))),
                         );
        $sqlStr = prepSQLQuery("CALL SetSiteData( [ACCOUNT_ID], '[CHANNEL_GUID]',
                                                 '[SITE_NAME]', '[SITE_DESCR]', '[SITE_KEYS]', '[SITE_THEME]', '[SITE_COLOR]', '[SITE_FFAMILY]', '[SITE_FSIZE]',
                                                 '[PRIVACY]', '[SITE_PASS]',
                                                 '[SHOW_GEO]', '[SHOW_NOTE]', '[SHOW_BLOG]', '[SHOW_BKMK]', '[SHOW_LOCS]', '[SHOW_QUOT]', '[SHOW_PHOT]', '[SHOW_GLOBAL]');", $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) {
                if ( nullInt($Row['version_id']) <= 0 ) {
                    if ( $isWebReq ) { redirectTo($this->settings['HomeURL'] . '/403', $this->settings); }
                }
            }
        }

        /* If This is a Web Request, Redirect the Visitor */
        if ( $isWebReq ) { redirectTo($this->settings['HomeURL'], $this->settings); }

        /* Get the Updated Information */
        $rVal = $this->_getSiteData();

        /* Return the Information */
        return $rVal;
    }

    /**
     *  Function Records the Syndication-specific Information for a Channel
     */
    private function _setSyndicationData() {
        // Perform Some Basic Error Checking
        if ( NoNull($this->settings['channel_guid'], $this->settings['channel-guid']) == '' ) {
            $this->_setMetaMessage("Invalid Channel GUID Supplied", 400);
            return false;
        }
        $isWebReq = YNBool(NoNull($this->settings['web-req'], $this->settings['webreq']));
        $cdnUrl = getCdnUrl();

        // Determine if the Explicit value is valid
        $validOpts = array( 'c', 'n', 'y' );
        $siteExplicit = strtolower(NoNull($this->settings['explicit'], $this->settings['site-explicit']));
        if ( in_array($siteExplicit, $validOpts) === false ) { $siteExplicit = 'n'; }

        // Determine if the RSS Items value is valid
        $validOpts = array( '1', '15', '25', '50', '100', '250', '99999' );
        $siteRssItems = strtolower(NoNull($this->settings['rss-items'], $this->settings['rss-count']));
        if ( in_array($siteRssItems, $validOpts) === false ) { $siteRssItems = '15'; }

        // Determine if the RSS License is Valid
        $validOpts = array( 'cc cc0', 'cc by', 'cc by-sa', 'cc by-nc', 'cc by-nc-sa', 'cc by-nd', 'cc by-nc-nd' );
        $rssLicense = NoNull($this->settings['rss-license'], $this->settings['license']);
        if ( in_array($rssLicense, $validOpts) === false ) { $rssLicense = 'CC BY-NC-SA'; }

        // Determine if the Cover Image is Valid
        $coverImg = str_replace($cdnUrl, '', NoNull($this->settings['cover-img'], $this->settings['cover']));
        if ( $coverImg != '' ) {
            $coverFile = CDN_PATH . $coverImg;
            if ( file_exists($coverFile) === false ) { $coverImg = ''; }
        }

        // Determine the Correct Categories
        $RssCat1 = $this->_getPodcastCategoriesFromKey(NoNull($this->settings['category_1'], $this->settings['category-1']));
        $RssCat2 = $this->_getPodcastCategoriesFromKey(NoNull($this->settings['category_2'], $this->settings['category-2']));
        $RssCat3 = $this->_getPodcastCategoriesFromKey(NoNull($this->settings['category_3'], $this->settings['category-3']));

        // Let's Record the Data
        $ReplStr = array( '[CHANNEL_GUID]'  => sqlScrub(NoNull($this->settings['channel_guid'], $this->settings['channel-guid'])),
                          '[ACCOUNT_ID]'    => nullInt($this->settings['_account_id']),

                          '[RSS_AUTHOR]'    => sqlScrub(NoNull($this->settings['rss_author'], $this->settings['rss-author'])),
                          '[RSS_SUMMARY]'   => sqlScrub(NoNull($this->settings['rss_summary'], $this->settings['rss-summary'])),
                          '[RSS_LICENSE]'   => sqlScrub(strtoupper($rssLicense)),
                          '[RSS_ITEMS]'     => nullInt($siteRssItems),
                          '[EXPLICIT]'      => sqlScrub(strtoupper($siteExplicit)),
                          '[COVER_IMG]'     => sqlScrub($coverImg),

                          '[RSS_CAT_1]'     => sqlScrub($RssCat1['category']),
                          '[RSS_SUB_1]'     => sqlScrub($RssCat1['sub']),
                          '[RSS_CAT_2]'     => sqlScrub($RssCat2['category']),
                          '[RSS_SUB_2]'     => sqlScrub($RssCat2['sub']),
                          '[RSS_CAT_3]'     => sqlScrub($RssCat3['category']),
                          '[RSS_SUB_3]'     => sqlScrub($RssCat3['sub']),

                          '[SQL_SPLITTER]'  => sqlScrub(SQL_SPLITTER),
                         );
        $sqlStr = readResource(SQL_DIR . '/site/setSyndicationMeta.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);

        // If This is a Web Request, Redirect the Visitor
        if ( $isWebReq ) { redirectTo($this->settings['HomeURL'] . '/syndication?sid=' . md5(json_encode($ReplStr)), $this->settings); }

        // Get the Updated Information
        $rVal = $this->_getSiteData();

        // Return the Information
        return $rVal;
    }

    private function _getPodcastCategoriesFromKey( $Category ) {
        if ( array_key_exists('categories', $this->cache) === false ) {
            $this->cache['categories'] = getPodcastCategories($this->strings);
        }
        $data = $this->cache['categories'];

        if ( is_array($data) ) {
            foreach ( $data as $Key=>$Val ) {
                if ( strtolower($Key) == strtolower($Category) ) {
                    return array( 'category' => $Key,
                                  'sub'      => ''
                                 );
                }
                if ( is_array($Val['subcategories']) ) {
                    foreach ( $Val['subcategories'] as $sub ) {
                        if ( is_array($sub) ) {
                            foreach ( $sub as $kk=>$vv ) {
                                if ( strtolower($kk) == strtolower($Category) ) {
                                    return array( 'category' => $Key,
                                                  'sub'      => $kk
                                                 );
                                }
                            }
                        }
                    }
                }
            }
        }

        // Return Empty Values
        return array( 'category' => '',
                      'sub'      => ''
                     );
    }

    /**
     *  Function Returns the Site.ID Given a Channel.ID
     */
    private function _getSiteIDFromChannelID( $ChannelID ) {
        if ( nullInt($ChannelID) <= 0 ) { return false; }
        $rVal = false;

        $ReplStr = array( '[CHANNEL_ID]' => nullInt($ChannelID) );
        $sqlStr = readResource(SQL_DIR . '/site/getSiteFromChannel.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            foreach ( $rslt as $Row ) { $rVal = nullInt($Row['site_id']); }
        }

        // Return the Site.ID or an Unhappy Boolean
        return $rVal;
    }

    /**
     *  Function Returns the RSS Information for a given site
     */
    private function _getRSSData() {
        $SiteID = $this->_getSiteIDFromChannelID( $this->settings['channel_id'] );
        if ( $SiteID <= 0 || $SiteID == false ) { $SiteID = nullInt($this->settings['site_id']); }
        if ( $SiteID <= 0 ) { return false; }
        $rVal = false;

        $ReplStr = array( '[SITE_ID]' => $SiteID );
        $sqlStr = readResource(SQL_DIR . '/site/getSiteMeta.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            $SiteAuthor = '';
            $SiteCover = '';
            $SiteDescr = '';
            $SiteOwner = 0;
            $SiteName = '';
            $SiteMail = '';
            $SiteTags = '';
            $data = array();

            foreach ( $rslt as $Row ) {
                switch ( strtolower($Row['key']) ) {
                    case 'site.author':
                        $SiteAuthor = NoNull($Row['value']);
                        $data['author'] = $SiteAuthor;
                        break;

                    case 'site.name':
                        $SiteName = NoNull($Row['value']);
                        $data['name'] = $SiteName;
                        break;

                    case 'site.mailaddr':
                        $SiteMail = NoNull($Row['value']);
                        $data['mailaddr'] = $SiteMail;
                        break;

                    case 'site.owner':
                        $SiteOwner = nullInt($Row['value']);
                        break;

                    case 'site.subtitle':
                        $SiteDescr = NoNull($Row['value']);
                        $data['subtitle'] = $SiteDescr;
                        break;

                    case 'site.tags':
                        $SiteTags = NoNull($Row['value']);
                        $data['tags'] = $SiteTags;
                        break;

                    case 'rss.author':
                        $data[ str_replace('rss.', '', $Row['key']) ] = NoNull($Row['value'], $SiteAuthor);
                        break;

                    case 'rss.name':
                        $data[ str_replace('rss.', '', $Row['key']) ] = NoNull($Row['value'], $SiteName);
                        break;

                    case 'rss.mailaddr':
                        $data[ str_replace('rss.', '', $Row['key']) ] = NoNull($Row['value'], $SiteMail);
                        break;

                    case 'rss.subtitle':
                        $data[ str_replace('rss.', '', $Row['key']) ] = NoNull($Row['value'], $SiteDescr);
                        break;

                    case 'rss.tags':
                        $data[ str_replace('rss.', '', $Row['key']) ] = NoNull($Row['value'], $SiteTags);
                        break;

                    default:
                        $data[ str_replace('rss.', '', $Row['key']) ] = NoNull($Row['value']);
                }
            }

            // If We Have Data, Return It
            if ( count($data) > 0 ) {
                $data['cover-img'] = '//' . CDN_URL . '/images/podcast_default.jpg';
                if ( NoNull($data['cover']) != '' ) {
                    $data['cover-img'] = '//' . CDN_URL . '/' . intToAlpha($SiteOwner) . '/' . $data['cover'];
                }
                $this->settings['site_id'] = $SiteID;
                $data['site'] = $this->_getSiteDataByID();
                $data['site_id'] = $SiteID;
                $rVal = $data;
            }
        }

        // Return an Array of the Site Data
        return $rVal;
    }

    /**
     *  Function Records the RSS Information for a given site
     */
    private function _setRSSData() {
        $Valids = array('author', 'cover', 'explicit', 'license', 'name', 'mailaddr', 'subtitle', 'summary', 'tags', 'itunesurl',
                        'category1', 'category2', 'category3', 'subcategory1', 'subcategory2', 'subcategory3');
        $SiteID = $this->_getSiteIDFromChannelID( $this->settings['channel_id'] );
        if ( $SiteID <= 0 || $SiteID == false ) { $SiteID = nullInt($this->settings['site_id']); }
        if ( $SiteID <= 0 ) { return false; }

        // Construct the SQL Queries
        $sqlStr = '';
        foreach ( $Valids as $Key ) {
            $ReplStr = array( '[SITE_ID]' => nullInt($SiteID),
                              '[METAKEY]' => sqlScrub(strtolower('rss.' . $Key)),
                              '[METAVAL]' => sqlScrub(NoNull($this->settings[$Key])),
                             );
            if ( $sqlStr != '' ) { $sqlStr .= SQL_SPLITTER; }
            $sqlStr .= readResource(SQL_DIR . '/site/setSiteMeta.sql', $ReplStr);
        }

        // Execute the Query If We Have One
        if ( $sqlStr != '' ) { $isOK = doSQLExecute($sqlStr); }

        // Return the Site Meta
        return $this->_getRSSData();
    }

    /** ********************************************************************* *
     *  Site Creation / Deletion
     ** ********************************************************************* */
    /**
     *  Function Confirms if a Domain is Available or Not
     */
    private function _chkDomainAvailable( $prefix ) {
        $excludes = array( 'api', 'app', 'www', 'web', 'www3', '', 'docs', 'dev',
                           'fuck', 'shit', 'cunt', 'fail', 'sex', 'sexy', 'tit', 'tits', );
        if ( in_array($prefix, $excludes) ) { return false; }
        $rVal = false;

        $ReplStr = array( '[PREFIX]' => sqlScrub($prefix) );
        $sqlStr = readResource(SQL_DIR . '/site/chkSiteAvail.sql', $ReplStr);
        $rslt = doSQLQuery($sqlStr);
        if ( is_array($rslt) ) {
            if ( nullInt($rslt[0]['sites']) == 0 ) { $rVal = true; }
        }

        // Return the Boolean Value
        return $rVal;
    }

    /**
     *  Function Creates a Site for a Given Account
     */
    private function _createSite() {
        $CleanPrefix = strtolower(NoNull($this->settings['prefix'], $this->settings['user_name']));
        $isAvail = $this->_chkDomainAvailable($CleanPrefix);
        if ( !$isAvail ) { return "Site URL Is In Use"; }
        $SiteID = 0;
        $rVal = false;

        // Create the Site Record
        $ReplStr = array( '[USER_ID]'   => nullInt($this->settings['on_behalf_of_id'], $this->settings['account_id']),
                          '[SITE_URL]'  => sqlScrub($CleanPrefix . SITE_DOMAIN),
                          '[THEME]'     => sqlScrub(SITE_THEME),
                          '[VISIBLE]'   => 'visibility.public',
                          '[CHAN_TYPE]' => 'channel.website',
                          '[CHAN_ID]'   => 0,
                          '[SITE_ID]'   => 0,
                         );
        $sqlStr = readResource(SQL_DIR . '/site/createSite.sql', $ReplStr);
        $rslt = doSQLExecute($sqlStr);
        if ( $rslt > 0 ) { $SiteID = nullInt($rslt); }

        // Create the Channel Record
        if ( $SiteID > 0 ) {
            $ReplStr['[SITE_ID]'] = nullInt($SiteID);
            $sqlStr = readResource(SQL_DIR . '/site/createChannel.sql', $ReplStr);
            $ChannelID = doSQLExecute($sqlStr);

            // Set the Channel Author Records
            if ( $ChannelID > 0 ) {
                $ReplStr['[CHAN_ID]'] = $ChannelID;
                $sqlStr = readResource(SQL_DIR . '/site/createChannelAuthor.sql', $ReplStr);
                $isOK = doSQLExecute($sqlStr);

                $this->settings['site_id'] = $SiteID;
                $rVal = $this->_getSiteDataByID();
            }
        }

        // Return the Site Array or an Unhappy Boolean Value
        return $rVal;
    }

    /**
     *  Function Marks a Site (and all it's content?) as Deleted
     */
    private function _deleteSite() {

    }

    private function _addChannelAuthor() {

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