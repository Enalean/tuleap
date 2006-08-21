#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
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
# Global Variables
##############################

db_include = os.getenv('SF_LOCAL_INC_PREFIX','')+"/etc/codex/conf/local.inc"	# Local Include file for database username and password
tar_dir	= "/tmp"		# Place to put deleted user's accounts
uid_add	= "20000"	# How much to add to the database uid to get the unix uid
gid_add	= "1000"		# How much to add to the database gid to get the unix uid
homedir_prefix = "/home/users/";	# What prefix to add to the user's homedir
grpdir_prefix  = "/home/groups/"; # What prefix to add to the project's homedir
cvs_prefix     = "/cvsroot"; # What prefix to add to the cvs root directory
svn_prefix     = "/svnroot"; # What prefix to add to the subversion root directory
ftp_frs_dir_prefix  = "/home/ftp/codex/" # What prefix to add to the ftp project file release homedir
ftp_anon_dir_prefix  = "/home/ftp/pub/" # What prefix to add to the anon ftp project homedir
file_dir = "/home/dummy/dumps/" # Where should we stick files we're working with
dummy_uid = "103" # UserID of the dummy user that will own group's files
date = int(time.time()/3600/24) # Get the number of days since 1/1/1970 for /etc/shadow
apache_conf = "/etc/httpd/conf/httpd.conf" # Apache configuration file


##############################
# Local Configuration Load
##############################
def load_local_config():
    """Local Configuration Load"""
    try:
        f = open(db_include)
    except IOError, (errno, strerror):
        print "Can't open %s: I/O error(%s): %s" % (db_include, errno, strerror)
    else:
        comment_pat   = re.compile("^\s*\/\/")
        empty_pat     = re.compile("^\s*$")
        assign_pat    = re.compile("^\s*\$(.*);\s*$")
        nodollar_pat  = re.compile("(\s+)\$")
        dottoplus_pat = re.compile("(\s+)\.(\s+)")
        while True:
            line = f.readline()
            if not line: break
            if comment_pat.match(line) or empty_pat.match(line): continue
            m = assign_pat.match(line)
            if m is not None:
                n = nodollar_pat.sub(" ",m.group(1))
                n = dottoplus_pat.sub(" + ",n)
                exec n in globals()
        f.close()


##############################
# Database Connect Functions
##############################
def db_connect():
    """Connect to CodeX database"""
    global dbh
    load_local_config()
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

#############################
# Get CodeX User from the apache
# configuration file
#############################
def get_codex_user():
    """Get CodeX user name from apache conf file."""
    try:
        f = open(apache_conf)
    except IOError, (errno, strerror):
        return None
    else:
        user_pat  = re.compile("^\s*User\s+(.*)\s*")
        while True:
            line = f.readline()
            if not line: break
            m = user_pat.match(line)
            if m is not None:
                return m.group(1)
        f.close
        
#############################
# Create hyperlinks to project references
# in the text extract.
#############################
def util_make_links(text, group_name):
    """Create hyperlinks to project references in the text extract."""

    import httplib, urllib
    host = sys_default_domain
    params = urllib.urlencode({'text': text,
                              'group_name': group_name})
    headers = {"Content-type": "application/x-www-form-urlencoded",
               "Accept": "text/plain",
               'User-agent': 'CodeX Python Agent',
               'Host': host}
    conn = httplib.HTTPConnection(host)
    conn.request("POST", "/api/reference/insert", params, headers)
    response = conn.getresponse()
    
    if response.status == 200:  #OK
        new_text = response.read()
        conn.close
        return new_text
    else:
        conn.close
        return text
    
