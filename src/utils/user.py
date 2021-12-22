#
# Codendi
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
# 
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    This Python file mimics some of the fucntion in www/include/user.php
#    to allow Python scripts to handle exit errors and messages

import include
import session
import MySQLdb

global USER_IS_SUPER_USER
global USER_NAMES

USER_NAMES = {}
USER_IS_SUPER_USER = None
USER_IS_RESTRICTED = None

def user_isloggedin():
    return session.G_USER.has_key('user_id')

def user_is_restricted():

    global USER_IS_RESTRICTED

    if USER_IS_RESTRICTED is not None: return USER_IS_RESTRICTED

    if user_isloggedin():

        cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
        cursor.execute("SELECT * FROM user WHERE user_id='"+str(user_getid())+
                       "' AND status='R'")
        row = cursor.fetchone()
        cursor.close()

        if row is None:
            USER_IS_RESTRICTED = False
        else:
            USER_IS_RESTRICTED = True

    else:
        USER_IS_RESTRICTED = False

    return USER_IS_RESTRICTED



def user_is_member(group_id, type='0'):

    if not user_isloggedin():
        return False

    user_id = user_getid() #optimization

    # for everyone else, do a query
    query = "SELECT user_id FROM user_group WHERE user_id='"+str(user_id)+"' AND group_id='"+str(group_id)+"'"

    type = type.upper()

    if type == '0':
        pass
    elif type == 'A': query += " AND admin_flags = 'A'"
    elif type == 'B1': query += ' AND bug_flags IN (1,2)'
    elif type == 'B2': query += ' AND bug_flags IN (2,3)'
    elif type == 'P1': query += ' AND project_flags IN (1,2)'
    elif type == 'P2': query += ' AND project_flags IN (2,3)'
    elif type == 'C1': query += ' AND patch_flags IN (1,2)'
    elif type == 'C2': query += ' AND patch_flags IN (2,3)'
    elif type == 'F2': query += ' AND forum_flags IN (2)'
    elif type == 'S1': query += ' AND support_flags IN (1,2)'
    elif type == 'S2': query += ' AND support_flags IN (2,3)'

    cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
    cursor.execute(query)
    row = cursor.fetchone()
    cursor.close()
  
    if row is None:
        return False
    else:
        return True


def user_getid():
    if user_isloggedin():
        return session.G_USER['user_id']
    else:
        return 0
