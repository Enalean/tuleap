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
#  This script migrates a site running CodeX 2.4 to CodeX 2.6
#


progname=$0
if [ -z "$scriptdir" ]; then 
    scriptdir=`dirname $progname`
fi
cd ${scriptdir};TOP_DIR=`pwd`;cd -
RPMS_DIR=${TOP_DIR}/RPMS_CodeX
nonRPMS_DIR=${TOP_DIR}/nonRPMS_CodeX
CodeX_DIR=${TOP_DIR}/CodeX
TODO_FILE=/tmp/todo_codex_migration.txt
CODEX_TOPDIRS="SF site-content documentation cgi-bin codex_tools"
INSTALL_DIR="/home/httpd"

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

# Functions
create_group() {
    # $1: groupname, $2: groupid
    $GROUPDEL "$1" 2>/dev/null
    $GROUPADD -g "$2" "$1"
}

build_dir() {
    # $1: dir path, $2: user, $3: group, $4: permission
    $MKDIR -p "$1" 2>/dev/null; $CHOWN "$2.$3" "$1";$CHMOD "$4" "$1";
}

make_backup() {
    # $1: file name, $2: extension for old file (optional)
    file="$1"
    ext="$2"
    if [ -z $ext ]; then
	ext="nocodex"
    fi
    backup_file="$1.$ext"
    [ -e "$file" -a ! -e "$backup_file" ] && $CP "$file" "$backup_file"
}

todo() {
    # $1: message to log in the todo file
    echo -e "- $1" >> $TODO_FILE
}

die() {
  # $1: message to prompt before exiting
  echo -e "**ERROR** $1"; exit 1
}

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  $PERL -pi -e "s/$2/$3/g" $1
}

##############################################
# CodeX 2.4 to 2.6 migration
##############################################


##############################################
# Check the machine is running CodeX 2.4
#
OLD_CX_RELEASE='2.4'
yn="y"
$GREP -q "$OLD_CX_RELEASE" $INSTALL_DIR/SF/www/VERSION
if [ $? -ne 0 ]; then
    $CAT <<EOF
This machine does not have CodeX ${OLD_CX_RELEASE} installed. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Found CodeX ${OLD_CX_RELEASE} installed... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi


##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done

##############################################
# Warn user about upgrade
#

yn="y"
$CAT <<EOF
This script will reinstall some configuration files:
/etc/httpd/conf/httpd.conf
/etc/httpd/conf.d/php.conf
If you have customized these files for your special needs, you should backup them before starting the migration.

EOF
read -p "Continue? [yn]: " yn

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

##############################################
# Check we are running on RHEL 3
#
RH_RELEASE="3"
yn="y"
$RPM -q redhat-release-${RH_RELEASE}* 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Running on RedHat Enterprise Linux ${RH_RELEASE}... good!"
fi

if [ "$yn" = "n" ]; then
    echo "Bye now!"
    exit 1
fi

$RM -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE CODEX MIGRATION (see $TODO_FILE)"

##############################################
# Stop some services before upgrading
#
echo "Stopping crond, apache and httpd, sendmail, and postfix ..."
$SERVICE crond stop
$SERVICE apache stop
$SERVICE httpd stop
$SERVICE mysql stop
$SERVICE sendmail stop
$SERVICE postfix stop
$SERVICE mailman stop


##############################################
# Check Required Stock RedHat RPMs are installed
# (note: gcc is required to recompile mailman)
#

# Removed: see install script for required RPMs. No new RPM needed for upgrade

##############################################
# Ask for domain name and other installation parameters
#
read -p "CodeX Domain name: " sys_default_domain
read -p "Codex Server IP address: " sys_ip_address

##############################################

##############################################
# Update local.inc
#
make_backup /etc/codex/conf/local.inc codex24

substitute /etc/codex/conf/local.inc "sys_themedefault[\\s]*=[\\s]*['\"]codex['\"]" "sys_themedefault = 'CodeX'"
echo '
//
// Plugins root directory 
$sys_pluginsroot="/home/httpd/plugins/";' >> /etc/codex/conf/local.inc

