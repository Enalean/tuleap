#!/bin/bash
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# THIS FILE IS THE PROPERTY OF XEROX AND IS ONLY DISTRIBUTED WITH A
# COMMERCIAL LICENSE OF CODEX. IT IS *NOT* DISTRIBUTED UNDER THE GNU
# PUBLIC LICENSE.
#
#  $Id$
#
#      Originally written by Laurent Julliard 2004, CodeX Team, Xerox
#
#
# This file will eventually become the script that migrates a 
# a site running CodeX 2.0 to CodeX 2.2
#

cat <<EOF
When migrating a 2.0 site to 2.2 here are the things that must be done:

- create /etc/codex/(conf|themes|themes/css|themes/images|documentation|site-content)
- move /etc/local.inc /etc/codex/conf/local.inc
- add $sys_win_domain in /etc/codex/conf/local.inc
- upgrade mailman from 2.0 to 2.1
- change mailman crontab cron/qrunner becomes bin/qrunner -o -r All
- move codex.zone and codex_full.zone from /usr/local/domain/data/primary/ into /var/named
- Add an svn and svn1 alias in the /var/named/codex.zone file
- if /home/httpd/site-content/custom exists then move all files (and subdir) into /etc/codex/site-documentation
- if /etc/motd.inc exists move it into /etc/codex/site-content/en_US/others/motd.txt
- if /home/httpd/SF/www/css/custom exist then move all subdirs in /etc/codex/themes/css
- if /home/httpd/SF/www/images/custom exist then move all subdirs in /etc/codex/themes/images
- Copy SF/etc/httpd.conf.dist in /etc/httpd/conf/httpd.conf and make the necessary changes
- Copy SF/etc/ssl.conf.dist in /etc/httpd/conf.d/ssl.conf and make the necessary changes
- Copy SF/etc/php.conf.dist in /etc/httpd/conf.d/php.conf and make the necessary changes
- Copy /home/httpd/documentation/user_guide/xml/en_US/ParametersLocal.dtd to /etc/codex/documentation/user_guide/xml/en_US/ParametersLocal.dtd (do a mkdir -p to create full path first)
- if SF/utils/custom/default_page.php exist then copy it to /etc/codex/site-content/en_US/others
- Faire un diff sur database_structure.sql and database_initvalues.sql avec la v 2.0 pour reverifier tous les chagement dans la base et les mettre dans le script d'upgrade.
- delete Foundry (type=2) from the group_type table
- delete tables people_job, people_job_category, people_job_inventory, people_job_status
- delete field help_requests from table stats_agr_project, stats_project, stats_project_tmp


-> FOR Subversion
=================
- Copy SF/etc/subversion.conf.dist in /etc/httpd/conf.d/subversion.conf and make the necessary changes
- Install all necessary RPMs for subversion:
Server: remove with nodeps db4-devel, db4-utils (db42-utils conflicts with native db4-utils-4.1.xx), install db42 , subversion-server, subversion, neon (use --nodeps because it requires apr-0.95 and httpd >=2.0.48 but those 2 conflict with the httpd packages)
install in one go httpd, mod_ssl, apr, apr-util
- create the /svnroot directory with perm and mod sourceforge.sourceforge 755
- touch /etc/httpd/conf/codex_htpasswd
- copy /home/httpd/SF/utils/svn/commit-email.pl to /usr/local/bin/ mod sourceforge.sourceforge 755
- ALTER groups table and create new fields in the group database use_svn, svn_box, svn_tracker svn_events_mailing_list svn_events_mailing_header svn_preamble
  ALTER TABLE groups ADD COLUMN svn_box VARCHAR(20) NOT NULL DEFAULT 'svn1' AFTER cvs_box;
  ALTER TABLE groups ADD COLUMN use_svn int(11) NOT NULL DEFAULT '1' AFTER use_cvs;
  ALTER TABLE groups ADD COLUMN svn_tracker INT(11) NOT NULL DEFAULT '1';
  ALTER TABLE groups ADD COLUMN svn_events_mailing_list VARCHAR(64) binary DEFAULT NULL;
  ALTER TABLE groups ADD COLUMN svn_events_mailing_header VARCHAR(64) binary DEFAULT NULL;
  ALTER TABLE groups ADD COLUMN svn_preamble TEXT NOT NULL;

