create table project_group_list (
group_project_id int not null primary key auto_increment,
group_id int not null,
project_name text not null
);

create index idx_project_group_list_group_id on project_group_list(group_id);

create table project_task (
project_task_id int not null primary key auto_increment,
group_project_id int not null,
summary text not null,
details text not null,
percent_complete int not null,
priority int not null,
hours int not null,
start_date int not null,
end_date int not null,
created_by int not null,
status_id int not null
);

create index idx_project_task_group_project_id on project_task(group_project_id);

create table project_dependencies (
project_depend_id int not null auto_increment primary key,
project_task_id int not null,
is_dependent_on_task_id int not null
);

create index idx_project_dependencies_task_id on project_dependencies(project_task_id);
create index idx_project_is_dependent_on_task_id on project_dependencies(is_dependent_on_task_id);

create table project_assigned_to (
project_assigned_id int not null auto_increment primary key,
project_task_id int not null,
assigned_to_id int not null
);

create index idx_project_assigned_to_task_id on project_assigned_to(project_task_id);
create index idx_project_assigned_to_assigned_to on project_assigned_to(assigned_to_id);

create table project_history (
project_history_id int not null auto_increment primary key,
project_task_id int not null,
field_changed text not null,
old_value text not null,
mod_by int not null,
date int not null
);

create index idx_project_history_task_id on project_history(project_task_id);

create table project_status (
status_id int not null primary key auto_increment,
status_name text not null
);

insert into project_status values ('','Open');
insert into project_status values ('','Closed');
insert into project_status values ('100','None');

