#
# Tuleap
#
# Copyright (c) Enalean, 2016. All Rights Reserved.
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
#
#  License:
#    This file is a part of Tuleap.
#
#    Tuleap is free software; you can redistribute it and/or modify
#    it under the terms of the GNU General Public License as published by
#    the Free Software Foundation; either version 2 of the License, or
#    (at your option) any later version.
#
#    Tuleap is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU General Public License for more details.
#
#    You should have received a copy of the GNU General Public License
#    along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
#
# Purpose:
#    This Python file mimics some of the function in www/include/session.php
#    to allow Python scripts to handle user session stuff

import os
import time
import string
import Cookie
import MySQLdb
import include
import hashlib

global G_SESSION, G_USER
G_USER    = {}
G_SESSION = {}

def session_setglobals(user_id):
    global G_USER

    if user_id > 0:
        cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
        cursor.execute("SELECT * from user WHERE user_id = %s", (user_id,))
        row = cursor.fetchone()
        cursor.close()

        if row is None:
            G_USER = {}
        else:
            G_USER['user_id'] = row['user_id']
            G_USER['user_name'] = row['user_name']

    else:
        G_USER = {}


def session_set():
    global G_SESSION, G_USER

    id_is_good = False

    # get cookies
    c = Cookie.SimpleCookie()
    c.load(os.environ["HTTP_COOKIE"])

    # if hash value given by browser then check to see if it is OK.
    cookie_name = include.get_cookie_prefix()+'_session_hash'
    if cookie_name in c:
        session_identifier_parts = c[cookie_name].value.split('.')

        if len(session_identifier_parts) != 2:
            G_SESSION = {}
            G_USER = {}
            return None

        (session_id, session_token) = session_identifier_parts

        current_time = time.time()
        cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
        cursor.execute(
            "SELECT * FROM session WHERE id = %s AND time + %s > %s",
            (session_id, include.sys_session_lifetime, current_time)
        )
        row = cursor.fetchone()
        cursor.close()

        hashed_session_token = hashlib.sha256(session_token).hexdigest()
        if row is not None and include.constant_time_str_compare(row['session_hash'], hashed_session_token):
                id_is_good = True

    if id_is_good:
        G_SESSION = row
        session_setglobals(G_SESSION['user_id'])
    else:
        G_SESSION = {}
        G_USER = {}
