#!/usr/bin/perl -U

use DBI;



$root_path = $ENV{'SF_LOCAL_INC_PREFIX'} || "/home/";
require $root_path."httpd/SF/utils/include.pl";

&db_connect;

# Add a requestor to any group if none
## first update all the groups and users acl_ids to 0

sub check_old_used {
  my ($group_id, $table, $field) = @_;
  $query = "SELECT * FROM $table where $field = $group_id";
  $a = $dbh->prepare($query);
  $a->execute();
  if ($a->rows == 0) {
    return 0;
  } else {
    return 1;
  }
  

}


sub copyFields {
  my ($atid_dest, $tracker_name)= @_;

  ## get reference tracker with Name

  $sql = "select group_artifact_id FROM artifact_group_list WHERE group_id=100 AND name='".$tracker_name."'";
  $sth = $dbh->prepare($sql);
  $res = $sth->execute();
  $hash_ref = $sth->fetchrow_hashref;
  $tracker_id = $hash_ref->{'group_artifact_id'};
  ##
  ## Copy artifact_field records
  ##
  $sql='SELECT field_id,field_name,data_type,display_type,display_size,label,description,scope,required,empty_ok,keep_history,special,value_function,default_value '.
    'FROM artifact_field '.
      'WHERE group_artifact_id='.$tracker_id;
		
  ##echo $sql;
  $sth1 = $dbh->prepare($sql);
  $res = $sth1->execute();
	
  while ($field_array = $sth1->fetchrow_hashref) {
    $sql_insert = 'INSERT INTO artifact_field VALUES ('.$field_array->{'field_id'}.','.$atid_dest.',"'.$field_array->{'field_name'}.'",'.$field_array->{'data_type'}.
      ',"'.$field_array->{'display_type'}.'","'.$field_array->{'display_size'}.'","'.$field_array->{'label'}.
	'",'.$dbh->quote($field_array->{'description'}).',"'.$field_array->{'scope'}.'",'.$field_array->{'required'}.
	  ','.$field_array->{'empty_ok'}.','.$field_array->{'keep_history'}.','.$field_array->{'special'}.
	    ',"'.$field_array->{'value_function'}.'","'.$field_array->{'default_value'}.'")';
	    				  
    $sth = $dbh->prepare($sql_insert);
    $res_insert = $sth->execute();
    ##echo $sql_insert;
    if (!$res_insert || $sth->rows <= 0) {
      print stderr "Error during inserting artifact_field (" , $field_array->{'field_id'}, "-", $atid_dest, "-", $tracker_name, ")  \n";
      return false;
    }
  }				## while
	
  ##
  ## Copy artifact_field_usage records
  ##
  $sql='SELECT field_id,use_it,show_on_add,show_on_add_members,place '.
    'FROM artifact_field_usage '.
      'WHERE group_artifact_id='.$tracker_id;
		
  ##echo $sql;
		
  $sth1 = $dbh->prepare($sql);
  $res = $sth1->execute();
	
  while ($field_array = $sth1->fetchrow_hashref) {
	
    $place = ($field_array->{'place'} == ""?"null":$field_array->{'place'});
    $sql_insert = 'INSERT INTO artifact_field_usage VALUES ('.$field_array->{'field_id'}.','.$atid_dest.','.$field_array->{'use_it'}.
      ','.$field_array->{'show_on_add'}.','.$field_array->{'show_on_add_members'}.','.$place.')';
	    				  
    ##echo $sql_insert;
    $sth = $dbh->prepare($sql_insert);
    $res_insert = $sth->execute();
    if (!$res_insert || $sth->rows  <= 0) {
      print stderr "Error during inserting artifact_field_usage (" , $field_array->{'field_id'}, "-", $atid_dest, "-", $tracker_name, ")  \n";
      return false;
    }
  }				## while
		
  ##
  ## Copy artifact_field_value_list records
  ##
  $sql='SELECT field_id,value_id,value,description,order_id,status '.
    'FROM artifact_field_value_list '.
      'WHERE group_artifact_id='.$tracker_id;
		
  ##echo $sql;
		
  $sth1 = $dbh->prepare($sql);
  $res = $sth1->execute();
	
  while ($field_array = $sth1->fetchrow_hashref) {
	
    $sql_insert = 'INSERT INTO artifact_field_value_list VALUES ('.$field_array->{'field_id'}.','.$atid_dest.','.$field_array->{'value_id'}.
      ',"'.$field_array->{'value'}.'",'.$dbh->quote($field_array->{'description'}).','.$field_array->{'order_id'}.
	',"'.$field_array->{'status'}.'")';
	    				  
    ## echo $sql_insert;
    $sth = $dbh->prepare($sql_insert);
    $res_insert = $sth->execute();
    if (!$res_insert || $sth->rows <= 0) {
      print stderr "Error during inserting artifact_field_value_list (" , $field_array->{'field_id'}, "-", $atid_dest, "-", $tracker_name, "-", $sql_insert, ")  \n";
      return false;
    }
  }				## while
		
}

