CREATE TABLE IF NOT EXISTS plugin_roadmap_widget (
    id int(11) unsigned NOT NULL auto_increment PRIMARY KEY,
    owner_id int(11) unsigned NOT NULL default '0',
    owner_type varchar(1) NOT NULL default 'u',
    title TEXT NOT NULL,
    tracker_id INT(11) NOT NULL,
    KEY (owner_id, owner_type)
) ENGINE=InnoDB;
