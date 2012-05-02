alter table plugin_docman_metadata_value add FULLTEXT fltxt (valueText, valueString);
alter table plugin_docman_metadata_value add FULLTEXT fltxt_txt (valueText);
alter table plugin_docman_metadata_value add FULLTEXT fltxt_str (valueString);

alter table plugin_docman_item add FULLTEXT fltxt_title (title);
alter table plugin_docman_item add FULLTEXT fltxt_description (description);
alter table plugin_docman_item add FULLTEXT fltxt (title, description);

alter table plugin_docman_version add FULLTEXT fltxt (label, changelog, filename);


update plugin_docman_item set status = status + 100;
alter table plugin_docman_item change column status status TINYINT(4) DEFAULT 100 NOT NULL;