sub copyReports {
  my ($atid_dest, $tracker_name)= @_;

  $sql = "select group_artifact_id FROM artifact_group_list WHERE group_id=100 AND name='".$tracker_name."'";
  ##print stderr $sql, " to get ",$tracker_name," tracker_id\n";
  $sth = $dbh->prepare($sql);
  $res = $sth->execute();
  $hash_ref = $sth->fetchrow_hashref;
  $tracker_id = $hash_ref->{'group_artifact_id'};

  $sql='SELECT report_id,user_id,name,description,scope '.
    'FROM artifact_report '.
      'WHERE group_artifact_id='.$tracker_id;
		
  ##print stderr $sql, "\n";
		
  $sth1 = $dbh->prepare($sql);
  $res = $sth1->execute();
	
  while ($report_array = $sth1->fetchrow_hashref) {
    $sql_insert = 'INSERT INTO artifact_report (group_artifact_id,user_id,name,description,scope) VALUES ('.$atid_dest.','.$report_array->{'user_id'}.
      ',"'.$report_array->{'name'}.'",'.$dbh->quote($report_array->{'description'}).',"'.$report_array->{'scope'}.'")';
    ##echo $sql_insert;
    $sth = $dbh->prepare($sql_insert);
    $res_insert = $sth->execute();
    if (!$res_insert || $sth->rows  <= 0) {
      print stderr "Error during inserting artifact_report (", $report_array->{'field_id'}, "-", $atid_dest, " - ", $tracker_name, " - ", $sql_insert,")  \n";
      return false;
    }
    $report_id = $sth->{'mysql_insertid'};

    ##
    ## Copy artifact_report_field records
    ##
    $sql_fields='SELECT field_name,show_on_query,show_on_result,place_query,place_result,col_width '.
      'FROM artifact_report_field '.
	'WHERE report_id='.$tracker_id;
			
    ##print stderr $sql_fields, "\n";
    $sth2 = $dbh->prepare($sql_fields);
    $res_fields = $sth2->execute();
	
    while ($field_array = $sth2->fetchrow_hashref) {
      $show_on_query = ($field_array->{'show_on_query'} == ""?"null":$field_array->{'show_on_query'});
      $show_on_result = ($field_array->{'show_on_result'} == ""?"null":$field_array->{'show_on_result'});
      $place_query = ($field_array->{'place_query'} == ""?"null":$field_array->{'place_query'});
      $place_result = ($field_array->{'place_result'} == ""?"null":$field_array->{'place_result'});
      $col_width = ($field_array->{'col_width'} == ""?"null":$field_array->{'col_width'});

      $sql_insert = 'INSERT INTO artifact_report_field VALUES ('.$report_id.',"'.$field_array->{'field_name'}.
	'",'.$show_on_query.','.$show_on_result.','.$place_query.
	  ','.$place_result.','.$col_width.')';
		    				  
      ## print stderr $sql_insert, "\n";
      $sth = $dbh->prepare($sql_insert);
      $res_insert = $sth->execute();
      if (!$res_insert || $sth->rows  <= 0) {
	print stderr "Error during inserting artifact_report_field (", $field_array->{'field_id'}, "-", $atid_dest, "-", $tracker_name, ")  \n";
	return false;
      }
    }
  }
}

