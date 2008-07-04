<?php
//
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2008. All rights reserved
//
// 
//
//  Written for CodeX by Benjamin Ninassi
//
require_once('/etc/codex/conf/database.inc');


$link = mysql_connect($sys_dbhost, $sys_dbuser, $sys_dbpasswd);
if (!$link) {
	die('Impossible de se connecter : ' . mysql_error());
}
mysql_select_db($sys_dbname);

// table artifact_group_list
$sql = 'INSERT INTO artifact_group_list ' .
		'(group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification)' .
		' VALUES (100, "Scrum Backlog", "Scrum backlog used to store user stories and to plan sprints", "story", 1, NULL, NULL, 1, 0)';
	
mysql_query($sql);
$artifact_group_id=mysql_insert_id();

//table artifact_field_set
$sql= 'INSERT INTO artifact_field_set ( group_artifact_id, name, description, rank) ' .
		'VALUES ('.$artifact_group_id.', "fieldset_scrum_description_lbl_key", "fieldset_scrum_description_desc_key", 10)';
mysql_query($sql);
$description_field_set_id=mysql_insert_id();

$sql= 'INSERT INTO artifact_field_set (group_artifact_id, name, description, rank) ' .
		'VALUES ('.$artifact_group_id.', "fieldset_scrum_status_lbl_key", "fieldset_scrum_status_desc_key", 20)';
mysql_query($sql);
$status_field_set_id=mysql_insert_id();

//table artifact_field

$sql= 'INSERT INTO artifact_field VALUES (1,'.$artifact_group_id.','.$description_field_set_id.',"submitted_by",5,"SB", "", "Submitted by","User who originally submitted the artifact","",0,1,1,1,"artifact_submitters","")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (2,'.$artifact_group_id.','.$description_field_set_id.',"open_date",4,"DF", "", "Submitted on","Date and time for the initial artifact submission","",0,0,0,1,NULL,"")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (3,'.$artifact_group_id.','.$description_field_set_id.',"close_date",4,"DF", "", "Close Date","Close Date","",0,0,0,0,NULL,"")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (4,'.$artifact_group_id.','.$description_field_set_id.',"summary",1,"TF", "60/150", "Summary","One line description of the artifact","",0,0,1,0,NULL,"")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (5,'.$artifact_group_id.','.$description_field_set_id.',"artifact_id",2,"TF", "6/10", "Artifact ID","Unique artifact identifier","",0,0,0,1,NULL,"")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (6,'.$artifact_group_id.','.$description_field_set_id.',"severity",2,"SB", "", "Priority","Customer priority","",0,0,1,0,NULL,"5")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (7,'.$artifact_group_id.','.$description_field_set_id.',"details",1,"TA", "60/7", "Original Submission","A full description of the artifact","",0,1,1,0,NULL,"")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (8,'.$artifact_group_id.','.$description_field_set_id.',"category",2,"SB", "", "Category","Category this story belongs to (e.g. User interface, reporting, account management, etc.)","",0,1,1,0,NULL,"100")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (9,'.$artifact_group_id.','.$description_field_set_id.',"customer_value",2,"TF", "", "Value","Customer value for this story (integer))","",0,1,1,0,NULL,"0")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (10,'.$artifact_group_id.','.$description_field_set_id.',"acceptance_criteria",1,"TA", "80/3", "Acceptance criteria","Description of customer acceptance criteria for this story","",0,1,1,0,NULL,"")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (11,'.$artifact_group_id.','.$description_field_set_id.',"product",2,"SB", "80/3", "Product","Product impacted by story (for multi-product project)","",0,1,1,0,NULL,"100")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (12,'.$artifact_group_id.','.$description_field_set_id.',"customer",2,"SB", "", "Customer","Customer(s) that requested this story (for multi-customer projects)","",0,1,1,0,NULL,"100")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (13,'.$artifact_group_id.','.$status_field_set_id.',"status_id",2,"SB", "", "Status","Artifact Status","",0,0,1,0,NULL,"1")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (14,'.$artifact_group_id.','.$status_field_set_id.',"assigned_to",5,"SB", "", "Owner","Who is in charge of this story","",0,1,1,0,"group_members","100")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (15,'.$artifact_group_id.','.$status_field_set_id.',"stage",2,"SB", "", "Stage","Stage in the life cycle of the artifact","",0,0,1,0,NULL,"1")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (16,'.$artifact_group_id.','.$status_field_set_id.',"initial_effort",2,"TF", "", "Initial Effort Estimate","Initial estimation of effort","",0,1,1,0,NULL,"0")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (17,'.$artifact_group_id.','.$status_field_set_id.',"current_effort",2,"TF", "", "Current Effort Estimate","Current estimation of effort","",0,1,1,0,NULL,"0")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (18,'.$artifact_group_id.','.$status_field_set_id.',"release",2,"SB", "", "Release","Planned release for this story","",0,1,1,0,NULL,"100")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (19,'.$artifact_group_id.','.$status_field_set_id.',"iteration",2,"TF", "", "Iteration","Iteration number when the story is planned.","",0,1,1,0,NULL,"0")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (20,'.$artifact_group_id.','.$status_field_set_id.',"rank",2,"TF", "", "Rank","Rank of story in current sprint (i.e priority)","",0,1,1,0,NULL,"0")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (21,'.$artifact_group_id.','.$status_field_set_id.',"backlog",2,"SB", "", "Backlog","Sprint or Product backlog?","",0,0,1,0,NULL,"101")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field VALUES (22,'.$artifact_group_id.','.$status_field_set_id.',"remaining_effort",2,"TF", "", "Remaining Effort","Remaining Effort","",0,1,1,0,NULL,"0")';
mysql_query($sql);

//table artifact_field_usage

