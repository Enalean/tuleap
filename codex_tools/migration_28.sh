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
#  $Id: migration_24.sh 1776 2005-06-23 14:29:05Z guerin $
#
#      Originally written by Laurent Julliard 2004, CodeX Team, Xerox
#
#  This file is part of the CodeX software and must be placed at the same
#  level as the CodeX, RPMS_CodeX and nonRPMS_CodeX directory when
#  delivered on a CD or by other means
#
#  This script migrates a site running CodeX 2.6 to CodeX 2.8
#



# path to command line tools
GROUPADD='/usr/sbin/groupadd'
GROUPDEL='/usr/sbin/groupdel'
USERADD='/usr/sbin/useradd'
USERDEL='/usr/sbin/userdel'
USERMOD='/usr/sbin/usermod'
MV='/bin/mv'
CP='/bin/cp'
LN='/bin/ln'
LS='/bin/ls'
RM='/bin/rm'
TAR='/bin/tar'
MKDIR='/bin/mkdir'
RPM='/bin/rpm'
CHOWN='/bin/chown'
CHMOD='/bin/chmod'
FIND='/usr/bin/find'
MYSQL='/usr/bin/mysql'
TOUCH='/bin/touch'
CAT='/bin/cat'
MAKE='/usr/bin/make'
TAIL='/usr/bin/tail'
GREP='/bin/grep'
CHKCONFIG='/sbin/chkconfig'
SERVICE='/sbin/service'
PERL='/usr/bin/perl'

CMD_LIST="GROUPADD GROUDEL USERADD USERDEL USERMOD MV CP LN LS RM TAR \
MKDIR RPM CHOWN CHMOD FIND TOUCH CAT MAKE TAIL GREP CHKCONFIG \
SERVICE PERL"


# French documentation
$MKDIR -p  /etc/codex/documentation/user_guide/xml/fr_FR
$CHOWN -R sourceforge.sourceforge /etc/codex/documentation
$CP $INSTALL_DIR/documentation/user_guide/xml/fr_FR/ParametersLocal.dtd /etc/codex/documentation/user_guide/xml/fr_FR
$MKDIR -p  $INSTALL_DIR/documentation/user_guide/html/fr_FR
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/documentation/user_guide/html/fr_FR
$MKDIR -p  $INSTALL_DIR/documentation/user_guide/pdf/fr_FR
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/documentation/user_guide/pdf/fr_FR


###############################################################################
#
#
ALTER TABLE `plugin` CHANGE `enabled` `available` TINYINT( 4 ) DEFAULT '0' NOT NULL 

ajouter $sys_custompluginsroot dans local.inc

###############################################################################
# DynamicFields: create tables
#
DROP TABLE IF EXISTS artifact_rule;
CREATE TABLE artifact_rule (
  id int(11) unsigned NOT NULL auto_increment,
  group_artifact_id int(11) unsigned NOT NULL default '0',
  source_field_id int(11) unsigned NOT NULL default '0',
  source_value_id int(11) unsigned NOT NULL default '0',
  target_field_id int(11) unsigned NOT NULL default '0',
  rule_type tinyint(4) unsigned NOT NULL default '0',
  target_value_id int(11) unsigned default NULL,
  PRIMARY KEY  (id),
  KEY group_artifact_id (group_artifact_id)
);




###############################################################################
#
#
#
# Table structure for table 'reference'
#
# Notes: 
# - scope='S' means a artifact report available to all projects
# (defined by CodeX Site administrators, group_id =100)
# - scope='P' means a artifact report available to all project members
# of project group_id (defined by project admin)
# # (NOT USED) - scope='I' means a personal (individual) artifact report only visible 
# # and manageable by the owner. 
#
CREATE TABLE reference (
  id int(11) NOT NULL auto_increment,
  keyword varchar(25) NOT NULL,
  description text NOT NULL,
  link text NOT NULL,
  scope char(1) NOT NULL default 'P',
  service_short_name TEXT,
  PRIMARY KEY  (id),
  INDEX keyword_idx (keyword),
  INDEX scope_idx (scope)
);

CREATE TABLE reference_group (
  id int(11) NOT NULL auto_increment, #??? not used?
  reference_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  is_active tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  INDEX group_id_idx (group_id,is_active)
);

INSERT INTO reference SET \
    id='1',        \
    keyword='art', \
    description='reference_art_desc_key', \
    link='/tracker/?func=detail&aid=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='tracker';

