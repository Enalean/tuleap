#!/usr/bin/perl -U
#
# Fill new columns artifacts_opened and artifacts_closed (from table stats_project) with existing stats
#

use DBI;
use Time::Local;
use POSIX qw( strftime );

$root_path = "../../";
require $root_path."utils/include.pl";

# load local.inc variables
&load_local_config();


&db_connect;



sub init_artifact_history {

  my ($query, $c, $res);

  if (!$sys_activate_tracker) {
    # no need to update values for artifacts!
    print "DONE... Your system is up to date\n";
    exit 0;
  }

  # Before calling this script, the artifacts_opened and artifacts_closed columns should be created
  $query = "DESCRIBE stats_project";
  $c = $dbh->prepare($query);
  $c->execute();
  $updated=0;
  while (my ($field,$else) =  $c->fetchrow()) {
    if ($field=~ m/artifacts_closed/) {
      $updated=1;
      last;
    }
  }
  if (!$updated) {
    print "Please apply SQL patch before executing this script!\n";
    exit 1;
  }


  # Now loop on each day when stats where collected (normally, every day since installation...)
  $query="select month, day, group_id from stats_project where month> 200310";
  $c = $dbh->prepare($query);
  $c->execute();
  $count=0;
  while (my ($monthyear, $day,$group_id) = $c->fetchrow()) {
    $count++;
    # Check if an artifact was closed or opened this day
    # Convert date
    $monthyear=~/(....)(..)/;
    my ($year,$month)=($1,$2);
    $day_begin = timegm(0,0,0,$day,$month-1,$year);
    $day_end = timegm(59,59,23,$day,$month-1,$year);

    if (!($count % 200)) { print "$count\tentries processed\n";}

    ## artifacts_opened
    $sql="SELECT COUNT(artifact.artifact_id) 
        FROM artifact_group_list, artifact
	WHERE ( artifact.open_date > $day_begin 
                AND artifact.open_date < $day_end 
                AND artifact_group_list.group_artifact_id = artifact.group_artifact_id 
                AND artifact_group_list.group_id=$group_id )
	GROUP BY artifact_group_list.group_id";
    $c2 = $dbh->prepare($sql);
    $c2->execute();
    ($nb_art) = $c2->fetchrow();
    if ($nb_art>0) {
      #print "$year $month $day $group_id : $nb_art art opened\n";
      $sql3="UPDATE stats_project SET artifacts_opened=$nb_art WHERE month=$monthyear AND day=$day AND group_id=$group_id";
      $c3 = $dbh->prepare($sql3);
      $c3->execute();
    }


    ## artifacts_closed
    $sql4="SELECT COUNT(artifact.artifact_id) 
        FROM artifact_group_list, artifact
	WHERE ( artifact.close_date > $day_begin 
                AND artifact.close_date < $day_end 
                AND artifact_group_list.group_artifact_id = artifact.group_artifact_id 
                AND artifact_group_list.group_id=$group_id )
	GROUP BY artifact_group_list.group_id";
    $c4 = $dbh->prepare($sql4);
    $c4->execute();
    ($nb_art) = $c4->fetchrow();
    if ($nb_art>0) {
      #print "$year $month $day $group_id : $nb_art art closed\n";
      $sql5="UPDATE stats_project SET artifacts_closed=$nb_art WHERE month=$monthyear AND day=$day AND group_id=$group_id";
      $c5 = $dbh->prepare($sql5);
      $c5->execute();
    }
  }
  print "Database successfully updated\n";

}


#print "Checking variables: SF_LOCAL_INC_PREFIX=".$ENV{'SF_LOCAL_INC_PREFIX'}."\n";
#print "                    sys_activate_tracker=$sys_activate_tracker\n";
#print "                    sys_default_domain=$sys_default_domain\n";

init_artifact_history();

1;
