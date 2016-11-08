#
# Copyright (c) Enalean, 2016. All Rights Reserved.
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

import re
import sys
import string
import user
import group
import MySQLdb
import include
import pkgutil

global SVNACCESS, SVNGROUPS
SVNACCESS = None
SVNGROUPS = None

def fetch_access_file(svnrepo):
    global SVNACCESS, SVNGROUPS
    filename = svnrepo+"/.SVNAccessFile"
    SVNACCESS = {}
    SVNGROUPS = {}
    
    try:
        f = open(filename)
    except IOError, (errno, strerror):
        print "Can't open %s: I/O error(%s): %s" % (filename, errno, strerror)
    else:
        path_pat    = re.compile("^\s*\[(.*)\]") # assume no repo name 'repo:'
        perm_pat    = re.compile("^\s*([^ ]*)\s*=\s*(.*)$")
        group_pat   = re.compile("^\s*([^ ]*)\s*=\s*(.*)$")
        empty_pat   = re.compile("^\s*$")
        comment_pat = re.compile("^\s*#")

        ST_START = 0
        ST_GROUP = 1
        ST_PATH = 2

        state = ST_START

        while True:
            line = f.readline()
            if not line: break
            if comment_pat.match(line) or empty_pat.match(line): continue

            m = path_pat.match(line)
            if m is not None:
                path = m.group(1)
                if path == "groups":
                    state = ST_GROUP
                else:
                    state = ST_PATH

            if state == ST_GROUP:
                m = group_pat.match(line)
                if m is not None:
                    group = m.group(1)
                    users = m.group(2)
                    # Apply stripName lambda on each element of the list of
                    # user names
                    SVNGROUPS[group.lower()] = map(string.strip, string.split(users.lower(),","))

            elif state == ST_PATH:
                m = perm_pat.match(line)
                if m is not None:
                    who = m.group(1)
                    perm = m.group(2)

                    if who[0] == '@':
                        this_group=who[1:]
                        if SVNGROUPS.has_key(this_group.lower()):
                            for who in SVNGROUPS[this_group.lower()]:
                                if not SVNACCESS.has_key(who): SVNACCESS[who] = {}
                                SVNACCESS[who][path] = string.strip(perm)
                    else:
                        if not SVNACCESS.has_key(who.lower()): SVNACCESS[who.lower()] = {}
                        SVNACCESS[who.lower()][path] = string.strip(perm)

        f.close()

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

    if __is_using_epel_viewvc():
        return __check_read_access_with_epel_viewvc(username, svnrepo, svnpath)

    return __check_read_with_tuleap_viewvc(username, svnrepo, svnpath)


def __is_using_epel_viewvc():
    loader_vcauth = pkgutil.find_loader('vcauth')
    if loader_vcauth is None:
        return False
    loader_svnauthz = pkgutil.find_loader('vcauth.svnauthz')
    return loader_svnauthz is not None


def __check_read_access_with_epel_viewvc(username, svnrepo, svnpath):
    from vcauth.svnauthz import ViewVCAuthorizer
    root_lookup_func = lambda _: 'svn', svnrepo
    authorizer = ViewVCAuthorizer(root_lookup_func, username, {'authzfile' : svnrepo + '/.SVNAccessFile'})
    requested_path_parts = filter(None, svnpath.split('/'))
    return authorizer.check_path_access(svnrepo, requested_path_parts, None)


def __check_read_with_tuleap_viewvc(username, svnrepo, svnpath):
    global SVNACCESS, SVNGROUPS
    if SVNACCESS is None:
        fetch_access_file(svnrepo)

    perm = ''
    path = '/'+svnpath
    while True:
        if SVNACCESS.has_key(username) and SVNACCESS[username].has_key(path):
            perm = SVNACCESS[username][path]
            break
        elif SVNACCESS.has_key('*') and SVNACCESS['*'].has_key(path):
            perm = SVNACCESS['*'][path]
            break
        else:
            # see if it maches higher in the path
            if path == '/': break
            idx = string.rfind(path,'/')
            if idx == 0:
                path = '/'
            else:
                path = path[:idx]

    if perm == 'r' or perm == 'rw':
        return True
    else:
        return False
