#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
# http://codex.xerox.com
#
# $Id: session.py 5506 2007-03-23 15:48:53 +0000 (Fri, 23 Mar 2007) guerin $
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    This Python file mimics some of the function in www/include/session.php
#    to allow Python scripts to handle user session stuff

import os
import string
import Cookie
import MySQLdb
import include

global G_SESSION, G_USER
G_USER    = {}
G_SESSION = {}

def session_checkip(oldip,newip):

    eoldip = string.split(oldip,'.')
    enewip = string.split(newip,'.')
    
    # require same class b subnet
    return ((eoldip[0]==enewip[0]) and (eoldip[1]==enewip[1]))


def session_setglobals(user_id):
    
    global G_USER

    if user_id > 0:
        cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
        cursor.execute("SELECT * from user WHERE user_id='"+str(user_id)+"'")
        row = cursor.fetchone()
        cursor.close()

        if row is None:
            G_USER = {}
        else:
            G_USER['user_id'] = row['user_id']
            G_USER['user_name'] = row['user_name']
            #print G_USER['user_name']+'<BR>'
          
    else:
        G_USER = {}


def session_set():
    
    global G_SESSION, G_USER
    
    id_is_good = False

    # get cookies
    c = Cookie.SimpleCookie()
    c.load(os.environ["HTTP_COOKIE"])

    #print "Content-type: text/html\n"
    #print "name =",c,"<BR>"
    
    # if hash value given by browser then check to see if it is OK.
    cookie_name=include.get_cookie_prefix()+'_session_hash'
    if c.has_key(cookie_name):

        cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
        cursor.execute("SELECT user_id,session_hash,ip_addr,time FROM session WHERE session_hash='"+c[cookie_name].value+"'")
        row = cursor.fetchone()
        cursor.close()
        
        # does hash value exists
        if row is not None:
            if session_checkip(row['ip_addr'], os.environ['REMOTE_ADDR']):
                id_is_good = True

    if id_is_good:
        G_SESSION = row
        session_setglobals(G_SESSION['user_id'])
    else:
        G_SESSION = {}
        G_USER = {}


#  print "id_is_good=",id_is_good
#  print "G_SESSION=",G_SESSION
#  print "G_USER=",G_USER
