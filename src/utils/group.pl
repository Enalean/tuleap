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
#    This Perl include file mimics some of the fucntion in www/include/Group.class.php
#    to allow Perl scripts to handle group information

my ($GROUP_INFO);

sub set_group_info_from_name {

  my ($gname) = @_;
  my ($query, $c, $res);

  $query = "SELECT * FROM groups WHERE unix_group_name='$gname'";
  $c = $dbh->prepare($query);
  $res = $c->execute();

  if (!$res || ($c->rows < 1)) {
    return 0;
  } else {
    $GROUP_INFO = $c->fetchrow_hashref;
  }

  return $$GROUP_INFO{'group_id'};
    
}

sub isGroupCvsTracked {
  return $$GROUP_INFO{'cvs_tracker'};
}

sub cvsGroup_mail_header {
  return $$GROUP_INFO{'cvs_events_mailing_header'};
}

sub cvsGroup_mailto {
  return $$GROUP_INFO{'cvs_events_mailing_list'};
}

sub isGroupSvnTracked {
  return $$GROUP_INFO{'svn_tracker'};
}

sub svnGroup_mail_header {
  return $$GROUP_INFO{'svn_events_mailing_header'};
}

sub svnGroup_mailto {
  return $$GROUP_INFO{'svn_events_mailing_list'};
}

sub isGroupPublic {
  return $$GROUP_INFO{'is_public'};
}

1;