sub copyNotificationDefaults {
  my ($artifact_id, $tracker_name)= @_;
  $sql = "insert into artifact_notification_event ".
    "select event_id,".$artifact_id.",event_label ,short_description,description,rank ".
      "from artifact_notification_event_default";
			   
  $sth = $dbh->prepare($sql);
  $res_insert = $sth->execute();
	
		
  if (!$res_insert || $sth->rows <= 0) {
    print stderr "Fail to copy notification event", "-", $atid_dest, "-", $tracker_name, ")  \n";
  }
  $sql = "insert into artifact_notification_role ".
    "select role_id,".$artifact_id.",role_label  ,short_description,description,rank ".
      "from artifact_notification_role_default";
  $sth = $dbh->prepare($sql);
  $res_insert = $sth->execute();
	
		
  if (!$res_insert || $sth->rows <= 0) {
    print stderr "Fail to copy notification role", "-", $atid_dest, "-", $tracker_name, ")  \n";
  }

}

sub create_tracker {
  my ($group_id, $tracker_name, $itemname, $description, $isPublic, $allowsAnon) = @_;

  ## first check none exists for this group
    $query = "SELECT * from artifact_group_list where group_id=$group_id AND name='$tracker_name'";
    $ath = $dbh->prepare($query);
   $ath->execute();
  if ($ath->rows > 0) {
    print stderr "Tracker ", $tracker_name, " already exists (", $query, ")\n";
    return;
  }

  ## then, we create a new ArtifactType into artifact_group_list
		
  $sql="INSERT INTO 
			artifact_group_list 
			(group_id,
			name,
			description,
			item_name,
			is_public,
			allow_anon) 
			VALUES 
			('". $group_id ."',
			'". $tracker_name ."',
			". $dbh->quote($description) .",
			'". $itemname ."',
			".$isPublic.",".$allowsAnon.")";
		##print stderr "Creating ", $tracker_name, " tracker with \n",  $sql, "\n";
		##$res = db_query($sql);

  $sth = $dbh->prepare($sql);
  $res = $sth->execute();
                ## get the artifact_id from inserted_row
  if (!$res) {
    print stderr "Could not create ", $tracker_name, " tracker for group_id ", $group_id, "\n";
    return;
  } else {
    $artifact_id = $sth->{'mysql_insertid'};
  }

  ## now copy fields from template for new tracker
  copyFields($artifact_id, $tracker_name);

  ## then copy the reports information
  copyReports($artifact_id, $tracker_name);

  ## Copy artifact_notification_event and artifact_notification_role
  copyNotificationDefaults($artifact_id, $tracker_name);

}


sub init_trackers_db {

  my ($query, $c, $res);


  ## then insert groups
  $query = "select distinct group_id,unix_group_name from groups";
  $c = $dbh->prepare($query);
  $c->execute();

  while (my ($group_id, $group_name) = $c->fetchrow()) {

    ## look if activate_old_bugs is needed
    $activate_bugs = check_old_used($group_id, 'bug', 'group_id');
    $activate_srs = check_old_used($group_id, 'support', 'group_id');
    $activate_tasks = check_old_used($group_id, 'project_task', 'group_project_id');
 
     $query = "UPDATE groups SET activate_old_bug=$activate_bugs, activate_old_task=$activate_tasks, activate_old_sr=$activate_srs, use_trackers='1', use_bugs='0', use_support='0', use_pm='0' WHERE group_id=".$group_id;
      $c2 = $dbh->prepare($query);
      $c2->execute();
     ## print stderr $query, "\n";

    if ($activate_srs == 0) {
      create_tracker($group_id, 'Supports', 'SR', 'Support Requests', '1', '1');
    }
    if ($activate_bugs == 0) {
      create_tracker($group_id, 'Bugs', 'bug', 'Bugs Tracker', '1', '0');
    }
    if ($activate_tasks == 0) {
      create_tracker($group_id, 'Tasks', 'task', 'Tasks Manager', '1', '0');
    }
  }
}

init_trackers_db();

1;
