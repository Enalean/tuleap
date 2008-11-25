DROP TABLE IF EXISTS plugin_graphontrackers_scrum_burndown;
CREATE TABLE plugin_graphontrackers_scrum_burndown(
  id int(11)  NOT NULL PRIMARY KEY ,
  start_date int(11),
  duration int(11)
);

