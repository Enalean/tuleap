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

    # if file was removed, allow access anyway.
    if not os.path.exists(path):
        return True

    mode = os.stat(path)[stat.ST_MODE]
    mode_repo = os.stat(cvsrepo)[stat.ST_MODE]

    #print "Content-type: text/html\n"
    #print user.user_is_member(group_id, '0')

    # A directory that is not world readable can only be viewed
    # through viewvc if the user is a project member
    # Since we only removes read access on top directory,
    # we need to also check it.
    if group_id and ((mode & stat.S_IROTH) == 0 or (mode_repo & stat.S_IROTH) == 0) and not user.user_is_member(group_id, '0'):
        return False
    else:
        return True
