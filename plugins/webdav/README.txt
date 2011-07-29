INSTALLATION
============

if you access to webdav by "https://webdav.domain":

Under /etc/httpd/conf/httpd.conf: add a virtual host to WebDAV domain name:

ServerName webdav.domain:443
Include conf.d/php.conf
DocumentRoot /usr/share/codendi/plugins/webdav/www

AliasMatch ^/(.*) /usr/share/codendi/plugins/webdav/www/index.php

Options Indexes MultiViews
AllowOverride None
Order allow,deny
Allow from all


if you access to webdav by "https://domain/plugins/webdav/":

Under "/etc/httpd/conf.d/codendi_aliases.conf" add:

# 0- WebDAV plugin web/php pages 
AliasMatch ^/plugins/webdav/(.*) /usr/share/codendi/plugins/webdav/www/index.php
<DirectoryMatch "/usr/share/codendi/plugins/webdav/www/">
    Options Indexes MultiViews
    AllowOverride None
    Order allow,deny
    Allow from all
</DirectoryMatch>


-To add locks directory:

    /usr/bin/install -d -g codendiadm -o codendiadm -m 00750 /var/tmp/codendi_cache/plugins/webdav/locks/


CONFIGURATION
=============

1. Go to PluginsAdministartion
2. Configure properties
3. Make the plugin available.
