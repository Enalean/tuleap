#CREATE TABLE filemodule (
#  filemodule_id int(11) DEFAULT '0' NOT NULL auto_increment,
#  group_id int(11) DEFAULT '0' NOT NULL,
#  module_name varchar(40),
#  recent_filerelease varchar(20) DEFAULT '' NOT NULL,
#  PRIMARY KEY (filemodule_id),
#  KEY idx_filemodule_group_id (group_id)
#);
#INSERT INTO frs_file
#SELECT filerelease_id,group_id,filename,'100','9999','9999',
#release_time,file_size,status,post_time
#FROM filerelease;

#CREATE TABLE filerelease (
#filerelease_id int(11) DEFAULT '0' NOT NULL auto_increment,
#group_id int(11) DEFAULT '0' NOT NULL,
#user_id int(11) DEFAULT '0' NOT NULL,
#unix_box varchar(20) DEFAULT 'remission' NOT NULL,
#unix_partition int(11) DEFAULT '0' NOT NULL,
#text_notes text,
#text_changes text,
#release_version varchar(20),
#filename varchar(80),
#filemodule_id int(11) DEFAULT '0' NOT NULL,
#file_type varchar(50),
#release_time int(11),
#downloads int(11) DEFAULT '0' NOT NULL,
#file_size int(11),
#post_time int(11) DEFAULT '0' NOT NULL,
#text_format int(11) DEFAULT '0' NOT NULL,
#downloads_week int(11) DEFAULT '0' NOT NULL,
#status char(1) DEFAULT 'N' NOT NULL,
#old_filename varchar(80) DEFAULT '' NOT NULL,
#PRIMARY KEY (filerelease_id),
#KEY group_id_idx (group_id),
#KEY user_id_idx (user_id),
#KEY unix_box_idx (unix_box),
#KEY post_time_idx (post_time)
#);


#
#
#
#    create the sql for the new filerelease system
#    you also must run the /www/admin/upgrade_filerelease_data.php script
#
#
#

DROP TABLE IF EXISTS frs_package;

CREATE TABLE frs_package (
  package_id int(11) DEFAULT '0' NOT NULL auto_increment,
  group_id int(11) DEFAULT '0' NOT NULL,
  name text,
  status_id int NOT NULL DEFAULT 0,
  PRIMARY KEY (package_id),
  KEY idx_package_group_id (group_id)
);

#
#  begin migration of data to new system
#
INSERT INTO frs_package SELECT filemodule_id,group_id,module_name,'1' FROM filemodule;

DROP TABLE IF EXISTS frs_release;

CREATE TABLE frs_release (
  release_id int(11) DEFAULT '0' NOT NULL auto_increment,
  package_id int(11) DEFAULT '0' NOT NULL,
  name text,
  notes text,
  changes text,
  status_id int NOT NULL DEFAULT 0,
  preformatted int NOT NULL DEFAULT 0,
  release_date int NOT NULL DEFAULT 0,
  released_by int not null default 0,
  PRIMARY KEY (release_id),
  KEY idx_frs_release_by (released_by),
  KEY idx_frs_release_date (release_date),
  KEY idx_frs_release_package (package_id)
);

#
#  more migration of releases - will have to write a prog to insert release notes
#
INSERT INTO frs_release SELECT DISTINCT '',filemodule_id,release_version,'','',1,'',0,100 
FROM filerelease WHERE status='A';

DROP TABLE IF EXISTS frs_filetype;

CREATE TABLE frs_filetype (
  type_id int NOT NULL primary key auto_increment,
  name text
);

INSERT INTO frs_filetype VALUES ('1000','.deb');
INSERT INTO frs_filetype VALUES ('2000','.rpm');
INSERT INTO frs_filetype VALUES ('3000','.zip');
INSERT INTO frs_filetype VALUES ('','.bz2');
INSERT INTO frs_filetype VALUES ('','.gz');
INSERT INTO frs_filetype VALUES ('5000','Source .zip');
INSERT INTO frs_filetype VALUES ('','Source .bz2');
INSERT INTO frs_filetype VALUES ('','Source .gz');
INSERT INTO frs_filetype VALUES ('5100','Source .rpm');
INSERT INTO frs_filetype VALUES ('5900','Other Source File');
INSERT INTO frs_filetype VALUES ('8000','.jpg');
INSERT INTO frs_filetype VALUES ('','text');
INSERT INTO frs_filetype VALUES ('','html');
INSERT INTO frs_filetype VALUES ('','pdf');
INSERT INTO frs_filetype VALUES ('9999','Other');

DROP TABLE IF EXISTS frs_status;

CREATE TABLE frs_status (
status_id int NOT NULL primary key auto_increment,
name text
);

INSERT INTO frs_status VALUES ('1','Active');
INSERT INTO frs_status VALUES ('3','Hidden');

DROP TABLE IF EXISTS frs_processor;

CREATE TABLE frs_processor (
  processor_id int NOT NULL primary key auto_increment,
  name text
);

INSERT INTO frs_processor VALUES ('1000','i386');
INSERT INTO frs_processor VALUES ('6000','IA64');
INSERT INTO frs_processor VALUES ('7000','Alpha');
INSERT INTO frs_processor VALUES ('8000','Any');
INSERT INTO frs_processor VALUES ('2000','PPC');
INSERT INTO frs_processor VALUES ('3000','MIPS');
INSERT INTO frs_processor VALUES ('4000','Sparc');
INSERT INTO frs_processor VALUES ('5000','UltraSparc');
INSERT INTO frs_processor VALUES ('9999','Other');

DROP TABLE IF EXISTS frs_file;

CREATE TABLE frs_file (
  file_id int DEFAULT '0' NOT NULL auto_increment,
  filename text,
  release_id int DEFAULT '0' NOT NULL,
  type_id int DEFAULT '0' NOT NULL,
  processor_id int DEFAULT '0' NOT NULL,
  release_time int DEFAULT '0' NOT NULL,
  file_size int DEFAULT '0' NOT NULL,
  post_date int DEFAULT '0' NOT NULL,
  PRIMARY KEY (file_id),
  key idx_frs_file_release_id (release_id),
  key idx_frs_file_type (type_id),
  key idx_frs_file_date (post_date),
  key idx_frs_file_processor (processor_id)
);

create index idx_frs_file_name on frs_file(filename(45));
