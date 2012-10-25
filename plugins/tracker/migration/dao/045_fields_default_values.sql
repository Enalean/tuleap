-- DEFAULT_VALUES + SIZE
-- msb size
REPLACE INTO tracker_field_msb (field_id, size)
SELECT id, CAST(display_size AS SIGNED INTEGER) AS size
FROM tracker_field INNER JOIN artifact_field ON(old_id = field_id 
                                            AND tracker_id = group_artifact_id 
                                            AND display_size <> ''
                                            AND formElement_type = 'msb');
-- Default date + size
INSERT INTO tracker_field_date (field_id, default_value, default_value_type)
SELECT f.id, old.default_value, IF(old.default_value = '', 0, 1)
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'date'
    );

-- Default int + size
REPLACE INTO tracker_field_int (field_id, default_value, maxchars, size)
SELECT f.id, 
    old.default_value,
    CAST(SUBSTRING_INDEX(display_size, '/', -1) AS SIGNED INTEGER) AS maxchars, 
    CAST(SUBSTRING_INDEX(display_size, '/', 1) AS SIGNED INTEGER) AS size
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'int' AND
        display_size LIKE '%/%'
    );
    
-- Default float + size
REPLACE INTO tracker_field_float (field_id, default_value, maxchars, size)
SELECT f.id, 
       old.default_value,
       CAST(SUBSTRING_INDEX(display_size, '/', -1) AS SIGNED INTEGER) AS maxchars, 
       CAST(SUBSTRING_INDEX(display_size, '/', 1) AS SIGNED INTEGER) AS size
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'float' AND
        display_size LIKE '%/%'
    );

-- Default text + size
REPLACE INTO tracker_field_text (field_id, default_value, rows, cols)
SELECT f.id, 
       old.default_value,
       CAST(SUBSTRING_INDEX(display_size, '/', -1) AS SIGNED INTEGER) AS rows, 
       CAST(SUBSTRING_INDEX(display_size, '/', 1) AS SIGNED INTEGER) AS cols
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'text' AND
        display_size LIKE '%/%'
    );

-- Default string + size
REPLACE INTO tracker_field_string (field_id, default_value, maxchars, size)
SELECT f.id, 
       old.default_value,
       CAST(SUBSTRING_INDEX(display_size, '/', -1) AS SIGNED INTEGER) AS maxchars, 
       CAST(SUBSTRING_INDEX(display_size, '/', 1) AS SIGNED INTEGER) AS size
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'string' AND
        display_size LIKE '%/%'
    );

-- Default list

-- SB static list (default values except none)
INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
SELECT f.id, new.id 
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'sb' AND 
        (old.value_function IS NULL OR old.value_function = ""))
    INNER JOIN tracker_field_list_bind_static_value AS new ON (
        old.default_value = new.old_id AND
        new.field_id = f.id
        );

-- SB user list (default values except none)
INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
SELECT f.id, user.user_id 
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'sb' AND 
        (old.value_function IS NOT NULL AND old.value_function <> ""))
    INNER JOIN user ON (
        old.default_value = user.user_id AND
        user.user_id <> 100
        );

-- SB list (default value = none) for static and users
INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
SELECT f.id, old.default_value
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'sb' AND
        old.default_value = 100);

-- MSB static list (only *single* default values except none)
INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
SELECT f.id, new.id 
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'msb' AND 
        (old.value_function IS NULL OR old.value_function = "") AND 
        POSITION("," IN old.default_value) = 0)
    INNER JOIN tracker_field_list_bind_static_value AS new ON (
        old.default_value = new.old_id AND
        new.field_id = f.id
        );

-- MSB user list (only *single* default values except none)
INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
SELECT f.id, user.user_id 
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'msb' AND 
        (old.value_function IS NOT NULL AND old.value_function <> "") AND 
        POSITION("," IN old.default_value) = 0)
    INNER JOIN user ON (
        old.default_value = user.user_id AND
        user.user_id <> 100
        );

-- MSB list (default value = none) for static and users
INSERT INTO tracker_field_list_bind_defaultvalue (field_id, value_id)
SELECT f.id, old.default_value
FROM tracker_field AS f
    INNER JOIN artifact_field AS old ON (
        f.old_id = old.field_id AND 
        f.tracker_id = old.group_artifact_id AND 
        f.formElement_type = 'msb' AND
        old.default_value = 100);
        
