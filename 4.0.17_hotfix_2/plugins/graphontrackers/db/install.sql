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

