


ALTER TABLE svn_commits DROP INDEX idx_search, ADD INDEX idx_search(group_id,revision,id)

OPTIMIZE TABLE svn_dirs, svn_commits, svn_checkins








