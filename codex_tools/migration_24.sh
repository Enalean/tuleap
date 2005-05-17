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
#  This file is part of the CodeX software and must be place at the same
#  level as the CodeX, RPMS_CodeX and nonRPMS_CodeX directory when
#  delivered on a CD or by other means
#
#  This script migrates a site running CodeX 2.2 to CodeX 2.4
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
# CodeX 2.2 to 2.4 migration
##############################################


##############################################
# Check the machine is running CodeX 2.2
#
OLD_CX_RELEASE='2.2'
yn="y"
$GREP -q "$OLD_CX_RELEASE" $INSTALL_DIR/SF/www/VERSION
if [ $? -eq 1 ]; then
    cat <<EOF
This machine does not have CodeX ${OLD_CX_RELEASE} installed. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Found CodeX ${OLD_CX_RELEASE} installed... good!"
fi

[ "$yn" != "y" ] && (echo "Bye now!"; exit 1;)


##############################################
# Check that all command line tools we need are available
#
for cmd in `echo ${CMD_LIST}`
do
    [ ! -x ${!cmd} ] && die "Command line tool '${!cmd}' not available. Stopping installation!"
done

##############################################
# Check we are running on RHEL 3 ES
#
RH_RELEASE="3ES"
yn="y"
$RPM -q redhat-release-${RH_RELEASE} 2>/dev/null 1>&2
if [ $? -eq 1 ]; then
    cat <<EOF
This machine is not running RedHat Enterprise Linux ${RH_RELEASE}. Executing this install
script may cause data loss or corruption.
EOF
read -p "Continue? [yn]: " yn
else
    echo "Running on RedHat ${RH_RELEASE}... good!"
fi

[ "$yn" != "y" ] && (echo "Bye now!"; exit 1;)

$RM -f $TODO_FILE
todo "WHAT TO DO TO FINISH THE CODEX MIGRATION (see $TODO_FILE)"

##############################################
# Stop some services before upgrading
#
echo "Stopping crond, apache and httpd, sendmail, and postfix ..."
$SERVICE crond stop
$SERVICE apache stop
$SERVICE httpd stop
$SERVICE sendmail stop
$SERVICE postfix stop


#
#
#
#
#
#
#
#  OLD 2.0 to 2.2 migration stuff
#
#
#
#
#
#
#

##############################################
# Check Required Stock RedHat RPMs are installed
# (note: gcc is required to recompile mailman)
#
rpms_ok=1
for rpm in openssh-server openssh openssh-clients openssh-askpass \
   openssl openldap perl perl-DBI perl-CGI gd gcc \
   sendmail telnet bind ntp samba python php php-mysql php-ldap enscript \
   bind python-devel rcs
do
    $RPM -q $rpm  2>/dev/null 1>&2
    if [ $? -eq 1 ]; then
	rpms_ok=0
	missing_rpms="$missing_rpms $rpm"
    fi
done
if [ $rpms_ok -eq 0 ]; then
    msg="The following Redhat Linux RPMs must be installed first:\n"
    msg="${msg}$missing_rpms\n"
    msg="${msg}Get them from your Redhat CDROM or FTP site, install them and re-run the installation script"
    die "$msg"
fi
echo "All requested RedHat RPMS installed... good!"

##############################################
# Ask for domain name and other installation parameters
#
read -p "CodeX Domain name: " sys_default_domain
read -p "Codex Server IP address: " sys_ip_address

##############################################

##############################################
# Create new directory structure for configuration and
# customization items
#
echo "Creating new directories for CodeX 2.2..."

build_dir /etc/codex sourceforge sourceforge 755
build_dir /etc/codex/conf sourceforge sourceforge 755
build_dir /etc/codex/documentation sourceforge sourceforge 755
build_dir /etc/codex/documentation/user_guide sourceforge sourceforge 755
build_dir /etc/codex/documentation/user_guide/xml sourceforge sourceforge 755
build_dir /etc/codex/documentation/user_guide/xml/en_US sourceforge sourceforge 755
build_dir /etc/codex/themes sourceforge sourceforge 755
build_dir /etc/codex/themes/css sourceforge sourceforge 755
build_dir /etc/codex/themes/images sourceforge sourceforge 755
build_dir /svnroot sourceforge sourceforge 755

build_dir /home/ftp/codex/DELETED sourceforge sourceforge 755

# Move configuration file
$MV /etc/local.inc /etc/codex/conf/local.inc

# Add $sys_win_domain to configuration file
$PERL -i'.orig' -p -e's:(// Part II.*paths$):// Windows Workgroup CodeX belog to (Samba)\n\$sys_win_domain = "YOURDOMAIN";\n\n\1:' /etc/codex/conf/local.inc
todo "- Customize the sys_win_domain parameter in /etc/codex/conf/local.inc"
# Update one line that is not compatible with Python
$PERL -i'.orig2' -p -e's:(sys_themeroot.*)\$.*(themes):\1"/home/httpd/SF/www/\2:' /etc/codex/conf/local.inc

######
# Now install CodeX specific RPMS (and remove RedHat RPMs)
#

