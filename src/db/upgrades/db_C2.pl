#!/usr/bin/perl

# create the field permissions for all existing trackers including template trackers

use DBI;

$root_path = "../../";
require $root_path."utils/include.pl";

&db_connect;

sub insert_field_permissions {

my ($query, $c, $q, $d, $e);


  $query = "SELECT group_artifact_id, allow_anon, is_public FROM artifact_group_list WHERE group_artifact_id > 100";

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

    $query = "SELECT af.field_id,af.field_name,afu.show_on_add,afu.show_on_add_members FROM artifact_field af,artifact_field_usage afu WHERE af.group_artifact_id = afu.group_artifact_id AND af.field_id =afu.field_id AND af.group_artifact_id = $group_artifact_id order by field_id";
    $d = $dbh->prepare($query);
    $d->execute();

    while (my ($field_id,$field_name,$show_on_add,$show_on_add_members) = $d->fetchrow()) {

      if ($field_name ne 'submitted_by' && $field_name ne 'open_date' && $field_name ne 'artifact_id') {
	$q = "INSERT INTO permissions VALUES ";
	if ($allow_anon == 0) {
	  if ($show_on_add) {
	    $q .= "('TRACKER_FIELD_SUBMIT','$group_artifact_id#$field_id',2),";
	  } elsif ($show_on_add_members) {
	    $q .= "('TRACKER_FIELD_SUBMIT','$group_artifact_id#$field_id',3),";
	  }
	} else {
	  if ($show_on_add) {
	    $q .= "('TRACKER_FIELD_SUBMIT','$group_artifact_id#$field_id',1),";
	  } elsif ($show_on_add_members) {
	    $q .= "('TRACKER_FIELD_SUBMIT','$group_artifact_id#$field_id',3),";
	  }
	}
	$q .= "('TRACKER_FIELD_READ','$group_artifact_id#$field_id',1),('TRACKER_FIELD_UPDATE','$group_artifact_id#$field_id',16)";
      } else {
	$q = "INSERT INTO permissions VALUES ('TRACKER_FIELD_READ','$group_artifact_id#$field_id',1)";
      }
      #print $q."\n";
      $e = $dbh->prepare($q);
      $e->execute();
    }
  }
}


sub delete_unused_columns {
my ($query, $c, $q, $d, $e);


  $query = "ALTER TABLE artifact_group_list DROP is_public";
  $c = $dbh->prepare($query);
  $c->execute();
  $query = "ALTER TABLE artifact_group_list DROP allow_anon";
  $c = $dbh->prepare($query);
  $c->execute();
  $query = "ALTER TABLE artifact_field_usage DROP show_on_add";
  $c = $dbh->prepare($query);
  $c->execute();
  $query = "ALTER TABLE artifact_field_usage DROP show_on_add_members";
  $c = $dbh->prepare($query);
  $c->execute();
}


insert_field_permissions();
delete_unused_columns();

1;
