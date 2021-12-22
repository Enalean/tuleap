#
# Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
#
# Purpose:
#    This Python file fetches the SVN access file and determine whether
#    a given user has access to a given path in the repository
#

def check_read_access(username, svnrepo, svnpath):
    # make sure that usernames are lowercase
    username = username.lower()
    return __check_read_access_with_epel_viewvc(username, svnrepo, svnpath)

def __check_read_access_with_epel_viewvc(username, svnrepo, svnpath):
    from vcauth.svnauthz import ViewVCAuthorizer
    root_lookup_func = lambda _: 'svn', svnrepo
    authorizer = ViewVCAuthorizer(root_lookup_func, username, {'authzfile' : svnrepo + '/.SVNAccessFile'})
    requested_path_parts = filter(None, svnpath.split('/'))
    return authorizer.check_path_access(svnrepo, requested_path_parts, None)
