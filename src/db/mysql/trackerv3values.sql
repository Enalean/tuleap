#****************************************************************
#*******       G E N E R I C   T R A C K E R S       ************
#*******               V  A  L  U  E  S              ************
#****************************************************************

--
-- Dumping data for table 'artifact_group_list'
--

INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification) VALUES (1, 100, 'Bugs', 'Bugs Tracker', 'bug', 0, NULL, NULL, 1, 0);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification) VALUES (2, 100, 'Tasks', 'Tasks Tracker', 'task', 0, NULL, NULL, 1, 0);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification) VALUES (3, 100, 'Support Requests', 'Support Requests Tracker', 'SR', 0, NULL, NULL, 1, 0);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification) VALUES (4, 100, 'Empty', 'Empty Tracker', '', 0, NULL, NULL, 0, 0);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification) VALUES (5, 100, 'Patches', 'Patch Tracker', 'patch', 0,NULL, NULL, 0, 0);
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification) VALUES (7, 100, 'Scrum Backlog', 'Scrum backlog used to store user stories and to plan sprints', 'story', 1, NULL, NULL, 1, 0);
--
-- This tracker has the id 100 to force the next id to be greater than 100
-- 100 is a special value (None value)
--
INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, allow_copy, submit_instructions, browse_instructions, instantiate_for_new_projects, stop_notification) VALUES (100, 100, 'None', 'None', '', 0, NULL, NULL, 0, 0);