# -> wu-ftpd
echo "Removing existing wu-ftp daemon.."
$RPM -e --nodeps wu-ftpd 2>/dev/null
echo "Installing wu-ftpd..."
cd ${RPMS_DIR}/wu-ftpd
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/wu-ftpd*.i386.rpm

# -> perlsuid
echo "Removing Perl suid if any..."
$RPM -e --nodeps perl-suidperl 2>/dev/null
echo "Installing Perl suid..."
cd ${RPMS_DIR}/perl-suidperl
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/perl-suidperl*.i386.rpm

# -> Perl DBD for MySQL
echo "Removing Redhat Perl DBD MySQL if any..."
$RPM -e --nodeps perl-DBD-MySQL 2>/dev/null
echo "Installing Perl DBD MySQL..."
cd ${RPMS_DIR}/perl-dbd-mysql
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/perl-DBD-MySQL-*.i386.rpm

# -> mysql
echo "Removing existing MySQL..."
$SERVICE mysql stop 2>/dev/null
sleep 2
[ -e /usr/bin/mysqladmin ] && /usr/bin/mysqladmin shutdown 2>/dev/null
sleep 2
$RPM -e --nodeps MySQL MySQL-client MySQL-shared mysql-bench MySQL-bench MySQL-devel MySQL-server 2>/dev/null
echo "Installing MySQL RPMs for CodeX...."
cd ${RPMS_DIR}/mysql
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/MySQL-*.i386.rpm
$SERVICE mysql start
$CHKCONFIG mysql on

# -> mysql module for Python
echo "Removing existing MySQL module for Python..."
$RPM -e --nodeps MySQL-python 2>/dev/null
echo "Installing Python MySQL module RPM for CodeX...."
cd ${RPMS_DIR}/mysql-python
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/MySQL-python-*.i386.rpm

# -> apache
echo "Removing existing Apache..."
#$SERVICE httpd stop
$RPM -e --nodeps apache apache-devel apache-manual httpd httpd-devel httpd-manual 2>/dev/null
$RPM -e --nodeps 'apr*' apr-util mod_ssl db4-devel db4-utils 2>/dev/null
echo "Installing Apache RPMs for CodeX...."
cd ${RPMS_DIR}/apache
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/db42-4.*.i386.rpm ${newest_rpm}/db42-utils*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/apr-0.*.i386.rpm ${newest_rpm}/apr-util-0.*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/httpd-2*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/mod_ssl-*.i386.rpm
$CHKCONFIG httpd on
# restart Apache after subversion installation - see below


# -> subversion
echo "Removing existing subversion .."
$RPM -e --nodeps `rpm -qa 'subversion*' 'swig*' 'neon*' 'rapidsvn*'` 2>/dev/null
echo "Installing Subversion RPMs for CodeX...."
cd ${RPMS_DIR}/subversion
newest_rpm=`$LS -1  -I old -I TRANS.TBL | $TAIL -1`
$RPM -Uvh --force ${newest_rpm}/neon-0*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/swig-1*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-1.*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-server*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-python*.i386.rpm
$RPM -Uvh --force ${newest_rpm}/subversion-tools*.i386.rpm


# Restart Apache after subversion is installed
# so that mod_dav_svn module is taken into account
$SERVICE httpd start



##############################################
# Move custom and config files to new places
#
echo "Move custom and configuration files to /etc/codex/ directory. Some files may not exist."

$MV -f $INSTALL_DIR/site-content/custom /etc/codex/site-content
$MV -f /etc/motd.inc /etc/codex/site-content/en_US/others/motd.txt
$MV -f $INSTALL_DIR/SF/www/css/custom/* /etc/codex/themes/css
$MV -f $INSTALL_DIR/SF/www/images/custom/* /etc/codex/themes/images
build_dir /tmp/custom root root 755
$MV $INSTALL_DIR/SF/www/css/custom /tmp/custom/css       # for future manual deletion
$MV $INSTALL_DIR/SF/www/images/custom /tmp/custom/images # for future manual deletion

$MV -f $INSTALL_DIR/documentation/user_guide/xml/en_US/ParametersLocal.dtd /etc/codex/documentation/user_guide/xml/en_US/

build_dir /etc/codex/site-content/en_US/others sourceforge sourceforge 755
# First, copy default page, then overwrite if a custom one exists
$CP $INSTALL_DIR/site-content/en_US/others/default_page.php /etc/codex/site-content/en_US/others
$MV -f $INSTALL_DIR/SF/utils/custom/default_page.php /etc/codex/site-content/en_US/others
$MV $INSTALL_DIR/SF/utils/custom /tmp/custom/utils  # for future manual deletion


##############################################
# Update the CodeX software

echo "Installing the CodeX software..."
cd /home
$MV httpd httpd_20
$MKDIR httpd;
cd httpd
$TAR xfz ${CodeX_DIR}/codex*.tgz
$CHOWN -R sourceforge.sourceforge $INSTALL_DIR

# copy some configuration files 
make_backup /etc/httpd/conf/httpd.conf codex20
$CP $INSTALL_DIR/SF/etc/httpd.conf.dist /etc/httpd/conf/httpd.conf
$CP $INSTALL_DIR/SF/etc/mailman.conf.dist /etc/httpd/conf/mailman.conf
$CP $INSTALL_DIR/SF/etc/ssl.conf.dist /etc/httpd/conf.d/ssl.conf
$CP $INSTALL_DIR/SF/etc/php.conf.dist /etc/httpd/conf.d/php.conf
$CP $INSTALL_DIR/SF/etc/subversion.conf.dist /etc/httpd/conf.d/subversion.conf

# replace string patterns in httpd.conf
substitute '/etc/httpd/conf/httpd.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf/httpd.conf' '%sys_ip_address%' "$sys_ip_address"

# replace string patterns in ssl.conf
substitute '/etc/httpd/conf.d/ssl.conf' '%sys_default_domain%' "$sys_default_domain"
substitute '/etc/httpd/conf.d/ssl.conf' '%sys_ip_address%' "$sys_ip_address"

todo "Edit the new /etc/httpd/conf/httpd.conf file and update it"
todo "Edit the new /etc/httpd/conf.d/ssl.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/php.conf file and update it if needed"
todo "Edit the new /etc/httpd/conf.d/subversion.conf file and update it if needed"

# needed by newparse.pl
$TOUCH /etc/httpd/conf/htpasswd
$CHMOD 644 /etc/httpd/conf/htpasswd



#############################################
# Because of directory structure change, specifically move one site-content file if it exists
if [ -d /etc/codex/site-content/en_US/project/ ]; then
    build_dir /etc/codex/site-content/en_US/file sourceforge sourceforge 755
    $MV -f /etc/codex/site-content/en_US/project/editrelease_attach_file.txt /etc/codex/site-content/en_US/file/
fi


#############################################
# Copy new icons in all custom themes
$CP  $INSTALL_DIR/SF/www/images/codex.theme/ic/lock.png /etc/codex/themes/images/*/ic/
$CP  $INSTALL_DIR/SF/www/images/codex.theme/ic/svn16b.png /etc/codex/themes/images/*/ic/
$CP  $INSTALL_DIR/SF/www/images/codex.theme/ic/file.png /etc/codex/themes/images/*/ic/



