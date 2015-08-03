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
#    This Perl include file mimics some of the fucntion in www/include/session.php
#    to allow Perl scripts to handle user session stuff

use CGI;
use CGI::Cookie;

use vars qw ( %G_SESSION %G_USER %cookies);


sub session_checkip {
  my ($oldip, $newip) = @_;

  my @eoldip = split(/\./,$oldip);
  my @enewip = split(/\./,$newip);

  # require same class b subnet
  return (($eoldip[0]==$enewip[0]) && ($eoldip[1]==$enewip[1]));

}

sub session_setglobals {

  my $user_id = shift;

  if ($user_id > 0) {
    my $sth = $dbh->prepare("SELECT user_id,user_name FROM user WHERE user_id='$user_id'");
    my $result = $sth->execute();

    if (!$result || $sth->rows < 1) {
      # echo db_error();
      %G_USER = {};
    } else {
      my $hash_ref = $sth->fetchrow_hashref; 
      foreach my $key (keys %$hash_ref) {
	$G_USER{$key} = $hash_ref->{$key};
      }
      # echo $G_USER['user_name'].'<BR>';
    }
  } else {
    %G_USER = {};
  }
}

#############################
# Get Codendi apache user from local.inc
#############################
sub session_store_access {
  my ($uid) = @_;

  my $sth = $dbh->prepare("SELECT last_access_date FROM user_access WHERE user_id='$uid'");
  $sth->execute();
  $hash_ref = $sth->fetchrow_hashref; 

  # does hash value exists
  if ($hash_ref->{'last_access_date'}) {
    $current_date=time();
    # Don't log access if already accessed in the past 6 hours (scalability+privacy)
    if (abs($current_date - $hash_ref->{'last_access_date'}) > 21600) {
      $upd_query="UPDATE user_access SET last_access_date='".$current_date."' WHERE user_id='$uid'";
      $d = $dbh->prepare($upd_query);
      $d->execute();
    }
  }
}


1;
