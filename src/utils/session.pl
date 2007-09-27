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

sub session_set {

  my $id_is_good = 0;
  my $hash_ref;

  # get cookies 
  %cookies = fetch CGI::Cookie;
#  print "Content-type: text/html\n";
#  print "name =",$cookies{'session_hash'}->name,"<BR>";
#  print "value =",$cookies{'session_hash'}->value,"<BR>";

  # if hash value given by browser then check to see if it is OK.
  if ($cookies{sys_cookie_prefix.'_session_hash'}) {

    my $sth = $dbh->prepare("SELECT * FROM session WHERE session_hash='".$cookies{'session_hash'}->value."'");
    $sth->execute();
    $hash_ref = $sth->fetchrow_hashref; 


    # does hash value exists
    if ($hash_ref->{'session_hash'}) {
      if (session_checkip($hash_ref->{'ip_addr'}, $ENV{'REMOTE_ADDR'})) {
	$id_is_good = 1;
      }
    } # else hash was not in database
  } # else  (hash does not exist) or (session hash is bad)


  if ($id_is_good) {
    foreach my $key (keys %$hash_ref) {
      $G_SESSION{$key} = $hash_ref->{$key};
    }
    session_setglobals($G_SESSION{'user_id'});
  } else {
    undef %G_SESSION;
    undef %G_USER;
  }

#  print "id_is_good=$id_is_good";
#  print "G_SESSION=",%G_SESSION;
#  print "G_USER=",%G_USER;


}

#############################
# Get CodeX apache user from local.inc
#############################
sub session_store_access {
  my ($uid) = @_;

  my $sth = $dbh->prepare("SELECT last_access_date FROM user WHERE user_id='$uid'");
  $sth->execute();
  $hash_ref = $sth->fetchrow_hashref; 

  # does hash value exists
  if ($hash_ref->{'last_access_date'}) {
    $current_date=time();
    # Don't log access if already accessed in the past 6 hours (scalability+privacy)
    if (abs($current_date - $hash_ref->{'last_access_date'}) > 21600) {
      $upd_query="UPDATE user SET last_access_date='".$current_date."' WHERE user_id='$uid'";
      $d = $dbh->prepare($upd_query);
      $d->execute();
    }
  }
}


1;