##############################################
# Now install CodeX specific RPMS (and remove RedHat RPMs)
#

#TODO

##############################################
# Update the CodeX software

echo "Installing the CodeX software..."
cd /home
$MV httpd httpd_24
$MKDIR httpd;
cd httpd
$TAR xfz ${CodeX_DIR}/codex*.tgz
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR

# copy some configuration files 
make_backup /etc/httpd/conf/httpd.conf codex24
$CP $INSTALL_DIR/SF/etc/httpd.conf.dist /etc/httpd/conf/httpd.conf
$CP $INSTALL_DIR/SF/etc/php.conf.dist /etc/httpd/conf.d/php.conf

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

todo "Edit the new /etc/httpd/conf/httpd.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/php.conf file and update it if needed"

# New directories
#TODO: déplacer les themes
build_dir /etc/codex/themes/messages sourceforge sourceforge 755

# Re-copy phpMyAdmin and viewcvs installations
$CP -af /home/httpd_24/phpMyAdmin* /home/httpd
$CP -af /home/httpd_24/cgi-bin/viewcvs.cgi /home/httpd/cgi-bin

###########################################
#{{{ Themes directories
	echo "Updating custom themes..."
	cd /etc/codex/themes

	#{{{CSS
		if [ -d css ]; then
			echo -ne "Copy of themes css...\t\t"
			cd css
			for i in *
			do
				if [ ! -d ../$i ]; then 
					$MKDIR ../$i
				fi
				if [ ! -d ../$i/css ]; then
					$MKDIR ../$i/css
				fi
				for j in $i/*
				do
					$PERL -pi -e "s|images/custom/([^/]+)\.theme|custom/\$1/images|g" $j
				done
				$MV $i/* ../$i/css/.
			done
			cd ..
			rm -rf css
			echo "done."
		fi
	#}}}
	
	#{{{Images
		if [ -d images ]; then
			echo -ne "Copy of themes images...\t"
			cd images
			for i in *
			do
				if [ ! -d ../$i ]; then 
					$MKDIR ../$i
				fi
				if [ ! -d ../$i/images ]; then
					$MKDIR ../$i/images
				fi
				mv $i/* ../$i/images/.
			done
			cd ..
			$RM -rf images
			echo "done."
		fi
	#}}}
	
	#{{{merge of themes directory (/codex/ vs /codex.theme/)
		echo -ne "Merge of themes directory...\t"
		for i in *
		do
			if [ -d $i.theme ]; then
				$CP -r $i.theme/* $i/.
				$RM -rf $i.theme
			fi
		done
		echo "done."
	#}}}
	
	#{{{ Creation of Theme.class
		echo -ne "Creation of Theme classes...\t"
		for i in *
		do
		if [ ! -f $i/${i}_Theme.class ]; then
		
			$CAT <<'EOF' > $i/${i}_Theme.class
<?php

require_once('www/include/Layout.class');

EOF
				echo "class ${i}_Theme extends Layout {" >> $i/${i}_Theme.class
				$CAT <<'EOF' >> $i/${i}_Theme.class
}

?>
EOF
			fi
		done
		echo "done."
	#}}}
	
	cd ../../..
#}}}

#############################################
# Copy new icons in all custom themes

$CP  $INSTALL_DIR/SF/www/themes/CodeX/images/ic/thread.png /etc/codex/themes/*/images/ic/ 2> /dev/null


##############################################
# Database Structure and initvalues upgrade
#
echo "Updating the CodeX database..."

$SERVICE mysql start
sleep 5

pass_opt=""
# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

$CAT <<EOF | $MYSQL $pass_opt sourceforge

