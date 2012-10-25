--  renderers > table

-- create report table renderer

-- retrieve previously migrated report data to create a table renderer
INSERT INTO tracker_report_renderer(report_id, renderer_type, name, description, rank)
SELECT id, 'table', 'Results', '', 1
FROM tracker_report;

-- initializes table renderer attributes
INSERT INTO tracker_report_renderer_table(renderer_id, chunksz, multisort)
SELECT id, 25, 0
FROM tracker_report_renderer;

-- retrieves fields as columns for the table renderer
INSERT INTO tracker_report_renderer_table_columns(renderer_id, field_id, rank, width)
SELECT TRR.id, TF.id, ARF.place_result, 0
FROM artifact_report_field AS ARF
     INNER JOIN tracker_report AS TR ON(TR.old_id = ARF.report_id) -- for a given report
     INNER JOIN tracker_field AS TF ON(TF.name = ARF.field_name AND TF.tracker_id = TR.tracker_id AND TF.use_it = 1) -- all used report fields
     INNER JOIN tracker_report_renderer AS TRR ON(TRR.report_id = TR.id) -- renderer fields
WHERE ARF.show_on_result = 1 -- retrieve only fields viewable in result table renderer
ORDER BY TRR.id, ARF.place_result;

--  Reorder report fields for prepareRanking usage
SET @counter = 0;
SET @previous = NULL;
UPDATE tracker_report_renderer_table_columns 
        INNER JOIN (SELECT @counter := IF(@previous = renderer_id, @counter + 1, 1) AS new_rank, 
                           @previous := renderer_id, 
                           tracker_report_renderer_table_columns.* 
                    FROM tracker_report_renderer_table_columns 
                    ORDER BY renderer_id, rank, field_id
        ) as R1 USING(renderer_id,field_id)
SET tracker_report_renderer_table_columns.rank = R1.new_rank;