$sql= 'INSERT INTO artifact_field_usage VALUES (1,'.$artifact_group_id.',0,30)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (2,'.$artifact_group_id.',0,40)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (3,'.$artifact_group_id.',0,0)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (4,'.$artifact_group_id.',1,10)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (5,'.$artifact_group_id.',1,0)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (13,'.$artifact_group_id.',1,1000)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (6,'.$artifact_group_id.',1,70)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (7,'.$artifact_group_id.',1,20)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (14,'.$artifact_group_id.',1,60)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (15,'.$artifact_group_id.',1,50)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (16,'.$artifact_group_id.',1,70)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (17,'.$artifact_group_id.',1,80)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (18,'.$artifact_group_id.',0,20)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (19,'.$artifact_group_id.',1,30)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (8,'.$artifact_group_id.',0,60)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (9,'.$artifact_group_id.',1,90)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (10,'.$artifact_group_id.',0,100)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (11,'.$artifact_group_id.',0,50)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (12,'.$artifact_group_id.',1,80)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (21,'.$artifact_group_id.',1,10)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (20,'.$artifact_group_id.',0,40)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_usage VALUES (22,'.$artifact_group_id.',1,90)';
mysql_query($sql);

//table artifact_field_value_list

$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',1,"New","The artifact has just been submitted",20,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',2,"Analyzed","The cause of the artifact has been identified and documented",30,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',3,"Accepted","The artifact will be worked on.",40,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',4,"Under Implementation","The artifact is being worked on.",50,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',5,"Ready for Review","Updated/Created non-software work product (e.g. documentation) is ready for review and approval.",60,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',6,"Ready for Test","Updated/Created software is ready to be included in the next build",70,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',7,"In Test","Updated/Created software is in the build and is ready to enter the test phase",80,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',8,"Approved","The artifact fix has been succesfully tested. It is approved and awaiting release.",90,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',9,"Declined","The artifact was not accepted.",200,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',10,"Done","The artifact is closed.",110,"H")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (15,'.$artifact_group_id.',11,"Deployed","Artifact in production",110,"A")';
mysql_query($sql);

$sql= 'INSERT INTO artifact_field_value_list VALUES (13,'.$artifact_group_id.',1,"Open","The artifact has been submitted",20,"P")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (13,'.$artifact_group_id.',3,"Closed","The artifact is no longer active. See the Resolution field for details on how it was resolved.",400,"P")';
mysql_query($sql);

$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',1,"1 - Ordinary","",10,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',2,"2","",20,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',3,"3","",30,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',4,"4","",40,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',5,"5 - Major","",50,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',6,"6","",60,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',7,"7","",70,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',8,"8","",80,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (6,'.$artifact_group_id.',9,"9 - Critical","",90,"A")';
mysql_query($sql);

$sql= 'INSERT INTO artifact_field_value_list VALUES (18,'.$artifact_group_id.',100,"None","",10,"P")';
mysql_query($sql);

$sql= 'INSERT INTO artifact_field_value_list VALUES (8,'.$artifact_group_id.',100,"None","",10,"P")';
mysql_query($sql);

$sql= 'INSERT INTO artifact_field_value_list VALUES (11,'.$artifact_group_id.',100,"None","",10,"P")';
mysql_query($sql);

$sql= 'INSERT INTO artifact_field_value_list VALUES (12,'.$artifact_group_id.',100,"None","",10,"P")';
mysql_query($sql);

$sql= 'INSERT INTO artifact_field_value_list VALUES (21,'.$artifact_group_id.',100,"None","",10,"P")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (21,'.$artifact_group_id.',101,"Product Backlog","Product Backlog",20,"A")';
mysql_query($sql);
$sql= 'INSERT INTO artifact_field_value_list VALUES (21,'.$artifact_group_id.',102,"Sprint Backlog","Sprint Backlog",30,"A")';
mysql_query($sql);

//table artifact_report
$sql= 'INSERT INTO artifact_report (group_artifact_id, user_id, name, description, scope, is_default)' .
		' VALUES ('.$artifact_group_id.',100,"Scrum","Scrum Report","P",1)';
mysql_query($sql);
$artifact_report_id=mysql_insert_id();

//table artifact_report_field
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"initial_effort",0,1,NULL,80,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"stage",0,1,NULL,60,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"assigned_to",1,1,50,70,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"rank",0,1,NULL,40,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"iteration",1,1,30,50,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"summary",1,1,40,20,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"backlog",1,1,10,30,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"artifact_id",0,1,NULL,10,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"current_effort",0,1,NULL,90,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"remaining_effort",0,1,NULL,100,NULL)';
mysql_query($sql);
$sql= 'INSERT INTO artifact_report_field VALUES ('.$artifact_report_id.',"status_id",1,0,60,NULL,NULL)';
mysql_query($sql);

//table permissions
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#1",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#2",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#3",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#4",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#5",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#6",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#7",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#8",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#9",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#10",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#11",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#12",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#13",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#14",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#15",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#16",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#17",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#18",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#19",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#20",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#21",1)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_READ","'.$artifact_group_id.'#22",1)';
mysql_query($sql);

$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#10",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#20",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#12",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#6",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#7",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#21",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#9",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#16",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#18",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#19",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#4",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#8",2)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_SUBMIT","'.$artifact_group_id.'#11",2)';
mysql_query($sql);

$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#13",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#20",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#7",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#22",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#4",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#14",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#7",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#3",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#21",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#13",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#15",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#10",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#8",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#19",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#11",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#18",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#12",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#17",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#16",3)';
mysql_query($sql);
$sql = 'INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ("TRACKER_FIELD_UPDATE","'.$artifact_group_id.'#9",3)';
mysql_query($sql);


?>