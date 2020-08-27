# Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
#
# This file is a part of Codendi.
#
# Codendi is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# Codendi is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Codendi. If not, see <http://www.gnu.org/licenses/>.
#

#
# Purpose:
#    This Perl include file mimics some of the fucntion in common/project/Group.class.php
#    to allow Perl scripts to handle checkins tables in codendi db


require $utils_path."/session.pl";

sub cvs_db_connect {
	my ($foo, $bar);

	# open up database include file and get the database variables
	open(FILE, $db_include) || die "Can't open $db_include: $!\n";
	while (<FILE>) {
		next if ( /^\s*\/\// );
		($foo, $bar) = split /=/;
		if ($foo) { eval $_ };
	}
	close(FILE);

	# connect to the database
	$dbvh ||= DBI->connect("DBI:mysql:$sys_dbname:$sys_dbhost", "$sys_dbuser", "$sys_dbpasswd");
}


sub db_get_field {
  local ($table, $fieldname, $value, $retfieldname) = @_;
  my ($query, $res);
  $query = "SELECT $retfieldname  FROM $table WHERE $fieldname='$value'";
  $sth = $dbh->prepare($query);
  $res = $sth->execute();
  if ($sth->rows >= 1) {
    $hash_ref = $sth->fetchrow_hashref;
    $result = $hash_ref->{$retfieldname};
  } else {
    print STDERR "user d'nt exist\n";
    $result = '0';
  }
  return $result;
}

sub db_get_index {
  local ($table, $fieldname, $value) = @_;
  if (!$value) { $value=""; }
  my ($query, $res);
  $debug = 0;
  $query = "SELECT id  FROM $table WHERE $fieldname = ?";
  if ($debug) {
    print STDERR $query, "\n";
  }
  $sth = $dbh->prepare($query);
  $res = $sth->execute($value);
  if ($sth->rows >= 1) {
    $hash_ref = $sth->fetchrow_hashref;
    $res = $hash_ref->{'id'};
  } else {
    ## new repository to create
    $query = "INSERT INTO $table (id, $fieldname) VALUES ('', ?)";
    $sth = $dbh->prepare($query);
    $res = $sth->execute($value);
    if (!$res) {
      $res = 0;
    } else {
      $res = $sth->{'mysql_insertid'};
    }
  }
    return $res;
}


sub db_add_record {
  local ($commit_id, $who, $repo, $when, $dir, $file, $type, $version, $branch, ,$added, $removed, @desc) = @_;

  $fulldesc = join('\n', @desc);
  $fulldesc = join("&amp;",split("&", $fulldesc));
  $fulldesc = join("&quot;",split("\"", $fulldesc));
  $fulldesc = join("&#39;",split("'", $fulldesc));
  $fulldesc = join("&gt;",split(">", $fulldesc));
  $fulldesc = join("&lt;",split("<", $fulldesc));
  $repo_id = db_get_index('cvs_repositories','repository', $repo);
  $who_id = db_get_field('user','user_name', $who, 'user_id');
  $desc_id = db_get_index('cvs_descs', 'description', $fulldesc);
  $dir_id = db_get_index('cvs_dirs', 'dir', $dir);
  $file_id = db_get_index('cvs_files', 'file', $file);
  $branch_id = db_get_index('cvs_branches','branch',$branch);

  if ($type eq 'a'){
    $type = "Add";
  }
  if ($type eq 'c'){
    $type = "Change";
  }
  if ($type eq 'r'){
    $type = "Remove";
  }
  $query = "INSERT INTO cvs_checkins (type, whoid, repositoryid, dirid, fileid, revision, branchid, descid, commitid, addedlines, removedlines)".
    "VALUES ('$type', '$who_id', '$repo_id','$dir_id','$file_id','$version','$branch_id','$desc_id', '$commit_id', '$added', '$removed')";
  if ($debug) {
    print STDERR $query, "\n";
  }
  $sth = $dbh->prepare($query);
  $res = $sth->execute();

  return $res;
}


sub db_get_commit {

  local ($debug) = @_;
  my ($query, $uid, $c, $res);
  my ($who) = $ENV{'SUDO_USER'};
  $uid = db_get_field('user','user_name', $who, 'user_id');

  $query = "INSERT INTO cvs_commits (whoid) VALUES ('$uid')";
  if ($debug) {
    print STDERR $query, "\n";
  }
  $sth = $dbh->prepare($query);
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
