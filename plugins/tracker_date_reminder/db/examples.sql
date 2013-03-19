-- For the default Task tracker, this will send to project admin and tracker admins
-- * 1 e-mail 2 days before 'Start Date' date
-- * 1 e-mail on 'Start Date' date
-- * 1 e-mail 2 days after 'Start Date' date
INSERT INTO artifact_date_reminder_settings
            (reminder_id, field_id, group_artifact_id, notification_start, notification_type, frequency, recurse, notified_people)
VALUES      (1,           5,        2,                 2,                  0,                 2,         3,       'g4,g15');

-- For the default Task tracker, this will send to submitter and project members
-- * 1 e-mail 1 days after 'End Date' date
-- * 1 e-mail 3 days after 'End Date' date
INSERT INTO artifact_date_reminder_settings
            (reminder_id, field_id, group_artifact_id, notification_start, notification_type, frequency, recurse, notified_people)
VALUES      (2,           18,       2,                 1,                  1,                 2,         2,       '1,g3');
