# Copyright (c) Enalean, 2015-Present. All Rights Reserved.
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
#
# This file is a part of Tuleap.
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/
#

use vars qw ( %G_USER );


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
