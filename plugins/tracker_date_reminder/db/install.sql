## 
## Sql Install Script
##
#
# Add a new table 'artifact_date_reminder_settings'
#
DROP TABLE IF EXISTS artifact_date_reminder_settings;
CREATE TABLE artifact_date_reminder_settings (
  reminder_id int(11) NOT NULL auto_increment,
  field_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  notification_start int(11) NOT NULL default '0',
  notification_type int(11) NOT NULL default '0',
  frequency int(11) NOT NULL default '0',
  recurse int(11) NOT NULL default '0',
  notified_people varchar(255) NOT NULL default '',
  PRIMARY KEY (reminder_id)
);

#
# Add a new table 'artifact_date_reminder_processing'
#
DROP TABLE IF EXISTS artifact_date_reminder_processing;
CREATE TABLE artifact_date_reminder_processing (
  notification_id int(11) NOT NULL auto_increment,
  reminder_id int(11) NOT NULL default '0',
  artifact_id int(11) NOT NULL default '0',
  field_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  notification_sent int(11) NOT NULL default '0',
  PRIMARY KEY (notification_id)
);