#
#
#
#
#
#
#
#  2.2 to 2.4 migration stuff
#
#
#
#
#
#
#

##############################################
# Database Structure and initvalues upgrade
#
echo "Updating the CodeX database..."

pass_opt=""
# See if MySQL root account is password protected
mysqlshow 2>&1 | grep password
while [ $? -eq 0 ]; do
    read -p "Existing CodeX DB is password protected. What is the Mysql root password?: " old_passwd
    mysqlshow --password=$old_passwd 2>&1 | grep password
done
[ "X$old_passwd" != "X" ] && pass_opt="--password=$old_passwd"

$CAT <<EOF | $MYSQL $pass_opt sourceforge


---
--- LDAP support
---
ALTER TABLE user ADD ldap_name TEXT AFTER unix_box;



---
--- DocManager update
---

--- DocManager roles
DELETE FROM ugroup WHERE ugroup_id='10';
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (12, "document_tech", "Document Manager Technicians", 100);
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (13, "document_admin", "Document Manager Administrators", 100);

--- Update existing roles
UPDATE user_group SET doc_flags = '2' WHERE doc_flags = '1';


--- default possible permissions
DELETE FROM permissions_values WHERE permission_type='DOCUMENT_READ';

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('DOCGROUP_READ',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCGROUP_READ',10);
-- No default value for DOCUMENT_READ -> use parent permissions
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('DOCUMENT_READ',10);


--- Delete 'deleted' documents
DELETE FROM doc_data WHERE stateid='2';

--- Delete doc_states table (now useless since we have ugroups permissions)
DROP TABLE doc_states;




---
--- add a new tracker template to replace legacy patch tracker
---

UPDATE service SET is_active=0, is_used=0

INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, is_public, allow_anon, email_all_updates, email_address, submit_instructions, browse_instructions, instantiate_for_new_projects) VALUES (5, 100, 'Patches', 'Patch Tracker', 'patch', 1, 0, 0, '', NULL, NULL, 1);

