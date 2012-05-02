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
#    This Perl include file mimics some of the fucntion in common/project/Group.class.php
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

# Retrive an array of emails watching a given changing directory within a given SVN checkins.
# Here an example of notification map, each entry is defined by (path, notifications):
# '/'                                                'tuleap-devel@example.com, bob@example.com'
# '/trunk'                                        'carole@example.com'
# '/trunk/src/common/'                   'dave@example.com'
# '/trunk/plugins/'                          'eve@example.com'
# '/trunk/src/commoncommon'          'oscar@example.com'
# '/trunk/src'                                 'walter@example.com'
# '/trunk/src/common&amp;common' 'trudy@example.com'
# Given an SVN commit  that performed changes in the directory '/trunk/src/common/',
# this subroutine would return an array with the following notification emails:
# [tuleap-devel@example.com, bob@example.com, carole@example.com, dave@example.com, walter@example.com]
#
# input: a string handled as changed directory, an integer handled as project ID.
# output: an array of email addresses corresponding to the given project id, path and subpathes
#
sub get_emails_by_path {
    my ($changed_directory, $groupid) = @_;
    my ($query, $res);
    # Split a given path into subpathes according to depth, then build a regular expression like below:
    # Path: '/trunk/src/common/' =>
    # Regex: '^(/trunk)$|^(/trunk/)$|^(/trunk/src)$|^(/trunk/src/)$|^(/trunk/src/common)$|^(/trunk/src/common/)$'
    my @dirs = split('/', $changed_directory);
    $root = "/";
    $patternMatcher = '';
    $patternBuilder = '';
    foreach my $dirVal (@dirs) {
        if ($patternMatcher ne '') {
                $patternBuilder .= $root.$dirVal;
                $patternMatcher .= '|^('.$patternBuilder.')$|^('.$patternBuilder.'/)$';
        } else {
                $patternBuilder .= $root.$dirVal;
                $patternMatcher .= '^('.$patternBuilder.')$|^('.$patternBuilder.'/)$';
        }
    }

    my $groupid = $dbh->quote($groupid);
    if ($patternMatcher ne '') {
        my $patternMatcher = $dbh->quote($patternMatcher);
        $subPathsExpression = "OR path RLIKE $patternMatcher";
    } else {
        $subPathsExpression = "";
    }
    $query = "SELECT svn_events_mailing_list FROM svn_notification WHERE group_id = $groupid and (path = '/' ".$subPathsExpression.")";
    $sth = $dbh->prepare($query);
    $res = $sth->execute();
    my @emails = ();
    if ($sth->rows >= 1) {
        while (my @row = $sth->fetchrow_array()) {
            my $email = shift @row;
            my @notifEmails = split(',', $email);
            foreach my $emailVal (@notifEmails) {
                # Remove whitespace from the start and end of the email string
                $emailVal =~ s/^\s+//;
                push @emails, $emailVal;
            }
        }
    } else {
        print STDERR "$query\nCan't select field: $DBI::errstr\n";
    }
    return @emails;
}

#
# Keep only one occurrence of each element of a given array.
# It's used in the "commit-email.pl" script (line#386) to remove redundant notification emails from the array of notification emails
# retrieved for each changing directory within a given SVN checkins.
# input: an array with redundant elements
# output: an array without redundant elements
#
sub redundancy_grep {
    my ($ref_array) = @_;
    my %hash_without_redundancy;
    return grep { !$hash_without_redundancy{$_}++ } @{$ref_array};
}

#
# input: a string handled as a email address
# output: 1 if the email address is valid, or 0 if the email address is not known in Codendi, or if all the accounts associated with it are deleted or suspended
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
            # if the email address is known in the Codendi system, we check if it is not associated with a wrong account (suspended or deleted)
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
