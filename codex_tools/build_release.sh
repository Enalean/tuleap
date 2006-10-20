#!/bin/sh
PACKAGE_DIR=/root/packages-rhel4
BUILD_DIR=/root/build_dir
ISO_LABEL="CodeX 3.0 sup"
ISO_FILE="/tmp/codex-3.0.sup.iso"

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
$RM codex_install.sh INSTALL migration_*.sh README  RELEASE_NOTES

# Copy the install script at the top directory
echo "Copying the CodeX installation script..."
cd $PACKAGE_DIR
$CP -af $PACKAGE_DIR/CodeX/src/codex_tools/codex_install.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/codex_install.sh

# Copy the migration script at the top directory
echo "Copying the CodeX migration script..."
cd $PACKAGE_DIR
$CP -af $PACKAGE_DIR/CodeX/src/codex_tools/migration_30.sh $BUILD_DIR
$CHMOD +x $BUILD_DIR/migration_30.sh

# Copy the entire CodeX and nonRPMS_CodeX dir
echo "Copying the CodeX software and nonRPMS packages..."
$RSYNC -av --delete $PACKAGE_DIR/nonRPMS_CodeX $BUILD_DIR
mkdir -p $BUILD_DIR/CodeX
$RSYNC -a --delete $PACKAGE_DIR/CodeX/src $BUILD_DIR/CodeX

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
    $RSYNC -a --delete $newest_rpm $BUILD_DIR/RPMS_CodeX/$i
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
- For an update from 2.8 to 3.0 please read carefully migration_30.README and follow the instructions.

-- The CodeX Team
   <info@codex.xerox.com>
EOF

# create a RELEASE_NOTES file at the top
cat <<'EOF' >RELEASE_NOTES
CodeX: Breaking Down the Barriers to Source Code Sharing
Copyright (c) Xerox Corporation, CodeX/CodeX Team, 2001-2006. All Rights Reserved
http://codex.xrce.xerox.com

This is CodeX 3.0.

After downloading the file, read the README and INSTALL files
carefully. And get in touch with us at codex-contact@codex.xerox.com
if you have questions.


What's new in CodeX 3.0?

- New Document Manager
  The CodeX document manager has been completely rewritten. Now, it:
  - can host an "unlimited" hierarchy of documents
  - accepts files, wiki pages, URLs and embedded documents
  - allows deletion, move
  - enforces read/write/manage permissions, 
  - allows document versioning and history access
  - is more scalable
  - has a nice javascript-based UI with multiple views
  While new CodeX projects will immediately benefit from the new document
  manager, existing projects will need to activate the tool in the project 
  service administration page. Existing projects that had not used the 
  legacy document manager now automatically have access to the new one.

- SOAP API foundation in CodeX.
  CodeX now offers SOAP API for programmatical access to your project data.
  For the moment, only tracker access is available through this API.

- Command-line client for CodeX
  CodeX provides a command line script that allows project members to
  consult or update tracker data. The tool and its documentation is available
  from the CodeX welcome page.

- An experimental server update mechanism is now provided to CodeX administrators
  to help manage CodeX updates.

- Tracker improvement: you may now group tracker fields in 'field sets', that
  simplify tracker management and improve readability for complex trackers.
  
- Project templates: you may now create project templates with a specific configuration
  (trackers, user groups, services, etc.). 
  A project created with a specific template will inherit its configuration.

- Platform update: CodeX now runs on Red Hat Enterprise Linux 4 and benefits
  from many updated packages and improved security.

Other changes:
- survey manager has been improved
- project news may now be private, i.e. only visible to project members
- tracker fields may now be bound to multiple user groups
- and many bugs fixed!

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


