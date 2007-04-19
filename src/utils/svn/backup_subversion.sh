#!/bin/sh

set -e

# $Id$
#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX, 2001-2005. All Rights Reserved
# http://codex.xerox.com
# Adapted from scripts by Scott Lawrence (slawrence_at_pingtel.com)and Vicky Tuttle (Xerox)
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#

#
# Do backups of all CodeX subversion repositories
#
#
# This script provides both full backups and incremental backups.
# Incremental backups are recorded from the latest dumped revision 
# (either from a full or incremental backup).
# It is compatible with both BDB and fsfs repositories.
#
# How to restore a backup:
# 1- restore latest full backup: 
#    simply copy ${SvnFullBackupPath}/current/projectname-rev1 as ${SvnParentPath}/projectname
#    please note the number rev1.
# 2- restore incremental dumps made after the full backup:
#    cd ${SvnIncrBackupPath}/projectname
#    bunzip2 < projectname.incr.rev2:rev3.bz2 | svnadmin load ${SvnParentPath}/projectname 
#    bunzip2 < projectname.incr.rev4:rev5.bz2 | svnadmin load ${SvnParentPath}/projectname 
#    ...
#
################################################################
# configuration variables

# SvnParentPath is the directory where project repositories are found
#SvnParentPath=/var/lib/codex/svnroot
SvnParentPath=/svnroot # We can use the symbolic link

SvnBackupPath=/var/lib/codex/backup/subversion
# SvnIncrBackupPath is the directory where incremental backups are created
SvnIncrBackupPath=${SvnBackupPath}/incr
# SvnFullBackupPath is the directory where full backups are created
SvnFullBackupPath=${SvnBackupPath}/full
# SvnOldBackupPath is the directory where old backups are moved
SvnOldBackupPath=${SvnBackupPath}/old

# LogFacility - what facility name to write log messages with (default='cron' -> /var/log/cron)
LogFacility=cron
# LogTag - a tag to prepend to log messages
LogTag=SVN

# What umask to use when creating backups
Umask=027

LS=/bin/ls
RM=/bin/rm
MKDIR=/bin/mkdir
SVNADMIN=/usr/bin/svnadmin
SVNLOOK=/usr/bin/svnlook
HOTCOPY=/usr/lib/subversion/tools/backup/hot-backup.py
PERL=/usr/bin/perl
OLPERL="${PERL} -ne"

# end of configuration variables
################################################################

# if INCREMENTAL=1 -> incremental backup. Else: full backup.
INCREMENTAL=0
HELP=0
VERBOSE=0
# if NOOLD=1 -> Old full backup is deleted instread of staying
# in a dedicated directory.
NOOLD=0

# Check arguments
while   ((1))   # look for options
do      case    "$1" in
        \-v)   VERBOSE=1;;
        \-i)   INCREMENTAL=1;;
        \-h)   HELP=1;;
        \-noarchives)   NOOLD=1;;
        *)      if [ ! -z "$1" ];
            then
                echo "Invalid option $1";
                HELP=1;
            fi
            break;;
        esac
        shift # next argument
done

if [ "$HELP" == 1 ]
then
    echo "Usage: backup_subversion.sh [-i] [-v] [-h]";
    echo "  -i : do an incremental backup instead of a full backup";
    echo "  -v : verbose";
    echo "  -h : help";
    echo "  -noarchives: delete all previous backups (full and incremental)";
    echo "               only used with full backup";
    exit 2;
fi

if [ "$VERBOSE" == 1 ]
then
    # Output to stderr
    LOGCMD="logger -s -t ${LogTag}"
else
    LOGCMD="logger -t ${LogTag}"
fi


######

Start=`date +%s`

umask ${Umask}


