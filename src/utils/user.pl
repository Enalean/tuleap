#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
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
#    This Perl include file mimics some of the fucntion in www/include/user.php
#    to allow Perl scripts to handle exit errors and messages


use vars qw ( %G_SESSION %G_USER %cookies);

my ($USER_IS_SUPER_USER);

sub user_isloggedin {
  return defined($G_USER{'user_id'});
}

sub user_is_super_user {
  my ($query, $c, $res);

  return $USER_IS_SUPER_USER if (defined($USER_IS_SUPER_USER));
    		
  if (user_isloggedin()) {

    $query="SELECT * FROM user_group WHERE user_id='". user_getid() .
      "' AND group_id='1' AND admin_flags='A'";
    $c = $dbh->prepare($query);
    $res = $c->execute();
    
    if (!$res || ($c->rows < 1) ) {
      $USER_IS_SUPER_USER = 0;
    } else {
      $USER_IS_SUPER_USER = 1;
    }
    
  } else {
    $USER_IS_SUPER_USER = 0;
  }
  
  return $USER_IS_SUPER_USER;
}

sub user_is_member {
  my($group_id, $type) = @_;
  my ($query, $c, $res, $user_id);

  if (!user_isloggedin()) {
    return 0;
  }

  $user_id = user_getid(); #optimization

  # Super User always a project member
  if (user_is_super_user()) {
    return 1;
  }

  # for everyone else, do a query
  $query = "SELECT user_id FROM user_group "
    . "WHERE user_id='$user_id' AND group_id='$group_id'";

  $type =~ tr/a-z/A-Z/;

 SWITCH: {

    if ($type eq '0') { last SWITCH; }
    if ($type eq 'A') { $query .= " AND admin_flags = 'A'"; last SWITCH; }
    if ($type eq 'B1') { $query .= ' AND bug_flags IN (1,2)'; last SWITCH; }
    if ($type eq 'B2') { $query .= ' AND bug_flags IN (2,3)'; last SWITCH; }
    if ($type eq 'P1') { $query .= ' AND project_flags IN (1,2)'; last SWITCH; }
    if ($type eq 'P2') { $query .= ' AND project_flags IN (2,3)'; last SWITCH; }
    if ($type eq 'C1') { $query .= ' AND patch_flags IN (1,2)'; last SWITCH; }
    if ($type eq 'C2') { $query .= ' AND patch_flags IN (2,3)'; last SWITCH; }
    if ($type eq 'F2') { $query .= ' AND forum_flags IN (2)'; last SWITCH; }
    if ($type eq 'S1') { $query .= ' AND support_flags IN (1,2)'; last SWITCH; }
    if ($type eq 'S2') { $query .= ' AND support_flags IN (2,3)'; last SWITCH; }
    if ($type eq 'D1') { $query .= " AND doc_flags IN (1,2)"; last SWITCH; }
    if ($type eq 'D2') { $query .= " AND doc_flags IN (2,3)"; last SWITCH; }
    if ($type eq 'R2') { $query .= " AND file_flags = '2'"; last SWITCH; }
  }

  $c = $dbh->prepare($query);
  $res = $c->execute();

  if (!$res || ($c->rows < 1)) {
    return 0;
  } else {
    return 1;
  }

}


sub user_getid {
  return (user_isloggedin()?$G_USER{'user_id'}:0);
}


1;
