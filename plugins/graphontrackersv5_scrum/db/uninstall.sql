DELETE C.*, B.*
FROM plugin_graphontrackersv5_chart AS C, plugin_graphontrackersv5_scrum_burndown AS B
WHERE C.id = B.id;

DELETE C.*, B.*
FROM plugin_graphontrackersv5_chart AS C, plugin_graphontrackersv5_scrum_burnup AS B
WHERE C.id = B.id;

DROP TABLE IF EXISTS plugin_graphontrackersv5_scrum_burndown;
DROP TABLE IF EXISTS plugin_graphontrackersv5_scrum_burnup;
