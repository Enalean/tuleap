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

##############################
# Local Configuration Load
##############################
def load_local_config(filename):
    """Local Configuration Load"""
    try:
        f = open(filename)
    except IOError, (errno, strerror):
        print "Can't open %s: I/O error(%s): %s" % (filename, errno, strerror)
    else:
        comment_pat   = re.compile("^\s*\/\/")
        empty_pat     = re.compile("^\s*$")
        assign_pat    = re.compile("^\s*\$(.*);\s*$")
        nodollar_pat  = re.compile("(\s+)\$")
        dottoplus_pat = re.compile("(\s+)\.(\s+)")
        # filter end of line comment, but beware of things like http:// or ldap:// -> make sure there is a blank char before the '//'
        nocomment_pat = re.compile(" \/\/.*")
        while True:
            line = f.readline()
            if not line: break
            if comment_pat.match(line) or empty_pat.match(line): continue
            m = assign_pat.match(line)
            if m is not None:
                n = nodollar_pat.sub(" ",m.group(1))
                n = dottoplus_pat.sub(" + ",n)
                n = nocomment_pat.sub("",n)
                exec n in globals()
        f.close()


##############################
# Global Variables
##############################

db_include = os.getenv('CODENDI_LOCAL_INC','')
if db_include is "":
    db_include = "/etc/codendi/conf/local.inc"
# Local Include file for database username and password
date = int(time.time()/3600/24) # Get the number of days since 1/1/1970 for /etc/shadow
load_local_config(db_include)


##############################
# Database Connect Functions
##############################
def db_connect():
    """Connect to Codendi database"""
    global dbh
    load_local_config(db_config_file)
    # connect to the database
    dbh = MySQLdb.connect(db=sys_dbname, host=sys_dbhost, user=sys_dbuser, passwd=sys_dbpasswd)

##############################
# File open function, spews the entire file to an array.
##############################
def open_array_file(filename):
    """File open function, spews the entire file to an array."""
    try:
        f = open(filename)
    except IOError, (errno, strerror):
        print "Can't open %s: I/O error(%s): %s" % (filename, errno, strerror)
    else:
        lines = f.readlines()
        f.close()
    return(lines)

#############################
# File write function.
#############################
def write_array_file(filename, lines):
    """File write function."""
    try:
        f = open(filename, 'r+')
    except IOError, (errno, strerror):
        print "Can't open %s: I/O error(%s): %s" % (filename, errno, strerror)
    else:
        while True:
            line = lines.pop(0)
            if line is not None:
                f.write(line)
        f.close()


##############################
# Access to local.inc variables from other modules:
##############################

# Get cookie Prefix
def get_cookie_prefix():
    """Get cookie prefix"""
    if not globals().has_key('sys_cookie_prefix'):
	return ('')
    return (sys_cookie_prefix)


# Get Codendi Apache User
def get_codendi_user():
    """Get Codendi Apache user name"""
    return sys_http_user
#    """Get Codendi user name from apache conf file."""
#    try:
#        f = open(apache_conf)
#    except IOError, (errno, strerror):
#        return None
#    else:
#        user_pat  = re.compile("^\s*User\s+(.*)\s*")
#        while True:
#            line = f.readline()
#            if not line: break
#            m = user_pat.match(line)
#            if m is not None:
#                return m.group(1)
#        f.close
        
