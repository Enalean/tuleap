create table db_images (
id int not null auto_increment primary key,
group_id int not null default 0,
description text not null default '',
bin_data longblob NOT NULL,
filename text DEFAULT '' NOT NULL,
filesize int DEFAULT 0 NOT NULL,
filetype text  DEFAULT '' NOT NULL,
width int default 0 not null,
height int default 0 not null
);

create index idx_db_images_group on db_images (group_id);