- (Upgrade) CREATE table group_svn_history
- DONE Modify Project.class and usesSvn
- (Upgrade) Add sys_svn_host to local.inc
- (Upgrade) Add SYS_SVN_HOST to /etc/codex/documentation/user_guide/xml/en_US/ParametersLocal.dtd
- (Upgrade) Add a svn entry into the service table for each project. Set the svn service to disabled by default for all projects
- (Upgrade) Create all svn_xxxxx tables for SVN commit tracking
- For mass update on trackers create table script_state

TODO in install script:
- DONE - Update httpd.conf with the version for Apache 2.0 from codex.xerox.com (and finish the cleanup in the file)
- DONE - Put %..% patterns in all *.dist files and change them at installation time
- Change AliasFile in /etc/mail/sendmail.cf at installation time
- Create /etc/mail/local-host-names at installation time
- DONE - change /home/httpd/SF/utils/underworld-dummy/mail_aliases.pl (wrapper is now called mailman - Installation file already updated)
- DONE - Substitute _domain_name_ in the database init file with the domain name before creating the database
- DONE - Update installation script with subversion installation (see upgrade notes above for installation)
- DONE touch /etc/httpd/conf/codex_htpasswd
- DONE copy /home/httpd/SF/utils/svn/commit-email.pl to /usr/local/bin/ mod sourceforge.sourceforge 755
- DONE Create new fields in the group database use_svn, svn_box, svn_tracker svn_events_mailing_list svn_events_mailing_header svn_preamble 

TODO
- DONE - Change /usr/local/domain/data/primary/ into /var/named (standard place)
- DONE - Change /etc/local.inc into /etc/codex/conf/local.inc in all source code
- DONE - Move motd.inc in site-content
- DONE - Move default_page.php in site-content and look for customized version in /etc/codex/site-content
- Add Include conf.d/subversion.conf in httpd.conf
- Add subversion.conf.dist in SF/etc

Notes:
- DONE - to create a new SVN repo in new_parse use mkdir /svnroot/codex; chmod 775 /svnroot/xxxxx; svnadmin create /svnroot/xxxxx; chown -R sourceforge.xxxxx /svnroot/xxxxx; 
- gensmbpasswd crashes because load_client_codepage: filename /usr/share/samba/codepages/codepage.850 is not in /usr/share/samba/codepages (to be investigated and fixed - gensmbpasswd must be recompiled against Smaba 3.0 because codepage are now handled through .so files)
- For Subversion
 . permission management in user management PHP script (read/write/none globally)
 . by directory permission (to be investigated)
 . mail notification hook to put in place
 . subversion query interface
 . integrate viewcvs with cvs and subversion

- For viewcvs
 . cvs checkout viewcvs 1.0-dev cvs -d:pserver:anonymous@cvs.sourceforge.net:/cvsroot/viewcvs co -d viewcvs-1.0 viewcvs
 . viewcvs-install and force a return on the install dir question
 . chown -R sourceforge.sourceforge /usr/local/viewcvs-*
 . cp -a /usr/local/viewcvs-x.y.z/cgi/viewcvs.cgi /home/httpd/cgi-bin/
 . Must install swig and subversion python binding 
rpm -Uvh ~/packages-rhel3/RPMS_CodeX/subversion/subversion-1.0.1/subversion-python-1.0.1-1.wbel3.i386.rpm ~/packages-rhel3/RPMS_CodeX/subversion/subversion-1.0.1/swig-1.3.19-2.i386.rpm
 . install enscript (make sure it is in the list of mandatory RedHat RPMs at insntallation - DONE)

Subversion Integration
=====================
This is development notes not migration

- Check all other places in source code where CVS is mentioned to see
what must be done to bring the same features for SVN as for CVS
(statistics, source code access logs, etc...)

- Problem with time. when changing time zone the time shown in the Web
query interface doesn't change.


EOF