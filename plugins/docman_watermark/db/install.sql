DROP TABLE IF EXISTS plugin_docmanwatermark_metadata_love_extension;
CREATE TABLE plugin_docmanwatermark_metadata_love_extension (
  value_id INT(11) UNSIGNED NOT NULL,
  watermark INT(1) UNSIGNED DEFAULT 0,
  PRIMARY KEY(value_id)
);

DROP TABLE IF EXISTS plugin_docmanwatermark_metadata_extension;
CREATE TABLE plugin_docmanwatermark_metadata_extension (
  group_id INT(11) UNSIGNED NOT NULL,
  field_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(group_id, field_id)
);
