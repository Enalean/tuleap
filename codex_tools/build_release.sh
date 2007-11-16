#!/bin/sh
CX_VERSION='support/CX_3_4_SUP'
CX_SHORT_VERSION='3.4.sup'
#CX_VERSION='dev/trunk'
#CX_SHORT_VERSION='trunk'
PACKAGE_DIR=/root/CodeX_Packaging/code.xrce/$CX_VERSION/packages-rhel4
SOURCE_DIR=/root/CodeX_Packaging/src/$CX_VERSION
BUILD_DIR=/root/CodeX_Packaging/temp_dir/$CX_VERSION
echo "Building ISO image for version: $CX_VERSION"
yn="0"
read -p "Update source and package working copies? [y|n]:" yn
if [ "$yn" = "y" ]; then
  echo `cd $SOURCE_DIR; svn update`
  echo `cd $PACKAGE_DIR; svn update`
fi
CX_REVISION=`svn info $SOURCE_DIR | grep Revision | sed 's/Revision: //'`
ISO_LABEL="CodeX $CX_SHORT_VERSION"
ISO_FILE="/root/CodeX_Packaging/iso_images/codex-$CX_SHORT_VERSION.r$CX_REVISION.iso"

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
$RM codex_install.sh INSTALL migration_* README  RELEASE_NOTES

# Copy the install script at the top directory
echo "Copying the CodeX installation script..."
cd $PACKAGE_DIR
$CP -af $SOURCE_DIR/codex_tools/codex_install.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/codex_install.sh

# Copy the migration script at the top directory
echo "Copying the CodeX migration script..."
cd $PACKAGE_DIR
$CP -af $SOURCE_DIR/codex_tools/migration_from_CodeX_3.2_to_CodeX_3.4.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/migration_from_CodeX_3.2_to_CodeX_3.4.sh

# Copy the entire CodeX and nonRPMS_CodeX dir
echo "Copying the CodeX software and nonRPMS packages..."
$RSYNC -a --exclude='.svn' --delete $PACKAGE_DIR/nonRPMS_CodeX $BUILD_DIR
mkdir -p $BUILD_DIR/CodeX
BRANCH_NAME=`echo $SOURCE_DIR|sed 's/.*\///'`
if [ -e $BUILD_DIR/CodeX/src ]; then
  $MV $BUILD_DIR/CodeX/src $BUILD_DIR/CodeX/$BRANCH_NAME
fi
$RSYNC -a --delete $SOURCE_DIR $BUILD_DIR/CodeX
$MV $BUILD_DIR/CodeX/$BRANCH_NAME $BUILD_DIR/CodeX/src
# Only copy the latest RPMs from RPMS CodeX
echo "Copying the CodeX RPMS packages..."
$MKDIR -p $BUILD_DIR/RPMS_CodeX
cd $PACKAGE_DIR/RPMS_CodeX
RPM_LIST=`ls -1`
for i in $RPM_LIST
do
    cd $PACKAGE_DIR/RPMS_CodeX/$i
    newest_rpm=`$LS -1 -I old | $TAIL -1`
    $MKDIR -p $BUILD_DIR/RPMS_CodeX/$i
    $RSYNC -a --exclude='.svn' --delete $newest_rpm $BUILD_DIR/RPMS_CodeX/$i
    cd $BUILD_DIR/RPMS_CodeX/$i
    old_rpms=`$LS -1 | $GREP -v $newest_rpm`
    for j in $old_rpms
    do
      echo "deleting $i/$j from build dir"
      $RM -rf $j
    done
done

# Remove deprecated packages
cd $BUILD_DIR/RPMS_CodeX 
RPM_LIST=`ls -1`
for i in $RPM_LIST
do
    if [ ! -e $PACKAGE_DIR/RPMS_CodeX/$i ];
    then
        echo "Removing deprecated package: $i"
        echo $RM -rf $BUILD_DIR/RPMS_CodeX/$i
    fi
done

