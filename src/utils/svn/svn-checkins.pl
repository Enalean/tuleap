#
# Copyright (c) Enalean, 2017. All Rights Reserved.
# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
#
# Tuleap is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Tuleap is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
#

use DBI qw(:sql_types);

require $utils_path."/session.pl";

sub db_get_field {
  my ($table, $fieldname, $value, $retfieldname) = @_;
  my ($query, $res);
  $query = "SELECT $retfieldname  FROM $table WHERE $fieldname=?";
  $sth = $dbh->prepare($query);
  $sth->bind_param(1, $value, SQL_VARCHAR);
  $res = $sth->execute();
  if ($sth->rows >= 1) {
    $hash_ref = $sth->fetchrow_hashref;
    $result = $hash_ref->{$retfieldname};
  } else {
    print STDERR "$query\nCan't select field: $DBI::errstr\n";
    $result = '0';
  }
  return $result;
}

sub db_get_index {
  my ($table, $fieldname, $value) = @_;
  my ($query, $res);
  $debug = 0;
  $query = "SELECT id  FROM $table WHERE $fieldname=?";
  if ($debug) {
    print STDERR $query, "\n";
  }
  $sth = $dbh->prepare($query);
  $sth->bind_param(1, $value, SQL_VARCHAR);
  $res = $sth->execute();
  if ($sth->rows >= 1) {
    $hash_ref = $sth->fetchrow_hashref;
    $res = $hash_ref->{'id'};
  } else {
    ## new repository to create
    $query = "INSERT INTO $table (id, $fieldname) VALUES ('', ?)";
    $sth = $dbh->prepare($query);
    $sth->bind_param(1, $value, SQL_VARCHAR);
    $res = $sth->execute();
    if (!$res) {
      $res = 0;
    } else {
      $res = $sth->{'mysql_insertid'};
    }
  }
    return $res;
}


sub db_add_record {
  my ($type, $commit_id, $repo, $dir, $file, $added, $removed) = @_;

  $dir_id = db_get_index('svn_dirs', 'dir', $dir);
  $file_id = db_get_index('svn_files', 'file', $file);

  if ($type eq 'A'){
    $type = "Add";
  }
  elsif ($type eq 'M'){
    $type = "Change";
  } 
  elsif ($type eq 'D'){
    $type = "Delete";
  }

  $query = "INSERT INTO svn_checkins (type, commitid, dirid, fileid, addedlines, removedlines)".
    "VALUES (?, ?, ?, ?, ?, ?)";
  if ($debug) {
    print STDERR $query, "\n";
  }
  $sth = $dbh->prepare($query);
  $sth->bind_param(1, $type, SQL_VARCHAR);
  $sth->bind_param(2, $commit_id, SQL_INTEGER);
  $sth->bind_param(3, $dir_id, SQL_INTEGER);
  $sth->bind_param(4, $file_id, SQL_INTEGER);
  $sth->bind_param(5, $added, SQL_INTEGER);
  $sth->bind_param(6, $removed, SQL_INTEGER);
  $res = $sth->execute();

  return $res;
}


sub db_get_commit {

  my ($group_id, $repo, $revision, $date, $uid, @desc) = @_;
  my ($query, $c, $res, $fulldesc);

  @desc_escaped = @desc;
  foreach(@desc_escaped) { s/\\/\\\\/g }

  $fulldesc = join('\n', @desc_escaped);
  $fulldesc = join("&amp;",split("&", $fulldesc));
  $fulldesc = join("&quot;",split("\"", $fulldesc));
  $fulldesc = join("&#39;",split("'", $fulldesc));
  $fulldesc = join("&gt;",split(">", $fulldesc));
  $fulldesc = join("&lt;",split("<", $fulldesc));

  #$uid = db_get_field('user','user_name', $who, 'user_id');
  if ( int $uid <= 0 ) {
    $uid = 100;
  }
  $repo_id = db_get_index('svn_repositories','repository', $repo);

  $query = "INSERT INTO svn_commits (group_id,repositoryid,revision,date,whoid,description) ".
    "VALUES (?, ?, ?, ?, ?, ?)";
  if ($debug) {
    print STDERR $query, "\n";
  }
  $sth = $dbh->prepare($query);
  $sth->bind_param(1, $group_id, SQL_INTEGER);
  $sth->bind_param(2, $repo_id, SQL_INTEGER);
  $sth->bind_param(3, $revision, SQL_INTEGER);
  $sth->bind_param(4, $date, SQL_INTEGER);
  $sth->bind_param(5, $uid, SQL_INTEGER);
  $sth->bind_param(6, $fulldesc, SQL_VARCHAR);
  $res = $sth->execute();
  if (!$res) {
    if ($debug) {
      print STDERR "\t res: ",  $res, "\n";
    }
    return 0;
  } else {
    # Update last_access_date
    session_store_access($uid);
    if ($debug) {
      print STDERR "\t size: ", $rows, "\n";
      print STDERR "\t created commit_id: ",  $sth->{'mysql_insertid'};
    }
  }
  return $sth->{'mysql_insertid'};
}


1;

