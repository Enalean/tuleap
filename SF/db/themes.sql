# MySQL dump 7.1
#
# Host: localhost    Database: sourceforge
#--------------------------------------------------------
# Server version	3.22.32

#
# Table structure for table 'theme_prefs'
#
CREATE TABLE theme_prefs (
  user_id int(11) DEFAULT '0' NOT NULL,
  user_theme int(8) DEFAULT '0' NOT NULL,
  BODY_font char(80) DEFAULT '',
  BODY_size char(5) DEFAULT '',
  TITLEBAR_font char(80) DEFAULT '',
  TITLEBAR_size char(5) DEFAULT '',
  COLOR_TITLEBAR_BACK char(7) DEFAULT '',
  COLOR_LTBACK1 char(7) DEFAULT '',
  PRIMARY KEY (user_id)
);

# MySQL dump 7.1
#
# Host: localhost    Database: sourceforge
#--------------------------------------------------------
# Server version	3.22.32

#
# Table structure for table 'themes'
#
CREATE TABLE themes (
  theme_id int(11) DEFAULT '0' NOT NULL auto_increment,
  dirname varchar(80),
  fullname varchar(80),
  PRIMARY KEY (theme_id)
);

#
# Dumping data for table 'themes'
#

INSERT INTO themes VALUES (1,'forged','Forged Metal');
INSERT INTO themes VALUES (2,'classic','Classic Sourceforge');

