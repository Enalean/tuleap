#!/usr/bin/perl -U

use DBI;

$root_path = "../../";
require $root_path."utils/include.pl";

# load local.inc variables
&load_local_config();


&db_connect;


sub check_homepage {
  my ($homepage,$unix_group_name,$sys_default_domain) = @_;
  if (lc($homepage) eq "$unix_group_name.$sys_default_domain") {
    return "http://$unix_group_name.$sys_default_domain";
  } else {
    return "http://$homepage";
  }
}


sub createService {
  my ($group_id,$label,$description,$short_name,$link,$is_active,$is_used,$scope,$rank) = @_;
  $sql = "INSERT INTO service (group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES ($group_id,'$label','$description','$short_name','$link',$is_active,$is_used,'$scope',$rank)";
  $sth = $dbh->prepare($sql);
  $res = $sth->execute();
  ## get the service_id from inserted_row
  if (!$res) {
    print "Could not create service ".$label.", group_id ". $group_id."\n";
    print "Upgrade process stopped. Please contact your CodeX support representative\n";
    exit;
  }
}

sub delete_column {
  my ($table,$column) = @_;
  $sql="ALTER TABLE $table DROP $column";
  $sth = $dbh->prepare($sql);
  $res = $sth->execute();
  if (!$res) {
    print "Could not delete column $column from $table\n";
    print "Upgrade process stopped. Please contact your CodeX support representative\n";
    exit;
  }
}


sub init_service_db {

  my ($query, $c, $res);

  # Before calling this script, the 'service' table should be created and populated with services for group 100
  $query = "select * from service where group_id=100";
  $c = $dbh->prepare($query);
  $c->execute();
  if ($c->rows <2) {
    print "Please apply SQL patch before executing this script!\n";
    exit 1;
  }

  ## get existing values from the group table
  $query = "select group_id, unix_group_name, homepage, use_bugs, use_mail, use_survey, use_patch, use_forum, use_pm, use_cvs, use_news, use_support, use_docman, use_trackers, activate_old_bug, activate_old_task, activate_old_sr FROM groups where group_id!=100";
  $c = $dbh->prepare($query);
  $c->execute();

  while (my ($group_id, $unix_group_name, $homepage, $use_bugs, $use_mail, $use_survey, $use_patch, $use_forum, $use_pm, $use_cvs, $use_news, $use_support, $use_docman, $use_trackers, $activate_old_bug, $activate_old_task, $activate_old_sr) = $c->fetchrow()) {

    ## look if activate_old_bugs is needed
    $new_homepage = check_homepage($homepage,$unix_group_name,$sys_default_domain);

    if (!$activate_old_bug)  { $use_bugs=0; }
    if (!$activate_old_task) { $use_pm=0; }
    if (!$activate_old_sr)   { $use_support=0; }

    if (!$sys_activate_tracker) { $use_trackers=0; $sys_activate_tracker=0; }
    
    createService($group_id, 'Summary', 'Project Summary', 'summary', "/projects/$unix_group_name/", 1, 1, 'system', 10);
    createService($group_id, 'Admin', 'Project Administration', 'admin', "/project/admin/?group_id=$group_id", 1, 1, 'system', 20);

    createService($group_id, 'Home Page', 'Project Home Page', 'homepage', $new_homepage, 1, 1, 'system', 30);
    createService($group_id, 'Forums', 'Project Forums', 'forum', "/forum/?group_id=$group_id", 1, $use_forum, 'system', 40);
    createService($group_id, 'Bugs', 'Bug Tracking System', 'bugs', "/bugs/?group_id=$group_id", $activate_old_bug, $use_bugs, 'system', 50);
    createService($group_id, 'Support', 'Support Request Manager', 'support', "/support/?group_id=$group_id", $activate_old_sr, $use_support, 'system', 60);
    createService($group_id, 'Patches', 'Patch Manager', 'patch', "/patch/?group_id=$group_id", 1, $use_patch, 'system', 70);
    createService($group_id, 'Lists', 'Mailing Lists', 'mail', "/mail/?group_id=$group_id", 1, $use_mail, 'system', 80);
    createService($group_id, 'Tasks', 'Task Manager', 'task', "/pm/?group_id=$group_id", $activate_old_task, $use_pm, 'system', 90);
    createService($group_id, 'Docs', 'Document Manager', 'doc', "/docman/?group_id=$group_id", 1, $use_docman, 'system', 100);
    createService($group_id, 'Surveys', 'Project Surveys', 'survey', "/survey/?group_id=$group_id", 1, $use_survey, 'system', 110);
    createService($group_id, 'News', 'Project News', 'news', "/news/?group_id=$group_id", 1, $use_news, 'system', 120);
    createService($group_id, 'CVS', 'CVS Access', 'cvs', "/cvs/?group_id=$group_id", 1, $use_cvs, 'system', 130);
    createService($group_id, 'Files', 'File Releases', 'file', "/project/filelist.php?group_id=$group_id", 1, 1, 'system', 140);
    createService($group_id, 'Trackers', 'Project Trackers', 'tracker', "/tracker/index.php?group_id=$group_id", $sys_activate_tracker, $use_trackers, 'system', 150);

  }

  # Then delete useless columns
  delete_column('groups','homepage');
  delete_column('groups','use_bugs');
  delete_column('groups','use_mail');
  delete_column('groups','use_survey');
  delete_column('groups','use_patch');
  delete_column('groups','use_forum');
  delete_column('groups','use_pm');
  delete_column('groups','use_cvs');
  delete_column('groups','use_news');
  delete_column('groups','use_support');
  delete_column('groups','use_docman');
  delete_column('groups','use_trackers');
  delete_column('groups','activate_old_bug');
  delete_column('groups','activate_old_task');
  delete_column('groups','activate_old_sr');

  print "Database successfully updated\n";

}


print "Checking variables: SF_LOCAL_INC_PREFIX=".$ENV{'SF_LOCAL_INC_PREFIX'}."\n";
print "                    sys_activate_tracker=$sys_activate_tracker\n";
print "                    sys_default_domain=$sys_default_domain\n";

init_service_db();

1;
