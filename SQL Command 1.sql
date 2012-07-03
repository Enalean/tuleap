select c1.artifact_id, FROM_UNIXTIME(c1.submitted_on) 
from tracker_changeset c1
left JOIN tracker_changeset c2 ON (c2.artifact_id = c1.artifact_id AND c1.submitted_on < c2.submitted_on)
where c1.submitted_on < UNIX_TIMESTAMP('2012-06-28 23:59:59')
and c1.artifact_id IN (903, 906, 908, 909, 920, 924)
and c2.id IS NULL