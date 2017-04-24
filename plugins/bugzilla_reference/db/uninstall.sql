DROP TABLE plugin_bugzilla_reference;

DELETE FROM cross_references WHERE source_type = 'bugzilla' OR target_type = 'bugzilla';
