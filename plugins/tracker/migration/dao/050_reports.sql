
--  reports
INSERT INTO tracker_report(old_id, project_id, tracker_id, is_default, user_id, name, description, current_renderer_id, is_query_displayed)
SELECT report_id as old_id, G2.group_id as project_id, G2.group_artifact_id as tracker_id, is_default, CASE R.scope 
                                                                                            WHEN 'I' THEN user_id 
                                                                                            ELSE NULL END AS user_id, R.name, R.description, 0, 1
FROM artifact_report AS R 
     INNER JOIN artifact_group_list AS G USING (group_artifact_id),
     artifact_group_list AS G2
WHERE R.report_id = 100;

INSERT INTO tracker_report(old_id, project_id, tracker_id, is_default, user_id, name, description, current_renderer_id, is_query_displayed)
SELECT report_id as old_id, group_id as project_id, group_artifact_id as tracker_id, is_default, CASE R.scope 
                                                                                            WHEN 'I' THEN user_id 
                                                                                            ELSE NULL END AS user_id, R.name, R.description, 0, 1
FROM artifact_report AS R INNER JOIN artifact_group_list AS G USING (group_artifact_id)
WHERE R.report_id <> 100;


--  reports > criteria
INSERT INTO tracker_report_criteria(report_id, field_id, rank, is_advanced)
SELECT R.id, F.id, place_query, 0
FROM tracker_report AS R
     INNER JOIN artifact_report_field AS RF ON (R.old_id = RF.report_id)
     INNER JOIN tracker_field AS F ON(F.name = RF.field_name AND F.tracker_id = R.tracker_id)
WHERE show_on_query = 1
ORDER BY R.id, place_query;

--  Reorder report fields for prepareRanking usage
SET @counter = 0;
SET @previous = NULL;
UPDATE tracker_report_criteria 
        INNER JOIN (SELECT @counter := IF(@previous = report_id, @counter + 1, 1) AS new_rank, 
                           @previous := report_id, 
                           tracker_report_criteria.* 
                    FROM tracker_report_criteria 
                    ORDER BY report_id, rank, field_id
        ) as R1 USING(report_id,field_id)
SET tracker_report_criteria.rank = R1.new_rank;


