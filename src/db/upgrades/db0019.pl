#!/usr/bin/perl

use DBI;



#$root_path = $ENV{'SF_LOCAL_INC_PREFIX'} || "/home/";
$root_path = "../../";
require $root_path."utils/include.pl";

&db_connect;


sub get_current_value {
  my ($artifact_id, $field_name) = @_;
  
  my ($query, $c, $d, $current_value);

  if ($field_name eq 'summary' || $field_name eq 'status_id' || $field_name eq 'severity') {
    $query = "SELECT $field_name FROM artifact WHERE artifact_id=$artifact_id";
    $c = $dbh->prepare($query);
    $c->execute();
    $current_value = $c->fetchrow();
    return $current_value;
  } else {
    $query = "SELECT af.field_id,af.data_type,af.display_type,af.value_function "
      ."FROM artifact,artifact_field AS af "
      ."WHERE artifact.group_artifact_id = af.group_artifact_id "
	."AND artifact.artifact_id = $artifact_id "
	  ."AND af.field_name = '".$field_name."'";
    print $query."\n";
    $c = $dbh->prepare($query);
    $c->execute();
    my ($field_id,$data_type,$display_type,$value_function) = $c->fetchrow();
    print '-> $field_id='.$field_id .', $data_type='. $data_type.', $display_type='.$display_type.', $value_function='.$value_function."\n";
    
    $query = "SELECT valueInt,valueText,valueFloat,valueDate from artifact_field_value where field_id = $field_id and artifact_id = $artifact_id";
    print $query."\n";
    $c = $dbh->prepare($query);
    $c->execute();
    while (my ($valueInt,$valueText,$valueFloat,$valueDate) = $c->fetchrow()) {
      print '-> $valueInt='.$valueInt.', $valueText='.$valueText.', $valueFloat='.$valueFloat.', $valueDate='.$valueDate."\n";
      if ($current_value ne '') {
	$current_value  .= ',';
      }
      if ($data_type == 1) {
	return $valueText;
      } elsif (($data_type == 2) || ($data_type == 5)) {
	if ($display_type eq 'MB') {
	  if (($data_type == 5) || 
	     (($data_type == 2) && ($value_function ne ''))) {
	    ## unfortunately there are also fields with data_type = 2 for int which contain users.
	    ## therefore test the value_function which contains in such cases values as 'group_members', etc.
	    $query = "SELECT user_name FROM user WHERE user_id=$valueInt";
	  } else {
	    $query = "SELECT value FROM artifact_field_value_list where value_id=$valueInt";
	  }
	  print $query."\n";
	  $d = $dbh->prepare($query);
	  $d->execute();
	  if ($d->rows > 0) {
	    my ($label) = $d->fetchrow();
	    $current_value .= $label;
	  } else {
	    $current_value .= $valueInt;
	  }
	} else {
	  return $valueInt;
	}
      } elsif ($data_type == 3) {
	return $valueFloat;
      } elsif ($data_type == 4) {
	return $valueDate;
      }
    }
  }
  return $current_value;
}

sub update_new_value { 
  my ($new_value,$artifact_id,$field_name,$date) = @_;
  my ($query, $c);
  $query = "UPDATE artifact_history SET new_value='".$new_value."' WHERE artifact_id=$artifact_id AND field_name='".$field_name."' AND date = $date";
  print $query."\n";
  $c = $dbh->prepare($query);
  $c->execute();
}

sub fill_new_values_db {
  
  my ($query, $c, $d, $e, $f, $i);
  
  
  ## search for all artifacts in the history
  $query = "select distinct artifact_id from artifact_history";
  $c = $dbh->prepare($query);
  $c->execute();
  
  while (my ($artifact_id) = $c->fetchrow()) {
    
    $query = "SELECT field_name,count(*) AS count FROM artifact_history WHERE artifact_id=$artifact_id GROUP BY field_name";
    print "\n\n+++++   ".$query."\n";
    $d = $dbh->prepare($query);
    $d->execute();
    
    while (my ($field_name, $count) = $d->fetchrow()) {
      ## work only for fields other the "attachment" and "cc" that exist only since the new_value column has been added
      if ($field_name ne 'attachment' && $field_name ne 'cc' && $field_name ne 'details') {
	print "\n".'+++   $field_name='.$field_name.', $count='.$count."\n\n";
	if ($count == 1) {
	  $query = "SELECT new_value,date FROM artifact_history WHERE artifact_id=$artifact_id AND field_name='".$field_name."'";
	  print $query."\n";
	  $e = $dbh->prepare($query);
	  $e->execute();
	  my ($new_value,$date) = $e->fetchrow();
	  if ($new_value eq '') {
	    $current_value = get_current_value ($artifact_id, $field_name);
	    update_new_value($current_value,$artifact_id,$field_name,$date);
	  }
	} else {
	  $query = "SELECT old_value,new_value,date FROM artifact_history WHERE artifact_id=$artifact_id AND field_name = '".$field_name."' ORDER BY date DESC";
	  print $query."\n";
	  $f = $dbh->prepare($query);
	  $f->execute();
	  $i = 0;
	  while (my ($old_value, $new_value, $date) = $f->fetchrow()) {

	    $i+=1;
	    if ($i == 1) {
	      if ($new_value eq '') {
		$current_value = get_current_value ($artifact_id, $field_name);
		update_new_value($current_value,$artifact_id,$field_name,$date);
	      }
	    } else {
	      if ($new_value eq '') {
		update_new_value($keep_value,$artifact_id,$field_name,$date);
	      }
	    }
	    $keep_value = $old_value;
	  }
	}
      }
    }
  }
}



fill_new_values_db();

1;
