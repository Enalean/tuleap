#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
# This file is licensed under the CodeX Component Software License
# http://codex.xerox.com
#
# 
#
# Purpose:
#    Automatically re-generate online documentation
#
MV='/bin/mv'
CP='/bin/cp'
LN='/bin/ln'
LS='/bin/ls'
RM='/bin/rm'
RMDIR='/bin/rmdir'
TAR='/bin/tar'
MKDIR='/bin/mkdir'
CHOWN='/bin/chown'
CHMOD='/bin/chmod'
FIND='/usr/bin/find'
TOUCH='/bin/touch'
CAT='/bin/cat'
GREP='/bin/grep'
ZIP='/usr/bin/zip'
CHCON='/usr/bin/chcon'
SED='/bin/sed'
DIRNAME='/usr/bin/dirname'
TR='/usr/bin/tr'
ECHO='/bin/echo'
WC='/usr/bin/wc'
PERL='/usr/bin/perl'

substitute() {
  # $1: filename, $2: string to match, $3: replacement string
  # Allow '/' is $3, so we need to double-escape the string
  replacement=`echo $3 | sed "s|/|\\\\\/|g"`
  $PERL -pi -e "s/$2/$replacement/g" $1
}

progname=$0
if [ -z "$scriptdir" ]; then 
    scriptdir=`$DIRNAME $progname`
    cd $scriptdir;
    # we need the complete path to the script directory, in order to call the doc generation
    TOP_SCRIPT_DIR=`pwd`;
    cd - > /dev/null; # redirect to /dev/null to remove display of folder (RHEL4 only)
fi

FORCE=0
HELP=0
VERBOSE=0
EXIST_CHANGE=0

CURRENTDIR=`pwd`
# honor BASEDOCDIR if defined
if [ -z "$BASEDOCDIR" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    CODEX_DOCUMENTATION_PREFIX=`$GREP '^\$codex_documentation_prefix' $CODEX_LOCAL_INC | $SED -e 's/\$codex_documentation_prefix\s*=\s*\(.*\);\(.*\)/\1/' | $TR -d '"' | $TR -d "'"`
    BASEDOCDIR=$CODEX_DOCUMENTATION_PREFIX
fi
CMDDOCDIR=$BASEDOCDIR/cli/cmd

# honor BASESRCDIR if defined
if [ -z "$BASESRCDIR" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    CODEX_DIR=`$GREP '^\$codex_dir' $CODEX_LOCAL_INC | $SED -e 's/\$codex_dir\s*=\s*\(.*\);\(.*\)/\1/' | $TR -d '"' | $TR -d "'"`
    BASESRCDIR=$CODEX_DIR/cli
fi

# honor TMPDIR if defined
if [ -z "$TMPDIR" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    TMP_DIR=`$GREP '^\$tmp_dir' $CODEX_LOCAL_INC | $SED -e 's/\$tmp_dir\s*=\s*\(.*\);\(.*\)/\1/' | $TR -d '"' | $TR -d "'"`
    TMPDIR=$TMP_DIR
fi

# honor sys_default_domain if defined
if [ -z "$sys_default_domain" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    sys_default_domain=`$GREP '^\$sys_default_domain' $CODEX_LOCAL_INC | $SED -e 's/\$sys_default_domain\s*=\s*\(.*\);\(.*\)/\1/' | $TR -d '"' | $TR -d "'"`
fi

# honor $sys_https_host if defined
if [ -z "$sys_https_host" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    sys_https_host=`$GREP '^\$sys_https_host' $CODEX_LOCAL_INC | $SED -e 's/\$sys_https_host\s*=\s*\(.*\);\(.*\)/\1/' | $TR -d '"' | $TR -d "'"`
fi

# honor archivename if defined
if [ -z "$archivename" ]; then
    cli_version=`$GREP '\$CLI_VERSION = ' $BASESRCDIR/codex.php | $SED -e 's/$CLI_VERSION = "\(.*\)";/\1/'`
    archivename="codex_cli-${cli_version}"
fi


# Check arguments
while	((1))	# look for options
do	case	"$1" in
    \-v*)	VERBOSE=1;;
    \-f*)	FORCE=1;;
    \-h*)	HELP=1;;
    \-d*)	DESTDIR=$2; shift;;
    *)	if [ ! -z "$1" ];
	    then
	        $ECHO "Invalid option $1";
	        $ECHO "Use -h flag to see all the valid options";
	        exit 2;
	    fi
	    break;;
	esac
	shift # next argument
done

# honor DESTDIR if defined
if [ -z "$DESTDIR" ]; then
    if [ -z "$CODEX_LOCAL_INC" ]; then
        CODEX_LOCAL_INC=/etc/codex/conf/local.inc
    fi
    DEST_DIR=`$GREP '^\$codex_downloads_dir' $CODEX_LOCAL_INC | $SED -e 's/\$codex_downloads_dir\s*=\s*\(.*\);\(.*\)/\1/' | $TR -d '"' | $TR -d "'"`
    DESTDIR=$DEST_DIR
fi


if [ $HELP == 1 ]
then
    $ECHO "Usage: generate_cli_package.sh [-f] [-v] [-h]";
    $ECHO "  -f : force to generate the package without checking file dates";
    $ECHO "  -v : verbose";
    $ECHO "  -d : target directory where the archive will be stored (optional, default is $DESTDIR)";
    $ECHO "  -h : help";
    exit 2;
