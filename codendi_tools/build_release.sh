#!/bin/sh
date
#CODENDI_VERSION='Codendi_Pro_4.0'
#CODENDI_SHORT_VERSION='pro.4.0'
CODENDI_VERSION='labs'
CODENDI_SHORT_VERSION='labs'
PACKAGE_DIR=/root/Codendi_Packaging/$CODENDI_VERSION/packages
SOURCE_DIR=/root/Codendi_Packaging/$CODENDI_VERSION/codendi
BUILD_DIR=/root/Codendi_Packaging/$CODENDI_VERSION/build
echo "Building ISO image for version: $CODENDI_VERSION"
yn="0"
read -p "Update source and package working copies? [y|n]:" yn
if [ "$yn" = "y" ]; then
  echo `cd $SOURCE_DIR; svn update`
  echo `cd $PACKAGE_DIR; svn update`
fi
echo "SVN update done at:"
CODENDI_REVISION=`svn info $SOURCE_DIR | grep Revision | sed 's/Revision: //'`
ISO_LABEL="Codendi $CODENDI_SHORT_VERSION"
ISO_FILE="/root/Codendi_Packaging/$CODENDI_VERSION/iso_images/codendi-$CODENDI_SHORT_VERSION.r$CODENDI_REVISION.iso"

# Shell commands used
LS='/bin/ls'
CP='/bin/cp'
TAR='/bin/tar'
TAIL='/usr/bin/tail'
MKDIR='/bin/mkdir'
CHOWN='/bin/chown'
CHMOD='/bin/chmod'
RSYNC='/usr/bin/rsync'
GREP='/bin/grep'
RM='/bin/rm'
MV='/bin/mv'

# Misc functions
die() {
  # $1: message to prompt before exiting
  echo $1; exit 1
}

##### MAIN
# Must be root to execute this dir
[ `id -u` -ne 0 ] && die "Must be root to execute this script!"

# Clean up build dir
echo "Creating clean build directory..."
#rm -rf $BUILD_DIR; 
$MKDIR -p $BUILD_DIR
cd $BUILD_DIR
$RM -f codendi_install.sh INSTALL migration_* README  RELEASE_NOTES
# Copy the install script at the top directory
echo "Copying the Codendi installation script at:"
cd $PACKAGE_DIR
$CP -af $SOURCE_DIR/codendi_tools/codendi_install.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/codendi_install.sh

# Copy the migration script at the top directory
echo "Copying the Codendi migration script..."
cd $PACKAGE_DIR
$CP -af $SOURCE_DIR/codendi_tools/migration_from_Codendi_3.6_to_Codendi_4.0.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/migration_from_Codendi_3.6_to_Codendi_4.0.sh
$CP -af $SOURCE_DIR/codendi_tools/migration_from_Codendi_3.6_to_Codendi_4.0.README $BUILD_DIR
$CHMOD +x $BUILD_DIR/migration_from_Codendi_3.6_to_Codendi_4.0.README

# Copy the entire Codendi and nonRPMS_Codendi dir
echo "Copying the Codendi software and nonRPMS packages... at:"
$RSYNC -a --exclude='.svn' --delete $PACKAGE_DIR/nonRPMS_Codendi $BUILD_DIR
mkdir -p $BUILD_DIR/Codendi
BRANCH_NAME=`echo $SOURCE_DIR|sed 's/.*\///'`
if [ -e $BUILD_DIR/Codendi/src ]; then
  $MV $BUILD_DIR/Codendi/src $BUILD_DIR/Codendi/$BRANCH_NAME
fi
echo "... source1 done at:"
$RSYNC -a --delete $SOURCE_DIR $BUILD_DIR/Codendi
echo "... source2 done at:"
$MV $BUILD_DIR/Codendi/$BRANCH_NAME $BUILD_DIR/Codendi/src
# Only copy the latest RPMs from RPMS Codendi
echo "Copying the Codendi RPMS packages..."
$MKDIR -p $BUILD_DIR/RPMS_Codendi
cd $PACKAGE_DIR/RPMS_Codendi
RPM_LIST=`ls -1`
for i in $RPM_LIST
do
    cd $PACKAGE_DIR/RPMS_Codendi/$i
    newest_rpm=`$LS -1 -I old | $TAIL -1`
    $MKDIR -p $BUILD_DIR/RPMS_Codendi/$i
    $RSYNC -a --exclude='.svn' --delete $newest_rpm $BUILD_DIR/RPMS_Codendi/$i
    cd $BUILD_DIR/RPMS_Codendi/$i
    old_rpms=`$LS -1 | $GREP -v $newest_rpm`
    for j in $old_rpms
    do
      echo "deleting $i/$j from build dir"
      $RM -rf $j
    done
