create table survey_questions (
question_id int auto_increment primary key,
group_id int not null,
question text not null,
question_type int not null
);

CREATE INDEX idx_survey_questions_group ON survey_questions(group_id);

create table surveys (
survey_id int auto_increment primary key,
group_id int not null,
survey_title text not null,
survey_questions text not null
);

CREATE INDEX idx_surveys_group ON surveys(group_id);

create table survey_responses (
user_id int not null,
group_id int not null,
survey_id int not null,
question_id int not null,
response text not null,
date int not null
);

CREATE INDEX idx_survey_responses_user_survey ON survey_responses(user_id,survey_id);
CREATE INDEX idx_survey_responses_user_survey_question ON survey_responses(user_id,survey_id,question_id);
CREATE INDEX idx_survey_responses_survey_question ON survey_responses(survey_id,question_id);
CREATE INDEX idx_survey_responses_group_id ON survey_responses(group_id);

create table survey_rating_response (
user_id int not null,
type int not null,
id int not null,
response int not null,
date int not null
);

CREATE INDEX idx_survey_rating_responses_user_type_id ON survey_rating_response(user_id,type,id);
CREATE INDEX idx_survey_rating_responses_type_id ON survey_rating_response(type,id);

DROP table survey_rating_aggregate;

CREATE TABLE survey_rating_aggregate2 (type int not null,id int not null,response float not null,count int not null);
INSERT INTO survey_rating_aggregate2 SELECT type,id,avg(response),count(*) FROM survey_rating_response GROUP BY type,id;
DROP TABLE survey_rating_aggregate;
ALTER TABLE survey_rating_aggregate2 RENAME AS survey_rating_aggregate;
CREATE INDEX idx_survey_rating_aggregate_type_id ON survey_rating_aggregate(type,id);

create table survey_question_types (
id int not null primary key auto_increment,
type text not null
);

INSERT INTO survey_question_types VALUES ('1','Radio Buttons 1-5');
INSERT INTO survey_question_types VALUES ('2','Text Area');
INSERT INTO survey_question_types VALUES ('3','Radio Buttons Yes/No');
INSERT INTO survey_question_types VALUES ('4','Comment Only');
INSERT INTO survey_question_types VALUES ('5','Text Field');
INSERT INTO survey_question_types VALUES ('6','Poll Question');
INSERT INTO survey_question_types VALUES ('100','None');
