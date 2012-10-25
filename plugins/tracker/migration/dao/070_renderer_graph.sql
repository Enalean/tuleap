--  TODO: check that plugin graphontrackers is installed before
--  graphontrackers
-- scope <> 'P'

-- insert personal graphic report in each personal report
INSERT INTO tracker_report_renderer(old_id, report_id, renderer_type, name, description, rank)
SELECT G.report_graphic_id, R.id, 'plugin_graphontrackers', G.name, G.description, 2
FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R
WHERE G.user_id = R.user_id AND G.scope <> 'P' AND G.group_artifact_id = R.tracker_id;

-- perso graph without perso report 
-- insert perso report
INSERT INTO tracker_report(old_id, project_id, tracker_id, is_default, user_id, name, description, current_renderer_id, is_query_displayed)
SELECT DISTINCT G.report_graphic_id, T.group_id, G.group_artifact_id, 0, G.user_id, G.name, G.description, 0,1
FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R, tracker AS T
WHERE G.scope <> 'P' AND 
      G.group_artifact_id = R.tracker_id AND 
      R.user_id IS NULL  AND 
      G.report_graphic_id NOT IN (
        SELECT G.report_graphic_id 
        FROM plugin_graphontrackers_report_graphic AS G
        INNER JOIN tracker_report AS R ON (G.user_id = R.user_id AND G.scope <> 'P' AND G.group_artifact_id = R.tracker_id)
       ) AND
       R.tracker_id = T.id;
       
-- adding criteria = artifact_id to personal report created for personal graph
INSERT INTO tracker_report_criteria(report_id, field_id, rank, is_advanced) 
SELECT R.id, F.id, 1, 0
FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R, tracker_field AS F
WHERE G.scope <> 'P' AND 
      G.group_artifact_id = R.tracker_id  AND 
      R.old_id = G.report_graphic_id AND 
      F.tracker_id = R.tracker_id AND 
      F.name = 'artifact_id' ;

-- insert a renderer
INSERT INTO tracker_report_renderer(old_id, report_id, renderer_type, name, description, rank)
SELECT G.report_graphic_id, R.id, 'plugin_graphontrackers', G.name, G.description, 1
FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R
WHERE G.scope <> 'P' AND G.group_artifact_id = R.tracker_id  AND R.old_id = G.report_graphic_id;

--  scope = 'P'
INSERT INTO tracker_report_renderer(old_id, report_id, renderer_type, name, description, rank)
SELECT G.report_graphic_id, R.id, 'plugin_graphontrackers', G.name, G.description, 2
FROM plugin_graphontrackers_report_graphic AS G, tracker_report AS R
WHERE G.scope = 'P' AND G.group_artifact_id = R.tracker_id;

ALTER TABLE plugin_graphontrackers_chart 
    ADD old_report_graphic_id INT NULL AFTER report_graphic_id,
    ADD old_id INT NULL AFTER id;

-- Reorder renderer
SET @counter = 0;
SET @previous = NULL;
UPDATE tracker_report_renderer 
        INNER JOIN (SELECT @counter := IF(@previous = report_id, @counter + 1, 1) AS new_rank, 
                           @previous := report_id, 
                           tracker_report_renderer.*
                    FROM tracker_report_renderer
                    ORDER BY report_id, rank, id
        ) as R1 USING(report_id, id)
SET tracker_report_renderer.rank = R1.new_rank;

-- Update the plugin_graphontrackers_chart with the first report of each tracker
UPDATE tracker_report_renderer AS R
    INNER JOIN (SELECT tracker_id, MIN(id) AS min_report_id FROM tracker_report GROUP BY tracker_id) AS M 
        ON (M.min_report_id = R.report_id AND R.renderer_type='plugin_graphontrackers')
    INNER JOIN plugin_graphontrackers_chart AS C ON (R.old_id = C.report_graphic_id)
SET C.old_report_graphic_id = C.report_graphic_id, 
    C.report_graphic_id = R.id;

    
