use codendi;
-- MySQL dump 10.13  Distrib 5.1.49, for apple-darwin10.4.0 (i386)
--
-- Host: piton    Database: codendi_orange
-- ------------------------------------------------------
-- Server version	5.0.77-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Not dumping tablespaces as no INFORMATION_SCHEMA.FILES table on this server
--

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `day` int(11) NOT NULL default '0',
  `hour` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `browser` varchar(8) NOT NULL default 'OTHER',
  `ver` float(10,2) NOT NULL default '0.00',
  `platform` varchar(8) NOT NULL default 'OTHER',
  `time` int(11) NOT NULL default '0',
  `page` text,
  `type` int(11) NOT NULL default '0',
  KEY `idx_activity_log_day` (`day`),
  KEY `idx_activity_log_group` (`group_id`),
  KEY `type_idx` (`type`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_log_old`
--

DROP TABLE IF EXISTS `activity_log_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log_old` (
  `day` int(11) NOT NULL default '0',
  `hour` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `browser` varchar(8) NOT NULL default 'OTHER',
  `ver` float(10,2) NOT NULL default '0.00',
  `platform` varchar(8) NOT NULL default 'OTHER',
  `time` int(11) NOT NULL default '0',
  `page` text,
  `type` int(11) NOT NULL default '0',
  KEY `idx_activity_log_day` (`day`),
  KEY `idx_activity_log_group` (`group_id`),
  KEY `type_idx` (`type`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log_old`
--

LOCK TABLES `activity_log_old` WRITE;
/*!40000 ALTER TABLE `activity_log_old` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log_old` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_log_old_old`
--

DROP TABLE IF EXISTS `activity_log_old_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log_old_old` (
  `day` int(11) NOT NULL default '0',
  `hour` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `browser` varchar(8) NOT NULL default 'OTHER',
  `ver` float(10,2) NOT NULL default '0.00',
  `platform` varchar(8) NOT NULL default 'OTHER',
  `time` int(11) NOT NULL default '0',
  `page` text,
  `type` int(11) NOT NULL default '0',
  KEY `idx_activity_log_day` (`day`),
  KEY `idx_activity_log_group` (`group_id`),
  KEY `type_idx` (`type`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log_old_old`
--

LOCK TABLES `activity_log_old_old` WRITE;
/*!40000 ALTER TABLE `activity_log_old_old` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_log_old_old` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact`
--

DROP TABLE IF EXISTS `artifact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact` (
  `artifact_id` int(11) NOT NULL auto_increment,
  `group_artifact_id` int(11) NOT NULL default '0',
  `use_artifact_permissions` tinyint(1) NOT NULL default '0',
  `status_id` int(11) NOT NULL default '1',
  `submitted_by` int(11) NOT NULL default '100',
  `open_date` int(11) NOT NULL default '0',
  `close_date` int(11) NOT NULL default '0',
  `last_update_date` int(11) unsigned NOT NULL default '0',
  `summary` text NOT NULL,
  `details` text NOT NULL,
  `severity` int(11) NOT NULL default '0',
  PRIMARY KEY  (`artifact_id`),
  KEY `idx_fk_group_artifact_id` (`group_artifact_id`),
  KEY `idx_fk_status_id` (`status_id`),
  KEY `idx_fk_submitted_by` (`submitted_by`)
)  AUTO_INCREMENT=82233 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact`
--

LOCK TABLES `artifact` WRITE;
/*!40000 ALTER TABLE `artifact` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_canned_responses`
--

DROP TABLE IF EXISTS `artifact_canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_canned_responses` (
  `artifact_canned_id` int(11) NOT NULL auto_increment,
  `group_artifact_id` int(11) NOT NULL default '0',
  `title` text,
  `body` text,
  PRIMARY KEY  (`artifact_canned_id`),
  KEY `idx_artifact_canned_response_group_artifact_id` (`group_artifact_id`)
)  AUTO_INCREMENT=56 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_canned_responses`
--

LOCK TABLES `artifact_canned_responses` WRITE;
/*!40000 ALTER TABLE `artifact_canned_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_canned_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_cc`
--

DROP TABLE IF EXISTS `artifact_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_cc` (
  `artifact_cc_id` int(11) NOT NULL auto_increment,
  `artifact_id` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL,
  `added_by` int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`artifact_cc_id`),
  KEY `artifact_id_idx` (`artifact_id`)
)  AUTO_INCREMENT=23722 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_cc`
--

LOCK TABLES `artifact_cc` WRITE;
/*!40000 ALTER TABLE `artifact_cc` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_cc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_dependencies`
--

DROP TABLE IF EXISTS `artifact_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_dependencies` (
  `artifact_depend_id` int(11) NOT NULL auto_increment,
  `artifact_id` int(11) NOT NULL default '0',
  `is_dependent_on_artifact_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`artifact_depend_id`),
  KEY `idx_artifact_dependencies_artifact_id` (`artifact_id`),
  KEY `idx_actifact_is_dependent_on_artifact_id` (`is_dependent_on_artifact_id`)
)  AUTO_INCREMENT=1992 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_dependencies`
--

LOCK TABLES `artifact_dependencies` WRITE;
/*!40000 ALTER TABLE `artifact_dependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_dependencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_field`
--

DROP TABLE IF EXISTS `artifact_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_field` (
  `field_id` int(11) NOT NULL auto_increment,
  `group_artifact_id` int(11) NOT NULL default '0',
  `field_set_id` int(11) unsigned NOT NULL default '0',
  `field_name` varchar(255) NOT NULL,
  `data_type` int(11) NOT NULL default '0',
  `display_type` varchar(255) NOT NULL,
  `display_size` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `scope` char(1) NOT NULL,
  `required` int(11) NOT NULL default '0',
  `empty_ok` int(11) NOT NULL default '0',
  `keep_history` int(11) NOT NULL default '0',
  `special` int(11) NOT NULL default '0',
  `value_function` text,
  `default_value` text NOT NULL,
  PRIMARY KEY  (`field_id`,`group_artifact_id`),
  KEY `idx_fk_field_name` (`field_name`),
  KEY `idx_fk_group_artifact_id` (`group_artifact_id`),
  KEY `idx_fname_grp` (`field_name`(20),`group_artifact_id`)
)  AUTO_INCREMENT=114 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_field`
--

LOCK TABLES `artifact_field` WRITE;
/*!40000 ALTER TABLE `artifact_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_field_set`
--

DROP TABLE IF EXISTS `artifact_field_set`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_field_set` (
  `field_set_id` int(11) unsigned NOT NULL auto_increment,
  `group_artifact_id` int(11) unsigned NOT NULL default '0',
  `name` text NOT NULL,
  `description` text NOT NULL,
  `rank` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`field_set_id`),
  KEY `idx_fk_group_artifact_id` (`group_artifact_id`)
)  AUTO_INCREMENT=12754 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_field_set`
--

LOCK TABLES `artifact_field_set` WRITE;
/*!40000 ALTER TABLE `artifact_field_set` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_field_set` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_field_usage`
--

DROP TABLE IF EXISTS `artifact_field_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_field_usage` (
  `field_id` int(11) NOT NULL default '0',
  `group_artifact_id` int(11) NOT NULL default '0',
  `use_it` int(11) NOT NULL default '0',
  `place` int(11) default NULL,
  KEY `idx_fk` (`field_id`,`group_artifact_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_field_usage`
--

LOCK TABLES `artifact_field_usage` WRITE;
/*!40000 ALTER TABLE `artifact_field_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_field_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_field_value`
--

DROP TABLE IF EXISTS `artifact_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_field_value` (
  `field_id` int(11) NOT NULL default '0',
  `artifact_id` int(11) NOT NULL default '0',
  `valueInt` int(11) default NULL,
  `valueText` text,
  `valueFloat` float(10,4) default NULL,
  `valueDate` int(11) default NULL,
  KEY `idx_valueInt` (`artifact_id`,`field_id`,`valueInt`),
  KEY `xtrk_valueInt` (`valueInt`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_field_value`
--

LOCK TABLES `artifact_field_value` WRITE;
/*!40000 ALTER TABLE `artifact_field_value` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_field_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_field_value_list`
--

DROP TABLE IF EXISTS `artifact_field_value_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_field_value_list` (
  `field_id` int(11) NOT NULL default '0',
  `group_artifact_id` int(11) NOT NULL default '0',
  `value_id` int(11) NOT NULL default '0',
  `value` text NOT NULL,
  `description` text NOT NULL,
  `order_id` int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (`field_id`,`group_artifact_id`,`value_id`),
  KEY `idx_fv_group_artifact_id` (`group_artifact_id`),
  KEY `idx_fv_value_id` (`value_id`),
  KEY `idx_fv_status` (`status`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_field_value_list`
--

LOCK TABLES `artifact_field_value_list` WRITE;
/*!40000 ALTER TABLE `artifact_field_value_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_field_value_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_file`
--

DROP TABLE IF EXISTS `artifact_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_file` (
  `id` int(11) NOT NULL auto_increment,
  `artifact_id` int(11) NOT NULL default '0',
  `description` text NOT NULL,
  `bin_data` longblob NOT NULL,
  `filename` text NOT NULL,
  `filesize` int(11) NOT NULL default '0',
  `filetype` text NOT NULL,
  `adddate` int(11) NOT NULL default '0',
  `submitted_by` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `artifact_id` (`artifact_id`)
)  AUTO_INCREMENT=16539 DEFAULT CHARSET=utf8 MAX_ROWS=1000000 AVG_ROW_LENGTH=1000000;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_file`
--

LOCK TABLES `artifact_file` WRITE;
/*!40000 ALTER TABLE `artifact_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_global_notification`
--

DROP TABLE IF EXISTS `artifact_global_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_global_notification` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `tracker_id` int(11) NOT NULL default '0',
  `addresses` text NOT NULL,
  `all_updates` tinyint(1) NOT NULL default '0',
  `check_permissions` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `tracker_id` (`tracker_id`)
)  AUTO_INCREMENT=8141 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_global_notification`
--

LOCK TABLES `artifact_global_notification` WRITE;
/*!40000 ALTER TABLE `artifact_global_notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_global_notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_group_list`
--

DROP TABLE IF EXISTS `artifact_group_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_group_list` (
  `group_artifact_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `name` text,
  `description` text,
  `item_name` text,
  `allow_copy` int(11) NOT NULL default '0',
  `submit_instructions` text,
  `browse_instructions` text,
  `status` char(1) NOT NULL default 'A',
  `deletion_date` int(11) default NULL,
  `instantiate_for_new_projects` int(11) NOT NULL default '0',
  `stop_notification` int(11) NOT NULL default '0',
  PRIMARY KEY  (`group_artifact_id`),
  KEY `idx_fk_group_id` (`group_id`)
)  AUTO_INCREMENT=9638 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_group_list`
--

LOCK TABLES `artifact_group_list` WRITE;
/*!40000 ALTER TABLE `artifact_group_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_group_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_history`
--

DROP TABLE IF EXISTS `artifact_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_history` (
  `artifact_history_id` int(11) NOT NULL auto_increment,
  `artifact_id` int(11) NOT NULL default '0',
  `field_name` varchar(255) NOT NULL,
  `old_value` text NOT NULL,
  `new_value` text NOT NULL,
  `mod_by` int(11) NOT NULL default '0',
  `email` varchar(100) NOT NULL,
  `date` int(11) default NULL,
  `type` int(11) default NULL,
  `format` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`artifact_history_id`),
  KEY `idx_artifact_history_artifact_id` (`artifact_id`),
  KEY `field_name` (`field_name`(10))
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_history`
--

LOCK TABLES `artifact_history` WRITE;
/*!40000 ALTER TABLE `artifact_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_notification`
--

DROP TABLE IF EXISTS `artifact_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_notification` (
  `user_id` int(11) NOT NULL default '0',
  `group_artifact_id` int(11) NOT NULL default '0',
  `role_id` int(11) NOT NULL default '0',
  `event_id` int(11) NOT NULL default '0',
  `notify` int(11) NOT NULL default '1',
  KEY `user_id_idx` (`user_id`),
  KEY `group_artifact_id_idx` (`group_artifact_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_notification`
--

LOCK TABLES `artifact_notification` WRITE;
/*!40000 ALTER TABLE `artifact_notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_notification_event`
--

DROP TABLE IF EXISTS `artifact_notification_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_notification_event` (
  `event_id` int(11) NOT NULL default '0',
  `group_artifact_id` int(11) NOT NULL default '0',
  `event_label` varchar(255) default NULL,
  `rank` int(11) NOT NULL default '0',
  `short_description_msg` varchar(255) default NULL,
  `description_msg` varchar(255) default NULL,
  KEY `event_id_idx` (`event_id`),
  KEY `group_artifact_id_idx` (`group_artifact_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_notification_event`
--

LOCK TABLES `artifact_notification_event` WRITE;
/*!40000 ALTER TABLE `artifact_notification_event` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_notification_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_notification_event_default`
--

DROP TABLE IF EXISTS `artifact_notification_event_default`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_notification_event_default` (
  `event_id` int(11) NOT NULL default '0',
  `event_label` varchar(255) default NULL,
  `rank` int(11) NOT NULL default '0',
  `short_description_msg` varchar(255) default NULL,
  `description_msg` varchar(255) default NULL,
  KEY `event_id_idx` (`event_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_notification_event_default`
--

LOCK TABLES `artifact_notification_event_default` WRITE;
/*!40000 ALTER TABLE `artifact_notification_event_default` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_notification_event_default` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_notification_role`
--

DROP TABLE IF EXISTS `artifact_notification_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_notification_role` (
  `role_id` int(11) NOT NULL default '0',
  `group_artifact_id` int(11) NOT NULL default '0',
  `role_label` varchar(255) default NULL,
  `rank` int(11) NOT NULL default '0',
  `short_description_msg` varchar(255) default NULL,
  `description_msg` varchar(255) default NULL,
  KEY `role_id_idx` (`role_id`),
  KEY `group_artifact_id_idx` (`group_artifact_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_notification_role`
--

LOCK TABLES `artifact_notification_role` WRITE;
/*!40000 ALTER TABLE `artifact_notification_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_notification_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_notification_role_default`
--

DROP TABLE IF EXISTS `artifact_notification_role_default`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_notification_role_default` (
  `role_id` int(11) NOT NULL default '0',
  `role_label` varchar(255) default NULL,
  `rank` int(11) NOT NULL default '0',
  `short_description_msg` varchar(255) default NULL,
  `description_msg` varchar(255) default NULL,
  KEY `role_id_idx` (`role_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_notification_role_default`
--

LOCK TABLES `artifact_notification_role_default` WRITE;
/*!40000 ALTER TABLE `artifact_notification_role_default` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_notification_role_default` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_perm`
--

DROP TABLE IF EXISTS `artifact_perm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_perm` (
  `id` int(11) NOT NULL auto_increment,
  `group_artifact_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `perm_level` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `unique_user` (`group_artifact_id`,`user_id`)
)  AUTO_INCREMENT=81800 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_perm`
--

LOCK TABLES `artifact_perm` WRITE;
/*!40000 ALTER TABLE `artifact_perm` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_perm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_report`
--

DROP TABLE IF EXISTS `artifact_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_report` (
  `report_id` int(11) NOT NULL auto_increment,
  `group_artifact_id` int(11) NOT NULL default '100',
  `user_id` int(11) NOT NULL default '100',
  `name` varchar(80) default NULL,
  `description` varchar(255) default NULL,
  `scope` char(1) NOT NULL default 'I',
  `is_default` int(11) NOT NULL default '0',
  PRIMARY KEY  (`report_id`),
  KEY `group_artifact_id_idx` (`group_artifact_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `scope_idx` (`scope`)
)  AUTO_INCREMENT=9844 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_report`
--

LOCK TABLES `artifact_report` WRITE;
/*!40000 ALTER TABLE `artifact_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_report_field`
--

DROP TABLE IF EXISTS `artifact_report_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_report_field` (
  `report_id` int(11) NOT NULL default '100',
  `field_name` varchar(255) default NULL,
  `show_on_query` int(11) default NULL,
  `show_on_result` int(11) default NULL,
  `place_query` int(11) default NULL,
  `place_result` int(11) default NULL,
  `col_width` int(11) default NULL,
  KEY `profile_id_idx` (`report_id`),
  KEY `field_name_idx` (`field_name`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_report_field`
--

LOCK TABLES `artifact_report_field` WRITE;
/*!40000 ALTER TABLE `artifact_report_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_report_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_rule`
--

DROP TABLE IF EXISTS `artifact_rule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_rule` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `group_artifact_id` int(11) unsigned NOT NULL default '0',
  `source_field_id` int(11) unsigned NOT NULL default '0',
  `source_value_id` int(11) unsigned NOT NULL default '0',
  `target_field_id` int(11) unsigned NOT NULL default '0',
  `rule_type` tinyint(4) unsigned NOT NULL default '0',
  `target_value_id` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `group_artifact_id` (`group_artifact_id`)
)  AUTO_INCREMENT=15539 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_rule`
--

LOCK TABLES `artifact_rule` WRITE;
/*!40000 ALTER TABLE `artifact_rule` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_rule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `artifact_watcher`
--

DROP TABLE IF EXISTS `artifact_watcher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artifact_watcher` (
  `user_id` int(11) NOT NULL default '0',
  `watchee_id` int(11) NOT NULL default '0',
  `artifact_group_id` int(11) NOT NULL default '0',
  KEY `watchee_id_idx` (`watchee_id`,`artifact_group_id`),
  KEY `user_id_idx` (`user_id`,`artifact_group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `artifact_watcher`
--

LOCK TABLES `artifact_watcher` WRITE;
/*!40000 ALTER TABLE `artifact_watcher` DISABLE KEYS */;
/*!40000 ALTER TABLE `artifact_watcher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug`
--

DROP TABLE IF EXISTS `bug`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug` (
  `bug_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `status_id` int(11) NOT NULL default '1',
  `severity` int(11) NOT NULL default '5',
  `category_id` int(11) NOT NULL default '100',
  `submitted_by` int(11) NOT NULL default '100',
  `assigned_to` int(11) NOT NULL default '100',
  `date` int(11) NOT NULL default '0',
  `summary` text,
  `details` text,
  `close_date` int(11) default NULL,
  `bug_group_id` int(11) NOT NULL default '100',
  `resolution_id` int(11) NOT NULL default '100',
  `category_version_id` int(11) NOT NULL default '100',
  `platform_version_id` int(11) NOT NULL default '100',
  `reproducibility_id` int(11) NOT NULL default '100',
  `size_id` int(11) NOT NULL default '100',
  `fix_release_id` int(11) NOT NULL default '100',
  `plan_release_id` int(11) NOT NULL default '100',
  `hours` float(10,2) NOT NULL default '0.00',
  `component_version` varchar(255) NOT NULL,
  `fix_release` varchar(255) NOT NULL,
  `plan_release` varchar(255) NOT NULL,
  `priority` int(11) NOT NULL default '100',
  `keywords` varchar(255) NOT NULL,
  `release_id` int(11) NOT NULL default '100',
  `release_name` varchar(255) NOT NULL,
  `originator_name` varchar(255) NOT NULL,
  `originator_email` varchar(255) NOT NULL,
  `originator_phone` varchar(255) NOT NULL,
  `custom_tf1` varchar(255) NOT NULL,
  `custom_tf2` varchar(255) NOT NULL,
  `custom_tf3` varchar(255) NOT NULL,
  `custom_tf4` varchar(255) NOT NULL,
  `custom_tf5` varchar(255) NOT NULL,
  `custom_tf6` varchar(255) NOT NULL,
  `custom_tf7` varchar(255) NOT NULL,
  `custom_tf8` varchar(255) NOT NULL,
  `custom_tf9` varchar(255) NOT NULL,
  `custom_tf10` varchar(255) NOT NULL,
  `custom_ta1` text NOT NULL,
  `custom_ta2` text NOT NULL,
  `custom_ta3` text NOT NULL,
  `custom_ta4` text NOT NULL,
  `custom_ta5` text NOT NULL,
  `custom_ta6` text NOT NULL,
  `custom_ta7` text NOT NULL,
  `custom_ta8` text NOT NULL,
  `custom_ta9` text NOT NULL,
  `custom_ta10` text NOT NULL,
  `custom_sb1` int(11) NOT NULL default '100',
  `custom_sb2` int(11) NOT NULL default '100',
  `custom_sb3` int(11) NOT NULL default '100',
  `custom_sb4` int(11) NOT NULL default '100',
  `custom_sb5` int(11) NOT NULL default '100',
  `custom_sb6` int(11) NOT NULL default '100',
  `custom_sb7` int(11) NOT NULL default '100',
  `custom_sb8` int(11) NOT NULL default '100',
  `custom_sb9` int(11) NOT NULL default '100',
  `custom_sb10` int(11) NOT NULL default '100',
  `custom_df1` int(11) NOT NULL default '0',
  `custom_df2` int(11) NOT NULL default '0',
  `custom_df3` int(11) NOT NULL default '0',
  `custom_df4` int(11) NOT NULL default '0',
  `custom_df5` int(11) NOT NULL default '0',
  PRIMARY KEY  (`bug_id`),
  KEY `idx_bug_group_id` (`group_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug`
--

LOCK TABLES `bug` WRITE;
/*!40000 ALTER TABLE `bug` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_bug_dependencies`
--

DROP TABLE IF EXISTS `bug_bug_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_bug_dependencies` (
  `bug_depend_id` int(11) NOT NULL auto_increment,
  `bug_id` int(11) NOT NULL default '0',
  `is_dependent_on_bug_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`bug_depend_id`),
  KEY `idx_bug_bug_dependencies_bug_id` (`bug_id`),
  KEY `idx_bug_bug_is_dependent_on_task_id` (`is_dependent_on_bug_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_bug_dependencies`
--

LOCK TABLES `bug_bug_dependencies` WRITE;
/*!40000 ALTER TABLE `bug_bug_dependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_bug_dependencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_canned_responses`
--

DROP TABLE IF EXISTS `bug_canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_canned_responses` (
  `bug_canned_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `title` text,
  `body` text,
  PRIMARY KEY  (`bug_canned_id`),
  KEY `idx_bug_canned_response_group_id` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_canned_responses`
--

LOCK TABLES `bug_canned_responses` WRITE;
/*!40000 ALTER TABLE `bug_canned_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_canned_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_cc`
--

DROP TABLE IF EXISTS `bug_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_cc` (
  `bug_cc_id` int(11) NOT NULL auto_increment,
  `bug_id` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL,
  `added_by` int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`bug_cc_id`),
  KEY `bug_id_idx` (`bug_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_cc`
--

LOCK TABLES `bug_cc` WRITE;
/*!40000 ALTER TABLE `bug_cc` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_cc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_field`
--

DROP TABLE IF EXISTS `bug_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_field` (
  `bug_field_id` int(11) NOT NULL auto_increment,
  `field_name` varchar(255) NOT NULL,
  `display_type` varchar(255) NOT NULL,
  `display_size` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `scope` char(1) NOT NULL,
  `required` int(11) NOT NULL default '0',
  `empty_ok` int(11) NOT NULL default '0',
  `keep_history` int(11) NOT NULL default '0',
  `special` int(11) NOT NULL default '0',
  `custom` int(11) NOT NULL default '0',
  `value_function` varchar(255) default NULL,
  PRIMARY KEY  (`bug_field_id`),
  KEY `idx_bug_field_name` (`field_name`)
)  AUTO_INCREMENT=605 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_field`
--

LOCK TABLES `bug_field` WRITE;
/*!40000 ALTER TABLE `bug_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_field_usage`
--

DROP TABLE IF EXISTS `bug_field_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_field_usage` (
  `bug_field_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `use_it` int(11) NOT NULL default '0',
  `show_on_add` int(11) NOT NULL default '0',
  `show_on_add_members` int(11) NOT NULL default '0',
  `place` int(11) default NULL,
  `custom_label` varchar(255) default NULL,
  `custom_description` varchar(255) default NULL,
  `custom_display_size` varchar(255) default NULL,
  `custom_empty_ok` int(11) default NULL,
  `custom_keep_history` int(11) default NULL,
  `custom_value_function` varchar(255) default NULL,
  KEY `idx_bug_fu_field_id` (`bug_field_id`),
  KEY `idx_bug_fu_group_id` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_field_usage`
--

LOCK TABLES `bug_field_usage` WRITE;
/*!40000 ALTER TABLE `bug_field_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_field_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_field_value`
--

DROP TABLE IF EXISTS `bug_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_field_value` (
  `bug_fv_id` int(11) NOT NULL auto_increment,
  `bug_field_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `value_id` int(11) NOT NULL default '0',
  `value` text NOT NULL,
  `description` text NOT NULL,
  `order_id` int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (`bug_fv_id`),
  KEY `idx_bug_fv_field_id` (`bug_fv_id`),
  KEY `idx_bug_fv_group_id` (`group_id`),
  KEY `idx_bug_fv_value_id` (`value_id`),
  KEY `idx_bug_fv_status` (`status`)
)  AUTO_INCREMENT=410 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_field_value`
--

LOCK TABLES `bug_field_value` WRITE;
/*!40000 ALTER TABLE `bug_field_value` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_field_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_file`
--

DROP TABLE IF EXISTS `bug_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_file` (
  `bug_file_id` int(11) NOT NULL auto_increment,
  `bug_id` int(11) NOT NULL default '0',
  `submitted_by` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `description` text NOT NULL,
  `file` longblob NOT NULL,
  `filename` text NOT NULL,
  `filesize` int(11) NOT NULL default '0',
  `filetype` text NOT NULL,
  PRIMARY KEY  (`bug_file_id`),
  KEY `bug_id_idx` (`bug_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_file`
--

LOCK TABLES `bug_file` WRITE;
/*!40000 ALTER TABLE `bug_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_filter`
--

DROP TABLE IF EXISTS `bug_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_filter` (
  `filter_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `sql_clause` text NOT NULL,
  `is_active` int(11) NOT NULL default '0',
  PRIMARY KEY  (`filter_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_filter`
--

LOCK TABLES `bug_filter` WRITE;
/*!40000 ALTER TABLE `bug_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_history`
--

DROP TABLE IF EXISTS `bug_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_history` (
  `bug_history_id` int(11) NOT NULL auto_increment,
  `bug_id` int(11) NOT NULL default '0',
  `field_name` text NOT NULL,
  `old_value` text NOT NULL,
  `mod_by` int(11) NOT NULL default '0',
  `date` int(11) default NULL,
  `type` int(11) default NULL,
  PRIMARY KEY  (`bug_history_id`),
  KEY `idx_bug_history_bug_id` (`bug_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_history`
--

LOCK TABLES `bug_history` WRITE;
/*!40000 ALTER TABLE `bug_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_notification`
--

DROP TABLE IF EXISTS `bug_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_notification` (
  `user_id` int(11) NOT NULL default '0',
  `role_id` int(11) NOT NULL default '0',
  `event_id` int(11) NOT NULL default '0',
  `notify` int(11) NOT NULL default '1',
  KEY `user_id_idx` (`user_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_notification`
--

LOCK TABLES `bug_notification` WRITE;
/*!40000 ALTER TABLE `bug_notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_notification_event`
--

DROP TABLE IF EXISTS `bug_notification_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_notification_event` (
  `event_id` int(11) NOT NULL default '0',
  `event_label` varchar(255) default NULL,
  `short_description` varchar(40) default NULL,
  `description` varchar(255) default NULL,
  `rank` int(11) NOT NULL default '0',
  KEY `event_id_idx` (`event_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_notification_event`
--

LOCK TABLES `bug_notification_event` WRITE;
/*!40000 ALTER TABLE `bug_notification_event` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_notification_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_notification_role`
--

DROP TABLE IF EXISTS `bug_notification_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_notification_role` (
  `role_id` int(11) NOT NULL default '0',
  `role_label` varchar(255) default NULL,
  `short_description` varchar(40) default NULL,
  `description` varchar(255) default NULL,
  `rank` int(11) NOT NULL default '0',
  KEY `role_id_idx` (`role_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_notification_role`
--

LOCK TABLES `bug_notification_role` WRITE;
/*!40000 ALTER TABLE `bug_notification_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_notification_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_report`
--

DROP TABLE IF EXISTS `bug_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_report` (
  `report_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '100',
  `user_id` int(11) NOT NULL default '100',
  `name` varchar(80) default NULL,
  `description` varchar(255) default NULL,
  `scope` char(1) NOT NULL default 'I',
  PRIMARY KEY  (`report_id`),
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `scope_idx` (`scope`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_report`
--

LOCK TABLES `bug_report` WRITE;
/*!40000 ALTER TABLE `bug_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_report_field`
--

DROP TABLE IF EXISTS `bug_report_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_report_field` (
  `report_id` int(11) NOT NULL default '100',
  `field_name` varchar(255) default NULL,
  `show_on_query` int(11) default NULL,
  `show_on_result` int(11) default NULL,
  `place_query` int(11) default NULL,
  `place_result` int(11) default NULL,
  `col_width` int(11) default NULL,
  KEY `profile_id_idx` (`report_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_report_field`
--

LOCK TABLES `bug_report_field` WRITE;
/*!40000 ALTER TABLE `bug_report_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_report_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_task_dependencies`
--

DROP TABLE IF EXISTS `bug_task_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_task_dependencies` (
  `bug_depend_id` int(11) NOT NULL auto_increment,
  `bug_id` int(11) NOT NULL default '0',
  `is_dependent_on_task_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`bug_depend_id`),
  KEY `idx_bug_task_dependencies_bug_id` (`bug_id`),
  KEY `idx_bug_task_is_dependent_on_task_id` (`is_dependent_on_task_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_task_dependencies`
--

LOCK TABLES `bug_task_dependencies` WRITE;
/*!40000 ALTER TABLE `bug_task_dependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_task_dependencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bug_watcher`
--

DROP TABLE IF EXISTS `bug_watcher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bug_watcher` (
  `user_id` int(11) NOT NULL default '0',
  `watchee_id` int(11) NOT NULL default '0',
  KEY `user_id_idx` (`user_id`),
  KEY `watchee_id_idx` (`watchee_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bug_watcher`
--

LOCK TABLES `bug_watcher` WRITE;
/*!40000 ALTER TABLE `bug_watcher` DISABLE KEYS */;
/*!40000 ALTER TABLE `bug_watcher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cross_references`
--

DROP TABLE IF EXISTS `cross_references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cross_references` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `created_at` int(11) NOT NULL default '0',
  `user_id` int(11) unsigned NOT NULL default '0',
  `source_type` varchar(255) NOT NULL,
  `source_keyword` varchar(32) NOT NULL,
  `source_id` varchar(255) NOT NULL default '0',
  `source_gid` int(11) unsigned NOT NULL default '0',
  `target_type` varchar(255) NOT NULL,
  `target_keyword` varchar(32) NOT NULL,
  `target_id` varchar(255) NOT NULL default '0',
  `target_gid` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=14512 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cross_references`
--

LOCK TABLES `cross_references` WRITE;
/*!40000 ALTER TABLE `cross_references` DISABLE KEYS */;
/*!40000 ALTER TABLE `cross_references` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cvs_branches`
--

DROP TABLE IF EXISTS `cvs_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_branches` (
  `id` mediumint(9) NOT NULL auto_increment,
  `branch` varchar(64) character set utf8 collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `branch` (`branch`)
)  AUTO_INCREMENT=689 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cvs_branches`
--

LOCK TABLES `cvs_branches` WRITE;
/*!40000 ALTER TABLE `cvs_branches` DISABLE KEYS */;
/*!40000 ALTER TABLE `cvs_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cvs_checkins`
--

DROP TABLE IF EXISTS `cvs_checkins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_checkins` (
  `type` enum('Change','Add','Remove') default NULL,
  `ci_when` datetime NOT NULL default '0000-00-00 00:00:00',
  `whoid` mediumint(9) NOT NULL default '0',
  `repositoryid` mediumint(9) NOT NULL default '0',
  `dirid` mediumint(9) NOT NULL default '0',
  `fileid` mediumint(9) NOT NULL default '0',
  `revision` varchar(32) character set utf8 collate utf8_bin default NULL,
  `stickytag` varchar(255) character set utf8 collate utf8_bin NOT NULL,
  `branchid` mediumint(9) NOT NULL default '0',
  `addedlines` int(11) NOT NULL default '999',
  `removedlines` int(11) NOT NULL default '999',
  `commitid` int(11) NOT NULL default '0',
  `descid` int(11) NOT NULL default '0',
  UNIQUE KEY `repositoryid` (`repositoryid`,`dirid`,`fileid`,`revision`),
  KEY `ci_when` (`ci_when`),
  KEY `repositoryid_2` (`repositoryid`),
  KEY `dirid` (`dirid`),
  KEY `fileid` (`fileid`),
  KEY `branchid` (`branchid`),
  KEY `commitid` (`commitid`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cvs_checkins`
--

LOCK TABLES `cvs_checkins` WRITE;
/*!40000 ALTER TABLE `cvs_checkins` DISABLE KEYS */;
/*!40000 ALTER TABLE `cvs_checkins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cvs_commits`
--

DROP TABLE IF EXISTS `cvs_commits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_commits` (
  `id` mediumint(9) NOT NULL auto_increment,
  `comm_when` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `whoid` mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `whoid` (`whoid`)
)  AUTO_INCREMENT=281956 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cvs_commits`
--

LOCK TABLES `cvs_commits` WRITE;
/*!40000 ALTER TABLE `cvs_commits` DISABLE KEYS */;
/*!40000 ALTER TABLE `cvs_commits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cvs_descs`
--

DROP TABLE IF EXISTS `cvs_descs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_descs` (
  `id` mediumint(9) NOT NULL auto_increment,
  `description` text,
  `hash` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `hash` (`hash`)
)  AUTO_INCREMENT=220147 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cvs_descs`
--

LOCK TABLES `cvs_descs` WRITE;
/*!40000 ALTER TABLE `cvs_descs` DISABLE KEYS */;
/*!40000 ALTER TABLE `cvs_descs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cvs_dirs`
--

DROP TABLE IF EXISTS `cvs_dirs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_dirs` (
  `id` mediumint(9) NOT NULL auto_increment,
  `dir` varchar(128) character set utf8 collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `dir` (`dir`)
)  AUTO_INCREMENT=134314 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cvs_dirs`
--

LOCK TABLES `cvs_dirs` WRITE;
/*!40000 ALTER TABLE `cvs_dirs` DISABLE KEYS */;
/*!40000 ALTER TABLE `cvs_dirs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cvs_files`
--

DROP TABLE IF EXISTS `cvs_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_files` (
  `id` mediumint(9) NOT NULL auto_increment,
  `file` varchar(128) character set utf8 collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `file` (`file`)
)  AUTO_INCREMENT=285080 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cvs_files`
--

LOCK TABLES `cvs_files` WRITE;
/*!40000 ALTER TABLE `cvs_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `cvs_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cvs_repositories`
--

DROP TABLE IF EXISTS `cvs_repositories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_repositories` (
  `id` mediumint(9) NOT NULL auto_increment,
  `repository` varchar(64) character set utf8 collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `repository` (`repository`)
)  AUTO_INCREMENT=467 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cvs_repositories`
--

LOCK TABLES `cvs_repositories` WRITE;
/*!40000 ALTER TABLE `cvs_repositories` DISABLE KEYS */;
/*!40000 ALTER TABLE `cvs_repositories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cvs_tags`
--

DROP TABLE IF EXISTS `cvs_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cvs_tags` (
  `repositoryid` mediumint(9) NOT NULL default '0',
  `branchid` mediumint(9) NOT NULL default '0',
  `dirid` mediumint(9) NOT NULL default '0',
  `fileid` mediumint(9) NOT NULL default '0',
  `revision` varchar(32) character set utf8 collate utf8_bin NOT NULL,
  KEY `repositoryid_2` (`repositoryid`),
  KEY `dirid` (`dirid`),
  KEY `fileid` (`fileid`),
  KEY `branchid` (`branchid`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cvs_tags`
--

LOCK TABLES `cvs_tags` WRITE;
/*!40000 ALTER TABLE `cvs_tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `cvs_tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `db_images`
--

DROP TABLE IF EXISTS `db_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `db_images` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `description` text NOT NULL,
  `bin_data` longblob NOT NULL,
  `filename` text NOT NULL,
  `filesize` int(11) NOT NULL default '0',
  `filetype` text NOT NULL,
  `width` int(11) NOT NULL default '0',
  `height` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `idx_db_images_group` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `db_images`
--

LOCK TABLES `db_images` WRITE;
/*!40000 ALTER TABLE `db_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `db_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_data`
--

DROP TABLE IF EXISTS `doc_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_data` (
  `docid` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  `data` longblob NOT NULL,
  `updatedate` int(11) NOT NULL default '0',
  `createdate` int(11) NOT NULL default '0',
  `created_by` int(11) NOT NULL default '0',
  `doc_group` int(11) NOT NULL default '0',
  `description` text,
  `filename` text,
  `filesize` int(10) unsigned NOT NULL default '0',
  `filetype` text,
  `rank` int(11) NOT NULL default '0',
  PRIMARY KEY  (`docid`),
  KEY `idx_doc_group_doc_group` (`doc_group`)
)  AUTO_INCREMENT=2430 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_data`
--

LOCK TABLES `doc_data` WRITE;
/*!40000 ALTER TABLE `doc_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_groups`
--

DROP TABLE IF EXISTS `doc_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_groups` (
  `doc_group` int(12) NOT NULL auto_increment,
  `groupname` varchar(255) NOT NULL,
  `group_rank` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`doc_group`),
  KEY `idx_doc_groups_group` (`group_id`)
)  AUTO_INCREMENT=3502 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_groups`
--

LOCK TABLES `doc_groups` WRITE;
/*!40000 ALTER TABLE `doc_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `doc_log`
--

DROP TABLE IF EXISTS `doc_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `doc_log` (
  `user_id` int(11) NOT NULL default '0',
  `docid` int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  KEY `all_idx` (`user_id`,`docid`),
  KEY `time_idx` (`time`),
  KEY `docid_idx` (`docid`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doc_log`
--

LOCK TABLES `doc_log` WRITE;
/*!40000 ALTER TABLE `doc_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `doc_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feedback` (
  `session_hash` varchar(32) NOT NULL,
  `feedback` text NOT NULL,
  `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`session_hash`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback`
--

LOCK TABLES `feedback` WRITE;
/*!40000 ALTER TABLE `feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filedownload_log`
--

DROP TABLE IF EXISTS `filedownload_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filedownload_log` (
  `user_id` int(11) NOT NULL default '0',
  `filerelease_id` int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  KEY `all_idx` (`user_id`,`filerelease_id`),
  KEY `time_idx` (`time`),
  KEY `filerelease_id_idx` (`filerelease_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filedownload_log`
--

LOCK TABLES `filedownload_log` WRITE;
/*!40000 ALTER TABLE `filedownload_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `filedownload_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filemodule`
--

DROP TABLE IF EXISTS `filemodule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filemodule` (
  `filemodule_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `module_name` varchar(40) default NULL,
  `recent_filerelease` varchar(20) NOT NULL,
  PRIMARY KEY  (`filemodule_id`),
  KEY `idx_filemodule_group_id` (`group_id`)
)  AUTO_INCREMENT=2990 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filemodule`
--

LOCK TABLES `filemodule` WRITE;
/*!40000 ALTER TABLE `filemodule` DISABLE KEYS */;
/*!40000 ALTER TABLE `filemodule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filemodule_monitor`
--

DROP TABLE IF EXISTS `filemodule_monitor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filemodule_monitor` (
  `filemodule_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  KEY `idx_filemodule_monitor_id` (`filemodule_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filemodule_monitor`
--

LOCK TABLES `filemodule_monitor` WRITE;
/*!40000 ALTER TABLE `filemodule_monitor` DISABLE KEYS */;
/*!40000 ALTER TABLE `filemodule_monitor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `filerelease`
--

DROP TABLE IF EXISTS `filerelease`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `filerelease` (
  `filerelease_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `unix_box` varchar(20) NOT NULL default 'remission',
  `unix_partition` int(11) NOT NULL default '0',
  `text_notes` text,
  `text_changes` text,
  `release_version` varchar(20) default NULL,
  `filename` varchar(80) default NULL,
  `filemodule_id` int(11) NOT NULL default '0',
  `file_type` varchar(50) default NULL,
  `release_time` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  `file_size` int(11) default NULL,
  `post_time` int(11) NOT NULL default '0',
  `text_format` int(11) NOT NULL default '0',
  `downloads_week` int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'N',
  `old_filename` varchar(80) NOT NULL,
  PRIMARY KEY  (`filerelease_id`),
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `unix_box_idx` (`unix_box`),
  KEY `post_time_idx` (`post_time`),
  KEY `idx_release_time` (`release_time`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `filerelease`
--

LOCK TABLES `filerelease` WRITE;
/*!40000 ALTER TABLE `filerelease` DISABLE KEYS */;
/*!40000 ALTER TABLE `filerelease` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forge_upgrade_bucket`
--

DROP TABLE IF EXISTS `forge_upgrade_bucket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forge_upgrade_bucket` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `script` varchar(255) NOT NULL default '',
  `start_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `end_date` datetime NOT NULL default '0000-00-00 00:00:00',
  `status` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=81 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forge_upgrade_bucket`
--

LOCK TABLES `forge_upgrade_bucket` WRITE;
/*!40000 ALTER TABLE `forge_upgrade_bucket` DISABLE KEYS */;
INSERT INTO `forge_upgrade_bucket` VALUES (48,'/usr/share/codendi/src/db/mysql/updates/2010/201007220743_add_table_notification_delegation.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(49,'/usr/share/codendi/src/db/mysql/updates/2010/201007291109_modify_pk_table_notification_delegation.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(50,'/usr/share/codendi/src/db/mysql/updates/2010/201008200802_reduce_load_on_user_table.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(51,'/usr/share/codendi/src/db/mysql/updates/2010/201009280731_add_table_groups_notif_delegation_message.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(52,'/usr/share/codendi/plugins/docman/db/mysql/updates/201010181624_add_table_plugin_docman_version_deleted.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(53,'/usr/share/codendi/src/db/mysql/updates/2010/201010191436_add_table_frs_file_deleted.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(54,'/usr/share/codendi/src/db/mysql/updates/2010/201010201546_fill_frs_file_deleted.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(55,'/usr/share/codendi/plugins/docman/db/mysql/updates/201010201624_add_table_plugin_docman_item_deleted.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(56,'/usr/share/codendi/src/db/mysql/updates/2010/201011230835_add_column_format_to_artifact_history.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(57,'/usr/share/codendi/src/db/mysql/updates/2010/201012140821_improve_frs_file.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(58,'/usr/share/codendi/src/db/mysql/updates/2010/201012240808_add_table_frs_log.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(59,'/usr/share/codendi/plugins/git/db/mysql/updates/201102081526_add_table_plugin_git_post_receive_mail.php','2012-01-16 21:01:02','2012-01-16 21:01:02',1),(60,'/usr/share/codendi/plugins/git/db/mysql/updates/201102090815_add_column_repository_events_mailing_prefix.php','2012-01-16 21:01:02','2012-01-16 21:01:02',2),(61,'/usr/share/codendi/plugins/git/db/mysql/updates/201102090815_add_column_repository_events_mailing_prefix.php','2012-01-16 21:03:15','2012-01-16 21:03:15',2),(62,'/usr/share/codendi/plugins/git/db/mysql/updates/201102090815_add_column_repository_events_mailing_prefix.php','2012-01-16 21:35:32','2012-01-16 21:35:32',2),(63,'/usr/share/codendi/plugins/git/db/mysql/updates/201102090815_add_column_repository_events_mailing_prefix.php','2012-01-16 21:36:40','2012-01-16 21:36:40',2),(64,'/usr/share/codendi/plugins/git/updates/2011/201102091302_deploy_post_receive_to_existing_repositories.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(65,'/usr/share/codendi/src/db/mysql/updates/2011/201103081738_add_column_filepath_to_frs_file.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(66,'/usr/share/codendi/src/db/mysql/updates/2011/201103281122_add_wiki_attachment_delete_mechanism.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(67,'/usr/share/codendi/plugins/git/db/mysql/updates/201106281541_add_backend_type.php','2012-01-16 21:36:40','2012-01-16 21:36:40',2),(68,'/usr/share/codendi/plugins/git/db/mysql/updates/201107071731_add_permission_type.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(69,'/usr/share/codendi/src/db/mysql/updates/2011/201108241423_remove_slmbug_and_story.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(70,'/usr/share/codendi/plugins/tracker/db/mysql/updates/2011/201108311118_add_widget_renderer.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(71,'/usr/share/codendi/src/db/mysql/updates/2011/201108311456_add_image_renderer.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(72,'/usr/share/codendi/plugins/tracker/db/mysql/updates/2011/201109151443_reactivate_plugin_in_templates.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(73,'/usr/share/codendi/plugins/tracker/db/mysql/updates/2011/201110051717_add_postaction_field_date_table.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(74,'/usr/share/codendi/plugins/docman/db/mysql/updates/201110171036_add_docman_approval_user_index.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(75,'/usr/share/codendi/src/db/mysql/updates/2011/201111021759_id_sharing.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(76,'/usr/share/codendi/plugins/git/db/mysql/updates/201111090857_add_table_plugin_git_log.php','2012-01-16 21:36:40','2012-01-16 21:36:40',1),(77,'/usr/share/codendi/plugins/git/db/mysql/updates/201112130946_add_user_id.php','2012-01-16 21:36:40','2012-01-16 21:36:40',2),(78,'/usr/share/codendi/plugins/git/db/mysql/updates/201112150858_add_repository_scope.php','2012-01-16 21:36:40','2012-01-16 21:36:40',2),(79,'/usr/share/codendi/plugins/git/db/mysql/updates/201112151709_add_repository_namespace.php','2012-01-16 21:36:40','2012-01-16 21:36:40',2),(80,'/usr/share/codendi/plugins/git/db/mysql/updates/201112160838_delete_user_id.php','2012-01-16 21:36:40','2012-01-16 21:36:40',2);
/*!40000 ALTER TABLE `forge_upgrade_bucket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forge_upgrade_log`
--

DROP TABLE IF EXISTS `forge_upgrade_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forge_upgrade_log` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `bucket_id` int(11) unsigned default NULL,
  `timestamp` datetime NOT NULL default '0000-00-00 00:00:00',
  `logger` varchar(64) NOT NULL default '',
  `level` varchar(32) NOT NULL default '',
  `message` text NOT NULL,
  `thread` varchar(32) NOT NULL default '',
  `file` varchar(255) NOT NULL default '',
  `line` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `bucket_id` (`bucket_id`),
  CONSTRAINT `forge_upgrade_log_ibfk_1` FOREIGN KEY (`bucket_id`) REFERENCES `forge_upgrade_bucket` (`id`) ON DELETE CASCADE
)  AUTO_INCREMENT=197 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forge_upgrade_log`
--

LOCK TABLES `forge_upgrade_log` WRITE;
/*!40000 ALTER TABLE `forge_upgrade_log` DISABLE KEYS */;
INSERT INTO `forge_upgrade_log` VALUES (72,48,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201007220743_add_table_notification_delegation','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(73,48,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(74,48,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(75,48,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(76,49,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201007291109_modify_pk_table_notification_delegation','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(77,49,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(78,49,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(79,49,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(80,50,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201008200802_reduce_load_on_user_table','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(81,50,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(82,50,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(83,50,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(84,51,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201009280731_add_table_groups_notif_delegation_message','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(85,51,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(86,51,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(87,51,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(88,52,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201010181624_add_table_plugin_docman_version_deleted','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(89,52,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(90,52,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(91,52,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(92,53,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201010191436_add_table_frs_file_deleted','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(93,53,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(94,53,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(95,53,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(96,54,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201010201546_fill_frs_file_deleted','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(97,54,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(98,54,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(99,54,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(100,55,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201010201624_add_table_plugin_docman_item_deleted','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(101,55,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(102,55,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(103,55,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(104,56,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201011230835_add_column_format_to_artifact_history','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(105,56,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(106,56,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(107,56,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(108,57,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201012140821_improve_frs_file','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(109,57,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(110,57,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(111,57,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(112,58,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201012240808_add_table_frs_log','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(113,58,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(114,58,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(115,58,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(116,59,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201102081526_add_table_plugin_git_post_receive_mail','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(117,59,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(118,59,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Up OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(119,59,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PostUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(120,60,'2012-01-16 21:01:02','ForgeUpgrade','INFO','Processing b201102090815_add_column_repository_events_mailing_prefix','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(121,60,'2012-01-16 21:01:02','ForgeUpgrade','INFO','PreUp OK','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(122,60,'2012-01-16 21:01:02','ForgeUpgrade','ERROR','An error occured while adding the column repository_events_mailing_prefix to the table plugin_git','4854','/usr/share/forgeupgrade/src/ForgeUpgrade.php',333),(123,61,'2012-01-16 21:03:15','ForgeUpgrade','INFO','Processing b201102090815_add_column_repository_events_mailing_prefix','4874','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(124,61,'2012-01-16 21:03:15','ForgeUpgrade','INFO','PreUp OK','4874','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(125,61,'2012-01-16 21:03:15','ForgeUpgrade','ERROR','An error occured while adding the column repository_events_mailing_prefix to the table plugin_git','4874','/usr/share/forgeupgrade/src/ForgeUpgrade.php',333),(126,62,'2012-01-16 21:35:32','ForgeUpgrade','INFO','Processing b201102090815_add_column_repository_events_mailing_prefix','4936','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(127,62,'2012-01-16 21:35:32','ForgeUpgrade','INFO','PreUp OK','4936','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(128,62,'2012-01-16 21:35:32','ForgeUpgrade','ERROR','An error occured while adding the column repository_events_mailing_prefix to the table plugin_git','4936','/usr/share/forgeupgrade/src/ForgeUpgrade.php',333),(129,63,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201102090815_add_column_repository_events_mailing_prefix','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(130,63,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(131,63,'2012-01-16 21:36:40','ForgeUpgrade','ERROR','An error occured while adding the column repository_events_mailing_prefix to the table plugin_git','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',342),(132,64,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201102091302_deploy_post_receive_to_existing_repositories','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(133,64,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(134,64,'2012-01-16 21:36:40','ForgeUpgrade_Bucket','INFO','Deploy /var/lib/codendi/gitroot/nty/hoooola.git/hooks/post-receive','4970','/usr/share/tuleap_jm1974/plugins/git/updates/2011/201102091302_deploy_post_receive_to_existing_repositories.php',33),(135,64,'2012-01-16 21:36:40','ForgeUpgrade_Bucket','INFO','Deploy /var/lib/codendi/gitroot/nty/hej.git/hooks/post-receive','4970','/usr/share/tuleap_jm1974/plugins/git/updates/2011/201102091302_deploy_post_receive_to_existing_repositories.php',33),(136,64,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(137,64,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(138,65,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201103081738_add_column_filepath_to_frs_file','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(139,65,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(140,65,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(141,65,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(142,66,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201103281122_add_wiki_attachment_delete_mechanism','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(143,66,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(144,66,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(145,66,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(146,67,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201106281541_add_backend_type','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(147,67,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(148,67,'2012-01-16 21:36:40','ForgeUpgrade','ERROR','An error occured while adding the column repository_events_mailing_prefix to the table plugin_git','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',342),(149,68,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201107071731_add_permission_type','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(150,68,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(151,68,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(152,68,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(153,69,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201108241423_remove_slmbug_and_story','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(154,69,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(155,69,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(156,69,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(157,70,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201108311118_add_widget_renderer','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(158,70,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(159,70,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(160,70,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(161,71,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201108311456_add_image_renderer','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(162,71,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(163,71,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(164,71,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(165,72,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201109151443_reactivate_plugin_in_templates','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(166,72,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(167,72,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(168,72,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(169,73,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201110051717_add_postaction_field_date_table','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(170,73,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(171,73,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(172,73,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(173,74,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201110171036_add_docman_approval_user_index','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(174,74,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(175,74,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(176,74,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(177,75,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201111021759_id_sharing','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(178,75,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(179,75,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(180,75,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(181,76,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201111090857_add_table_plugin_git_log','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(182,76,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(183,76,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Up OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',301),(184,76,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PostUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',304),(185,77,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201112130946_add_user_id','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(186,77,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(187,77,'2012-01-16 21:36:40','ForgeUpgrade','ERROR','An error occured while adding the column user_id to the table plugin_git','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',342),(188,78,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201112150858_add_repository_scope','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(189,78,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(190,78,'2012-01-16 21:36:40','ForgeUpgrade','ERROR','An error occured while adding the column repository_scope to the table plugin_git','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',342),(191,79,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201112151709_add_repository_namespace','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(192,79,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(193,79,'2012-01-16 21:36:40','ForgeUpgrade','ERROR','An error occured while adding the column repository_namespace to the table plugin_git','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',342),(194,80,'2012-01-16 21:36:40','ForgeUpgrade','INFO','Processing b201112160838_delete_user_id','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',293),(195,80,'2012-01-16 21:36:40','ForgeUpgrade','INFO','PreUp OK','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',297),(196,80,'2012-01-16 21:36:40','ForgeUpgrade','ERROR','An error occured while updating the column repository_scope to the table plugin_git','4970','/usr/share/forgeupgrade/src/ForgeUpgrade.php',342);
/*!40000 ALTER TABLE `forge_upgrade_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum`
--

DROP TABLE IF EXISTS `forum`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum` (
  `msg_id` int(11) NOT NULL auto_increment,
  `group_forum_id` int(11) NOT NULL default '0',
  `posted_by` int(11) NOT NULL default '0',
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  `is_followup_to` int(11) NOT NULL default '0',
  `thread_id` int(11) NOT NULL default '0',
  `has_followups` int(11) default '0',
  PRIMARY KEY  (`msg_id`),
  KEY `idx_forum_group_forum_id` (`group_forum_id`),
  KEY `idx_forum_is_followup_to` (`is_followup_to`),
  KEY `idx_forum_thread_id` (`thread_id`),
  KEY `idx_forum_id_date` (`group_forum_id`,`date`),
  KEY `idx_forum_id_date_followup` (`group_forum_id`,`date`,`is_followup_to`),
  KEY `idx_forum_thread_date_followup` (`thread_id`,`date`,`is_followup_to`)
)  AUTO_INCREMENT=11029 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum`
--

LOCK TABLES `forum` WRITE;
/*!40000 ALTER TABLE `forum` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_agg_msg_count`
--

DROP TABLE IF EXISTS `forum_agg_msg_count`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_agg_msg_count` (
  `group_forum_id` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  PRIMARY KEY  (`group_forum_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_agg_msg_count`
--

LOCK TABLES `forum_agg_msg_count` WRITE;
/*!40000 ALTER TABLE `forum_agg_msg_count` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_agg_msg_count` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_group_list`
--

DROP TABLE IF EXISTS `forum_group_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_group_list` (
  `group_forum_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `forum_name` text NOT NULL,
  `is_public` int(11) NOT NULL default '0',
  `description` text,
  PRIMARY KEY  (`group_forum_id`),
  KEY `idx_forum_group_list_group_id` (`group_id`),
  FULLTEXT KEY `description` (`description`)
)  AUTO_INCREMENT=9844 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_group_list`
--

LOCK TABLES `forum_group_list` WRITE;
/*!40000 ALTER TABLE `forum_group_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_group_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_monitored_forums`
--

DROP TABLE IF EXISTS `forum_monitored_forums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_monitored_forums` (
  `monitor_id` int(11) NOT NULL auto_increment,
  `forum_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`monitor_id`),
  KEY `idx_forum_monitor_thread_id` (`forum_id`),
  KEY `idx_forum_monitor_combo_id` (`forum_id`,`user_id`)
)  AUTO_INCREMENT=9661 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_monitored_forums`
--

LOCK TABLES `forum_monitored_forums` WRITE;
/*!40000 ALTER TABLE `forum_monitored_forums` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_monitored_forums` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_monitored_threads`
--

DROP TABLE IF EXISTS `forum_monitored_threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_monitored_threads` (
  `thread_monitor_id` int(11) NOT NULL auto_increment,
  `forum_id` int(11) NOT NULL default '0',
  `thread_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`thread_monitor_id`)
)  AUTO_INCREMENT=116 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_monitored_threads`
--

LOCK TABLES `forum_monitored_threads` WRITE;
/*!40000 ALTER TABLE `forum_monitored_threads` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_monitored_threads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_saved_place`
--

DROP TABLE IF EXISTS `forum_saved_place`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_saved_place` (
  `saved_place_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `forum_id` int(11) NOT NULL default '0',
  `save_date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`saved_place_id`)
)  AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_saved_place`
--

LOCK TABLES `forum_saved_place` WRITE;
/*!40000 ALTER TABLE `forum_saved_place` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_saved_place` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forum_thread_id`
--

DROP TABLE IF EXISTS `forum_thread_id`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `forum_thread_id` (
  `thread_id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`thread_id`)
)  AUTO_INCREMENT=10155 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forum_thread_id`
--

LOCK TABLES `forum_thread_id` WRITE;
/*!40000 ALTER TABLE `forum_thread_id` DISABLE KEYS */;
/*!40000 ALTER TABLE `forum_thread_id` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_dlstats_agg`
--

DROP TABLE IF EXISTS `frs_dlstats_agg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_dlstats_agg` (
  `file_id` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  `downloads_http` int(11) NOT NULL default '0',
  `downloads_ftp` int(11) NOT NULL default '0',
  KEY `file_id_idx` (`file_id`),
  KEY `day_idx` (`day`),
  KEY `downloads_http_idx` (`downloads_http`),
  KEY `downloads_ftp_idx` (`downloads_ftp`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_dlstats_agg`
--

LOCK TABLES `frs_dlstats_agg` WRITE;
/*!40000 ALTER TABLE `frs_dlstats_agg` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_dlstats_agg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_dlstats_file_agg`
--

DROP TABLE IF EXISTS `frs_dlstats_file_agg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_dlstats_file_agg` (
  `file_id` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  KEY `idx_dlstats_file_file_id` (`file_id`),
  KEY `idx_dlstats_file_day` (`day`),
  KEY `idx_dlstats_file_down` (`downloads`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_dlstats_file_agg`
--

LOCK TABLES `frs_dlstats_file_agg` WRITE;
/*!40000 ALTER TABLE `frs_dlstats_file_agg` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_dlstats_file_agg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_dlstats_filetotal_agg`
--

DROP TABLE IF EXISTS `frs_dlstats_filetotal_agg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_dlstats_filetotal_agg` (
  `file_id` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  KEY `idx_stats_agr_tmp_fid` (`file_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_dlstats_filetotal_agg`
--

LOCK TABLES `frs_dlstats_filetotal_agg` WRITE;
/*!40000 ALTER TABLE `frs_dlstats_filetotal_agg` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_dlstats_filetotal_agg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_dlstats_group_agg`
--

DROP TABLE IF EXISTS `frs_dlstats_group_agg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_dlstats_group_agg` (
  `group_id` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  KEY `group_id_idx` (`group_id`),
  KEY `day_idx` (`day`),
  KEY `downloads_idx` (`downloads`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_dlstats_group_agg`
--

LOCK TABLES `frs_dlstats_group_agg` WRITE;
/*!40000 ALTER TABLE `frs_dlstats_group_agg` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_dlstats_group_agg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_dlstats_grouptotal_agg`
--

DROP TABLE IF EXISTS `frs_dlstats_grouptotal_agg`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_dlstats_grouptotal_agg` (
  `group_id` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  KEY `idx_stats_agr_tmp_gid` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_dlstats_grouptotal_agg`
--

LOCK TABLES `frs_dlstats_grouptotal_agg` WRITE;
/*!40000 ALTER TABLE `frs_dlstats_grouptotal_agg` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_dlstats_grouptotal_agg` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_file`
--

DROP TABLE IF EXISTS `frs_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_file` (
  `file_id` int(11) NOT NULL auto_increment,
  `filename` text,
  `filepath` varchar(255) default NULL,
  `release_id` int(11) NOT NULL default '0',
  `type_id` int(11) NOT NULL default '0',
  `processor_id` int(11) NOT NULL default '0',
  `release_time` int(11) NOT NULL default '0',
  `file_size` bigint(20) NOT NULL default '0',
  `post_date` int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  `computed_md5` varchar(32) default NULL,
  `reference_md5` varchar(32) default NULL,
  `user_id` int(11) default NULL,
  PRIMARY KEY  (`file_id`),
  KEY `idx_frs_file_release_id` (`release_id`),
  KEY `idx_frs_file_type` (`type_id`),
  KEY `idx_frs_file_date` (`post_date`),
  KEY `idx_frs_file_processor` (`processor_id`),
  KEY `idx_frs_file_name` (`filename`(45))
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_file`
--

LOCK TABLES `frs_file` WRITE;
/*!40000 ALTER TABLE `frs_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_file_deleted`
--

DROP TABLE IF EXISTS `frs_file_deleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_file_deleted` (
  `file_id` int(11) NOT NULL,
  `filename` text,
  `filepath` varchar(255) default NULL,
  `release_id` int(11) NOT NULL default '0',
  `type_id` int(11) NOT NULL default '0',
  `processor_id` int(11) NOT NULL default '0',
  `release_time` int(11) NOT NULL default '0',
  `file_size` bigint(20) NOT NULL default '0',
  `post_date` int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  `computed_md5` varchar(32) default NULL,
  `reference_md5` varchar(32) default NULL,
  `user_id` int(11) default NULL,
  `delete_date` int(11) unsigned default NULL,
  `purge_date` int(11) unsigned default NULL,
  PRIMARY KEY  (`file_id`),
  KEY `idx_delete_date` (`delete_date`),
  KEY `idx_purge_date` (`purge_date`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_file_deleted`
--

LOCK TABLES `frs_file_deleted` WRITE;
/*!40000 ALTER TABLE `frs_file_deleted` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_file_deleted` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_filetype`
--

DROP TABLE IF EXISTS `frs_filetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_filetype` (
  `type_id` int(11) NOT NULL auto_increment,
  `name` text,
  PRIMARY KEY  (`type_id`)
)  AUTO_INCREMENT=10000 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_filetype`
--

LOCK TABLES `frs_filetype` WRITE;
/*!40000 ALTER TABLE `frs_filetype` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_filetype` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_log`
--

DROP TABLE IF EXISTS `frs_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_log` (
  `log_id` int(11) NOT NULL auto_increment,
  `time` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `item_id` int(11) NOT NULL,
  `action_id` int(11) NOT NULL,
  PRIMARY KEY  (`log_id`),
  KEY `idx_frs_log_group_item` (`group_id`,`item_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_log`
--

LOCK TABLES `frs_log` WRITE;
/*!40000 ALTER TABLE `frs_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_package`
--

DROP TABLE IF EXISTS `frs_package`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_package` (
  `package_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `name` text,
  `status_id` int(11) NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  `approve_license` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`package_id`),
  KEY `idx_package_group_id` (`group_id`)
)  AUTO_INCREMENT=2708 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_package`
--

LOCK TABLES `frs_package` WRITE;
/*!40000 ALTER TABLE `frs_package` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_package` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_processor`
--

DROP TABLE IF EXISTS `frs_processor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_processor` (
  `processor_id` int(11) NOT NULL auto_increment,
  `name` text,
  `rank` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`processor_id`)
)  AUTO_INCREMENT=10021 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_processor`
--

LOCK TABLES `frs_processor` WRITE;
/*!40000 ALTER TABLE `frs_processor` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_processor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frs_release`
--

DROP TABLE IF EXISTS `frs_release`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `frs_release` (
  `release_id` int(11) NOT NULL auto_increment,
  `package_id` int(11) NOT NULL default '0',
  `name` text,
  `notes` text,
  `changes` text,
  `status_id` int(11) NOT NULL default '0',
  `preformatted` int(11) NOT NULL default '0',
  `release_date` int(11) NOT NULL default '0',
  `released_by` int(11) NOT NULL default '0',
  PRIMARY KEY  (`release_id`),
  KEY `idx_frs_release_by` (`released_by`),
  KEY `idx_frs_release_date` (`release_date`),
  KEY `idx_frs_release_package` (`package_id`)
)  AUTO_INCREMENT=9721 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `frs_release`
--

LOCK TABLES `frs_release` WRITE;
/*!40000 ALTER TABLE `frs_release` DISABLE KEYS */;
/*!40000 ALTER TABLE `frs_release` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_cvs_full_history`
--

DROP TABLE IF EXISTS `group_cvs_full_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_cvs_full_history` (
  `group_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  `cvs_commits` int(11) NOT NULL default '0',
  `cvs_adds` int(11) NOT NULL default '0',
  `cvs_checkouts` int(11) NOT NULL default '0',
  `cvs_browse` int(11) NOT NULL default '0',
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `day_idx` (`day`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_cvs_full_history`
--

LOCK TABLES `group_cvs_full_history` WRITE;
/*!40000 ALTER TABLE `group_cvs_full_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_cvs_full_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_cvs_history`
--

DROP TABLE IF EXISTS `group_cvs_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_cvs_history` (
  `group_id` int(11) NOT NULL default '0',
  `user_name` varchar(80) NOT NULL,
  `cvs_commits` int(11) NOT NULL default '0',
  `cvs_commits_wk` int(11) NOT NULL default '0',
  `cvs_adds` int(11) NOT NULL default '0',
  `cvs_adds_wk` int(11) NOT NULL default '0',
  KEY `group_id_idx` (`group_id`),
  KEY `user_name_idx` (`user_name`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_cvs_history`
--

LOCK TABLES `group_cvs_history` WRITE;
/*!40000 ALTER TABLE `group_cvs_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_cvs_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_desc`
--

DROP TABLE IF EXISTS `group_desc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_desc` (
  `group_desc_id` int(11) NOT NULL auto_increment,
  `desc_required` tinyint(1) NOT NULL default '0',
  `desc_name` varchar(255) NOT NULL,
  `desc_description` text,
  `desc_rank` int(11) NOT NULL default '0',
  `desc_type` enum('line','text') NOT NULL default 'text',
  PRIMARY KEY  (`group_desc_id`),
  UNIQUE KEY `desc_name` (`desc_name`)
)  AUTO_INCREMENT=106 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_desc`
--

LOCK TABLES `group_desc` WRITE;
/*!40000 ALTER TABLE `group_desc` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_desc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_desc_value`
--

DROP TABLE IF EXISTS `group_desc_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_desc_value` (
  `desc_value_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `group_desc_id` int(11) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`desc_value_id`)
)  AUTO_INCREMENT=6799 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_desc_value`
--

LOCK TABLES `group_desc_value` WRITE;
/*!40000 ALTER TABLE `group_desc_value` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_desc_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_history`
--

DROP TABLE IF EXISTS `group_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_history` (
  `group_history_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `field_name` text NOT NULL,
  `old_value` text NOT NULL,
  `mod_by` int(11) NOT NULL default '0',
  `date` int(11) default NULL,
  PRIMARY KEY  (`group_history_id`),
  KEY `idx_group_history_group_id` (`group_id`)
)  AUTO_INCREMENT=296535 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_history`
--

LOCK TABLES `group_history` WRITE;
/*!40000 ALTER TABLE `group_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_svn_full_history`
--

DROP TABLE IF EXISTS `group_svn_full_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_svn_full_history` (
  `group_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  `svn_commits` int(11) NOT NULL default '0',
  `svn_adds` int(11) NOT NULL default '0',
  `svn_deletes` int(11) NOT NULL default '0',
  `svn_checkouts` int(11) NOT NULL default '0',
  `svn_access_count` int(11) NOT NULL default '0',
  `svn_browse` int(11) NOT NULL default '0',
  UNIQUE KEY `accessid` (`group_id`,`user_id`,`day`),
  KEY `group_id_idx` (`group_id`),
  KEY `user_id_idx` (`user_id`),
  KEY `day_idx` (`day`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_svn_full_history`
--

LOCK TABLES `group_svn_full_history` WRITE;
/*!40000 ALTER TABLE `group_svn_full_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_svn_full_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `group_type`
--

DROP TABLE IF EXISTS `group_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `group_type` (
  `type_id` int(11) NOT NULL default '0',
  `name` text NOT NULL,
  PRIMARY KEY  (`type_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `group_type`
--

LOCK TABLES `group_type` WRITE;
/*!40000 ALTER TABLE `group_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL auto_increment,
  `group_name` varchar(255) default NULL,
  `is_public` int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  `unix_group_name` varchar(30) NOT NULL,
  `unix_box` varchar(20) NOT NULL default 'shell1',
  `http_domain` varchar(80) default NULL,
  `short_description` varchar(255) default NULL,
  `cvs_box` varchar(20) NOT NULL default 'cvs1',
  `svn_box` varchar(20) NOT NULL default 'svn1',
  `license` varchar(16) default NULL,
  `license_other` text,
  `register_time` int(11) NOT NULL default '0',
  `rand_hash` text,
  `new_bug_address` text NOT NULL,
  `new_patch_address` text NOT NULL,
  `new_support_address` text NOT NULL,
  `new_task_address` text NOT NULL,
  `type` int(11) NOT NULL default '1',
  `built_from_template` int(11) NOT NULL default '100',
  `send_all_bugs` int(11) NOT NULL default '0',
  `send_all_patches` int(11) NOT NULL default '0',
  `send_all_support` int(11) NOT NULL default '0',
  `send_all_tasks` int(11) NOT NULL default '0',
  `bug_preamble` text NOT NULL,
  `support_preamble` text NOT NULL,
  `patch_preamble` text NOT NULL,
  `pm_preamble` text NOT NULL,
  `xrx_export_ettm` int(11) NOT NULL default '0',
  `bug_allow_anon` int(11) NOT NULL default '1',
  `cvs_tracker` int(11) NOT NULL default '1',
  `cvs_watch_mode` int(11) NOT NULL default '0',
  `cvs_events_mailing_list` text NOT NULL,
  `cvs_events_mailing_header` varchar(64) character set utf8 collate utf8_bin default NULL,
  `cvs_preamble` text NOT NULL,
  `cvs_is_private` tinyint(1) NOT NULL default '0',
  `svn_tracker` int(11) NOT NULL default '1',
  `svn_mandatory_ref` tinyint(4) NOT NULL default '0',
  `svn_events_mailing_list` text NOT NULL,
  `svn_events_mailing_header` varchar(64) character set utf8 collate utf8_bin default NULL,
  `svn_preamble` text NOT NULL,
  `svn_accessfile` text,
  PRIMARY KEY  (`group_id`),
  KEY `idx_groups_status` (`status`),
  KEY `idx_groups_public` (`is_public`),
  KEY `idx_groups_unix` (`unix_group_name`),
  KEY `idx_groups_type` (`type`)
)  AUTO_INCREMENT=3195 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups`
--

LOCK TABLES `groups` WRITE;
/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups_notif_delegation`
--

DROP TABLE IF EXISTS `groups_notif_delegation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_notif_delegation` (
  `group_id` int(11) NOT NULL default '0',
  `ugroup_id` int(11) NOT NULL,
  PRIMARY KEY  (`group_id`,`ugroup_id`),
  KEY `group_id` (`group_id`,`ugroup_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups_notif_delegation`
--

LOCK TABLES `groups_notif_delegation` WRITE;
/*!40000 ALTER TABLE `groups_notif_delegation` DISABLE KEYS */;
/*!40000 ALTER TABLE `groups_notif_delegation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `groups_notif_delegation_message`
--

DROP TABLE IF EXISTS `groups_notif_delegation_message`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups_notif_delegation_message` (
  `group_id` int(11) NOT NULL default '0',
  `msg_to_requester` text NOT NULL,
  PRIMARY KEY  (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `groups_notif_delegation_message`
--

LOCK TABLES `groups_notif_delegation_message` WRITE;
/*!40000 ALTER TABLE `groups_notif_delegation_message` DISABLE KEYS */;
INSERT INTO `groups_notif_delegation_message` VALUES (100,'member_request_delegation_msg_to_requester');
/*!40000 ALTER TABLE `groups_notif_delegation_message` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `image`
--

DROP TABLE IF EXISTS `image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `image` (
  `image_id` int(11) NOT NULL auto_increment,
  `image_category` int(11) NOT NULL default '1',
  `image_type` varchar(40) NOT NULL,
  `image_data` blob,
  `group_id` int(11) NOT NULL default '0',
  `image_bytes` int(11) NOT NULL default '0',
  `image_caption` text,
  `organization_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`image_id`),
  KEY `image_category_idx` (`image_category`),
  KEY `image_type_idx` (`image_type`),
  KEY `group_id_idx` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `image`
--

LOCK TABLES `image` WRITE;
/*!40000 ALTER TABLE `image` DISABLE KEYS */;
/*!40000 ALTER TABLE `image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `layouts`
--

DROP TABLE IF EXISTS `layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `layouts` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `scope` char(1) NOT NULL default 'S',
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `layouts`
--

LOCK TABLES `layouts` WRITE;
/*!40000 ALTER TABLE `layouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `layouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `layouts_contents`
--

DROP TABLE IF EXISTS `layouts_contents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `layouts_contents` (
  `owner_id` int(11) unsigned NOT NULL default '0',
  `owner_type` char(1) NOT NULL default 'u',
  `layout_id` int(11) unsigned NOT NULL default '0',
  `column_id` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `rank` int(11) NOT NULL default '0',
  `is_minimized` tinyint(1) NOT NULL default '0',
  `is_removed` tinyint(1) NOT NULL default '0',
  `display_preferences` tinyint(1) NOT NULL default '0',
  `content_id` int(11) unsigned NOT NULL default '0',
  KEY `user_id` (`owner_id`,`owner_type`,`layout_id`,`name`,`content_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `layouts_contents`
--

LOCK TABLES `layouts_contents` WRITE;
/*!40000 ALTER TABLE `layouts_contents` DISABLE KEYS */;
/*!40000 ALTER TABLE `layouts_contents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `layouts_rows`
--

DROP TABLE IF EXISTS `layouts_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `layouts_rows` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `layout_id` int(11) unsigned NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `layout_id` (`layout_id`)
)  AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `layouts_rows`
--

LOCK TABLES `layouts_rows` WRITE;
/*!40000 ALTER TABLE `layouts_rows` DISABLE KEYS */;
/*!40000 ALTER TABLE `layouts_rows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `layouts_rows_columns`
--

DROP TABLE IF EXISTS `layouts_rows_columns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `layouts_rows_columns` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `layout_row_id` int(11) unsigned NOT NULL default '0',
  `width` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `layout_row_id` (`layout_row_id`)
)  AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `layouts_rows_columns`
--

LOCK TABLES `layouts_rows_columns` WRITE;
/*!40000 ALTER TABLE `layouts_rows_columns` DISABLE KEYS */;
/*!40000 ALTER TABLE `layouts_rows_columns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mail_group_list`
--

DROP TABLE IF EXISTS `mail_group_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mail_group_list` (
  `group_list_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `list_name` text,
  `is_public` int(11) NOT NULL default '0',
  `password` varchar(16) default NULL,
  `list_admin` int(11) NOT NULL default '0',
  `status` int(11) NOT NULL default '0',
  `description` text,
  PRIMARY KEY  (`group_list_id`),
  KEY `idx_mail_group_list_group` (`group_id`)
)  AUTO_INCREMENT=998 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mail_group_list`
--

LOCK TABLES `mail_group_list` WRITE;
/*!40000 ALTER TABLE `mail_group_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `mail_group_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_bytes`
--

DROP TABLE IF EXISTS `news_bytes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_bytes` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `submitted_by` int(11) NOT NULL default '0',
  `is_approved` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `forum_id` int(11) NOT NULL default '0',
  `summary` text,
  `details` text,
  PRIMARY KEY  (`id`),
  KEY `idx_news_bytes_forum` (`forum_id`),
  KEY `idx_news_bytes_group` (`group_id`),
  KEY `idx_news_bytes_approved` (`is_approved`)
)  AUTO_INCREMENT=801 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_bytes`
--

LOCK TABLES `news_bytes` WRITE;
/*!40000 ALTER TABLE `news_bytes` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_bytes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `user_id` int(11) NOT NULL default '0',
  `object_id` int(11) NOT NULL default '0',
  `type` varchar(100) NOT NULL,
  PRIMARY KEY  (`user_id`,`object_id`,`type`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `owner_layouts`
--

DROP TABLE IF EXISTS `owner_layouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owner_layouts` (
  `owner_id` int(11) unsigned NOT NULL default '0',
  `owner_type` char(1) NOT NULL default 'u',
  `layout_id` int(11) unsigned NOT NULL default '0',
  `is_default` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`owner_id`,`owner_type`,`layout_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owner_layouts`
--

LOCK TABLES `owner_layouts` WRITE;
/*!40000 ALTER TABLE `owner_layouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `owner_layouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patch`
--

DROP TABLE IF EXISTS `patch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch` (
  `patch_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `patch_status_id` int(11) NOT NULL default '0',
  `patch_category_id` int(11) NOT NULL default '0',
  `submitted_by` int(11) NOT NULL default '0',
  `assigned_to` int(11) NOT NULL default '0',
  `open_date` int(11) NOT NULL default '0',
  `summary` text,
  `code` longblob,
  `close_date` int(11) NOT NULL default '0',
  `filename` varchar(255) NOT NULL,
  `filesize` varchar(50) NOT NULL,
  `filetype` varchar(50) NOT NULL,
  PRIMARY KEY  (`patch_id`),
  KEY `idx_patch_group_id` (`group_id`)
)  AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patch`
--

LOCK TABLES `patch` WRITE;
/*!40000 ALTER TABLE `patch` DISABLE KEYS */;
/*!40000 ALTER TABLE `patch` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patch_category`
--

DROP TABLE IF EXISTS `patch_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_category` (
  `patch_category_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `category_name` text NOT NULL,
  PRIMARY KEY  (`patch_category_id`),
  KEY `idx_patch_group_group_id` (`group_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patch_category`
--

LOCK TABLES `patch_category` WRITE;
/*!40000 ALTER TABLE `patch_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `patch_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patch_history`
--

DROP TABLE IF EXISTS `patch_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_history` (
  `patch_history_id` int(11) NOT NULL auto_increment,
  `patch_id` int(11) NOT NULL default '0',
  `field_name` text NOT NULL,
  `old_value` text NOT NULL,
  `mod_by` int(11) NOT NULL default '0',
  `date` int(11) default NULL,
  PRIMARY KEY  (`patch_history_id`),
  KEY `idx_patch_history_patch_id` (`patch_id`)
)  AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patch_history`
--

LOCK TABLES `patch_history` WRITE;
/*!40000 ALTER TABLE `patch_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `patch_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `patch_status`
--

DROP TABLE IF EXISTS `patch_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patch_status` (
  `patch_status_id` int(11) NOT NULL auto_increment,
  `status_name` text,
  PRIMARY KEY  (`patch_status_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patch_status`
--

LOCK TABLES `patch_status` WRITE;
/*!40000 ALTER TABLE `patch_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `patch_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `people_skill`
--

DROP TABLE IF EXISTS `people_skill`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_skill` (
  `skill_id` int(11) NOT NULL auto_increment,
  `name` text,
  PRIMARY KEY  (`skill_id`)
)  AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `people_skill`
--

LOCK TABLES `people_skill` WRITE;
/*!40000 ALTER TABLE `people_skill` DISABLE KEYS */;
/*!40000 ALTER TABLE `people_skill` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `people_skill_inventory`
--

DROP TABLE IF EXISTS `people_skill_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_skill_inventory` (
  `skill_inventory_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `skill_id` int(11) NOT NULL default '0',
  `skill_level_id` int(11) NOT NULL default '0',
  `skill_year_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`skill_inventory_id`)
)  AUTO_INCREMENT=678 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `people_skill_inventory`
--

LOCK TABLES `people_skill_inventory` WRITE;
/*!40000 ALTER TABLE `people_skill_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `people_skill_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `people_skill_level`
--

DROP TABLE IF EXISTS `people_skill_level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_skill_level` (
  `skill_level_id` int(11) NOT NULL auto_increment,
  `name` text,
  PRIMARY KEY  (`skill_level_id`)
)  AUTO_INCREMENT=61 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `people_skill_level`
--

LOCK TABLES `people_skill_level` WRITE;
/*!40000 ALTER TABLE `people_skill_level` DISABLE KEYS */;
/*!40000 ALTER TABLE `people_skill_level` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `people_skill_year`
--

DROP TABLE IF EXISTS `people_skill_year`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `people_skill_year` (
  `skill_year_id` int(11) NOT NULL auto_increment,
  `name` text,
  PRIMARY KEY  (`skill_year_id`)
)  AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `people_skill_year`
--

LOCK TABLES `people_skill_year` WRITE;
/*!40000 ALTER TABLE `people_skill_year` DISABLE KEYS */;
/*!40000 ALTER TABLE `people_skill_year` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `permission_type` varchar(255) NOT NULL,
  `object_id` varchar(255) NOT NULL,
  `ugroup_id` int(11) NOT NULL default '0',
  KEY `object_id` (`object_id`(10))
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions_values`
--

DROP TABLE IF EXISTS `permissions_values`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions_values` (
  `permission_type` text NOT NULL,
  `ugroup_id` int(11) NOT NULL default '0',
  `is_default` int(11) NOT NULL default '0'
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions_values`
--

LOCK TABLES `permissions_values` WRITE;
/*!40000 ALTER TABLE `permissions_values` DISABLE KEYS */;
INSERT INTO `permissions_values` VALUES ('PLUGIN_GIT_READ',2,1),('PLUGIN_GIT_READ',3,0),('PLUGIN_GIT_READ',4,0),('PLUGIN_GIT_WRITE',2,0),('PLUGIN_GIT_WRITE',3,1),('PLUGIN_GIT_WRITE',4,0),('PLUGIN_GIT_WPLUS',2,0),('PLUGIN_GIT_WPLUS',3,0),('PLUGIN_GIT_WPLUS',4,0);
/*!40000 ALTER TABLE `permissions_values` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin`
--

DROP TABLE IF EXISTS `plugin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(100) NOT NULL,
  `available` tinyint(4) NOT NULL default '0',
  `prj_restricted` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
)  AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin`
--

LOCK TABLES `plugin` WRITE;
/*!40000 ALTER TABLE `plugin` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_cvstodimensions_log`
--

DROP TABLE IF EXISTS `plugin_cvstodimensions_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_cvstodimensions_log` (
  `group_id` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `tag` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL default '0',
  `error` varchar(255) default NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_cvstodimensions_log`
--

LOCK TABLES `plugin_cvstodimensions_log` WRITE;
/*!40000 ALTER TABLE `plugin_cvstodimensions_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_cvstodimensions_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_cvstodimensions_modules`
--

DROP TABLE IF EXISTS `plugin_cvstodimensions_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_cvstodimensions_modules` (
  `group_id` int(11) NOT NULL default '0',
  `module` varchar(255) NOT NULL,
  `design_part` varchar(255) NOT NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_cvstodimensions_modules`
--

LOCK TABLES `plugin_cvstodimensions_modules` WRITE;
/*!40000 ALTER TABLE `plugin_cvstodimensions_modules` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_cvstodimensions_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_cvstodimensions_parameters`
--

DROP TABLE IF EXISTS `plugin_cvstodimensions_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_cvstodimensions_parameters` (
  `group_id` int(11) NOT NULL default '0',
  `product` varchar(255) NOT NULL,
  `dimensions_db` varchar(255) NOT NULL,
  `status` int(11) NOT NULL default '0'
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_cvstodimensions_parameters`
--

LOCK TABLES `plugin_cvstodimensions_parameters` WRITE;
/*!40000 ALTER TABLE `plugin_cvstodimensions_parameters` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_cvstodimensions_parameters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_approval`
--

DROP TABLE IF EXISTS `plugin_docman_approval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_approval` (
  `table_id` int(11) unsigned NOT NULL auto_increment,
  `item_id` int(11) unsigned default NULL,
  `version_id` int(11) unsigned default NULL,
  `wiki_version_id` int(11) unsigned default NULL,
  `table_owner` int(11) unsigned NOT NULL default '0',
  `date` int(11) unsigned default NULL,
  `description` text,
  `status` tinyint(4) NOT NULL default '0',
  `notification` tinyint(4) NOT NULL default '0',
  `auto_status` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`table_id`),
  UNIQUE KEY `item_id` (`item_id`,`wiki_version_id`),
  KEY `idx_owner` (`table_owner`,`table_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_approval`
--

LOCK TABLES `plugin_docman_approval` WRITE;
/*!40000 ALTER TABLE `plugin_docman_approval` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_approval` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_approval_user`
--

DROP TABLE IF EXISTS `plugin_docman_approval_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_approval_user` (
  `table_id` int(11) unsigned NOT NULL,
  `reviewer_id` int(11) unsigned NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  `date` int(11) unsigned default NULL,
  `state` tinyint(4) NOT NULL default '0',
  `comment` text,
  `version` int(11) unsigned default NULL,
  PRIMARY KEY  (`table_id`,`reviewer_id`),
  KEY `rank` (`rank`),
  KEY `idx_review` (`reviewer_id`,`table_id`),
  KEY `idx_reviewer` (`reviewer_id`,`table_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_approval_user`
--

LOCK TABLES `plugin_docman_approval_user` WRITE;
/*!40000 ALTER TABLE `plugin_docman_approval_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_approval_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_item`
--

DROP TABLE IF EXISTS `plugin_docman_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_item` (
  `item_id` int(11) unsigned NOT NULL auto_increment,
  `parent_id` int(11) unsigned default NULL,
  `group_id` int(11) unsigned default NULL,
  `title` text,
  `description` text,
  `create_date` int(11) unsigned default NULL,
  `update_date` int(11) unsigned default NULL,
  `delete_date` int(11) unsigned default NULL,
  `user_id` int(11) unsigned default NULL,
  `status` tinyint(4) NOT NULL default '100',
  `obsolescence_date` int(11) NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  `item_type` int(11) unsigned default NULL,
  `link_url` text,
  `wiki_page` text,
  `file_is_embedded` int(11) unsigned default NULL,
  PRIMARY KEY  (`item_id`),
  KEY `idx_group_id` (`group_id`),
  KEY `parent_id` (`parent_id`),
  KEY `rank` (`rank`),
  FULLTEXT KEY `fltxt_title` (`title`),
  FULLTEXT KEY `fltxt_description` (`description`),
  FULLTEXT KEY `fltxt` (`title`,`description`)
)  AUTO_INCREMENT=32486 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_item`
--

LOCK TABLES `plugin_docman_item` WRITE;
/*!40000 ALTER TABLE `plugin_docman_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_item_deleted`
--

DROP TABLE IF EXISTS `plugin_docman_item_deleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_item_deleted` (
  `item_id` int(11) unsigned NOT NULL,
  `parent_id` int(11) unsigned default NULL,
  `group_id` int(11) unsigned default NULL,
  `title` text,
  `description` text,
  `create_date` int(11) unsigned default NULL,
  `update_date` int(11) unsigned default NULL,
  `delete_date` int(11) unsigned default NULL,
  `purge_date` int(11) unsigned default NULL,
  `user_id` int(11) unsigned default NULL,
  `status` tinyint(4) NOT NULL default '100',
  `obsolescence_date` int(11) NOT NULL default '0',
  `rank` int(11) NOT NULL default '0',
  `item_type` int(11) unsigned default NULL,
  `link_url` text,
  `wiki_page` text,
  `file_is_embedded` int(11) unsigned default NULL,
  PRIMARY KEY  (`item_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_item_deleted`
--

LOCK TABLES `plugin_docman_item_deleted` WRITE;
/*!40000 ALTER TABLE `plugin_docman_item_deleted` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_item_deleted` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_item_lock`
--

DROP TABLE IF EXISTS `plugin_docman_item_lock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_item_lock` (
  `item_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `lock_date` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`item_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_item_lock`
--

LOCK TABLES `plugin_docman_item_lock` WRITE;
/*!40000 ALTER TABLE `plugin_docman_item_lock` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_item_lock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_log`
--

DROP TABLE IF EXISTS `plugin_docman_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_log` (
  `time` int(11) unsigned NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `item_id` int(11) unsigned NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  `old_value` text,
  `new_value` text,
  `field` text,
  KEY `time` (`time`),
  KEY `item_id` (`item_id`),
  KEY `group_id` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_log`
--

LOCK TABLES `plugin_docman_log` WRITE;
/*!40000 ALTER TABLE `plugin_docman_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_metadata`
--

DROP TABLE IF EXISTS `plugin_docman_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_metadata` (
  `field_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `data_type` int(11) NOT NULL default '0',
  `label` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `required` int(11) NOT NULL default '0',
  `empty_ok` int(11) NOT NULL default '0',
  `mul_val_ok` tinyint(4) NOT NULL default '0',
  `special` int(11) NOT NULL default '0',
  `use_it` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`field_id`),
  KEY `idx_name` (`name`(10)),
  KEY `idx_group_id` (`group_id`,`use_it`)
)  AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_metadata`
--

LOCK TABLES `plugin_docman_metadata` WRITE;
/*!40000 ALTER TABLE `plugin_docman_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_metadata_love`
--

DROP TABLE IF EXISTS `plugin_docman_metadata_love`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_metadata_love` (
  `value_id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `rank` int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (`value_id`),
  KEY `idx_fv_status` (`status`),
  KEY `rank` (`rank`),
  KEY `name` (`name`(10))
)  AUTO_INCREMENT=108 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_metadata_love`
--

LOCK TABLES `plugin_docman_metadata_love` WRITE;
/*!40000 ALTER TABLE `plugin_docman_metadata_love` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_metadata_love` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_metadata_love_md`
--

DROP TABLE IF EXISTS `plugin_docman_metadata_love_md`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_metadata_love_md` (
  `field_id` int(11) NOT NULL default '0',
  `value_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`field_id`,`value_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_metadata_love_md`
--

LOCK TABLES `plugin_docman_metadata_love_md` WRITE;
/*!40000 ALTER TABLE `plugin_docman_metadata_love_md` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_metadata_love_md` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_metadata_value`
--

DROP TABLE IF EXISTS `plugin_docman_metadata_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_metadata_value` (
  `field_id` int(11) NOT NULL default '0',
  `item_id` int(11) NOT NULL default '0',
  `valueInt` int(11) default NULL,
  `valueText` text,
  `valueDate` int(11) default NULL,
  `valueString` text,
  KEY `idx_field_item_id` (`field_id`,`item_id`),
  FULLTEXT KEY `fltxt` (`valueText`,`valueString`),
  FULLTEXT KEY `fltxt_txt` (`valueText`),
  FULLTEXT KEY `fltxt_str` (`valueString`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_metadata_value`
--

LOCK TABLES `plugin_docman_metadata_value` WRITE;
/*!40000 ALTER TABLE `plugin_docman_metadata_value` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_metadata_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_project_settings`
--

DROP TABLE IF EXISTS `plugin_docman_project_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_project_settings` (
  `group_id` int(11) NOT NULL default '0',
  `view` varchar(255) default NULL,
  `use_obsolescence_date` tinyint(4) NOT NULL default '0',
  `use_status` tinyint(4) NOT NULL default '0',
  KEY `group_id` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_project_settings`
--

LOCK TABLES `plugin_docman_project_settings` WRITE;
/*!40000 ALTER TABLE `plugin_docman_project_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_project_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_report`
--

DROP TABLE IF EXISTS `plugin_docman_report`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_report` (
  `report_id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `title` varchar(255) default NULL,
  `group_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '100',
  `item_id` int(11) default NULL,
  `scope` char(1) NOT NULL default 'I',
  `is_default` tinyint(1) NOT NULL default '0',
  `advanced_search` tinyint(1) NOT NULL default '0',
  `description` text,
  `image` int(11) default NULL,
  PRIMARY KEY  (`report_id`),
  KEY `group_idx` (`group_id`),
  KEY `user_idx` (`user_id`)
)  AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_report`
--

LOCK TABLES `plugin_docman_report` WRITE;
/*!40000 ALTER TABLE `plugin_docman_report` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_report` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_report_filter`
--

DROP TABLE IF EXISTS `plugin_docman_report_filter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_report_filter` (
  `report_id` int(11) NOT NULL default '0',
  `label` varchar(255) NOT NULL,
  `value_love` int(11) default NULL,
  `value_string` varchar(255) default NULL,
  `value_date1` varchar(32) default NULL,
  `value_date2` varchar(32) default NULL,
  `value_date_op` tinyint(2) default NULL,
  KEY `report_label_idx` (`report_id`,`label`(10))
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_report_filter`
--

LOCK TABLES `plugin_docman_report_filter` WRITE;
/*!40000 ALTER TABLE `plugin_docman_report_filter` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_report_filter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_tokens`
--

DROP TABLE IF EXISTS `plugin_docman_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_tokens` (
  `user_id` int(11) NOT NULL default '0',
  `token` varchar(32) NOT NULL,
  `url` text NOT NULL,
  `created_at` datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`user_id`,`token`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_tokens`
--

LOCK TABLES `plugin_docman_tokens` WRITE;
/*!40000 ALTER TABLE `plugin_docman_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_version`
--

DROP TABLE IF EXISTS `plugin_docman_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_version` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `item_id` int(11) unsigned default NULL,
  `number` int(11) unsigned default NULL,
  `user_id` int(11) unsigned default NULL,
  `label` text,
  `changelog` text,
  `date` int(11) unsigned default NULL,
  `filename` text,
  `filesize` int(11) unsigned default NULL,
  `filetype` text,
  `path` text,
  PRIMARY KEY  (`id`),
  KEY `item_id` (`item_id`),
  KEY `idx_item_id` (`item_id`),
  FULLTEXT KEY `fltxt` (`label`,`changelog`,`filename`)
)  AUTO_INCREMENT=23476 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_version`
--

LOCK TABLES `plugin_docman_version` WRITE;
/*!40000 ALTER TABLE `plugin_docman_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_version_deleted`
--

DROP TABLE IF EXISTS `plugin_docman_version_deleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_version_deleted` (
  `id` int(11) unsigned NOT NULL,
  `item_id` int(11) unsigned default NULL,
  `number` int(11) unsigned default NULL,
  `user_id` int(11) unsigned default NULL,
  `label` text,
  `changelog` text,
  `create_date` int(11) unsigned default NULL,
  `delete_date` int(11) unsigned default NULL,
  `purge_date` int(11) unsigned default NULL,
  `filename` text,
  `filesize` int(11) unsigned default NULL,
  `filetype` text,
  `path` text,
  PRIMARY KEY  (`id`),
  KEY `item_id` (`item_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_version_deleted`
--

LOCK TABLES `plugin_docman_version_deleted` WRITE;
/*!40000 ALTER TABLE `plugin_docman_version_deleted` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_version_deleted` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_docman_widget_embedded`
--

DROP TABLE IF EXISTS `plugin_docman_widget_embedded`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_docman_widget_embedded` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `owner_id` int(11) unsigned NOT NULL,
  `owner_type` varchar(1) NOT NULL,
  `title` varchar(255) NOT NULL,
  `item_id` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `owner_id` (`owner_id`,`owner_type`)
)  AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_docman_widget_embedded`
--

LOCK TABLES `plugin_docman_widget_embedded` WRITE;
/*!40000 ALTER TABLE `plugin_docman_widget_embedded` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_docman_widget_embedded` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_git_log`
--

DROP TABLE IF EXISTS `plugin_git_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_git_log` (
  `repository_id` int(10) unsigned NOT NULL,
  `user_id` int(11) unsigned default NULL,
  `push_date` datetime NOT NULL,
  `commits_number` int(11) default NULL,
  KEY `idx_repository_user` (`repository_id`,`user_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_git_log`
--

LOCK TABLES `plugin_git_log` WRITE;
/*!40000 ALTER TABLE `plugin_git_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_git_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_git_post_receive_mail`
--

DROP TABLE IF EXISTS `plugin_git_post_receive_mail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_git_post_receive_mail` (
  `recipient_mail` varchar(255) NOT NULL,
  `repository_id` int(10) NOT NULL,
  KEY `repository_id` (`repository_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_git_post_receive_mail`
--

LOCK TABLES `plugin_git_post_receive_mail` WRITE;
/*!40000 ALTER TABLE `plugin_git_post_receive_mail` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_git_post_receive_mail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_graphontrackers_bar_chart`
--

DROP TABLE IF EXISTS `plugin_graphontrackers_bar_chart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_graphontrackers_bar_chart` (
  `id` int(11) NOT NULL,
  `field_base` varchar(255) default NULL,
  `field_group` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_graphontrackers_bar_chart`
--

LOCK TABLES `plugin_graphontrackers_bar_chart` WRITE;
/*!40000 ALTER TABLE `plugin_graphontrackers_bar_chart` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_graphontrackers_bar_chart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_graphontrackers_chart`
--

DROP TABLE IF EXISTS `plugin_graphontrackers_chart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_graphontrackers_chart` (
  `id` int(11) NOT NULL auto_increment,
  `report_graphic_id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `chart_type` varchar(255) default NULL,
  `title` varchar(255) default NULL,
  `description` text,
  `width` int(11) default '600',
  `height` int(11) default '400',
  PRIMARY KEY  (`id`),
  KEY `report_graphic_id` (`report_graphic_id`),
  KEY `chart_type` (`chart_type`)
)  AUTO_INCREMENT=22947 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_graphontrackers_chart`
--

LOCK TABLES `plugin_graphontrackers_chart` WRITE;
/*!40000 ALTER TABLE `plugin_graphontrackers_chart` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_graphontrackers_chart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_graphontrackers_gantt_chart`
--

DROP TABLE IF EXISTS `plugin_graphontrackers_gantt_chart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_graphontrackers_gantt_chart` (
  `id` int(11) NOT NULL,
  `field_start` varchar(255) default NULL,
  `field_due` varchar(255) default NULL,
  `field_finish` varchar(255) default NULL,
  `field_percentage` varchar(255) default NULL,
  `field_righttext` varchar(255) default NULL,
  `scale` varchar(20) default NULL,
  `as_of_date` int(11) default NULL,
  `summary` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_graphontrackers_gantt_chart`
--

LOCK TABLES `plugin_graphontrackers_gantt_chart` WRITE;
/*!40000 ALTER TABLE `plugin_graphontrackers_gantt_chart` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_graphontrackers_gantt_chart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_graphontrackers_pie_chart`
--

DROP TABLE IF EXISTS `plugin_graphontrackers_pie_chart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_graphontrackers_pie_chart` (
  `id` int(11) NOT NULL,
  `field_base` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_graphontrackers_pie_chart`
--

LOCK TABLES `plugin_graphontrackers_pie_chart` WRITE;
/*!40000 ALTER TABLE `plugin_graphontrackers_pie_chart` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_graphontrackers_pie_chart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_graphontrackers_report_graphic`
--

DROP TABLE IF EXISTS `plugin_graphontrackers_report_graphic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_graphontrackers_report_graphic` (
  `report_graphic_id` int(11) NOT NULL auto_increment,
  `group_artifact_id` int(11) default NULL,
  `user_id` int(11) default NULL,
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `scope` char(1) default NULL,
  PRIMARY KEY  (`report_graphic_id`)
)  AUTO_INCREMENT=9180 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_graphontrackers_report_graphic`
--

LOCK TABLES `plugin_graphontrackers_report_graphic` WRITE;
/*!40000 ALTER TABLE `plugin_graphontrackers_report_graphic` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_graphontrackers_report_graphic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_hudson_job`
--

DROP TABLE IF EXISTS `plugin_hudson_job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_hudson_job` (
  `job_id` int(11) unsigned NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `job_url` varchar(255) NOT NULL,
  `name` varchar(128) NOT NULL,
  `use_svn_trigger` tinyint(4) NOT NULL default '0',
  `use_cvs_trigger` tinyint(4) NOT NULL default '0',
  `token` varchar(128) NOT NULL,
  PRIMARY KEY  (`job_id`)
)  AUTO_INCREMENT=62 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_hudson_job`
--

LOCK TABLES `plugin_hudson_job` WRITE;
/*!40000 ALTER TABLE `plugin_hudson_job` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_hudson_job` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_hudson_widget`
--

DROP TABLE IF EXISTS `plugin_hudson_widget`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_hudson_widget` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `widget_name` varchar(64) NOT NULL,
  `owner_id` int(11) unsigned NOT NULL,
  `owner_type` varchar(1) NOT NULL,
  `job_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_hudson_widget`
--

LOCK TABLES `plugin_hudson_widget` WRITE;
/*!40000 ALTER TABLE `plugin_hudson_widget` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_hudson_widget` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_salome_activatedplugins`
--

DROP TABLE IF EXISTS `plugin_salome_activatedplugins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_salome_activatedplugins` (
  `group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`group_id`,`name`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_salome_activatedplugins`
--

LOCK TABLES `plugin_salome_activatedplugins` WRITE;
/*!40000 ALTER TABLE `plugin_salome_activatedplugins` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_salome_activatedplugins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_salome_configuration`
--

DROP TABLE IF EXISTS `plugin_salome_configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_salome_configuration` (
  `group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` tinyint(1) NOT NULL,
  PRIMARY KEY  (`group_id`,`name`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_salome_configuration`
--

LOCK TABLES `plugin_salome_configuration` WRITE;
/*!40000 ALTER TABLE `plugin_salome_configuration` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_salome_configuration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_salome_proxy`
--

DROP TABLE IF EXISTS `plugin_salome_proxy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_salome_proxy` (
  `user_id` int(11) NOT NULL,
  `proxy` varchar(128) NOT NULL,
  `proxy_user` varchar(128) NOT NULL,
  `proxy_password` varchar(128) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY  (`user_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_salome_proxy`
--

LOCK TABLES `plugin_salome_proxy` WRITE;
/*!40000 ALTER TABLE `plugin_salome_proxy` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_salome_proxy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_serverupdate_upgrade`
--

DROP TABLE IF EXISTS `plugin_serverupdate_upgrade`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_serverupdate_upgrade` (
  `date` int(11) unsigned NOT NULL default '0',
  `script` varchar(64) NOT NULL,
  `execution_mode` varchar(32) NOT NULL,
  `success` tinyint(4) NOT NULL default '0',
  `error` text NOT NULL,
  PRIMARY KEY  (`date`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_serverupdate_upgrade`
--

LOCK TABLES `plugin_serverupdate_upgrade` WRITE;
/*!40000 ALTER TABLE `plugin_serverupdate_upgrade` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_serverupdate_upgrade` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_svntodimensions_log`
--

DROP TABLE IF EXISTS `plugin_svntodimensions_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_svntodimensions_log` (
  `log_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `tag` varchar(255) NOT NULL,
  `design_part` varchar(255) default NULL,
  `user_id` int(11) NOT NULL,
  `state` int(11) NOT NULL,
  `error` varchar(255) default NULL,
  PRIMARY KEY  (`log_id`)
)  AUTO_INCREMENT=215 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_svntodimensions_log`
--

LOCK TABLES `plugin_svntodimensions_log` WRITE;
/*!40000 ALTER TABLE `plugin_svntodimensions_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_svntodimensions_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_svntodimensions_parameters`
--

DROP TABLE IF EXISTS `plugin_svntodimensions_parameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_svntodimensions_parameters` (
  `group_id` int(11) NOT NULL,
  `product` varchar(255) NOT NULL,
  `dimensions_db` varchar(255) NOT NULL,
  `status` int(11) NOT NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_svntodimensions_parameters`
--

LOCK TABLES `plugin_svntodimensions_parameters` WRITE;
/*!40000 ALTER TABLE `plugin_svntodimensions_parameters` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_svntodimensions_parameters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plugin_userlog_request`
--

DROP TABLE IF EXISTS `plugin_userlog_request`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plugin_userlog_request` (
  `time` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `session_hash` char(32) NOT NULL default '',
  `http_user_agent` varchar(255) NOT NULL default '',
  `http_request_uri` varchar(255) NOT NULL default '',
  `http_request_method` varchar(4) NOT NULL default '',
  `http_remote_addr` varchar(16) NOT NULL default '',
  `http_referer` varchar(255) NOT NULL default '',
  KEY `idx_time` (`time`),
  KEY `idx_group_id` (`group_id`),
  KEY `idx_user_id` (`user_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plugin_userlog_request`
--

LOCK TABLES `plugin_userlog_request` WRITE;
/*!40000 ALTER TABLE `plugin_userlog_request` DISABLE KEYS */;
/*!40000 ALTER TABLE `plugin_userlog_request` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `priority_plugin_hook`
--

DROP TABLE IF EXISTS `priority_plugin_hook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `priority_plugin_hook` (
  `plugin_id` int(11) NOT NULL default '0',
  `hook` varchar(100) NOT NULL,
  `priority` int(11) NOT NULL default '0'
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `priority_plugin_hook`
--

LOCK TABLES `priority_plugin_hook` WRITE;
/*!40000 ALTER TABLE `priority_plugin_hook` DISABLE KEYS */;
/*!40000 ALTER TABLE `priority_plugin_hook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_assigned_to`
--

DROP TABLE IF EXISTS `project_assigned_to`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_assigned_to` (
  `project_assigned_id` int(11) NOT NULL auto_increment,
  `project_task_id` int(11) NOT NULL default '0',
  `assigned_to_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`project_assigned_id`),
  KEY `idx_project_assigned_to_task_id` (`project_task_id`),
  KEY `idx_project_assigned_to_assigned_to` (`assigned_to_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_assigned_to`
--

LOCK TABLES `project_assigned_to` WRITE;
/*!40000 ALTER TABLE `project_assigned_to` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_assigned_to` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_cc`
--

DROP TABLE IF EXISTS `project_cc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_cc` (
  `project_cc_id` int(11) NOT NULL auto_increment,
  `project_task_id` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL,
  `added_by` int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`project_cc_id`),
  KEY `project_id_idx` (`project_task_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_cc`
--

LOCK TABLES `project_cc` WRITE;
/*!40000 ALTER TABLE `project_cc` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_cc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_counts_tmp`
--

DROP TABLE IF EXISTS `project_counts_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_counts_tmp` (
  `group_id` int(11) default NULL,
  `type` text,
  `count` float(8,5) default NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_counts_tmp`
--

LOCK TABLES `project_counts_tmp` WRITE;
/*!40000 ALTER TABLE `project_counts_tmp` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_counts_tmp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_counts_weekly_tmp`
--

DROP TABLE IF EXISTS `project_counts_weekly_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_counts_weekly_tmp` (
  `group_id` int(11) default NULL,
  `type` text,
  `count` float(8,5) default NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_counts_weekly_tmp`
--

LOCK TABLES `project_counts_weekly_tmp` WRITE;
/*!40000 ALTER TABLE `project_counts_weekly_tmp` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_counts_weekly_tmp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_dependencies`
--

DROP TABLE IF EXISTS `project_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_dependencies` (
  `project_depend_id` int(11) NOT NULL auto_increment,
  `project_task_id` int(11) NOT NULL default '0',
  `is_dependent_on_task_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`project_depend_id`),
  KEY `idx_project_dependencies_task_id` (`project_task_id`),
  KEY `idx_project_is_dependent_on_task_id` (`is_dependent_on_task_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_dependencies`
--

LOCK TABLES `project_dependencies` WRITE;
/*!40000 ALTER TABLE `project_dependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_dependencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_field`
--

DROP TABLE IF EXISTS `project_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_field` (
  `project_field_id` int(11) NOT NULL auto_increment,
  `field_name` varchar(255) NOT NULL,
  `display_type` varchar(255) NOT NULL,
  `display_size` varchar(255) NOT NULL,
  `label` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `scope` char(1) NOT NULL,
  `required` int(11) NOT NULL default '0',
  `empty_ok` int(11) NOT NULL default '0',
  `keep_history` int(11) NOT NULL default '0',
  `special` int(11) NOT NULL default '0',
  `custom` int(11) NOT NULL default '0',
  PRIMARY KEY  (`project_field_id`),
  KEY `idx_project_field_name` (`field_name`)
)  AUTO_INCREMENT=104 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_field`
--

LOCK TABLES `project_field` WRITE;
/*!40000 ALTER TABLE `project_field` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_field` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_field_usage`
--

DROP TABLE IF EXISTS `project_field_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_field_usage` (
  `project_field_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `use_it` int(11) NOT NULL default '0',
  `show_on_add` int(11) NOT NULL default '0',
  `show_on_add_members` int(11) NOT NULL default '0',
  `place` int(11) default NULL,
  `custom_label` varchar(255) default NULL,
  `custom_description` varchar(255) default NULL,
  `custom_display_size` varchar(255) default NULL,
  `custom_empty_ok` int(11) default NULL,
  `custom_keep_history` int(11) default NULL,
  KEY `idx_project_fu_field_id` (`project_field_id`),
  KEY `idx_project_fu_group_id` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_field_usage`
--

LOCK TABLES `project_field_usage` WRITE;
/*!40000 ALTER TABLE `project_field_usage` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_field_usage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_field_value`
--

DROP TABLE IF EXISTS `project_field_value`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_field_value` (
  `project_fv_id` int(11) NOT NULL auto_increment,
  `project_field_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `value_id` int(11) NOT NULL default '0',
  `value` text NOT NULL,
  `description` text NOT NULL,
  `order_id` int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (`project_fv_id`),
  KEY `idx_project_fv_field_id` (`project_fv_id`),
  KEY `idx_project_fv_group_id` (`group_id`),
  KEY `idx_project_fv_value_id` (`value_id`),
  KEY `idx_project_fv_status` (`status`)
)  AUTO_INCREMENT=135 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_field_value`
--

LOCK TABLES `project_field_value` WRITE;
/*!40000 ALTER TABLE `project_field_value` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_field_value` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_file`
--

DROP TABLE IF EXISTS `project_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_file` (
  `project_file_id` int(11) NOT NULL auto_increment,
  `project_task_id` int(11) NOT NULL default '0',
  `submitted_by` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `description` text NOT NULL,
  `file` longblob NOT NULL,
  `filename` text NOT NULL,
  `filesize` int(11) NOT NULL default '0',
  `filetype` text NOT NULL,
  PRIMARY KEY  (`project_file_id`),
  KEY `project_task_id_idx` (`project_task_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_file`
--

LOCK TABLES `project_file` WRITE;
/*!40000 ALTER TABLE `project_file` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_group_list`
--

DROP TABLE IF EXISTS `project_group_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_group_list` (
  `group_project_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `project_name` text NOT NULL,
  `is_public` int(11) NOT NULL default '0',
  `description` text,
  `order_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`group_project_id`),
  KEY `idx_project_group_list_group_id` (`group_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_group_list`
--

LOCK TABLES `project_group_list` WRITE;
/*!40000 ALTER TABLE `project_group_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_group_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_history`
--

DROP TABLE IF EXISTS `project_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_history` (
  `project_history_id` int(11) NOT NULL auto_increment,
  `project_task_id` int(11) NOT NULL default '0',
  `field_name` text NOT NULL,
  `old_value` text NOT NULL,
  `mod_by` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`project_history_id`),
  KEY `idx_project_history_task_id` (`project_task_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_history`
--

LOCK TABLES `project_history` WRITE;
/*!40000 ALTER TABLE `project_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_metric`
--

DROP TABLE IF EXISTS `project_metric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_metric` (
  `ranking` int(11) NOT NULL auto_increment,
  `percentile` float(8,2) default NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY  (`ranking`),
  KEY `idx_project_metric_group` (`group_id`)
)  AUTO_INCREMENT=2990 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_metric`
--

LOCK TABLES `project_metric` WRITE;
/*!40000 ALTER TABLE `project_metric` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_metric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_metric_tmp1`
--

DROP TABLE IF EXISTS `project_metric_tmp1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_metric_tmp1` (
  `ranking` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `value` float(8,5) default NULL,
  PRIMARY KEY  (`ranking`)
)  AUTO_INCREMENT=2990 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_metric_tmp1`
--

LOCK TABLES `project_metric_tmp1` WRITE;
/*!40000 ALTER TABLE `project_metric_tmp1` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_metric_tmp1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_metric_weekly_tmp1`
--

DROP TABLE IF EXISTS `project_metric_weekly_tmp1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_metric_weekly_tmp1` (
  `ranking` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `value` float(8,5) default NULL,
  PRIMARY KEY  (`ranking`)
)  AUTO_INCREMENT=1068 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_metric_weekly_tmp1`
--

LOCK TABLES `project_metric_weekly_tmp1` WRITE;
/*!40000 ALTER TABLE `project_metric_weekly_tmp1` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_metric_weekly_tmp1` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_notification`
--

DROP TABLE IF EXISTS `project_notification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_notification` (
  `user_id` int(11) NOT NULL default '0',
  `role_id` int(11) NOT NULL default '0',
  `event_id` int(11) NOT NULL default '0',
  `notify` int(11) NOT NULL default '1',
  KEY `user_id_idx` (`user_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_notification`
--

LOCK TABLES `project_notification` WRITE;
/*!40000 ALTER TABLE `project_notification` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_notification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_notification_event`
--

DROP TABLE IF EXISTS `project_notification_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_notification_event` (
  `event_id` int(11) NOT NULL default '0',
  `event_label` varchar(255) default NULL,
  `short_description` varchar(40) default NULL,
  `description` varchar(255) default NULL,
  `rank` int(11) NOT NULL default '0',
  KEY `event_id_idx` (`event_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_notification_event`
--

LOCK TABLES `project_notification_event` WRITE;
/*!40000 ALTER TABLE `project_notification_event` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_notification_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_notification_role`
--

DROP TABLE IF EXISTS `project_notification_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_notification_role` (
  `role_id` int(11) NOT NULL default '0',
  `role_label` varchar(255) default NULL,
  `short_description` varchar(40) default NULL,
  `description` varchar(255) default NULL,
  `rank` int(11) NOT NULL default '0',
  KEY `role_id_idx` (`role_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_notification_role`
--

LOCK TABLES `project_notification_role` WRITE;
/*!40000 ALTER TABLE `project_notification_role` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_notification_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_plugin`
--

DROP TABLE IF EXISTS `project_plugin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_plugin` (
  `project_id` int(11) NOT NULL default '0',
  `plugin_id` int(11) NOT NULL default '0',
  UNIQUE KEY `project_plugin` (`project_id`,`plugin_id`),
  KEY `project_id_idx` (`project_id`),
  KEY `plugin_id_idx` (`plugin_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_plugin`
--

LOCK TABLES `project_plugin` WRITE;
/*!40000 ALTER TABLE `project_plugin` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_plugin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_status`
--

DROP TABLE IF EXISTS `project_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_status` (
  `status_id` int(11) NOT NULL auto_increment,
  `status_name` text NOT NULL,
  PRIMARY KEY  (`status_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_status`
--

LOCK TABLES `project_status` WRITE;
/*!40000 ALTER TABLE `project_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_task`
--

DROP TABLE IF EXISTS `project_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_task` (
  `project_task_id` int(11) NOT NULL auto_increment,
  `group_project_id` int(11) NOT NULL default '0',
  `summary` text NOT NULL,
  `details` text NOT NULL,
  `percent_complete` int(11) NOT NULL default '0',
  `priority` int(11) NOT NULL default '0',
  `hours` float(10,2) NOT NULL default '0.00',
  `start_date` int(11) NOT NULL default '0',
  `end_date` int(11) NOT NULL default '0',
  `created_by` int(11) NOT NULL default '0',
  `status_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`project_task_id`),
  KEY `idx_project_task_group_project_id` (`group_project_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_task`
--

LOCK TABLES `project_task` WRITE;
/*!40000 ALTER TABLE `project_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_watcher`
--

DROP TABLE IF EXISTS `project_watcher`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_watcher` (
  `user_id` int(11) NOT NULL default '0',
  `watchee_id` int(11) NOT NULL default '0',
  KEY `user_id_idx` (`user_id`),
  KEY `watchee_id_idx` (`watchee_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_watcher`
--

LOCK TABLES `project_watcher` WRITE;
/*!40000 ALTER TABLE `project_watcher` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_watcher` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_weekly_metric`
--

DROP TABLE IF EXISTS `project_weekly_metric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_weekly_metric` (
  `ranking` int(11) NOT NULL auto_increment,
  `percentile` float(8,2) default NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY  (`ranking`),
  KEY `idx_project_metric_weekly_group` (`group_id`)
)  AUTO_INCREMENT=1068 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_weekly_metric`
--

LOCK TABLES `project_weekly_metric` WRITE;
/*!40000 ALTER TABLE `project_weekly_metric` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_weekly_metric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reference`
--

DROP TABLE IF EXISTS `reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reference` (
  `id` int(11) NOT NULL auto_increment,
  `keyword` varchar(25) NOT NULL,
  `description` text NOT NULL,
  `link` text NOT NULL,
  `scope` char(1) NOT NULL default 'P',
  `service_short_name` text,
  `nature` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `keyword_idx` (`keyword`),
  KEY `scope_idx` (`scope`)
)  AUTO_INCREMENT=9592 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reference`
--

LOCK TABLES `reference` WRITE;
/*!40000 ALTER TABLE `reference` DISABLE KEYS */;
/*!40000 ALTER TABLE `reference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reference_group`
--

DROP TABLE IF EXISTS `reference_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reference_group` (
  `id` int(11) NOT NULL auto_increment,
  `reference_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `is_active` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `group_id_idx` (`group_id`,`is_active`)
)  AUTO_INCREMENT=74323 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reference_group`
--

LOCK TABLES `reference_group` WRITE;
/*!40000 ALTER TABLE `reference_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `reference_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `server`
--

DROP TABLE IF EXISTS `server`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `server` (
  `id` int(11) unsigned NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `http` text NOT NULL,
  `https` text NOT NULL,
  `is_master` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `server`
--

LOCK TABLES `server` WRITE;
/*!40000 ALTER TABLE `server` DISABLE KEYS */;
/*!40000 ALTER TABLE `server` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `service`
--

DROP TABLE IF EXISTS `service`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `service` (
  `service_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `label` text,
  `description` text,
  `short_name` text,
  `link` text,
  `is_active` int(11) NOT NULL default '0',
  `is_used` int(11) NOT NULL default '0',
  `scope` text NOT NULL,
  `rank` int(11) NOT NULL default '0',
  `location` enum('master','same','satellite') NOT NULL default 'master',
  `server_id` int(11) unsigned default NULL,
  `is_in_iframe` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`service_id`),
  KEY `idx_group_id` (`group_id`)
)  AUTO_INCREMENT=59732 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service`
--

LOCK TABLES `service` WRITE;
/*!40000 ALTER TABLE `service` DISABLE KEYS */;
/*!40000 ALTER TABLE `service` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `session`
--

DROP TABLE IF EXISTS `session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `session` (
  `user_id` int(11) NOT NULL default '0',
  `session_hash` char(32) NOT NULL,
  `ip_addr` char(15) NOT NULL,
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`session_hash`),
  KEY `idx_session_user_id` (`user_id`),
  KEY `time_idx` (`time`),
  KEY `idx_session_time` (`time`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `session`
--

LOCK TABLES `session` WRITE;
/*!40000 ALTER TABLE `session` DISABLE KEYS */;
/*!40000 ALTER TABLE `session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet`
--

DROP TABLE IF EXISTS `snippet`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet` (
  `snippet_id` int(11) NOT NULL auto_increment,
  `created_by` int(11) NOT NULL default '0',
  `name` text,
  `description` text,
  `type` int(11) NOT NULL default '0',
  `language` int(11) NOT NULL default '0',
  `license` text NOT NULL,
  `category` int(11) NOT NULL default '0',
  PRIMARY KEY  (`snippet_id`),
  KEY `idx_snippet_language` (`language`),
  KEY `idx_snippet_category` (`category`)
)  AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet`
--

LOCK TABLES `snippet` WRITE;
/*!40000 ALTER TABLE `snippet` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_category`
--

DROP TABLE IF EXISTS `snippet_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_category` (
  `category_id` int(11) NOT NULL default '0',
  `category_name` varchar(255) NOT NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_category`
--

LOCK TABLES `snippet_category` WRITE;
/*!40000 ALTER TABLE `snippet_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_language`
--

DROP TABLE IF EXISTS `snippet_language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_language` (
  `language_id` int(11) NOT NULL default '0',
  `language_name` varchar(255) NOT NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_language`
--

LOCK TABLES `snippet_language` WRITE;
/*!40000 ALTER TABLE `snippet_language` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_license`
--

DROP TABLE IF EXISTS `snippet_license`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_license` (
  `license_id` int(11) NOT NULL default '0',
  `license_name` varchar(255) NOT NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_license`
--

LOCK TABLES `snippet_license` WRITE;
/*!40000 ALTER TABLE `snippet_license` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_license` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_package`
--

DROP TABLE IF EXISTS `snippet_package`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_package` (
  `snippet_package_id` int(11) NOT NULL auto_increment,
  `created_by` int(11) NOT NULL default '0',
  `name` text,
  `description` text,
  `category` int(11) NOT NULL default '0',
  `language` int(11) NOT NULL default '0',
  PRIMARY KEY  (`snippet_package_id`),
  KEY `idx_snippet_package_language` (`language`),
  KEY `idx_snippet_package_category` (`category`)
)  AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_package`
--

LOCK TABLES `snippet_package` WRITE;
/*!40000 ALTER TABLE `snippet_package` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_package` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_package_item`
--

DROP TABLE IF EXISTS `snippet_package_item`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_package_item` (
  `snippet_package_item_id` int(11) NOT NULL auto_increment,
  `snippet_package_version_id` int(11) NOT NULL default '0',
  `snippet_version_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`snippet_package_item_id`),
  KEY `idx_snippet_package_item_pkg_ver` (`snippet_package_version_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_package_item`
--

LOCK TABLES `snippet_package_item` WRITE;
/*!40000 ALTER TABLE `snippet_package_item` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_package_item` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_package_version`
--

DROP TABLE IF EXISTS `snippet_package_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_package_version` (
  `snippet_package_version_id` int(11) NOT NULL auto_increment,
  `snippet_package_id` int(11) NOT NULL default '0',
  `changes` text,
  `version` text,
  `submitted_by` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`snippet_package_version_id`),
  KEY `idx_snippet_package_version_pkg_id` (`snippet_package_id`)
)  AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_package_version`
--

LOCK TABLES `snippet_package_version` WRITE;
/*!40000 ALTER TABLE `snippet_package_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_package_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_type`
--

DROP TABLE IF EXISTS `snippet_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_type` (
  `type_id` int(11) NOT NULL default '0',
  `type_name` varchar(255) NOT NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_type`
--

LOCK TABLES `snippet_type` WRITE;
/*!40000 ALTER TABLE `snippet_type` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `snippet_version`
--

DROP TABLE IF EXISTS `snippet_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippet_version` (
  `snippet_version_id` int(11) NOT NULL auto_increment,
  `snippet_id` int(11) NOT NULL default '0',
  `changes` text,
  `version` text,
  `submitted_by` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `code` longblob,
  `filename` varchar(255) NOT NULL,
  `filesize` varchar(50) NOT NULL,
  `filetype` varchar(50) NOT NULL,
  PRIMARY KEY  (`snippet_version_id`),
  KEY `idx_snippet_version_snippet_id` (`snippet_id`)
)  AUTO_INCREMENT=16 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `snippet_version`
--

LOCK TABLES `snippet_version` WRITE;
/*!40000 ALTER TABLE `snippet_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `snippet_version` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agg_logo_by_day`
--

DROP TABLE IF EXISTS `stats_agg_logo_by_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agg_logo_by_day` (
  `day` int(11) default NULL,
  `count` int(11) default NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agg_logo_by_day`
--

LOCK TABLES `stats_agg_logo_by_day` WRITE;
/*!40000 ALTER TABLE `stats_agg_logo_by_day` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agg_logo_by_day` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agg_logo_by_group`
--

DROP TABLE IF EXISTS `stats_agg_logo_by_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agg_logo_by_group` (
  `day` int(11) default NULL,
  `group_id` int(11) default NULL,
  `count` int(11) default NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agg_logo_by_group`
--

LOCK TABLES `stats_agg_logo_by_group` WRITE;
/*!40000 ALTER TABLE `stats_agg_logo_by_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agg_logo_by_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agg_pages_by_browser`
--

DROP TABLE IF EXISTS `stats_agg_pages_by_browser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agg_pages_by_browser` (
  `browser` varchar(8) default NULL,
  `count` int(11) default NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agg_pages_by_browser`
--

LOCK TABLES `stats_agg_pages_by_browser` WRITE;
/*!40000 ALTER TABLE `stats_agg_pages_by_browser` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agg_pages_by_browser` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agg_pages_by_day`
--

DROP TABLE IF EXISTS `stats_agg_pages_by_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agg_pages_by_day` (
  `day` int(11) NOT NULL default '0',
  `count` int(11) NOT NULL default '0',
  KEY `idx_pages_by_day_day` (`day`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agg_pages_by_day`
--

LOCK TABLES `stats_agg_pages_by_day` WRITE;
/*!40000 ALTER TABLE `stats_agg_pages_by_day` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agg_pages_by_day` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agg_pages_by_day_old`
--

DROP TABLE IF EXISTS `stats_agg_pages_by_day_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agg_pages_by_day_old` (
  `day` int(11) default NULL,
  `count` int(11) default NULL
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agg_pages_by_day_old`
--

LOCK TABLES `stats_agg_pages_by_day_old` WRITE;
/*!40000 ALTER TABLE `stats_agg_pages_by_day_old` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agg_pages_by_day_old` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agg_site_by_day`
--

DROP TABLE IF EXISTS `stats_agg_site_by_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agg_site_by_day` (
  `day` int(11) NOT NULL default '0',
  `count` int(11) NOT NULL default '0'
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agg_site_by_day`
--

LOCK TABLES `stats_agg_site_by_day` WRITE;
/*!40000 ALTER TABLE `stats_agg_site_by_day` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agg_site_by_day` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agg_site_by_group`
--

DROP TABLE IF EXISTS `stats_agg_site_by_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agg_site_by_group` (
  `day` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `count` int(11) NOT NULL default '0'
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agg_site_by_group`
--

LOCK TABLES `stats_agg_site_by_group` WRITE;
/*!40000 ALTER TABLE `stats_agg_site_by_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agg_site_by_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agr_filerelease`
--

DROP TABLE IF EXISTS `stats_agr_filerelease`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agr_filerelease` (
  `filerelease_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  KEY `idx_stats_agr_tmp_fid` (`filerelease_id`),
  KEY `idx_stats_agr_tmp_gid` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agr_filerelease`
--

LOCK TABLES `stats_agr_filerelease` WRITE;
/*!40000 ALTER TABLE `stats_agr_filerelease` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agr_filerelease` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_agr_project`
--

DROP TABLE IF EXISTS `stats_agr_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_agr_project` (
  `group_id` int(11) NOT NULL default '0',
  `group_ranking` int(11) NOT NULL default '0',
  `group_metric` float(8,5) NOT NULL default '0.00000',
  `developers` smallint(6) NOT NULL default '0',
  `file_releases` smallint(6) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  `site_views` int(11) NOT NULL default '0',
  `logo_views` int(11) NOT NULL default '0',
  `msg_posted` smallint(6) NOT NULL default '0',
  `msg_uniq_auth` smallint(6) NOT NULL default '0',
  `bugs_opened` smallint(6) NOT NULL default '0',
  `bugs_closed` smallint(6) NOT NULL default '0',
  `support_opened` smallint(6) NOT NULL default '0',
  `support_closed` smallint(6) NOT NULL default '0',
  `patches_opened` smallint(6) NOT NULL default '0',
  `patches_closed` smallint(6) NOT NULL default '0',
  `tasks_opened` smallint(6) NOT NULL default '0',
  `tasks_closed` smallint(6) NOT NULL default '0',
  `cvs_checkouts` smallint(6) NOT NULL default '0',
  `cvs_commits` smallint(6) NOT NULL default '0',
  `cvs_adds` smallint(6) NOT NULL default '0',
  `svn_commits` smallint(6) NOT NULL default '0',
  `svn_adds` smallint(6) NOT NULL default '0',
  `svn_deletes` smallint(6) NOT NULL default '0',
  `svn_checkouts` smallint(6) NOT NULL default '0',
  `svn_access_count` smallint(6) NOT NULL default '0',
  KEY `idx_project_agr_log_group` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_agr_project`
--

LOCK TABLES `stats_agr_project` WRITE;
/*!40000 ALTER TABLE `stats_agr_project` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_agr_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_ftp_downloads`
--

DROP TABLE IF EXISTS `stats_ftp_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_ftp_downloads` (
  `day` int(11) NOT NULL default '0',
  `filerelease_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  KEY `idx_ftpdl_day` (`day`),
  KEY `idx_ftpdl_fid` (`filerelease_id`),
  KEY `idx_ftpdl_group_id` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_ftp_downloads`
--

LOCK TABLES `stats_ftp_downloads` WRITE;
/*!40000 ALTER TABLE `stats_ftp_downloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_ftp_downloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_http_downloads`
--

DROP TABLE IF EXISTS `stats_http_downloads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_http_downloads` (
  `day` int(11) NOT NULL default '0',
  `filerelease_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  KEY `idx_httpdl_day` (`day`),
  KEY `idx_httpdl_fid` (`filerelease_id`),
  KEY `idx_httpdl_group_id` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_http_downloads`
--

LOCK TABLES `stats_http_downloads` WRITE;
/*!40000 ALTER TABLE `stats_http_downloads` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_http_downloads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_project`
--

DROP TABLE IF EXISTS `stats_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_project` (
  `month` int(11) NOT NULL default '0',
  `week` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `group_ranking` int(11) NOT NULL default '0',
  `group_metric` float(8,5) NOT NULL default '0.00000',
  `developers` smallint(6) NOT NULL default '0',
  `file_releases` smallint(6) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  `site_views` int(11) NOT NULL default '0',
  `subdomain_views` int(11) NOT NULL default '0',
  `msg_posted` smallint(6) NOT NULL default '0',
  `msg_uniq_auth` smallint(6) NOT NULL default '0',
  `bugs_opened` smallint(6) NOT NULL default '0',
  `bugs_closed` smallint(6) NOT NULL default '0',
  `support_opened` smallint(6) NOT NULL default '0',
  `support_closed` smallint(6) NOT NULL default '0',
  `patches_opened` smallint(6) NOT NULL default '0',
  `patches_closed` smallint(6) NOT NULL default '0',
  `tasks_opened` smallint(6) NOT NULL default '0',
  `tasks_closed` smallint(6) NOT NULL default '0',
  `cvs_checkouts` smallint(6) NOT NULL default '0',
  `cvs_commits` smallint(6) NOT NULL default '0',
  `cvs_adds` smallint(6) NOT NULL default '0',
  `svn_commits` smallint(6) NOT NULL default '0',
  `svn_adds` smallint(6) NOT NULL default '0',
  `svn_deletes` smallint(6) NOT NULL default '0',
  `svn_checkouts` smallint(6) NOT NULL default '0',
  `svn_access_count` smallint(6) NOT NULL default '0',
  `artifacts_opened` smallint(6) NOT NULL default '0',
  `artifacts_closed` smallint(6) NOT NULL default '0',
  KEY `idx_project_log_group` (`group_id`),
  KEY `idx_archive_project_month` (`month`),
  KEY `idx_archive_project_week` (`week`),
  KEY `idx_archive_project_day` (`day`),
  KEY `idx_archive_project_monthday` (`month`,`day`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_project`
--

LOCK TABLES `stats_project` WRITE;
/*!40000 ALTER TABLE `stats_project` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_project_tmp`
--

DROP TABLE IF EXISTS `stats_project_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_project_tmp` (
  `month` int(11) NOT NULL default '0',
  `week` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `group_ranking` int(11) NOT NULL default '0',
  `group_metric` float(8,5) NOT NULL default '0.00000',
  `developers` smallint(6) NOT NULL default '0',
  `file_releases` smallint(6) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  `site_views` int(11) NOT NULL default '0',
  `subdomain_views` int(11) NOT NULL default '0',
  `msg_posted` smallint(6) NOT NULL default '0',
  `msg_uniq_auth` smallint(6) NOT NULL default '0',
  `bugs_opened` smallint(6) NOT NULL default '0',
  `bugs_closed` smallint(6) NOT NULL default '0',
  `support_opened` smallint(6) NOT NULL default '0',
  `support_closed` smallint(6) NOT NULL default '0',
  `patches_opened` smallint(6) NOT NULL default '0',
  `patches_closed` smallint(6) NOT NULL default '0',
  `tasks_opened` smallint(6) NOT NULL default '0',
  `tasks_closed` smallint(6) NOT NULL default '0',
  `cvs_checkouts` smallint(6) NOT NULL default '0',
  `cvs_commits` smallint(6) NOT NULL default '0',
  `cvs_adds` smallint(6) NOT NULL default '0',
  `svn_commits` smallint(6) NOT NULL default '0',
  `svn_adds` smallint(6) NOT NULL default '0',
  `svn_deletes` smallint(6) NOT NULL default '0',
  `svn_checkouts` smallint(6) NOT NULL default '0',
  `svn_access_count` smallint(6) NOT NULL default '0',
  `artifacts_opened` smallint(6) NOT NULL default '0',
  `artifacts_closed` smallint(6) NOT NULL default '0',
  KEY `idx_project_log_group` (`group_id`),
  KEY `idx_project_stats_day` (`day`),
  KEY `idx_project_stats_week` (`week`),
  KEY `idx_project_stats_month` (`month`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_project_tmp`
--

LOCK TABLES `stats_project_tmp` WRITE;
/*!40000 ALTER TABLE `stats_project_tmp` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_project_tmp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stats_site`
--

DROP TABLE IF EXISTS `stats_site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stats_site` (
  `month` int(11) NOT NULL default '0',
  `week` int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  `site_views` int(11) NOT NULL default '0',
  `subdomain_views` int(11) NOT NULL default '0',
  `downloads` int(11) NOT NULL default '0',
  `uniq_users` int(11) NOT NULL default '0',
  `sessions` int(11) NOT NULL default '0',
  `total_users` int(11) NOT NULL default '0',
  `new_users` int(11) NOT NULL default '0',
  `new_projects` int(11) NOT NULL default '0',
  KEY `idx_stats_site_month` (`month`),
  KEY `idx_stats_site_week` (`week`),
  KEY `idx_stats_site_day` (`day`),
  KEY `idx_stats_site_monthday` (`month`,`day`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stats_site`
--

LOCK TABLES `stats_site` WRITE;
/*!40000 ALTER TABLE `stats_site` DISABLE KEYS */;
/*!40000 ALTER TABLE `stats_site` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support`
--

DROP TABLE IF EXISTS `support`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support` (
  `support_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `support_status_id` int(11) NOT NULL default '0',
  `support_category_id` int(11) NOT NULL default '0',
  `priority` int(11) NOT NULL default '0',
  `submitted_by` int(11) NOT NULL default '0',
  `assigned_to` int(11) NOT NULL default '0',
  `open_date` int(11) NOT NULL default '0',
  `summary` text,
  `close_date` int(11) NOT NULL default '0',
  PRIMARY KEY  (`support_id`),
  KEY `idx_support_group_id` (`group_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support`
--

LOCK TABLES `support` WRITE;
/*!40000 ALTER TABLE `support` DISABLE KEYS */;
/*!40000 ALTER TABLE `support` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_canned_responses`
--

DROP TABLE IF EXISTS `support_canned_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_canned_responses` (
  `support_canned_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `title` text,
  `body` text,
  PRIMARY KEY  (`support_canned_id`),
  KEY `idx_support_canned_response_group_id` (`group_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_canned_responses`
--

LOCK TABLES `support_canned_responses` WRITE;
/*!40000 ALTER TABLE `support_canned_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_canned_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_category`
--

DROP TABLE IF EXISTS `support_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_category` (
  `support_category_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `category_name` text NOT NULL,
  PRIMARY KEY  (`support_category_id`),
  KEY `idx_support_group_group_id` (`group_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_category`
--

LOCK TABLES `support_category` WRITE;
/*!40000 ALTER TABLE `support_category` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_category` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_history`
--

DROP TABLE IF EXISTS `support_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_history` (
  `support_history_id` int(11) NOT NULL auto_increment,
  `support_id` int(11) NOT NULL default '0',
  `field_name` text NOT NULL,
  `old_value` text NOT NULL,
  `mod_by` int(11) NOT NULL default '0',
  `date` int(11) default NULL,
  PRIMARY KEY  (`support_history_id`),
  KEY `idx_support_history_support_id` (`support_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_history`
--

LOCK TABLES `support_history` WRITE;
/*!40000 ALTER TABLE `support_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_messages`
--

DROP TABLE IF EXISTS `support_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_messages` (
  `support_message_id` int(11) NOT NULL auto_increment,
  `support_id` int(11) NOT NULL default '0',
  `from_email` text,
  `date` int(11) NOT NULL default '0',
  `body` text,
  PRIMARY KEY  (`support_message_id`),
  KEY `idx_support_messages_support_id` (`support_id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_messages`
--

LOCK TABLES `support_messages` WRITE;
/*!40000 ALTER TABLE `support_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_status`
--

DROP TABLE IF EXISTS `support_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `support_status` (
  `support_status_id` int(11) NOT NULL auto_increment,
  `status_name` text,
  PRIMARY KEY  (`support_status_id`)
)  AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `support_status`
--

LOCK TABLES `support_status` WRITE;
/*!40000 ALTER TABLE `support_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_question_types`
--

DROP TABLE IF EXISTS `survey_question_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_question_types` (
  `id` int(11) NOT NULL auto_increment,
  `type` text NOT NULL,
  `rank` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=101 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_question_types`
--

LOCK TABLES `survey_question_types` WRITE;
/*!40000 ALTER TABLE `survey_question_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_question_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_questions`
--

DROP TABLE IF EXISTS `survey_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_questions` (
  `question_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `question` text NOT NULL,
  `question_type` int(11) NOT NULL default '0',
  PRIMARY KEY  (`question_id`),
  KEY `idx_survey_questions_group` (`group_id`)
)  AUTO_INCREMENT=297 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_questions`
--

LOCK TABLES `survey_questions` WRITE;
/*!40000 ALTER TABLE `survey_questions` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_radio_choices`
--

DROP TABLE IF EXISTS `survey_radio_choices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_radio_choices` (
  `choice_id` int(11) NOT NULL auto_increment,
  `question_id` int(11) NOT NULL default '0',
  `choice_rank` int(11) NOT NULL default '0',
  `radio_choice` text NOT NULL,
  PRIMARY KEY  (`choice_id`),
  KEY `idx_survey_radio_choices_question_id` (`question_id`)
)  AUTO_INCREMENT=84 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_radio_choices`
--

LOCK TABLES `survey_radio_choices` WRITE;
/*!40000 ALTER TABLE `survey_radio_choices` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_radio_choices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_rating_aggregate`
--

DROP TABLE IF EXISTS `survey_rating_aggregate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_rating_aggregate` (
  `type` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `response` float NOT NULL,
  `count` int(11) NOT NULL,
  KEY `idx_survey_rating_aggregate_type_id` (`type`,`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_rating_aggregate`
--

LOCK TABLES `survey_rating_aggregate` WRITE;
/*!40000 ALTER TABLE `survey_rating_aggregate` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_rating_aggregate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_rating_response`
--

DROP TABLE IF EXISTS `survey_rating_response`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_rating_response` (
  `user_id` int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  `id` int(11) NOT NULL default '0',
  `response` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  KEY `idx_survey_rating_responses_user_type_id` (`user_id`,`type`,`id`),
  KEY `idx_survey_rating_responses_type_id` (`type`,`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_rating_response`
--

LOCK TABLES `survey_rating_response` WRITE;
/*!40000 ALTER TABLE `survey_rating_response` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_rating_response` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `survey_responses`
--

DROP TABLE IF EXISTS `survey_responses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `survey_responses` (
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `survey_id` int(11) NOT NULL default '0',
  `question_id` int(11) NOT NULL default '0',
  `response` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  KEY `idx_survey_responses_user_survey` (`user_id`,`survey_id`),
  KEY `idx_survey_responses_user_survey_question` (`user_id`,`survey_id`,`question_id`),
  KEY `idx_survey_responses_survey_question` (`survey_id`,`question_id`),
  KEY `idx_survey_responses_group_id` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `survey_responses`
--

LOCK TABLES `survey_responses` WRITE;
/*!40000 ALTER TABLE `survey_responses` DISABLE KEYS */;
/*!40000 ALTER TABLE `survey_responses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `surveys`
--

DROP TABLE IF EXISTS `surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `surveys` (
  `survey_id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `survey_title` text NOT NULL,
  `survey_questions` text NOT NULL,
  `is_active` int(11) NOT NULL default '1',
  `is_anonymous` int(11) NOT NULL default '0',
  PRIMARY KEY  (`survey_id`),
  KEY `idx_surveys_group` (`group_id`)
)  AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `surveys`
--

LOCK TABLES `surveys` WRITE;
/*!40000 ALTER TABLE `surveys` DISABLE KEYS */;
/*!40000 ALTER TABLE `surveys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_checkins`
--

DROP TABLE IF EXISTS `svn_checkins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_checkins` (
  `id` int(11) NOT NULL auto_increment,
  `type` enum('Change','Add','Delete') default NULL,
  `commitid` int(11) NOT NULL default '0',
  `dirid` int(11) NOT NULL default '0',
  `fileid` int(11) NOT NULL default '0',
  `addedlines` int(11) NOT NULL default '999',
  `removedlines` int(11) NOT NULL default '999',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_checkins_idx` (`commitid`,`dirid`,`fileid`),
  KEY `dirid` (`dirid`),
  KEY `fileid` (`fileid`)
)  AUTO_INCREMENT=23502093 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_checkins`
--

LOCK TABLES `svn_checkins` WRITE;
/*!40000 ALTER TABLE `svn_checkins` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_checkins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_commits`
--

DROP TABLE IF EXISTS `svn_commits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_commits` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `repositoryid` int(11) NOT NULL default '0',
  `revision` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `whoid` int(11) NOT NULL default '0',
  `description` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_commits_idx` (`repositoryid`,`revision`),
  KEY `whoid` (`whoid`),
  KEY `revision` (`revision`),
  KEY `idx_search` (`group_id`,`whoid`,`id`),
  FULLTEXT KEY `description` (`description`)
)  AUTO_INCREMENT=2153200 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_commits`
--

LOCK TABLES `svn_commits` WRITE;
/*!40000 ALTER TABLE `svn_commits` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_commits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_dirs`
--

DROP TABLE IF EXISTS `svn_dirs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_dirs` (
  `id` int(11) NOT NULL auto_increment,
  `dir` varchar(255) character set utf8 collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_dir_idx` (`dir`)
)  AUTO_INCREMENT=2667191 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_dirs`
--

LOCK TABLES `svn_dirs` WRITE;
/*!40000 ALTER TABLE `svn_dirs` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_dirs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_files`
--

DROP TABLE IF EXISTS `svn_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_files` (
  `id` int(11) NOT NULL auto_increment,
  `file` varchar(255) character set utf8 collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_file_idx` (`file`)
)  AUTO_INCREMENT=2838241 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_files`
--

LOCK TABLES `svn_files` WRITE;
/*!40000 ALTER TABLE `svn_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `svn_repositories`
--

DROP TABLE IF EXISTS `svn_repositories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `svn_repositories` (
  `id` int(11) NOT NULL auto_increment,
  `repository` varchar(255) character set utf8 collate utf8_bin NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_repository_idx` (`repository`)
)  AUTO_INCREMENT=2365 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `svn_repositories`
--

LOCK TABLES `svn_repositories` WRITE;
/*!40000 ALTER TABLE `svn_repositories` DISABLE KEYS */;
/*!40000 ALTER TABLE `svn_repositories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_event`
--

DROP TABLE IF EXISTS `system_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_event` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `type` varchar(255) NOT NULL default '',
  `parameters` text,
  `priority` tinyint(1) NOT NULL default '0',
  `status` enum('NEW','RUNNING','DONE','ERROR','WARNING') NOT NULL default 'NEW',
  `create_date` datetime NOT NULL,
  `process_date` datetime default NULL,
  `end_date` datetime default NULL,
  `log` text,
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=22872 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_event`
--

LOCK TABLES `system_event` WRITE;
/*!40000 ALTER TABLE `system_event` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_event` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_events_followers`
--

DROP TABLE IF EXISTS `system_events_followers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_events_followers` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `emails` text NOT NULL,
  `types` varchar(31) NOT NULL,
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_events_followers`
--

LOCK TABLES `system_events_followers` WRITE;
/*!40000 ALTER TABLE `system_events_followers` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_events_followers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tmp_projs_releases_tmp`
--

DROP TABLE IF EXISTS `tmp_projs_releases_tmp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tmp_projs_releases_tmp` (
  `year` int(11) NOT NULL default '0',
  `month` int(11) NOT NULL default '0',
  `total_proj` int(11) NOT NULL default '0',
  `total_releases` int(11) NOT NULL default '0'
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tmp_projs_releases_tmp`
--

LOCK TABLES `tmp_projs_releases_tmp` WRITE;
/*!40000 ALTER TABLE `tmp_projs_releases_tmp` DISABLE KEYS */;
/*!40000 ALTER TABLE `tmp_projs_releases_tmp` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `top_group`
--

DROP TABLE IF EXISTS `top_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `top_group` (
  `group_id` int(11) NOT NULL default '0',
  `group_name` varchar(40) default NULL,
  `downloads_all` int(11) NOT NULL default '0',
  `rank_downloads_all` int(11) NOT NULL default '0',
  `rank_downloads_all_old` int(11) NOT NULL default '0',
  `downloads_week` int(11) NOT NULL default '0',
  `rank_downloads_week` int(11) NOT NULL default '0',
  `rank_downloads_week_old` int(11) NOT NULL default '0',
  `userrank` int(11) NOT NULL default '0',
  `rank_userrank` int(11) NOT NULL default '0',
  `rank_userrank_old` int(11) NOT NULL default '0',
  `forumposts_week` int(11) NOT NULL default '0',
  `rank_forumposts_week` int(11) NOT NULL default '0',
  `rank_forumposts_week_old` int(11) NOT NULL default '0',
  `pageviews_proj` int(11) NOT NULL default '0',
  `rank_pageviews_proj` int(11) NOT NULL default '0',
  `rank_pageviews_proj_old` int(11) NOT NULL default '0',
  KEY `rank_downloads_all_idx` (`rank_downloads_all`),
  KEY `rank_downloads_week_idx` (`rank_downloads_week`),
  KEY `rank_userrank_idx` (`rank_userrank`),
  KEY `rank_forumposts_week_idx` (`rank_forumposts_week`),
  KEY `pageviews_proj_idx` (`pageviews_proj`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `top_group`
--

LOCK TABLES `top_group` WRITE;
/*!40000 ALTER TABLE `top_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `top_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tracker_idsharing_artifact`
--

DROP TABLE IF EXISTS `tracker_idsharing_artifact`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tracker_idsharing_artifact` (
  `id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tracker_idsharing_artifact`
--

LOCK TABLES `tracker_idsharing_artifact` WRITE;
/*!40000 ALTER TABLE `tracker_idsharing_artifact` DISABLE KEYS */;
/*!40000 ALTER TABLE `tracker_idsharing_artifact` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tracker_idsharing_tracker`
--

DROP TABLE IF EXISTS `tracker_idsharing_tracker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tracker_idsharing_tracker` (
  `id` int(11) NOT NULL auto_increment,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tracker_idsharing_tracker`
--

LOCK TABLES `tracker_idsharing_tracker` WRITE;
/*!40000 ALTER TABLE `tracker_idsharing_tracker` DISABLE KEYS */;
/*!40000 ALTER TABLE `tracker_idsharing_tracker` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tracker_widget_renderer`
--

DROP TABLE IF EXISTS `tracker_widget_renderer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tracker_widget_renderer` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `owner_id` int(11) unsigned NOT NULL default '0',
  `owner_type` varchar(1) NOT NULL default 'u',
  `title` varchar(255) NOT NULL,
  `renderer_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `owner_id` (`owner_id`,`owner_type`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tracker_widget_renderer`
--

LOCK TABLES `tracker_widget_renderer` WRITE;
/*!40000 ALTER TABLE `tracker_widget_renderer` DISABLE KEYS */;
/*!40000 ALTER TABLE `tracker_widget_renderer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tracker_workflow_transition_postactions_field_date`
--

DROP TABLE IF EXISTS `tracker_workflow_transition_postactions_field_date`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tracker_workflow_transition_postactions_field_date` (
  `id` int(11) NOT NULL auto_increment,
  `transition_id` int(11) NOT NULL,
  `field_id` int(11) unsigned default NULL,
  `value_type` tinyint(2) default NULL,
  PRIMARY KEY  (`id`),
  KEY `idx_wf_transition_id` (`transition_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tracker_workflow_transition_postactions_field_date`
--

LOCK TABLES `tracker_workflow_transition_postactions_field_date` WRITE;
/*!40000 ALTER TABLE `tracker_workflow_transition_postactions_field_date` DISABLE KEYS */;
/*!40000 ALTER TABLE `tracker_workflow_transition_postactions_field_date` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trove_cat`
--

DROP TABLE IF EXISTS `trove_cat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trove_cat` (
  `trove_cat_id` int(11) NOT NULL auto_increment,
  `version` int(11) NOT NULL default '0',
  `parent` int(11) NOT NULL default '0',
  `root_parent` int(11) NOT NULL default '0',
  `shortname` varchar(80) default NULL,
  `fullname` varchar(80) default NULL,
  `description` varchar(255) default NULL,
  `count_subcat` int(11) NOT NULL default '0',
  `count_subproj` int(11) NOT NULL default '0',
  `fullpath` text NOT NULL,
  `fullpath_ids` text,
  PRIMARY KEY  (`trove_cat_id`),
  KEY `parent_idx` (`parent`),
  KEY `root_parent_idx` (`root_parent`),
  KEY `version_idx` (`version`)
)  AUTO_INCREMENT=351 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trove_cat`
--

LOCK TABLES `trove_cat` WRITE;
/*!40000 ALTER TABLE `trove_cat` DISABLE KEYS */;
/*!40000 ALTER TABLE `trove_cat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trove_group_link`
--

DROP TABLE IF EXISTS `trove_group_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trove_group_link` (
  `trove_group_id` int(11) NOT NULL auto_increment,
  `trove_cat_id` int(11) NOT NULL default '0',
  `trove_cat_version` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `trove_cat_root` int(11) NOT NULL default '0',
  PRIMARY KEY  (`trove_group_id`),
  KEY `idx_trove_group_link_group_id` (`group_id`),
  KEY `idx_trove_group_link_cat_id` (`trove_cat_id`)
)  AUTO_INCREMENT=23949 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trove_group_link`
--

LOCK TABLES `trove_group_link` WRITE;
/*!40000 ALTER TABLE `trove_group_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `trove_group_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ugroup`
--

DROP TABLE IF EXISTS `ugroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ugroup` (
  `ugroup_id` int(11) NOT NULL auto_increment,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `group_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ugroup_id`)
)  AUTO_INCREMENT=1222 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ugroup`
--

LOCK TABLES `ugroup` WRITE;
/*!40000 ALTER TABLE `ugroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `ugroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ugroup_mapping`
--

DROP TABLE IF EXISTS `ugroup_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ugroup_mapping` (
  `to_group_id` int(11) NOT NULL default '0',
  `src_ugroup_id` int(11) NOT NULL default '0',
  `dst_ugroup_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`to_group_id`,`src_ugroup_id`,`dst_ugroup_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ugroup_mapping`
--

LOCK TABLES `ugroup_mapping` WRITE;
/*!40000 ALTER TABLE `ugroup_mapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `ugroup_mapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ugroup_user`
--

DROP TABLE IF EXISTS `ugroup_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ugroup_user` (
  `ugroup_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0'
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ugroup_user`
--

LOCK TABLES `ugroup_user` WRITE;
/*!40000 ALTER TABLE `ugroup_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `ugroup_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL auto_increment,
  `user_name` text NOT NULL,
  `email` text NOT NULL,
  `user_pw` varchar(32) NOT NULL,
  `realname` varchar(32) NOT NULL,
  `register_purpose` text,
  `status` char(1) NOT NULL default 'A',
  `shell` varchar(50) NOT NULL default '/usr/lib/codendi/bin/cvssh-restricted',
  `unix_pw` varchar(40) NOT NULL,
  `unix_status` char(1) NOT NULL default 'N',
  `unix_uid` int(11) NOT NULL default '0',
  `unix_box` varchar(10) NOT NULL default 'shell1',
  `ldap_id` text,
  `add_date` int(11) NOT NULL default '0',
  `approved_by` int(11) NOT NULL default '0',
  `confirm_hash` varchar(32) default NULL,
  `mail_siteupdates` int(11) NOT NULL default '0',
  `mail_va` int(11) NOT NULL default '0',
  `sticky_login` int(11) NOT NULL default '0',
  `authorized_keys` text,
  `email_new` text,
  `people_view_skills` int(11) NOT NULL default '0',
  `people_resume` text NOT NULL,
  `timezone` varchar(64) default 'GMT',
  `fontsize` int(10) unsigned NOT NULL default '0',
  `theme` varchar(50) default NULL,
  `language_id` varchar(17) NOT NULL default 'en_US',
  `last_pwd_update` int(11) unsigned NOT NULL default '0',
  `expiry_date` int(11) default NULL,
  PRIMARY KEY  (`user_id`),
  KEY `idx_user_user` (`status`),
  KEY `idx_user_name` (`user_name`(10))
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_access`
--

DROP TABLE IF EXISTS `user_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_access` (
  `user_id` int(11) NOT NULL default '0',
  `last_access_date` int(11) NOT NULL default '0',
  `prev_auth_success` int(11) NOT NULL default '0',
  `last_auth_success` int(11) NOT NULL default '0',
  `last_auth_failure` int(11) NOT NULL default '0',
  `nb_auth_failure` int(11) NOT NULL default '0',
  PRIMARY KEY  (`user_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_access`
--

LOCK TABLES `user_access` WRITE;
/*!40000 ALTER TABLE `user_access` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_access` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_bookmarks`
--

DROP TABLE IF EXISTS `user_bookmarks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_bookmarks` (
  `bookmark_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `bookmark_url` text,
  `bookmark_title` text,
  PRIMARY KEY  (`bookmark_id`),
  KEY `idx_user_bookmark_user_id` (`user_id`)
)  AUTO_INCREMENT=1830 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_bookmarks`
--

LOCK TABLES `user_bookmarks` WRITE;
/*!40000 ALTER TABLE `user_bookmarks` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_bookmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_group`
--

DROP TABLE IF EXISTS `user_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_group` (
  `user_group_id` int(11) NOT NULL auto_increment,
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `admin_flags` char(16) NOT NULL,
  `bug_flags` int(11) NOT NULL default '0',
  `forum_flags` int(11) NOT NULL default '0',
  `project_flags` int(11) NOT NULL default '2',
  `patch_flags` int(11) NOT NULL default '1',
  `support_flags` int(11) NOT NULL default '1',
  `doc_flags` int(11) NOT NULL default '0',
  `file_flags` int(11) NOT NULL default '0',
  `wiki_flags` int(11) NOT NULL default '0',
  `svn_flags` int(11) NOT NULL default '0',
  `news_flags` int(11) NOT NULL default '0',
  PRIMARY KEY  (`user_group_id`),
  KEY `idx_user_group_user_id` (`user_id`),
  KEY `idx_user_group_group_id` (`group_id`),
  KEY `bug_flags_idx` (`bug_flags`),
  KEY `forum_flags_idx` (`forum_flags`),
  KEY `project_flags_idx` (`project_flags`),
  KEY `admin_flags_idx` (`admin_flags`)
)  AUTO_INCREMENT=29262 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_group`
--

LOCK TABLES `user_group` WRITE;
/*!40000 ALTER TABLE `user_group` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_plugin`
--

DROP TABLE IF EXISTS `user_plugin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_plugin` (
  `user_id` int(11) NOT NULL default '0',
  `plugin_id` int(11) NOT NULL default '0'
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_plugin`
--

LOCK TABLES `user_plugin` WRITE;
/*!40000 ALTER TABLE `user_plugin` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_plugin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_preferences` (
  `user_id` int(11) NOT NULL default '0',
  `preference_name` varchar(255) NOT NULL,
  `preference_value` text,
  PRIMARY KEY  (`user_id`,`preference_name`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `widget_image`
--

DROP TABLE IF EXISTS `widget_image`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `widget_image` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `owner_id` int(11) unsigned NOT NULL default '0',
  `owner_type` varchar(1) NOT NULL default 'u',
  `title` varchar(255) NOT NULL,
  `url` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `owner_id` (`owner_id`,`owner_type`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `widget_image`
--

LOCK TABLES `widget_image` WRITE;
/*!40000 ALTER TABLE `widget_image` DISABLE KEYS */;
/*!40000 ALTER TABLE `widget_image` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `widget_rss`
--

DROP TABLE IF EXISTS `widget_rss`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `widget_rss` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `owner_id` int(11) unsigned NOT NULL default '0',
  `owner_type` char(1) NOT NULL default 'u',
  `title` varchar(255) NOT NULL,
  `url` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `owner_id` (`owner_id`,`owner_type`)
)  AUTO_INCREMENT=90 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `widget_rss`
--

LOCK TABLES `widget_rss` WRITE;
/*!40000 ALTER TABLE `widget_rss` DISABLE KEYS */;
/*!40000 ALTER TABLE `widget_rss` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `widget_twitterfollow`
--

DROP TABLE IF EXISTS `widget_twitterfollow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `widget_twitterfollow` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `owner_id` int(11) unsigned NOT NULL default '0',
  `owner_type` varchar(1) NOT NULL default 'u',
  `title` varchar(255) NOT NULL,
  `user` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `owner_id` (`owner_id`,`owner_type`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `widget_twitterfollow`
--

LOCK TABLES `widget_twitterfollow` WRITE;
/*!40000 ALTER TABLE `widget_twitterfollow` DISABLE KEYS */;
/*!40000 ALTER TABLE `widget_twitterfollow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `widget_wikipage`
--

DROP TABLE IF EXISTS `widget_wikipage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `widget_wikipage` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `owner_id` int(11) unsigned NOT NULL default '0',
  `owner_type` varchar(1) NOT NULL default 'u',
  `title` varchar(255) NOT NULL,
  `group_id` int(11) unsigned NOT NULL default '0',
  `wiki_page` text,
  PRIMARY KEY  (`id`),
  KEY `owner_id` (`owner_id`,`owner_type`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `widget_wikipage`
--

LOCK TABLES `widget_wikipage` WRITE;
/*!40000 ALTER TABLE `widget_wikipage` DISABLE KEYS */;
/*!40000 ALTER TABLE `widget_wikipage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_attachment`
--

DROP TABLE IF EXISTS `wiki_attachment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_attachment` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `filesystem_name` varchar(255) default NULL,
  `delete_date` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_attachment`
--

LOCK TABLES `wiki_attachment` WRITE;
/*!40000 ALTER TABLE `wiki_attachment` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_attachment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_attachment_deleted`
--

DROP TABLE IF EXISTS `wiki_attachment_deleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_attachment_deleted` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `filesystem_name` varchar(255) default NULL,
  `delete_date` int(11) unsigned default NULL,
  `purge_date` int(11) unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `idx_delete_date` (`delete_date`),
  KEY `idx_purge_date` (`purge_date`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_attachment_deleted`
--

LOCK TABLES `wiki_attachment_deleted` WRITE;
/*!40000 ALTER TABLE `wiki_attachment_deleted` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_attachment_deleted` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_attachment_log`
--

DROP TABLE IF EXISTS `wiki_attachment_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_attachment_log` (
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `wiki_attachment_id` int(11) NOT NULL default '0',
  `wiki_attachment_revision_id` int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  KEY `all_idx` (`user_id`,`group_id`),
  KEY `time_idx` (`time`),
  KEY `group_id_idx` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_attachment_log`
--

LOCK TABLES `wiki_attachment_log` WRITE;
/*!40000 ALTER TABLE `wiki_attachment_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_attachment_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_attachment_revision`
--

DROP TABLE IF EXISTS `wiki_attachment_revision`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_attachment_revision` (
  `id` int(11) NOT NULL auto_increment,
  `attachment_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `revision` int(11) NOT NULL default '0',
  `mimetype` varchar(255) NOT NULL,
  `size` bigint(20) NOT NULL,
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=3758 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_attachment_revision`
--

LOCK TABLES `wiki_attachment_revision` WRITE;
/*!40000 ALTER TABLE `wiki_attachment_revision` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_attachment_revision` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_group_list`
--

DROP TABLE IF EXISTS `wiki_group_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_group_list` (
  `id` int(11) NOT NULL auto_increment,
  `group_id` int(11) NOT NULL default '0',
  `wiki_name` varchar(255) NOT NULL,
  `wiki_link` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `rank` int(11) NOT NULL default '0',
  `language_id` varchar(17) NOT NULL default 'en_US',
  PRIMARY KEY  (`id`)
)  AUTO_INCREMENT=1485 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_group_list`
--

LOCK TABLES `wiki_group_list` WRITE;
/*!40000 ALTER TABLE `wiki_group_list` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_group_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_link`
--

DROP TABLE IF EXISTS `wiki_link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_link` (
  `linkfrom` int(11) NOT NULL default '0',
  `linkto` int(11) NOT NULL default '0',
  KEY `linkfrom` (`linkfrom`),
  KEY `linkto` (`linkto`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_link`
--

LOCK TABLES `wiki_link` WRITE;
/*!40000 ALTER TABLE `wiki_link` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_log`
--

DROP TABLE IF EXISTS `wiki_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_log` (
  `user_id` int(11) NOT NULL default '0',
  `group_id` int(11) NOT NULL default '0',
  `pagename` varchar(255) NOT NULL,
  `time` int(11) NOT NULL default '0',
  KEY `all_idx` (`user_id`,`group_id`),
  KEY `time_idx` (`time`),
  KEY `group_id_idx` (`group_id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_log`
--

LOCK TABLES `wiki_log` WRITE;
/*!40000 ALTER TABLE `wiki_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_nonempty`
--

DROP TABLE IF EXISTS `wiki_nonempty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_nonempty` (
  `id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_nonempty`
--

LOCK TABLES `wiki_nonempty` WRITE;
/*!40000 ALTER TABLE `wiki_nonempty` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_nonempty` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_page`
--

DROP TABLE IF EXISTS `wiki_page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_page` (
  `id` int(11) NOT NULL auto_increment,
  `pagename` varchar(100) character set utf8 collate utf8_bin NOT NULL,
  `hits` int(11) NOT NULL default '0',
  `pagedata` mediumtext NOT NULL,
  `group_id` int(11) NOT NULL default '0',
  `cached_html` mediumblob,
  PRIMARY KEY  (`id`),
  KEY `idx_page_group` (`group_id`,`pagename`(10))
)  AUTO_INCREMENT=195286 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_page`
--

LOCK TABLES `wiki_page` WRITE;
/*!40000 ALTER TABLE `wiki_page` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_recent`
--

DROP TABLE IF EXISTS `wiki_recent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_recent` (
  `id` int(11) NOT NULL default '0',
  `latestversion` int(11) default NULL,
  `latestmajor` int(11) default NULL,
  `latestminor` int(11) default NULL,
  PRIMARY KEY  (`id`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_recent`
--

LOCK TABLES `wiki_recent` WRITE;
/*!40000 ALTER TABLE `wiki_recent` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_recent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_session`
--

DROP TABLE IF EXISTS `wiki_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_session` (
  `sess_id` varchar(32) NOT NULL,
  `sess_data` blob NOT NULL,
  `sess_date` int(10) unsigned NOT NULL default '0',
  `sess_ip` varchar(15) NOT NULL,
  PRIMARY KEY  (`sess_id`),
  KEY `sess_date` (`sess_date`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_session`
--

LOCK TABLES `wiki_session` WRITE;
/*!40000 ALTER TABLE `wiki_session` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wiki_version`
--

DROP TABLE IF EXISTS `wiki_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wiki_version` (
  `id` int(11) NOT NULL default '0',
  `version` int(11) NOT NULL default '0',
  `mtime` int(11) NOT NULL default '0',
  `minor_edit` tinyint(4) default '0',
  `content` mediumtext NOT NULL,
  `versiondata` mediumtext NOT NULL,
  PRIMARY KEY  (`id`,`version`),
  KEY `mtime` (`mtime`)
)  DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wiki_version`
--

LOCK TABLES `wiki_version` WRITE;
/*!40000 ALTER TABLE `wiki_version` DISABLE KEYS */;
/*!40000 ALTER TABLE `wiki_version` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2012-01-17 14:54:52
