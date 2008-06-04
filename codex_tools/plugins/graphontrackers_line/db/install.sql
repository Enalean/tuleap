DROP TABLE IF EXISTS plugin_graphontrackers_line_chart;
CREATE TABLE plugin_graphontrackers_line_chart(
  id int(11)  NOT NULL PRIMARY KEY ,
  field_base varchar(255) ,
  state_source varchar(255) ,
  state_target varchar(255) ,
  date_min int(11) ,
  date_max int(11) ,
  date_reference int(11) ,
  method varchar(255) 
);
