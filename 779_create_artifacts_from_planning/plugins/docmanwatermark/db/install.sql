DROP TABLE IF EXISTS plugin_docmanwatermark_metadata_love_md_extension;
CREATE TABLE plugin_docmanwatermark_metadata_love_md_extension (
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

DROP TABLE IF EXISTS plugin_docmanwatermark_item_excluded;
CREATE TABLE plugin_docmanwatermark_item_excluded (
  item_id INT(11) UNSIGNED NOT NULL,
  PRIMARY KEY(item_id)
);

DROP TABLE IF EXISTS plugin_docmanwatermark_item_excluded_log;
CREATE TABLE plugin_docmanwatermark_item_excluded_log (
  item_id INT(11) UNSIGNED NOT NULL,
  time INT(11) UNSIGNED NOT NULL DEFAULT 0,
  who INT(11) UNSIGNED NOT NULL DEFAULT 0,
  watermarked TINYINT(4) UNSIGNED NOT NULL DEFAULT 0,
  INDEX idx_show_log(item_id, time)
);
