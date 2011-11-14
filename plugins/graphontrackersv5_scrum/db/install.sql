DROP TABLE IF EXISTS plugin_graphontrackersv5_scrum_burndown;
CREATE TABLE plugin_graphontrackersv5_scrum_burndown(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_id int(11),
  start_date int(11),
  duration int(11)
);

DROP TABLE IF EXISTS plugin_graphontrackersv5_scrum_burnup;
CREATE TABLE plugin_graphontrackersv5_scrum_burnup(
  id int(11)  NOT NULL PRIMARY KEY ,
  remaining_field_id int(11),
  done_field_id int(11),
  start_date int(11),
  duration int(11)
);