fi

# Check if the package exists. If not, we force the generation
$MKDIR -p $DESTDIR
cd $DESTDIR
if [ ! -e $DESTDIR/$archivename.zip ]; then
    FORCE=1;
fi

if [ $FORCE != 1 ]
then
    # check if need some update with CLI source code (and nusoap symbolic link too)
    COUNT=`$FIND $BASESRCDIR -newer $DESTDIR/$archivename.zip | $WC -l`
    if [ $COUNT == 0 ]
    then
        # No changes in the CLI source code
        if [ $VERBOSE == 1 ]
        then
            $ECHO "No changes in the CLI source code";
        fi
    else 
        EXIST_CHANGE=1;
        if [ $VERBOSE == 1 ]
        then
            $ECHO "Changes found in the CLI source code";
        fi
    fi
fi

if [ $FORCE != 1 ]
then
    # check if need some update with CLI documentation
    COUNT=`$FIND $BASEDOCDIR/cli/xml -newer $DESTDIR/$archivename.zip | $WC -l`
    if [ $COUNT == 0 ]
    then
        # No changes in the CLI documentation
        if [ $VERBOSE == 1 ]
        then
            $ECHO "No changes in the CLI documentation";
        fi
    else 
        if [ $VERBOSE == 1 ]
        then
            $ECHO "Changes found in the documentation";
            $ECHO "Generating documentation";
            $TOP_SCRIPT_DIR/generate_cli_doc.sh -v -f
        else
            $TOP_SCRIPT_DIR/generate_cli_doc.sh -f
        fi
        EXIST_CHANGE=1;
    fi
else
    # force the documentation generation
    if [ $VERBOSE == 1 ]
    then
        $ECHO "Generating documentation";
        $TOP_SCRIPT_DIR/generate_cli_doc.sh -v -f
    else
        $TOP_SCRIPT_DIR/generate_cli_doc.sh -f
    fi
fi

# Check here there is no change and if we don't force, then we exit
if [ $EXIST_CHANGE != 1 ]
then
    if [ $FORCE != 1 ]
    then
        # No changes in the archive
        if [ $VERBOSE == 1 ]
        then
            $ECHO "No changes found in the files that compose the archive. Zip generation not necessary. Use -f to enforce the generation."
        fi
        exit 0
    fi
fi

# Use the tar command to make a complex copy :
# we copy the file contained in cli, documentation/cli/pdf, documentation/cli/html into $TMPDIR,
# excluding the files .svn (subversion admin files) and *_old (old pdf documentation)
(cd $CODEX_DIR; $TAR --exclude '.svn' --exclude "*_old.*" -h -cf - cli/ documentation/cli/pdf documentation/cli/html) | (cd $TMPDIR; $TAR xf -)
cd $TMPDIR
# We reorganize the files to fit the archive organization we want
$MV documentation/cli cli/documentation
# We remove documentation (empty now)
$RMDIR documentation
# Fix WSDL path 
if [ -n "$sys_https_host" ]; then
    wsdl_domain="https://$sys_https_host";
elif [ -n "$sys_https_host" ]; then
    wsdl_domain="http://$sys_default_domain";
else
    wsdl_domain='http://codex.xerox.com';
fi   
substitute "$TMPDIR/cli/codex.php" '%wsdl_domain%' "$wsdl_domain" 

# Rename the dir cli before creating the archive
$MV cli $archivename

# zip the CLI package
if [ $VERBOSE == 1 ]
then
    $ZIP -r "${archivename}_new.zip" $archivename
else
    $ZIP -q -r "${archivename}_new.zip" $archivename
fi

# Then permute the new archive with the former one
if [ -f "$DESTDIR/$archivename.zip" ]; then
    $CP -f $DESTDIR/$archivename.zip "$DESTDIR/${archivename}_old.zip" > /dev/null
fi
$MV "${archivename}_new.zip" $DESTDIR/$archivename.zip

if [ $? != 0 ]
then
    cd "$CURRENTDIR"
    $ECHO "CodeX CLI package generation failed!";
    exit 1
fi

if [ -f "$DESTDIR/CodeX_CLI.zip" ]; then
   $RM "$DESTDIR/CodeX_CLI.zip"
fi
$LN -s $archivename.zip $DESTDIR/CodeX_CLI.zip

# Fix SELinux context (it is set to 'user_u:object_r:tmp_t')
SELINUX_ENABLED=1
if [ ! -e $CHCON ] || [ ! -e "/etc/selinux/config" ] || `$GREP -i -q '^SELINUX=disabled' /etc/selinux/config`; then
   # SELinux not installed
   SELINUX_ENABLED=0
fi
if [ $SELINUX_ENABLED != 0 ]; then
  $CHCON -h  root:object_r:httpd_sys_content_t $DESTDIR/$archivename.zip
  $CHCON -h  root:object_r:httpd_sys_content_t $DESTDIR/CodeX_CLI.zip
fi

# Then delete the copied files needed to create the archive
$RM -r $archivename/*
$RMDIR $archivename

cd "$CURRENTDIR"
exit 0