-- Plugin tables
-- {{{
CREATE TABLE `priority_plugin_hook` (
`plugin_id` INT NOT NULL,
`hook` VARCHAR(100) NOT NULL,
`priority` INT NOT NULL
);
CREATE TABLE `plugin` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `enabled` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
);
CREATE TABLE `project_plugin` (
`project_id` INT NOT NULL ,
`plugin_id` INT NOT NULL
);
CREATE TABLE `user_plugin` (
`user_id` INT NOT NULL ,
`plugin_id` INT NOT NULL
);
-- }}}

-- install and enable pluginsadministration
INSERT INTO `plugin` (`name`, `enabled`) VALUES ('pluginsadministration', '1');


-- themes codex --> CodeX
UPDATE user SET theme = 'CodeX' WHERE theme = 'codex'

-- slow trackers, see SR 318 on Partners
ALTER TABLE `artifact_file` ADD INDEX ( `artifact_id` )

EOF

###############################################################################
# pre-install pluginsadministration
echo "Installing PluginsAdministration..."

if [ -d /etc/codex/plugins ]; then
  $MKDIR /etc/codex/plugins
fi

if [ -d /etc/codex/plugins/pluginsadministration ]; then
  $MKDIR /etc/codex/plugins/pluginsadministration
fi


###############################################################################
# Update DB to remove tech_tracker role
#
$PERL <<'EOF'
use DBI;
require "/home/httpd/SF/utils/include.pl";

## load local.inc variables
&load_local_config();

&db_connect;


sub exec_sql {
  my ($query) = @_;
  my ($c);
  
  #print $query."\n";
  $c = $dbh->prepare($query);
  $c->execute();
}


sub has_role {
  my ($group_artifact_id, $role) = @_;
  my ($q, $d);
  
  $q = "SELECT agl.group_id, agl.item_name, agl.name FROM artifact_perm ap, artifact_group_list agl WHERE ap.group_artifact_id = agl.group_artifact_id AND ap.group_artifact_id = $group_artifact_id AND ap.perm_level = '$role'";
  #print $q."\n";
  $d = $dbh->prepare($q);
  $d->execute();
  
  #print "* $group_artifact_id has ".$d->rows." $role \n";
  
  return ($d->rows > 0);
}



sub has_tech_permissions {
  my ($group_artifact_id) = @_;
  my ($q, $d);
  
  $q = "SELECT permission_type, object_id  FROM permissions WHERE ugroup_id = 16 AND (object_id = '$group_artifact_id' OR object_id LIKE '".$group_artifact_id."#')";
  #print $q."\n";
  $d = $dbh->prepare($q);
  $d->execute();
  
  return ($d->rows > 0);
}


sub has_tech_value_functions {
  my ($group_artifact_id) = @_;
  my ($q, $d);

  $q = "SELECT value_function FROM artifact_field WHERE group_artifact_id = $group_artifact_id AND value_function = 'artifact_technicians'";
  #print $q."\n";
  $d = $dbh->prepare($q);
  $d->execute();
  
  return ($d->rows > 0);
}


sub create_tech_ugroup {
  my ($group_id, $group_artifact_id, $item_name, $name) = @_;

  my ($q, $d, $uname, $ugroup_id);

  #verify first if several trackers with same name exist for this group
  $q = "SELECT item_name FROM artifact_group_list WHERE item_name = '$item_name' AND group_id = $group_id";
  #print $q."\n";
  $d = $dbh->prepare($q);
  $d->execute();
  if ($d->rows > 1) {
    #print "several trackers with item_name $item_name in project $group_id \n";
    $uname = $item_name."_".$group_artifact_id."_techs";
  } else {
    $uname = $item_name."_techs";
  }
  $q = "INSERT INTO ugroup (name,description,group_id) VALUES ('$uname','The technicians of the $name tracker',$group_id)";
  #print $q."\n";
  $d = $dbh->prepare($q);
  $d->execute();
  $ugroup_id = $d->{'mysql_insertid'};

  $q2 = "SELECT user_id FROM artifact_perm WHERE group_artifact_id = $group_artifact_id AND perm_level IN (2,3)";
  #print $q2."\n";
  $d2 = $dbh->prepare($q2);
  $d2->execute();
  while (my ($user_id) = $d2->fetchrow()) {
    #insert user into newly created ugroup
    $q = "INSERT INTO ugroup_user (ugroup_id,user_id) VALUES ($ugroup_id,$user_id)";
    #print $q."\n";
    $d = $dbh->prepare($q);
    $d->execute();
  }
  return $ugroup_id;
}

