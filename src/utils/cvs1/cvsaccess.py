#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    This Python file determine whether a given user has access to the CVS
#    repository of a given project. Any directory that is not world readable
#    can only be viewed by project members.
#

import os
import stat
import re
import sys
import string
import group
import user



def check_read_access(username, cvsrepo, cvspath):

    # extract project unix name (group name) and fetch
    # the group information from DB
    path_elements = string.split(cvsrepo,'/')
    group_name = path_elements[len(path_elements)-1]
    group_id = group.set_group_info_from_name(group_name)

    # if the file path exists as such then it's a directory
    # else add the ,v extension because it's a file
    path = cvsrepo+'/'+cvspath
    if not os.path.exists(path):
        path = path+',v'

    mode = os.stat(path)[stat.ST_MODE]
    mode_repo = os.stat(cvsrepo)[stat.ST_MODE]

    #print "Content-type: text/html\n"
    #print user.user_is_member(group_id, '0')

    # A directory that is not world readable can only be viewed
    # through viewvc if the user is a project member
    # Since .CODEX_PRIVATE only removes read access on top directory,
    # we need to also check it.
    if group_id and ((mode & stat.S_IROTH) == 0 or (mode_repo & stat.S_IROTH) == 0) and not user.user_is_member(group_id, '0'):
        return False
    else:
        return True
