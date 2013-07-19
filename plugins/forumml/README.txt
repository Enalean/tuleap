==== RPM INSTALLATION ====

After having installed plugin-forumml, you should update /etc/mailman/mm_cfg.py and set
at the end:
PUBLIC_EXTERNAL_ARCHIVER = '/usr/lib/codendi/bin/mail_2_DB.pl %(listname)s ;'
PRIVATE_EXTERNAL_ARCHIVER = '/usr/lib/codendi/bin/mail_2_DB.pl %(listname)s ;'

Then restart mailman:
$> service mailman restart

==== INSTALLATION BY HAND ====

## All operations below must be run as root

## Install ForumMl temp dir
/usr/bin/install -d -g codendiadm -o codendiadm -m 00750 /var/run/forumml

## Install ForumMl hook
/usr/bin/install -g codendiadm -o codendiadm -m 06755 /usr/share/codendi/plugins/forumml/bin/mail_2_DB.pl /usr/lib/codendi/bin

## Install requested RPMS:
# Standard rpm (shiped with RHEL/CentOs):
php-pear

## Install some pear packages
pear install Mail

# Codendi rpm (you might need to rebuild them, see below):
mailman-2.1.9-5.codendi.i386.rpm

## Install requested Pear packages
# You need pear >= 1.6. You can check with 'pear version'
# See Upgrade PEAR below if needed

# Server with internet access
# You need following packages/min versions:
# Mail             1.1.14
# Mail_Mbox        0.6.1
# Mail_Mime        1.5.2
# Mail_mimeDecode  1.5.0
pear install Mail Mail_Mbox Mail_mimeDecode Mail_Mime

# Server without internet access
Mail-1.1.14.tgz
Mail_Mbox-0.6.1.tgz
Mail_mimeDecode-1.5.0.tgz
Mail_Mime-1.5.2.tgz

pear install Mail-1.1.14.tgz Mail_Mbox-0.6.1.tgz Mail_mimeDecode-1.5.0.tgz Mail_Mime-1.5.2.tgz

## Update /etc/httpd/conf.d/php.conf
Add /usr/share/pear in include_path

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



==== Ugrade PEAR ====
## Upgrade to PEAR 1.9

# Server with internet access
pear upgrade --force PEAR

# Server without internet access
Download following packages:
PEAR-1.9.0.tgz
Archive_Tar-1.3.3.tgz
Console_Getopt-1.2.3.tgz
XML_Util-1.2.1.tgz
Structures_Graph-1.0.2.tgz

pear upgrade --force PEAR-1.9.0.tgz Archive_Tar-1.3.3.tgz Console_Getopt-1.2.3.tgz XML_Util-1.2.1.tgz Structures_Graph-1.0.2.tgz



==== REBUILD RPMS ====
## You might need to rebuild few RPMs:
cd /usr/share/codendi/rpm/SPECS
rpmbuild -ba mailman.codendi.spec

