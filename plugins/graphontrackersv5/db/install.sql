## 
## Sql Install Script
##
DROP TABLE IF EXISTS plugin_graphontrackersv5_report_graphic;
CREATE TABLE plugin_graphontrackersv5_report_graphic (
  report_graphic_id int(11)  NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  group_artifact_id int(11) ,
  user_id int(11) ,
  name varchar(255) ,
  description varchar(255) ,
  scope char(1) 
);
DROP TABLE IF EXISTS plugin_graphontrackersv5_chart;
CREATE TABLE plugin_graphontrackersv5_chart (
  id int(11)  NOT NULL PRIMARY KEY AUTO_INCREMENT ,
  old_id int(11),
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
DROP TABLE IF EXISTS plugin_graphontrackersv5_gantt_chart;
CREATE TABLE plugin_graphontrackersv5_gantt_chart(
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
DROP TABLE IF EXISTS plugin_graphontrackersv5_pie_chart;
CREATE TABLE plugin_graphontrackersv5_pie_chart(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_base varchar(255)
);
DROP TABLE IF EXISTS plugin_graphontrackersv5_bar_chart;
CREATE TABLE plugin_graphontrackersv5_bar_chart(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_base varchar(255) ,
  field_group varchar(255)
);

DROP TABLE IF EXISTS plugin_graphontrackersv5_widget_chart;
CREATE TABLE IF NOT EXISTS plugin_graphontrackersv5_widget_chart (
  id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  title varchar(255) NOT NULL,
  chart_id TEXT NOT NULL,
  KEY (owner_id, owner_type)
);

DROP TABLE IF EXISTS plugin_graphontrackersv5_scrum_burndown;
CREATE TABLE plugin_graphontrackersv5_scrum_burndown(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_id int(11),
  start_date int(11),
  duration int(11)
);

-- DROP TABLE IF EXISTS plugin_graphontrackersv5_scrum_burnup;
-- CREATE TABLE plugin_graphontrackersv5_scrum_burnup(
--   id int(11)  NOT NULL PRIMARY KEY ,
--   remaining_field_id int(11),
--   done_field_id int(11),
--   start_date int(11),
--   duration int(11)
-- );

DROP TABLE IF EXISTS plugin_graphontrackersv5_evolution_chart;
CREATE TABLE plugin_graphontrackersv5_evolution_chart(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_id int(11),
  start_date int(11),
  step int(11),
  unit int(11)
);
