#!/bin/sh
CX_VERSION='support/CX_3_2_SUP'
CX_SHORT_VERSION='3.2'
PACKAGE_DIR=/root/CodeX_Packaging/code.xrce/$CX_VERSION/packages-rhel4
SOURCE_DIR=/root/CodeX_Packaging/src/$CX_VERSION
BUILD_DIR=/root/CodeX_Packaging/build_dir
echo `cd $SOURCE_DIR; svn update`
CX_REVISION=`svn info $SOURCE_DIR | grep Revision | sed 's/Revision: //'`
ISO_LABEL="CodeX $CX_SHORT_VERSION sup"
ISO_FILE="/tmp/codex-$CX_SHORT_VERSION.sup.r$CX_REVISION.iso"

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
$CP -af $SOURCE_DIR/codex_tools/migration_from_CodeX_3.0_to_CodeX_3.0.1.sh $SOURCE_DIR/codex_tools/migration_from_CodeX_3.0.1_to_CodeX_3.2.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/migration_from_CodeX_3.0_to_CodeX_3.0.1.sh
$CHMOD +x $BUILD_DIR/migration_from_CodeX_3.0.1_to_CodeX_3.2.sh

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
        echo "Removing depracted package: $i"
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
- For an update from 3.0.1 please run migration_from_CodeX_3.0.1_to_CodeX_3.2.sh.
- For an update from 3.0 please run migration_from_CodeX_3.0_to_CodeX_3.0.1.sh first to upgrade to 3.0.1, then run migration_from_CodeX_3.0.1_to_CodeX_3.2.sh.

-- The CodeX Team
   <info@codex.xerox.com>
EOF

# create a RELEASE_NOTES file at the top
cat <<'EOF' >RELEASE_NOTES
CodeX: The Xerox Solution to Maximize the Value of Your Corporate Software Assets
Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2007. All Rights Reserved
http://codex.xrce.xerox.com

This is CodeX 3.2.

Please read the README and INSTALL files carefully, and get in touch with us
at codex-contact@codex.xerox.com if you have questions.


What's new in CodeX 3.2?

- Distributed services: 
  CodeX now supports a distributed architecture that allows some services (files, Subversion)
  to be located on satellite servers. 
  Please read the administration guide for more details on how to setup a 
  distributed architecture.
  Also note that this feature is still experimental.

- Improved Services:
  - The interface to the File Release Manager has been rewritten to offer a
    simpler, faster and more convenient experience.
  - The Document Manager has been upgraded with new features, like the
    ability to clone folders (within a project and across projects), the 'empty'
    document type, etc.

- Web service API and Command Line Client improved.
  You may now use the CLI or the SOAP interface to access the tracker service, 
  the document manager and the file manager.

- The server update mechanism has been improved. 
  This tool is intended for CodeX administrators to help them manage CodeX updates.
  
- Project templates have been improved.
  The template mechanism now supports the document manager, forums, CVS/SVN, in 
  addition to trackers and services.

Other changes:
- the project creation interface has been improved
- new roles: Subversion admin and News admin
- support for CVS watch mode through the CodeX interface.
- embed external services in CodeX pages (service configuration)
- permissions on news.
- news promotion mechanism updated
- Subversion upgraded to version 1.4.3

and many more smaller additions and fixes.

Enjoy CodeX!

-- The CodeX Team
   <info@codex.xerox.com>
EOF

# Build ISO image
echo "Building ISO image in $ISO_FILE..."
mkisofs -A "$ISO_LABEL" -V "$ISO_LABEL" -J -R -x ./lost+found -o "$ISO_FILE" $BUILD_DIR

echo "CodeX ISO image available at $ISO_FILE ..."
echo "Done!"
exit 0
# end of it


