--
-- Dumping data for table 'plugin_graphontrackers_report_graphic'
--
INSERT INTO plugin_graphontrackers_report_graphic (report_graphic_id, group_artifact_id,user_id,name,description,scope) VALUES (1,1,101,'Default','Graphic Report By Default For Bugs','P');
INSERT INTO plugin_graphontrackers_report_graphic (report_graphic_id, group_artifact_id,user_id,name,description,scope) VALUES (2,3,101,'Default','Graphic Report By Default For Support Requests','P');
INSERT INTO plugin_graphontrackers_report_graphic (report_graphic_id, group_artifact_id,user_id,name,description,scope) VALUES (3,2,101,'Default','Graphic Report By Default For Tasks','P');
INSERT INTO plugin_graphontrackers_report_graphic (report_graphic_id, group_artifact_id,user_id,name,description,scope) VALUES (4,2,101,'Gantt','Gantt Graph for Task Management','P');
--
-- Dumping data for table 'plugin_graphontrackers_chart'
--
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (1,1,5,'pie','Status','Number of Artifacts by Status') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (2,1,10,'bar','Severity','Number of Artifacts by severity level') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (3,1,15,'pie','Assignment','Number of Artifacts by Assignee') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (4,2,5,'pie','Status','Number of Artifacts by Status') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (5,2,10,'bar','Severity','Number of Artifacts by severity level') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (6,2,15,'pie','Assignment','Number of Artifacts by Assignee') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (7,3,5,'pie','Status','Number of Artifacts by Status') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (8,3,10,'bar','Severity','Number of Artifacts by severity level') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (9,3,15,'bar','Assignment','Number of Artifacts by Assignee') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description,width,height) VALUES (10,4,5,'gantt','Gantt','Gantt Chart for Task Management',0,0) ;
--
-- Dumping data for table 'plugin_graphontrackers_bar_chart'
--
INSERT INTO plugin_graphontrackers_bar_chart (id,field_base,field_group) VALUES (2,'severity','');
INSERT INTO plugin_graphontrackers_bar_chart (id,field_base,field_group) VALUES (5,'severity','');
INSERT INTO plugin_graphontrackers_bar_chart (id,field_base,field_group) VALUES (8,'severity','');
INSERT INTO plugin_graphontrackers_bar_chart (id,field_base,field_group) VALUES (9,'multi_assigned_to','');
--
-- Dumping data for table 'plugin_graphontrackers_pie_chart'
--
INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) VALUES (1,'status_id');
INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) VALUES (4,'status_id');
INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) VALUES (7,'status_id');
INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) VALUES (3,'assigned_to');
INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) VALUES (6,'assigned_to');
--
-- Dumping data for table 'plugin_graphontrackers_gantt_chart'
--
INSERT INTO plugin_graphontrackers_gantt_chart (id,field_start,field_due, field_finish,field_percentage,field_righttext,scale,as_of_date,summary) VALUES (10,'start_date','due_date', 'end_date','percent','severity','day',0,'summary');

