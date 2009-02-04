DELETE FROM service WHERE short_name='hudson';

DROP TABLE plugin_hudson_job;
DROP TABLE plugin_hudson_widget;

DELETE FROM reference WHERE service_short_name='hudson';
