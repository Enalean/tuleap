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
#    This Perl include file stores Subversion commit information into the 
#   Codendi Database
#


require $utils_path."/session.pl";

sub db_get_field {
  my ($table, $fieldname, $value, $retfieldname) = @_;
  my ($query, $res);
  $query = "SELECT $retfieldname  FROM $table WHERE $fieldname='$value'";
  $sth = $dbh->prepare($query);
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
    $query = sprintf "INSERT INTO $table (id, $fieldname) VALUES ('', %s)",
                $dbh->quote($value);
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
    "VALUES ('$type', '$commit_id','$dir_id','$file_id', '$added', '$removed')";
  if ($debug) {
    print STDERR $query, "\n";
  }
  $sth = $dbh->prepare($query);
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
    "VALUES ('$group_id','$repo_id','$revision','$date','$uid','$fulldesc')";
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

sub db_update_description {

  my ($group_id, $repo, $revision, @desc) = @_;
  my ($query, $c, $res, $fulldesc);

  @desc_escaped = @desc;
  foreach(@desc_escaped) { s/\\/\\\\/g }

  $fulldesc = join('\n', @desc_escaped);
  $fulldesc = join("&amp;",split("&", $fulldesc));
  $fulldesc = join("&quot;",split("\"", $fulldesc));
  $fulldesc = join("&#39;",split("'", $fulldesc));
  $fulldesc = join("&gt;",split(">", $fulldesc));
  $fulldesc = join("&lt;",split("<", $fulldesc));

  $repo_id = db_get_index('svn_repositories','repository', $repo);

  $query = "UPDATE svn_commits SET description='$fulldesc'".
           "WHERE group_id='$group_id' AND repositoryid='$repo_id' AND revision='$revision'";
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
  }
}

1;