# Change ownership of everything
echo "Changing ownership to root.root for everything..."
$CHOWN -R root.root $BUILD_DIR/*

# delete codex_tools directory in BUILD_DIR
echo "Deleting codex_tools directory..."
cd $BUILD_DIR/CodeX/src
rm -rf codex_tools

# create the tar file of CodeX sources
echo "Creating tar file of CodeX sources..."
cd $BUILD_DIR/CodeX/src
$TAR cfz ../codex.tgz .
$CHOWN root.root ../codex.tgz

# create a README file at the top
cd $BUILD_DIR
cat <<'EOF' >README
CodeX: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2007. All Rights Reserved
http://codex.xrce.xerox.com

The CodeX software From Xerox aims at providing large companies with a
easy to use, cost effective and scalable platform to make global software
sharing and reuse a reality.

CodeX provides all project development teams with a series of tools that
are needed on a daily basis to produce good software (defect/task/requirements
tracking, version control, access permission, software release mechanisms,
communication channels,etc.)

Beside that CodeX also provide managers as well as all other authorized
visitors with a global view on all on-going software development activities.
Using CodeX you can maximize chances of reuse since sharing becomes completely
painless.

License
-------
CodeX is originally based on SourceForge 2.0 and for the most part the numerous
enhancements brought to the original software are under the GNU General Public
License (GPL).

Some portion of the CodeX software are however under the CodeX Component
Software License and can only be used with a commercial license of CodeX.

Contact
-------
If you want to know more about CodeX or if you have questions send an email
to info@codex.xerox.com

Support Requests
----------------
CodeX users inside of the Xerox Network must submit their support requests
through the CodeX central site at:
http://codex.xerox.com/tracker/?func=add&group_id=1&atid=922 

CodeX customers outside of Xerox must submit their support requests through
the external CodeX support site at:
https://partners.xrce.xerox.com/tracker/?func=add&group_id=120&atid=199

-- The CodeX Team
   <info@codex.xerox.com>

EOF

# create a INSTALL file at the top
cat <<'EOF' >INSTALL
CodeX: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2007. All Rights Reserved
http://codex.xrce.xerox.com

- login as root user
- cd into the directory where the codex_install.sh script is located
(probably /mnt/cdrom if you received the CodeX software on a CDROM)
- For a fresh CodeX installation run the installation script with ./codex_install.sh
- For an update from 3.2 please run migration_from_CodeX_3.2_to_CodeX_3.4.sh.
- For an update from a prior release, please update to CodeX 3.2 first.

-- The CodeX Team
   <info@codex.xerox.com>
EOF

# create a RELEASE_NOTES file at the top
cat <<'EOF' >RELEASE_NOTES
CodeX: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2007. All Rights Reserved
http://codex.xrce.xerox.com

This is CodeX 3.4.

Please read the README and INSTALL files carefully, and get in touch with us
at codex-contact@codex.xerox.com if you have questions.


What's new in CodeX 3.4?

- Eclipse Plugin: 
  CodeX users may now access their tasks and bugs directly from Eclipse!
  An Eclipse plugin is provided with CodeX 3.4. It allows full access to
  CodeX trackers: you may list, filter, edit and submit artifacts directly
  from the Eclipse IDE.
  This is implemented over the CodeX SOAP interface.


- Customize your personnal page and the project summary page with CodeX widgets.
  You may now select what information you want to display on your page, organize it,
  and even import external information through the new RSS reader.
  Project administrators may also customize the project summary page to display
  relevant information about the project.

- Artifact Edition improvement.
  The user interface of the artifact edition page has been improved. You may
  now edit, delete, point to, quote and sort follow-up comments. You can also
  fold/unfold the artifact details appropriately.
  

- CodeX Java Runtime Interface.
  Java developpers may now build CodeX extensions and tools with the CodeX JRI.
  The CodeX JRI is a java layer over the SOAP API that provides Java classes 
  to interact with the CodeX server. JARS and Javadoc are available.


- Many other improvements
  * SOAP API extended (mostly for tracker interaction)
  * New document manager features (obsolete documents, metadata, search and filtering)
  * various security fixes: password robustness tool, XSS fixes, htmlpurifier integration
  * template mechanism extended to all services
  * test projects
  * tracker: temporarily suspend email notifications
  * tracker: prevent fortuitous deletion of artifact data during concurrent update.
  * performance improvements: new_parse script, DB indexes
  * File packages may now be deleted
  * access logs export
  * Subversion upgraded to version 1.4.4
and many more smaller additions and fixes.

Enjoy CodeX!

-- The CodeX Team
   <info@codex.xerox.com>
EOF

# Build ISO image
echo "Building ISO image in $ISO_FILE..."
mkisofs -quiet -A "$ISO_LABEL" -V "$ISO_LABEL" -J -R -x ./lost+found -x .. -o "$ISO_FILE" $BUILD_DIR

echo "CodeX ISO image available at $ISO_FILE ..."
echo "Done!"
exit 0
# end of it


