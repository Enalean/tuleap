#alter table groups add column type int not null default 1;

#create index idx_groups_type on groups(type);

#create table group_type (
#type_id int not null auto_increment primary key,
#name text
#);

#INSERT INTO group_type VALUES ('1','Project');
#INSERT INTO group_type VALUES ('2','Foundry');

##UPDATE group_type set name='Foundry' where type_id=2;

#convert some projects to foundries

#games
#UPDATE groups SET type='2',unix_group_name='games' WHERE group_id=6772;
#java
#UPDATE groups SET type='2' WHERE group_id=6770;
#printing
#UPDATE groups SET type='2' WHERE group_id=1872;
#graphics
#UPDATE groups SET type='2',unix_group_name='3d' WHERE group_id=6771;

#this table is populated nightly from perl cron job
create table foundry_projects (
id int not null auto_increment primary key,
foundry_id int not null,
project_id int not null
);

create index idx_foundry_projects_foundry on foundry_projects(foundry_id);

create table foundry_preferred_projects (
foundry_project_id int not null auto_increment primary key,
foundry_id int not null,
group_id int not null,
rank int not null
);

create index idx_foundry_project_group on foundry_preferred_projects(group_id);
create index idx_foundry_project_group_rank on foundry_preferred_projects(group_id,rank);

create table foundry_news (
foundry_news_id  int not null auto_increment primary key,
foundry_id int not null,
news_id int not null,
approve_date int not null default 0,
is_approved int not null default 0
);

create index idx_foundry_news_foundry on foundry_news(foundry_id);
create index idx_foundry_news_foundry_approved_date on foundry_news(foundry_id,is_approved,approve_date);
create index idx_foundry_news_foundry_approved on foundry_news(foundry_id,is_approved);

create table foundry_data (
foundry_id int not null auto_increment primary key,
freeform1_html text,
freeform2_html text,
sponsor1_html text,
sponsor2_html text
);
#alter table groups drop column portal_freeform;

alter table foundry_data add column guide_image_id int not null default 0;
alter table foundry_data add column logo_image_id int not null default 0;

