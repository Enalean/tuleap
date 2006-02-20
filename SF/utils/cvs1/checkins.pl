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
#    This Perl include file mimics some of the fucntion in www/include/Group.class
#    to allow Perl scripts to handle chackins tables in codex sourceforge db

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
	$dbvh ||= DBI->connect("DBI:mysql:sourceforge:$sys_dbhost", "$sys_dbuser", "$sys_dbpasswd");
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
  my ($query, $res);
  $debug = 0;
  $query = "SELECT id  FROM $table WHERE $fieldname=\"$value\"";
  if ($debug) {
    print STDERR $query, "\n";
  }
  $sth = $dbh->prepare($query);
  $res = $sth->execute();
  if ($sth->rows >= 1) {
    $hash_ref = $sth->fetchrow_hashref;
    $res = $hash_ref->{'id'};
  } else {
    ## new repository to create
    $query = "INSERT INTO $table (id, $fieldname) VALUES ('', '$value')";
    $sth = $dbh->prepare($query);
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
  my ($who) = $ENV{'USER'};
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
    if ($debug) {
      print STDERR "\t size: ", $rows, "\n";
      print STDERR "\t created commit_id: ",  $sth->{'mysql_insertid'};
    }
  }
  return $sth->{'mysql_insertid'};
}


1;
