DROP TABLE IF EXISTS plugin_related_project_link_type;
CREATE TABLE plugin_related_project_link_type (
	link_type_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	group_id INT(11) UNSIGNED NOT NULL,
	name TEXT NOT NULL,
	reverse_name TEXT NOT NULL,
	description TEXT NULL,
	uri_plus TEXT NULL,
	PRIMARY KEY(link_type_id)
);
--
-- Table structure for table 'plugin_related_project_link_type'
--
-- link_type_id:    (Auto) System-unique ID for the link type
-- group_id:           Project that owns this link type
-- name:                 Short name of this link type, e.g. "Child" – this must be unique within the project
-- reverse_name:  Short name of reverse linkage (if necessary), e.g. "Parent" - may not be null and defaults to the value in the name field - must be unique in the project
-- description:       Long description of what this relationship type means
-- uri_plus:            URI with replaceable parameters: %group_id% %project_name%
--
DROP TABLE IF EXISTS plugin_related_project_relationship;
CREATE TABLE plugin_related_project_relationship (
	link_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	link_type_id INT(11) UNSIGNED NOT NULL,
	master_group_id INT(11) UNSIGNED NOT NULL,
	target_group_id INT(11) UNSIGNED NOT NULL,
	creation_date INT(11) UNSIGNED NOT NULL,
	PRIMARY KEY(link_id)
);
--
-- Table structure for table 'plugin_related_project_relationship'
--
--
-- link_type_id:      Index to plugin_related_project_link_type table to define the link
--                             Or 0 to mean a ring link when the target is a ringmaster project.
-- master_group_id:   Project who owns this link.
-- target_group_id:   Project linked to.
-- creation_date:     (date) Date link was created
--

