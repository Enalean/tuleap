-- Fieldsets
INSERT INTO tracker_fieldset(id, tracker_id, name, description, rank)
SELECT field_set_id, 
    group_artifact_id, 
    REPLACE(REPLACE(name, '&gt;', '>'), '&lt;', '<'),
    REPLACE(REPLACE(description, '&gt;', '>'), '&lt;', '<'), 
    rank
FROM artifact_field_set;

-- Add cc fieldset
INSERT INTO tracker_fieldset(tracker_id, name, description, rank)
SELECT DISTINCT T1.id, 'CC List', 'Dependency links from an artifact to one or several other artifacts', S1.rank
FROM tracker AS T1 
     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id);

-- Add attachments fieldset
INSERT INTO tracker_fieldset(tracker_id, name, description, rank)
SELECT DISTINCT T1.id, 'Attachments', 'Attach virtually any piece of information to an artifact in the form of a file', S1.rank
FROM tracker AS T1 
     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id);

-- Add dependencies fieldset
INSERT INTO tracker_fieldset(tracker_id, name, description, rank)
SELECT DISTINCT T1.id, 'Dependencies', 'Establish a dependency link from an artifact to one or several other artifacts belonging to any of the tracker of any project', S1.rank
FROM tracker AS T1 
     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id);

-- Add references fieldset
INSERT INTO tracker_fieldset(tracker_id, name, description, rank)
SELECT DISTINCT T1.id, 'References', 'Cross-reference any artifact, or any other object', S1.rank
FROM tracker AS T1 
     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id);

-- Add permissions fieldset
INSERT INTO tracker_fieldset(tracker_id, name, description, rank)
SELECT DISTINCT T1.id, 'Permissions', 'Restrict access to artifact', S1.rank
FROM tracker AS T1 
     INNER JOIN (SELECT max(rank)+1 as rank, tracker_id FROM tracker_fieldset GROUP BY tracker_id) AS S1 ON (T1.id = S1.tracker_id);

--  Reorder Fieldsets for prepareRanking usage
SET @counter = 0;
SET @previous = NULL;
UPDATE tracker_fieldset 
        INNER JOIN (SELECT @counter := IF(@previous = tracker_id, @counter + 1, 1) AS new_rank, 
                           @previous := tracker_id, 
                           tracker_fieldset.* 
                    FROM tracker_fieldset 
                    ORDER BY tracker_id, rank, id
        ) as R1 USING(tracker_id,id)
SET tracker_fieldset.rank = R1.new_rank;

