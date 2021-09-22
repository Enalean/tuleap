#
# Codendi
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
# http://www.codendi.com
#
#
#
# include.py - Include file for all the python scripts that contains reusable functions
#

import sys
import os
import string
import time
import re
import MySQLdb
import Cookie
import subprocess
import json

##############################
# Local Configuration Load
##############################
def load_local_config():
    """Local Configuration Load"""

    str=subprocess.check_output(["/usr/bin/tuleap", "config-dump", "sys_dbname", "sys_dbhost", "sys_dbuser", "sys_dbpasswd", "sys_dbport", "sys_enablessl", "sys_db_ssl_ca", "sys_cookie_prefix", "sys_http_user"])
    for key, value in json.loads(str).items():
        globals()[key] = value

##############################
# Global Variables
##############################

# Local Include file for database username and password
date = int(time.time()/3600/24) # Get the number of days since 1/1/1970 for /etc/shadow
load_local_config()

##############################
# Database Connect Functions
##############################
def db_connect():
    """Connect to application database"""
    global dbh
    global sys_dbhost

    connect_args = dict(db=sys_dbname, host=sys_dbhost, user=sys_dbuser, passwd=sys_dbpasswd)

    if 'sys_dbport' in globals():
        connect_args['port'] = sys_dbport

    if sys_enablessl == '1':
        # Due to MySQLdb limitations, we cannot enforce certificate trust
        # see https://mysqlclient.readthedocs.io/user_guide.html 'ssl' section
        # and related https://dev.mysql.com/doc/refman/5.7/en/mysql-ssl-set.html
        connect_args['ssl'] = {'ca': sys_db_ssl_ca}

    pos = sys_dbhost.find(':')
    if pos > 0:
        dbport = int(sys_dbhost[pos+1:])
        sys_dbhost = sys_dbhost[:pos]
        connect_args['host'] = sys_dbhost
        connect_args['port'] = dbport

    # connect to the database
    dbh = MySQLdb.connect(**connect_args)

##############################
# Access to local.inc variables from other modules:
##############################

# Get cookie Prefix
def get_cookie_prefix():
    """Get cookie prefix"""
    cookie_prefix = '__Host-'
    if globals().has_key('sys_cookie_prefix'):
        return cookie_prefix + sys_cookie_prefix
    return cookie_prefix


# Get Codendi Apache User
def get_codendi_user():
    """Get Codendi Apache user name"""
    return sys_http_user

def constant_time_str_compare(value1, value2):
    if len(value1) != len(value2):
        return False

    result = 0

    for x, y in zip(value1, value2):
        result |= ord(x) ^ ord(y)

    return result == 0
