INSERT INTO tracker_perm(id, tracker_id, user_id, perm_level)
SELECT id, group_artifact_id, user_id, perm_level
FROM artifact_perm;

