#
#
#  permanent, archival ratings table
#  lists every rating of every user
#
#

CREATE TABLE user_ratings (
rated_by int not null default 0,
user_id int not null default 0,
rate_field int not null default 0,
rating int not null default 0
);

CREATE INDEX idx_user_ratings_rated_by on user_ratings(rated_by);
CREATE INDEX idx_user_ratings_user_id on user_ratings(user_id);

#
#
#  Permanent diary table
#
#

create table user_diary (
id int not null default 0 auto_increment primary key,
user_id int not null default 0, 
date_posted int not null default 0,
summary text,
details text
);

create index idx_user_diary_user_date on user_diary(user_id,date_posted);
create index idx_user_diary_date on user_diary(date_posted);
create index idx_user_diary_user on user_diary(user_id);

#
#
#  Users monitoring diaries
#
#

create table user_diary_monitor (
monitor_id int not null default 0 auto_increment primary key,
user_monitored int not null default 0,
user_id int not null default 0
);

create index idx_user_diary_monitor_user on user_diary_monitor(user_id);

#
#
#  ONE TIME RUN
#  
#  admins on the top 1% of projects will be trusted initially
#  with average ratings 
#  
#

DROP TABLE IF EXISTS user_metric0;

create table user_metric0 (
ranking int not null default 0 auto_increment primary key,
user_id int not null default 0,
times_ranked int not null default 0,
avg_raters_importance float(8,8) not null default 0,
avg_rating float(8,8) not null default 0,
metric float(8,8) not null default 0,
percentile float(8,8) not null default 0,
importance_factor float(8,8) not null default 0
);

CREATE INDEX idx_user_metric0_user_id on user_metric0 (user_id);

#
#
#  10200 is the group_id for the sfpeerratings project
#
#
INSERT INTO user_metric0 SELECT DISTINCT '',user.user_id,5,1.25,1,0,0,1.25 
FROM user,user_group,project_weekly_metric 
WHERE user.user_id=user_group.user_id 
AND user_group.group_id=10200 
AND user_group.admin_flags='A';

#select count(*) from user_metric0
#to get 70 number below

UPDATE user_metric0 SET 
metric=(log(times_ranked)*avg_rating),
percentile=(100-(100*((ranking-1)/70))),
importance_factor=(1+((percentile/100)*.5));


#
#
#  Set up some random test data
#  
#  
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),1,((RAND()*6)-3) FROM user_metric0;
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),2,((RAND()*6)-3) FROM user_metric0;
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),3,((RAND()*6)-3) FROM user_metric0;

#
#
#  ITERATION 1
#
#  temp table used for grabbing only trusted ratings as we
#  iterate through the process
#
#


DROP TABLE IF EXISTS user_metric_tmp1_1;

create table user_metric_tmp1_1 (
user_id int not null default 0,
times_ranked int not null default 0,
avg_raters_importance float(8,8) not null default 0,
avg_rating float(8,8) not null default 0,
metric float(8,8) not null default 0);

INSERT INTO user_metric_tmp1_1
SELECT user_ratings.user_id,count(*) AS count,
avg(user_metric0.avg_raters_importance),
avg(user_ratings.rating),0
FROM user_ratings,user_metric0
WHERE user_ratings.rated_by=user_metric0.user_id
GROUP BY user_ratings.user_id;

UPDATE user_metric_tmp1_1 SET metric=(log(times_ranked)*avg_raters_importance*avg_rating);

#  
#  
#  HERE carry forward the users from the last round if they are not in this round
#
#  INSERT INTO user_metric_tmp1_1 SELECT times_ranked,avg_raters_importance,avg_rating,metric
#  FRON user_metric0 WHERE user_id NOT IN (LIST OF IDS IN user_metric_tmp1_1);
#

DROP TABLE IF EXISTS user_metric1;

create table user_metric1 (
ranking int not null default 0 auto_increment primary key,
user_id int not null default 0,
times_ranked int not null default 0,
avg_raters_importance float(8,8) not null default 0,
avg_rating float(8,8) not null default 0,
metric float(8,8) not null default 0,
percentile float(8,8) not null default 0,
importance_factor float(8,8) not null default 0);

#
#
#  user_metric1 now contains a list of trusted users as of this round
#
#
INSERT INTO user_metric1
SELECT '',user_id,times_ranked,avg_raters_importance,avg_rating,metric,0,0
FROM user_metric_tmp1_1
HAVING metric > 1.6 
ORDER BY metric DESC;

#select count(*) from user_metric_tmp1_1
#to get XXXXX number below

UPDATE user_metric1 SET
percentile=(100-(100*((ranking-1)/199))),
importance_factor=(1+((percentile/100)*.5));


#
#  ITERATION 2
#
#  Set up some random test data for second round
#
#
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),1,((RAND()*6)-3) FROM user_metric1 WHERE metric > 2;
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),2,((RAND()*6)-3) FROM user_metric1 WHERE metric > 2;
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),3,((RAND()*6)-3) FROM user_metric1 WHERE metric > 2;

#
# noise data from all users
#
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),1,((RAND()*6)-3) FROM user;
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),2,((RAND()*6)-3) FROM user;
INSERT INTO user_ratings SELECT user_id,(RAND()*50000),3,((RAND()*6)-3) FROM user;


#
#  grab the inital trusted users and add them to new trusted users
#
#  INSERT INTO user_metric1 SELECT * FROM user_metric0 WHERE user_id NOT IN (- IDS IN user_metric1 -)
#
#  re-run round1 drawing the data from user_metric1 instead of user_metric0
#




#
#
#   final metric table
#
#
create table user_trust_metric (
ranking int not null default 0 auto_increment primary key,
user_id int not null default 0,
times_ranked int not null default 0,
avg_raters_importance float(8,8) not null default 0,
avg_rating float(8,8) not null default 0,
metric float(8,8) not null default 0,
percentile float(8,8) not null default 0,
importance_factor float(8,8) not null default 0
);

#
#
#  metric is log(times_ranked)*avg(raters_importance)*avg(rating)
#  percentile is (100-(100*((ranking-1)/$row_count)))
#  importance_factor is (1+(percentile*.5))
#
#
