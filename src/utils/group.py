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
#    This Python file mimics some of the functions in common/project/Group.class.php
#    to allow Python scripts to handle group information

import include
import MySQLdb

global GROUP_INFO

def set_group_info_from_name(gname):

  cursor = include.dbh.cursor(cursorclass=MySQLdb.cursors.DictCursor)
  cursor.execute("SELECT * FROM groups WHERE unix_group_name='"+gname+"'")
  row = cursor.fetchone()
  cursor.close()
  
  if row is None:
    return 0
  else:
    GROUP_INFO = row

  return GROUP_INFO['group_id']

def isGroupCvsTracked():
  return GROUP_INFO['cvs_tracker']

def cvsGroup_mail_header():
  return GROUP_INFO['cvs_events_mailing_header']

def cvsGroup_mailto():
  return GROUP_INFO['cvs_events_mailing_list']

def isGroupSvnTracked():
  return GROUP_INFO['cvs_tracker']

def svnGroup_mail_header():
  return GROUP_INFO['cvs_events_mailing_header']

def svnGroup_mailto():
  return GROUP_INFO['cvs_events_mailing_list']

def isGroupPublic():
  return (GROUP_INFO['access'] != 'private')