--
-- Dumping data for table 'artifact_field_set'
--
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (1, 1, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (2, 1, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (3, 2, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (4, 2, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (5, 3, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (6, 3, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (7, 4, 'fieldset_default_lbl_key', 'fieldset_default_desc_key', 10);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (8, 5, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (9, 5, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (10, 5, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (14, 7, 'fieldset_scrum_description_lbl_key', 'fieldset_scrum_description_desc_key', 10);
INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (15, 7, 'fieldset_scrum_status_lbl_key', 'fieldset_scrum_status_desc_key', 20);
--
-- Dumping data for table 'artifact_field'
--

-- Bugs tracker
INSERT INTO artifact_field VALUES (7,1,1,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'artifact_submitters','');
INSERT INTO artifact_field VALUES (6,1,1,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (5,1,1,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (1,1,1,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (4,1,2,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'group_members','100');
INSERT INTO artifact_field VALUES (31,1,1,'last_update_date',4,'DF','','Last Modified On','Date and time of the latest modification in an artifact','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,1,1,'category_id',2,'SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (2,1,2,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (30,1,2,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (8,1,1,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (10,1,1,'comment_type_id',2,'SB','','Comment Type','Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)','',0,1,0,1,NULL,'100');
INSERT INTO artifact_field VALUES (9,1,1,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (16,1,2,'resolution_id',2,'SB','','Resolution','How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (20,1,1,'bug_group_id',2,'SB','','Group','Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (11,1,1,'category_version_id',2,'SB','','Component Version','The version of the System Component (aka Category) impacted by the artifact','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (12,1,1,'platform_version_id',2,'SB','','Platform Version','The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (13,1,2,'reproducibility_id',2,'SB','','Reproducibility','How easy is it to reproduce the artifact','S',0,0,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (14,1,2,'size_id',2,'SB','','Size (loc)','The size of the code you need to develop or rework in order to fix the artifact','S',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (15,1,2,'fix_release_id',2,'SB','','Fixed Release','The release in which the artifact was actually fixed','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (17,1,2,'hours',3,'TF','5/5','Effort','Number of hours of work needed to fix the artifact (including testing)','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (18,1,2,'plan_release_id',2,'SB','','Planned Release','The release in which you initially planned the artifact to be fixed','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (19,1,1,'component_version',1,'TF','10/40','Component Version','Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (22,1,1,'priority',2,'SB','','Priority','How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)','S',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (23,1,1,'keywords',1,'TF','60/120','Keywords','A list of comma separated keywords associated with a artifact','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (24,1,1,'release_id',2,'SB','','Release','The release (global version number) impacted by the artifact','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (26,1,1,'originator_name',1,'TF','20/40','Originator Name','The name of the person who reported the artifact (if different from the submitter field)','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (27,1,1,'originator_email',1,'TF','20/40','Originator Email','Email address of the person who reported the artifact. Automatically included in the artifact email notification process.','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (28,1,1,'originator_phone',1,'TF','10/40','Originator Phone','Phone number of the person who reported the artifact','S',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (29,1,2,'close_date',4,'DF','','Close Date','Close Date','',0,1,0,0,NULL,0);
-- Tasks tracker
INSERT INTO artifact_field VALUES (2,2,4,'percent',2,'TF','3/3','Percentage of completion (1-100)','Integer value between 0-100','',0,0,1,0,NULL,'0');
INSERT INTO artifact_field VALUES (4,2,4,'hours',3,'TF','5/5','Effort','Number of hours of work needed to fix the artifact (including testing)','',0,1,1,0,NULL,'0.00');
INSERT INTO artifact_field VALUES (5,2,4,'start_date',4,'DF','','Start Date','Start Date','',0,1,1,0,NULL,0);
INSERT INTO artifact_field VALUES (6,2,4,'close_date',4,'DF','','Close Date','Close Date','',0,0,0,1,NULL,0);
INSERT INTO artifact_field VALUES (7,2,3,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (8,2,3,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (11,2,4,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (15,2,4,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (1,2,3,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (10,2,3,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (9,2,3,'multi_assigned_to',5,'MB','','Assigned to (multiple)','Who is in charge of this artifact','',0,1,1,0,'group_members','100');
INSERT INTO artifact_field VALUES (12,2,3,'subproject_id',2,'SB','','Subproject','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (13,2,3,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'artifact_submitters','');
INSERT INTO artifact_field VALUES (14,2,3,'severity',2,'SB','','Priority','How quickly the artifact must be completed','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (16,2,3,'last_update_date',4,'DF','','Last Modified On','Date and time of the latest modification in an artifact','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (17,2,4,'due_date',4,'DF','','Due Date','Due Date','',0,1,1,0,NULL,0);
INSERT INTO artifact_field VALUES (18,2,4,'end_date',4,'DF','','End Date','End Date','',0,1,1,0,NULL,0);
-- SR tracker
INSERT INTO artifact_field VALUES (9,3,5,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'artifact_submitters','');
INSERT INTO artifact_field VALUES (7,3,6,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (12,3,6,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (6,3,6,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'group_members','100');
INSERT INTO artifact_field VALUES (5,3,5,'category_id',2,'SB','','Category','Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (4,3,5,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,3,5,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (2,3,5,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (1,3,5,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (10,3,6,'close_date',4,'DF','','Close Date','Close Date','',0,1,0,0,NULL,0);
INSERT INTO artifact_field VALUES (11,3,5,'severity',2,'SB','','Priority','How quickly the artifact must be completed','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (13,3,5,'last_update_date',4,'DF','','Last Modified On','Date and time of the latest modification in an artifact','',0,0,0,1,'','');
-- Empty tracker
INSERT INTO artifact_field VALUES (1,4,7,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'artifact_submitters','');
INSERT INTO artifact_field VALUES (2,4,7,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,4,7,'close_date',4,'DF','','Close Date','Close Date','',0,1,0,0,NULL,0);
INSERT INTO artifact_field VALUES (4,4,7,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (5,4,7,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (6,4,7,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (11,4,7,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (7,4,7,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (8,4,7,'details',1,'TA','60/7','Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (9,4,7,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'group_members','100');
INSERT INTO artifact_field VALUES (10,4,7,'multi_assigned_to',5,'MB','','Assigned to (multiple)','Who is in charge of this artifact','',0,1,1,0,'group_members','100');
INSERT INTO artifact_field VALUES (12,4,7,'last_update_date',4,'DF','','Last Modified On','Date and time of the latest modification in an artifact','',0,0,0,1,'','');
-- Patches tracker
INSERT INTO artifact_field VALUES (1,5,8,'submitted_by',5,'SB','','Submitted by','User who originally submitted the artifact','',0,1,0,1,'artifact_submitters','');
INSERT INTO artifact_field VALUES (2,5,8,'open_date',4,'DF','','Submitted on','Date and time for the initial artifact submission','',0,0,0,1,'','');
INSERT INTO artifact_field VALUES (3,5,8,'summary',1,'TF','60/150','Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (4,5,8,'artifact_id',2,'TF','6/10','Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (5,5,9,'plain_text',1,'TA','60/7','Patch text','Plain-text version of the patch','',0,1,0,0,NULL,'');
INSERT INTO artifact_field VALUES (6,5,10,'assigned_to',5,'SB','','Assigned to','Who is in charge of solving the artifact','',0,1,1,0,'group_members','100');
INSERT INTO artifact_field VALUES (7,5,8,'category_id',2,'SB','','Category','Patch categories (e.g. mail module,gant chart module,interface, etc)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (8,5,8,'details',1,'TA','60/7','Description','Description of functionality and application of the patch','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (9,5,10,'status_id',2,'SB','','Status','Artifact Status','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (10,5,8,'severity',2,'SB','','Severity','Impact of the artifact on the system (Critical, Major,...)','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (11,5,8,'release_id',2,'SB','','Release','The release (global version number) impacted by the artifact','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (12,5,10,'stage',2,'SB','','Stage','Stage in the life cycle of the artifact','P',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (13,5,8,'last_update_date',4,'DF','','Last Modified On','Date and time of the latest modification in an artifact','',0,0,0,1,'','');
-- Scrum Backlog tracker
INSERT INTO artifact_field VALUES (1,7,14,'submitted_by',5,'SB', '', 'Submitted by','User who originally submitted the artifact','',0,1,1,1,'artifact_submitters','');
INSERT INTO artifact_field VALUES (2,7,14,'open_date',4,'DF', '', 'Submitted on','Date and time for the initial artifact submission','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (3,7,14,'close_date',4,'DF', '', 'Close Date','Close Date','',0,0,0,0,NULL,0);
INSERT INTO artifact_field VALUES (4,7,14,'summary',1,'TF', '60/150', 'Summary','One line description of the artifact','',0,0,1,0,NULL,'');
INSERT INTO artifact_field VALUES (5,7,14,'artifact_id',2,'TF', '6/10', 'Artifact ID','Unique artifact identifier','',0,0,0,1,NULL,'');
INSERT INTO artifact_field VALUES (6,7,14,'severity',2,'SB', '', 'Priority','Customer priority','',0,0,1,0,NULL,'5');
INSERT INTO artifact_field VALUES (7,7,14,'details',1,'TA', '60/7', 'Original Submission','A full description of the artifact','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (8,7,14,'category',2,'SB', '', 'Category','Category this story belongs to (e.g. User interface, reporting, account management, etc.)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (9,7,14,'customer_value',2,'TF', '', 'Value','Customer value for this story (integer))','',0,1,1,0,NULL,'0');
INSERT INTO artifact_field VALUES (10,7,14,'acceptance_criteria',1,'TA', '80/3', 'Acceptance criteria','Description of customer acceptance criteria for this story','',0,1,1,0,NULL,'');
INSERT INTO artifact_field VALUES (11,7,14,'product',2,'SB', '80/3', 'Product','Product impacted by story (for multi-product project)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (12,7,14,'customer',2,'SB', '', 'Customer','Customer(s) that requested this story (for multi-customer projects)','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (13,7,15,'status_id',2,'SB', '', 'Status','Artifact Status','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (14,7,15,'assigned_to',5,'SB', '', 'Owner','Who is in charge of this story','',0,1,1,0,'group_members','100');
INSERT INTO artifact_field VALUES (15,7,15,'stage',2,'SB', '', 'Stage','Stage in the life cycle of the artifact','',0,0,1,0,NULL,'1');
INSERT INTO artifact_field VALUES (16,7,15,'initial_effort',2,'TF', '', 'Initial Effort Estimate','Initial estimation of effort','',0,1,1,0,NULL,'0');
INSERT INTO artifact_field VALUES (17,7,15,'current_effort',2,'TF', '', 'Current Effort Estimate','Current estimation of effort','',0,1,1,0,NULL,'0');
INSERT INTO artifact_field VALUES (18,7,15,'release',2,'SB', '', 'Release','Planned release for this story','',0,1,1,0,NULL,'100');
INSERT INTO artifact_field VALUES (19,7,15,'iteration',2,'TF', '', 'Iteration','Iteration number when the story is planned.','',0,1,1,0,NULL,'0');
INSERT INTO artifact_field VALUES (20,7,15,'rank',2,'TF', '', 'Rank','Rank of story in current sprint (i.e priority)','',0,1,1,0,NULL,'0');
INSERT INTO artifact_field VALUES (21,7,15,'backlog',2,'SB', '', 'Backlog','Sprint or Product backlog?','',0,0,1,0,NULL,'101');
INSERT INTO artifact_field VALUES (22,7,15,'remaining_effort',2,'TF', '', 'Remaining Effort','Remaining Effort','',0,1,1,0,NULL,'0');
INSERT INTO artifact_field VALUES (23,7,14,'last_update_date',4,'DF','','Last Modified On','Date and time of the latest modification in an artifact','',0,0,0,1,'','');


--
-- Dumping data for table 'artifact_field_usage'
--


INSERT INTO artifact_field_usage VALUES (7,1,1,0);
INSERT INTO artifact_field_usage VALUES (6,1,1,0);
INSERT INTO artifact_field_usage VALUES (5,1,1,900);
INSERT INTO artifact_field_usage VALUES (1,1,1,0);
INSERT INTO artifact_field_usage VALUES (4,1,1,50);
INSERT INTO artifact_field_usage VALUES (3,1,1,10);
INSERT INTO artifact_field_usage VALUES (2,1,1,60);
INSERT INTO artifact_field_usage VALUES (30,1,0,0);
INSERT INTO artifact_field_usage VALUES (8,1,1,20);
INSERT INTO artifact_field_usage VALUES (10,1,1,NULL);
INSERT INTO artifact_field_usage VALUES (9,1,1,1000);
INSERT INTO artifact_field_usage VALUES (16,1,1,40);
INSERT INTO artifact_field_usage VALUES (20,1,1,30);
INSERT INTO artifact_field_usage VALUES (31,1,1,0);
INSERT INTO artifact_field_usage VALUES (2,2,1,20);
INSERT INTO artifact_field_usage VALUES (3,2,1,30);
INSERT INTO artifact_field_usage VALUES (4,2,1,40);
INSERT INTO artifact_field_usage VALUES (5,2,1,60);
INSERT INTO artifact_field_usage VALUES (17,2,1,62);
INSERT INTO artifact_field_usage VALUES (18,2,1,64);
INSERT INTO artifact_field_usage VALUES (6,2,0,0);
INSERT INTO artifact_field_usage VALUES (7,2,1,900);
INSERT INTO artifact_field_usage VALUES (8,2,1,1000);
INSERT INTO artifact_field_usage VALUES (11,2,1,50);
INSERT INTO artifact_field_usage VALUES (15,2,0,0);
INSERT INTO artifact_field_usage VALUES (1,2,1,1);
INSERT INTO artifact_field_usage VALUES (10,2,1,0);
INSERT INTO artifact_field_usage VALUES (16,2,1,0);
INSERT INTO artifact_field_usage VALUES (9,3,1,NULL);
INSERT INTO artifact_field_usage VALUES (7,3,1,30);
INSERT INTO artifact_field_usage VALUES (12,3,0,0);
INSERT INTO artifact_field_usage VALUES (6,3,1,20);
INSERT INTO artifact_field_usage VALUES (5,3,1,10);
INSERT INTO artifact_field_usage VALUES (4,3,1,5);
INSERT INTO artifact_field_usage VALUES (3,3,1,1000);
INSERT INTO artifact_field_usage VALUES (2,3,1,900);
INSERT INTO artifact_field_usage VALUES (1,3,1,1);
INSERT INTO artifact_field_usage VALUES (13,3,1,0);
INSERT INTO artifact_field_usage VALUES (9,2,1,70);
INSERT INTO artifact_field_usage VALUES (12,2,1,10);

INSERT INTO artifact_field_usage VALUES (11,1,0,0);
INSERT INTO artifact_field_usage VALUES (12,1,0,0);
INSERT INTO artifact_field_usage VALUES (13,1,0,0);
INSERT INTO artifact_field_usage VALUES (14,1,0,0);
INSERT INTO artifact_field_usage VALUES (15,1,0,0);
INSERT INTO artifact_field_usage VALUES (17,1,0,0);
INSERT INTO artifact_field_usage VALUES (18,1,0,0);
INSERT INTO artifact_field_usage VALUES (19,1,0,0);
INSERT INTO artifact_field_usage VALUES (22,1,0,0);
INSERT INTO artifact_field_usage VALUES (23,1,0,0);
INSERT INTO artifact_field_usage VALUES (24,1,0,0);
INSERT INTO artifact_field_usage VALUES (26,1,0,0);
INSERT INTO artifact_field_usage VALUES (27,1,0,0);
INSERT INTO artifact_field_usage VALUES (28,1,0,0);
INSERT INTO artifact_field_usage VALUES (29,1,0,0);

INSERT INTO artifact_field_usage VALUES (13,2,1,0);
INSERT INTO artifact_field_usage VALUES (14,2,1,30);

INSERT INTO artifact_field_usage VALUES (10,3,0,0);
INSERT INTO artifact_field_usage VALUES (11,3,1,40);

INSERT INTO artifact_field_usage VALUES (1,4,1,0);
INSERT INTO artifact_field_usage VALUES (2,4,1,0);
INSERT INTO artifact_field_usage VALUES (3,4,0,0);
INSERT INTO artifact_field_usage VALUES (4,4,1,10);
INSERT INTO artifact_field_usage VALUES (5,4,1,0);
INSERT INTO artifact_field_usage VALUES (6,4,0,0);
INSERT INTO artifact_field_usage VALUES (7,4,1,0);
INSERT INTO artifact_field_usage VALUES (8,4,1,20);
INSERT INTO artifact_field_usage VALUES (9,4,1,30);
INSERT INTO artifact_field_usage VALUES (10,4,0,40);
INSERT INTO artifact_field_usage VALUES (11,4,0,0);
INSERT INTO artifact_field_usage VALUES (12,4,1,0);


INSERT INTO artifact_field_usage VALUES (1,5,1,0);
INSERT INTO artifact_field_usage VALUES (2,5,1,0);
INSERT INTO artifact_field_usage VALUES (3,5,1,30);
INSERT INTO artifact_field_usage VALUES (4,5,1,0);
INSERT INTO artifact_field_usage VALUES (5,5,1,70);
INSERT INTO artifact_field_usage VALUES (6,5,1,0);
INSERT INTO artifact_field_usage VALUES (7,5,1,10);
INSERT INTO artifact_field_usage VALUES (8,5,1,50);
INSERT INTO artifact_field_usage VALUES (9,5,1,0);
INSERT INTO artifact_field_usage VALUES (10,5,1,0);
INSERT INTO artifact_field_usage VALUES (11,5,0,0);
INSERT INTO artifact_field_usage VALUES (12,5,1,0);
INSERT INTO artifact_field_usage VALUES (13,5,1,0);

INSERT INTO artifact_field_usage VALUES (1,7,0,30);
INSERT INTO artifact_field_usage VALUES (2,7,0,40);
INSERT INTO artifact_field_usage VALUES (3,7,0,0);
INSERT INTO artifact_field_usage VALUES (4,7,1,10);
INSERT INTO artifact_field_usage VALUES (5,7,1,0);
INSERT INTO artifact_field_usage VALUES (13,7,1,1000);
INSERT INTO artifact_field_usage VALUES (6,7,1,70);
INSERT INTO artifact_field_usage VALUES (7,7,1,20);
INSERT INTO artifact_field_usage VALUES (14,7,1,60);
INSERT INTO artifact_field_usage VALUES (15,7,1,50);
INSERT INTO artifact_field_usage VALUES (16,7,1,70);
INSERT INTO artifact_field_usage VALUES (17,7,1,80);
INSERT INTO artifact_field_usage VALUES (18,7,0,20);
INSERT INTO artifact_field_usage VALUES (19,7,1,30);
INSERT INTO artifact_field_usage VALUES (8,7,0,60);
INSERT INTO artifact_field_usage VALUES (9,7,1,90);
INSERT INTO artifact_field_usage VALUES (10,7,0,100);
INSERT INTO artifact_field_usage VALUES (11,7,0,50);
INSERT INTO artifact_field_usage VALUES (12,7,1,80);
INSERT INTO artifact_field_usage VALUES (21,7,1,10);
INSERT INTO artifact_field_usage VALUES (20,7,0,40);
INSERT INTO artifact_field_usage VALUES (22,7,1,90);
INSERT INTO artifact_field_usage VALUES (23,7,1,45);

--
-- Dumping data for table 'artifact_field_value_list'
--


INSERT INTO artifact_field_value_list VALUES (2,1,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (2,1,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');

INSERT INTO artifact_field_value_list VALUES (30,1,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (30,1,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (30,1,10,'Done','The artifact is closed.',110,'A');

INSERT INTO artifact_field_value_list VALUES (3,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (8,1,9,'9 - Critical','',90,'P');
INSERT INTO artifact_field_value_list VALUES (10,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (16,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (16,1,1,'Fixed','The bug was resolved',20,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,2,'Invalid','The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)',30,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,3,'Wont Fix','The bug won''t be fixed (probably because it is very minor)',40,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,4,'Later','The bug will be fixed later (no date given)',50,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,5,'Remind','The bug will be fixed later but keep in the remind state for easy identification',60,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,6,'Works for me','The project team was unable to reproduce the bug',70,'A');
INSERT INTO artifact_field_value_list VALUES (16,1,7,'Duplicate','This bug is already covered by another bug description (see related bugs list)',80,'A');

INSERT INTO artifact_field_value_list VALUES (11,2,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (11,2,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');

INSERT INTO artifact_field_value_list VALUES (15,2,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (15,2,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (15,2,10,'Done','The artifact is closed.',110,'A');

INSERT INTO artifact_field_value_list VALUES (12,2,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (2,2,1095,'95%','',95,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1090,'90%','',90,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1085,'85%','',85,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1080,'80%','',80,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1075,'75%','',75,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1070,'70%','',70,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1065,'65%','',65,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1060,'60%','',60,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1055,'55%','',55,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1050,'50%','',50,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1045,'45%','',45,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1040,'40%','',40,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1035,'35%','',35,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1030,'30%','',30,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1025,'25%','',25,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1020,'20%','',20,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1015,'15%','',15,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1010,'10%','',10,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1000,'Not started','',0,'A');
INSERT INTO artifact_field_value_list VALUES (2,2,1100,'100%','',100,'P');
INSERT INTO artifact_field_value_list VALUES (7,3,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (7,3,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');

INSERT INTO artifact_field_value_list VALUES (12,3,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (12,3,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (12,3,10,'Done','The artifact is closed.',110,'A');


INSERT INTO artifact_field_value_list VALUES (5,3,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (8,3,1095,'95%','',95,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1090,'90%','',90,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1085,'85%','',85,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1080,'80%','',80,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1075,'75%','',75,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1070,'70%','',70,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1065,'65%','',65,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1060,'60%','',60,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1055,'55%','',55,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1050,'50%','',50,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1045,'45%','',45,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1040,'40%','',40,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1035,'35%','',35,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1030,'30%','',30,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1025,'25%','',25,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1020,'20%','',20,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1015,'15%','',15,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1010,'10%','',10,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1000,'Not started','',0,'A');
INSERT INTO artifact_field_value_list VALUES (8,3,1100,'100%','',100,'A');

INSERT INTO artifact_field_value_list VALUES (11,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (12,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (13,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (14,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (15,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (18,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (20,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (22,1,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (24,1,100,'None','',10,'P');

INSERT INTO artifact_field_value_list VALUES (14,2,1,'1 - Lowest','',10,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,5,'5 - Medium','',50,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (14,2,9,'9 - Highest','',90,'P');

INSERT INTO artifact_field_value_list VALUES (11,3,1,'1 - Lowest','',10,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,5,'5 - Medium','',50,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (11,3,9,'9 - Highest','',90,'P');

INSERT INTO artifact_field_value_list VALUES (6,4,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (6,4,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');

INSERT INTO artifact_field_value_list VALUES (11,4,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,4,'Under Implementation','The artifact is being worked on.',50,'A');

INSERT INTO artifact_field_value_list VALUES (11,4,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,9,'Declined','The artifact was not accepted.',100,'A');
INSERT INTO artifact_field_value_list VALUES (11,4,10,'Done','The artifact is closed.',110,'A');


INSERT INTO artifact_field_value_list VALUES (7,4,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (7,4,9,'9 - Critical','',90,'P');


INSERT INTO artifact_field_value_list VALUES (7,5,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (9,5,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (9,5,3,'Closed','The artifact is no longer active',400,'P');

INSERT INTO artifact_field_value_list VALUES (10,5,1,'1 - Ordinary','',10,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,2,'2','',20,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,3,'3','',30,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,4,'4','',40,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,5,'5 - Major','',50,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,6,'6','',60,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,7,'7','',70,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,8,'8','',80,'P');
INSERT INTO artifact_field_value_list VALUES (10,5,9,'9 - Critical','',90,'P');

INSERT INTO artifact_field_value_list VALUES (12,5,1,'Accepted','The artifact will be worked on. If it won\'t be worked on, indicate why and close it',10,'A'); 
-- ' For proper syntax highlighting in emacs :-)
INSERT INTO artifact_field_value_list VALUES (12,5,2,'Declined','The artifact was not accepted.',50,'A');

INSERT INTO artifact_field_value_list VALUES (15,7,1,'New','The artifact has just been submitted',20,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,2,'Analyzed','The cause of the artifact has been identified and documented',30,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,3,'Accepted','The artifact will be worked on.',40,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,4,'Under Implementation','The artifact is being worked on.',50,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,5,'Ready for Review','Updated/Created non-software work product (e.g. documentation) is ready for review and approval.',60,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,6,'Ready for Test','Updated/Created software is ready to be included in the next build',70,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,7,'In Test','Updated/Created software is in the build and is ready to enter the test phase',80,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,8,'Approved','The artifact fix has been succesfully tested. It is approved and awaiting release.',90,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,9,'Declined','The artifact was not accepted.',200,'A');
INSERT INTO artifact_field_value_list VALUES (15,7,10,'Done','The artifact is closed.',110,'H');
INSERT INTO artifact_field_value_list VALUES (15,7,11,'Deployed','Artifact in production',110,'A');

INSERT INTO artifact_field_value_list VALUES (13,7,1,'Open','The artifact has been submitted',20,'P');
INSERT INTO artifact_field_value_list VALUES (13,7,3,'Closed','The artifact is no longer active. See the Resolution field for details on how it was resolved.',400,'P');

INSERT INTO artifact_field_value_list VALUES (6,7,1,'1 - Ordinary','',10,'A');
INSERT INTO artifact_field_value_list VALUES (6,7,2,'2','',20,'A');
INSERT INTO artifact_field_value_list VALUES (6,7,3,'3','',30,'A');
INSERT INTO artifact_field_value_list VALUES (6,7,4,'4','',40,'A');
INSERT INTO artifact_field_value_list VALUES (6,7,5,'5 - Major','',50,'A');
INSERT INTO artifact_field_value_list VALUES (6,7,6,'6','',60,'A');
INSERT INTO artifact_field_value_list VALUES (6,7,7,'7','',70,'A');
INSERT INTO artifact_field_value_list VALUES (6,7,8,'8','',80,'A');
INSERT INTO artifact_field_value_list VALUES (6,7,9,'9 - Critical','',90,'A');

INSERT INTO artifact_field_value_list VALUES (18,7,100,'None','',10,'P');

INSERT INTO artifact_field_value_list VALUES (8,7,100,'None','',10,'P');

INSERT INTO artifact_field_value_list VALUES (11,7,100,'None','',10,'P');

INSERT INTO artifact_field_value_list VALUES (12,7,100,'None','',10,'P');

INSERT INTO artifact_field_value_list VALUES (21,7,100,'None','',10,'P');
INSERT INTO artifact_field_value_list VALUES (21,7,101,'Product Backlog','Product Backlog',20,'A');
INSERT INTO artifact_field_value_list VALUES (21,7,102,'Sprint Backlog','Sprint Backlog',30,'A');

--
-- Dumping data for table 'artifact_report'
--


INSERT INTO artifact_report VALUES (100,100,100,'Default','The system default artifact report','S',0);
INSERT INTO artifact_report VALUES (2,2,100,'Tasks','Tasks Report','P',0);
INSERT INTO artifact_report VALUES (3,3,100,'SR','Support Requests Report','P',0);
INSERT INTO artifact_report VALUES (4,1,100,'Bugs','Bugs Report','P',0);
INSERT INTO artifact_report VALUES (6,7,100,'Scrum','Scrum Report','P',1);

--
-- Dumping data for table 'artifact_report_field'
--

INSERT INTO artifact_report_field VALUES (100,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (100,'assigned_to',1,1,30,40,NULL);
INSERT INTO artifact_report_field VALUES (100,'status_id',1,0,20,NULL,NULL);
INSERT INTO artifact_report_field VALUES (100,'artifact_id',1,1,50,10,NULL);
INSERT INTO artifact_report_field VALUES (100,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (100,'open_date',1,1,40,30,NULL);
INSERT INTO artifact_report_field VALUES (100,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (100,'severity',0,0,NULL,NULL,NULL);
INSERT INTO artifact_report_field VALUES (2,'subproject_id',1,1,10,30,NULL);
INSERT INTO artifact_report_field VALUES (2,'multi_assigned_to',1,1,20,60,NULL);
INSERT INTO artifact_report_field VALUES (2,'status_id',1,1,30,100,NULL);
INSERT INTO artifact_report_field VALUES (2,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (2,'start_date',0,1,NULL,40,NULL);
INSERT INTO artifact_report_field VALUES (2,'close_date',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (2,'hours',0,1,NULL,70,NULL);
INSERT INTO artifact_report_field VALUES (2,'percent_complete',0,1,NULL,80,NULL);
INSERT INTO artifact_report_field VALUES (2,'artifact_id',0,1,NULL,1,NULL);
INSERT INTO artifact_report_field VALUES (3,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'status_id',1,0,30,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'summary',0,0,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (3,'open_date',0,1,NULL,30,NULL);
INSERT INTO artifact_report_field VALUES (3,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (3,'severity',0,1,NULL,NULL,NULL);
INSERT INTO artifact_report_field VALUES (3,'artifact_id',0,1,NULL,10,NULL);
INSERT INTO artifact_report_field VALUES (3,'assigned_to',1,1,20,40,NULL);
INSERT INTO artifact_report_field VALUES (4,'category_id',1,0,10,NULL,NULL);
INSERT INTO artifact_report_field VALUES (4,'assigned_to',1,1,30,40,NULL);
INSERT INTO artifact_report_field VALUES (4,'status_id',1,0,40,NULL,NULL);
INSERT INTO artifact_report_field VALUES (4,'artifact_id',0,1,NULL,10,NULL);
INSERT INTO artifact_report_field VALUES (4,'summary',0,1,NULL,20,NULL);
INSERT INTO artifact_report_field VALUES (4,'open_date',0,1,NULL,30,NULL);
INSERT INTO artifact_report_field VALUES (4,'submitted_by',0,1,NULL,50,NULL);
INSERT INTO artifact_report_field VALUES (4,'bug_group_id',1,0,20,NULL,NULL);

INSERT INTO artifact_report_field VALUES (6,'initial_effort',0,1,NULL,80,NULL);
INSERT INTO artifact_report_field VALUES (6,'stage',0,1,NULL,60,NULL);
INSERT INTO artifact_report_field VALUES (6,'assigned_to',1,1,50,70,NULL);
INSERT INTO artifact_report_field VALUES (6,'rank',0,1,NULL,40,NULL);
INSERT INTO artifact_report_field VALUES (6,'iteration',1,1,30,50,NULL);
INSERT INTO artifact_report_field VALUES (6,'summary',1,1,40,20,NULL);
INSERT INTO artifact_report_field VALUES (6,'backlog',1,1,10,30,NULL);
INSERT INTO artifact_report_field VALUES (6,'artifact_id',0,1,NULL,10,NULL);
INSERT INTO artifact_report_field VALUES (6,'current_effort',0,1,NULL,90,NULL);
INSERT INTO artifact_report_field VALUES (6,'remaining_effort',0,1,NULL,100,NULL);
INSERT INTO artifact_report_field VALUES (6,'status_id',1,0,60,NULL,NULL);

# ==============================
# artifact_notification_role table
# ==============================
# Create the list of roles a user can play wrt to a artifact
#
INSERT INTO artifact_notification_role_default VALUES (1,'SUBMITTER',10,'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc');
INSERT INTO artifact_notification_role_default VALUES (2,'ASSIGNEE',20,'role_ASSIGNEE_short_desc','role_ASSIGNEE_desc');
INSERT INTO artifact_notification_role_default VALUES (3,'CC',30,'role_CC_short_desc','role_CC_desc');
INSERT INTO artifact_notification_role_default VALUES (4,'COMMENTER',40,'role_COMMENTER_short_desc','role_COMMENTER_desc');

# ==============================
# artifact_notification_event table
# ==============================
# Create the list of events that can occur in a artifact update
#
INSERT INTO artifact_notification_event_default VALUES (1,'ROLE_CHANGE',10,'event_ROLE_CHANGE_shortdesc','event_ROLE_CHANGE_desc');
INSERT INTO artifact_notification_event_default VALUES (2,'NEW_COMMENT',20,'event_NEW_COMMENT_short_desc','event_NEW_COMMENT_desc');
INSERT INTO artifact_notification_event_default VALUES (3,'NEW_FILE',30,'event_NEW_FILE_short_desc','event_NEW_FILE_desc');
INSERT INTO artifact_notification_event_default VALUES (4,'CC_CHANGE',40,'event_CC_CHANGE_short_desc','event_CC_CHANGE_desc');
INSERT INTO artifact_notification_event_default VALUES (5,'CLOSED',50,'event_CLOSED_short_desc','event_CLOSED_desc');
INSERT INTO artifact_notification_event_default VALUES (6,'PSS_CHANGE',60,'event_PSS_CHANGE_short_desc','event_PSS_CHANGE_desc');
INSERT INTO artifact_notification_event_default VALUES (7,'ANY_OTHER_CHANGE',70,'event_ANY_OTHER_CHANGE_short_desc','event_ANY_OTHER_CHANGE_desc');
INSERT INTO artifact_notification_event_default VALUES (8,'I_MADE_IT',80,'event_I_MADE_IT_short_desc','event_I_MADE_IT_desc');
INSERT INTO artifact_notification_event_default VALUES (9,'NEW_ARTIFACT',90,'event_NEW_ARTIFACT_short_desc','event_NEW_ARTIFACT_desc');
INSERT INTO artifact_notification_event_default VALUES (10,'COMMENT_CHANGE',100,'event_COMMENT_CHANGE_short_desc','event_COMMENT_CHANGE_desc');

# Project 100
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (15, 100, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=$group_id', 1, 1, 'system', 150);

# Project 1
insert into service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank) VALUES (41, 1, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=1', 1, 1, 'system', 150);

# Tracker admin
INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (15, "ugroup_tracker_admins_name_key", "ugroup_tracker_admins_desc_key", 100);

# Permissions
-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_ACCESS_FULL',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_FULL',15);


-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_SUBMITTER',15);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ACCESS_ASSIGNEE',15);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT',15);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',100);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_READ',15);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',100);
-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE',15);

-- INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',100);
INSERT INTO permissions_values (permission_type,ugroup_id,is_default) VALUES ('TRACKER_ARTIFACT_ACCESS',1,1);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',2);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',3);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',4);
INSERT INTO permissions_values (permission_type,ugroup_id) VALUES ('TRACKER_ARTIFACT_ACCESS',15);

-- Bugs
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','1',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#3',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#4',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#5',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#8',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#9',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','1#20',2);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#12',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#13',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#14',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#15',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#16',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#17',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#18',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#19',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#20',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#22',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#23',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#24',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#26',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#27',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#28',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#29',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#30',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','1#31',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#2',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#3',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#5',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#9',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#10',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#11',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#12',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#13',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#14',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#15',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#16',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#17',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#18',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#19',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#20',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#22',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#23',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#24',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#26',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#27',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#28',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#29',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','1#30',3);

-- Tasks
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','2',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#2',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#5',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#9',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#12',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#14',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#17',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','2#18',3);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#12',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#13',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#14',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#15',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#16',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#17',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','2#18',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#2',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#5',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#9',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#11',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#12',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#14',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#15',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#17',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','2#18',3);

-- SR
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','3',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','3#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','3#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','3#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','3#11',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#12',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','3#13',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#2',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#3',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#5',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#6',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#10',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#11',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','3#12',3);

-- Empty
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','4',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#9',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','4#10',3);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','4#12',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#3',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#6',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#9',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#10',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','4#11',3);


-- Patch
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','5',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#3',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#5',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#7',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#8',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','5#10',2);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#12',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','5#13',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#3',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#5',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#6',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#9',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#10',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#11',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','5#12',3);

-- SCRUM

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_ACCESS_FULL','7',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#1',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#2',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#3',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#4',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#5',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#6',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#7',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#8',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#9',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#10',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#11',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#12',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#13',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#14',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#15',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#16',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#17',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#18',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#19',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#20',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#21',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#22',1);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_READ','7#23',1);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#10',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#20',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#12',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#6',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#21',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#9',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#16',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#18',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#19',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#8',2);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_SUBMIT','7#11',2);

INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#13',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#20',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#6',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#22',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#4',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#14',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#7',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#3',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#21',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#13',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#15',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#10',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#8',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#19',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#11',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#18',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#12',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#17',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#16',3);
INSERT INTO permissions (permission_type,object_id,ugroup_id) VALUES ('TRACKER_FIELD_UPDATE','7#9',3);

# As we add a tracker 100, add this *if necessary* in order to be consistent
INSERT INTO tracker_idsharing_tracker (id)
SELECT GREATEST(IFNULL(MAX(tracker_idsharing_tracker.id) , 0), IFNULL(MAX(artifact_group_list.group_artifact_id), 100)) + 1
FROM tracker_idsharing_tracker, artifact_group_list;