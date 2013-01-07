RedirectMatch 301 ^(/plugins/mediawiki/wiki/[-a-zA-Z0-9_]*)/*$ $1/index.php
AliasMatch ^/plugins/mediawiki/wiki/[-a-zA-Z0-9_]*/api.php /usr/share/codendi/plugins/mediawiki/www/api.php
AliasMatch ^/plugins/mediawiki/wiki/[-a-zA-Z0-9_]*/index.php /usr/share/codendi/plugins/mediawiki/www/index.php
AliasMatch ^/plugins/mediawiki/wiki/([-a-zA-Z0-9_]*)/images/(.*) /var/lib/codendi/plugins/mediawiki/wikidata/$1/images/$2
AliasMatch ^/plugins/mediawiki/wiki/([-a-zA-Z0-9_]*)/skins/(.*) /usr/share/mediawiki115/skins/$2
AliasMatch ^/plugins/mediawiki/wiki/([-a-zA-Z0-9_]*)/extensions/(.*) /usr/share/mediawiki115/extensions/$2
AliasMatch ^/plugins/mediawiki/dumps/(.*)$ /var/lib/codendi/plugins/mediawiki/dumps/$1

<Directory /var/lib/codendi/plugins/mediawiki/dumps>
        Options -FollowSymLinks +Indexes
        AllowOverride None
</Directory>
