<?php

/**
 * @author Jason F. Irwin
 */
define('APP_ROOT', '/');                                    // The Application Root Location
define('APP_NAME', 'Streams');                              // The Application Name
define('CACHE_EXPY', 3600);                                 // Number of Seconds Cache Files Can Survive
define('COOKIE_EXPY', 7200);                                // Number of Seconds Mortal Cookies Live For
define('SHA_SALT', 'FooBarBeeGnoFoo');                      // Salt Value used with SHA256 Encryption (Changing Renders Existing Encrypted Data Unreadable)

define('ENABLE_MULTILANG', 1);                              // Enables Multi-Language Support
define('ENABLE_CACHING', 0);                                // Enables Resource Caching
define('DEFAULT_LANG', 'en-us');                            // The Default Application Language (If Not Already Defined)
define('DEBUG_ENABLED', 0);                                 // Set the Debug Level (If Not Already Defined)
define('HAMMER_LIMIT', 120);                                // Maximum Number of Requests from a Device Per Minute (Per Server)
define('ENFORCE_PHPVERSION', 1);                            // Enforce a Requirement that the Server Is Running at Least PHP v.X
define('MIN_PHPVERSION', 70000);                            // The Lowest Version of PHP to Accept

define('DEFAULT_DOMAIN', '');                               // The domain to use when generating new sites as a subdomain
define('RATE_LIMIT', 5000);                                 // The Maximum Number of Hourly Calls to the API By a Given Token
define('TOKEN_PREFIX', 'CSQAA_');                           // The Authentication Token Validation Prefix
define('TOKEN_EXPY', 30);                                   // Number of Days Tokens Can Sit Idle
define('TIMEZONE', 'UTC');                                  // The Primary Timezone for the Server

define('DB_SERV', '127.0.0.1');                             // Write Database Server (Usually the Primary Database)
define('DB_NAME', '');                                      // Write Database Name
define('DB_USER', '');                                      // Database Login
define('DB_PASS', '');                                      // Database Password
define('DB_CHARSET', 'utf8mb4');                            // Database Character Set
define('DB_COLLATE', 'utf8mb4_unicode_ci');                 // Database Collation
define('DB_PERSIST', 1);                                    // Database Connection Persistence
define('SQL_SPLITTER', '[-|-|-]');                          // The Split String for Multi-SQL Statements

define('MSSQLDB_SERV', '');                                 // Write Database Server (Usually the Primary Database)
define('MSSQLDB_NAME', '');                                 // Write Database Name
define('MSSQLDB_USER', '');                                 // Database Login
define('MSSQLDB_PASS', '');                                 // Database Password

define('CRON_KEY', '');                                     // A key for use with scheduled jobs

define('PASSWORD_LIFE', 10000);                             // Number of Days a Password Can Be Used for Before Expiring (Default to 28 Years)
define('PASSWORD_UNIQUES', 0);                              // Specifies Whether Passwords Must Be Unique (for an Account) | 0 = No, 1 = Yes

define('CDN_UPLOAD_LIMIT', 128);                            // The Maximum File Size Upload (in MB)
define('CDN_PATH', '/var/www/files');                       // The Path of the CDN's Non-Public Files
define('CDN_POOL_SIZE', 5);                                 // The Size of the CDN's Storage Pool (in GB)
define('CDN_DOMAIN', '');                                   // The Single Domain to Use for all CDN-based files
define('API_DOMAIN', '');                                   // The Single Domain to Use for all API-based requests

define('SYND_INTERVAL', 60);                                // How often will we check external syndication feeds for content?
define('SYND_LIMIT', 10);                                   // How many syndication feeds should be checked at any given time?

define('MAIL_SMTPAUTH', 1);                                 // Use SMTP Authentication (This Should Be 1)
define('MAIL_SMTPSECURE', "ssl");                           // The Type of SMTP Security (SSL, TLS, etc.)
define('MAIL_MAILHOST', "");                                // The Host Address of the Mail Server
define('MAIL_MAILPORT', 465);                               // The Port for the Mail Server (465, 590, 990, etc.)
define('MAIL_USERNAME', "");                                // The Login Name for the Mail Server
define('MAIL_USERPASS', "");                                // The Password for the Mail Server
define('MAIL_ADDRESS', "");                                 // The Default Reply-To Address Attached to Emails
define('MAIL_RATELIMIT', 15);                               // The Maximum Number of Messages to Send per Minute

define('CLOUDFLARE_API_KEY', '');                           // The CloudFlare Global API Key
define('CLOUDFLARE_API_URL', '');                           // The CloudFlare API Address
define('CLOUDFLARE_EMAIL', '');                             // The CloudFlare Email Address
define('CLOUDFLARE_SITEDNSTYPE', '');                       // The Type of DNS Record Created in CloudFlare (A, AAAA, CNAME, etc.)
define('CLOUDFLARE_SITEDNSVAL', '');                        // The Value for the DNS Record creatd in CloudFlare (An IP Address or CNAME Pointer)

define('SCRIPTURE_API_KEY', '');                            // The Api.Bible Scripture API Key
define('SCRIPTURE_API_URL', '');                            // The Api.Bible Scripture API Address

define('USE_S3', 0);                                        // Use Amazon S3 Storage
define('AWS_ACCESS_KEY', '');                               // The Amazon S3 Access Key
define('AWS_SECRET_KEY', '');                               // The Amazon S3 Secret Key
define('CLOUDFRONT_URL', '');                               // The Amazon Cloudfront URL

define('USE_W3W', 0);                                       // Use What3Words with GeoTagging
define('W3W_KEY', '');                                      // The Application Key for What3Words

define('USE_MAPBOX', 0);                                    // Use Mapbox for Static Map Images
define('MAPBOX_KEY', '');                                   // The Access Key for the Mapbox API

define('B2_CDN_URL', '');                                   // The Backblaze CDN URL
define('B2_APP_ID', '');                                    // The Backblaze CDN Application ID
define('B2_APP_KEY', '');                                   // The Backblaze CDN Application Key

?>