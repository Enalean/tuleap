create table forum_saved_place (
saved_place_id int not null auto_increment primary key,
user_id int not null,
forum_id int not null,
save_date int not null
);

alter table forum_monitored_threads rename as forum_monitored_forums;
delete from forum_monitored_forums;
alter table forum_monitored_forums change column thread_id forum_id int not null;
