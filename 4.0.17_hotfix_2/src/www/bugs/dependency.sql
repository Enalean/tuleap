create table bug_task_dependencies (
bug_depend_id int not null auto_increment primary key,
bug_id int not null,
is_dependent_on_task_id int not null);

create index idx_bug_task_dependencies_bug_id on bug_task_dependencies(bug_id);
create index idx_bug_task_is_dependent_on_task_id on bug_task_dependencies(is_dependent_on_task_id);

create table bug_bug_dependencies (
bug_depend_id int not null auto_increment primary key,
bug_id int not null,
is_dependent_on_bug_id int not null);

create index idx_bug_bug_dependencies_bug_id on bug_bug_dependencies(bug_id);
create index idx_bug_bug_is_dependent_on_task_id on bug_bug_dependencies(is_dependent_on_bug_id);

