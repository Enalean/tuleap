DROP TABLE IF EXISTS plugin_admindelegation_service_user;
DROP TABLE IF EXISTS plugin_admindelegation_service_user_log;

-- Remove widget
DELETE FROM layouts_contents WHERE name = "admindelegation";
DELETE FROM layouts_contents WHERE name = "admindelegation_projects";

-- Reorder widgets (thanks to N. Terray)
SET @counter = 0;
SET @previous = NULL;
UPDATE layouts_contents
         INNER JOIN (SELECT @counter := IF(@previous = CONCAT(owner_id, owner_type, layout_id, column_id), @counter + 1, 1) AS new_rank,
                            @previous := CONCAT(owner_id, owner_type, layout_id, column_id),
                            layouts_contents.*
                     FROM layouts_contents
                     ORDER BY owner_id, owner_type, layout_id, column_id, rank, name, content_id
                     ) as R1 
                     USING (owner_id, owner_type, layout_id, column_id, rank, name, content_id)
SET layouts_contents.rank = R1.new_rank;