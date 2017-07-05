#
# Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
#
# Originally written by Manuel Vacelet, 2008
#
# This file is a part of CodeX.
#
# CodeX is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# CodeX is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with CodeX; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
#

import ldap
import MySQLdb
import include
import os

ldapIncFile = include.sys_custompluginsroot+'/ldap/etc/ldap.inc';
include.load_local_config(ldapIncFile)

def ldap_connect():
    for server in include.sys_ldap_server.split(','):
        try:
            l = ldap.initialize(server)
            l.set_option(ldap.OPT_REFERRALS, 0)
            if hasattr(include, 'sys_ldap_bind_dn'):
                l.simple_bind_s(include.sys_ldap_bind_dn, include.sys_ldap_bind_passwd)
            else:
                l.simple_bind_s()
            return l
        except ldap.SERVER_DOWN:
            pass

def get_login_from_eduid(ldap_id):
    l = ldap_connect()
    r = l.search_s(include.sys_ldap_dn, ldap.SCOPE_SUBTREE, include.sys_ldap_eduid+'='+ldap_id, [include.sys_ldap_uid])
    if len(r) == 1:
        entry = r[0][1];
        username = entry[include.sys_ldap_uid][0].lower();
        return username
    else:
        return ''
    

def get_login_from_username(username):
    cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
    cursor.execute('SELECT ldap_id'+ 
                   ' FROM user'+
                   ' WHERE user_name="'+username+'"')
    row = cursor.fetchone()
    if row != None:
        return get_login_from_eduid(row['ldap_id'])
    else:
        return ''

def project_has_ldap_auth(svnrepo):
    unix_group_name = os.getenv('TULEAP_PROJECT_NAME')
    if unix_group_name is None:
        # This function is used in svnaccess::check_read_access that refer to svn
        # repo with the /svnroot... (and may have a leading slash).
        unix_group_name = svnrepo.replace('/svnroot/', '', 1);
        unix_group_name = unix_group_name.replace('/','');
    
    cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
    cursor.execute('SELECT NULL'+ 
                   ' FROM plugin_ldap_svn_repository'+
                   ' JOIN groups USING (group_id)'+
                   ' WHERE unix_group_name="'+unix_group_name+'"')
    return (cursor.rowcount == 1)

def get_name_for_svn(svnrepo, username):
    if project_has_ldap_auth(svnrepo):
        return get_login_from_username(username)
    else:
        return username.lower()

#import sys
#sys.path.insert(0,'/usr/share/codex/src/utils')
#include.db_connect()
#print get_login_from_eduid('edtete');
#print get_login_from_username('john_doe');
#print project_has_ldap_auth('codex');
#print project_has_ldap_auth('/svnroot/codex');
#print project_has_ldap_auth('/svnroot/codex/');
#print get_name_for_svn('/svnroot/sds/', 'manuel_vacelet')
