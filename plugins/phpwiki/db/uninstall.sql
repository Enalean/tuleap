DROP TABLE IF EXISTS plugin_phpwiki_page;
DROP TABLE IF EXISTS plugin_phpwiki_version;
DROP TABLE IF EXISTS plugin_phpwiki_recent;
DROP TABLE IF EXISTS plugin_phpwiki_nonempty;
DROP TABLE IF EXISTS plugin_phpwiki_link;
DROP TABLE IF EXISTS plugin_phpwiki_group_list;
DROP TABLE IF EXISTS plugin_phpwiki_log;
DROP TABLE IF EXISTS plugin_phpwiki_attachement;
DROP TABLE IF EXISTS plugin_phpwiki_attachment_deleted;
DROP TABLE IF EXISTS plugin_phpwiki_attachment_revision;
DROP TABLE IF EXISTS plugin_phpwiki_attachment_log;

DELETE FROM service WHERE short_name='plugin_phpwiki';
DELETE FROM reference WHERE service_short_name='PHPWiki';

DELETE FROM permissions_values WHERE permission_type IN ('PHPWIKI_READ', 'PHPWIKIPAGE_READ', 'PHPWIKIATTACHMENT_READ');
DELETE FROM permissions WHERE permission_type IN ('PHPWIKI_READ', 'PHPWIKIPAGE_READ', 'PHPWIKIATTACHMENT_READ');