INSERT INTO reference SET \
    id='2',        \
    keyword='artifact', \
    description='reference_art_desc_key', \
    link='/tracker/?func=detail&aid=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='tracker';

INSERT INTO reference SET \
    id='3',        \
    keyword='commit', \
    description='reference_cvs_desc_key', \
    link='/cvs/?func=detailcommit&commit_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='cvs';

INSERT INTO reference SET \
    id='4',        \
    keyword='cvs', \
    description='reference_cvs_desc_key', \
    link='/cvs/?func=detailcommit&commit_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='cvs';

INSERT INTO reference SET \
    id='5',        \
    keyword='rev', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='svn';

INSERT INTO reference SET \
    id='6',        \
    keyword='revision', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='svn';

INSERT INTO reference SET \
    id='7',        \
    keyword='svn', \
    description='reference_svn_desc_key', \
    link='/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='svn';

INSERT INTO reference SET \
    id='8',        \
    keyword='wiki', \
    description='reference_wiki_desc_key', \
    link='/wiki/index.php?group_id=$group_id&pagename=$1', \
    scope='S', \
    service_short_name='wiki';

INSERT INTO reference SET \
    id='9',        \
    keyword='wiki', \
    description='reference_wikiversion_desc_key', \
    link='/wiki/index.php?group_id=$group_id&pagename=$1&version=$2', \
    scope='S', \
    service_short_name='wiki';

INSERT INTO reference SET \
    id='10',        \
    keyword='doc', \
    description='reference_doc_desc_key', \
    link='/docman/display_doc.php?docid=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='doc';

INSERT INTO reference SET \
    id='11',        \
    keyword='document', \
    description='reference_doc_desc_key', \
    link='/docman/display_doc.php?docid=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='doc';

INSERT INTO reference SET \
    id='12',        \
    keyword='news', \
    description='reference_news_desc_key', \
    link='/forum/forum.php?forum_id=$1', \
    scope='S', \
    service_short_name='news';

INSERT INTO reference SET \
    id='13',        \
    keyword='forum', \
    description='reference_forum_desc_key', \
    link='/forum/forum.php?forum_id=$1', \
    scope='S', \
    service_short_name='forum';

INSERT INTO reference SET \
    id='14',        \
    keyword='msg', \
    description='reference_msg_desc_key', \
    link='/forum/message.php?msg_id=$1', \
    scope='S', \
    service_short_name='forum';

INSERT INTO reference SET \
    id='15',        \
    keyword='file', \
    description='reference_file_desc_key', \
    link='', \
    scope='S', \
    service_short_name='file';

# Legacy references
INSERT INTO reference SET \
    id='90',        \
    keyword='bug', \
    description='reference_bug_desc_key', \
    link='/bugs/?func=detailbug&bug_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='bug';
INSERT INTO reference SET \
    id='91',        \
    keyword='task', \
    description='reference_task_desc_key', \
    link='/pm/task.php?func=detailtask&project_task_id=$a1&group_id=$group_id', \
    scope='S', \
    service_short_name='task';
INSERT INTO reference SET \
    id='92',        \
    keyword='sr', \
    description='reference_sr_desc_key', \
    link='/support/index.php?func=detailsupport&support_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='support';
INSERT INTO reference SET \
    id='93',        \
    keyword='patch', \
    description='reference_patch_desc_key', \
    link='/patch/?func=detailpatch&patch_id=$1&group_id=$group_id', \
    scope='S', \
    service_short_name='patch';

# empty reference
INSERT INTO reference SET \
    id='100',        \
    keyword='', \
    description='Empty reference', \
    link='', \
    scope='S', \
    service_short_name='';

#
# Temporary stuff for testing:
#
INSERT INTO reference_group SET reference_id='1', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='2', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='3', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='4', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='5', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='6', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='7', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='8', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='9', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='10', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='11', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='12', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='13', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='14', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='15', group_id='100', is_active='1';
INSERT INTO reference_group SET reference_id='90', group_id='100', is_active='0';
INSERT INTO reference_group SET reference_id='91', group_id='100', is_active='0';
INSERT INTO reference_group SET reference_id='92', group_id='100', is_active='0';
INSERT INTO reference_group SET reference_id='93', group_id='100', is_active='0';
INSERT INTO reference_group SET reference_id='1', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='2', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='3', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='4', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='5', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='6', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='7', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='8', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='9', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='10', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='11', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='12', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='13', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='14', group_id='109', is_active='1';
INSERT INTO reference_group SET reference_id='15', group_id='109', is_active='1';



