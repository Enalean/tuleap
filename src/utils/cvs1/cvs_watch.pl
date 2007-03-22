# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
# http://codex.xerox.com
#
# $Id: cvs_watch.pl 3524 2006-11-22 hardyau $
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    This Perl include file contains function to enable or disable cvs watch on a given module

sub cvs_watch {
    local($repository, $temp_name, $module, $id, $watch_mode) = @_;
    $TMPDIR = "/var/run/log_accum";
    $DIR_NAME = sprintf ("$TMPDIR/#%s.sandbox", $temp_name);
    if($watch_mode eq '1'){
        system("mkdir $DIR_NAME.$id;cd $DIR_NAME.$id;cvs -d/$repository co $module 2>/dev/null 1>&2;cd $module; cvs -d/$repository watch on 2>/dev/null 1>&2;");
    }else{
        system("mkdir $DIR_NAME.$id;cd $DIR_NAME.$id;cvs -d/$repository co $module 2>/dev/null 1>&2;cd $module; cvs -d/$repository watch off 2>/dev/null 1>&2;");
    }
    system("cd $TEMPDIR;rm -rf $DIR_NAME.$id;");
    
}
1;

