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