INSERT INTO plugin_graphontrackers_chart(report_graphic_id, old_report_graphic_id, old_id, rank, chart_type, title,description, width, height)
SELECT Re.id, Re.old_id, C.id, C.rank, C.chart_type, C.title, C.description, C.width, C.height
FROM (SELECT tracker_id, MIN(id) AS min_report_id FROM tracker_report GROUP BY tracker_id) AS M
    INNER JOIN tracker_report AS R ON (M.tracker_id = R.tracker_id)
    INNER JOIN tracker_report_renderer AS Re ON (R.id = Re.report_id AND M.min_report_id < Re.report_id AND Re.renderer_type='plugin_graphontrackers')
    INNER JOIN plugin_graphontrackers_chart AS C ON (Re.old_id = C.old_report_graphic_id);

-- pie
INSERT INTO plugin_graphontrackers_pie_chart(id, field_base)
SELECT C.id, P.field_base
FROM plugin_graphontrackers_pie_chart AS P
INNER JOIN plugin_graphontrackers_chart AS C 
    ON (C.chart_type='pie' AND C.old_id = P.id);

-- bar
INSERT INTO plugin_graphontrackers_bar_chart(id, field_base, field_group)
SELECT C.id, B.field_base, B.field_group
FROM plugin_graphontrackers_bar_chart AS B
INNER JOIN plugin_graphontrackers_chart AS C 
    ON (C.chart_type='bar' AND C.old_id = B.id);
    
-- gantt
INSERT INTO plugin_graphontrackers_gantt_chart(id, field_start, field_due, field_finish, field_percentage, field_righttext, scale, as_of_date, summary)
SELECT C.id, G.field_start, G.field_due, G.field_finish, G.field_percentage, G.field_righttext, G.scale, G.as_of_date, G.summary
FROM plugin_graphontrackers_gantt_chart AS G
INNER JOIN plugin_graphontrackers_chart AS C 
    ON (C.chart_type='gantt' AND C.old_id = G.id);


-- migrate graph on trackers tables    TODO: move in plugin's workspace?

-- update with field_id
UPDATE plugin_graphontrackers_pie_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_base)
SET A.field_base = F.id;
ALTER TABLE plugin_graphontrackers_pie_chart
    CHANGE field_base field_base int(11) unsigned NULL AFTER id;

UPDATE plugin_graphontrackers_bar_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_base)
SET A.field_base = F.id;

UPDATE plugin_graphontrackers_bar_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_group)
SET A.field_group = F.id;
ALTER TABLE plugin_graphontrackers_bar_chart
    CHANGE field_base field_base int(11) unsigned NULL AFTER id,
    CHANGE field_group field_group int(11) unsigned NULL AFTER field_base;

UPDATE plugin_graphontrackers_gantt_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_start)
SET A.field_start = F.id;
UPDATE plugin_graphontrackers_gantt_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_due)
SET A.field_due = F.id;
UPDATE plugin_graphontrackers_gantt_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_finish)
SET A.field_finish = F.id;
UPDATE plugin_graphontrackers_gantt_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_percentage)
SET A.field_percentage = F.id;
UPDATE plugin_graphontrackers_gantt_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.field_righttext)
SET A.field_righttext = F.id;
UPDATE plugin_graphontrackers_gantt_chart AS A 
       INNER JOIN plugin_graphontrackers_chart AS C ON(A.id = C.id)
       INNER JOIN tracker_report_renderer AS Re ON ( Re.id = C.report_graphic_id )
       INNER JOIN tracker_report AS R ON(R.id = Re.report_id)
       INNER JOIN tracker_field AS F ON(F.tracker_id = R.tracker_id AND F.name = A.summary)
SET A.summary = F.id;
ALTER TABLE plugin_graphontrackers_gantt_chart
    CHANGE field_start field_start int(11) unsigned NULL AFTER id,
    CHANGE field_due field_due int(11) unsigned NULL AFTER field_start,
    CHANGE field_finish field_finish int(11) unsigned NULL AFTER field_due,
    CHANGE field_percentage field_percentage int(11) unsigned NULL AFTER field_finish,
    CHANGE field_righttext field_righttext int(11) unsigned NULL AFTER field_percentage,
    CHANGE summary summary int(11) unsigned NULL AFTER as_of_date;


