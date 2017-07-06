#
# Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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
# Warning:
#    Some of this code source is writing in PHP too.
#    If you modify part of this code, thanks to check if
#    the corresponding PHP code needs to be updated too.
#    (see src/www/svn/svn_utils.php)
#

import string
import user
import group
import MySQLdb
import include

# Check if ldap plugin is installed and available
def ldap_plugin_is_enabled():
    cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
    res    = cursor.execute('SELECT NULL'+ 
                            ' FROM plugin'+
                            ' WHERE name="ldap"'+
                            ' AND available=1')
    return (cursor.rowcount == 1)

# Specific to LDAP plugin: if the current repository is handled by LDAP
# authentication we must check user access with it's ldap name instead of codex
# name because they can be different (ldap login: 'john doe' => 'john_doe')
def get_name_for_svn_access(svnrepo, username):
    if ldap_plugin_is_enabled():
        import codendildap
        if codendildap.project_has_ldap_auth(svnrepo):
            return codendildap.get_login_from_username(username)
        else:
            return username.lower()
    else:
       return username.lower() 

def get_group_name_from_plugin_svnrepo_path(svnrepo):
    path_elements   = string.split(svnrepo,'/')
    repository_name = MySQLdb.escape_string(path_elements[len(path_elements)-1])
    group_id        = MySQLdb.escape_string(path_elements[len(path_elements)-2])

    cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)

    query  = 'SHOW TABLES LIKE "plugin_svn_repositories"'
    res    = cursor.execute(query)

    if (res > 0):
        query  = 'SELECT g.unix_group_name FROM plugin_svn_repositories r JOIN groups g ON (g.group_id = r.project_id) WHERE project_id = "'+str(group_id)+'" AND name = "'+str(repository_name)+'"'
        res    = cursor.execute(query)
        row    = cursor.fetchone()
        cursor.close()

        if (cursor.rowcount == 1):
            return row['unix_group_name']

    return False

def get_group_name_from_core_svnrepo_path(svnrepo):
    path_elements = string.split(svnrepo,'/')
    group_name    = path_elements[len(path_elements)-1]

    return group_name

def get_group_from_svnrepo_path(svnrepo):
    group_name = get_group_name_from_plugin_svnrepo_path(svnrepo)
    if (group_name == False):
        return get_group_name_from_core_svnrepo_path(svnrepo)
    return group_name

def check_read_access(username, svnrepo, svnpath):
    # make sure that usernames are lowercase
    username = get_name_for_svn_access(svnrepo, username)

    if user.user_is_super_user():
        return True
    if user.user_is_restricted():
        group_name = get_group_from_svnrepo_path(svnrepo)
        group_id = group.set_group_info_from_name(group_name)
        if not user.user_is_member(group_id):
            return False

    return __check_read_access_with_epel_viewvc(username, svnrepo, svnpath)

def __check_read_access_with_epel_viewvc(username, svnrepo, svnpath):
    from vcauth.svnauthz import ViewVCAuthorizer
    root_lookup_func = lambda _: 'svn', svnrepo
    authorizer = ViewVCAuthorizer(root_lookup_func, username, {'authzfile' : svnrepo + '/.SVNAccessFile'})
    requested_path_parts = filter(None, svnpath.split('/'))
    return authorizer.check_path_access(svnrepo, requested_path_parts, None)