done
echo "... packages done at:"

# Remove deprecated packages
cd $BUILD_DIR/RPMS_Codendi 
RPM_LIST=`ls -1`
for i in $RPM_LIST
do
    if [ ! -e $PACKAGE_DIR/RPMS_Codendi/$i ];
    then
        echo "Removing deprecated package: $i"
        echo $RM -rf $BUILD_DIR/RPMS_Codendi/$i
    fi
done

# Change ownership of everything
echo "Changing ownership to root.root for everything..."
$CHOWN -R root.root $BUILD_DIR/*
echo "... done at:"

# create the tar file of Codendi sources
echo "Creating tar file of Codendi sources..."
cd $BUILD_DIR/Codendi/src
$TAR cfz ../codendi.tgz .
$CHOWN root.root ../codendi.tgz
echo "... done at:"

# create a README file at the top
cd $BUILD_DIR
cat <<'EOF' >README
Codendi: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, 2001-2009. All Rights Reserved
http://www.codendi.com

The Codendi software From Xerox aims at providing large companies with an
easy to use, cost effective and scalable platform to make global software
sharing and reuse a reality.

Codendi provides all project development teams with a series of tools that
are needed on a daily basis to produce good software (defect/task/requirements
tracking, version control, access permission, software release mechanisms,
communication channels,etc.)

Beside that Codendi also provide managers as well as all other authorized
visitors with a global view on all on-going software development activities.
Using Codendi you can maximize chances of reuse since sharing becomes completely
painless.

License
-------
Codendi is originally based on SourceForge 2.0 and the numerous
enhancements brought to the original software are under the GNU General Public
License (GPL v2).

Contact
-------
If you want to know more about Codendi or if you have questions, submit them
at http://www.codendi.com

-- The Codendi Team
http://www.codendi.com

EOF

# create a INSTALL file at the top
cat <<'EOF' >INSTALL
Codendi: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, 2001-2009. All Rights Reserved
http://www.codendi.com

- login as root user
- cd into the directory where the codendi_install.sh script is located
(probably /mnt/cdrom if you received the Codendi software on a CDROM)
- For a fresh Codendi installation run the installation script with ./codendi_install.sh
- For an update from 3.6 please read migration_from_Codendi_3.6_to_Codendi_4.0.README.
- For an update from a prior release, please update to Codendi 3.6 first.

After a fresh installation, here is a checklist of things to verify or update:
- Finish sendmail installation (see installation Guide) and create codendi-admin alias in /etc/aliases
- You may want to customize configuration files for your local settings:
    /etc/codendi/conf/local.inc
    /etc/codendi/conf/database.inc
    /etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd
    /etc/codendi/documentation/cli/xml/ParametersLocal.dtd
    /etc/httpd/conf/httpd.conf
    /usr/lib/codendi/bin/backup_job
    /usr/lib/codendi/bin/backup_subversion.sh
    /etc/codendi/site-content/en_US/others/default_page.php (project web site default home page)
- Customize site-content information for your site.
    For instance: contact/contact.txt cvs/intro.txt
    svn/intro.txt include/new_project_email.txt, layout/osdn_sites.txt etc.
- If you are behind a proxy, then you need to declare the proxy in two files: 
  * sys_proxy in /etc/codendi/conf/local.inc (for external RSS feeds support)
  * /home/codendiadm/.subversion/servers for the Server Update plugin
- In order to enable the subversion update, you also need to type the following commands (as codendiadm):
      cd /usr/share/codendi/
      svn status -u --username <your_login_on_partners>
    Accept the certificate permanently, type in your password, and accept to store it locally.
- If only HTTPS is enabled on the Codendi server:
   * update ENTITY SYS_UPDATE_SITE in /etc/codendi/documentation/user_guide/xml/ParametersLocal.dtd (replace 'http' by 'https')
   * WARNING: The Eclipse plugin *requires* a *valid* SSL certificate (from a certified authority). Self-signed certificates *won't* work.
- If you wish to use SSL encryption with the Jabber server, you need to import or generate an SSL server into Openfire's web server:
   * Go in Openfire Admin iterface (on port 9090 by def), then: Server Settings -> Server Certificates
- Create the shell login files for Codendi users in /etc/skel_codendi
- Change the default login shell if needed in the database (/sbin/nologin or /usr/lib/codendi/bin/cvssh, etc.)
- Last, log in as 'admin' on web server, read/accept the license, and click on 'server update'. Then update the server to the latest available version. 


-- The Codendi Team
http://www.codendi.com
EOF

# create a RELEASE_NOTES file at the top
cat <<'EOF' >RELEASE_NOTES
Codendi: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, 2001-2009. All Rights Reserved
http://www.codendi.com

This is Codendi 4.0 
Please read the README and INSTALL files carefully, and get in touch with us
at http://www.codendi.com if you have questions.


Here are the new features of Codendi 4.0:

- New "Dawn" theme: 
  This is certainly the most visible change. we hope you like it! 
  Of course, you can still use all the previous themes.

- Project and personal dashboards
  Codendi 4.0 provides fully-customizable dashboard for projects (replacing the previous 'Summary' page) and for 'My Personal Page'.
  The dashboards can be customized with many widgets provided with Codendi 4.0, and their layout  is completely customizable: columns, lines, can be arranged like you want.
  Among the new widgets, you will find Continuous Integration widgets (status of latest build, current trend, etc.), a Subversion statistics widget, or a Twitter follow widget!

- Continuous Integration with Hudson
  Codendi may now be connected to your Hudson Continuous Integration (CI) servers. This allows you to access your CI information directly from your project dashboard. 
  Moreover, we provide guidelines for a better integration between Hudson and Codendi, allowing you to trigger a CI build on every commit, or to be notified when a build fails. 
  Read the user documentation for more details.

- Web Chat
  Codendi now provides a web interface to your project chat room! Interact with your teammates on Jabber with your web browser.
  Additionally, Codendi now stores the chat logs in its database, so that you can access them later.

- References management (like "bug #123")
  - Cross-reference extraction and storage now works on all services (tracker, SVN, CVS, documents, files, etc.)
  - New button to delete stored references
  - Added meaningful tooltips on references
  - Implement mandatory references in CVS or SVN commits: with this option, the commit is rejected if it does not contain a reference to a Codendi object (task, bug, revision, etc.)

- Document Manager enhancements:
  - Docman uploader/downloader
  - Improved SOAP API
  - one approval table per wiki or file version
  - wiki page permissions linked to docman if needed
  - display docman references (if any) in front of wiki page  - Links to documents in notification emails
  - Filter a search by item type
  - Enable monitoring of an item for people that are added to the item's approval table (starting from the moment they're notified by the approval table)
  - Tree view: expand a folder after an item is created inside and scroll to the page to the item
  - When creating a metadata, display "allow multiple selection" choice. Checkbox is disabled if the type is not "list". Also, a non-empty name is required, and it cannot be the same name as another property (verification also done when updating a property).
  - Statistics tabs for folders (number of items, size)

- Permissions on artifacts:
  It is now possible to set specific permissions on individual artifacts.

- New backend, completely rewritten in PHP!
  - Backend actions (repository creation, membership change, etc.) performed within one minute!
  - System event implementation
  - System Event monitoring page and notification for administrators
  - authentication (system, CVS, SVN) is now based on database queries.
  - In a "restricted user" setup, non-restricted users may now access public SVN repositories.
  - new Codendi log file in /var/log/codendi
  - improved SVN logging

- Improved performance
  In Codendi 4.0, we worked on improving the user experience by implementing several performance-related tasks (database optimizations, Javascript, language files, etc.)

Other changes
- Add support for files larger than 2GB in File Release Manager.
- CodeX cleanup: CodeX, the old name of Codendi, has now (almost) completely disappeared from the code base.
- Updated packages: Codendi 4.0 provides Subversion 1.6, and many other recent packages.
- .CODEX_PRIVATE file is now deprecated (use the new CVS private repository option instead).
- Samba support is also discontinued (access to Windows shares) for security reasons.

...and many more smaller additions and fixes.

Enjoy Codendi!

-- The Codendi Team
   <http://www.codendi.com>
EOF

# Build ISO image
echo "Building ISO image in $ISO_FILE at:"
mkisofs -quiet -A "$ISO_LABEL" -V "$ISO_LABEL" -J -R -x ./lost+found -x .. -o "$ISO_FILE" $BUILD_DIR

echo "Codendi ISO image available at $ISO_FILE ..."
date
echo "Done!"
exit 0
# end of it


