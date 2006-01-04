#!/bin/sh
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2005. All Rights Reserved
# http://codex.xerox.com
# Originally written by Patrice Karatchentzeff (for France Telecom) and Manuel Vacelet (ST Microelectronics)
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
#
# This script automates Subversion repository conversion from BDB to FSFS 
#
# ex:
# Check one repository: 
#  ./bdb2fsfs.sh --verbose --identify --repository==/svnroot/project
# Convert all svn repositories:
#  ./bdb2fsfs.sh --verbose --convert --svnroot=/svnroot
#
debug=
set -e${debug}

function error {
    set -e${debug}

    echo "*** error: $@"
    exit 1
}

function check_fstype {
    set -e${debug}

    svnrepository=$1

    if [ -f "${svnrepository}/format" ]; then
	# Check if it's fsfs or not
	if [ -f "${svnrepository}/db/fs-type" ]; then
	    fstype=$(cat ${svnrepository}/db/fs-type)
        else
            fstype="bdb"
	fi
    
	if [ "${fstype}" != "fsfs" ]; then
	    echo "${svnrepository}: BDB"
	else
	    echo "${svnrepository}: FSFS"
	fi
    else
        echo "${svnrepository} does not exist or is not a Subversion repository"
    fi
}

function bkp_repo {
    set -e${debug}
    
    root=$1
    bkpdir=$2

    ${MKDIR} -p ${bkpdir}
    ${CP} -arp  ${root}/hooks ${bkpdir}
    ${CP} -ap  ${root}/.SVNAccessFile.bck ${bkpdir}/.SVNAccessFile
}

function dump_repo {
    set -e${debug}

    root=$1
    dumpfile=$2

    ${MKDIR} -p $(dirname ${dumpfile})
    
    ${SVNADMIN} dump ${root} > ${dumpfile}
}

function create_repo {
    set -e${debug}

    root=$1

    ${SVNADMIN} create --fs-type=fsfs ${root}
    cat <<EOF > ${root}/.SVNAccessFile
# Temp access rights during update
[/]
*=
EOF
}

function restore_repo {
    set -e${debug}

    root=$1
    dumpfile=$2

    ${SVNADMIN} load ${root} < ${dumpfile}
}

function restore_prefs {
    set -e${debug}
    
    root=$1
    bkpdir=$2

    ${CP} -arp  ${bkpdir}/hooks/* ${root}/hooks
    ${CP} -ap  ${bkpdir}/.SVNAccessFile ${root}
}

function set_access_rights {
    set -e${debug}

    root=$1
    repo=$2

    ${CHOWN} -R sourceforge:${repo} ${root}
}

function lock_repo {
    set -e${debug}

    repo=$1
    
    $CP ${repo}/.SVNAccessFile ${repo}/.SVNAccessFile.bck &&
    cat << EOF > ${repo}/.SVNAccessFile
[/]
*=
EOF
}

function convert {
  set -e${debug}

  svnrepository=$1

  reponame=$(basename ${svnrepository})

  # Check it's a svn repository
  if [ -f "$svnrepository/format" ]; then
    # Check if it's fsfs or not
    if [ -f "$svnrepository/db/fs-type" ]; then
	fstype=$(cat $svnrepository/db/fs-type)
    else
        fstype="bdb"
    fi
    
    if [ "${fstype}" != "fsfs" ]; then	    
	last=$(${SVNLOOK} youngest ${svnrepository})
	
	if [ "${last}" = "0" ]; then
	    echo -n "Convert empty repository ${svnrepository}... "
	else
	    echo "Convert used repository ${svnrepository}"
	fi

	# lock access
	lock_repo ${svnrepository}
	bkp_repo ${svnrepository} ${BKPDIR}/${reponame}

	# Dump
	dump_repo ${svnrepository} ${BKPDIR}/${reponame}/${reponame}.dump
	
	${RM} -rf ${svnrepository} &&
	create_repo ${svnrepository}

	# Reload
	restore_repo  ${svnrepository} ${BKPDIR}/${reponame}/${reponame}.dump

	restore_prefs ${svnrepository} ${BKPDIR}/${reponame}
	set_access_rights ${svnrepository} ${reponame}

	echo "Done"
    else 
	if [ "${verbose}" == "true" ]; then
	    echo "Already fsfs (${svnrepository})"
	fi
    fi
  else
      echo "${svnrepository} does not exist or is not a Subversion repository"
  fi
}


BKPDIR=/home/backup/__tmp_svn__backup__

LS=/bin/ls
CP=/bin/cp
RM=/bin/rm
MKDIR=/bin/mkdir
CHOWN=/bin/chown
ID=/usr/bin/id
SVN=/usr/bin/svn
SVNADMIN=/usr/bin/svnadmin
SVNLOOK=/usr/bin/svnlook

force="false"
action="check"
svnrepository="false"
svnroot="false"

while [ "$1" != "" ]; do
    case $1 in
	--repository=*)
	    svnrepository=$(echo $1 | sed -e 's/--repository=//')
	    ;;
	--svnroot=*)
	    svnroot=$(echo $1 | sed -e 's/--svnroot=//')
	    ;;
	--force)
	    force="true"
	    ;;
	-v|--verbose)
	    verbose="true"
	    ;;
	-i|--identify)
	    action="check"
	    ;;
	--convert)
	    action="convert"
	    ;;
	*)
	    svnrepository=$1
	    ;;
    esac
    shift
done

if [ ${svnrepository} == "false" ] && [ ${svnroot} == "false" ]; then
   echo Usage: $0 [--force] [--verbose] [--identify or --convert] [--svnroot=path or --repository=path]
   exit 1
fi

case $action in
    check)
        if [ ${svnrepository} != "false" ];
        then
            check_fstype ${svnrepository}
        else 
            for repo in `${LS} ${svnroot}`
            do
                check_fstype ${svnroot}/${repo}
            done
        fi
	;;

    convert)

        uid=$(${ID} -u)
        if [ "${uid}" -ne 0 ]; then
            if [ "${force}" != "true" ]; then
                error "You have to be root to launch this command"
            fi
        fi
        if [ ${svnrepository} != "false" ];
        then
            convert ${svnrepository}
        else 
            for repo in `${LS} ${svnroot}`
            do
                convert ${svnroot}/${repo}
            done
        fi
        echo "You may now remove the temporary backup in $BKPDIR"

;;
    *)
	error "undefined action: $action"
;;
esac
