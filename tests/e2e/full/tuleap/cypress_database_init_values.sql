UPDATE service SET is_active=true WHERE short_name='wiki';

# Add nature for frs plugin tests
INSERT INTO plugin_tracker_artifactlink_natures (shortname, forward_label, reverse_label) VALUES ('fixed_in', 'Fixed in', 'Fixed by');
