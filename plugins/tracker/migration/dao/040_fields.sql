--  Fields
INSERT INTO tracker_field (
    old_id,
    tracker_id,
    parent_id,
    formElement_type,
    name,
    label,
    description,
    use_it,
    rank,
    scope,
    required)
SELECT field_id,
  group_artifact_id,
  field_set_id,
  '',
  field_name,
  REPLACE(REPLACE(label, '&gt;', '>'), '&lt;', '<'),
  REPLACE(REPLACE(description, '&gt;', '>'), '&lt;', '<'),
  0,
  0,
  scope,
  IF(empty_ok = 1 
        OR field_name = 'submitted_by'
        OR field_name = 'open_date'
        OR field_name = 'last_update_date'
        OR field_name = 'artifact_id'
    , 0
    , 1)
FROM artifact_field
WHERE field_name NOT IN('comment_type_id');

--  TODO Manage comment type field

--  field > use_it & rank
UPDATE tracker_field AS f, artifact_field_usage AS u
SET f.use_it = u.use_it, f.rank = u.place, f.parent_id = If(u.use_it, f.parent_id, 0)
WHERE f.old_id = u.field_id
  AND f.tracker_id = u.group_artifact_id;

--  Reorder Fields for prepareRanking usage
SET @counter = 0;
SET @previous = NULL;
UPDATE tracker_field 
        INNER JOIN (SELECT @counter := IF(@previous = parent_id, @counter + 1, 1) AS new_rank, 
                           @previous := parent_id, 
                           tracker_field.* 
                    FROM tracker_field 
                    ORDER BY parent_id, rank, id
        ) as R1 USING(parent_id,id)
SET tracker_field.rank = R1.new_rank;

--  field > formElement_type
UPDATE tracker_field AS f, artifact_field as a
SET f.formElement_type = CASE 
        WHEN a.display_type = 'SB' AND f.name = 'submitted_by' THEN 'subby'  
        WHEN a.display_type = 'SB' THEN 'sb'
        WHEN a.display_type = 'MB' THEN 'msb'
        WHEN a.display_type = 'TF' AND a.data_type = 1 THEN 'string'
        WHEN a.display_type = 'TF' AND a.data_type = 2 AND name <> 'artifact_id' THEN 'int'
        WHEN a.display_type = 'TF' AND a.data_type = 2 AND name = 'artifact_id' THEN 'aid'
        WHEN a.display_type = 'TF' AND a.data_type = 3 THEN 'float'
        WHEN a.display_type = 'TA' THEN 'text'
        WHEN a.display_type = 'DF' AND f.name = 'open_date'        THEN 'subon'   
        WHEN a.display_type = 'DF' AND f.name = 'last_update_date' THEN 'lud'    
        WHEN a.display_type = 'DF' THEN 'date'
        ELSE a.display_type END,
    f.notifications = CASE 
        WHEN a.display_type = 'SB' AND f.name = 'submitted_by' THEN 1
        WHEN f.name = 'assigned_to' OR f.name = 'multi_assigned_to' THEN 1
        ELSE 0 END
WHERE f.old_id = a.field_id
  AND f.tracker_id = a.group_artifact_id;


INSERT INTO tracker_field_list(field_id, bind_type)
SELECT f.id, CASE WHEN a.value_function = '' OR a.value_function IS NULL THEN 'static' ELSE 'users' END
FROM tracker_field AS f INNER JOIN artifact_field as a 
     ON (a.field_id = f.old_id AND a.group_artifact_id = f.tracker_id)
WHERE f.formElement_type IN ('sb', 'msb');

--  field > static
INSERT INTO tracker_field_list_bind_static(field_id, is_rank_alpha)
SELECT field_id, 0 FROM tracker_field_list WHERE bind_type = 'static';

INSERT INTO tracker_field_list_bind_static_value(old_id, field_id, label, description, rank, is_hidden)
SELECT v.value_id, 
    f.id, 
    REPLACE(REPLACE(v.value, '&gt;', '>'), '&lt;', '<'),
    REPLACE(REPLACE(v.description, '&gt;', '>'), '&lt;', '<'),
    v.order_id,
    IF(v.status = 'H', 1, 0)
FROM artifact_field_value_list AS v INNER JOIN tracker_field AS f
     ON (v.field_id = f.old_id AND v.group_artifact_id = f.tracker_id AND v.value_id != 100);

--  field > users
INSERT INTO tracker_field_list_bind_users(field_id, value_function)
SELECT l.field_id, a.value_function
FROM tracker_field_list AS l 
     INNER JOIN tracker_field AS f ON (f.id = l.field_id)
     INNER JOIN artifact_field as a ON (a.field_id = f.old_id AND a.group_artifact_id = f.tracker_id)
WHERE bind_type = 'users';

--  field > decorators (take the severity and put the default css value)
INSERT INTO tracker_field_list_bind_decorator(field_id, value_id, red, green, blue)
SELECT f.id, b.id, 218 as red,
    CASE b.old_id
    WHEN 1 THEN 218
    WHEN 2 THEN 208
    WHEN 3 THEN 202
    WHEN 4 THEN 192
    WHEN 5 THEN 186
    WHEN 6 THEN 176
    WHEN 7 THEN 170
    WHEN 8 THEN 144
    ELSE 138 END as green,
    CASE b.old_id
    WHEN 1 THEN 218
    WHEN 2 THEN 208
    WHEN 3 THEN 202
    WHEN 4 THEN 192
    WHEN 5 THEN 186
    WHEN 6 THEN 176
    WHEN 7 THEN 170
    WHEN 8 THEN 144
    ELSE 138 END as blue
FROM tracker_field_list_bind_static_value AS b 
     INNER JOIN tracker_field AS f  ON (f.id = b.field_id)
     INNER JOIN artifact_field as a ON (a.field_id = f.old_id AND a.group_artifact_id = f.tracker_id)
WHERE a.field_name = 'severity' AND b.old_id BETWEEN 1 AND 9;


INSERT INTO tracker_field_list(field_id, bind_type)
SELECT id, 'users'
FROM tracker_field 
WHERE formElement_type = 'subby';

INSERT INTO tracker_field_list_bind_users(field_id, value_function)
SELECT id, 'artifact_submitters'
FROM tracker_field 
WHERE formElement_type = 'subby';

