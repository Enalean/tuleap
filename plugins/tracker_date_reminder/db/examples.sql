-- For the default Task tracker, this will send to project admin and tracker admins
-- * 1 e-mail 2 days before 'Start Date' date
-- * 1 e-mail on 'Start Date' date
-- * 1 e-mail 2 days after 'Start Date' date
INSERT INTO artifact_date_reminder_settings(reminder_id, field_id, group_artifact_id, notification_start, notification_type, frequency, recurse, notified_people)
            VALUES                         (1,           5,        2,                 2,                  0,                 2,         3,       'g4,g15');
