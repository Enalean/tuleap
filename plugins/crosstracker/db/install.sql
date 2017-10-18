DROP TABLE IF EXISTS plugin_crosstracker_report;
CREATE TABLE plugin_crosstracker_report (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB;

DROP TABLE IF EXISTS plugin_crosstracker_report_tracker;
CREATE TABLE plugin_crosstracker_report_tracker (
    report_id INT(11) NOT NULL,
    tracker_id INT(11) NOT NULL,
    PRIMARY KEY (report_id, tracker_id),
    INDEX idx_report_id(report_id)
) ENGINE=InnoDB;
