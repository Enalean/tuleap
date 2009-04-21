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
# delete codendi_tools directory in BUILD_DIR
#echo "Deleting codendi_tools directory..."
#cd $BUILD_DIR/Codendi/src
#rm -rf codendi_tools

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
Copyright (c) Xerox Corporation, 2001-2008. All Rights Reserved
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
License (GPL).

Contact
-------
If you want to know more about Codendi or if you have questions send an email
to info@codendi.xerox.com

Support Requests
----------------
Codendi users inside of the Xerox Network must submit their support requests
through the Codendi central site at:
http://codendi.xerox.com/tracker/?func=add&group_id=1&atid=922 

Codendi customers outside of Xerox must submit their support requests through
the external Codendi support site at:
https://partners.xrce.xerox.com/tracker/?func=add&group_id=120&atid=199

-- The Codendi Team
   <info@codendi.xerox.com>

EOF

# create a INSTALL file at the top
cat <<'EOF' >INSTALL
Codendi: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, 2001-2008. All Rights Reserved
http://www.codendi.com

- login as root user
- cd into the directory where the codendi_install.sh script is located
(probably /mnt/cdrom if you received the Codendi software on a CDROM)
- For a fresh Codendi installation run the installation script with ./codendi_install.sh
- For an update from 3.4 please read migration_from_Codendi_3.4_to_Codendi_4.0.README.
- For an update from a prior release, please update to Codendi 3.4 first.

-- The Codendi Team
   <info@codendi.xerox.com>
EOF

# create a RELEASE_NOTES file at the top
cat <<'EOF' >RELEASE_NOTES
Codendi: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, 2001-2008. All Rights Reserved
http://www.codendi.com

This is Codendi 4.0 
Please read the README and INSTALL files carefully, and get in touch with us
at codendi-contact@codendi.xerox.com if you have questions.


Here are the new features of Codendi 4.0:

- Codendi now includes a test management framework: SalomeTMF.
  Salomé-TMF is an independent, open source Test Management Tool, which helps 
  you to manage your entire testing process - creating test scripts, executing
  tests, tracking results, produce documentation, and more.
    * Organize your test cases and your campaign in hierarchical tree structure
    * Define manual tests (sequence of actions) or automatic (programs or scripts of tests)
    * Define several environments under tests in a project
    * Parametrize your manual and automatic tests and carry out them on several environments
    * Track results associated with test execution on an environment under test
    * Attach files or URLs on the data managed by Salome-TMF (test, execution, environment, etc) 
  Salomé is nicely integrated into Codendi: it is automatically setup in each project,
  and is launched with a single click. You can then submit test defects in a Codendi
  tracker directly from Salomé.
  Salomé is provided as a Java Web Start application, but its configuration is done
  in the Codendi interface. The Codendi template mechanism makes it possible to inherit
  the Salome configuration from the template project.
  Please note that the current version of Salomé provided with Codendi needs a direct
  JDBC access to the Codendi server. Another version based on SOAP is being integrated.

- Instant Messaging server.
  Codendi 4.0 provides a Jabber/XMPP server for instant messaging (IM).
  Every user declared in Codendi has a corresponding jabberID, and every project
  has a chat room reserved for its members. 
  Simply connect to Codendi with a Jabber client and use your jabber ID: your
  list of contact automatically contains all the projects you belong to, with all the
  project members: you can chat with them instantly!
  Furthermore, in some places, Codendi displays a jabber icon next to the user names
  showing the jabber status of the user (available, disconnected, away, etc.) so
  that you know if you can start a chat session!


- Graphs for trackers
  Add some visual information on your trackers! Codendi provides easy-to-use
  graphical components for trackers that allow creation of Pie, Bar and
  Gantt charts related to trackers.
  You may now visually display the distribution of bugs per severity or assignees,
  display your project plan in a Gantt chart.... 
  This feature perfectly fits our generic tracker, and can be used with any custom fields.

- Improved security. Codendi 4.0 benefits from all the work done on Codendi 3.4-Security release.
  User input is properly filtered to avoid all types of security risks.

- Improve cross-reference tracking.
  If you reference an artifact in a SVN or CVS commit, the artifact is automatically updated with a 
  reference to the commit (and vice-versa). All references are now clearly displayed;

- Codendi 4.0 comes with Subversion 1.5, that now provides merge tracking!

- Backend updates:
  * Platform upgrade: Codendi 4.0 runs on RedHat Enterprise Linux 5 and CentOS 5. This means that 
    all packages have been upgraded: PHP5, Apache 2.2, MySQL 5, etc.
    New SELinux modules are provided and are fully compatible with Codendi
  * Major switch of charset from iso-latin1 to UTF-8. All services are impacted, and this now
    provides a better handling of non-ASCII characters.
  * Codendi now uses the SOAP library provided by PHP5 for better performance and scalability.
    Some changes were made to the API.

- Other smaller improvements:
* Codendi provides a new tracker template "Scrum Backlog" for managing Scrum user stories
* Project description fields (short description, long description, etc.) can now be customized 
  by the site administrator.
* New WYSIWYG interface for embedded document edition (in Document Manager): you may now edit
  an HTML document online as you would edit a document on your desktop.
* You may now set default value for text area fields in trackers. For instance,
  you can create a template for "original submission"
* improved user statistics (number of active users)
* updated documentation explaining all the new features
* nice SVN repository listing (when directly accessing URLs like http://server/svnroot/guineapig/).
* improved, scalable interface for user group edition
* Site administrators may now directly create user accounts
* user accounts can have an expiry date
* CLI now support a proxy option
* new calendar graphical element to replace the old pop-up.
* new "last-update-date" field that allows you to know which artifacts were updated recently.
* display username according to user preferences (unix name, real name, etc.) in many places, including tracker fields and SVN search.
* provide a link to the new/edited artifact in the feedback box
* you may now select the default report in a tracker

- Other backend improvements:
* Simplified vhost and DNS management
* Packaging: Eclise, JRI and Salome provided as RPMs
* No longer support for CGI scripts in project web site for better security
* Support project web sites even if subdomains are not available (use /www/projname URL).

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


