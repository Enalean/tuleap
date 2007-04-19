#
# CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
# Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2001. All Rights Reserved
# http://codex.xerox.com
#
# $Id: group.pl 5722 2007-04-05 14:10:33 +0000 (Thu, 05 Apr 2007) guerin $
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


# Perl trim function to remove whitespace from the start and end of the string
sub trim($) {
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}

#
# input: a string corresponding to email adresse or login name, separated by a comma
# output: the same string, but without wrong login, suspended users, delete users
#
sub filter_valid_logins_and_emails {

    my ($str_to_filter) = $_[0];
    my (@array_filtered);

    @item_array = split(',', $str_to_filter);
    foreach $item (@item_array) {
        $item = trim($item);
        print "curr=#"; print $item; print "#\n";
        if (index($item,"@") < 0) {
            # assume that it is a login name
            $curr_email = get_email_from_login($item);
            if ($curr_email ne 0) {
                push(@array_filtered, $curr_email);
            }
        } else {
            # assume it is an email address
            $ok = is_valid_email($item);
            if ($ok eq 1) {
                push(@array_filtered, $item);
            }
        }
    }
    return join(",", @array_filtered);
}

#
# input: a string handled as a login name
# output: the email address corresponding to the login, or 0 if the login is not a valid one, or if the account is deleted or suspended
#
sub get_email_from_login {

    my ($username) = $_[0];
    my ($query, $c, $res);

    if ($username ne '') {
        $query = "SELECT email FROM user WHERE user_name='$username' AND (status='A' OR status='R') ";
        
        $c = $dbh->prepare($query);
        $res = $c->execute();
    
        if (!$res || ($c->rows < 1)) {
            return 0;
        } else {
            # there is only one user associated to one login
            $user_hash_ref = $c->fetchrow_hashref;
            return $user_hash_ref->{email};
        }
    } else {
        return 0;
    }
}

#
# input: a string handled as a email address
# output: 1 if the email address is valid, or 0 if the email address is not known in CodeX, or if all the accounts associated with it are deleted or suspended
#
sub is_valid_email {

    my ($email) = $_[0];
    my ($query, $c, $res);

    if ($email ne '') {
        $query = "SELECT * FROM user WHERE email='$email' ";
        
        $c = $dbh->prepare($query);
        $res = $c->execute();
        
        if (!$res || ($c->rows < 1)) {
            # if the email is unknow, we add it (it is an external email address for instance)
            return 1;
        } else {
            # if the email address is known in the CodeX system, we check if it is not associated with a wrong account (suspended or deleted)
            while ($user_hash_ref = $c->fetchrow_hashref) {
                if ($user_hash_ref->{status} eq 'A' || $user_hash_ref->{status} eq 'R') {
                    return 1;
                }
            }
            return 0;
        }
    } else {
        return 0;
    }
}

1;