###############################################################################
# SR #282 on partners: simplify status field
#
-- create stage field for Bug, Tasks, SR and Empty tracker templates (Patch tracker already has the field response)

INSERT INTO artifact_field VALUES (30,1,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (15,2,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (12,3,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (11,4,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');

DELETE FROM artifact_field WHERE group_artifact_id = 5 AND field_id = 12;
INSERT INTO artifact_field VALUES (12,5,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','P',0,1,1,0,NULL,'1');

INSERT INTO artifact_field_usage VALUES (30,1,0,0);
INSERT INTO artifact_field_usage VALUES (15,2,0,0);
INSERT INTO artifact_field_usage VALUES (12,3,0,0);
INSERT INTO artifact_field_usage VALUES (11,4,0,0);

-- delete values for status field

DELETE FROM artifact_field_value_list WHERE group_artifact_id = 1 AND field_id = 2 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 2 AND field_id = 11 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 3 AND field_id = 7 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 4 AND field_id = 6 AND status != 'P' AND value_id > 3;
DELETE FROM artifact_field_value_list WHERE group_artifact_id = 5 AND field_id = 9 AND status != 'P' AND value_id > 3;

-- create field values for new stage field

INSERT INTO artifact_field_value_list VALUES (30,1,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (30,1,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,10,'Done','The artifact is closed.',110,'A');

INSERT INTO artifact_field_value_list VALUES (15,2,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (15,2,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,10,'Done','The artifact is closed.',110,'A');


INSERT INTO artifact_field_value_list VALUES (12,3,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (12,3,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,10,'Done','The artifact is closed.',110,'A');


INSERT INTO artifact_field_value_list VALUES (11,4,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (11,4,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,10,'Done','The artifact is closed.',110,'A');


DELETE FROM artifact_field_value_list WHERE group_artifact_id = 5 AND field_id = 12;

INSERT INTO artifact_field_value_list VALUES (12,5,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (12,5,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,10,'Done','The artifact is closed.',110,'A');

###############################################################################
# add project ugroup definition in CodeX default SVN Access file
#


$PERL <<'EOF'

use DBI;
use Sys::Hostname;
use Carp;

require "/home/httpd/SF/utils/include.pl";  # Include all the predefined functions

&load_local_config();

# reuse from database_dump.pl

my $group_array = ();

&db_connect;

# Dump the Groups Table information
$query = "select group_id,unix_group_name,status,is_public,cvs_tracker,svn_tracker from groups";
$c = $dbh->prepare($query);
$c->execute();

while(my ($group_id, $group_name, $status, $is_public, $cvs_tracker, $svn_tracker) = $c->fetchrow()) {

	my $new_query = "select user.user_name AS user_name FROM user,user_group WHERE user.user_id=user_group.user_id AND group_id=$group_id";
	my $d = $dbh->prepare($new_query);
	$d->execute();

	my $user_list = "";
	
	while($user_name = $d->fetchrow()) {
	   $user_list .= "$user_name,";
	}

	$user_list =~ s/,$//;

	my $ugroup_list = "";

	my $new1_query = "select name,ugroup_id from ugroup where group_id=$group_id ORDER BY ugroup_id";
	my $d1 = $dbh->prepare($new1_query);
	$d1->execute();

	while (my ($ug_name, $ug_id) = $d1->fetchrow()) {

	  $ugroup_list .= " $ug_name=";	  
	  my $new2_query = "select u.user_name from user u, ugroup_user ugu where ugu.ugroup_id=$ug_id AND ugu.user_id = u.user_id";
	  my $d2 = $dbh->prepare($new2_query);
	  $d2->execute();

	  while ($user_name = $d2->fetchrow()) {
	    $ugroup_list .= "$user_name,";
	  }

	  $ugroup_list =~ s/,$//;
	}

	$grouplist = "$group_name:$status:$is_public:$cvs_tracker:$svn_tracker:$group_id:$user_list:$ugroup_list\n";

	push @group_array, $grouplist;
}

# Now write out the files
write_array_file($file_dir."group_dump", @group_array);
    

# reuse from new_parse.pl

# Make sure umask is properly positioned for the
# entire session. Root has umask 022 by default
# causing all the mkdir xxx, 775 to actually 
# create dir with permission 755 !!
# So set umask to 002 for the entire script session 
umask 002;

my $group_file = $file_dir . "group_dump";
my ($uid, $status, $username, $shell, $passwd, $win_passwd, $winnt_passwd, $email, $realname);
my ($gname, $gstatus, $gid, $userlist, $ugrouplist);

# Open up all the files that we need.
@groupdump_array = open_array_file($group_file);


#
# Loop through @groupdump_array and deal w/ users.
#
print ("\n\n	Processing Groups\n\n");
while ($ln = pop(@groupdump_array)) {
    chop($ln);
    ($gname, $gstatus, $gis_public, $cvs_tracker, $svn_tracker, $gid, $userlist, $ugrouplist) = split(":", $ln);

    print ("	     ",$gname,"\n");
    $gid += $gid_add;

    # Add sourceforge user to the group if it is a private project
    # otherwise Apache won't be able to access the document Root
    # of the project web iste which is not world readable (see below)
    $public_grp = $gis_public && ! -e "$grpdir_prefix/$gname/.CODEX_PRIVATE";
    if ($userlist eq "") {
	$userlist = "sourceforge" unless $public_grp;
    } else {
	$userlist .= ",sourceforge" unless $public_grp;
    }

    # make all user names lower case.
    $userlist =~ tr/A-Z/a-z/;
    $ugrouplist =~ tr/A-Z/a-z/;

    # update Subversion DAV access control file if needed
    my $svnaccess_file = "$svn_prefix/$gname/.SVNAccessFile";
    
    my $custom_perm=0;
    my $custom_lines = "";
    my $public_svn = $gis_public && ! -e "$svn_prefix/$gname/.CODEX_PRIVATE";
		
    # Retrieve custom permissions, if any
    if (-e "$svnaccess_file") {
	open(SVNACCESS,"$svnaccess_file");
	while (<SVNACCESS>) {
	    if ($custom_perm) {
		$custom_lines.=$_;
	    } else {
		if (m/END CODEX DEFAULT SETTINGS/) {$custom_perm=1;}
	    }
	}
	close(SVNACCESS);
    }
    
    if (-d "$svn_prefix/$gname") {
	open(SVNACCESS,">$svnaccess_file")
	or croak "Can't open Subversion access file $svnaccess_file: $!";
	# if you change these block markers also change them in
	# SF/www/svn/svn_utils.php
	print SVNACCESS "# BEGIN CODEX DEFAULT SETTINGS - DO NOT REMOVE\n";
	print SVNACCESS "[groups]\n";
	print SVNACCESS "members = ",$userlist,"\n";

	$new_custom_lines = $custom_lines;
	@ugroup_array = split(" ",$ugrouplist);

	while ($ln = pop(@ugroup_array)) {
	    print SVNACCESS $ln,"\n";

	    ##parse custom rules to rename groups that have the same name as ugroup
	    ($ug_name,$ugulist) = split("=",$ln);
	    @custom_array = split("\n",$custom_lines);
	    foreach (@custom_array) {
		if (/^${ug_name}=/) {
		    $new_custom_lines =~ s/${ug_name}=/${ug_name}_svn=/g;
		}
	    }


	}
	print SVNACCESS "\n";

	print SVNACCESS "[/]\n";
	if ($sys_allow_restricted_users) {
	    print SVNACCESS "* = \n"; # deny all access by default
	    # we don't know yet how to enable read access to all active users,
	    # and deny it to all restricted users...
	} else {
	    if ($public_svn) { print SVNACCESS "* = r\n"; }
	    else { print SVNACCESS "* = \n";}
	}
	print SVNACCESS "\@members = rw\n";
	print SVNACCESS "# END CODEX DEFAULT SETTINGS\n";
	if ($custom_perm) { print SVNACCESS $new_custom_lines;}
	close(SVNACCESS);
            
	# set group ownership, codex user as owner so that
	# PHP scripts can write to it directly
	system("chown -R $cxname:$gid $svnaccess_file");
	system("chmod g+rw $svnaccess_file");
    }
}

exit;
EOF


# Still To Do
#
# - copy new svn backup script
# - convert all repositories to FSFS
# add this in httpd.conf:
<Directory "/home/httpd/SF/www/api">
    ForceType application/x-httpd-php
</Directory>
