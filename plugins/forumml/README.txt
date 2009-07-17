==== INSTALLATION ===

## All operations below must be run as root

## Install ForumMl temp dir
/usr/bin/install -d -g codendiadm -o codendiadm -m 00750 /var/run/forumml

## Install ForumMl hook
/usr/bin/install -g codendiadm -o codendiadm -m 06755 /usr/share/codendi/plugins/forumml/bin/mail_2_DB.pl /usr/lib/codendi/bin

## Update /etc/php.ini
include_path = "/usr/share/codendi/src/www/include:/usr/share/codendi/src:/usr/share/pear:."

## Rebuild mailman RPM and install it

## Update Mailman config to enable the Hook
# edit /etc/mm_cfg.py and set
PUBLIC_EXTERNAL_ARCHIVER = '/usr/lib/codendi/bin/mail_2_DB.pl %(listname)s ;'
PRIVATE_EXTERNAL_ARCHIVER = '/usr/lib/codendi/bin/mail_2_DB.pl %(listname)s ;'

## restart mailman
service mailman restart

==== Import list ====

## To import ML archives of specific projects, into ForumML DB, 
run 'mail_2_DB.php' script.
1st argument: list name
2nd argument: 2
$> php-launcher plugins/forumml/bin/mail_2_DB.php codex-support 2

## To import ML archives of all Codendi projects, for which the plugin is enabled
run 'ml_arch_2_DB.pl' script:
$> ./plugins/forumml/bin/ml_arch_2_DB.pl