INSERT INTO artifact_field VALUES (1,5,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'artifact_submitters','');
INSERT INTO artifact_field VALUES (2,5,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,5,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (4,5,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (5,5,'plain_text',1,'TA','60/7','Paste the patch here (text only),<br> OR attach it as a file <br> in the \'Attachments\' section','Plain-text version of the patch','',0,1,0,0,NULL,'');
INSERT INTO artifact_field VALUES (6,5,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'artifact_technicians','100');
INSERT INTO artifact_field VALUES (7,5,'category_id',2,'SB','','Category','Patch categories (e.g. mail module,gant chart module,interface, etc)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (8,5,'details',1,'TA','60/7','Description','Description of functionality and application of the patch','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (9,5,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (10,5,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (11,5,'release_id',2,'SB','','Release','The release (global version number) impacted by the artifact','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (12,5,'response_id',2,'SB','','Response','The project team\'s response to the artifact (typically Accepted, Declined, etc.)','P',0,1,1,0,NULL,'100');

INSERT INTO artifact_field_usage VALUES (1,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (2,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (3,5,1,1,1,30);
INSERT INTO artifact_field_usage VALUES (4,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (5,5,1,1,1,50);
INSERT INTO artifact_field_usage VALUES (6,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (7,5,1,1,1,10);
INSERT INTO artifact_field_usage VALUES (8,5,1,1,1,50);
INSERT INTO artifact_field_usage VALUES (9,5,1,0,0,0);
INSERT INTO artifact_field_usage VALUES (10,5,1,1,1,0);
INSERT INTO artifact_field_usage VALUES (11,5,0,0,0,0);
INSERT INTO artifact_field_usage VALUES (12,5,1,0,0,0);

INSERT INTO artifact_field_value_list VALUES (7,5,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (9,5,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (9,5,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');
INSERT INTO artifact_field_value_list VALUES (9,5,4,'Analyzed','The cause of the artifact has been identified and documented',30,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,5,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why in the Resolution field and close it',50,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,6,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',70,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,7,'Ready for Test','Updated/Created software is ready to be included in the next build',90,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,8,'In Test','Updated/Created software is in the build and is ready to enter the test phase',110,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,9,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',130,'H');
INSERT INTO artifact_field_value_list VALUES (9,5,10,'Declined','The artifact was not accepted. Alternatively, you can also Set the status to \"Closed\" and use the Resolution field to explain why it was declined',150,'H');

INSERT INTO artifact_field_value_list VALUES (12,5,1,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why and close it',10,'A');
INSERT INTO artifact_field_value_list VALUES (12,5,2,'Declined','The artifact was not accepted. Alternatively, you can also set the status to \"Closed\" and explain why it was declined',50,'A');



---
--- Wiki Service
---

INSERT INTO service SET service_id=17, group_id=100, label='Wiki', description='Wiki', short_name='wiki', link='/wiki/?group_id=$group_id', is_active=1, is_used=1, scope='system', rank=105;

CREATE TABLE wiki_group_list (
	id int(11) NOT NULL auto_increment,
	group_id int(11) NOT NULL default '0',
	wiki_name varchar(255) NOT NULL default '',
	wiki_link varchar(255) NOT NULL default '',
	description varchar(255) NOT NULL default '',
	rank int(11) NOT NULL default '0',
	PRIMARY KEY (id)	
) TYPE=MyISAM;

-- Table for Wiki access logs
CREATE TABLE `wiki_log` (
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `pagename` varchar(255) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  KEY `all_idx` (`user_id`,`group_id`),
  KEY `time_idx` (`time`),
  KEY `group_id_idx` (`group_id`)
) TYPE=MyISAM;


INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',1); --???
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('WIKI_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKI_READ',14);

INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',1); --???
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('WIKIPAGE_READ',2,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('WIKIPAGE_READ',14);

--
-- Info for Wiki Admin 
--

ALTER TABLE `user_group` ADD `wiki_flags` INT( 11 ) DEFAULT '0' NOT NULL ;

--
-- PHP Wiki tables
--
CREATE TABLE wiki_page (
	id              INT NOT NULL AUTO_INCREMENT,
        pagename        VARCHAR(100) BINARY NOT NULL,
	hits            INT NOT NULL DEFAULT 0,
        pagedata        MEDIUMTEXT NOT NULL DEFAULT '',
	group_id        INT NOT NULL DEFAULT 0,
        PRIMARY KEY (id)
);

CREATE TABLE wiki_version (
	id              INT NOT NULL,
        version         INT NOT NULL,
	mtime           INT NOT NULL,
	minor_edit      TINYINT DEFAULT 0,
        content         MEDIUMTEXT NOT NULL DEFAULT '',
        versiondata     MEDIUMTEXT NOT NULL DEFAULT '',
        PRIMARY KEY (id,version),
	INDEX (mtime)
);


CREATE TABLE wiki_recent (
	id              INT NOT NULL,
	latestversion   INT,
	latestmajor     INT,
	latestminor     INT,
        PRIMARY KEY (id)
);


CREATE TABLE wiki_nonempty (
	id              INT NOT NULL,
	PRIMARY KEY (id)
);


CREATE TABLE wiki_link (
	linkfrom        INT NOT NULL,
        linkto          INT NOT NULL,
	INDEX (linkfrom),
        INDEX (linkto)
);


--
-- localising the DB 
--


-- artifact_notification_event table
--
ALTER TABLE artifact_notification_event ADD short_description_msg VARCHAR(255);
ALTER TABLE artifact_notification_event ADD description_msg VARCHAR(255);
UPDATE artifact_notification_event SET short_description_msg='event_ROLE_CHANGE_short_desc', description_msg='event_ROLE_CHANGE_desc' where event_label='ROLE_CHANGE';
UPDATE artifact_notification_event SET short_description_msg='event_NEW_COMMENT_short_desc', description_msg='event_NEW_COMMENT_desc' where event_label='NEW_COMMENT';
UPDATE artifact_notification_event SET short_description_msg='event_NEW_FILE_short_desc', description_msg='event_NEW_FILE_desc' where event_label='NEW_FILE';
UPDATE artifact_notification_event SET short_description_msg='event_CC_CHANGE_short_desc', description_msg='event_CC_CHANGE_desc' where event_label='CC_CHANGE';
UPDATE artifact_notification_event SET short_description_msg='event_CLOSED_short_desc', description_msg='event_CLOSED_desc' where event_label='CLOSED';
UPDATE artifact_notification_event SET short_description_msg='event_PSS_CHANGE_short_desc', description_msg='event_PSS_CHANGE_desc' where event_label='PSS_CHANGE';
UPDATE artifact_notification_event SET short_description_msg='event_ANY_OTHER_CHANGE_short_desc', description_msg='event_ANY_OTHER_CHANGE_desc' where event_label='ANY_OTHER_CHANGE';
UPDATE artifact_notification_event SET short_description_msg='event_I_MADE_IT_short_desc', description_msg='event_I_MADE_IT_desc' where event_label='I_MADE_IT';
UPDATE artifact_notification_event SET short_description_msg='event_NEW_ARTIFACT_short_desc', description_msg='event_NEW_ARTIFACT_desc' where event_label='NEW_ARTIFACT';

ALTER TABLE artifact_notification_event DROP short_description;
ALTER TABLE artifact_notification_event DROP description;


--  artifact_notification_event_default table
--
ALTER TABLE artifact_notification_event_default ADD short_description_msg VARCHAR(255);
ALTER TABLE artifact_notification_event_default ADD description_msg VARCHAR(255);
UPDATE artifact_notification_event_default SET short_description_msg='event_ROLE_CHANGE_short_desc', description_msg='event_ROLE_CHANGE_desc' where event_label='ROLE_CHANGE';
UPDATE artifact_notification_event_default SET short_description_msg='event_NEW_COMMENT_short_desc', description_msg='event_NEW_COMMENT_desc' where event_label='NEW_COMMENT';
UPDATE artifact_notification_event_default SET short_description_msg='event_NEW_FILE_short_desc', description_msg='event_NEW_FILE_desc' where event_label='NEW_FILE';
UPDATE artifact_notification_event_default SET short_description_msg='event_CC_CHANGE_short_desc', description_msg='event_CC_CHANGE_desc' where event_label='CC_CHANGE';
UPDATE artifact_notification_event_default SET short_description_msg='event_CLOSED_short_desc', description_msg='event_CLOSED_desc' where event_label='CLOSED';
UPDATE artifact_notification_event_default SET short_description_msg='event_PSS_CHANGE_short_desc', description_msg='event_PSS_CHANGE_desc' where event_label='PSS_CHANGE';
UPDATE artifact_notification_event_default SET short_description_msg='event_ANY_OTHER_CHANGE_short_desc', description_msg='event_ANY_OTHER_CHANGE_desc' where event_label='ANY_OTHER_CHANGE';
UPDATE artifact_notification_event_default SET short_description_msg='event_I_MADE_IT_short_desc', description_msg='event_I_MADE_IT_desc' where event_label='I_MADE_IT';
UPDATE artifact_notification_event_default SET short_description_msg='event_NEW_ARTIFACT_short_desc', description_msg='event_NEW_ARTIFACT_desc' where event_label='NEW_ARTIFACT';

ALTER TABLE artifact_notification_event_default DROP short_description;
ALTER TABLE artifact_notification_event_default DROP description;


--  artifact_notification_role table
--
ALTER TABLE artifact_notification_role ADD short_description_msg VARCHAR(255);
ALTER TABLE artifact_notification_role ADD description_msg VARCHAR(255);
UPDATE artifact_notification_role SET short_description_msg='role_SUBMITTER_short_desc', description_msg='role_SUBMITTER_desc' where role_label='SUBMITTER';
UPDATE artifact_notification_role SET short_description_msg='role_ASSIGNEE_short_desc', description_msg='role_ASSIGNEE_desc' where role_label='ASSIGNEE';
UPDATE artifact_notification_role SET short_description_msg='role_CC_short_desc', description_msg='role_CC_desc' where role_label='CC';
UPDATE artifact_notification_role SET short_description_msg='role_COMMENTER_short_desc', description_msg='role_COMMENTER_desc' where role_label='COMMENTER';

ALTER TABLE artifact_notification_role DROP short_description;
ALTER TABLE artifact_notification_role DROP description;


--  artifact_notification_role_default table
--
ALTER TABLE artifact_notification_role_default ADD short_description_msg VARCHAR(255);
ALTER TABLE artifact_notification_role_default ADD description_msg VARCHAR(255);
UPDATE artifact_notification_role_default SET short_description_msg='role_SUBMITTER_short_desc', description_msg='role_SUBMITTER_desc' where role_label='SUBMITTER';
UPDATE artifact_notification_role_default SET short_description_msg='role_ASSIGNEE_short_desc', description_msg='role_ASSIGNEE_desc' where role_label='ASSIGNEE';
UPDATE artifact_notification_role_default SET short_description_msg='role_CC_short_desc', description_msg='role_CC_desc' where role_label='CC';
UPDATE artifact_notification_role_default SET short_description_msg='role_COMMENTER_short_desc', description_msg='role_COMMENTER_desc' where role_label='COMMENTER';

ALTER TABLE artifact_notification_role_default DROP short_description;
ALTER TABLE artifact_notification_role_default DROP description;



-- service table
--

UPDATE service SET label='service_summary_lbl_key', description='service_summary_desc_key' WHERE short_name='summary' AND label='Summary' AND description='Project Summary';
UPDATE service SET label='service_admin_lbl_key', description='service_admin_desc_key' WHERE short_name='admin' AND label='Admin' AND description='Project Administration';
UPDATE service SET label='service_homepage_lbl_key', description='service_homepage_desc_key' WHERE short_name='homepage' AND label='Home Page' AND description='Project Home Page';
UPDATE service SET label='service_forum_lbl_key', description='service_forum_desc_key' WHERE short_name='forum' AND label='Forums' AND description='Project Forums';
UPDATE service SET label='service_bugs_lbl_key', description='service_bugs_desc_key' WHERE short_name='bugs' AND label='Bugs' AND description='Bug Tracking System';
UPDATE service SET label='service_support_lbl_key', description='service_support_desc_key' WHERE short_name='support' AND label='Support' AND description='Support Request Manager';
UPDATE service SET label='service_patch_lbl_key', description='service_patch_desc_key' WHERE short_name='patch' AND label='Patches' AND description='Patch Manager';
UPDATE service SET label='service_mail_lbl_key', description='service_mail_desc_key' WHERE short_name='mail' AND label='Lists' AND description='Mailing Lists';
UPDATE service SET label='service_task_lbl_key', description='service_task_desc_key' WHERE short_name='task' AND label='Tasks' AND description='Task Manager';
UPDATE service SET label='service_doc_lbl_key', description='service_doc_desc_key' WHERE short_name='doc' AND label='Docs' AND description='Document Manager';
UPDATE service SET label='service_survey_lbl_key', description='service_survey_desc_key' WHERE short_name='survey' AND label='Surveys' AND description='Project Surveys';
UPDATE service SET label='service_news_lbl_key', description='service_news_desc_key' WHERE short_name='news' AND label='News' AND description='Project News';
UPDATE service SET label='service_cvs_lbl_key', description='service_cvs_desc_key' WHERE short_name='cvs' AND label='CVS' AND description='CVS Access';
UPDATE service SET label='service_file_lbl_key', description='service_file_desc_key' WHERE short_name='file' AND label='Files' AND description='File Releases';
UPDATE service SET label='service_tracker_lbl_key', description='service_tracker_desc_key' WHERE short_name='tracker' AND label='Trackers' AND description='Project Trackers';
UPDATE service SET label='service_svn_lbl_key', description='service_svn_desc_key' WHERE short_name='svn' AND label='Subversion' AND description='Subversion Access';
UPDATE service SET label='service_wiki_lbl_key', description='service_wiki_desc_key' WHERE short_name='wiki' AND label='Wiki' AND description='Wiki';


EOF


###############################################################################
# Update document access rights
#
$PERL <<'EOF'
use DBI;
require "/home/httpd/SF/utils/include.pl";

## load local.inc variables
&load_local_config();

&db_connect;


## get existing values from the doc_data table
$query = "SELECT docid,stateid,doc_group,restricted_access FROM doc_data";
$c = $dbh->prepare($query);
$c->execute();

my $failed=0;

while (my ($docid,$stateid,$doc_group,$restricted_access) = $c->fetchrow()) {
    if ($stateid=="2") { #deleted
        my $query3 = "DELETE FROM doc_data WHERE docid='$docid'";
        my $c3 = $dbh->prepare($query3);
        my $res=$c3->execute();
        if (!$res) {
            print "Could not delete document $docid\n";
            $failed=1;;
        }
    } elsif ($stateid=="4") { #hidden
        my $query3 = "INSERT INTO permissions VALUES ('DOCUMENT_READ',$docid,100)";
        my $c3 = $dbh->prepare($query3);
        my $res=$c3->execute();
        if (!$res) {
            print "Could not set hidden status for document $docid\n";
            $failed=1;;
        }
    } elsif ($stateid=="5") { #private
        my $query3 = "INSERT INTO permissions VALUES ('DOCUMENT_READ',$docid,3)";
        my $c3 = $dbh->prepare($query3);
        my $res=$c3->execute();
        if (!$res) {
            print "Could not set private status to document $docid\n";
            $failed=1;;
        }
    } else { #active or pending, check restricted_access
        if ($restricted_access=="1") {
            my $query3 = "INSERT INTO permissions VALUES ('DOCUMENT_READ',$docid,2)";
            my $c3 = $dbh->prepare($query3);
            my $res=$c3->execute();
            if (!$res) {
                print "Could not restrict access to document $docid\n";
                $failed=1;;
            }
        }
    }
}

# Now delete useless rows
if (!$failed) {
    my $query3 = "ALTER TABLE doc_data DROP COLUMN stateid, DROP COLUMN restricted_access";
    my $c3 = $dbh->prepare($query3);
    my $res=$c3->execute();
    if (!$res) {
        print "Could not drop columns from doc_data\n";
        $failed=1;;
    }
}

exit;
EOF




###############################################################################
# Add wiki service
#


$PERL <<'EOF'
use DBI;
require "/home/httpd/SF/utils/include.pl";

# load local.inc variables
&load_local_config();

&db_connect;

sub wiki_exist {
    my ($group_id) = @_;
    
    my ($query, $c, $res);
    $query = "SELECT service_id FROM service WHERE group_id=".$group_id." AND short_name='wiki'";
    $c = $dbh->prepare($query);
    $c->execute();
    return $c->rows;
}

# Check wiki service entry existency before calling this script
sub check_wiki_service_exist {
    my ($query, $c, $res);

    $query = "SELECT * FROM service WHERE service_id=17";
    $c = $dbh->prepare($query);
    $c->execute();
    if ($c->rows != 1) {
	print "Please apply SQL patch before executing this script!\n";
	exit 1;
    }
}

sub add_wiki {
    my ($group_id) = @_;
    my ($query, $c, $res);
    $query = "INSERT INTO service SET group_id=".$group_id.", label='Wiki', description='Wiki', short_name='wiki', link='/wiki/?group_id=".$group_id."', is_active=1, is_used=0, scope='system', rank=105";
    $c = $dbh->prepare($query);
    $res = $c->execute();
    if(!$res) {
	return FALSE;
    }
    return TRUE;
}

sub add_wiki_service {
    my ($group_id) = @_;

    if(!wiki_exist($group_id)) {
	if(!add_wiki($group_id)) {
	    print " Error Adding Wiki service for $group_id\n";
	    exit;
	}
	else {
	    #print " OK\n";
	}
    }
    else {
	print 'There is already a wiki service for '.$group_id."\n";
    }

}

sub setup_interwiki_map {
    my $interwikimap = '/home/httpd/SF/common/wiki/phpwiki/lib/interwiki.map';
    
    print 'Update '.$interwikimap."\n";

    if(($sys_force_ssl == 1) || ($sys_stay_in_ssl == 1)) {
	$host = 'https://'.$sys_https_host;
    }
    else {
	$host = 'http://'.$sys_default_domain;
    }
    
    open(FILE, ">$interwikimap") || die "Can't open $interwikimap: $!\n";
    print FILE "CodeX ".$host."/wiki/index.php?group_id=1&pagename=\n";
    close(FILE)
}


#
# MAIN
#

# Check wiki service entry existency before calling this script
check_wiki_service_exist();

# Insert a wiki for each project
my ($query, $c, $res);
$query = "SELECT group_id FROM service WHERE group_id>100 OR group_id=1 GROUP BY group_id";
$c = $dbh->prepare($query);
$c->execute();
while(my $group_id = $c->fetchrow()) {
    add_wiki_service($group_id);
}

# Setup interwiki_map
setup_interwiki_map();

exit;
EOF

# OLD 2.0 TO 2.2 STUFF





# Update 'file' service for each project
$PERL <<'EOF'
use DBI;
require "/home/httpd/SF/utils/include.pl";

## load local.inc variables
&load_local_config();

&db_connect;

sub updateFileService {
  my ($group_id,$url) = @_;
  $sql= "UPDATE service SET link='$url' WHERE group_id='$group_id' AND short_name='file'";
  $sth = $dbh->prepare($sql);
  $res = $sth->execute();

  if (!$res) {
    print "Could not update service 'file' for group_id ". $group_id."\n";
    print "Please contact your CodeX support representative\n";
  }
}

## get existing values from the group table
$query = "select group_id FROM groups";
$c = $dbh->prepare($query);
$c->execute();

while (my ($group_id) = $c->fetchrow()) {
    if ($group_id == 100) {
	$url = "/file/showfiles.php?group_id=\$group_id";
    } else {
        $url = "/file/showfiles.php?group_id=$group_id";
    }
    updateFileService($group_id, $url);
}

exit;
EOF






##############################################
# Install and Configure Subversion
#
$CP $INSTALL_DIR/SF/utils/svn/commit-email.pl /usr/local/bin
$CHOWN sourceforge.sourceforge /usr/local/bin/commit-email.pl
$CHMOD 775 /usr/local/bin/commit-email.pl

# Add sys_svn_host variable to local.inc
# and Documentation parameter file (ParametersLocal.dtd)
sys_svn_host="svn.$sys_default_domain"

$PERL -i'.orig' -p -e"s:(^\\\$sys_cvs_host.*):\1\n\n// Machine that hosts Subversion\n// Note that while this machine need not be the same as the CodeX host,\n// the \"viewcvs\" interface currently must run on the CodeX host system.\n// If this host is different from the CodeX host, then the CodeX host\n// will need to be able to access the CVS file tree via NFS.\n\\\$sys_svn_host = \"$sys_svn_host\";:" /etc/codex/conf/local.inc

$PERL -i'.orig' -p -e"s:(^<\!ENTITY SYS_CVS_HOST.*):\1\n<\!ENTITY SYS_SVN_HOST \"$sys_svn_host\":" /etc/codex/documentation/user_guide/xml/en_US/ParametersLocal.dtd


##############################################
#
# A few updates that may not be necessary in case of a real OS upgrade
# but that are useful in case of fresh install of OS.
# In any case, you should run these updates
#
##############################################


##############################################
# FTP server configuration
#
make_backup "/etc/xinetd.d/wu-ftpd" codex20
echo "Configuring FTP servers and directories..."
$CAT <<EOF >/etc/xinetd.d/wu-ftpd
service ftp
{
        disable = no
        socket_type             = stream
        wait                    = no
        user                    = root
        server                  = /usr/sbin/in.ftpd
        server_args             = -l -a
        log_on_success          += DURATION
        nice                    = 10
}
EOF

make_backup "/etc/ftpaccess"
$CAT <<EOF >/etc/ftpaccess
class   all   real,guest,anonymous  *
class anonftp anonymous *

upload /home/ftp * no
upload /home/ftp /bin no
upload /home/ftp /etc no
upload /home/ftp /lib no
noretrieve .notar
upload /home/ftp /incoming yes ftpadmin ftpadmin 0644 nodirs
noretrieve /home/ftp/incoming
noretrieve /home/ftp/codex

email root@localhost

loginfails 5

readme  README*    login
readme  README*    cwd=*

message /welcome.msg            login
message .message                cwd=*

compress        yes             all
tar             yes             all
chmod        no        guest,anonymous
delete        no        guest,anonymous
overwrite    no        guest,anonymous
rename        no        guest,anonymous

log transfers anonymous,real inbound,outbound

shutdown /etc/shutmsg

passwd-check rfc822 warn
EOF

##############################################
# Crontab configuration
#

# Install root crontab if it is not set
install_root_crontab=1;
if [ -a /var/spool/cron/root ]; then
   grep xerox_crontab /var/spool/cron/root > /dev/null
   if [ $? -eq 0 ]; then
     install_root_crontab=0;
     echo "root user crontab seems up to date..."
   fi
fi

if [ $install_root_crontab -eq 1 ]; then 
  echo "Installing root user crontab..."
  $CAT <<'EOF' >/tmp/cronfile
# run the Codex crontab script once every 2 hours
# this script synchronizes user, groups, cvs repo,
# directories, mailing lists, etc...
0 0-23/2 * * * /home/httpd/SF/utils/xerox_crontab.sh
#
# run the daily statistics script just a little bit after
# midnight so that it computes stats for the day before
# Run at 0:30 am
30 0 * * * /home/httpd/SF/utils/xerox_all_daily_stats.sh
#
# run the weekly stats for projects. Run it on Monday morning so that
# it computes the stats for the week before
# Run on Monday at 1am
0 1 * * Mon (cd /home/httpd/SF/utils/underworld-root; ./db_project_weekly_metric.pl)
#
# weekly backup preparation (mysql shutdown, file dump and restart)
45 0 * * Sun /home/tools/backup_job
#
# Delete all files in FTP incoming that are older than 2 weeks (336 hours)
#
0 3 * * * /usr/sbin/tmpwatch -m -f 336 /home/ftp/incoming
#
# It looks like we have memory leaks in Apache in some versions so restart it
# on Sunday. Do it while the DB is down for backup
50 0 * * Sun /etc/rc.d/init.d/httpd restart
#
# Once a minute make sure that the setuid bit is set on some critical files
* * * * * (cd /usr/local/bin; /bin/chmod u+s commit-email.pl log_accum tmpfilemove fileforge)
EOF
  crontab -u root /tmp/cronfile
fi


# Install sourceforge crontab if needed
install_sourceforge_crontab=1;
if [ -a /var/spool/cron/sourceforge ]; then
   grep generate_doc /var/spool/cron/sourceforge > /dev/null
   if [ $? -eq 0 ]; then
     install_sourceforge_crontab=0;
     echo "sourceforge user crontab seems up to date..."
   fi
fi

if [ $install_sourceforge_crontab -eq 1 ]; then 
  echo "Installing sourceforge user crontab..."
  $CAT <<'EOF' >/tmp/cronfile
# Re-generate the CodeX User Guide on a daily basis
00 03 * * * /home/httpd/SF/utils/generate_doc.sh -f
EOF
  crontab -u sourceforge /tmp/cronfile
fi


##############################################
# Restarting some services before upgrading
#
echo "Starting crond and apache..."
$SERVICE crond start
$SERVICE httpd restart
$SERVICE sendmail start


##############################################
# Generate Documentation
#
echo "Updating the User Manual. This might take a few minutes."
/home/httpd/SF/utils/generate_doc.sh -f
todo "Edit utils/generate_doc.sh to make sure that the CVS update is possible. Do a cvs login on CVS server as user 'sourceforge'. Alternatively, add a '-f' flag in the 'sourceforge' crontab to force generation each night"

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
todo "Customize /etc/php.ini: "
todo "  - memory_limit = 30M"
todo "  - post_max_size = 20M"
todo "  - upload_max_file_size = 20M"
todo "Customize LDAP parameters in local.inc "

# End of it
echo "=============================================="
echo "Installation completed succesfully!"
$CAT $TODO_FILE

exit 1;


cat <<EOF >/dev/null
When migrating a 2.2 site to 2.4 here are the things that must be done:

- Add language_id field in user_table
- Create table supported_languages and feed it with English and French entries
- Create the directory /etc/codex/themes/messages with sourceforge.sourceforge 755
- Check for the existence of the following customized content files and advise the the person in charge of the installation of the new .tab files to customize to obtain the same effect:
  * account/register_confirmation.txt, account/register_needs_approval.txt, account/register_email.txt, account/register_purpose.txt: see account/account.tab#account_register
  * homepage/staff.txt, homepage/thanks.txt, homepage/welcome_intro.txt: see homepage/homepage.tab
  * my/intro.txt: see my/my.tab
OK - docman specific updates
EOF
