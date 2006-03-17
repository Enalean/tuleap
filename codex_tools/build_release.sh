#!/bin/sh
PACKAGE_DIR=/root/packages-rhel3
BUILD_DIR=/root/build_dir
ISO_LABEL="CodeX 2.8sup"
ISO_FILE="/tmp/codex-2.8sup.iso"

# Shell commands used
LS='/bin/ls'
CP='/bin/cp'
TAR='/bin/tar'
TAIL='/usr/bin/tail'
MKDIR='/bin/mkdir'
CHOWN='/bin/chown'
CHMOD='/bin/chmod'

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
rm -rf $BUILD_DIR; mkdir -p $BUILD_DIR

# Copy the install script at the top directory
echo "Copying the CodeX installation script..."
cd $PACKAGE_DIR
$CP -af $PACKAGE_DIR/CodeX/src/codex_tools/codex_install.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/codex_install.sh

# Copy the 2.6 to 2.8 migration script at the top directory
echo "Copying the CodeX 2.6 to 2.8 migration script..."
cd $PACKAGE_DIR
$CP -af $PACKAGE_DIR/CodeX/src/codex_tools/migration_28.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/migration_28.sh

# Copy the entire CodeX and nonRPMS_CodeX dir
echo "Copying the CodeX software and nonRPMS packages..."
$CP -af $PACKAGE_DIR/nonRPMS_CodeX $BUILD_DIR
$CP -af $PACKAGE_DIR/CodeX $BUILD_DIR

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
    $CP -af $newest_rpm $BUILD_DIR/RPMS_CodeX/$i
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
CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2006. All Rights Reserved
http://codex.xerox.com

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
CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2006. All Rights Reserved
http://codex.xerox.com

- login as root user
- cd into the directory where the codex_install.sh script is located
(probably /mnt/cdrom if you received the CodeX software on a CDROM)
- For a fresh CodeX installation run the installation script with ./codex_install.sh
- For an update from 2.6 to 2.8 run the migration script ./migration_28.sh 
- Follow the instructions of the migration script

-- The CodeX Team
   <info@codex.xerox.com>
EOF

# create a RELEASE_NOTES file at the top
cat <<'EOF' >RELEASE_NOTES
CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2006. All Rights Reserved
http://codex.xerox.com

This is CodeX 2.8.

After downloading the file, read the README and INSTALL files
carefully. And get in touch with us at codex-contact@codex.xerox.com
if you have questions.


Major improvements of CodeX 2.8 over 2.6:
- Field dependencies in trackers: Field dependencies allow you to link source field values to target field values. In other words, the values proposed to a final user for a field will depend upon the value selected for another field.
- New Reference system. With earlier versions of CodeX, it was possible to automatically create links in commit messages or artifact follow-ups by using certain patterns: 'commit #123' to reference a CVS commit, 'rev #234' for subversion, or 'art #246' for an artifact. This made a direct link to the object. With CodeX 2.8, it is now possible to reference any kind of object (documents, files, artifacts, revisions, external objects, etc.), and to customize the list of recognized patterns per project.
- new LDAP authentication mechanism. It is now provided as a plugin, which is a contribution from ST.
- Improved scalability and response time.
- User guide is now available in French
- Menus (e.g. 'admin') are only displayed if the user has enough permission to use them.
 

Other changes:
- Improved plugin architecture
- File download popup is not mandatory any longer (see local.inc)
- artifact status simplified: only 'open' and 'close' values allowed. Use the new 'stage' field to specify more info (new, analyzed, accepted, under implementation, etc.)
- Subversion permission file improved: you may now use ugroups in your permissions.
- CVS lockdir moved from /cvsroot/projectname/.lockdir to /var/lock/cvs/projectname
- updated SVN backup script
- allow anonymous access to wiki
- and many bugs fixed!

Package Update:
- Now install Subversion 1.2.3 (without BDB support) on new CodeX servers (do not upgrade existing servers)

-- The CodeX Team
   <info@codex.xerox.com>
EOF

# Build ISO image
echo "Building ISO image in $ISO_FILE..."
mkisofs -A "$ISO_LABEL" -V "$ISO_LABEL" -J -R -x ./lost+found -o "$ISO_FILE" $BUILD_DIR

echo "CodeX ISO image available at $ISO_FILE..."
echo "Done!"
exit 0
# end of it


