# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
#
# This file is a part of Codendi.
#
# Codendi is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Codendi is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Codendi. If not, see <http://www.gnu.org/licenses/>.
#

#
# Purpose:
#    This Perl include file contains function to enable or disable cvs watch on a given module

sub cvs_watch {
    local($repository, $temp_name, $id, $watch_mode) = @_;
    $TMPDIR = $cvs_hook_tmp_dir;
    $DIR_NAME = sprintf ("$TMPDIR/#%s.sandbox", $temp_name);
    if($watch_mode eq '1'){
        system("mkdir $DIR_NAME.$id; cd $DIR_NAME.$id;cvs -d/$repository co . 2>/dev/null 1>&2;cvs -d/$repository watch on 2>/dev/null 1>&2;");
    }else{
        system("mkdir $DIR_NAME.$id;cd $DIR_NAME.$id;cvs -d/$repository co . 2>/dev/null 1>&2;cvs -d/$repository watch off 2>/dev/null 1>&2;");
    }
    system("cd $TEMPDIR;rm -rf $DIR_NAME.$id;");
    
}
1;

