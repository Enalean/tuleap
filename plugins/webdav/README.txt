INSTALLATION
============

Option 1: if you access to webdav by "http[s]://webdav.domain.tld":

    Under /etc/httpd/conf/httpd.conf: 
    Add a virtual host to WebDAV domain name (before definition of "Project web site virtual hosts alias" if any) :
    <VirtualHost webdav.domain.tld:80>
        Include conf.d/php.conf
        DocumentRoot /usr/share/tuleap/plugins/webdav/www

        AliasMatch ^/(.*) /usr/share/tuleap/plugins/webdav/www/index.php

        <Directory /usr/share/tuleap/plugins/webdav/www>
            Options Indexes MultiViews
            AllowOverride None
            Order allow,deny
            Allow from all
        </Directory>
    </VirtualHost>

Then plugin configuration:
webdav_base_uri = "/";
webdav_host     = "webdav.domain.tld":

Option 2: if you access to webdav by "https://domain.tld/plugins/webdav/" (doesnt work with Windows XP clients):

    Under "/etc/httpd/conf.d/codendi_aliases.conf" add (in alias matches definition, add it to the beginning):

    # 0- WebDAV plugin web/php pages 
    AliasMatch ^/plugins/webdav/(.*) /usr/share/tuleap/plugins/webdav/www/index.php
    <DirectoryMatch "/usr/share/tuleap/plugins/webdav/www/">
        Options Indexes MultiViews
        AllowOverride None
        Order allow,deny
        Allow from all
    </DirectoryMatch>

Then plugin configuration:
webdav_base_uri = "/plugins/webdav";
webdav_host     = "domain.tld":

CONFIGURATION
=============

1. Go to PluginsAdministartion
2. Configure properties
3. Make the plugin available.

DEVELOPERS
==========

- If you install by and (without RPM package) do not forget to install SabreDav = 1.4
- And add locks directory:
    /usr/bin/install -d -g codendiadm -o codendiadm -m 00750 /var/tmp/tuleap/plugins/webdav/locks/
