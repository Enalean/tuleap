
#
# Table structure of 'layouts'
#

DROP TABLE IF EXISTS layouts;
CREATE TABLE IF NOT EXISTS layouts (
  id int(11) unsigned NOT NULL auto_increment,
  name varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default 'S',
  PRIMARY KEY  (id)
);

# --------------------------------------------------------

#
# Table structure of 'layouts_rows'
#

DROP TABLE IF EXISTS layouts_rows;
CREATE TABLE IF NOT EXISTS layouts_rows (
  id int(11) unsigned NOT NULL auto_increment,
  layout_id int(11) unsigned NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY layout_id (layout_id)
);

# --------------------------------------------------------

#
# Table structure of 'layouts_rows_columns'
#

DROP TABLE IF EXISTS layouts_rows_columns;
CREATE TABLE IF NOT EXISTS layouts_rows_columns (
  id int(11) unsigned NOT NULL auto_increment,
  layout_row_id int(11) unsigned NOT NULL default '0',
  width int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY layout_row_id (layout_row_id)
);

# --------------------------------------------------------

#
# Table structure of 'owner_layouts'
#

DROP TABLE IF EXISTS owner_layouts;
CREATE TABLE IF NOT EXISTS owner_layouts (
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  layout_id int(11) unsigned NOT NULL default '0',
  is_default tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (owner_id, owner_type, layout_id)
);

# --------------------------------------------------------

#
# Table structure of 'layouts_contents'
#

DROP TABLE IF EXISTS layouts_contents;
CREATE TABLE IF NOT EXISTS layouts_contents (
  owner_id int(11) unsigned NOT NULL default '0',
  owner_type varchar(1) NOT NULL default 'u',
  layout_id int(11) unsigned NOT NULL default '0',
  column_id int(11) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  `rank` int(11) NOT NULL default '0',
  is_minimized tinyint(1) NOT NULL default '0',
  is_removed tinyint(1) NOT NULL default '0',
  display_preferences tinyint(1) NOT NULL default '0',
  content_id int(11) unsigned NOT NULL default '0',
  KEY user_id (owner_id,owner_type,layout_id,name,content_id)
);


--
-- Layouts
--
INSERT INTO layouts (id, name, description, scope) VALUES
(1, '2 columns', 'Simple layout made of 2 columns', 'S'),
(2, '3 columns', 'Simple layout made of 3 columns', 'S'),
(3, 'Left', 'Simple layout made of a main column and a small, left sided, column', 'S'),
(4, 'Right', 'Simple layout made of a main column and a small, right sided, column', 'S');

INSERT INTO layouts_rows (id, layout_id, `rank`) VALUES (1, 1, 0), (2, 2, 0),(3, 3, 0), (4, 4, 0);
INSERT INTO layouts_rows_columns (id, layout_row_id, width) VALUES (1, 1, 50), (2, 1, 50), (3, 2, 33), (4, 2, 33), (5, 2, 33), (6, 3, 33), (7, 3, 66), (8, 4, 66), (9, 4, 33);

-- Users

INSERT INTO owner_layouts (owner_id, owner_type, layout_id, is_default)
SELECT user_id, 'u', 1, 1
FROM user;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT user_id, 'u', 1, 1, 'myprojects', 0
FROM user;

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT user_id, 'u', 1, 1, 'mybookmarks', 1
FROM user;

-- Add mydocman only if docman is installed
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT user_id, 'u', 1, 1, 'mydocman', 2
FROM user, plugin
WHERE plugin.name = 'docman';

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT user_id, 'u', 1, 1, 'mymonitoredforums', 3
FROM user;

-- Add myadmin only to current admins
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT DISTINCT user_id, 'u', 1, 2, 'myadmin', -2
FROM user_group
WHERE group_id = 1
  AND admin_flags = 'A';

-- Add mysystemevent only to current admins
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT DISTINCT user_id, 'u', 1, 2, 'mysystemevent', -3
FROM user_group
WHERE group_id = 1
  AND admin_flags = 'A';

INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT user_id, 'u', 1, 2, 'mymonitoredfp', 1
FROM user;



-- Projects

INSERT INTO owner_layouts (owner_id, owner_type, layout_id, is_default)
SELECT group_id, 'g', 1, 1
FROM `groups`;

-- First column
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT group_id, 'g', 1, 1, 'projectdescription', 0
FROM `groups`;


INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT group_id, 'g', 1, 1, 'projectpublicareas', 2
FROM `groups`;

-- Second column
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT group_id, 'g', 1, 2, 'projectmembers', 0
FROM `groups`;

-- only if News is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT group_id, 'g', 1, 2, 'projectlatestnews', 1
FROM service
WHERE short_name = 'news' AND is_active = 1 AND is_used = 1;

-- only if FRS is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT group_id, 'g', 1, 2, 'projectlatestfilereleases', 2
FROM service
WHERE short_name = 'file' AND is_active = 1 AND is_used = 1;

-- only if SVN is used
INSERT INTO layouts_contents (owner_id, owner_type, layout_id, column_id, name, `rank`)
SELECT group_id, 'g', 1, 2, 'projectlatestsvncommits', 3
FROM service
WHERE short_name = 'svn' AND is_active = 1 AND is_used = 1;