sub update_each_tracker {
  my ($query, $c, $q, $d, $uname, $ugroup_id);
  
  $query = "SELECT group_artifact_id, group_id, item_name, name from artifact_group_list ORDER BY group_artifact_id";
  #print $query."\n";
  $c = $dbh->prepare($query);
  $c->execute();
  
  while (my ($group_artifact_id, $group_id, $item_name, $name) = $c->fetchrow()) {
    #print "** Treat tracker $group_artifact_id, $group_id, $item_name, $name \n";
    if ($group_artifact_id < 100) {
      ## for template trackers:
      ## ** 1 **
      ## update permissions table: replace dynamic tech_tracker ugroup by dynamic group_members ugroup
      exec_sql("UPDATE permissions SET ugroup_id = '3' WHERE ugroup_id = '16' AND (object_id = '$group_artifact_id' OR object_id LIKE '".$group_artifact_id."#%')");
      ## ** 2 **
      ## update value_functions in artifact_field table
      exec_sql("UPDATE artifact_field SET value_function = 'group_members' WHERE group_artifact_id = $group_artifact_id AND value_function = 'artifact_technicians'");
      
      
    } else {
      ## for real project trackers
      ## ** 1 **
      ## create or not a specific tracker techs ugroup ??
      $user_only = has_role($group_artifact_id, '0');
      $tech_only = has_role($group_artifact_id, '1');
      $tech_admin = has_role($group_artifact_id, '2');
      $admin_only = has_role($group_artifact_id, '3');
      
      $has_tech_vf = has_tech_value_functions($group_artifact_id);
      $has_tech_p = has_tech_permissions($group_artifact_id);
      

      if ( ($user_only && !$tech_only && !$admin_only) ||
	   ($user_only && !$tech_only && !$tech_admin) ) {
	## TRACKER_ADMINS	
	## migrate value_functions in artifact fields
	if ($has_tech_vf) {
	  exec_sql("UPDATE artifact_field SET value_function = 'tracker_admins' WHERE group_artifact_id = $group_artifact_id AND value_function = 'artifact_technicians'");
	}
	## migrate permissions
	if ($has_tech_p) {
	  exec_sql("UPDATE permissions SET ugroup_id = 3 WHERE ugroup_id = 15 AND (object_id = '$group_artifact_id' OR object_id LIKE '".$group_artifact_id."#')");
	}

      } elsif ($user_only ||
	      (!$user_only && $tech_only && $admin_only) ) {  
	
	if ($has_tech_vf || $has_tech_p) {
	  ## need to create a specific techs ugroup
	  $ugroup_id = create_tech_ugroup($group_id, $group_artifact_id, $item_name, $name);
	  
	  ## migrate value_functions in artifact fields
	  if ($has_tech_vf) {
	    exec_sql("UPDATE artifact_field SET value_function = 'ugroup_".$ugroup_id.
		     "' WHERE group_artifact_id = $group_artifact_id AND value_function = 'artifact_technicians'");
	  }
	  ## migrate permissions
	  if ($has_tech_p) {
	    exec_sql("UPDATE permissions SET ugroup_id = $ugroup_id WHERE ugroup_id = 16 AND (object_id = '$group_artifact_id' OR object_id LIKE '".$group_artifact_id."#')");
	  }
	}

      } elsif ( (!$user_only && $tech_only && $tech_admin && !$admin_only) ||
		(!$user_only && $tech_only && !$tech_admin && !$admin_only) ||
		(!$user_only && !$tech_only) ){
	## GROUP_MEMBERS
	## migrate value_functions in artifact fields
	if ($has_tech_vf) {
	  exec_sql("UPDATE artifact_field SET value_function = 'group_members' WHERE group_artifact_id = $group_artifact_id AND value_function = 'artifact_technicians'");
	}
	## migrate permissions
	if ($has_tech_p) {
	  exec_sql("UPDATE permissions SET ugroup_id = 3 WHERE ugroup_id = 16 AND (object_id = '$group_artifact_id' OR object_id LIKE '".$group_artifact_id."#')");
	}
	
      }
      
    }
  }
}


