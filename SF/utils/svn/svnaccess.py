#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
# http://codex.xerox.com
#
# $Id$
#
#  License:
#    This file is subject to the terms and conditions of the GNU General Public
#    license. See the file COPYING in the main directory of this archive for
#    more details.
#
# Purpose:
#    This Python file fetches the SVN access file and determine whether
#    a given user has access to a given path in the repository
#

import re
import sys
import string

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
                    SVNGROUPS[group] = string.split(string.replace(users,' ',''),",")
                
            elif state == ST_PATH: 
                m = perm_pat.match(line)
                if m is not None:
                    who = m.group(1)
                    perm = m.group(2)

                    if who[0] == '@':
                        for who in SVNGROUPS[who[1:]]:
                            if not SVNACCESS.has_key(who): SVNACCESS[who] = {}
                            SVNACCESS[who][path] = string.strip(perm)
                            #SVNACCESS[who][path] = perm
                    else:
                        if not SVNACCESS.has_key(who): SVNACCESS[who] = {}
                        SVNACCESS[who][path] = string.strip(perm)
                        #SVNACCESS[who][path] = perm

        f.close()
        print SVNGROUPS
        print SVNACCESS


def check_read_access(username, svnrepo, svnpath):
    
    global SVNACCESS, SVNGROUPS

    if username == 'admin':
        return True

    if SVNACCESS is None:
        fetch_access_file(svnrepo)

    perm = ''
    path = '/'+svnpath
    while True:
        if SVNACCESS.has_key(username) and SVNACCESS[username].has_key(path):
            perm = SVNACCESS[username][path]
            #print "match: SVNACCESS[",username,"][",path,"]",perm
            break
        elif SVNACCESS.has_key('*') and SVNACCESS['*'].has_key(path):
            perm = SVNACCESS['*'][path]
            #print "match: SVNACCESS[*][",path,"]",perm
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

#check_read_access('laurent','/svnroot/codex/', '/codex/trunk/SRC')
#check_read_access('schneide','/svnroot/ngproj/', 'trunk/')
#check_read_access('schneide','/svnroot/ngproj/', 'tags/')
#check_read_access('guerin','/svnroot/ngproj/', 'trunk/')
#check_read_access('guerin','/svnroot/ngproj/', 'tags/')
#fetch_access_file('/svnroot/codex')
