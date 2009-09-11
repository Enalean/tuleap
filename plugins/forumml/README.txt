==== INSTALLATION ===

## All operations below must be run as root

## Install ForumMl temp dir
/usr/bin/install -d -g codendiadm -o codendiadm -m 00750 /var/run/forumml

## Install ForumMl hook
/usr/bin/install -g codendiadm -o codendiadm -m 06755 /usr/share/codendi/plugins/forumml/bin/mail_2_DB.pl /usr/lib/codendi/bin

## Install requested RPMS:
# Standard rpm (shiped with RHEL/CentOs):
php-pear

# Codendi rpm (you might need to rebuild them, see below):
mailman-2.1.9-5.codendi.i386.rpm
php-pear-Mail-Mbox-0.1.2-1.codendi.noarch.rpm
php-pear-Mail-Mime-1.3.0-1.codendi.noarch.rpm

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
$> /usr/share/codendi/src/utils/php-launcher /usr/share/codendi/plugins/forumml/bin/mail_2_DB.php codex-support 2

## To import ML archives of all Codendi projects, for which the plugin is enabled
run 'ml_arch_2_DB.pl' script:
$> /usr/share/codendi/plugins/forumml/bin/ml_arch_2_DB.pl

==== REBUILD RPMS ====
## You might need to rebuild few RPMs:
cd /usr/share/codendi/rpm/SPECS
rpmbuild -ba mailman.codendi.spec
rpmbuild -ba php-pear-Mail-Mime.spec
rpmbuild -ba php-pear-Mail-Mbox.spec
