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
  field_hintleft varchar(255) ,
  field_hintcenter varchar(255) ,
  field_hintright varchar(255) ,
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

INSERT INTO plugin_graphontrackers_report_graphic (group_artifact_id,user_id,name,description,scope) 
SELECT group_artifact_id,101,'Default','Graphic Report By Default','P'
FROM artifact_group_list;

#
INSERT INTO plugin_graphontrackers_chart (report_graphic_id,rank,chart_type,title,description)
SELECT report_graphic_id, 1, 'bar', 'Assignees','Number of artifacts by assignee'
FROM plugin_graphontrackers_report_graphic;

INSERT INTO plugin_graphontrackers_bar_chart (id, field_base,field_group) 
SELECT id,'assigned_to',''
FROM plugin_graphontrackers_chart, (SELECT IFNULL(MAX(id),0) as max_id FROM plugin_graphontrackers_bar_chart) AS R
WHERE chart_type = 'bar'
  AND id > R.max_id;

#
INSERT INTO plugin_graphontrackers_chart (report_graphic_id,rank,chart_type,title,description)
SELECT report_graphic_id, 2, 'pie', 'Severity','Artifacts by severity'
FROM plugin_graphontrackers_report_graphic;

INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) 
SELECT id,'severity'
FROM plugin_graphontrackers_chart, (SELECT IFNULL(MAX(id),0) as max_id FROM plugin_graphontrackers_pie_chart) AS R
WHERE chart_type = 'pie'
  AND id > R.max_id;

#
INSERT INTO plugin_graphontrackers_chart (report_graphic_id,rank,chart_type,title,description)
SELECT report_graphic_id, 3, 'pie', 'Category','Artifacts by category'
FROM plugin_graphontrackers_report_graphic;

INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) 
SELECT id,'category_id'
FROM plugin_graphontrackers_chart, (SELECT IFNULL(MAX(id),0) as max_id FROM plugin_graphontrackers_pie_chart) AS R
WHERE chart_type = 'pie'
  AND id > R.max_id;

#
INSERT INTO plugin_graphontrackers_chart (report_graphic_id,rank,chart_type,title,description)
SELECT report_graphic_id, 4, 'pie', 'Status','Artifacts by status'
FROM plugin_graphontrackers_report_graphic;

INSERT INTO plugin_graphontrackers_pie_chart (id,field_base) 
SELECT id,'status_id'
FROM plugin_graphontrackers_chart, (SELECT IFNULL(MAX(id),0) as max_id FROM plugin_graphontrackers_pie_chart) AS R
WHERE chart_type = 'pie'
  AND id > R.max_id;

