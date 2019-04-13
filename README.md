# Streams

### An Open-Source, IndieWeb-friendly Publishing Platform

The Following is a list of notes regarding the use and configuration of Stremas.

## General Requirements

You will need:

* a web server running Apache 2.4.x and PHP 7.0 or newer
* MySQL 5.7 or above, ideally 8.0 or newer (MariaDB 10.x is supported)

## LAMP Configuration Notes

### Linux Notes
This code has been tested to run on Ubuntu Server 18.04 LTS, though it should run on any version of Linux released in the last 5 years. Your mileage may very. Test often. Test well.

### Apache Notes

The following modules must be loaded:

* mod-php
* mod-rewrite
* mod-headers

### MySQL Notes

MySQL 8.0 is the database engine used for all testing, development, and deployment. The tables are all configured with InnoDB. Other database engines such as XtraDB have not been tested, so reliability is unknown. Avoid using MyISAM as this engine has been deprecated and is not ideal for highly concurrent environments.

### PHP Notes

The following modules are required:

* mbstring
* dev
* xml
* json
* mysql
* gd
* curl
* pear

### Other Setup Requirements

In addition to the basic LAMP stack, the following items need to be taken into account.

* the `htaccess` file in `/public` must be renamed `.htaccess`
* Apache must be configured to honour the `.htaccess` overrides
* Streams can use Amazon S3 storage for files, but is off by default
* Streams can enforce HTTPS redirects (and ideally should use it)
* Streams is designed to run on servers with as little as 1GB RAM

### Basic Web Server -- Minimum Recommended

* Ubuntu Server 18.04 LTS
* Dual-Core CPU
* 2GB RAM
* 10GB Storage

### Windows Configuration Notes

It is not recommended that Streams run on Windows in a WAMP-like fashion. It has not been tested and, as of this writing, will not be supported.

### Optional Components

There are some optional pieces to the puzzle that might make things a little better. These things include:

* something to drink
* good music
* a faithful dog