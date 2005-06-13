#!/usr/bin/perl

# create the field permissions for all existing trackers including template trackers

use DBI;

$root_path = "../../";
require $root_path."utils/include.pl";

&db_connect;

sub insert_field_permissions {

my ($query, $c, $q, $d, $e);


  $query = "SELECT group_artifact_id, allow_anon, is_public FROM artifact_group_list";

  $c = $dbh->prepare($query);
  $c->execute();
  while (my ($group_artifact_id, $allow_anon, $is_public) = $c->fetchrow()) {

    #first treat tracker permission
    if ($is_public == 0) {
      $access_group = 3; #project members only
    } else {
      $access_group = 1; #anonymous
    }

    $query = "INSERT INTO permissions VALUES ('TRACKER_ACCESS_FULL','$group_artifact_id',$access_group)";
    #print $query."\n";
    $d = $dbh->prepare($query);
    $d->execute();

    $query = "SELECT field_id,field_name FROM artifact_field WHERE group_artifact_id = $group_artifact_id order by field_id";
    $d = $dbh->prepare($query);
    $d->execute();

    while (my ($field_id,$field_name) = $d->fetchrow()) {

      if ($field_name ne 'submitted_by' && $field_name ne 'open_date' && $field_name ne 'artifact_id') {
	$q = "INSERT INTO permissions VALUES ";
	$q .= "('TRACKER_FIELD_SUBMIT','$group_artifact_id#$field_id',";
	if ($allow_anon == 0) {
	  $q .= "3)";
	} else {
	  $q .= "1)";
	}
	$q .= ",('TRACKER_FIELD_READ','$group_artifact_id#$field_id',1),('TRACKER_FIELD_UPDATE','$group_artifact_id#$field_id',16)";
      } else {
	$q = "INSERT INTO permissions VALUES ('TRACKER_FIELD_READ','$group_artifact_id#$field_id',1)";
      }
      #print $q."\n";
      $e = $dbh->prepare($q);
      $e->execute();
    }
  }
}


insert_field_permissions();

1;
