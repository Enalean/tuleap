## 
## Sql Install Script
##
DROP TABLE IF EXISTS plugin_graphontrackers_report_graphic;
CREATE TABLE plugin_graphontrackers_report_graphic (
  report_graphic_id int(11)  NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  group_artifact_id int(11) ,
  user_id int(11) ,
  name varchar(255) ,
  description varchar(255) ,
  scope char(1) 
);
DROP TABLE IF EXISTS plugin_graphontrackers_chart;
CREATE TABLE plugin_graphontrackers_chart (
  id int(11)  NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  report_graphic_id int(11) NOT NULL,
  rank int(11) NOT NULL,
  chart_type varchar(255),
  title varchar(255),
  description text,
  width int(11) DEFAULT 600,
  height int(11) DEFAULT 400,
  KEY (report_graphic_id),
  KEY (chart_type)
);
DROP TABLE IF EXISTS plugin_graphontrackers_gantt_chart;
CREATE TABLE plugin_graphontrackers_gantt_chart(
  id int(11)  NOT NULL PRIMARY KEY,
  field_start varchar(255) ,
  field_due varchar(255) ,
  field_finish varchar(255) ,
  field_percentage varchar(255) ,
  field_righttext varchar(255) ,
  scale varchar(20) ,
  as_of_date int(11) ,
  summary varchar(255) 
);
DROP TABLE IF EXISTS plugin_graphontrackers_pie_chart;
CREATE TABLE plugin_graphontrackers_pie_chart(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_base varchar(255)
);
DROP TABLE IF EXISTS plugin_graphontrackers_bar_chart;
CREATE TABLE plugin_graphontrackers_bar_chart(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_base varchar(255) ,
  field_group varchar(255)
);


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
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (1,1,5,pie,'Status','Number of Artifacts by Status') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (2,1,10,bar,'Severity','Number of Artifacts by severity level') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (3,1,15,pie,'Assignment','Number of Artifacts by Assignee') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (4,2,5,pie,'Status','Number of Artifacts by Status') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (5,2,10,bar,'Severity','Number of Artifacts by severity level') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (6,2,15,pie,'Assignment','Number of Artifacts by Assignee') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (7,3,5,pie,'Status','Number of Artifacts by Status') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (8,3,10,bar,'Severity','Number of Artifacts by severity level') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (9,3,15,bar,'Assignment','Number of Artifacts by Assignee') ;
INSERT INTO plugin_graphontrackers_chart (id,report_graphic_id,rank,chart_type,title,description) VALUES (10,4,5,gantt,'Gantt','Gantt Chart for Task Management') ;
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


