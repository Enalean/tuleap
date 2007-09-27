#!/usr/bin/perl -U

use DBI;

$root_path = $ENV{'SF_LOCAL_INC_PREFIX'} || "/home/httpd/SF/";
#$root_path = "../../";
require $root_path."utils/include.pl";

&db_connect;

sub migrateSeverity {

  ## get reference tracker with Name

  $sql='SELECT group_artifact_id,item_name '.
    'FROM artifact_group_list ';
		
  ##echo $sql;
  $sth1 = $dbh->prepare($sql);
  $res = $sth1->execute();
	
  while ($field_array = $sth1->fetchrow_hashref) {
  	if ( $field_array->{'item_name'} eq "bug" ) {
	    $sql_update = "UPDATE artifact_field_value_list SET status = 'A' where field_id = 8 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute()or die "Error during updating artifact_field_value_list (".$field_array->{'group_artifact_id'}.")\n";
	}

  	if ( $field_array->{'item_name'} eq "task" ) {
  		
  		## Delete Field 3 (Priority)
	    $sql_delete = "DELETE FROM artifact_field WHERE field_id = 3 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_delete);
	    $sth->execute() or die "Error during deleting artifact_field (3,".$field_array->{'group_artifact_id'}.") :".$sth->errstr."\n";
	      		
  		## Delete Field values list of field 3 (Priority)
	    $sql_delete = "DELETE FROM artifact_field_value_list WHERE field_id = 3 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_delete);
	    $sth->execute() or die "Error during deleting artifact_field_value_list (3,".$field_array->{'group_artifact_id'}.") :".$sth->errstr."\n";
	      		
  		## Delete Field values usage of field 3 (Priority)
	    $sql_delete = "DELETE FROM artifact_field_usage WHERE field_id = 3 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_delete);
	    $sth->execute() or die "Error during deleting artifact_field_usage (3,".$field_array->{'group_artifact_id'}.") :".$sth->errstr."\n";
	     
	    ## Update artifact field values of field 14 (Severity)
	    $sql_update = "UPDATE artifact_field_value_list SET status = 'A' where field_id = 14 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (".$field_array->{'group_artifact_id'}.")\n";
	    
	    ## Update artifact field usage of field 14 (Severity)
	    $sql_update = "UPDATE artifact_field_usage SET use_it = 1, show_on_add = 1,  show_on_add_members = 1, place = 30 where field_id = 14 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (".$field_array->{'group_artifact_id'}.")\n";
	    
	    ## Update the description and the label of severity field (label Priority)
	    $sql_update = "UPDATE artifact_field SET label = 'Priority', description = 'How quickly the artifact must be completed' where field_id = 14 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute()or die "Error during updating artifact_field (".$field_array->{'group_artifact_id'}.")\n";
	    
	    ## Update the description of severity field values 1,5 and 9
	    $sql_update = "UPDATE artifact_field_value_list SET value = '1 - Lowest' where field_id = 14 AND value_id = 1 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (1,".$field_array->{'group_artifact_id'}.")\n";
	    
	    $sql_update = "UPDATE artifact_field_value_list SET value = '5 - Medium' where field_id = 14 AND value_id = 5 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (5,".$field_array->{'group_artifact_id'}.")\n";
	    
	    $sql_update = "UPDATE artifact_field_value_list SET value = '9 - Highest' where field_id = 14 AND value_id = 9 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (1,".$field_array->{'group_artifact_id'}.")\n";
	    
	}

  	if ( $field_array->{'item_name'} eq "SR" ) {
  		## Delete Field 8 (Priority)
	    $sql_delete = "DELETE FROM artifact_field WHERE field_id = 8 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_delete);
	    $sth->execute() or die "Error during deleting artifact_field (3,".$field_array->{'group_artifact_id'}.") :".$sth->errstr."\n";
	      		
  		## Delete Field values list of field 8 (Priority)
	    $sql_delete = "DELETE FROM artifact_field_value_list WHERE field_id = 8 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_delete);
	    $sth->execute() or die "Error during deleting artifact_field_value_list (3,".$field_array->{'group_artifact_id'}.") :".$sth->errstr."\n";
	      		
  		## Delete Field values usage of field 8 (Priority)
	    $sql_delete = "DELETE FROM artifact_field_usage WHERE field_id = 8 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_delete);
	    $sth->execute() or die "Error during deleting artifact_field_usage (3,".$field_array->{'group_artifact_id'}.") :".$sth->errstr."\n";
	     
	    ## Update artifact field values of field 11 (Severity)
	    $sql_update = "UPDATE artifact_field_value_list SET status = 'A' where field_id = 11 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (".$field_array->{'group_artifact_id'}.")\n";

	    ## Update the description and the label of severity field (label Priority)
	    $sql_update = "UPDATE artifact_field SET label = 'Priority', description = 'How quickly the artifact must be completed' where field_id = 11 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	     $sth->execute() or die "Error during updating artifact_field (".$field_array->{'group_artifact_id'}.")\n";
	    
	    ## Update artifact field usage of field 11 (Severity)
	    $sql_update = "UPDATE artifact_field_usage SET use_it = 1, show_on_add = 1,  show_on_add_members = 1, place = 40 where field_id = 11 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (".$field_array->{'group_artifact_id'}.")\n";
	    
	    ## Update the description of severity field values 1,5 and 9
	    $sql_update = "UPDATE artifact_field_value_list SET value = '1 - Lowest' where field_id = 11 AND value_id = 1 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (1,".$field_array->{'group_artifact_id'}.")\n";
	    
	    $sql_update = "UPDATE artifact_field_value_list SET value = '5 - Medium' where field_id = 11 AND value_id = 5 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (5,".$field_array->{'group_artifact_id'}.")\n";
	    
	    $sql_update = "UPDATE artifact_field_value_list SET value = '9 - Highest' where field_id = 11 AND value_id = 9 AND group_artifact_id = ".$field_array->{'group_artifact_id'};
	    				  
	    $sth = $dbh->prepare($sql_update);
	    $sth->execute() or die "Error during updating artifact_field_value_list (1,".$field_array->{'group_artifact_id'}.")\n";
	}
  }	## while

  ## Special case for group_artifact_id = 4
  $sql_update = "UPDATE artifact_field_value_list SET status = 'A' where field_id = 7 AND group_artifact_id = 4";
					  
  $sth = $dbh->prepare($sql_update);
  $sth->execute() or die "Error during updating artifact_field_value_list (4)\n";
	
}

migrateSeverity();

1;
