#!/usr/bin/perl

#add 'Last Modified On' system field in all active trackers , as  a special and active field

use DBI;

$root_path = "../../";
require $root_path."utils/include.pl";

&db_connect;

$query = "SELECT group_artifact_id FROM artifact_group_list WHERE status = 'A' AND group_artifact_id <> 100";
$result = $dbh->prepare($query);
$result->execute();

while ( ($atid) = $result -> fetchrow_array ) {

	#extract latest field_id in this tracker
	$sql1 = "SELECT MAX(field_id) FROM artifact_field WHERE group_artifact_id = '$atid'";
	$res1 = $dbh->prepare($sql1);
	$res1->execute();
	while ( ($max) = $res1 -> fetchrow_array ) {
		$field_id = $max + 1;
	}

	#extract default field set id
	$sql2 = "SELECT MIN(field_set_id) FROM artifact_field_set WHERE group_artifact_id = '$atid'";
	$res2 = $dbh->prepare($sql2);
	$res2->execute();
	while ( ($fsid) = $res2 -> fetchrow_array ) {
		$field_set_id = $fsid;
	}

	#add new field to 'artifact_field' table
	$sql3 = "INSERT INTO artifact_field (field_id , group_artifact_id , field_set_id , field_name, data_type , display_type , label , description , required , empty_ok , keep_history , special) VALUES ('$field_id' , '$atid' , '$field_set_id' , 'last_update_date' , 4 , 'DF' , 'Last Modified On' , 'Date and time of the latest modification in an artifact' , 0 , 0 , 0 , 1)";
	$dbh->do($sql3) or die "Error : " . $dbh->errstr();

	#add field properties in 'artifact_field_usage' table
	$sql4 = "INSERT INTO artifact_field_usage (group_artifact_id , field_id , use_it , place) VALUES ('$atid' , '$field_id' , 1 , 0)";
	$dbh->do($sql4) or die "Error : " .$dbh->errstr();

	#define default permissions (all_users: Read Only)
	$object_id = $atid."#".$field_id;
	$sql5 = "INSERT INTO permissions (permission_type , object_id , ugroup_id) VALUES ('TRACKER_FIELD_READ' , '$object_id' , 1)";
	$dbh->do($sql5) or die "Error : ".$dbh->errstr();

}

$dbh->disconnect;

1;