if [ "${INCREMENTAL}" -ne 1 ]
then

  ################################################################
  ###
  ### FULL BACKUP
  ###

  if [ "$VERBOSE" == 1 ]
  then
    ${LOGCMD} -p ${LogFacility}.info "Starting full backup of Subversion repositories"
  fi

  ${MKDIR} -p ${SvnFullBackupPath}
  cd ${SvnFullBackupPath}
  ${LS} -1d ${SvnParentPath}/* > subversion.lis

  DATE=`date +%Y%m%d:%H%M`
  OldBackupPath=${SvnOldBackupPath}/${DATE}
  OldBackupPathFull=${OldBackupPath}/full
  OldBackupPathIncr=${OldBackupPath}/incr
  ${MKDIR} -p ${OldBackupPathFull}
  ${MKDIR} -p ${OldBackupPathIncr}
  ${MKDIR} -p current

  ## Archive previous backup
  # Move current full backup into the archive
  /bin/mv current/* ${OldBackupPathFull} || true
  
  # Move current incremental backup into the archive
  /bin/mv ${SvnIncrBackupPath}/* ${OldBackupPathIncr} || true

  
  ## Create new full backup

  for directory in `cat subversion.lis` ; do
    # don't backup empty repositories
    HEAD=`${SVNLOOK} youngest ${directory}`
    if [ "${HEAD}" -ne 0 ]
    then
        ${HOTCOPY} $directory current \
         | ${LOGCMD} -p ${LogFacility}.info
    fi
  done

  # If there is no archives keeping, delete old full backup
  # and previous incermental backup
  if [ "${NOOLD}" == 1 ]; then
      ${RM} -rf ${OldBackupPath}
  fi

else

  ################################################################
  ###
  ### INCREMENTAL  BACKUP
  ###

  if [ "$VERBOSE" == 1 ]
  then
    ${LOGCMD} -p ${LogFacility}.info "Starting incremental backup of Subversion repositories"
  fi

  cd ${SvnParentPath} || exit 1;

  for repo in `${LS} ${SvnParentPath}`
  do
   if [ -d "${repo}" ] && [ -f "${repo}/format" ]
   then
    BAK=${SvnIncrBackupPath}/${repo}    
    INCR=${BAK}/INCR.latest
    if [ -f "${INCR}" ]
    then
        LAST=`cat ${INCR}`
    else
        LAST=0
    fi

    # Last revision of full backup
    for lstfullbkp in $(${LS} -d ${SvnFullBackupPath}/current/${repo}-* 2>/dev/null); do	
	LASTFULL=$(basename ${lstfullbkp} | ${OLPERL} "print if s/^${repo}-([0-9]+)$/\1/;")
	if [ "${LASTFULL}" != "" ]; then
	    break
	fi
    done
    if [ "${LASTFULL}" == "" ]; then
	LASTFULL=0
    fi

    if [ -d "${SvnFullBackupPath}/current/${repo}-${LASTFULL}" ]
    then     
      if [ "${LAST}" -le "${LASTFULL}" ]
      then
        if [ "${LAST}" -ne 0 ]
        then
	    # A full backup is more recent than the latest incremental
          # remove previous incremental backups
          ${RM} -rf ${BAK}/*
        fi
        LAST=${LASTFULL}
      fi
    fi

    HEAD=`${SVNLOOK} youngest ${repo}`

    if [ "${LAST}" -lt "${HEAD}" ]
    then
      RANGE="$((${LAST} + 1)):${HEAD}"
      if [ "${LAST}" -ne 0 ]
      then
        type=--incremental
      else
        type=
      fi
      ${MKDIR} -p ${BAK}
      if ${SVNADMIN} dump --quiet ${type} -r ${RANGE} ${repo} \
          | bzip2 -c \
          > ${BAK}/${repo}.incr.${RANGE}.bz2
      then
        if echo ${HEAD} > ${INCR}
        then
          ${LOGCMD} -p ${LogFacility}.info \
              "Incremental backup of ${repo} ${RANGE}"
        else
          ${LOGCMD} -p ${LogFacility}.err \
              "Incremental backup - error recording ${INCR}"
        fi
      else
	  ${RM} ${BAK}/${repo}.incr.${RANGE}.bz2
        ${LOGCMD} -p ${LogFacility}.err \
            "Incremental backup failed for repository '${repo}' ${RANGE}"
      fi
    fi
   else
    ${LOGCMD} -p ${LogFacility}.warn \
        "Not a subversion repository: ${SvnParentPath}/${repo}"
   fi
  done
fi

Stop=`date +%s`

Seconds=`echo ${Stop} ${Start} - p | dc`

${LOGCMD} -p ${LogFacility}.info \
    "Backups completed in ${Seconds} seconds"