update_each_tracker();

#delete old dynamic techs ugroup
exec_sql("DELETE FROM ugroup WHERE ugroup_id=16");

#delete all the permissions for dynamic techs that are left
exec_sql("DELETE FROM permissions WHERE ugroup_id = 16");

#update the permissions_values table
exec_sql("INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_FIELD_UPDATE',3,1),('TRACKER_FIELD_UPDATE',4,0),('TRACKER_FIELD_UPDATE',15,0)");
exec_sql("DELETE FROM permissions_values WHERE ugroup_id = 16");


exit;
EOF

##############################################
# Reinstall modified shell scripts
#
$CP $INSTALL_DIR/SF/utils/svn/commit-email.pl /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/commit-email.pl
$CHMOD 775 /usr/local/bin/commit-email.pl
$CHMOD u+s /usr/local/bin/commit-email.pl

$CP $INSTALL_DIR/SF/utils/cvs1/log_accum /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/log_accum
$CHMOD 775 /usr/local/bin/log_accum
$CHMOD u+s /usr/local/bin/log_accum

# should have been done before but...
$CP $INSTALL_DIR/SF/utils/cvs1/cvssh-restricted /usr/local/bin


##############################################
# Check for the existence of the following customized content files and advise the person in charge of the installation of the new .tab files to customize to obtain the same effect
#

service="account"
file_exist=0
for sitefile in register_confirmation.txt register_needs_approval.txt \
    register_email.txt register_purpose.txt register_login.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

service="homepage"
file_exist=0
for sitefile in staff.txt thanks.txt welcome_intro.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

service="my"
file_exist=0
for sitefile in intro.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above are no longer used. Please customize /etc/codex/site-content/en_US/$service/$service.tab to obtain the same effect, then delete the old files."
fi

# other modified files
file_exist=0
for sitefile in contact/contact.txt \
cvs/intro.txt \
file/editrelease_attach_file.txt \
file/qrs_attach_file.txt \
include/new_project_email.txt \
include/restricted_user_permissions.txt \
layout/footer.txt \
others/default_page.php \
register/complete.txt \
register/intro.txt \
register/license.txt \
register/projectname.txt \
register/registration.txt \
register/tos.txt \
svn/intro.txt \
tos/privacy.txt
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ -e /etc/codex/site-content/en_US/$service/$sitefile ]; then
        file_exist=1
        echo /etc/codex/site-content/en_US/$service/$sitefile
    fi
done
if [ $file_exist -eq 1 ]; then
    echo "The file(s) listed above have change in CodeX 2.6. Please check that your customized files are still up-to-date."
fi


##############################################
# Restarting some services
#
echo "Starting crond and apache..."
$SERVICE crond start
$SERVICE httpd start
$SERVICE sendmail start
$SERVICE mailman start


##############################################
# Generate Documentation
#
echo "Updating the User Manual. This might take a few minutes."
todo "Make sure that the CVS update is possible in /home/httpd/SF/utils/utils/generate_doc.sh. Do a cvs login on CVS server as user 'sourceforge'."
/home/httpd/SF/utils/generate_doc.sh -f
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR/documentation


##############################################
# Make sure all major services are on
#
$CHKCONFIG named on
$CHKCONFIG sshd on
$CHKCONFIG httpd on
$CHKCONFIG mysql on
$CHKCONFIG cvs on
$CHKCONFIG mailman on

##############################################
# More things to do


# End of it
echo "=============================================="
echo "Installation completed succesfully!"
$CAT $TODO_FILE

exit 1;
