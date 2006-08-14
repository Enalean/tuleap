#!/usr/bin/perl
#
# $Id$
#
# Add default permissions to fields that have no permissions in at least 10 trackers.
# 10 trackers? This is to make sure that we don't add permissions to a field that was
# intentionnaly left without any permission.
#
# Default permission is: 
# - anyone can read,
# - nobody can submit -> to avoid change in submission interface
# - only members can update.
#

use DBI;

$root_path = "../../";
require $root_path."utils/include.pl";

$dbh = &db_connect();

my %field_without_perm_obj_id;
my %object_id;

print "Add permissions for fields that have no permissions\n";
print "This script is only useful for CodeX servers that have upgraded from CodeX 2.4, so it will have no effect for new CodeX servers installed after October 2005.\n";
print "Continue? (y/n) [y] : ";
if (<> =~ /^n/) {exit;}

print "Reading permissions for all fields, please wait...\n";

$query = "SELECT agl.group_artifact_id, agl.name, agl.group_id, af.field_id, af.field_name, af.label FROM artifact_group_list agl, artifact_field af WHERE af.group_artifact_id = agl.group_artifact_id ORDER BY agl.group_id,agl.group_artifact_id";
#print $query."\n";
$d = $dbh->prepare($query);
$d->execute();

while (my ($tracker_id,$tracker_name,$group_id,$field_id,$field_name,$field_label) = $d->fetchrow()) {
  $perm_query="SELECT * from permissions WHERE object_id='$tracker_id#$field_id'";
  $a = $dbh->prepare($perm_query);
  $a->execute();
  if ($a->rows == 0) {
    $field_without_perm_obj_id{$field_name}{$tracker_id}="$tracker_id#$field_id";
  }
}


print "Updating permissions, please wait...\n";

my $field_without_permission_exist=0;
my %field_without_permission;

foreach my $field_name (keys %field_without_perm_obj_id) {
  my $nbtrackers=0;
  foreach my $tracker_id (keys %{ $field_without_perm_obj_id{"$field_name"}}) {
    $nbtrackers++; # there is certainly a simpler way to count...
  }

  if ($nbtrackers >= 10) {
    print "Adding permissions of field $field_name for $nbtrackers trackers\n";
    my $tracker_id = 0;
    foreach $tracker_id (keys %{ $field_without_perm_obj_id{"$field_name"}}) {
      $dbh->do("INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','".$field_without_perm_obj_id{$field_name}{$tracker_id}."',1)");
      $dbh->do("INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','".$field_without_perm_obj_id{$field_name}{$tracker_id}."',3)");
      #$dbh->do("INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','".$field_without_perm_obj_id{$field_name}{$tracker_id}."',2)");
    }
  } else {
    $field_without_permission{$field_name}=$nbtrackers;
    $field_without_permission_exist=1;
  }
}
if ($field_without_permission_exist) {
  print "Please note that a few fields still have no permissions because they are not frequent enough.\nThis is not an issue since project admins might want to delete all permissions on some fields\n";
  print "List of fields without permissions:\n";
  foreach my $field_name (keys %field_without_permission) {
    print "- $field_name (".$field_without_permission{$field_name}.")\n";
  }
}
