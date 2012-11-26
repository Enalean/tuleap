--
-- Table structure for table `pfo_role`
--

DROP TABLE IF EXISTS `pfo_role`;
CREATE TABLE `pfo_role` (
  `role_id` int(11) NOT NULL auto_increment,
  `role_name` varchar(100) NOT NULL,
  `role_class` int(11) NOT NULL default '1',
  `home_group_id` int(11) default NULL,
  `is_public` tinyint(1) NOT NULL default '0',
  `old_role_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`role_id`),
  UNIQUE KEY `pfo_role_name_unique` (`role_id`,`role_name`)
);

--
-- Dumping data for table `pfo_role`
--

INSERT INTO `pfo_role` VALUES (1,'Anonymous',2,NULL,1,0),(2,'LoggedIn',3,NULL,1,0);

--
-- Table structure for table `pfo_role_class`
--

DROP TABLE IF EXISTS `pfo_role_class`;
CREATE TABLE `pfo_role_class` (
  `class_id` int(11) NOT NULL auto_increment,
  `class_name` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`class_id`),
  UNIQUE KEY `pfo_role_class_name_unique` (`class_name`)
);

--
-- Dumping data for table `pfo_role_class`
--

INSERT INTO `pfo_role_class` VALUES (1,'PFO_RoleExplicit'),(2,'PFO_RoleAnonymous'),(3,'PFO_RoleLoggedIn');

--
-- Table structure for table `pfo_role_setting`
--

DROP TABLE IF EXISTS `pfo_role_setting`;
CREATE TABLE `pfo_role_setting` (
  `role_id` int(11) NOT NULL default '0',
  `section_name` varchar(100) NOT NULL default '',
  `ref_id` int(11) NOT NULL default '0',
  `perm_val` int(11) NOT NULL default '0',
  UNIQUE KEY `pfo_role_setting_unique` (`role_id`,`section_name`,`ref_id`)
);

--
-- Table structure for table `pfo_user_role`
--

DROP TABLE IF EXISTS `pfo_user_role`;
CREATE TABLE `pfo_user_role` (
  `user_id` int(11) NOT NULL default '0',
  `role_id` int(11) NOT NULL default '0',
  UNIQUE KEY `pfo_user_role_unique` (`user_id`,`role_id`)
);

