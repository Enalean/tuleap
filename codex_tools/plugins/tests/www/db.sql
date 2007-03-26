-- phpMyAdmin SQL Dump
-- version 2.8.2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Mar 26, 2007 at 02:07 PM
-- Server version: 4.1.20
-- PHP Version: 4.3.9
-- 
-- Database: 'codex'
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table 'activity_log'
-- 

DROP TABLE IF EXISTS activity_log;
CREATE TABLE IF NOT EXISTS activity_log (
  `day` int(11) NOT NULL default '0',
  `hour` int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  browser varchar(8) NOT NULL default 'OTHER',
  ver float(10,2) NOT NULL default '0.00',
  platform varchar(8) NOT NULL default 'OTHER',
  `time` int(11) NOT NULL default '0',
  page text,
  `type` int(11) NOT NULL default '0',
  KEY idx_activity_log_day (`day`),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'activity_log'
-- 

INSERT INTO activity_log (day, hour, group_id, browser, ver, platform, time, page, type) VALUES (20070314, 18, 0, 'MOZILLA', 5.00, 'Linux', 1173891688, '/admin/servers/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566257, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566266, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566266, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566271, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566293, '/account/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566454, '/account/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566463, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566473, '/account/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566502, '/account/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566507, '/account/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566573, '/account/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566634, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566640, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566640, '/my/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566645, '/admin/userlist.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566654, '/admin/userlist.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566656, '/admin/userlist.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566660, '/admin/userlist.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566668, '/admin/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566673, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566673, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566675, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566690, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566690, '/my/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566696, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566696, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566698, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566714, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566725, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566729, '/my/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566736, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566736, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566738, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566761, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566761, '/my/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566768, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566768, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566771, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566784, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566784, '/my/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566790, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566796, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566821, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566829, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566835, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566868, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566900, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566912, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566917, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566921, '/project/register.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566928, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566928, '/index.php', 0),
(20070322, 13, 0, 'OTHER', 0.00, 'Other', 1174566928, '/write_cache.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566932, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566938, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566938, '/my/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566945, '/admin/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566946, '/admin/grouplist.php', 0),
(20070322, 13, 111, 'MOZILLA', 5.00, 'Linux', 1174566954, '/admin/groupedit.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566962, '/admin/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566967, '/admin/approve-pending.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566972, '/admin/approve-pending.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566981, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174566981, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567392, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567406, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567411, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567423, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567424, '/my/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567429, '/projects.php/testnews/', 0),
(20070322, 13, 111, 'MOZILLA', 5.00, 'Linux', 1174567437, '/project/memberlist.php', 0),
(20070322, 13, 111, 'MOZILLA', 5.00, 'Linux', 1174567438, '/project/admin/index.php', 0),
(20070322, 13, 111, 'MOZILLA', 5.00, 'Linux', 1174567446, '/project/admin/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567455, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567455, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567457, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567468, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567468, '/my/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567473, '/account/logout.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567473, '/index.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567477, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567501, '/account/login.php', 0),
(20070322, 13, 0, 'MOZILLA', 5.00, 'Linux', 1174567502, '/my/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580598, '/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174579996, '/index.php', 0),
(20070322, 17, 0, 'OTHER', 0.00, 'Other', 1174579996, '/write_cache.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174579998, '/account/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580028, '/account/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580034, '/account/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580059, '/account/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580067, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580078, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580079, '/my/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580086, '/admin/userlist.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580093, '/admin/userlist.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580095, '/admin/userlist.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580188, '/account/logout.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580188, '/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580190, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580202, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580202, '/my/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580204, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580215, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580232, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580237, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580267, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580281, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580289, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580291, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580301, '/project/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580308, '/account/logout.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580308, '/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580310, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580318, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580318, '/my/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580328, '/admin/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580334, '/admin/approve-pending.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580341, '/admin/approve-pending.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580348, '/account/logout.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174580348, '/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174581740, '/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174581226, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174581477, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174581478, '/my/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174581484, '/projects/testsvn/', 0),
(20070322, 17, 112, 'MOZILLA', 5.00, 'Linux', 1174581491, '/project/memberlist.php', 0),
(20070322, 17, 112, 'MOZILLA', 5.00, 'Linux', 1174581524, '/project/admin/index.php', 0),
(20070322, 17, 112, 'MOZILLA', 5.00, 'Linux', 1174581532, '/project/admin/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174581540, '/account/logout.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174581540, '/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582716, '/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582113, '/account/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582138, '/account/register.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582140, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582145, '/account/login.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582146, '/my/index.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582152, '/admin/userlist.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582160, '/admin/userlist.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582178, '/account/logout.php', 0),
(20070322, 17, 0, 'MOZILLA', 5.00, 'Linux', 1174582178, '/index.php', 0),
(20070322, 17, 0, 'OTHER', 0.00, 'Other', 1174582178, '/write_cache.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901370, '/index.php', 0),
(20070326, 11, 0, 'OTHER', 0.00, 'Other', 1174901370, '/write_cache.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901381, '/account/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901422, '/account/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901425, '/account/login.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901431, '/account/login.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901431, '/my/index.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901435, '/admin/userlist.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901438, '/admin/userlist.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901440, '/account/logout.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901440, '/index.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901442, '/account/login.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901454, '/account/login.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901454, '/my/index.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901462, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901472, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901523, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901525, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901539, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901553, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901556, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901558, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901562, '/project/register.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901565, '/account/logout.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901565, '/index.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901568, '/account/login.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901577, '/account/login.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901577, '/my/index.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901580, '/admin/grouplist.php', 0),
(20070326, 11, 113, 'MOZILLA', 5.00, 'Linux', 1174901583, '/admin/groupedit.php', 0),
(20070326, 11, 113, 'MOZILLA', 5.00, 'Linux', 1174901588, '/admin/groupedit.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901592, '/admin/index.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901595, '/admin/approve-pending.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901598, '/account/logout.php', 0),
(20070326, 11, 0, 'MOZILLA', 5.00, 'Linux', 1174901598, '/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910432, '/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910439, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910447, '/account/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910473, '/account/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910476, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910480, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910480, '/my/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910482, '/admin/userlist.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910485, '/admin/userlist.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910490, '/account/logout.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910490, '/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910491, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910503, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910504, '/my/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910507, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910510, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910579, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910581, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910590, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910599, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910602, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910603, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910606, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910611, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910614, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910638, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910640, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910648, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910659, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910662, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910663, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910666, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910671, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910674, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910687, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910689, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910696, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910706, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910709, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910710, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910712, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910715, '/account/logout.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910716, '/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910717, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910719, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910719, '/my/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910721, '/account/logout.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910722, '/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910723, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910728, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910728, '/my/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910730, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910732, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910744, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910746, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910754, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910760, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910762, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910764, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910766, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910769, '/account/logout.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910769, '/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910771, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910773, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910773, '/my/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910776, '/admin/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910778, '/admin/approve-pending.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910782, '/admin/approve-pending.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910785, '/admin/approve-pending.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910786, '/admin/approve-pending.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910788, '/admin/approve-pending.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910791, '/account/logout.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174910791, '/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911224, '/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911227, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911232, '/account/login.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911232, '/my/index.php', 0),
(20070326, 14, 114, 'MOZILLA', 5.00, 'Linux', 1174911234, '/project/admin/index.php', 0),
(20070326, 14, 114, 'MOZILLA', 5.00, 'Linux', 1174911238, '/project/admin/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911242, '/my/index.php', 0),
(20070326, 14, 116, 'MOZILLA', 5.00, 'Linux', 1174911244, '/project/admin/index.php', 0),
(20070326, 14, 116, 'MOZILLA', 5.00, 'Linux', 1174911247, '/project/admin/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911249, '/my/index.php', 0),
(20070326, 14, 117, 'MOZILLA', 5.00, 'Linux', 1174911251, '/project/admin/index.php', 0),
(20070326, 14, 117, 'MOZILLA', 5.00, 'Linux', 1174911254, '/project/admin/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911257, '/my/index.php', 0),
(20070326, 14, 115, 'MOZILLA', 5.00, 'Linux', 1174911258, '/project/admin/index.php', 0),
(20070326, 14, 115, 'MOZILLA', 5.00, 'Linux', 1174911261, '/project/admin/index.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911264, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911267, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911270, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911273, '/project/register.php', 0),
(20070326, 14, 0, 'MOZILLA', 5.00, 'Linux', 1174911273, '/index.php', 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'activity_log_old'
-- 

DROP TABLE IF EXISTS activity_log_old;
CREATE TABLE IF NOT EXISTS activity_log_old (
  `day` int(11) NOT NULL default '0',
  `hour` int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  browser varchar(8) NOT NULL default 'OTHER',
  ver float(10,2) NOT NULL default '0.00',
  platform varchar(8) NOT NULL default 'OTHER',
  `time` int(11) NOT NULL default '0',
  page text,
  `type` int(11) NOT NULL default '0',
  KEY idx_activity_log_day (`day`),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'activity_log_old'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'activity_log_old_old'
-- 

DROP TABLE IF EXISTS activity_log_old_old;
CREATE TABLE IF NOT EXISTS activity_log_old_old (
  `day` int(11) NOT NULL default '0',
  `hour` int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  browser varchar(8) NOT NULL default 'OTHER',
  ver float(10,2) NOT NULL default '0.00',
  platform varchar(8) NOT NULL default 'OTHER',
  `time` int(11) NOT NULL default '0',
  page text,
  `type` int(11) NOT NULL default '0',
  KEY idx_activity_log_day (`day`),
  KEY idx_activity_log_group (group_id),
  KEY type_idx (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'activity_log_old_old'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact'
-- 

DROP TABLE IF EXISTS artifact;
CREATE TABLE IF NOT EXISTS artifact (
  artifact_id int(11) NOT NULL auto_increment,
  group_artifact_id int(11) NOT NULL default '0',
  status_id int(11) NOT NULL default '1',
  submitted_by int(11) NOT NULL default '100',
  open_date int(11) NOT NULL default '0',
  close_date int(11) NOT NULL default '0',
  summary text NOT NULL,
  details text NOT NULL,
  severity int(11) NOT NULL default '0',
  PRIMARY KEY  (artifact_id),
  KEY idx_fk_group_artifact_id (group_artifact_id),
  KEY idx_fk_status_id (status_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_canned_responses'
-- 

DROP TABLE IF EXISTS artifact_canned_responses;
CREATE TABLE IF NOT EXISTS artifact_canned_responses (
  artifact_canned_id int(11) NOT NULL auto_increment,
  group_artifact_id int(11) NOT NULL default '0',
  title text,
  body text,
  PRIMARY KEY  (artifact_canned_id),
  KEY idx_artifact_canned_response_group_artifact_id (group_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_canned_responses'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_cc'
-- 

DROP TABLE IF EXISTS artifact_cc;
CREATE TABLE IF NOT EXISTS artifact_cc (
  artifact_cc_id int(11) NOT NULL auto_increment,
  artifact_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  added_by int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (artifact_cc_id),
  KEY artifact_id_idx (artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_cc'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_dependencies'
-- 

DROP TABLE IF EXISTS artifact_dependencies;
CREATE TABLE IF NOT EXISTS artifact_dependencies (
  artifact_depend_id int(11) NOT NULL auto_increment,
  artifact_id int(11) NOT NULL default '0',
  is_dependent_on_artifact_id int(11) NOT NULL default '0',
  PRIMARY KEY  (artifact_depend_id),
  KEY idx_artifact_dependencies_artifact_id (artifact_id),
  KEY idx_actifact_is_dependent_on_artifact_id (is_dependent_on_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_dependencies'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_field'
-- 

DROP TABLE IF EXISTS artifact_field;
CREATE TABLE IF NOT EXISTS artifact_field (
  field_id int(11) NOT NULL auto_increment,
  group_artifact_id int(11) NOT NULL default '0',
  field_set_id int(11) unsigned NOT NULL default '0',
  field_name varchar(255) NOT NULL default '',
  data_type int(11) NOT NULL default '0',
  display_type varchar(255) NOT NULL default '',
  display_size varchar(255) NOT NULL default '',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default '',
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  keep_history int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  value_function text,
  default_value text NOT NULL,
  PRIMARY KEY  (field_id,group_artifact_id),
  KEY idx_fk_field_name (field_name),
  KEY idx_fk_group_artifact_id (group_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_field'
-- 

INSERT INTO artifact_field (field_id, group_artifact_id, field_set_id, field_name, data_type, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, value_function, default_value) VALUES (7, 1, 1, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 0, 1, 'artifact_submitters', ''),
(6, 1, 1, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 1, 1, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, NULL, ''),
(1, 1, 1, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, NULL, ''),
(4, 1, 2, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 1, 1, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, NULL, '100'),
(2, 1, 2, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, NULL, '1'),
(30, 1, 2, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, NULL, '1'),
(8, 1, 1, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, NULL, '5'),
(10, 1, 1, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, NULL, '100'),
(9, 1, 1, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, NULL, ''),
(16, 1, 2, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, NULL, '100'),
(20, 1, 1, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, NULL, '100'),
(11, 1, 1, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, NULL, '100'),
(12, 1, 1, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, NULL, '100'),
(13, 1, 2, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, NULL, '100'),
(14, 1, 2, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, NULL, '100'),
(15, 1, 2, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, NULL, '100'),
(17, 1, 2, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, NULL, ''),
(18, 1, 2, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, NULL, '100'),
(19, 1, 1, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, NULL, ''),
(22, 1, 1, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, NULL, '100'),
(23, 1, 1, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, NULL, ''),
(24, 1, 1, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, NULL, '100'),
(26, 1, 1, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, NULL, ''),
(27, 1, 1, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, NULL, ''),
(28, 1, 1, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, NULL, ''),
(29, 1, 2, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, NULL, ''),
(2, 2, 4, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, NULL, '1000'),
(4, 2, 4, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, NULL, '0.00'),
(5, 2, 4, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, NULL, ''),
(6, 2, 4, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, NULL, ''),
(7, 2, 3, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, NULL, ''),
(8, 2, 3, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, NULL, ''),
(11, 2, 4, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, NULL, '1'),
(15, 2, 4, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, NULL, '1'),
(1, 2, 3, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, NULL, ''),
(10, 2, 3, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 2, 3, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 2, 3, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, NULL, '100'),
(13, 2, 3, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 0, 1, 'artifact_submitters', ''),
(14, 2, 3, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, NULL, '5'),
(9, 3, 5, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 0, 1, 'artifact_submitters', ''),
(7, 3, 6, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, NULL, '1'),
(12, 3, 6, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, NULL, '1'),
(6, 3, 6, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 3, 5, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, NULL, '100'),
(4, 3, 5, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 3, 5, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, NULL, ''),
(2, 3, 5, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, NULL, ''),
(1, 3, 5, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, NULL, ''),
(10, 3, 6, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, NULL, ''),
(11, 3, 5, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, NULL, '5'),
(1, 4, 7, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 0, 1, 'artifact_submitters', ''),
(2, 4, 7, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 4, 7, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, NULL, ''),
(4, 4, 7, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, NULL, ''),
(5, 4, 7, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, NULL, ''),
(6, 4, 7, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, NULL, '1'),
(11, 4, 7, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, NULL, '1'),
(7, 4, 7, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, NULL, '5'),
(8, 4, 7, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, NULL, ''),
(9, 4, 7, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(10, 4, 7, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(1, 5, 8, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 0, 1, 'artifact_submitters', ''),
(2, 5, 8, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 5, 8, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, NULL, ''),
(4, 5, 8, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, NULL, ''),
(5, 5, 9, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, NULL, ''),
(6, 5, 10, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 5, 8, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, NULL, '100'),
(8, 5, 8, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, NULL, ''),
(9, 5, 10, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, NULL, '1'),
(10, 5, 8, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, NULL, '5'),
(11, 5, 8, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, NULL, '100'),
(12, 5, 10, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, NULL, '100'),
(7, 101, 11, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 101, 11, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 101, 11, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 101, 11, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 101, 12, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 101, 11, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 101, 12, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 101, 12, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 101, 11, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 101, 11, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 101, 11, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 101, 12, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 101, 11, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 101, 11, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 101, 11, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 101, 12, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 101, 12, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 101, 12, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 101, 12, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 101, 12, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 101, 11, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 101, 11, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 101, 11, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 101, 11, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 101, 11, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 101, 11, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 101, 11, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 101, 12, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 102, 14, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 102, 14, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 102, 14, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 102, 14, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 102, 13, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 102, 13, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 102, 14, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 102, 14, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 102, 13, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 102, 13, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 102, 13, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 102, 13, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 102, 13, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 102, 13, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 103, 15, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 103, 16, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 103, 16, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 103, 16, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 103, 15, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 103, 15, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 103, 15, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 103, 15, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 103, 15, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 103, 16, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 103, 15, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 104, 17, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 104, 17, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 104, 17, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 104, 17, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 104, 18, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 104, 19, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 104, 17, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 104, 17, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 104, 19, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 104, 17, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 104, 17, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 104, 19, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 105, 20, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 105, 20, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 105, 20, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 105, 20, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 105, 21, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 105, 20, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 105, 21, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 105, 21, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 105, 20, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 105, 20, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 105, 20, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 105, 21, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 105, 20, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 105, 20, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 105, 20, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 105, 21, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 105, 21, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 105, 21, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 105, 21, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 105, 21, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 105, 20, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 105, 20, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 105, 20, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 105, 20, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 105, 20, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 105, 20, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 105, 20, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 105, 21, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 106, 23, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 106, 23, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 106, 23, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 106, 23, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 106, 22, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 106, 22, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 106, 23, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 106, 23, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 106, 22, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 106, 22, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 106, 22, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 106, 22, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 106, 22, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 106, 22, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 107, 24, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 107, 25, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 107, 25, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 107, 25, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 107, 24, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 107, 24, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 107, 24, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 107, 24, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 107, 24, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 107, 25, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 107, 24, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 108, 26, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 108, 26, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 108, 26, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 108, 26, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 108, 27, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 108, 28, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 108, 26, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 108, 26, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 108, 28, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 108, 26, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 108, 26, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 108, 28, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 109, 29, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 109, 29, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 109, 29, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 109, 29, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 109, 30, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 109, 29, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 109, 30, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 109, 30, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 109, 29, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 109, 29, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 109, 29, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 109, 30, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 109, 29, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 109, 29, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 109, 29, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 109, 30, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 109, 30, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 109, 30, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 109, 30, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 109, 30, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 109, 29, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 109, 29, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 109, 29, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 109, 29, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 109, 29, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 109, 29, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 109, 29, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 109, 30, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 110, 32, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 110, 32, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 110, 32, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 110, 32, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 110, 31, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 110, 31, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 110, 32, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 110, 32, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 110, 31, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 110, 31, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 110, 31, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 110, 31, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 110, 31, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 110, 31, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 111, 33, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 111, 34, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 111, 34, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 111, 34, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 111, 33, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 111, 33, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 111, 33, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 111, 33, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 111, 33, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 111, 34, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 111, 33, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 112, 35, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 112, 35, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 112, 35, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 112, 35, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 112, 36, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 112, 37, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 112, 35, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 112, 35, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 112, 37, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 112, 35, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 112, 35, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 112, 37, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 113, 38, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 113, 38, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 113, 38, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 113, 38, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 113, 39, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 113, 38, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 113, 39, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 113, 39, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 113, 38, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 113, 38, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 113, 38, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 113, 39, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 113, 38, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 113, 38, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 113, 38, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 113, 39, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 113, 39, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 113, 39, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 113, 39, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 113, 39, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 113, 38, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 113, 38, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 113, 38, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 113, 38, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 113, 38, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 113, 38, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 113, 38, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 113, 39, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 114, 41, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 114, 41, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 114, 41, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 114, 41, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 114, 40, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 114, 40, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 114, 41, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 114, 41, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 114, 40, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 114, 40, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 114, 40, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 114, 40, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 114, 40, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 114, 40, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 115, 42, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 115, 43, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 115, 43, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 115, 43, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 115, 42, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 115, 42, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 115, 42, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 115, 42, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 115, 42, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 115, 43, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 115, 42, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 116, 44, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 116, 44, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 116, 44, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 116, 44, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 116, 45, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 116, 46, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 116, 44, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 116, 44, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 116, 46, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 116, 44, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 116, 44, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 116, 46, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 117, 47, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 117, 47, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 117, 47, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 117, 47, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 117, 48, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 117, 47, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 117, 48, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 117, 48, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 117, 47, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 117, 47, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 117, 47, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 117, 48, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 117, 47, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 117, 47, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 117, 47, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100');
INSERT INTO artifact_field (field_id, group_artifact_id, field_set_id, field_name, data_type, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, value_function, default_value) VALUES (13, 117, 48, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 117, 48, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 117, 48, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 117, 48, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 117, 48, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 117, 47, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 117, 47, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 117, 47, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 117, 47, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 117, 47, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 117, 47, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 117, 47, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 117, 48, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 118, 50, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 118, 50, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 118, 50, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 118, 50, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 118, 49, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 118, 49, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 118, 50, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 118, 50, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 118, 49, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 118, 49, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 118, 49, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 118, 49, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 118, 49, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 118, 49, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 119, 51, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 119, 52, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 119, 52, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 119, 52, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 119, 51, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 119, 51, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 119, 51, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 119, 51, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 119, 51, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 119, 52, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 119, 51, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 120, 53, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 120, 53, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 120, 53, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 120, 53, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 120, 54, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 120, 55, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 120, 53, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 120, 53, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 120, 55, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 120, 53, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 120, 53, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 120, 55, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 121, 56, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 121, 56, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 121, 56, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 121, 56, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 121, 57, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 121, 56, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 121, 57, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 121, 57, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 121, 56, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 121, 56, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 121, 56, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 121, 57, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 121, 56, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 121, 56, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 121, 56, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 121, 57, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 121, 57, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 121, 57, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 121, 57, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 121, 57, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 121, 56, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 121, 56, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 121, 56, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 121, 56, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 121, 56, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 121, 56, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 121, 56, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 121, 57, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 122, 59, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 122, 59, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 122, 59, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 122, 59, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 122, 58, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 122, 58, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 122, 59, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 122, 59, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 122, 58, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 122, 58, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 122, 58, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 122, 58, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 122, 58, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 122, 58, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 123, 60, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 123, 61, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 123, 61, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 123, 61, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 123, 60, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 123, 60, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 123, 60, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 123, 60, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 123, 60, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 123, 61, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 123, 60, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 124, 62, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 124, 62, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 124, 62, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 124, 62, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 124, 63, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 124, 64, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 124, 62, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 124, 62, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 124, 64, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 124, 62, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 124, 62, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 124, 64, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 125, 65, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 125, 65, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 125, 65, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 125, 65, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 125, 66, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 125, 65, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 125, 66, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 125, 66, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 125, 65, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 125, 65, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 125, 65, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 125, 66, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 125, 65, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 125, 65, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 125, 65, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 125, 66, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 125, 66, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 125, 66, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 125, 66, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 125, 66, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 125, 65, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 125, 65, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 125, 65, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 125, 65, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 125, 65, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 125, 65, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 125, 65, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 125, 66, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 126, 68, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 126, 68, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 126, 68, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 126, 68, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 126, 67, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 126, 67, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 126, 68, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 126, 68, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 126, 67, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 126, 67, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 126, 67, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 126, 67, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 126, 67, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 126, 67, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 127, 69, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 127, 70, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 127, 70, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 127, 70, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 127, 69, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 127, 69, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 127, 69, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 127, 69, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 127, 69, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 127, 70, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 127, 69, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 128, 71, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 128, 71, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 128, 71, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 128, 71, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 128, 72, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 128, 73, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 128, 71, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 128, 71, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 128, 73, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 128, 71, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 128, 71, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 128, 73, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 129, 74, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 129, 74, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 129, 74, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 129, 74, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 129, 75, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 129, 74, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 129, 75, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 129, 75, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 129, 74, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 129, 74, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 129, 74, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 129, 75, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 129, 74, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 129, 74, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 129, 74, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 129, 75, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 129, 75, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 129, 75, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 129, 75, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 129, 75, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 129, 74, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 129, 74, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 129, 74, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 129, 74, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 129, 74, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 129, 74, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 129, 74, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 129, 75, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 130, 77, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 130, 77, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 130, 77, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 130, 77, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 130, 76, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 130, 76, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 130, 77, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 130, 77, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 130, 76, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 130, 76, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 130, 76, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 130, 76, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 130, 76, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 130, 76, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 131, 78, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 131, 79, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 131, 79, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 131, 79, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 131, 78, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 131, 78, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 131, 78, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 131, 78, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 131, 78, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 131, 79, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 131, 78, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 132, 80, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 132, 80, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 132, 80, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 132, 80, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 132, 81, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 132, 82, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 132, 80, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 132, 80, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 132, 82, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 132, 80, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 132, 80, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 132, 82, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 133, 83, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 133, 83, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 133, 83, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 133, 83, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 133, 84, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 133, 83, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 133, 84, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 133, 84, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 133, 83, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 133, 83, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 133, 83, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 133, 84, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 133, 83, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 133, 83, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 133, 83, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 133, 84, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 133, 84, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 133, 84, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 133, 84, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 133, 84, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 133, 83, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 133, 83, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 133, 83, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 133, 83, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 133, 83, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 133, 83, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 133, 83, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 133, 84, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 134, 86, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 134, 86, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 134, 86, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 134, 86, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 134, 85, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 134, 85, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 134, 86, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 134, 86, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 134, 85, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 134, 85, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 134, 85, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 134, 85, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(13, 134, 85, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 134, 85, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 135, 87, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 135, 88, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 135, 88, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 135, 88, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 135, 87, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 135, 87, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 135, 87, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 135, 87, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 135, 87, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 135, 88, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 135, 87, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 136, 89, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 136, 89, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 136, 89, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 136, 89, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 136, 90, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 136, 91, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 136, 89, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 136, 89, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 136, 91, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 136, 89, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 136, 89, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 136, 91, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100'),
(7, 137, 92, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(6, 137, 92, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(5, 137, 92, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 137, 92, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(4, 137, 93, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(3, 137, 92, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(2, 137, 93, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(30, 137, 93, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(8, 137, 92, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(10, 137, 92, 'comment_type_id', 2, 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this artifact (Workaround, Test Case, Impacted Files,...)', '', 0, 1, 0, 1, '', '100'),
(9, 137, 92, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(16, 137, 93, 'resolution_id', 2, 'SB', '', 'Resolution', 'How you have decided to fix the artifact (Fixed, Work for me, Duplicate,..)', '', 0, 1, 1, 0, '', '100'),
(20, 137, 92, 'bug_group_id', 2, 'SB', '', 'Group', 'Characterizes the nature of the artifact (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', '', 0, 1, 1, 0, '', '100'),
(11, 137, 92, 'category_version_id', 2, 'SB', '', 'Component Version', 'The version of the System Component (aka Category) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 137, 92, 'platform_version_id', 2, 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the artifact occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, '', '100'),
(13, 137, 93, 'reproducibility_id', 2, 'SB', '', 'Reproducibility', 'How easy is it to reproduce the artifact', 'S', 0, 0, 1, 0, '', '100'),
(14, 137, 93, 'size_id', 2, 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the artifact', 'S', 0, 1, 1, 0, '', '100'),
(15, 137, 93, 'fix_release_id', 2, 'SB', '', 'Fixed Release', 'The release in which the artifact was actually fixed', 'P', 0, 1, 1, 0, '', '100'),
(17, 137, 93, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', 'S', 0, 1, 1, 0, '', ''),
(18, 137, 93, 'plan_release_id', 2, 'SB', '', 'Planned Release', 'The release in which you initially planned the artifact to be fixed', 'P', 0, 1, 1, 0, '', '100'),
(19, 137, 92, 'component_version', 1, 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the artifact. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, '', ''),
(22, 137, 92, 'priority', 2, 'SB', '', 'Priority', 'How quickly the artifact must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, '', '100'),
(23, 137, 92, 'keywords', 1, 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a artifact', 'S', 0, 1, 1, 0, '', ''),
(24, 137, 92, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(26, 137, 92, 'originator_name', 1, 'TF', '20/40', 'Originator Name', 'The name of the person who reported the artifact (if different from the submitter field)', 'S', 0, 1, 1, 0, '', ''),
(27, 137, 92, 'originator_email', 1, 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the artifact. Automatically included in the artifact email notification process.', 'S', 0, 1, 1, 0, '', ''),
(28, 137, 92, 'originator_phone', 1, 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the artifact', 'S', 0, 1, 1, 0, '', ''),
(29, 137, 93, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(2, 138, 95, 'percent_complete', 2, 'SB', '2', 'Percent complete', 'Percentage of completion', '', 0, 0, 1, 0, '', '1000'),
(4, 138, 95, 'hours', 3, 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the artifact (including testing)', '', 0, 1, 1, 0, '', '0.00'),
(5, 138, 95, 'start_date', 4, 'DF', '', 'Start Date', 'Start Date', '', 0, 0, 0, 0, '', ''),
(6, 138, 95, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(7, 138, 94, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(8, 138, 94, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(11, 138, 95, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(15, 138, 95, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(1, 138, 94, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 138, 94, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(9, 138, 94, 'multi_assigned_to', 5, 'MB', '', 'Assigned to (multiple)', 'Who is in charge of this artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(12, 138, 94, 'subproject_id', 2, 'SB', '', 'Subproject', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100');
INSERT INTO artifact_field (field_id, group_artifact_id, field_set_id, field_name, data_type, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, value_function, default_value) VALUES (13, 138, 94, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(14, 138, 94, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(9, 139, 96, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(7, 139, 97, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(12, 139, 97, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', '', 0, 0, 1, 0, '', '1'),
(6, 139, 97, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(5, 139, 96, 'category_id', 2, 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', '', 0, 1, 1, 0, '', '100'),
(4, 139, 96, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 139, 96, 'details', 1, 'TA', '60/7', 'Original Submission', 'A full description of the artifact', '', 0, 1, 1, 0, '', ''),
(2, 139, 96, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(1, 139, 96, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(10, 139, 97, 'close_date', 4, 'DF', '', 'End Date', 'End Date', '', 0, 1, 0, 0, '', ''),
(11, 139, 96, 'severity', 2, 'SB', '', 'Priority', 'How quickly the artifact must be completed', '', 0, 0, 1, 0, '', '5'),
(1, 140, 98, 'submitted_by', 5, 'SB', '', 'Submitted by', 'User who originally submitted the artifact', '', 0, 1, 1, 1, 'artifact_submitters', ''),
(2, 140, 98, 'open_date', 4, 'DF', '', 'Submitted on', 'Date and time for the initial artifact submission', '', 0, 0, 0, 1, '', ''),
(3, 140, 98, 'summary', 1, 'TF', '60/150', 'Summary', 'One line description of the artifact', '', 0, 0, 1, 0, '', ''),
(4, 140, 98, 'artifact_id', 2, 'TF', '6/10', 'Artifact ID', 'Unique artifact identifier', '', 0, 0, 0, 1, '', ''),
(5, 140, 99, 'plain_text', 1, 'TA', '60/7', 'Patch text', 'Plain-text version of the patch', '', 0, 1, 0, 0, '', ''),
(6, 140, 100, 'assigned_to', 5, 'SB', '', 'Assigned to', 'Who is in charge of solving the artifact', '', 0, 1, 1, 0, 'group_members', '100'),
(7, 140, 98, 'category_id', 2, 'SB', '', 'Category', 'Patch categories (e.g. mail module,gant chart module,interface, etc)', '', 0, 1, 1, 0, '', '100'),
(8, 140, 98, 'details', 1, 'TA', '60/7', 'Description', 'Description of functionality and application of the patch', '', 0, 1, 1, 0, '', ''),
(9, 140, 100, 'status_id', 2, 'SB', '', 'Status', 'Artifact Status', '', 0, 0, 1, 0, '', '1'),
(10, 140, 98, 'severity', 2, 'SB', '', 'Severity', 'Impact of the artifact on the system (Critical, Major,...)', '', 0, 0, 1, 0, '', '5'),
(11, 140, 98, 'release_id', 2, 'SB', '', 'Release', 'The release (global version number) impacted by the artifact', 'P', 0, 1, 1, 0, '', '100'),
(12, 140, 100, 'stage', 2, 'SB', '', 'Stage', 'Stage in the life cycle of the artifact', 'P', 0, 1, 1, 0, '', '100');

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_field_set'
-- 

DROP TABLE IF EXISTS artifact_field_set;
CREATE TABLE IF NOT EXISTS artifact_field_set (
  field_set_id int(11) unsigned NOT NULL auto_increment,
  group_artifact_id int(11) unsigned NOT NULL default '0',
  name text NOT NULL,
  description text NOT NULL,
  rank int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (field_set_id),
  KEY idx_fk_group_artifact_id (group_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_field_set'
-- 

INSERT INTO artifact_field_set (field_set_id, group_artifact_id, name, description, rank) VALUES (1, 1, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(2, 1, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(3, 2, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(4, 2, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(5, 3, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(6, 3, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(7, 4, 'fieldset_default_lbl_key', 'fieldset_default_desc_key', 10),
(8, 5, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(9, 5, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(10, 5, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(11, 101, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(12, 101, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(13, 102, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(14, 102, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(15, 103, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(16, 103, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(17, 104, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(18, 104, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(19, 104, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(20, 105, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(21, 105, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(22, 106, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(23, 106, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(24, 107, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(25, 107, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(26, 108, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(27, 108, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(28, 108, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(29, 109, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(30, 109, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(31, 110, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(32, 110, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(33, 111, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(34, 111, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(35, 112, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(36, 112, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(37, 112, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(38, 113, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(39, 113, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(40, 114, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(41, 114, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(42, 115, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(43, 115, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(44, 116, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(45, 116, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(46, 116, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(47, 117, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(48, 117, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(49, 118, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(50, 118, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(51, 119, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(52, 119, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(53, 120, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(54, 120, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(55, 120, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(56, 121, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(57, 121, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(58, 122, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(59, 122, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(60, 123, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(61, 123, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(62, 124, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(63, 124, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(64, 124, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(65, 125, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(66, 125, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(67, 126, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(68, 126, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(69, 127, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(70, 127, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(71, 128, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(72, 128, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(73, 128, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(74, 129, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(75, 129, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(76, 130, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(77, 130, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(78, 131, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(79, 131, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(80, 132, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(81, 132, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(82, 132, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(83, 133, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(84, 133, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(85, 134, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(86, 134, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(87, 135, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(88, 135, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(89, 136, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(90, 136, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(91, 136, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30),
(92, 137, 'fieldset_default_bugs_lbl_key', 'fieldset_default_bugs_desc_key', 10),
(93, 137, 'fieldset_status_bugs_lbl_key', 'fieldset_status_bugs_desc_key', 20),
(94, 138, 'fieldset_default_tasks_lbl_key', 'fieldset_default_tasks_desc_key', 10),
(95, 138, 'fieldset_status_tasks_lbl_key', 'fieldset_status_tasks_desc_key', 20),
(96, 139, 'fieldset_default_SR_lbl_key', 'fieldset_default_SR_desc_key', 10),
(97, 139, 'fieldset_status_SR_lbl_key', 'fieldset_status_SR_desc_key', 20),
(98, 140, 'fieldset_default_patches_lbl_key', 'fieldset_default_patches_desc_key', 10),
(99, 140, 'fieldset_patchtext_patches_lbl_key', 'fieldset_patchtext_patches_desc_key', 20),
(100, 140, 'fieldset_status_patches_lbl_key', 'fieldset_status_patches_desc_key', 30);

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_field_usage'
-- 

DROP TABLE IF EXISTS artifact_field_usage;
CREATE TABLE IF NOT EXISTS artifact_field_usage (
  field_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  use_it int(11) NOT NULL default '0',
  place int(11) default NULL,
  KEY idx_fk_field_id (field_id),
  KEY idx_fk_group_artifact_id (group_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_field_usage'
-- 

INSERT INTO artifact_field_usage (field_id, group_artifact_id, use_it, place) VALUES (7, 1, 1, 0),
(6, 1, 1, 0),
(5, 1, 1, 900),
(1, 1, 1, 0),
(4, 1, 1, 50),
(3, 1, 1, 10),
(2, 1, 1, 60),
(30, 1, 0, 0),
(8, 1, 1, 20),
(10, 1, 1, NULL),
(9, 1, 1, 1000),
(16, 1, 1, 40),
(20, 1, 1, 30),
(2, 2, 1, 20),
(3, 2, 1, 30),
(4, 2, 1, 40),
(5, 2, 1, 60),
(6, 2, 1, 80),
(7, 2, 1, 900),
(8, 2, 1, 1000),
(11, 2, 1, 50),
(15, 2, 0, 0),
(1, 2, 1, 1),
(10, 2, 1, 0),
(9, 3, 1, NULL),
(7, 3, 1, 30),
(12, 3, 0, 0),
(6, 3, 1, 20),
(5, 3, 1, 10),
(4, 3, 1, 5),
(3, 3, 1, 1000),
(2, 3, 1, 900),
(1, 3, 1, 1),
(9, 2, 1, 70),
(12, 2, 1, 10),
(11, 1, 0, 0),
(12, 1, 0, 0),
(13, 1, 0, 0),
(14, 1, 0, 0),
(15, 1, 0, 0),
(17, 1, 0, 0),
(18, 1, 0, 0),
(19, 1, 0, 0),
(22, 1, 0, 0),
(23, 1, 0, 0),
(24, 1, 0, 0),
(26, 1, 0, 0),
(27, 1, 0, 0),
(28, 1, 0, 0),
(29, 1, 0, 0),
(13, 2, 1, 0),
(14, 2, 1, 30),
(10, 3, 0, 0),
(11, 3, 1, 40),
(1, 4, 1, 0),
(2, 4, 1, 0),
(3, 4, 0, 0),
(4, 4, 1, 10),
(5, 4, 1, 0),
(6, 4, 0, 0),
(7, 4, 1, 0),
(8, 4, 1, 20),
(9, 4, 1, 30),
(10, 4, 0, 40),
(11, 4, 0, 0),
(1, 5, 1, 0),
(2, 5, 1, 0),
(3, 5, 1, 30),
(4, 5, 1, 0),
(5, 5, 1, 70),
(6, 5, 1, 0),
(7, 5, 1, 10),
(8, 5, 1, 50),
(9, 5, 1, 0),
(10, 5, 1, 0),
(11, 5, 0, 0),
(12, 5, 1, 0),
(7, 101, 1, 0),
(6, 101, 1, 0),
(5, 101, 1, 900),
(1, 101, 1, 0),
(4, 101, 1, 50),
(3, 101, 1, 10),
(2, 101, 1, 60),
(30, 101, 0, 0),
(8, 101, 1, 20),
(10, 101, 1, NULL),
(9, 101, 1, 1000),
(16, 101, 1, 40),
(20, 101, 1, 30),
(11, 101, 0, 0),
(12, 101, 0, 0),
(13, 101, 0, 0),
(14, 101, 0, 0),
(15, 101, 0, 0),
(17, 101, 0, 0),
(18, 101, 0, 0),
(19, 101, 0, 0),
(22, 101, 0, 0),
(23, 101, 0, 0),
(24, 101, 0, 0),
(26, 101, 0, 0),
(27, 101, 0, 0),
(28, 101, 0, 0),
(29, 101, 0, 0),
(2, 102, 1, 20),
(4, 102, 1, 40),
(5, 102, 1, 60),
(6, 102, 1, 80),
(7, 102, 1, 900),
(8, 102, 1, 1000),
(11, 102, 1, 50),
(15, 102, 0, 0),
(1, 102, 1, 1),
(10, 102, 1, 0),
(9, 102, 1, 70),
(12, 102, 1, 10),
(13, 102, 1, 0),
(14, 102, 1, 30),
(9, 103, 1, NULL),
(7, 103, 1, 30),
(12, 103, 0, 0),
(6, 103, 1, 20),
(5, 103, 1, 10),
(4, 103, 1, 5),
(3, 103, 1, 1000),
(2, 103, 1, 900),
(1, 103, 1, 1),
(10, 103, 0, 0),
(11, 103, 1, 40),
(1, 104, 1, 0),
(2, 104, 1, 0),
(3, 104, 1, 30),
(4, 104, 1, 0),
(5, 104, 1, 70),
(6, 104, 1, 0),
(7, 104, 1, 10),
(8, 104, 1, 50),
(9, 104, 1, 0),
(10, 104, 1, 0),
(11, 104, 0, 0),
(12, 104, 1, 0),
(7, 105, 1, 0),
(6, 105, 1, 0),
(5, 105, 1, 900),
(1, 105, 1, 0),
(4, 105, 1, 50),
(3, 105, 1, 10),
(2, 105, 1, 60),
(30, 105, 0, 0),
(8, 105, 1, 20),
(10, 105, 1, NULL),
(9, 105, 1, 1000),
(16, 105, 1, 40),
(20, 105, 1, 30),
(11, 105, 0, 0),
(12, 105, 0, 0),
(13, 105, 0, 0),
(14, 105, 0, 0),
(15, 105, 0, 0),
(17, 105, 0, 0),
(18, 105, 0, 0),
(19, 105, 0, 0),
(22, 105, 0, 0),
(23, 105, 0, 0),
(24, 105, 0, 0),
(26, 105, 0, 0),
(27, 105, 0, 0),
(28, 105, 0, 0),
(29, 105, 0, 0),
(2, 106, 1, 20),
(4, 106, 1, 40),
(5, 106, 1, 60),
(6, 106, 1, 80),
(7, 106, 1, 900),
(8, 106, 1, 1000),
(11, 106, 1, 50),
(15, 106, 0, 0),
(1, 106, 1, 1),
(10, 106, 1, 0),
(9, 106, 1, 70),
(12, 106, 1, 10),
(13, 106, 1, 0),
(14, 106, 1, 30),
(9, 107, 1, NULL),
(7, 107, 1, 30),
(12, 107, 0, 0),
(6, 107, 1, 20),
(5, 107, 1, 10),
(4, 107, 1, 5),
(3, 107, 1, 1000),
(2, 107, 1, 900),
(1, 107, 1, 1),
(10, 107, 0, 0),
(11, 107, 1, 40),
(1, 108, 1, 0),
(2, 108, 1, 0),
(3, 108, 1, 30),
(4, 108, 1, 0),
(5, 108, 1, 70),
(6, 108, 1, 0),
(7, 108, 1, 10),
(8, 108, 1, 50),
(9, 108, 1, 0),
(10, 108, 1, 0),
(11, 108, 0, 0),
(12, 108, 1, 0),
(7, 109, 1, 0),
(6, 109, 1, 0),
(5, 109, 1, 900),
(1, 109, 1, 0),
(4, 109, 1, 50),
(3, 109, 1, 10),
(2, 109, 1, 60),
(30, 109, 0, 0),
(8, 109, 1, 20),
(10, 109, 1, NULL),
(9, 109, 1, 1000),
(16, 109, 1, 40),
(20, 109, 1, 30),
(11, 109, 0, 0),
(12, 109, 0, 0),
(13, 109, 0, 0),
(14, 109, 0, 0),
(15, 109, 0, 0),
(17, 109, 0, 0),
(18, 109, 0, 0),
(19, 109, 0, 0),
(22, 109, 0, 0),
(23, 109, 0, 0),
(24, 109, 0, 0),
(26, 109, 0, 0),
(27, 109, 0, 0),
(28, 109, 0, 0),
(29, 109, 0, 0),
(2, 110, 1, 20),
(4, 110, 1, 40),
(5, 110, 1, 60),
(6, 110, 1, 80),
(7, 110, 1, 900),
(8, 110, 1, 1000),
(11, 110, 1, 50),
(15, 110, 0, 0),
(1, 110, 1, 1),
(10, 110, 1, 0),
(9, 110, 1, 70),
(12, 110, 1, 10),
(13, 110, 1, 0),
(14, 110, 1, 30),
(9, 111, 1, NULL),
(7, 111, 1, 30),
(12, 111, 0, 0),
(6, 111, 1, 20),
(5, 111, 1, 10),
(4, 111, 1, 5),
(3, 111, 1, 1000),
(2, 111, 1, 900),
(1, 111, 1, 1),
(10, 111, 0, 0),
(11, 111, 1, 40),
(1, 112, 1, 0),
(2, 112, 1, 0),
(3, 112, 1, 30),
(4, 112, 1, 0),
(5, 112, 1, 70),
(6, 112, 1, 0),
(7, 112, 1, 10),
(8, 112, 1, 50),
(9, 112, 1, 0),
(10, 112, 1, 0),
(11, 112, 0, 0),
(12, 112, 1, 0),
(7, 113, 1, 0),
(6, 113, 1, 0),
(5, 113, 1, 900),
(1, 113, 1, 0),
(4, 113, 1, 50),
(3, 113, 1, 10),
(2, 113, 1, 60),
(30, 113, 0, 0),
(8, 113, 1, 20),
(10, 113, 1, NULL),
(9, 113, 1, 1000),
(16, 113, 1, 40),
(20, 113, 1, 30),
(11, 113, 0, 0),
(12, 113, 0, 0),
(13, 113, 0, 0),
(14, 113, 0, 0),
(15, 113, 0, 0),
(17, 113, 0, 0),
(18, 113, 0, 0),
(19, 113, 0, 0),
(22, 113, 0, 0),
(23, 113, 0, 0),
(24, 113, 0, 0),
(26, 113, 0, 0),
(27, 113, 0, 0),
(28, 113, 0, 0),
(29, 113, 0, 0),
(2, 114, 1, 20),
(4, 114, 1, 40),
(5, 114, 1, 60),
(6, 114, 1, 80),
(7, 114, 1, 900),
(8, 114, 1, 1000),
(11, 114, 1, 50),
(15, 114, 0, 0),
(1, 114, 1, 1),
(10, 114, 1, 0),
(9, 114, 1, 70),
(12, 114, 1, 10),
(13, 114, 1, 0),
(14, 114, 1, 30),
(9, 115, 1, NULL),
(7, 115, 1, 30),
(12, 115, 0, 0),
(6, 115, 1, 20),
(5, 115, 1, 10),
(4, 115, 1, 5),
(3, 115, 1, 1000),
(2, 115, 1, 900),
(1, 115, 1, 1),
(10, 115, 0, 0),
(11, 115, 1, 40),
(1, 116, 1, 0),
(2, 116, 1, 0),
(3, 116, 1, 30),
(4, 116, 1, 0),
(5, 116, 1, 70),
(6, 116, 1, 0),
(7, 116, 1, 10),
(8, 116, 1, 50),
(9, 116, 1, 0),
(10, 116, 1, 0),
(11, 116, 0, 0),
(12, 116, 1, 0),
(7, 117, 1, 0),
(6, 117, 1, 0),
(5, 117, 1, 900),
(1, 117, 1, 0),
(4, 117, 1, 50),
(3, 117, 1, 10),
(2, 117, 1, 60),
(30, 117, 0, 0),
(8, 117, 1, 20),
(10, 117, 1, NULL),
(9, 117, 1, 1000),
(16, 117, 1, 40),
(20, 117, 1, 30),
(11, 117, 0, 0),
(12, 117, 0, 0),
(13, 117, 0, 0),
(14, 117, 0, 0),
(15, 117, 0, 0),
(17, 117, 0, 0),
(18, 117, 0, 0),
(19, 117, 0, 0),
(22, 117, 0, 0),
(23, 117, 0, 0),
(24, 117, 0, 0),
(26, 117, 0, 0),
(27, 117, 0, 0),
(28, 117, 0, 0),
(29, 117, 0, 0),
(2, 118, 1, 20),
(4, 118, 1, 40),
(5, 118, 1, 60),
(6, 118, 1, 80),
(7, 118, 1, 900),
(8, 118, 1, 1000),
(11, 118, 1, 50),
(15, 118, 0, 0),
(1, 118, 1, 1),
(10, 118, 1, 0),
(9, 118, 1, 70),
(12, 118, 1, 10),
(13, 118, 1, 0),
(14, 118, 1, 30),
(9, 119, 1, NULL),
(7, 119, 1, 30),
(12, 119, 0, 0),
(6, 119, 1, 20),
(5, 119, 1, 10),
(4, 119, 1, 5),
(3, 119, 1, 1000),
(2, 119, 1, 900),
(1, 119, 1, 1),
(10, 119, 0, 0),
(11, 119, 1, 40),
(1, 120, 1, 0),
(2, 120, 1, 0),
(3, 120, 1, 30),
(4, 120, 1, 0),
(5, 120, 1, 70),
(6, 120, 1, 0),
(7, 120, 1, 10),
(8, 120, 1, 50),
(9, 120, 1, 0),
(10, 120, 1, 0),
(11, 120, 0, 0),
(12, 120, 1, 0),
(7, 121, 1, 0),
(6, 121, 1, 0),
(5, 121, 1, 900),
(1, 121, 1, 0),
(4, 121, 1, 50),
(3, 121, 1, 10),
(2, 121, 1, 60),
(30, 121, 0, 0),
(8, 121, 1, 20),
(10, 121, 1, NULL),
(9, 121, 1, 1000),
(16, 121, 1, 40),
(20, 121, 1, 30),
(11, 121, 0, 0),
(12, 121, 0, 0),
(13, 121, 0, 0),
(14, 121, 0, 0),
(15, 121, 0, 0),
(17, 121, 0, 0),
(18, 121, 0, 0),
(19, 121, 0, 0),
(22, 121, 0, 0),
(23, 121, 0, 0),
(24, 121, 0, 0),
(26, 121, 0, 0),
(27, 121, 0, 0),
(28, 121, 0, 0),
(29, 121, 0, 0),
(2, 122, 1, 20),
(4, 122, 1, 40),
(5, 122, 1, 60),
(6, 122, 1, 80),
(7, 122, 1, 900),
(8, 122, 1, 1000),
(11, 122, 1, 50),
(15, 122, 0, 0),
(1, 122, 1, 1),
(10, 122, 1, 0),
(9, 122, 1, 70),
(12, 122, 1, 10),
(13, 122, 1, 0),
(14, 122, 1, 30),
(9, 123, 1, NULL),
(7, 123, 1, 30),
(12, 123, 0, 0),
(6, 123, 1, 20),
(5, 123, 1, 10),
(4, 123, 1, 5),
(3, 123, 1, 1000),
(2, 123, 1, 900),
(1, 123, 1, 1),
(10, 123, 0, 0),
(11, 123, 1, 40),
(1, 124, 1, 0),
(2, 124, 1, 0),
(3, 124, 1, 30),
(4, 124, 1, 0),
(5, 124, 1, 70),
(6, 124, 1, 0),
(7, 124, 1, 10),
(8, 124, 1, 50),
(9, 124, 1, 0),
(10, 124, 1, 0),
(11, 124, 0, 0),
(12, 124, 1, 0),
(7, 125, 1, 0),
(6, 125, 1, 0),
(5, 125, 1, 900),
(1, 125, 1, 0),
(4, 125, 1, 50),
(3, 125, 1, 10),
(2, 125, 1, 60),
(30, 125, 0, 0),
(8, 125, 1, 20),
(10, 125, 1, NULL),
(9, 125, 1, 1000),
(16, 125, 1, 40),
(20, 125, 1, 30),
(11, 125, 0, 0),
(12, 125, 0, 0),
(13, 125, 0, 0),
(14, 125, 0, 0),
(15, 125, 0, 0),
(17, 125, 0, 0),
(18, 125, 0, 0),
(19, 125, 0, 0),
(22, 125, 0, 0),
(23, 125, 0, 0),
(24, 125, 0, 0),
(26, 125, 0, 0),
(27, 125, 0, 0),
(28, 125, 0, 0),
(29, 125, 0, 0),
(2, 126, 1, 20),
(4, 126, 1, 40),
(5, 126, 1, 60),
(6, 126, 1, 80),
(7, 126, 1, 900),
(8, 126, 1, 1000),
(11, 126, 1, 50),
(15, 126, 0, 0),
(1, 126, 1, 1),
(10, 126, 1, 0),
(9, 126, 1, 70),
(12, 126, 1, 10),
(13, 126, 1, 0),
(14, 126, 1, 30),
(9, 127, 1, NULL),
(7, 127, 1, 30),
(12, 127, 0, 0),
(6, 127, 1, 20),
(5, 127, 1, 10),
(4, 127, 1, 5),
(3, 127, 1, 1000),
(2, 127, 1, 900),
(1, 127, 1, 1),
(10, 127, 0, 0),
(11, 127, 1, 40),
(1, 128, 1, 0),
(2, 128, 1, 0),
(3, 128, 1, 30),
(4, 128, 1, 0),
(5, 128, 1, 70),
(6, 128, 1, 0),
(7, 128, 1, 10),
(8, 128, 1, 50),
(9, 128, 1, 0),
(10, 128, 1, 0),
(11, 128, 0, 0),
(12, 128, 1, 0),
(7, 129, 1, 0),
(6, 129, 1, 0),
(5, 129, 1, 900),
(1, 129, 1, 0),
(4, 129, 1, 50),
(3, 129, 1, 10),
(2, 129, 1, 60),
(30, 129, 0, 0),
(8, 129, 1, 20),
(10, 129, 1, NULL),
(9, 129, 1, 1000),
(16, 129, 1, 40),
(20, 129, 1, 30),
(11, 129, 0, 0),
(12, 129, 0, 0),
(13, 129, 0, 0),
(14, 129, 0, 0),
(15, 129, 0, 0),
(17, 129, 0, 0),
(18, 129, 0, 0),
(19, 129, 0, 0),
(22, 129, 0, 0),
(23, 129, 0, 0),
(24, 129, 0, 0),
(26, 129, 0, 0),
(27, 129, 0, 0),
(28, 129, 0, 0),
(29, 129, 0, 0),
(2, 130, 1, 20),
(4, 130, 1, 40),
(5, 130, 1, 60),
(6, 130, 1, 80),
(7, 130, 1, 900),
(8, 130, 1, 1000),
(11, 130, 1, 50),
(15, 130, 0, 0),
(1, 130, 1, 1),
(10, 130, 1, 0),
(9, 130, 1, 70),
(12, 130, 1, 10),
(13, 130, 1, 0),
(14, 130, 1, 30),
(9, 131, 1, NULL),
(7, 131, 1, 30),
(12, 131, 0, 0),
(6, 131, 1, 20),
(5, 131, 1, 10),
(4, 131, 1, 5),
(3, 131, 1, 1000),
(2, 131, 1, 900),
(1, 131, 1, 1),
(10, 131, 0, 0),
(11, 131, 1, 40),
(1, 132, 1, 0),
(2, 132, 1, 0),
(3, 132, 1, 30),
(4, 132, 1, 0),
(5, 132, 1, 70),
(6, 132, 1, 0),
(7, 132, 1, 10),
(8, 132, 1, 50),
(9, 132, 1, 0),
(10, 132, 1, 0),
(11, 132, 0, 0),
(12, 132, 1, 0),
(7, 133, 1, 0),
(6, 133, 1, 0),
(5, 133, 1, 900),
(1, 133, 1, 0),
(4, 133, 1, 50),
(3, 133, 1, 10),
(2, 133, 1, 60),
(30, 133, 0, 0),
(8, 133, 1, 20),
(10, 133, 1, NULL),
(9, 133, 1, 1000),
(16, 133, 1, 40),
(20, 133, 1, 30),
(11, 133, 0, 0),
(12, 133, 0, 0),
(13, 133, 0, 0),
(14, 133, 0, 0),
(15, 133, 0, 0),
(17, 133, 0, 0),
(18, 133, 0, 0),
(19, 133, 0, 0),
(22, 133, 0, 0),
(23, 133, 0, 0),
(24, 133, 0, 0),
(26, 133, 0, 0),
(27, 133, 0, 0),
(28, 133, 0, 0),
(29, 133, 0, 0),
(2, 134, 1, 20),
(4, 134, 1, 40),
(5, 134, 1, 60),
(6, 134, 1, 80),
(7, 134, 1, 900),
(8, 134, 1, 1000),
(11, 134, 1, 50),
(15, 134, 0, 0),
(1, 134, 1, 1),
(10, 134, 1, 0),
(9, 134, 1, 70),
(12, 134, 1, 10),
(13, 134, 1, 0),
(14, 134, 1, 30),
(9, 135, 1, NULL),
(7, 135, 1, 30),
(12, 135, 0, 0),
(6, 135, 1, 20),
(5, 135, 1, 10),
(4, 135, 1, 5),
(3, 135, 1, 1000),
(2, 135, 1, 900),
(1, 135, 1, 1),
(10, 135, 0, 0),
(11, 135, 1, 40),
(1, 136, 1, 0),
(2, 136, 1, 0),
(3, 136, 1, 30),
(4, 136, 1, 0),
(5, 136, 1, 70),
(6, 136, 1, 0),
(7, 136, 1, 10),
(8, 136, 1, 50),
(9, 136, 1, 0),
(10, 136, 1, 0),
(11, 136, 0, 0),
(12, 136, 1, 0),
(7, 137, 1, 0),
(6, 137, 1, 0),
(5, 137, 1, 900),
(1, 137, 1, 0),
(4, 137, 1, 50),
(3, 137, 1, 10),
(2, 137, 1, 60),
(30, 137, 0, 0),
(8, 137, 1, 20),
(10, 137, 1, NULL),
(9, 137, 1, 1000),
(16, 137, 1, 40),
(20, 137, 1, 30),
(11, 137, 0, 0),
(12, 137, 0, 0),
(13, 137, 0, 0),
(14, 137, 0, 0),
(15, 137, 0, 0),
(17, 137, 0, 0),
(18, 137, 0, 0),
(19, 137, 0, 0),
(22, 137, 0, 0),
(23, 137, 0, 0),
(24, 137, 0, 0),
(26, 137, 0, 0),
(27, 137, 0, 0),
(28, 137, 0, 0),
(29, 137, 0, 0),
(2, 138, 1, 20),
(4, 138, 1, 40),
(5, 138, 1, 60),
(6, 138, 1, 80),
(7, 138, 1, 900),
(8, 138, 1, 1000),
(11, 138, 1, 50),
(15, 138, 0, 0),
(1, 138, 1, 1),
(10, 138, 1, 0),
(9, 138, 1, 70),
(12, 138, 1, 10),
(13, 138, 1, 0),
(14, 138, 1, 30),
(9, 139, 1, NULL),
(7, 139, 1, 30),
(12, 139, 0, 0),
(6, 139, 1, 20),
(5, 139, 1, 10),
(4, 139, 1, 5),
(3, 139, 1, 1000),
(2, 139, 1, 900),
(1, 139, 1, 1),
(10, 139, 0, 0),
(11, 139, 1, 40),
(1, 140, 1, 0),
(2, 140, 1, 0),
(3, 140, 1, 30),
(4, 140, 1, 0),
(5, 140, 1, 70),
(6, 140, 1, 0),
(7, 140, 1, 10),
(8, 140, 1, 50),
(9, 140, 1, 0),
(10, 140, 1, 0),
(11, 140, 0, 0),
(12, 140, 1, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_field_value'
-- 

DROP TABLE IF EXISTS artifact_field_value;
CREATE TABLE IF NOT EXISTS artifact_field_value (
  field_id int(11) NOT NULL default '0',
  artifact_id int(11) NOT NULL default '0',
  valueInt int(11) default NULL,
  valueText text,
  valueFloat float(10,4) default NULL,
  valueDate int(11) default NULL,
  KEY idx_field_id (field_id),
  KEY idx_artifact_id (artifact_id),
  KEY valueInt (valueInt)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_field_value'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_field_value_list'
-- 

DROP TABLE IF EXISTS artifact_field_value_list;
CREATE TABLE IF NOT EXISTS artifact_field_value_list (
  field_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  `value` text NOT NULL,
  description text NOT NULL,
  order_id int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (field_id,group_artifact_id,value_id),
  KEY idx_fv_group_artifact_id (group_artifact_id),
  KEY idx_fv_value_id (value_id),
  KEY idx_fv_status (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_field_value_list'
-- 

INSERT INTO artifact_field_value_list (field_id, group_artifact_id, value_id, value, description, order_id, status) VALUES (2, 1, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 1, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 1, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 1, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 1, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 1, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 1, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 1, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 1, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 1, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 1, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 1, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 1, 100, 'None', '', 10, 'P'),
(8, 1, 1, '1 - Ordinary', '', 10, 'P'),
(8, 1, 2, '2', '', 20, 'P'),
(8, 1, 3, '3', '', 30, 'P'),
(8, 1, 4, '4', '', 40, 'P'),
(8, 1, 5, '5 - Major', '', 50, 'P'),
(8, 1, 6, '6', '', 60, 'P'),
(8, 1, 7, '7', '', 70, 'P'),
(8, 1, 8, '8', '', 80, 'P'),
(8, 1, 9, '9 - Critical', '', 90, 'P'),
(10, 1, 100, 'None', '', 10, 'P'),
(16, 1, 100, 'None', '', 10, 'P'),
(16, 1, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 1, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 1, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 1, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 1, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 1, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 1, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 2, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 2, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 2, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 2, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 2, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 2, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 2, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 2, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 2, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 2, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 2, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 2, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 2, 100, 'None', '', 10, 'P'),
(2, 2, 1095, '95%', '', 95, 'A'),
(2, 2, 1090, '90%', '', 90, 'A'),
(2, 2, 1085, '85%', '', 85, 'A'),
(2, 2, 1080, '80%', '', 80, 'A'),
(2, 2, 1075, '75%', '', 75, 'A'),
(2, 2, 1070, '70%', '', 70, 'A'),
(2, 2, 1065, '65%', '', 65, 'A'),
(2, 2, 1060, '60%', '', 60, 'A'),
(2, 2, 1055, '55%', '', 55, 'A'),
(2, 2, 1050, '50%', '', 50, 'A'),
(2, 2, 1045, '45%', '', 45, 'A'),
(2, 2, 1040, '40%', '', 40, 'A'),
(2, 2, 1035, '35%', '', 35, 'A'),
(2, 2, 1030, '30%', '', 30, 'A'),
(2, 2, 1025, '25%', '', 25, 'A'),
(2, 2, 1020, '20%', '', 20, 'A'),
(2, 2, 1015, '15%', '', 15, 'A'),
(2, 2, 1010, '10%', '', 10, 'A'),
(2, 2, 1000, 'Not started', '', 0, 'A'),
(2, 2, 1100, '100%', '', 100, 'P'),
(7, 3, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 3, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 3, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 3, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 3, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 3, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 3, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 3, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 3, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 3, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 3, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 3, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 3, 100, 'None', '', 10, 'P'),
(8, 3, 1095, '95%', '', 95, 'A'),
(8, 3, 1090, '90%', '', 90, 'A'),
(8, 3, 1085, '85%', '', 85, 'A'),
(8, 3, 1080, '80%', '', 80, 'A'),
(8, 3, 1075, '75%', '', 75, 'A'),
(8, 3, 1070, '70%', '', 70, 'A'),
(8, 3, 1065, '65%', '', 65, 'A'),
(8, 3, 1060, '60%', '', 60, 'A'),
(8, 3, 1055, '55%', '', 55, 'A'),
(8, 3, 1050, '50%', '', 50, 'A'),
(8, 3, 1045, '45%', '', 45, 'A'),
(8, 3, 1040, '40%', '', 40, 'A'),
(8, 3, 1035, '35%', '', 35, 'A'),
(8, 3, 1030, '30%', '', 30, 'A'),
(8, 3, 1025, '25%', '', 25, 'A'),
(8, 3, 1020, '20%', '', 20, 'A'),
(8, 3, 1015, '15%', '', 15, 'A'),
(8, 3, 1010, '10%', '', 10, 'A'),
(8, 3, 1000, 'Not started', '', 0, 'A'),
(8, 3, 1100, '100%', '', 100, 'A'),
(11, 1, 100, 'None', '', 10, 'P'),
(12, 1, 100, 'None', '', 10, 'P'),
(13, 1, 100, 'None', '', 10, 'P'),
(14, 1, 100, 'None', '', 10, 'P'),
(15, 1, 100, 'None', '', 10, 'P'),
(18, 1, 100, 'None', '', 10, 'P'),
(20, 1, 100, 'None', '', 10, 'P'),
(22, 1, 100, 'None', '', 10, 'P'),
(24, 1, 100, 'None', '', 10, 'P'),
(14, 2, 1, '1 - Lowest', '', 10, 'P'),
(14, 2, 2, '2', '', 20, 'P'),
(14, 2, 3, '3', '', 30, 'P'),
(14, 2, 4, '4', '', 40, 'P'),
(14, 2, 5, '5 - Medium', '', 50, 'P'),
(14, 2, 6, '6', '', 60, 'P'),
(14, 2, 7, '7', '', 70, 'P'),
(14, 2, 8, '8', '', 80, 'P'),
(14, 2, 9, '9 - Highest', '', 90, 'P'),
(11, 3, 1, '1 - Lowest', '', 10, 'P'),
(11, 3, 2, '2', '', 20, 'P'),
(11, 3, 3, '3', '', 30, 'P'),
(11, 3, 4, '4', '', 40, 'P'),
(11, 3, 5, '5 - Medium', '', 50, 'P'),
(11, 3, 6, '6', '', 60, 'P'),
(11, 3, 7, '7', '', 70, 'P'),
(11, 3, 8, '8', '', 80, 'P'),
(11, 3, 9, '9 - Highest', '', 90, 'P'),
(6, 4, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(6, 4, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(11, 4, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(11, 4, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(11, 4, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(11, 4, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(11, 4, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(11, 4, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(11, 4, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(11, 4, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(11, 4, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(11, 4, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(7, 4, 1, '1 - Ordinary', '', 10, 'P'),
(7, 4, 2, '2', '', 20, 'P'),
(7, 4, 3, '3', '', 30, 'P'),
(7, 4, 4, '4', '', 40, 'P'),
(7, 4, 5, '5 - Major', '', 50, 'P'),
(7, 4, 6, '6', '', 60, 'P'),
(7, 4, 7, '7', '', 70, 'P'),
(7, 4, 8, '8', '', 80, 'P'),
(7, 4, 9, '9 - Critical', '', 90, 'P'),
(7, 5, 100, 'None', '', 10, 'P'),
(9, 5, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 5, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 5, 1, '1 - Ordinary', '', 10, 'P'),
(10, 5, 2, '2', '', 20, 'P'),
(10, 5, 3, '3', '', 30, 'P'),
(10, 5, 4, '4', '', 40, 'P'),
(10, 5, 5, '5 - Major', '', 50, 'P'),
(10, 5, 6, '6', '', 60, 'P'),
(10, 5, 7, '7', '', 70, 'P'),
(10, 5, 8, '8', '', 80, 'P'),
(10, 5, 9, '9 - Critical', '', 90, 'P'),
(12, 5, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 5, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 101, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 101, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 101, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 101, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 101, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 101, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 101, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 101, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 101, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 101, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 101, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 101, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 101, 100, 'None', '', 10, 'P'),
(8, 101, 1, '1 - Ordinary', '', 10, 'P'),
(8, 101, 2, '2', '', 20, 'P'),
(8, 101, 3, '3', '', 30, 'P'),
(8, 101, 4, '4', '', 40, 'P'),
(8, 101, 5, '5 - Major', '', 50, 'P'),
(8, 101, 6, '6', '', 60, 'P'),
(8, 101, 7, '7', '', 70, 'P'),
(8, 101, 8, '8', '', 80, 'P'),
(8, 101, 9, '9 - Critical', '', 90, 'P'),
(10, 101, 100, 'None', '', 10, 'P'),
(16, 101, 100, 'None', '', 10, 'P'),
(16, 101, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 101, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 101, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 101, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 101, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 101, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 101, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 101, 100, 'None', '', 10, 'P'),
(12, 101, 100, 'None', '', 10, 'P'),
(13, 101, 100, 'None', '', 10, 'P'),
(14, 101, 100, 'None', '', 10, 'P'),
(15, 101, 100, 'None', '', 10, 'P'),
(18, 101, 100, 'None', '', 10, 'P'),
(20, 101, 100, 'None', '', 10, 'P'),
(22, 101, 100, 'None', '', 10, 'P'),
(24, 101, 100, 'None', '', 10, 'P'),
(11, 102, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 102, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 102, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 102, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 102, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 102, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 102, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 102, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 102, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 102, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 102, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 102, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 102, 100, 'None', '', 10, 'P'),
(2, 102, 1095, '95%', '', 95, 'A'),
(2, 102, 1090, '90%', '', 90, 'A'),
(2, 102, 1085, '85%', '', 85, 'A'),
(2, 102, 1080, '80%', '', 80, 'A'),
(2, 102, 1075, '75%', '', 75, 'A'),
(2, 102, 1070, '70%', '', 70, 'A'),
(2, 102, 1065, '65%', '', 65, 'A'),
(2, 102, 1060, '60%', '', 60, 'A'),
(2, 102, 1055, '55%', '', 55, 'A'),
(2, 102, 1050, '50%', '', 50, 'A'),
(2, 102, 1045, '45%', '', 45, 'A'),
(2, 102, 1040, '40%', '', 40, 'A'),
(2, 102, 1035, '35%', '', 35, 'A'),
(2, 102, 1030, '30%', '', 30, 'A'),
(2, 102, 1025, '25%', '', 25, 'A'),
(2, 102, 1020, '20%', '', 20, 'A'),
(2, 102, 1015, '15%', '', 15, 'A'),
(2, 102, 1010, '10%', '', 10, 'A'),
(2, 102, 1000, 'Not started', '', 0, 'A'),
(2, 102, 1100, '100%', '', 100, 'P'),
(14, 102, 1, '1 - Lowest', '', 10, 'P'),
(14, 102, 2, '2', '', 20, 'P'),
(14, 102, 3, '3', '', 30, 'P'),
(14, 102, 4, '4', '', 40, 'P'),
(14, 102, 5, '5 - Medium', '', 50, 'P'),
(14, 102, 6, '6', '', 60, 'P'),
(14, 102, 7, '7', '', 70, 'P'),
(14, 102, 8, '8', '', 80, 'P'),
(14, 102, 9, '9 - Highest', '', 90, 'P'),
(7, 103, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 103, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 103, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 103, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 103, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 103, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 103, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 103, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 103, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 103, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 103, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 103, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 103, 100, 'None', '', 10, 'P'),
(8, 103, 1095, '95%', '', 95, 'A'),
(8, 103, 1090, '90%', '', 90, 'A'),
(8, 103, 1085, '85%', '', 85, 'A'),
(8, 103, 1080, '80%', '', 80, 'A'),
(8, 103, 1075, '75%', '', 75, 'A'),
(8, 103, 1070, '70%', '', 70, 'A'),
(8, 103, 1065, '65%', '', 65, 'A'),
(8, 103, 1060, '60%', '', 60, 'A'),
(8, 103, 1055, '55%', '', 55, 'A'),
(8, 103, 1050, '50%', '', 50, 'A'),
(8, 103, 1045, '45%', '', 45, 'A'),
(8, 103, 1040, '40%', '', 40, 'A'),
(8, 103, 1035, '35%', '', 35, 'A'),
(8, 103, 1030, '30%', '', 30, 'A'),
(8, 103, 1025, '25%', '', 25, 'A'),
(8, 103, 1020, '20%', '', 20, 'A'),
(8, 103, 1015, '15%', '', 15, 'A'),
(8, 103, 1010, '10%', '', 10, 'A'),
(8, 103, 1000, 'Not started', '', 0, 'A'),
(8, 103, 1100, '100%', '', 100, 'A'),
(11, 103, 1, '1 - Lowest', '', 10, 'P'),
(11, 103, 2, '2', '', 20, 'P'),
(11, 103, 3, '3', '', 30, 'P'),
(11, 103, 4, '4', '', 40, 'P'),
(11, 103, 5, '5 - Medium', '', 50, 'P'),
(11, 103, 6, '6', '', 60, 'P'),
(11, 103, 7, '7', '', 70, 'P'),
(11, 103, 8, '8', '', 80, 'P'),
(11, 103, 9, '9 - Highest', '', 90, 'P'),
(7, 104, 100, 'None', '', 10, 'P'),
(9, 104, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 104, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 104, 1, '1 - Ordinary', '', 10, 'P'),
(10, 104, 2, '2', '', 20, 'P'),
(10, 104, 3, '3', '', 30, 'P'),
(10, 104, 4, '4', '', 40, 'P'),
(10, 104, 5, '5 - Major', '', 50, 'P'),
(10, 104, 6, '6', '', 60, 'P'),
(10, 104, 7, '7', '', 70, 'P'),
(10, 104, 8, '8', '', 80, 'P'),
(10, 104, 9, '9 - Critical', '', 90, 'P'),
(12, 104, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 104, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 105, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 105, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 105, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 105, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 105, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 105, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 105, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 105, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 105, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 105, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 105, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 105, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 105, 100, 'None', '', 10, 'P'),
(8, 105, 1, '1 - Ordinary', '', 10, 'P'),
(8, 105, 2, '2', '', 20, 'P'),
(8, 105, 3, '3', '', 30, 'P'),
(8, 105, 4, '4', '', 40, 'P'),
(8, 105, 5, '5 - Major', '', 50, 'P'),
(8, 105, 6, '6', '', 60, 'P'),
(8, 105, 7, '7', '', 70, 'P'),
(8, 105, 8, '8', '', 80, 'P'),
(8, 105, 9, '9 - Critical', '', 90, 'P'),
(10, 105, 100, 'None', '', 10, 'P'),
(16, 105, 100, 'None', '', 10, 'P'),
(16, 105, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 105, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 105, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 105, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 105, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 105, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 105, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 105, 100, 'None', '', 10, 'P'),
(12, 105, 100, 'None', '', 10, 'P'),
(13, 105, 100, 'None', '', 10, 'P'),
(14, 105, 100, 'None', '', 10, 'P'),
(15, 105, 100, 'None', '', 10, 'P'),
(18, 105, 100, 'None', '', 10, 'P'),
(20, 105, 100, 'None', '', 10, 'P'),
(22, 105, 100, 'None', '', 10, 'P'),
(24, 105, 100, 'None', '', 10, 'P'),
(11, 106, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 106, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 106, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 106, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 106, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 106, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 106, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 106, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 106, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 106, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 106, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 106, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 106, 100, 'None', '', 10, 'P'),
(2, 106, 1095, '95%', '', 95, 'A'),
(2, 106, 1090, '90%', '', 90, 'A'),
(2, 106, 1085, '85%', '', 85, 'A'),
(2, 106, 1080, '80%', '', 80, 'A'),
(2, 106, 1075, '75%', '', 75, 'A'),
(2, 106, 1070, '70%', '', 70, 'A'),
(2, 106, 1065, '65%', '', 65, 'A'),
(2, 106, 1060, '60%', '', 60, 'A'),
(2, 106, 1055, '55%', '', 55, 'A'),
(2, 106, 1050, '50%', '', 50, 'A'),
(2, 106, 1045, '45%', '', 45, 'A'),
(2, 106, 1040, '40%', '', 40, 'A'),
(2, 106, 1035, '35%', '', 35, 'A'),
(2, 106, 1030, '30%', '', 30, 'A'),
(2, 106, 1025, '25%', '', 25, 'A'),
(2, 106, 1020, '20%', '', 20, 'A'),
(2, 106, 1015, '15%', '', 15, 'A'),
(2, 106, 1010, '10%', '', 10, 'A'),
(2, 106, 1000, 'Not started', '', 0, 'A'),
(2, 106, 1100, '100%', '', 100, 'P'),
(14, 106, 1, '1 - Lowest', '', 10, 'P'),
(14, 106, 2, '2', '', 20, 'P'),
(14, 106, 3, '3', '', 30, 'P'),
(14, 106, 4, '4', '', 40, 'P'),
(14, 106, 5, '5 - Medium', '', 50, 'P'),
(14, 106, 6, '6', '', 60, 'P'),
(14, 106, 7, '7', '', 70, 'P'),
(14, 106, 8, '8', '', 80, 'P'),
(14, 106, 9, '9 - Highest', '', 90, 'P'),
(7, 107, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 107, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 107, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 107, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 107, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 107, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 107, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 107, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 107, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 107, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 107, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 107, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 107, 100, 'None', '', 10, 'P'),
(8, 107, 1095, '95%', '', 95, 'A'),
(8, 107, 1090, '90%', '', 90, 'A'),
(8, 107, 1085, '85%', '', 85, 'A'),
(8, 107, 1080, '80%', '', 80, 'A'),
(8, 107, 1075, '75%', '', 75, 'A'),
(8, 107, 1070, '70%', '', 70, 'A'),
(8, 107, 1065, '65%', '', 65, 'A'),
(8, 107, 1060, '60%', '', 60, 'A'),
(8, 107, 1055, '55%', '', 55, 'A'),
(8, 107, 1050, '50%', '', 50, 'A'),
(8, 107, 1045, '45%', '', 45, 'A'),
(8, 107, 1040, '40%', '', 40, 'A'),
(8, 107, 1035, '35%', '', 35, 'A'),
(8, 107, 1030, '30%', '', 30, 'A'),
(8, 107, 1025, '25%', '', 25, 'A'),
(8, 107, 1020, '20%', '', 20, 'A'),
(8, 107, 1015, '15%', '', 15, 'A'),
(8, 107, 1010, '10%', '', 10, 'A'),
(8, 107, 1000, 'Not started', '', 0, 'A'),
(8, 107, 1100, '100%', '', 100, 'A'),
(11, 107, 1, '1 - Lowest', '', 10, 'P'),
(11, 107, 2, '2', '', 20, 'P'),
(11, 107, 3, '3', '', 30, 'P'),
(11, 107, 4, '4', '', 40, 'P'),
(11, 107, 5, '5 - Medium', '', 50, 'P'),
(11, 107, 6, '6', '', 60, 'P'),
(11, 107, 7, '7', '', 70, 'P'),
(11, 107, 8, '8', '', 80, 'P'),
(11, 107, 9, '9 - Highest', '', 90, 'P'),
(7, 108, 100, 'None', '', 10, 'P'),
(9, 108, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 108, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 108, 1, '1 - Ordinary', '', 10, 'P'),
(10, 108, 2, '2', '', 20, 'P'),
(10, 108, 3, '3', '', 30, 'P'),
(10, 108, 4, '4', '', 40, 'P'),
(10, 108, 5, '5 - Major', '', 50, 'P'),
(10, 108, 6, '6', '', 60, 'P'),
(10, 108, 7, '7', '', 70, 'P'),
(10, 108, 8, '8', '', 80, 'P'),
(10, 108, 9, '9 - Critical', '', 90, 'P'),
(12, 108, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 108, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 109, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 109, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 109, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 109, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 109, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 109, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 109, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 109, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 109, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 109, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 109, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 109, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 109, 100, 'None', '', 10, 'P'),
(8, 109, 1, '1 - Ordinary', '', 10, 'P'),
(8, 109, 2, '2', '', 20, 'P'),
(8, 109, 3, '3', '', 30, 'P'),
(8, 109, 4, '4', '', 40, 'P'),
(8, 109, 5, '5 - Major', '', 50, 'P'),
(8, 109, 6, '6', '', 60, 'P'),
(8, 109, 7, '7', '', 70, 'P'),
(8, 109, 8, '8', '', 80, 'P'),
(8, 109, 9, '9 - Critical', '', 90, 'P'),
(10, 109, 100, 'None', '', 10, 'P'),
(16, 109, 100, 'None', '', 10, 'P'),
(16, 109, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 109, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 109, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 109, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 109, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 109, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 109, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 109, 100, 'None', '', 10, 'P'),
(12, 109, 100, 'None', '', 10, 'P'),
(13, 109, 100, 'None', '', 10, 'P'),
(14, 109, 100, 'None', '', 10, 'P'),
(15, 109, 100, 'None', '', 10, 'P'),
(18, 109, 100, 'None', '', 10, 'P'),
(20, 109, 100, 'None', '', 10, 'P'),
(22, 109, 100, 'None', '', 10, 'P'),
(24, 109, 100, 'None', '', 10, 'P'),
(11, 110, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 110, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 110, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 110, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 110, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 110, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 110, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 110, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 110, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 110, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 110, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 110, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 110, 100, 'None', '', 10, 'P'),
(2, 110, 1095, '95%', '', 95, 'A'),
(2, 110, 1090, '90%', '', 90, 'A'),
(2, 110, 1085, '85%', '', 85, 'A'),
(2, 110, 1080, '80%', '', 80, 'A'),
(2, 110, 1075, '75%', '', 75, 'A'),
(2, 110, 1070, '70%', '', 70, 'A'),
(2, 110, 1065, '65%', '', 65, 'A'),
(2, 110, 1060, '60%', '', 60, 'A'),
(2, 110, 1055, '55%', '', 55, 'A'),
(2, 110, 1050, '50%', '', 50, 'A'),
(2, 110, 1045, '45%', '', 45, 'A'),
(2, 110, 1040, '40%', '', 40, 'A'),
(2, 110, 1035, '35%', '', 35, 'A'),
(2, 110, 1030, '30%', '', 30, 'A'),
(2, 110, 1025, '25%', '', 25, 'A'),
(2, 110, 1020, '20%', '', 20, 'A'),
(2, 110, 1015, '15%', '', 15, 'A'),
(2, 110, 1010, '10%', '', 10, 'A'),
(2, 110, 1000, 'Not started', '', 0, 'A'),
(2, 110, 1100, '100%', '', 100, 'P'),
(14, 110, 1, '1 - Lowest', '', 10, 'P'),
(14, 110, 2, '2', '', 20, 'P'),
(14, 110, 3, '3', '', 30, 'P'),
(14, 110, 4, '4', '', 40, 'P'),
(14, 110, 5, '5 - Medium', '', 50, 'P'),
(14, 110, 6, '6', '', 60, 'P'),
(14, 110, 7, '7', '', 70, 'P'),
(14, 110, 8, '8', '', 80, 'P'),
(14, 110, 9, '9 - Highest', '', 90, 'P'),
(7, 111, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 111, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 111, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 111, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 111, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 111, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 111, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 111, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 111, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 111, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 111, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 111, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 111, 100, 'None', '', 10, 'P'),
(8, 111, 1095, '95%', '', 95, 'A'),
(8, 111, 1090, '90%', '', 90, 'A'),
(8, 111, 1085, '85%', '', 85, 'A'),
(8, 111, 1080, '80%', '', 80, 'A'),
(8, 111, 1075, '75%', '', 75, 'A'),
(8, 111, 1070, '70%', '', 70, 'A'),
(8, 111, 1065, '65%', '', 65, 'A'),
(8, 111, 1060, '60%', '', 60, 'A'),
(8, 111, 1055, '55%', '', 55, 'A'),
(8, 111, 1050, '50%', '', 50, 'A'),
(8, 111, 1045, '45%', '', 45, 'A'),
(8, 111, 1040, '40%', '', 40, 'A'),
(8, 111, 1035, '35%', '', 35, 'A'),
(8, 111, 1030, '30%', '', 30, 'A'),
(8, 111, 1025, '25%', '', 25, 'A'),
(8, 111, 1020, '20%', '', 20, 'A'),
(8, 111, 1015, '15%', '', 15, 'A'),
(8, 111, 1010, '10%', '', 10, 'A'),
(8, 111, 1000, 'Not started', '', 0, 'A'),
(8, 111, 1100, '100%', '', 100, 'A'),
(11, 111, 1, '1 - Lowest', '', 10, 'P'),
(11, 111, 2, '2', '', 20, 'P'),
(11, 111, 3, '3', '', 30, 'P'),
(11, 111, 4, '4', '', 40, 'P'),
(11, 111, 5, '5 - Medium', '', 50, 'P'),
(11, 111, 6, '6', '', 60, 'P'),
(11, 111, 7, '7', '', 70, 'P'),
(11, 111, 8, '8', '', 80, 'P'),
(11, 111, 9, '9 - Highest', '', 90, 'P'),
(7, 112, 100, 'None', '', 10, 'P'),
(9, 112, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 112, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 112, 1, '1 - Ordinary', '', 10, 'P'),
(10, 112, 2, '2', '', 20, 'P'),
(10, 112, 3, '3', '', 30, 'P'),
(10, 112, 4, '4', '', 40, 'P'),
(10, 112, 5, '5 - Major', '', 50, 'P'),
(10, 112, 6, '6', '', 60, 'P'),
(10, 112, 7, '7', '', 70, 'P'),
(10, 112, 8, '8', '', 80, 'P'),
(10, 112, 9, '9 - Critical', '', 90, 'P'),
(12, 112, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 112, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 113, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 113, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 113, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 113, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 113, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 113, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 113, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 113, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 113, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 113, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 113, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 113, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 113, 100, 'None', '', 10, 'P'),
(8, 113, 1, '1 - Ordinary', '', 10, 'P'),
(8, 113, 2, '2', '', 20, 'P'),
(8, 113, 3, '3', '', 30, 'P'),
(8, 113, 4, '4', '', 40, 'P'),
(8, 113, 5, '5 - Major', '', 50, 'P'),
(8, 113, 6, '6', '', 60, 'P'),
(8, 113, 7, '7', '', 70, 'P'),
(8, 113, 8, '8', '', 80, 'P'),
(8, 113, 9, '9 - Critical', '', 90, 'P'),
(10, 113, 100, 'None', '', 10, 'P'),
(16, 113, 100, 'None', '', 10, 'P'),
(16, 113, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 113, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 113, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 113, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 113, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 113, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 113, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 113, 100, 'None', '', 10, 'P'),
(12, 113, 100, 'None', '', 10, 'P'),
(13, 113, 100, 'None', '', 10, 'P'),
(14, 113, 100, 'None', '', 10, 'P'),
(15, 113, 100, 'None', '', 10, 'P'),
(18, 113, 100, 'None', '', 10, 'P'),
(20, 113, 100, 'None', '', 10, 'P'),
(22, 113, 100, 'None', '', 10, 'P'),
(24, 113, 100, 'None', '', 10, 'P'),
(11, 114, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 114, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 114, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 114, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 114, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 114, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 114, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 114, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 114, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 114, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 114, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 114, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 114, 100, 'None', '', 10, 'P'),
(2, 114, 1095, '95%', '', 95, 'A'),
(2, 114, 1090, '90%', '', 90, 'A'),
(2, 114, 1085, '85%', '', 85, 'A'),
(2, 114, 1080, '80%', '', 80, 'A'),
(2, 114, 1075, '75%', '', 75, 'A'),
(2, 114, 1070, '70%', '', 70, 'A'),
(2, 114, 1065, '65%', '', 65, 'A'),
(2, 114, 1060, '60%', '', 60, 'A'),
(2, 114, 1055, '55%', '', 55, 'A'),
(2, 114, 1050, '50%', '', 50, 'A'),
(2, 114, 1045, '45%', '', 45, 'A'),
(2, 114, 1040, '40%', '', 40, 'A'),
(2, 114, 1035, '35%', '', 35, 'A'),
(2, 114, 1030, '30%', '', 30, 'A'),
(2, 114, 1025, '25%', '', 25, 'A'),
(2, 114, 1020, '20%', '', 20, 'A'),
(2, 114, 1015, '15%', '', 15, 'A'),
(2, 114, 1010, '10%', '', 10, 'A'),
(2, 114, 1000, 'Not started', '', 0, 'A'),
(2, 114, 1100, '100%', '', 100, 'P'),
(14, 114, 1, '1 - Lowest', '', 10, 'P'),
(14, 114, 2, '2', '', 20, 'P'),
(14, 114, 3, '3', '', 30, 'P'),
(14, 114, 4, '4', '', 40, 'P'),
(14, 114, 5, '5 - Medium', '', 50, 'P'),
(14, 114, 6, '6', '', 60, 'P'),
(14, 114, 7, '7', '', 70, 'P'),
(14, 114, 8, '8', '', 80, 'P'),
(14, 114, 9, '9 - Highest', '', 90, 'P'),
(7, 115, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 115, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 115, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 115, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 115, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 115, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 115, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 115, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 115, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 115, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 115, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 115, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 115, 100, 'None', '', 10, 'P'),
(8, 115, 1095, '95%', '', 95, 'A'),
(8, 115, 1090, '90%', '', 90, 'A'),
(8, 115, 1085, '85%', '', 85, 'A'),
(8, 115, 1080, '80%', '', 80, 'A'),
(8, 115, 1075, '75%', '', 75, 'A'),
(8, 115, 1070, '70%', '', 70, 'A'),
(8, 115, 1065, '65%', '', 65, 'A'),
(8, 115, 1060, '60%', '', 60, 'A'),
(8, 115, 1055, '55%', '', 55, 'A'),
(8, 115, 1050, '50%', '', 50, 'A'),
(8, 115, 1045, '45%', '', 45, 'A'),
(8, 115, 1040, '40%', '', 40, 'A'),
(8, 115, 1035, '35%', '', 35, 'A'),
(8, 115, 1030, '30%', '', 30, 'A'),
(8, 115, 1025, '25%', '', 25, 'A'),
(8, 115, 1020, '20%', '', 20, 'A'),
(8, 115, 1015, '15%', '', 15, 'A'),
(8, 115, 1010, '10%', '', 10, 'A'),
(8, 115, 1000, 'Not started', '', 0, 'A'),
(8, 115, 1100, '100%', '', 100, 'A'),
(11, 115, 1, '1 - Lowest', '', 10, 'P'),
(11, 115, 2, '2', '', 20, 'P'),
(11, 115, 3, '3', '', 30, 'P'),
(11, 115, 4, '4', '', 40, 'P'),
(11, 115, 5, '5 - Medium', '', 50, 'P'),
(11, 115, 6, '6', '', 60, 'P'),
(11, 115, 7, '7', '', 70, 'P'),
(11, 115, 8, '8', '', 80, 'P'),
(11, 115, 9, '9 - Highest', '', 90, 'P'),
(7, 116, 100, 'None', '', 10, 'P'),
(9, 116, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 116, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 116, 1, '1 - Ordinary', '', 10, 'P'),
(10, 116, 2, '2', '', 20, 'P'),
(10, 116, 3, '3', '', 30, 'P'),
(10, 116, 4, '4', '', 40, 'P'),
(10, 116, 5, '5 - Major', '', 50, 'P'),
(10, 116, 6, '6', '', 60, 'P'),
(10, 116, 7, '7', '', 70, 'P'),
(10, 116, 8, '8', '', 80, 'P'),
(10, 116, 9, '9 - Critical', '', 90, 'P'),
(12, 116, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 116, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 117, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 117, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 117, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 117, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 117, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 117, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 117, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 117, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 117, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 117, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 117, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 117, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 117, 100, 'None', '', 10, 'P'),
(8, 117, 1, '1 - Ordinary', '', 10, 'P'),
(8, 117, 2, '2', '', 20, 'P'),
(8, 117, 3, '3', '', 30, 'P'),
(8, 117, 4, '4', '', 40, 'P'),
(8, 117, 5, '5 - Major', '', 50, 'P'),
(8, 117, 6, '6', '', 60, 'P'),
(8, 117, 7, '7', '', 70, 'P'),
(8, 117, 8, '8', '', 80, 'P'),
(8, 117, 9, '9 - Critical', '', 90, 'P'),
(10, 117, 100, 'None', '', 10, 'P'),
(16, 117, 100, 'None', '', 10, 'P'),
(16, 117, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 117, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 117, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 117, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 117, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 117, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 117, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 117, 100, 'None', '', 10, 'P'),
(12, 117, 100, 'None', '', 10, 'P'),
(13, 117, 100, 'None', '', 10, 'P'),
(14, 117, 100, 'None', '', 10, 'P'),
(15, 117, 100, 'None', '', 10, 'P'),
(18, 117, 100, 'None', '', 10, 'P'),
(20, 117, 100, 'None', '', 10, 'P'),
(22, 117, 100, 'None', '', 10, 'P'),
(24, 117, 100, 'None', '', 10, 'P'),
(11, 118, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 118, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 118, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 118, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 118, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 118, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 118, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 118, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 118, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 118, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 118, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 118, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 118, 100, 'None', '', 10, 'P'),
(2, 118, 1095, '95%', '', 95, 'A'),
(2, 118, 1090, '90%', '', 90, 'A'),
(2, 118, 1085, '85%', '', 85, 'A'),
(2, 118, 1080, '80%', '', 80, 'A'),
(2, 118, 1075, '75%', '', 75, 'A'),
(2, 118, 1070, '70%', '', 70, 'A'),
(2, 118, 1065, '65%', '', 65, 'A'),
(2, 118, 1060, '60%', '', 60, 'A'),
(2, 118, 1055, '55%', '', 55, 'A'),
(2, 118, 1050, '50%', '', 50, 'A'),
(2, 118, 1045, '45%', '', 45, 'A'),
(2, 118, 1040, '40%', '', 40, 'A'),
(2, 118, 1035, '35%', '', 35, 'A'),
(2, 118, 1030, '30%', '', 30, 'A'),
(2, 118, 1025, '25%', '', 25, 'A'),
(2, 118, 1020, '20%', '', 20, 'A'),
(2, 118, 1015, '15%', '', 15, 'A'),
(2, 118, 1010, '10%', '', 10, 'A'),
(2, 118, 1000, 'Not started', '', 0, 'A'),
(2, 118, 1100, '100%', '', 100, 'P'),
(14, 118, 1, '1 - Lowest', '', 10, 'P'),
(14, 118, 2, '2', '', 20, 'P'),
(14, 118, 3, '3', '', 30, 'P'),
(14, 118, 4, '4', '', 40, 'P'),
(14, 118, 5, '5 - Medium', '', 50, 'P'),
(14, 118, 6, '6', '', 60, 'P'),
(14, 118, 7, '7', '', 70, 'P'),
(14, 118, 8, '8', '', 80, 'P'),
(14, 118, 9, '9 - Highest', '', 90, 'P'),
(7, 119, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 119, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 119, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 119, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 119, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 119, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 119, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 119, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 119, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 119, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 119, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 119, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 119, 100, 'None', '', 10, 'P'),
(8, 119, 1095, '95%', '', 95, 'A'),
(8, 119, 1090, '90%', '', 90, 'A'),
(8, 119, 1085, '85%', '', 85, 'A'),
(8, 119, 1080, '80%', '', 80, 'A'),
(8, 119, 1075, '75%', '', 75, 'A'),
(8, 119, 1070, '70%', '', 70, 'A'),
(8, 119, 1065, '65%', '', 65, 'A'),
(8, 119, 1060, '60%', '', 60, 'A'),
(8, 119, 1055, '55%', '', 55, 'A'),
(8, 119, 1050, '50%', '', 50, 'A'),
(8, 119, 1045, '45%', '', 45, 'A'),
(8, 119, 1040, '40%', '', 40, 'A'),
(8, 119, 1035, '35%', '', 35, 'A'),
(8, 119, 1030, '30%', '', 30, 'A'),
(8, 119, 1025, '25%', '', 25, 'A'),
(8, 119, 1020, '20%', '', 20, 'A'),
(8, 119, 1015, '15%', '', 15, 'A'),
(8, 119, 1010, '10%', '', 10, 'A'),
(8, 119, 1000, 'Not started', '', 0, 'A'),
(8, 119, 1100, '100%', '', 100, 'A'),
(11, 119, 1, '1 - Lowest', '', 10, 'P'),
(11, 119, 2, '2', '', 20, 'P'),
(11, 119, 3, '3', '', 30, 'P'),
(11, 119, 4, '4', '', 40, 'P'),
(11, 119, 5, '5 - Medium', '', 50, 'P'),
(11, 119, 6, '6', '', 60, 'P'),
(11, 119, 7, '7', '', 70, 'P'),
(11, 119, 8, '8', '', 80, 'P'),
(11, 119, 9, '9 - Highest', '', 90, 'P'),
(7, 120, 100, 'None', '', 10, 'P'),
(9, 120, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 120, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 120, 1, '1 - Ordinary', '', 10, 'P'),
(10, 120, 2, '2', '', 20, 'P'),
(10, 120, 3, '3', '', 30, 'P'),
(10, 120, 4, '4', '', 40, 'P'),
(10, 120, 5, '5 - Major', '', 50, 'P'),
(10, 120, 6, '6', '', 60, 'P'),
(10, 120, 7, '7', '', 70, 'P'),
(10, 120, 8, '8', '', 80, 'P'),
(10, 120, 9, '9 - Critical', '', 90, 'P'),
(12, 120, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 120, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 121, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 121, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 121, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 121, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 121, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 121, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 121, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 121, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 121, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 121, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 121, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 121, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 121, 100, 'None', '', 10, 'P'),
(8, 121, 1, '1 - Ordinary', '', 10, 'P'),
(8, 121, 2, '2', '', 20, 'P'),
(8, 121, 3, '3', '', 30, 'P'),
(8, 121, 4, '4', '', 40, 'P'),
(8, 121, 5, '5 - Major', '', 50, 'P'),
(8, 121, 6, '6', '', 60, 'P'),
(8, 121, 7, '7', '', 70, 'P'),
(8, 121, 8, '8', '', 80, 'P'),
(8, 121, 9, '9 - Critical', '', 90, 'P'),
(10, 121, 100, 'None', '', 10, 'P'),
(16, 121, 100, 'None', '', 10, 'P'),
(16, 121, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 121, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 121, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 121, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 121, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 121, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 121, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 121, 100, 'None', '', 10, 'P'),
(12, 121, 100, 'None', '', 10, 'P'),
(13, 121, 100, 'None', '', 10, 'P'),
(14, 121, 100, 'None', '', 10, 'P'),
(15, 121, 100, 'None', '', 10, 'P'),
(18, 121, 100, 'None', '', 10, 'P'),
(20, 121, 100, 'None', '', 10, 'P'),
(22, 121, 100, 'None', '', 10, 'P'),
(24, 121, 100, 'None', '', 10, 'P'),
(11, 122, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 122, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 122, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 122, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 122, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 122, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 122, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 122, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 122, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 122, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 122, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 122, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 122, 100, 'None', '', 10, 'P'),
(2, 122, 1095, '95%', '', 95, 'A'),
(2, 122, 1090, '90%', '', 90, 'A'),
(2, 122, 1085, '85%', '', 85, 'A'),
(2, 122, 1080, '80%', '', 80, 'A'),
(2, 122, 1075, '75%', '', 75, 'A'),
(2, 122, 1070, '70%', '', 70, 'A'),
(2, 122, 1065, '65%', '', 65, 'A'),
(2, 122, 1060, '60%', '', 60, 'A'),
(2, 122, 1055, '55%', '', 55, 'A'),
(2, 122, 1050, '50%', '', 50, 'A'),
(2, 122, 1045, '45%', '', 45, 'A'),
(2, 122, 1040, '40%', '', 40, 'A'),
(2, 122, 1035, '35%', '', 35, 'A'),
(2, 122, 1030, '30%', '', 30, 'A'),
(2, 122, 1025, '25%', '', 25, 'A');
INSERT INTO artifact_field_value_list (field_id, group_artifact_id, value_id, value, description, order_id, status) VALUES (2, 122, 1020, '20%', '', 20, 'A'),
(2, 122, 1015, '15%', '', 15, 'A'),
(2, 122, 1010, '10%', '', 10, 'A'),
(2, 122, 1000, 'Not started', '', 0, 'A'),
(2, 122, 1100, '100%', '', 100, 'P'),
(14, 122, 1, '1 - Lowest', '', 10, 'P'),
(14, 122, 2, '2', '', 20, 'P'),
(14, 122, 3, '3', '', 30, 'P'),
(14, 122, 4, '4', '', 40, 'P'),
(14, 122, 5, '5 - Medium', '', 50, 'P'),
(14, 122, 6, '6', '', 60, 'P'),
(14, 122, 7, '7', '', 70, 'P'),
(14, 122, 8, '8', '', 80, 'P'),
(14, 122, 9, '9 - Highest', '', 90, 'P'),
(7, 123, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 123, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 123, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 123, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 123, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 123, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 123, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 123, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 123, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 123, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 123, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 123, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 123, 100, 'None', '', 10, 'P'),
(8, 123, 1095, '95%', '', 95, 'A'),
(8, 123, 1090, '90%', '', 90, 'A'),
(8, 123, 1085, '85%', '', 85, 'A'),
(8, 123, 1080, '80%', '', 80, 'A'),
(8, 123, 1075, '75%', '', 75, 'A'),
(8, 123, 1070, '70%', '', 70, 'A'),
(8, 123, 1065, '65%', '', 65, 'A'),
(8, 123, 1060, '60%', '', 60, 'A'),
(8, 123, 1055, '55%', '', 55, 'A'),
(8, 123, 1050, '50%', '', 50, 'A'),
(8, 123, 1045, '45%', '', 45, 'A'),
(8, 123, 1040, '40%', '', 40, 'A'),
(8, 123, 1035, '35%', '', 35, 'A'),
(8, 123, 1030, '30%', '', 30, 'A'),
(8, 123, 1025, '25%', '', 25, 'A'),
(8, 123, 1020, '20%', '', 20, 'A'),
(8, 123, 1015, '15%', '', 15, 'A'),
(8, 123, 1010, '10%', '', 10, 'A'),
(8, 123, 1000, 'Not started', '', 0, 'A'),
(8, 123, 1100, '100%', '', 100, 'A'),
(11, 123, 1, '1 - Lowest', '', 10, 'P'),
(11, 123, 2, '2', '', 20, 'P'),
(11, 123, 3, '3', '', 30, 'P'),
(11, 123, 4, '4', '', 40, 'P'),
(11, 123, 5, '5 - Medium', '', 50, 'P'),
(11, 123, 6, '6', '', 60, 'P'),
(11, 123, 7, '7', '', 70, 'P'),
(11, 123, 8, '8', '', 80, 'P'),
(11, 123, 9, '9 - Highest', '', 90, 'P'),
(7, 124, 100, 'None', '', 10, 'P'),
(9, 124, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 124, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 124, 1, '1 - Ordinary', '', 10, 'P'),
(10, 124, 2, '2', '', 20, 'P'),
(10, 124, 3, '3', '', 30, 'P'),
(10, 124, 4, '4', '', 40, 'P'),
(10, 124, 5, '5 - Major', '', 50, 'P'),
(10, 124, 6, '6', '', 60, 'P'),
(10, 124, 7, '7', '', 70, 'P'),
(10, 124, 8, '8', '', 80, 'P'),
(10, 124, 9, '9 - Critical', '', 90, 'P'),
(12, 124, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 124, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 125, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 125, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 125, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 125, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 125, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 125, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 125, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 125, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 125, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 125, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 125, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 125, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 125, 100, 'None', '', 10, 'P'),
(8, 125, 1, '1 - Ordinary', '', 10, 'P'),
(8, 125, 2, '2', '', 20, 'P'),
(8, 125, 3, '3', '', 30, 'P'),
(8, 125, 4, '4', '', 40, 'P'),
(8, 125, 5, '5 - Major', '', 50, 'P'),
(8, 125, 6, '6', '', 60, 'P'),
(8, 125, 7, '7', '', 70, 'P'),
(8, 125, 8, '8', '', 80, 'P'),
(8, 125, 9, '9 - Critical', '', 90, 'P'),
(10, 125, 100, 'None', '', 10, 'P'),
(16, 125, 100, 'None', '', 10, 'P'),
(16, 125, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 125, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 125, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 125, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 125, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 125, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 125, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 125, 100, 'None', '', 10, 'P'),
(12, 125, 100, 'None', '', 10, 'P'),
(13, 125, 100, 'None', '', 10, 'P'),
(14, 125, 100, 'None', '', 10, 'P'),
(15, 125, 100, 'None', '', 10, 'P'),
(18, 125, 100, 'None', '', 10, 'P'),
(20, 125, 100, 'None', '', 10, 'P'),
(22, 125, 100, 'None', '', 10, 'P'),
(24, 125, 100, 'None', '', 10, 'P'),
(11, 126, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 126, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 126, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 126, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 126, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 126, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 126, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 126, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 126, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 126, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 126, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 126, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 126, 100, 'None', '', 10, 'P'),
(2, 126, 1095, '95%', '', 95, 'A'),
(2, 126, 1090, '90%', '', 90, 'A'),
(2, 126, 1085, '85%', '', 85, 'A'),
(2, 126, 1080, '80%', '', 80, 'A'),
(2, 126, 1075, '75%', '', 75, 'A'),
(2, 126, 1070, '70%', '', 70, 'A'),
(2, 126, 1065, '65%', '', 65, 'A'),
(2, 126, 1060, '60%', '', 60, 'A'),
(2, 126, 1055, '55%', '', 55, 'A'),
(2, 126, 1050, '50%', '', 50, 'A'),
(2, 126, 1045, '45%', '', 45, 'A'),
(2, 126, 1040, '40%', '', 40, 'A'),
(2, 126, 1035, '35%', '', 35, 'A'),
(2, 126, 1030, '30%', '', 30, 'A'),
(2, 126, 1025, '25%', '', 25, 'A'),
(2, 126, 1020, '20%', '', 20, 'A'),
(2, 126, 1015, '15%', '', 15, 'A'),
(2, 126, 1010, '10%', '', 10, 'A'),
(2, 126, 1000, 'Not started', '', 0, 'A'),
(2, 126, 1100, '100%', '', 100, 'P'),
(14, 126, 1, '1 - Lowest', '', 10, 'P'),
(14, 126, 2, '2', '', 20, 'P'),
(14, 126, 3, '3', '', 30, 'P'),
(14, 126, 4, '4', '', 40, 'P'),
(14, 126, 5, '5 - Medium', '', 50, 'P'),
(14, 126, 6, '6', '', 60, 'P'),
(14, 126, 7, '7', '', 70, 'P'),
(14, 126, 8, '8', '', 80, 'P'),
(14, 126, 9, '9 - Highest', '', 90, 'P'),
(7, 127, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 127, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 127, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 127, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 127, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 127, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 127, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 127, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 127, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 127, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 127, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 127, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 127, 100, 'None', '', 10, 'P'),
(8, 127, 1095, '95%', '', 95, 'A'),
(8, 127, 1090, '90%', '', 90, 'A'),
(8, 127, 1085, '85%', '', 85, 'A'),
(8, 127, 1080, '80%', '', 80, 'A'),
(8, 127, 1075, '75%', '', 75, 'A'),
(8, 127, 1070, '70%', '', 70, 'A'),
(8, 127, 1065, '65%', '', 65, 'A'),
(8, 127, 1060, '60%', '', 60, 'A'),
(8, 127, 1055, '55%', '', 55, 'A'),
(8, 127, 1050, '50%', '', 50, 'A'),
(8, 127, 1045, '45%', '', 45, 'A'),
(8, 127, 1040, '40%', '', 40, 'A'),
(8, 127, 1035, '35%', '', 35, 'A'),
(8, 127, 1030, '30%', '', 30, 'A'),
(8, 127, 1025, '25%', '', 25, 'A'),
(8, 127, 1020, '20%', '', 20, 'A'),
(8, 127, 1015, '15%', '', 15, 'A'),
(8, 127, 1010, '10%', '', 10, 'A'),
(8, 127, 1000, 'Not started', '', 0, 'A'),
(8, 127, 1100, '100%', '', 100, 'A'),
(11, 127, 1, '1 - Lowest', '', 10, 'P'),
(11, 127, 2, '2', '', 20, 'P'),
(11, 127, 3, '3', '', 30, 'P'),
(11, 127, 4, '4', '', 40, 'P'),
(11, 127, 5, '5 - Medium', '', 50, 'P'),
(11, 127, 6, '6', '', 60, 'P'),
(11, 127, 7, '7', '', 70, 'P'),
(11, 127, 8, '8', '', 80, 'P'),
(11, 127, 9, '9 - Highest', '', 90, 'P'),
(7, 128, 100, 'None', '', 10, 'P'),
(9, 128, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 128, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 128, 1, '1 - Ordinary', '', 10, 'P'),
(10, 128, 2, '2', '', 20, 'P'),
(10, 128, 3, '3', '', 30, 'P'),
(10, 128, 4, '4', '', 40, 'P'),
(10, 128, 5, '5 - Major', '', 50, 'P'),
(10, 128, 6, '6', '', 60, 'P'),
(10, 128, 7, '7', '', 70, 'P'),
(10, 128, 8, '8', '', 80, 'P'),
(10, 128, 9, '9 - Critical', '', 90, 'P'),
(12, 128, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 128, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 129, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 129, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 129, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 129, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 129, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 129, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 129, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 129, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 129, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 129, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 129, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 129, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 129, 100, 'None', '', 10, 'P'),
(8, 129, 1, '1 - Ordinary', '', 10, 'P'),
(8, 129, 2, '2', '', 20, 'P'),
(8, 129, 3, '3', '', 30, 'P'),
(8, 129, 4, '4', '', 40, 'P'),
(8, 129, 5, '5 - Major', '', 50, 'P'),
(8, 129, 6, '6', '', 60, 'P'),
(8, 129, 7, '7', '', 70, 'P'),
(8, 129, 8, '8', '', 80, 'P'),
(8, 129, 9, '9 - Critical', '', 90, 'P'),
(10, 129, 100, 'None', '', 10, 'P'),
(16, 129, 100, 'None', '', 10, 'P'),
(16, 129, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 129, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 129, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 129, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 129, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 129, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 129, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 129, 100, 'None', '', 10, 'P'),
(12, 129, 100, 'None', '', 10, 'P'),
(13, 129, 100, 'None', '', 10, 'P'),
(14, 129, 100, 'None', '', 10, 'P'),
(15, 129, 100, 'None', '', 10, 'P'),
(18, 129, 100, 'None', '', 10, 'P'),
(20, 129, 100, 'None', '', 10, 'P'),
(22, 129, 100, 'None', '', 10, 'P'),
(24, 129, 100, 'None', '', 10, 'P'),
(11, 130, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 130, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 130, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 130, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 130, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 130, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 130, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 130, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 130, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 130, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 130, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 130, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 130, 100, 'None', '', 10, 'P'),
(2, 130, 1095, '95%', '', 95, 'A'),
(2, 130, 1090, '90%', '', 90, 'A'),
(2, 130, 1085, '85%', '', 85, 'A'),
(2, 130, 1080, '80%', '', 80, 'A'),
(2, 130, 1075, '75%', '', 75, 'A'),
(2, 130, 1070, '70%', '', 70, 'A'),
(2, 130, 1065, '65%', '', 65, 'A'),
(2, 130, 1060, '60%', '', 60, 'A'),
(2, 130, 1055, '55%', '', 55, 'A'),
(2, 130, 1050, '50%', '', 50, 'A'),
(2, 130, 1045, '45%', '', 45, 'A'),
(2, 130, 1040, '40%', '', 40, 'A'),
(2, 130, 1035, '35%', '', 35, 'A'),
(2, 130, 1030, '30%', '', 30, 'A'),
(2, 130, 1025, '25%', '', 25, 'A'),
(2, 130, 1020, '20%', '', 20, 'A'),
(2, 130, 1015, '15%', '', 15, 'A'),
(2, 130, 1010, '10%', '', 10, 'A'),
(2, 130, 1000, 'Not started', '', 0, 'A'),
(2, 130, 1100, '100%', '', 100, 'P'),
(14, 130, 1, '1 - Lowest', '', 10, 'P'),
(14, 130, 2, '2', '', 20, 'P'),
(14, 130, 3, '3', '', 30, 'P'),
(14, 130, 4, '4', '', 40, 'P'),
(14, 130, 5, '5 - Medium', '', 50, 'P'),
(14, 130, 6, '6', '', 60, 'P'),
(14, 130, 7, '7', '', 70, 'P'),
(14, 130, 8, '8', '', 80, 'P'),
(14, 130, 9, '9 - Highest', '', 90, 'P'),
(7, 131, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 131, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 131, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 131, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 131, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 131, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 131, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 131, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 131, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 131, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 131, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 131, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 131, 100, 'None', '', 10, 'P'),
(8, 131, 1095, '95%', '', 95, 'A'),
(8, 131, 1090, '90%', '', 90, 'A'),
(8, 131, 1085, '85%', '', 85, 'A'),
(8, 131, 1080, '80%', '', 80, 'A'),
(8, 131, 1075, '75%', '', 75, 'A'),
(8, 131, 1070, '70%', '', 70, 'A'),
(8, 131, 1065, '65%', '', 65, 'A'),
(8, 131, 1060, '60%', '', 60, 'A'),
(8, 131, 1055, '55%', '', 55, 'A'),
(8, 131, 1050, '50%', '', 50, 'A'),
(8, 131, 1045, '45%', '', 45, 'A'),
(8, 131, 1040, '40%', '', 40, 'A'),
(8, 131, 1035, '35%', '', 35, 'A'),
(8, 131, 1030, '30%', '', 30, 'A'),
(8, 131, 1025, '25%', '', 25, 'A'),
(8, 131, 1020, '20%', '', 20, 'A'),
(8, 131, 1015, '15%', '', 15, 'A'),
(8, 131, 1010, '10%', '', 10, 'A'),
(8, 131, 1000, 'Not started', '', 0, 'A'),
(8, 131, 1100, '100%', '', 100, 'A'),
(11, 131, 1, '1 - Lowest', '', 10, 'P'),
(11, 131, 2, '2', '', 20, 'P'),
(11, 131, 3, '3', '', 30, 'P'),
(11, 131, 4, '4', '', 40, 'P'),
(11, 131, 5, '5 - Medium', '', 50, 'P'),
(11, 131, 6, '6', '', 60, 'P'),
(11, 131, 7, '7', '', 70, 'P'),
(11, 131, 8, '8', '', 80, 'P'),
(11, 131, 9, '9 - Highest', '', 90, 'P'),
(7, 132, 100, 'None', '', 10, 'P'),
(9, 132, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 132, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 132, 1, '1 - Ordinary', '', 10, 'P'),
(10, 132, 2, '2', '', 20, 'P'),
(10, 132, 3, '3', '', 30, 'P'),
(10, 132, 4, '4', '', 40, 'P'),
(10, 132, 5, '5 - Major', '', 50, 'P'),
(10, 132, 6, '6', '', 60, 'P'),
(10, 132, 7, '7', '', 70, 'P'),
(10, 132, 8, '8', '', 80, 'P'),
(10, 132, 9, '9 - Critical', '', 90, 'P'),
(12, 132, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 132, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 133, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 133, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 133, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 133, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 133, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 133, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 133, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 133, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 133, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 133, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 133, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 133, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 133, 100, 'None', '', 10, 'P'),
(8, 133, 1, '1 - Ordinary', '', 10, 'P'),
(8, 133, 2, '2', '', 20, 'P'),
(8, 133, 3, '3', '', 30, 'P'),
(8, 133, 4, '4', '', 40, 'P'),
(8, 133, 5, '5 - Major', '', 50, 'P'),
(8, 133, 6, '6', '', 60, 'P'),
(8, 133, 7, '7', '', 70, 'P'),
(8, 133, 8, '8', '', 80, 'P'),
(8, 133, 9, '9 - Critical', '', 90, 'P'),
(10, 133, 100, 'None', '', 10, 'P'),
(16, 133, 100, 'None', '', 10, 'P'),
(16, 133, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 133, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 133, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 133, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 133, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 133, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 133, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 133, 100, 'None', '', 10, 'P'),
(12, 133, 100, 'None', '', 10, 'P'),
(13, 133, 100, 'None', '', 10, 'P'),
(14, 133, 100, 'None', '', 10, 'P'),
(15, 133, 100, 'None', '', 10, 'P'),
(18, 133, 100, 'None', '', 10, 'P'),
(20, 133, 100, 'None', '', 10, 'P'),
(22, 133, 100, 'None', '', 10, 'P'),
(24, 133, 100, 'None', '', 10, 'P'),
(11, 134, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 134, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 134, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 134, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 134, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 134, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 134, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 134, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 134, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 134, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 134, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 134, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 134, 100, 'None', '', 10, 'P'),
(2, 134, 1095, '95%', '', 95, 'A'),
(2, 134, 1090, '90%', '', 90, 'A'),
(2, 134, 1085, '85%', '', 85, 'A'),
(2, 134, 1080, '80%', '', 80, 'A'),
(2, 134, 1075, '75%', '', 75, 'A'),
(2, 134, 1070, '70%', '', 70, 'A'),
(2, 134, 1065, '65%', '', 65, 'A'),
(2, 134, 1060, '60%', '', 60, 'A'),
(2, 134, 1055, '55%', '', 55, 'A'),
(2, 134, 1050, '50%', '', 50, 'A'),
(2, 134, 1045, '45%', '', 45, 'A'),
(2, 134, 1040, '40%', '', 40, 'A'),
(2, 134, 1035, '35%', '', 35, 'A'),
(2, 134, 1030, '30%', '', 30, 'A'),
(2, 134, 1025, '25%', '', 25, 'A'),
(2, 134, 1020, '20%', '', 20, 'A'),
(2, 134, 1015, '15%', '', 15, 'A'),
(2, 134, 1010, '10%', '', 10, 'A'),
(2, 134, 1000, 'Not started', '', 0, 'A'),
(2, 134, 1100, '100%', '', 100, 'P'),
(14, 134, 1, '1 - Lowest', '', 10, 'P'),
(14, 134, 2, '2', '', 20, 'P'),
(14, 134, 3, '3', '', 30, 'P'),
(14, 134, 4, '4', '', 40, 'P'),
(14, 134, 5, '5 - Medium', '', 50, 'P'),
(14, 134, 6, '6', '', 60, 'P'),
(14, 134, 7, '7', '', 70, 'P'),
(14, 134, 8, '8', '', 80, 'P'),
(14, 134, 9, '9 - Highest', '', 90, 'P'),
(7, 135, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 135, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 135, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 135, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 135, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 135, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 135, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 135, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 135, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 135, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 135, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 135, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 135, 100, 'None', '', 10, 'P'),
(8, 135, 1095, '95%', '', 95, 'A'),
(8, 135, 1090, '90%', '', 90, 'A'),
(8, 135, 1085, '85%', '', 85, 'A'),
(8, 135, 1080, '80%', '', 80, 'A'),
(8, 135, 1075, '75%', '', 75, 'A'),
(8, 135, 1070, '70%', '', 70, 'A'),
(8, 135, 1065, '65%', '', 65, 'A'),
(8, 135, 1060, '60%', '', 60, 'A'),
(8, 135, 1055, '55%', '', 55, 'A'),
(8, 135, 1050, '50%', '', 50, 'A'),
(8, 135, 1045, '45%', '', 45, 'A'),
(8, 135, 1040, '40%', '', 40, 'A'),
(8, 135, 1035, '35%', '', 35, 'A'),
(8, 135, 1030, '30%', '', 30, 'A'),
(8, 135, 1025, '25%', '', 25, 'A'),
(8, 135, 1020, '20%', '', 20, 'A'),
(8, 135, 1015, '15%', '', 15, 'A'),
(8, 135, 1010, '10%', '', 10, 'A'),
(8, 135, 1000, 'Not started', '', 0, 'A'),
(8, 135, 1100, '100%', '', 100, 'A'),
(11, 135, 1, '1 - Lowest', '', 10, 'P'),
(11, 135, 2, '2', '', 20, 'P'),
(11, 135, 3, '3', '', 30, 'P'),
(11, 135, 4, '4', '', 40, 'P'),
(11, 135, 5, '5 - Medium', '', 50, 'P'),
(11, 135, 6, '6', '', 60, 'P'),
(11, 135, 7, '7', '', 70, 'P'),
(11, 135, 8, '8', '', 80, 'P'),
(11, 135, 9, '9 - Highest', '', 90, 'P'),
(7, 136, 100, 'None', '', 10, 'P'),
(9, 136, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 136, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 136, 1, '1 - Ordinary', '', 10, 'P'),
(10, 136, 2, '2', '', 20, 'P'),
(10, 136, 3, '3', '', 30, 'P'),
(10, 136, 4, '4', '', 40, 'P'),
(10, 136, 5, '5 - Major', '', 50, 'P'),
(10, 136, 6, '6', '', 60, 'P'),
(10, 136, 7, '7', '', 70, 'P'),
(10, 136, 8, '8', '', 80, 'P'),
(10, 136, 9, '9 - Critical', '', 90, 'P'),
(12, 136, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 136, 2, 'Declined', 'The artifact was not accepted.', 50, 'A'),
(2, 137, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(2, 137, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(30, 137, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(30, 137, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(30, 137, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(30, 137, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(30, 137, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(30, 137, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(30, 137, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(30, 137, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(30, 137, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(30, 137, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(3, 137, 100, 'None', '', 10, 'P'),
(8, 137, 1, '1 - Ordinary', '', 10, 'P'),
(8, 137, 2, '2', '', 20, 'P'),
(8, 137, 3, '3', '', 30, 'P'),
(8, 137, 4, '4', '', 40, 'P'),
(8, 137, 5, '5 - Major', '', 50, 'P'),
(8, 137, 6, '6', '', 60, 'P'),
(8, 137, 7, '7', '', 70, 'P'),
(8, 137, 8, '8', '', 80, 'P'),
(8, 137, 9, '9 - Critical', '', 90, 'P'),
(10, 137, 100, 'None', '', 10, 'P'),
(16, 137, 100, 'None', '', 10, 'P'),
(16, 137, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(16, 137, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(16, 137, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(16, 137, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(16, 137, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(16, 137, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(16, 137, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(11, 137, 100, 'None', '', 10, 'P'),
(12, 137, 100, 'None', '', 10, 'P'),
(13, 137, 100, 'None', '', 10, 'P'),
(14, 137, 100, 'None', '', 10, 'P'),
(15, 137, 100, 'None', '', 10, 'P'),
(18, 137, 100, 'None', '', 10, 'P'),
(20, 137, 100, 'None', '', 10, 'P'),
(22, 137, 100, 'None', '', 10, 'P'),
(24, 137, 100, 'None', '', 10, 'P'),
(11, 138, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(11, 138, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(15, 138, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(15, 138, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(15, 138, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(15, 138, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(15, 138, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(15, 138, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(15, 138, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(15, 138, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(15, 138, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(15, 138, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(12, 138, 100, 'None', '', 10, 'P'),
(2, 138, 1095, '95%', '', 95, 'A'),
(2, 138, 1090, '90%', '', 90, 'A'),
(2, 138, 1085, '85%', '', 85, 'A'),
(2, 138, 1080, '80%', '', 80, 'A'),
(2, 138, 1075, '75%', '', 75, 'A'),
(2, 138, 1070, '70%', '', 70, 'A'),
(2, 138, 1065, '65%', '', 65, 'A'),
(2, 138, 1060, '60%', '', 60, 'A'),
(2, 138, 1055, '55%', '', 55, 'A'),
(2, 138, 1050, '50%', '', 50, 'A'),
(2, 138, 1045, '45%', '', 45, 'A'),
(2, 138, 1040, '40%', '', 40, 'A'),
(2, 138, 1035, '35%', '', 35, 'A'),
(2, 138, 1030, '30%', '', 30, 'A'),
(2, 138, 1025, '25%', '', 25, 'A'),
(2, 138, 1020, '20%', '', 20, 'A'),
(2, 138, 1015, '15%', '', 15, 'A'),
(2, 138, 1010, '10%', '', 10, 'A'),
(2, 138, 1000, 'Not started', '', 0, 'A'),
(2, 138, 1100, '100%', '', 100, 'P'),
(14, 138, 1, '1 - Lowest', '', 10, 'P'),
(14, 138, 2, '2', '', 20, 'P'),
(14, 138, 3, '3', '', 30, 'P'),
(14, 138, 4, '4', '', 40, 'P'),
(14, 138, 5, '5 - Medium', '', 50, 'P'),
(14, 138, 6, '6', '', 60, 'P'),
(14, 138, 7, '7', '', 70, 'P'),
(14, 138, 8, '8', '', 80, 'P'),
(14, 138, 9, '9 - Highest', '', 90, 'P'),
(7, 139, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(7, 139, 3, 'Closed', 'The artifact is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(12, 139, 1, 'New', 'The artifact has just been submitted', 20, 'A'),
(12, 139, 2, 'Analyzed', 'The cause of the artifact has been identified and documented', 30, 'A'),
(12, 139, 3, 'Accepted', 'The artifact will be worked on.', 40, 'A'),
(12, 139, 4, 'Under Implementation', 'The artifact is being worked on.', 50, 'A'),
(12, 139, 5, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 60, 'A'),
(12, 139, 6, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 70, 'A'),
(12, 139, 7, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 80, 'A'),
(12, 139, 8, 'Approved', 'The artifact fix has been succesfully tested. It is approved and awaiting release.', 90, 'A'),
(12, 139, 9, 'Declined', 'The artifact was not accepted.', 100, 'A'),
(12, 139, 10, 'Done', 'The artifact is closed.', 110, 'A'),
(5, 139, 100, 'None', '', 10, 'P'),
(8, 139, 1095, '95%', '', 95, 'A'),
(8, 139, 1090, '90%', '', 90, 'A'),
(8, 139, 1085, '85%', '', 85, 'A'),
(8, 139, 1080, '80%', '', 80, 'A'),
(8, 139, 1075, '75%', '', 75, 'A'),
(8, 139, 1070, '70%', '', 70, 'A'),
(8, 139, 1065, '65%', '', 65, 'A'),
(8, 139, 1060, '60%', '', 60, 'A'),
(8, 139, 1055, '55%', '', 55, 'A'),
(8, 139, 1050, '50%', '', 50, 'A'),
(8, 139, 1045, '45%', '', 45, 'A'),
(8, 139, 1040, '40%', '', 40, 'A'),
(8, 139, 1035, '35%', '', 35, 'A'),
(8, 139, 1030, '30%', '', 30, 'A'),
(8, 139, 1025, '25%', '', 25, 'A'),
(8, 139, 1020, '20%', '', 20, 'A'),
(8, 139, 1015, '15%', '', 15, 'A'),
(8, 139, 1010, '10%', '', 10, 'A'),
(8, 139, 1000, 'Not started', '', 0, 'A'),
(8, 139, 1100, '100%', '', 100, 'A'),
(11, 139, 1, '1 - Lowest', '', 10, 'P'),
(11, 139, 2, '2', '', 20, 'P'),
(11, 139, 3, '3', '', 30, 'P'),
(11, 139, 4, '4', '', 40, 'P'),
(11, 139, 5, '5 - Medium', '', 50, 'P'),
(11, 139, 6, '6', '', 60, 'P'),
(11, 139, 7, '7', '', 70, 'P'),
(11, 139, 8, '8', '', 80, 'P'),
(11, 139, 9, '9 - Highest', '', 90, 'P'),
(7, 140, 100, 'None', '', 10, 'P'),
(9, 140, 1, 'Open', 'The artifact has been submitted', 20, 'P'),
(9, 140, 3, 'Closed', 'The artifact is no longer active', 400, 'P'),
(10, 140, 1, '1 - Ordinary', '', 10, 'P'),
(10, 140, 2, '2', '', 20, 'P'),
(10, 140, 3, '3', '', 30, 'P'),
(10, 140, 4, '4', '', 40, 'P'),
(10, 140, 5, '5 - Major', '', 50, 'P'),
(10, 140, 6, '6', '', 60, 'P'),
(10, 140, 7, '7', '', 70, 'P'),
(10, 140, 8, '8', '', 80, 'P'),
(10, 140, 9, '9 - Critical', '', 90, 'P'),
(12, 140, 1, 'Accepted', 'The artifact will be worked on. If it won''t be worked on, indicate why and close it', 10, 'A'),
(12, 140, 2, 'Declined', 'The artifact was not accepted.', 50, 'A');

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_file'
-- 

DROP TABLE IF EXISTS artifact_file;
CREATE TABLE IF NOT EXISTS artifact_file (
  id int(11) NOT NULL auto_increment,
  artifact_id int(11) NOT NULL default '0',
  description text NOT NULL,
  bin_data longblob NOT NULL,
  filename text NOT NULL,
  filesize int(11) NOT NULL default '0',
  filetype text NOT NULL,
  adddate int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY artifact_id (artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_file'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_global_notification'
-- 

DROP TABLE IF EXISTS artifact_global_notification;
CREATE TABLE IF NOT EXISTS artifact_global_notification (
  id int(11) unsigned NOT NULL auto_increment,
  tracker_id int(11) NOT NULL default '0',
  addresses text NOT NULL,
  all_updates tinyint(1) NOT NULL default '0',
  check_permissions tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY tracker_id (tracker_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_global_notification'
-- 

INSERT INTO artifact_global_notification (id, tracker_id, addresses, all_updates, check_permissions) VALUES (1, 1, '', 0, 1),
(2, 2, '', 0, 1),
(3, 3, '', 0, 1),
(4, 4, '', 0, 1),
(5, 5, '', 0, 1),
(6, 100, '', 0, 1),
(7, 101, '', 0, 1),
(8, 102, '', 0, 1),
(9, 103, '', 0, 1),
(10, 104, '', 0, 1),
(11, 105, '', 0, 1),
(12, 106, '', 0, 1),
(13, 107, '', 0, 1),
(14, 108, '', 0, 1),
(15, 109, '', 0, 1),
(16, 110, '', 0, 1),
(17, 111, '', 0, 1),
(18, 112, '', 0, 1),
(19, 113, '', 0, 1),
(20, 114, '', 0, 1),
(21, 115, '', 0, 1),
(22, 116, '', 0, 1),
(23, 117, '', 0, 1),
(24, 118, '', 0, 1),
(25, 119, '', 0, 1),
(26, 120, '', 0, 1),
(27, 121, '', 0, 1),
(28, 122, '', 0, 1),
(29, 123, '', 0, 1),
(30, 124, '', 0, 1),
(31, 125, '', 0, 1),
(32, 126, '', 0, 1),
(33, 127, '', 0, 1),
(34, 128, '', 0, 1),
(35, 129, '', 0, 1),
(36, 130, '', 0, 1),
(37, 131, '', 0, 1),
(38, 132, '', 0, 1),
(39, 133, '', 0, 1),
(40, 134, '', 0, 1),
(41, 135, '', 0, 1),
(42, 136, '', 0, 1),
(43, 137, '', 0, 1),
(44, 138, '', 0, 1),
(45, 139, '', 0, 1),
(46, 140, '', 0, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_group_list'
-- 

DROP TABLE IF EXISTS artifact_group_list;
CREATE TABLE IF NOT EXISTS artifact_group_list (
  group_artifact_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  name text,
  description text,
  item_name text,
  allow_copy int(11) NOT NULL default '0',
  email_all_updates int(11) NOT NULL default '0',
  email_address text NOT NULL,
  submit_instructions text,
  browse_instructions text,
  `status` char(1) NOT NULL default 'A',
  deletion_date int(11) default NULL,
  instantiate_for_new_projects int(11) NOT NULL default '0',
  PRIMARY KEY  (group_artifact_id),
  KEY idx_fk_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_group_list'
-- 

INSERT INTO artifact_group_list (group_artifact_id, group_id, name, description, item_name, allow_copy, email_all_updates, email_address, submit_instructions, browse_instructions, status, deletion_date, instantiate_for_new_projects) VALUES (1, 100, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', NULL, NULL, 'A', NULL, 1),
(2, 100, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', NULL, NULL, 'A', NULL, 1),
(3, 100, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', NULL, NULL, 'A', NULL, 1),
(4, 100, 'Empty', 'Empty Tracker', '', 0, 0, '', NULL, NULL, 'A', NULL, 0),
(5, 100, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', NULL, NULL, 'A', NULL, 1),
(100, 100, 'None', 'None', '', 0, 0, '', NULL, NULL, 'A', NULL, 0),
(101, 108, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(102, 108, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(103, 108, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(104, 108, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(105, 109, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(106, 109, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(107, 109, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(108, 109, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(109, 110, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(110, 110, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(111, 110, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(112, 110, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(113, 111, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(114, 111, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(115, 111, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(116, 111, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(117, 112, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(118, 112, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(119, 112, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(120, 112, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(121, 113, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(122, 113, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(123, 113, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(124, 113, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(125, 114, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(126, 114, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(127, 114, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(128, 114, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(129, 115, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(130, 115, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(131, 115, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(132, 115, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(133, 116, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(134, 116, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(135, 116, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(136, 116, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1),
(137, 117, 'Bugs', 'Bugs Tracker', 'bug', 0, 0, '', '', '', 'A', NULL, 1),
(138, 117, 'Tasks', 'Tasks Tracker', 'task', 0, 0, '', '', '', 'A', NULL, 1),
(139, 117, 'Support Requests', 'Support Requests Tracker', 'SR', 0, 0, '', '', '', 'A', NULL, 1),
(140, 117, 'Patches', 'Patch Tracker', 'patch', 0, 0, '', '', '', 'A', NULL, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_history'
-- 

DROP TABLE IF EXISTS artifact_history;
CREATE TABLE IF NOT EXISTS artifact_history (
  artifact_history_id int(11) NOT NULL auto_increment,
  artifact_id int(11) NOT NULL default '0',
  field_name varchar(255) NOT NULL default '',
  old_value text NOT NULL,
  new_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  email varchar(100) NOT NULL default '',
  `date` int(11) default NULL,
  `type` int(11) default NULL,
  PRIMARY KEY  (artifact_history_id),
  KEY idx_artifact_history_artifact_id (artifact_id),
  KEY field_name (field_name(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_history'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_notification'
-- 

DROP TABLE IF EXISTS artifact_notification;
CREATE TABLE IF NOT EXISTS artifact_notification (
  user_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  role_id int(11) NOT NULL default '0',
  event_id int(11) NOT NULL default '0',
  notify int(11) NOT NULL default '1',
  KEY user_id_idx (user_id),
  KEY group_artifact_id_idx (group_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_notification'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_notification_event'
-- 

DROP TABLE IF EXISTS artifact_notification_event;
CREATE TABLE IF NOT EXISTS artifact_notification_event (
  event_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  short_description_msg varchar(255) default NULL,
  description_msg varchar(255) default NULL,
  KEY event_id_idx (event_id),
  KEY group_artifact_id_idx (group_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_notification_event'
-- 

INSERT INTO artifact_notification_event (event_id, group_artifact_id, event_label, rank, short_description_msg, description_msg) VALUES (1, 101, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 101, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 101, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 101, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 101, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 101, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 101, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 101, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 101, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 102, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 102, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 102, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 102, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 102, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 102, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 102, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 102, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 102, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 103, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 103, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 103, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 103, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 103, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 103, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 103, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 103, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 103, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 104, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 104, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 104, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 104, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 104, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 104, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 104, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 104, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 104, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 105, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 105, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 105, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 105, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 105, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 105, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 105, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 105, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 105, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 106, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 106, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 106, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 106, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 106, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 106, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 106, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 106, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 106, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 107, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 107, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 107, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 107, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 107, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 107, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 107, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 107, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 107, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 108, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 108, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 108, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 108, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 108, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 108, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 108, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 108, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 108, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 109, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 109, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 109, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 109, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 109, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 109, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 109, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 109, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 109, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 110, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 110, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 110, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 110, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 110, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 110, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 110, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 110, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 110, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 111, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 111, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 111, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 111, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 111, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 111, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 111, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 111, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 111, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 112, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 112, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 112, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 112, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 112, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 112, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 112, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 112, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 112, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 113, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 113, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 113, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 113, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 113, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 113, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 113, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 113, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 113, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 114, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 114, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 114, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 114, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 114, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 114, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 114, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 114, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 114, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 115, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 115, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 115, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 115, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 115, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 115, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 115, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 115, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 115, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 116, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 116, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 116, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 116, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 116, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 116, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 116, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 116, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 116, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 117, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 117, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 117, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 117, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 117, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 117, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 117, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 117, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 117, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 118, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 118, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 118, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 118, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 118, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 118, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 118, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 118, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 118, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 119, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 119, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 119, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 119, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 119, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 119, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 119, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 119, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 119, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 120, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 120, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 120, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 120, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 120, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 120, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 120, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 120, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 120, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 121, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 121, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 121, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 121, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 121, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 121, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 121, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 121, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 121, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 122, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 122, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 122, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 122, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 122, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 122, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 122, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 122, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 122, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 123, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 123, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 123, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 123, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 123, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 123, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 123, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 123, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 123, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 124, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 124, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 124, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 124, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 124, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 124, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 124, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 124, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 124, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 125, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 125, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 125, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 125, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 125, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 125, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 125, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 125, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 125, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 126, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 126, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 126, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 126, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 126, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 126, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 126, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 126, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 126, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 127, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 127, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 127, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 127, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 127, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 127, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 127, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 127, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 127, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 128, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 128, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 128, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 128, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 128, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 128, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 128, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 128, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 128, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 129, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 129, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 129, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 129, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 129, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 129, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 129, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 129, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 129, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 130, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 130, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 130, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 130, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 130, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 130, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 130, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 130, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 130, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 131, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 131, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 131, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 131, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 131, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 131, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 131, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 131, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 131, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 132, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 132, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 132, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 132, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 132, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 132, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 132, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 132, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 132, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 133, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 133, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 133, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 133, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 133, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 133, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 133, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 133, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 133, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 134, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 134, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 134, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 134, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 134, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 134, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 134, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 134, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 134, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 135, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 135, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 135, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 135, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 135, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 135, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 135, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 135, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 135, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 136, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 136, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 136, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 136, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 136, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 136, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 136, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 136, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 136, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 137, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 137, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 137, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 137, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 137, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 137, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 137, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 137, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 137, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 138, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 138, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 138, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 138, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 138, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 138, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 138, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 138, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 138, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 139, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 139, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 139, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 139, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 139, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 139, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 139, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 139, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 139, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc'),
(1, 140, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 140, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 140, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 140, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 140, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 140, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 140, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 140, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 140, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc');

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_notification_event_default'
-- 

DROP TABLE IF EXISTS artifact_notification_event_default;
CREATE TABLE IF NOT EXISTS artifact_notification_event_default (
  event_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  short_description_msg varchar(255) default NULL,
  description_msg varchar(255) default NULL,
  KEY event_id_idx (event_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_notification_event_default'
-- 

INSERT INTO artifact_notification_event_default (event_id, event_label, rank, short_description_msg, description_msg) VALUES (1, 'ROLE_CHANGE', 10, 'event_ROLE_CHANGE_shortdesc', 'event_ROLE_CHANGE_desc'),
(2, 'NEW_COMMENT', 20, 'event_NEW_COMMENT_short_desc', 'event_NEW_COMMENT_desc'),
(3, 'NEW_FILE', 30, 'event_NEW_FILE_short_desc', 'event_NEW_FILE_desc'),
(4, 'CC_CHANGE', 40, 'event_CC_CHANGE_short_desc', 'event_CC_CHANGE_desc'),
(5, 'CLOSED', 50, 'event_CLOSED_short_desc', 'event_CLOSED_desc'),
(6, 'PSS_CHANGE', 60, 'event_PSS_CHANGE_short_desc', 'event_PSS_CHANGE_desc'),
(7, 'ANY_OTHER_CHANGE', 70, 'event_ANY_OTHER_CHANGE_short_desc', 'event_ANY_OTHER_CHANGE_desc'),
(8, 'I_MADE_IT', 80, 'event_I_MADE_IT_short_desc', 'event_I_MADE_IT_desc'),
(9, 'NEW_ARTIFACT', 90, 'event_NEW_ARTIFACT_short_desc', 'event_NEW_ARTIFACT_desc');

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_notification_role'
-- 

DROP TABLE IF EXISTS artifact_notification_role;
CREATE TABLE IF NOT EXISTS artifact_notification_role (
  role_id int(11) NOT NULL default '0',
  group_artifact_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  short_description_msg varchar(255) default NULL,
  description_msg varchar(255) default NULL,
  KEY role_id_idx (role_id),
  KEY group_artifact_id_idx (group_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_notification_role'
-- 

INSERT INTO artifact_notification_role (role_id, group_artifact_id, role_label, rank, short_description_msg, description_msg) VALUES (1, 101, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 101, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 101, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 101, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 102, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 102, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 102, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 102, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 103, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 103, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 103, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 103, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 104, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 104, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 104, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 104, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 105, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 105, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 105, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 105, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 106, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 106, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 106, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 106, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 107, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 107, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 107, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 107, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 108, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 108, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 108, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 108, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 109, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 109, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 109, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 109, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 110, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 110, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 110, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 110, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 111, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 111, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 111, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 111, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 112, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 112, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 112, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 112, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 113, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 113, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 113, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 113, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 114, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 114, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 114, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 114, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 115, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 115, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 115, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 115, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 116, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 116, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 116, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 116, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 117, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 117, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 117, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 117, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 118, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 118, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 118, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 118, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 119, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 119, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 119, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 119, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 120, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 120, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 120, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 120, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 121, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 121, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 121, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 121, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 122, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 122, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 122, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 122, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 123, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 123, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 123, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 123, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 124, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 124, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 124, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 124, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 125, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 125, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 125, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 125, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 126, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 126, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 126, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 126, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 127, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 127, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 127, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 127, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 128, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 128, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 128, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 128, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 129, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 129, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 129, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 129, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 130, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 130, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 130, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 130, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 131, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 131, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 131, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 131, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 132, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 132, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 132, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 132, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 133, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 133, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 133, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 133, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 134, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 134, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 134, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 134, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 135, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 135, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 135, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 135, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 136, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 136, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 136, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 136, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 137, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 137, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 137, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 137, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 138, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 138, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 138, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 138, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 139, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 139, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 139, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 139, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc'),
(1, 140, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 140, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 140, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 140, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc');

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_notification_role_default'
-- 

DROP TABLE IF EXISTS artifact_notification_role_default;
CREATE TABLE IF NOT EXISTS artifact_notification_role_default (
  role_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  short_description_msg varchar(255) default NULL,
  description_msg varchar(255) default NULL,
  KEY role_id_idx (role_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_notification_role_default'
-- 

INSERT INTO artifact_notification_role_default (role_id, role_label, rank, short_description_msg, description_msg) VALUES (1, 'SUBMITTER', 10, 'role_SUBMITTER_short_desc', 'role_SUBMITTER_desc'),
(2, 'ASSIGNEE', 20, 'role_ASSIGNEE_short_desc', 'role_ASSIGNEE_desc'),
(3, 'CC', 30, 'role_CC_short_desc', 'role_CC_desc'),
(4, 'COMMENTER', 40, 'role_COMMENTER_short_desc', 'role_COMMENTER_desc');

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_perm'
-- 

DROP TABLE IF EXISTS artifact_perm;
CREATE TABLE IF NOT EXISTS artifact_perm (
  id int(11) NOT NULL auto_increment,
  group_artifact_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  perm_level int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY unique_user (group_artifact_id,user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_perm'
-- 

INSERT INTO artifact_perm (id, group_artifact_id, user_id, perm_level) VALUES (1, 101, 102, 3),
(2, 102, 102, 3),
(3, 103, 102, 3),
(4, 104, 102, 3),
(5, 105, 102, 3),
(6, 106, 102, 3),
(7, 107, 102, 3),
(8, 108, 102, 3),
(9, 109, 101, 3),
(10, 110, 101, 3),
(11, 111, 101, 3),
(12, 112, 101, 3),
(13, 113, 103, 3),
(14, 114, 103, 3),
(15, 115, 103, 3),
(16, 116, 103, 3),
(17, 117, 106, 3),
(18, 118, 106, 3),
(19, 119, 106, 3),
(20, 120, 106, 3),
(21, 121, 109, 3),
(22, 122, 109, 3),
(23, 123, 109, 3),
(24, 124, 109, 3),
(25, 125, 110, 3),
(26, 126, 110, 3),
(27, 127, 110, 3),
(28, 128, 110, 3),
(29, 129, 110, 3),
(30, 130, 110, 3),
(31, 131, 110, 3),
(32, 132, 110, 3),
(33, 133, 110, 3),
(34, 134, 110, 3),
(35, 135, 110, 3),
(36, 136, 110, 3),
(37, 137, 110, 3),
(38, 138, 110, 3),
(39, 139, 110, 3),
(40, 140, 110, 3);

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_report'
-- 

DROP TABLE IF EXISTS artifact_report;
CREATE TABLE IF NOT EXISTS artifact_report (
  report_id int(11) NOT NULL auto_increment,
  group_artifact_id int(11) NOT NULL default '100',
  user_id int(11) NOT NULL default '100',
  name varchar(80) default NULL,
  description varchar(255) default NULL,
  scope char(1) NOT NULL default 'I',
  PRIMARY KEY  (report_id),
  KEY group_artifact_id_idx (group_artifact_id),
  KEY user_id_idx (user_id),
  KEY scope_idx (scope)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_report'
-- 

INSERT INTO artifact_report (report_id, group_artifact_id, user_id, name, description, scope) VALUES (100, 100, 100, 'Default', 'The system default artifact report', 'S'),
(2, 2, 100, 'Tasks', 'Tasks Report', 'P'),
(3, 3, 100, 'SR', 'Support Requests Report', 'P'),
(4, 1, 100, 'Bugs', 'Bugs Report', 'P'),
(101, 101, 100, 'Bugs', 'Bugs Report', 'P'),
(102, 102, 100, 'Tasks', 'Tasks Report', 'P'),
(103, 103, 100, 'SR', 'Support Requests Report', 'P'),
(104, 105, 100, 'Bugs', 'Bugs Report', 'P'),
(105, 106, 100, 'Tasks', 'Tasks Report', 'P'),
(106, 107, 100, 'SR', 'Support Requests Report', 'P'),
(107, 109, 100, 'Bugs', 'Bugs Report', 'P'),
(108, 110, 100, 'Tasks', 'Tasks Report', 'P'),
(109, 111, 100, 'SR', 'Support Requests Report', 'P'),
(110, 113, 100, 'Bugs', 'Bugs Report', 'P'),
(111, 114, 100, 'Tasks', 'Tasks Report', 'P'),
(112, 115, 100, 'SR', 'Support Requests Report', 'P'),
(113, 117, 100, 'Bugs', 'Bugs Report', 'P'),
(114, 118, 100, 'Tasks', 'Tasks Report', 'P'),
(115, 119, 100, 'SR', 'Support Requests Report', 'P'),
(116, 121, 100, 'Bugs', 'Bugs Report', 'P'),
(117, 122, 100, 'Tasks', 'Tasks Report', 'P'),
(118, 123, 100, 'SR', 'Support Requests Report', 'P'),
(119, 125, 100, 'Bugs', 'Bugs Report', 'P'),
(120, 126, 100, 'Tasks', 'Tasks Report', 'P'),
(121, 127, 100, 'SR', 'Support Requests Report', 'P'),
(122, 129, 100, 'Bugs', 'Bugs Report', 'P'),
(123, 130, 100, 'Tasks', 'Tasks Report', 'P'),
(124, 131, 100, 'SR', 'Support Requests Report', 'P'),
(125, 133, 100, 'Bugs', 'Bugs Report', 'P'),
(126, 134, 100, 'Tasks', 'Tasks Report', 'P'),
(127, 135, 100, 'SR', 'Support Requests Report', 'P'),
(128, 137, 100, 'Bugs', 'Bugs Report', 'P'),
(129, 138, 100, 'Tasks', 'Tasks Report', 'P'),
(130, 139, 100, 'SR', 'Support Requests Report', 'P');

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_report_field'
-- 

DROP TABLE IF EXISTS artifact_report_field;
CREATE TABLE IF NOT EXISTS artifact_report_field (
  report_id int(11) NOT NULL default '100',
  field_name varchar(255) default NULL,
  show_on_query int(11) default NULL,
  show_on_result int(11) default NULL,
  place_query int(11) default NULL,
  place_result int(11) default NULL,
  col_width int(11) default NULL,
  KEY profile_id_idx (report_id),
  KEY field_name_idx (field_name)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_report_field'
-- 

INSERT INTO artifact_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100, 'category_id', 1, 0, 10, NULL, NULL),
(100, 'assigned_to', 1, 1, 30, 40, NULL),
(100, 'status_id', 1, 0, 20, NULL, NULL),
(100, 'artifact_id', 1, 1, 50, 10, NULL),
(100, 'summary', 0, 1, NULL, 20, NULL),
(100, 'open_date', 1, 1, 40, 30, NULL),
(100, 'submitted_by', 0, 1, NULL, 50, NULL),
(100, 'severity', 0, 0, NULL, NULL, NULL),
(2, 'subproject_id', 1, 1, 10, 30, NULL),
(2, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(2, 'status_id', 1, 1, 30, 100, NULL),
(2, 'summary', 0, 1, NULL, 20, NULL),
(2, 'start_date', 0, 1, NULL, 40, NULL),
(2, 'close_date', 0, 1, NULL, 50, NULL),
(2, 'hours', 0, 1, NULL, 70, NULL),
(2, 'percent_complete', 0, 1, NULL, 80, NULL),
(2, 'artifact_id', 0, 1, NULL, 1, NULL),
(3, 'category_id', 1, 0, 10, NULL, NULL),
(3, 'status_id', 1, 0, 30, NULL, NULL),
(3, 'summary', 0, 1, NULL, 20, NULL),
(3, 'open_date', 0, 1, NULL, 30, NULL),
(3, 'submitted_by', 0, 1, NULL, 50, NULL),
(3, 'severity', 0, 0, NULL, NULL, NULL),
(3, 'artifact_id', 0, 1, NULL, 10, NULL),
(3, 'assigned_to', 1, 1, 20, 40, NULL),
(4, 'category_id', 1, 0, 10, NULL, NULL),
(4, 'assigned_to', 1, 1, 30, 40, NULL),
(4, 'status_id', 1, 0, 40, NULL, NULL),
(4, 'artifact_id', 0, 1, NULL, 10, NULL),
(4, 'summary', 0, 1, NULL, 20, NULL),
(4, 'open_date', 0, 1, NULL, 30, NULL),
(4, 'submitted_by', 0, 1, NULL, 50, NULL),
(4, 'bug_group_id', 1, 0, 20, NULL, NULL),
(101, 'category_id', 1, 0, 10, NULL, NULL),
(101, 'assigned_to', 1, 1, 30, 40, NULL),
(101, 'status_id', 1, 0, 40, NULL, NULL),
(101, 'artifact_id', 0, 1, NULL, 10, NULL),
(101, 'summary', 0, 1, NULL, 20, NULL),
(101, 'open_date', 0, 1, NULL, 30, NULL),
(101, 'submitted_by', 0, 1, NULL, 50, NULL),
(101, 'bug_group_id', 1, 0, 20, NULL, NULL),
(102, 'subproject_id', 1, 1, 10, 30, NULL),
(102, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(102, 'status_id', 1, 1, 30, 100, NULL),
(102, 'summary', 0, 1, NULL, 20, NULL),
(102, 'start_date', 0, 1, NULL, 40, NULL),
(102, 'close_date', 0, 1, NULL, 50, NULL),
(102, 'hours', 0, 1, NULL, 70, NULL),
(102, 'percent_complete', 0, 1, NULL, 80, NULL),
(102, 'artifact_id', 0, 1, NULL, 1, NULL),
(103, 'category_id', 1, 0, 10, NULL, NULL),
(103, 'status_id', 1, 0, 30, NULL, NULL),
(103, 'summary', 0, 1, NULL, 20, NULL),
(103, 'open_date', 0, 1, NULL, 30, NULL),
(103, 'submitted_by', 0, 1, NULL, 50, NULL),
(103, 'severity', 0, 0, NULL, NULL, NULL),
(103, 'artifact_id', 0, 1, NULL, 10, NULL),
(103, 'assigned_to', 1, 1, 20, 40, NULL),
(104, 'category_id', 1, 0, 10, NULL, NULL),
(104, 'assigned_to', 1, 1, 30, 40, NULL),
(104, 'status_id', 1, 0, 40, NULL, NULL),
(104, 'artifact_id', 0, 1, NULL, 10, NULL),
(104, 'summary', 0, 1, NULL, 20, NULL),
(104, 'open_date', 0, 1, NULL, 30, NULL),
(104, 'submitted_by', 0, 1, NULL, 50, NULL),
(104, 'bug_group_id', 1, 0, 20, NULL, NULL),
(105, 'subproject_id', 1, 1, 10, 30, NULL),
(105, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(105, 'status_id', 1, 1, 30, 100, NULL),
(105, 'summary', 0, 1, NULL, 20, NULL),
(105, 'start_date', 0, 1, NULL, 40, NULL),
(105, 'close_date', 0, 1, NULL, 50, NULL),
(105, 'hours', 0, 1, NULL, 70, NULL),
(105, 'percent_complete', 0, 1, NULL, 80, NULL),
(105, 'artifact_id', 0, 1, NULL, 1, NULL),
(106, 'category_id', 1, 0, 10, NULL, NULL),
(106, 'status_id', 1, 0, 30, NULL, NULL),
(106, 'summary', 0, 1, NULL, 20, NULL),
(106, 'open_date', 0, 1, NULL, 30, NULL),
(106, 'submitted_by', 0, 1, NULL, 50, NULL),
(106, 'severity', 0, 0, NULL, NULL, NULL),
(106, 'artifact_id', 0, 1, NULL, 10, NULL),
(106, 'assigned_to', 1, 1, 20, 40, NULL),
(107, 'category_id', 1, 0, 10, NULL, NULL),
(107, 'assigned_to', 1, 1, 30, 40, NULL),
(107, 'status_id', 1, 0, 40, NULL, NULL),
(107, 'artifact_id', 0, 1, NULL, 10, NULL),
(107, 'summary', 0, 1, NULL, 20, NULL),
(107, 'open_date', 0, 1, NULL, 30, NULL),
(107, 'submitted_by', 0, 1, NULL, 50, NULL),
(107, 'bug_group_id', 1, 0, 20, NULL, NULL),
(108, 'subproject_id', 1, 1, 10, 30, NULL),
(108, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(108, 'status_id', 1, 1, 30, 100, NULL),
(108, 'summary', 0, 1, NULL, 20, NULL),
(108, 'start_date', 0, 1, NULL, 40, NULL),
(108, 'close_date', 0, 1, NULL, 50, NULL),
(108, 'hours', 0, 1, NULL, 70, NULL),
(108, 'percent_complete', 0, 1, NULL, 80, NULL),
(108, 'artifact_id', 0, 1, NULL, 1, NULL),
(109, 'category_id', 1, 0, 10, NULL, NULL),
(109, 'status_id', 1, 0, 30, NULL, NULL),
(109, 'summary', 0, 1, NULL, 20, NULL),
(109, 'open_date', 0, 1, NULL, 30, NULL),
(109, 'submitted_by', 0, 1, NULL, 50, NULL),
(109, 'severity', 0, 0, NULL, NULL, NULL),
(109, 'artifact_id', 0, 1, NULL, 10, NULL),
(109, 'assigned_to', 1, 1, 20, 40, NULL),
(110, 'category_id', 1, 0, 10, NULL, NULL),
(110, 'assigned_to', 1, 1, 30, 40, NULL),
(110, 'status_id', 1, 0, 40, NULL, NULL),
(110, 'artifact_id', 0, 1, NULL, 10, NULL),
(110, 'summary', 0, 1, NULL, 20, NULL),
(110, 'open_date', 0, 1, NULL, 30, NULL),
(110, 'submitted_by', 0, 1, NULL, 50, NULL),
(110, 'bug_group_id', 1, 0, 20, NULL, NULL),
(111, 'subproject_id', 1, 1, 10, 30, NULL),
(111, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(111, 'status_id', 1, 1, 30, 100, NULL),
(111, 'summary', 0, 1, NULL, 20, NULL),
(111, 'start_date', 0, 1, NULL, 40, NULL),
(111, 'close_date', 0, 1, NULL, 50, NULL),
(111, 'hours', 0, 1, NULL, 70, NULL),
(111, 'percent_complete', 0, 1, NULL, 80, NULL),
(111, 'artifact_id', 0, 1, NULL, 1, NULL),
(112, 'category_id', 1, 0, 10, NULL, NULL),
(112, 'status_id', 1, 0, 30, NULL, NULL),
(112, 'summary', 0, 1, NULL, 20, NULL),
(112, 'open_date', 0, 1, NULL, 30, NULL),
(112, 'submitted_by', 0, 1, NULL, 50, NULL),
(112, 'severity', 0, 0, NULL, NULL, NULL),
(112, 'artifact_id', 0, 1, NULL, 10, NULL),
(112, 'assigned_to', 1, 1, 20, 40, NULL),
(113, 'category_id', 1, 0, 10, NULL, NULL),
(113, 'assigned_to', 1, 1, 30, 40, NULL),
(113, 'status_id', 1, 0, 40, NULL, NULL),
(113, 'artifact_id', 0, 1, NULL, 10, NULL),
(113, 'summary', 0, 1, NULL, 20, NULL),
(113, 'open_date', 0, 1, NULL, 30, NULL),
(113, 'submitted_by', 0, 1, NULL, 50, NULL),
(113, 'bug_group_id', 1, 0, 20, NULL, NULL),
(114, 'subproject_id', 1, 1, 10, 30, NULL),
(114, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(114, 'status_id', 1, 1, 30, 100, NULL),
(114, 'summary', 0, 1, NULL, 20, NULL),
(114, 'start_date', 0, 1, NULL, 40, NULL),
(114, 'close_date', 0, 1, NULL, 50, NULL),
(114, 'hours', 0, 1, NULL, 70, NULL),
(114, 'percent_complete', 0, 1, NULL, 80, NULL),
(114, 'artifact_id', 0, 1, NULL, 1, NULL),
(115, 'category_id', 1, 0, 10, NULL, NULL),
(115, 'status_id', 1, 0, 30, NULL, NULL),
(115, 'summary', 0, 1, NULL, 20, NULL),
(115, 'open_date', 0, 1, NULL, 30, NULL),
(115, 'submitted_by', 0, 1, NULL, 50, NULL),
(115, 'severity', 0, 0, NULL, NULL, NULL),
(115, 'artifact_id', 0, 1, NULL, 10, NULL),
(115, 'assigned_to', 1, 1, 20, 40, NULL),
(116, 'category_id', 1, 0, 10, NULL, NULL),
(116, 'assigned_to', 1, 1, 30, 40, NULL),
(116, 'status_id', 1, 0, 40, NULL, NULL),
(116, 'artifact_id', 0, 1, NULL, 10, NULL),
(116, 'summary', 0, 1, NULL, 20, NULL),
(116, 'open_date', 0, 1, NULL, 30, NULL),
(116, 'submitted_by', 0, 1, NULL, 50, NULL),
(116, 'bug_group_id', 1, 0, 20, NULL, NULL),
(117, 'subproject_id', 1, 1, 10, 30, NULL),
(117, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(117, 'status_id', 1, 1, 30, 100, NULL),
(117, 'summary', 0, 1, NULL, 20, NULL),
(117, 'start_date', 0, 1, NULL, 40, NULL),
(117, 'close_date', 0, 1, NULL, 50, NULL),
(117, 'hours', 0, 1, NULL, 70, NULL),
(117, 'percent_complete', 0, 1, NULL, 80, NULL),
(117, 'artifact_id', 0, 1, NULL, 1, NULL),
(118, 'category_id', 1, 0, 10, NULL, NULL),
(118, 'status_id', 1, 0, 30, NULL, NULL),
(118, 'summary', 0, 1, NULL, 20, NULL),
(118, 'open_date', 0, 1, NULL, 30, NULL),
(118, 'submitted_by', 0, 1, NULL, 50, NULL),
(118, 'severity', 0, 0, NULL, NULL, NULL),
(118, 'artifact_id', 0, 1, NULL, 10, NULL),
(118, 'assigned_to', 1, 1, 20, 40, NULL),
(119, 'category_id', 1, 0, 10, NULL, NULL),
(119, 'assigned_to', 1, 1, 30, 40, NULL),
(119, 'status_id', 1, 0, 40, NULL, NULL),
(119, 'artifact_id', 0, 1, NULL, 10, NULL),
(119, 'summary', 0, 1, NULL, 20, NULL),
(119, 'open_date', 0, 1, NULL, 30, NULL),
(119, 'submitted_by', 0, 1, NULL, 50, NULL),
(119, 'bug_group_id', 1, 0, 20, NULL, NULL),
(120, 'subproject_id', 1, 1, 10, 30, NULL),
(120, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(120, 'status_id', 1, 1, 30, 100, NULL),
(120, 'summary', 0, 1, NULL, 20, NULL),
(120, 'start_date', 0, 1, NULL, 40, NULL),
(120, 'close_date', 0, 1, NULL, 50, NULL),
(120, 'hours', 0, 1, NULL, 70, NULL),
(120, 'percent_complete', 0, 1, NULL, 80, NULL),
(120, 'artifact_id', 0, 1, NULL, 1, NULL),
(121, 'category_id', 1, 0, 10, NULL, NULL),
(121, 'status_id', 1, 0, 30, NULL, NULL),
(121, 'summary', 0, 1, NULL, 20, NULL),
(121, 'open_date', 0, 1, NULL, 30, NULL),
(121, 'submitted_by', 0, 1, NULL, 50, NULL),
(121, 'severity', 0, 0, NULL, NULL, NULL),
(121, 'artifact_id', 0, 1, NULL, 10, NULL),
(121, 'assigned_to', 1, 1, 20, 40, NULL),
(122, 'category_id', 1, 0, 10, NULL, NULL),
(122, 'assigned_to', 1, 1, 30, 40, NULL),
(122, 'status_id', 1, 0, 40, NULL, NULL),
(122, 'artifact_id', 0, 1, NULL, 10, NULL),
(122, 'summary', 0, 1, NULL, 20, NULL),
(122, 'open_date', 0, 1, NULL, 30, NULL),
(122, 'submitted_by', 0, 1, NULL, 50, NULL),
(122, 'bug_group_id', 1, 0, 20, NULL, NULL),
(123, 'subproject_id', 1, 1, 10, 30, NULL),
(123, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(123, 'status_id', 1, 1, 30, 100, NULL),
(123, 'summary', 0, 1, NULL, 20, NULL),
(123, 'start_date', 0, 1, NULL, 40, NULL),
(123, 'close_date', 0, 1, NULL, 50, NULL),
(123, 'hours', 0, 1, NULL, 70, NULL),
(123, 'percent_complete', 0, 1, NULL, 80, NULL),
(123, 'artifact_id', 0, 1, NULL, 1, NULL),
(124, 'category_id', 1, 0, 10, NULL, NULL),
(124, 'status_id', 1, 0, 30, NULL, NULL),
(124, 'summary', 0, 1, NULL, 20, NULL),
(124, 'open_date', 0, 1, NULL, 30, NULL),
(124, 'submitted_by', 0, 1, NULL, 50, NULL),
(124, 'severity', 0, 0, NULL, NULL, NULL),
(124, 'artifact_id', 0, 1, NULL, 10, NULL),
(124, 'assigned_to', 1, 1, 20, 40, NULL),
(125, 'category_id', 1, 0, 10, NULL, NULL),
(125, 'assigned_to', 1, 1, 30, 40, NULL),
(125, 'status_id', 1, 0, 40, NULL, NULL),
(125, 'artifact_id', 0, 1, NULL, 10, NULL),
(125, 'summary', 0, 1, NULL, 20, NULL),
(125, 'open_date', 0, 1, NULL, 30, NULL),
(125, 'submitted_by', 0, 1, NULL, 50, NULL),
(125, 'bug_group_id', 1, 0, 20, NULL, NULL),
(126, 'subproject_id', 1, 1, 10, 30, NULL),
(126, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(126, 'status_id', 1, 1, 30, 100, NULL),
(126, 'summary', 0, 1, NULL, 20, NULL),
(126, 'start_date', 0, 1, NULL, 40, NULL),
(126, 'close_date', 0, 1, NULL, 50, NULL),
(126, 'hours', 0, 1, NULL, 70, NULL),
(126, 'percent_complete', 0, 1, NULL, 80, NULL),
(126, 'artifact_id', 0, 1, NULL, 1, NULL),
(127, 'category_id', 1, 0, 10, NULL, NULL),
(127, 'status_id', 1, 0, 30, NULL, NULL),
(127, 'summary', 0, 1, NULL, 20, NULL),
(127, 'open_date', 0, 1, NULL, 30, NULL),
(127, 'submitted_by', 0, 1, NULL, 50, NULL),
(127, 'severity', 0, 0, NULL, NULL, NULL),
(127, 'artifact_id', 0, 1, NULL, 10, NULL),
(127, 'assigned_to', 1, 1, 20, 40, NULL),
(128, 'category_id', 1, 0, 10, NULL, NULL),
(128, 'assigned_to', 1, 1, 30, 40, NULL),
(128, 'status_id', 1, 0, 40, NULL, NULL),
(128, 'artifact_id', 0, 1, NULL, 10, NULL),
(128, 'summary', 0, 1, NULL, 20, NULL),
(128, 'open_date', 0, 1, NULL, 30, NULL),
(128, 'submitted_by', 0, 1, NULL, 50, NULL),
(128, 'bug_group_id', 1, 0, 20, NULL, NULL),
(129, 'subproject_id', 1, 1, 10, 30, NULL),
(129, 'multi_assigned_to', 1, 1, 20, 60, NULL),
(129, 'status_id', 1, 1, 30, 100, NULL),
(129, 'summary', 0, 1, NULL, 20, NULL),
(129, 'start_date', 0, 1, NULL, 40, NULL),
(129, 'close_date', 0, 1, NULL, 50, NULL),
(129, 'hours', 0, 1, NULL, 70, NULL),
(129, 'percent_complete', 0, 1, NULL, 80, NULL),
(129, 'artifact_id', 0, 1, NULL, 1, NULL),
(130, 'category_id', 1, 0, 10, NULL, NULL),
(130, 'status_id', 1, 0, 30, NULL, NULL),
(130, 'summary', 0, 1, NULL, 20, NULL),
(130, 'open_date', 0, 1, NULL, 30, NULL),
(130, 'submitted_by', 0, 1, NULL, 50, NULL),
(130, 'severity', 0, 0, NULL, NULL, NULL),
(130, 'artifact_id', 0, 1, NULL, 10, NULL),
(130, 'assigned_to', 1, 1, 20, 40, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_rule'
-- 

DROP TABLE IF EXISTS artifact_rule;
CREATE TABLE IF NOT EXISTS artifact_rule (
  id int(11) unsigned NOT NULL auto_increment,
  group_artifact_id int(11) unsigned NOT NULL default '0',
  source_field_id int(11) unsigned NOT NULL default '0',
  source_value_id int(11) unsigned NOT NULL default '0',
  target_field_id int(11) unsigned NOT NULL default '0',
  rule_type tinyint(4) unsigned NOT NULL default '0',
  target_value_id int(11) unsigned default NULL,
  PRIMARY KEY  (id),
  KEY group_artifact_id (group_artifact_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_rule'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'artifact_watcher'
-- 

DROP TABLE IF EXISTS artifact_watcher;
CREATE TABLE IF NOT EXISTS artifact_watcher (
  user_id int(11) NOT NULL default '0',
  watchee_id int(11) NOT NULL default '0',
  artifact_group_id int(11) NOT NULL default '0',
  KEY watchee_id_idx (watchee_id,artifact_group_id),
  KEY user_id_idx (user_id,artifact_group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'artifact_watcher'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug'
-- 

DROP TABLE IF EXISTS bug;
CREATE TABLE IF NOT EXISTS bug (
  bug_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  status_id int(11) NOT NULL default '1',
  severity int(11) NOT NULL default '5',
  category_id int(11) NOT NULL default '100',
  submitted_by int(11) NOT NULL default '100',
  assigned_to int(11) NOT NULL default '100',
  `date` int(11) NOT NULL default '0',
  summary text,
  details text,
  close_date int(11) default NULL,
  bug_group_id int(11) NOT NULL default '100',
  resolution_id int(11) NOT NULL default '100',
  category_version_id int(11) NOT NULL default '100',
  platform_version_id int(11) NOT NULL default '100',
  reproducibility_id int(11) NOT NULL default '100',
  size_id int(11) NOT NULL default '100',
  fix_release_id int(11) NOT NULL default '100',
  plan_release_id int(11) NOT NULL default '100',
  hours float(10,2) NOT NULL default '0.00',
  component_version varchar(255) NOT NULL default '',
  fix_release varchar(255) NOT NULL default '',
  plan_release varchar(255) NOT NULL default '',
  priority int(11) NOT NULL default '100',
  keywords varchar(255) NOT NULL default '',
  release_id int(11) NOT NULL default '100',
  release varchar(255) NOT NULL default '',
  originator_name varchar(255) NOT NULL default '',
  originator_email varchar(255) NOT NULL default '',
  originator_phone varchar(255) NOT NULL default '',
  custom_tf1 varchar(255) NOT NULL default '',
  custom_tf2 varchar(255) NOT NULL default '',
  custom_tf3 varchar(255) NOT NULL default '',
  custom_tf4 varchar(255) NOT NULL default '',
  custom_tf5 varchar(255) NOT NULL default '',
  custom_tf6 varchar(255) NOT NULL default '',
  custom_tf7 varchar(255) NOT NULL default '',
  custom_tf8 varchar(255) NOT NULL default '',
  custom_tf9 varchar(255) NOT NULL default '',
  custom_tf10 varchar(255) NOT NULL default '',
  custom_ta1 text NOT NULL,
  custom_ta2 text NOT NULL,
  custom_ta3 text NOT NULL,
  custom_ta4 text NOT NULL,
  custom_ta5 text NOT NULL,
  custom_ta6 text NOT NULL,
  custom_ta7 text NOT NULL,
  custom_ta8 text NOT NULL,
  custom_ta9 text NOT NULL,
  custom_ta10 text NOT NULL,
  custom_sb1 int(11) NOT NULL default '100',
  custom_sb2 int(11) NOT NULL default '100',
  custom_sb3 int(11) NOT NULL default '100',
  custom_sb4 int(11) NOT NULL default '100',
  custom_sb5 int(11) NOT NULL default '100',
  custom_sb6 int(11) NOT NULL default '100',
  custom_sb7 int(11) NOT NULL default '100',
  custom_sb8 int(11) NOT NULL default '100',
  custom_sb9 int(11) NOT NULL default '100',
  custom_sb10 int(11) NOT NULL default '100',
  custom_df1 int(11) NOT NULL default '0',
  custom_df2 int(11) NOT NULL default '0',
  custom_df3 int(11) NOT NULL default '0',
  custom_df4 int(11) NOT NULL default '0',
  custom_df5 int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_id),
  KEY idx_bug_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug'
-- 

INSERT INTO bug (bug_id, group_id, status_id, severity, category_id, submitted_by, assigned_to, date, summary, details, close_date, bug_group_id, resolution_id, category_version_id, platform_version_id, reproducibility_id, size_id, fix_release_id, plan_release_id, hours, component_version, fix_release, plan_release, priority, keywords, release_id, release, originator_name, originator_email, originator_phone, custom_tf1, custom_tf2, custom_tf3, custom_tf4, custom_tf5, custom_tf6, custom_tf7, custom_tf8, custom_tf9, custom_tf10, custom_ta1, custom_ta2, custom_ta3, custom_ta4, custom_ta5, custom_ta6, custom_ta7, custom_ta8, custom_ta9, custom_ta10, custom_sb1, custom_sb2, custom_sb3, custom_sb4, custom_sb5, custom_sb6, custom_sb7, custom_sb8, custom_sb9, custom_sb10, custom_df1, custom_df2, custom_df3, custom_df4, custom_df5) VALUES (100, 100, 3, 1, 100, 100, 100, 1058260700, 'None', '', 0, 100, 100, 100, 100, 100, 100, 100, 100, 0.00, '', '', '', 100, '', 100, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', 100, 100, 100, 100, 100, 100, 100, 100, 100, 100, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_bug_dependencies'
-- 

DROP TABLE IF EXISTS bug_bug_dependencies;
CREATE TABLE IF NOT EXISTS bug_bug_dependencies (
  bug_depend_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  is_dependent_on_bug_id int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_depend_id),
  KEY idx_bug_bug_dependencies_bug_id (bug_id),
  KEY idx_bug_bug_is_dependent_on_task_id (is_dependent_on_bug_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_bug_dependencies'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_canned_responses'
-- 

DROP TABLE IF EXISTS bug_canned_responses;
CREATE TABLE IF NOT EXISTS bug_canned_responses (
  bug_canned_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  title text,
  body text,
  PRIMARY KEY  (bug_canned_id),
  KEY idx_bug_canned_response_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_canned_responses'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_cc'
-- 

DROP TABLE IF EXISTS bug_cc;
CREATE TABLE IF NOT EXISTS bug_cc (
  bug_cc_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  added_by int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_cc_id),
  KEY bug_id_idx (bug_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_cc'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_field'
-- 

DROP TABLE IF EXISTS bug_field;
CREATE TABLE IF NOT EXISTS bug_field (
  bug_field_id int(11) NOT NULL auto_increment,
  field_name varchar(255) NOT NULL default '',
  display_type varchar(255) NOT NULL default '',
  display_size varchar(255) NOT NULL default '',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default '',
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  keep_history int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  custom int(11) NOT NULL default '0',
  value_function varchar(255) default NULL,
  PRIMARY KEY  (bug_field_id),
  KEY idx_bug_field_name (field_name)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_field'
-- 

INSERT INTO bug_field (bug_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom, value_function) VALUES (90, 'bug_id', 'TF', '6/10', 'Bug ID', 'Unique bug identifier', 'S', 1, 0, 0, 1, 0, NULL),
(91, 'group_id', 'TF', '', 'Group ID', 'Unique project identifier', 'S', 1, 0, 0, 1, 0, NULL),
(92, 'submitted_by', 'SB', '', 'Submitted by', 'User who originally submitted the bug', 'S', 1, 1, 0, 1, 0, NULL),
(93, 'date', 'DF', '10/15', 'Submitted on', 'Date and time for the initial bug submission', 'S', 1, 0, 0, 1, 0, 'bug_submitters'),
(94, 'close_date', 'DF', '10/15', 'Closed on', 'Date and time when the bug status was changed to ''Closed''', 'S', 1, 1, 0, 1, 0, NULL),
(101, 'status_id', 'SB', '', 'Status', 'Bug Status', 'P', 1, 0, 1, 0, 0, NULL),
(102, 'severity', 'SB', '', 'Severity', 'Impact of the bug on the system (Critical, Major,...)', 'S', 1, 0, 1, 0, 0, NULL),
(103, 'category_id', 'SB', '', 'Category', 'Generally correspond to high level modules or functionalities of your software (e.g. User interface, Configuration Manager, Scheduler, Memory Manager...)', 'P', 0, 1, 1, 0, 0, NULL),
(104, 'assigned_to', 'SB', '', 'Assigned to', 'Who is in charge of solving the bug', 'S', 1, 1, 1, 0, 0, 'bug_technicians'),
(105, 'summary', 'TF', '60/120', 'Summary', 'One line description of the bug', 'S', 1, 0, 1, 1, 0, NULL),
(106, 'details', 'TA', '60/7', 'Original Submission', 'A full description of the bug', 'S', 1, 1, 1, 1, 0, NULL),
(107, 'bug_group_id', 'SB', '', 'Bug Group', 'Characterizes the nature of the bug (e.g. Feature Request, Action Request, Crash Error, Documentation Typo, Installation Problem,...', 'P', 0, 1, 1, 0, 0, NULL),
(108, 'resolution_id', 'SB', '', 'Resolution', 'How you have decided to fix the bug (Fixed, Work for me, Duplicate,..)', 'S', 1, 1, 1, 0, 0, NULL),
(200, 'category_version_id', 'SB', '', 'Component Version', 'The version of the System Component (aka Bug Category) impacted by the bug', 'P', 0, 1, 1, 0, 0, NULL),
(201, 'platform_version_id', 'SB', '', 'Platform Version', 'The name and version of the platform your software was running on when the bug occured (e.g. Solaris 2.8, Linux 2.4, Windows NT4 SP2,...)', 'P', 0, 1, 1, 0, 0, NULL),
(202, 'reproducibility_id', 'SB', '', 'Reproducibility', 'How easy is it to reproduce the bug', 'S', 0, 0, 1, 0, 0, NULL),
(203, 'size_id', 'SB', '', 'Size (loc)', 'The size of the code you need to develop or rework in order to fix the bug', 'S', 0, 1, 1, 0, 0, NULL),
(204, 'fix_release_id', 'SB', '', 'Fixed Release', 'The release in which the bug was actually fixed', 'P', 0, 1, 1, 0, 0, NULL),
(205, 'comment_type_id', 'SB', '', 'Comment Type', 'Specify the nature of the  follow up comment attached to this bug (Workaround, Test Case, Impacted Files,...)', 'P', 1, 1, 0, 1, 0, NULL),
(206, 'hours', 'TF', '5/5', 'Effort', 'Number of hours of work needed to fix the bug (including testing)', 'S', 0, 1, 1, 0, 0, NULL),
(207, 'plan_release_id', 'SB', '', 'Planned Release', 'The release in which you initially planned the bug to be fixed', 'P', 0, 1, 1, 0, 0, NULL),
(208, 'component_version', 'TF', '10/40', 'Component Version', 'Version of the system component (or work product) impacted by the bug. Same as the other Component Version field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, 0, NULL),
(209, 'fix_release', 'TF', '10/40', 'Fixed Release', 'The release in which the bug was actually fixed. Same as the other Fixed Release field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, 0, NULL),
(210, 'plan_release', 'TF', '10/40', 'Planned Release', 'The release in which you initially planned the bug to be fixed. Same as the other Planned Release field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, 0, NULL),
(211, 'priority', 'SB', '', 'Priority', 'How quickly the bug must be fixed (Immediate, Normal, Low, Later,...)', 'S', 0, 1, 1, 0, 0, NULL),
(212, 'keywords', 'TF', '60/120', 'Keywords', 'A list of comma separated keywords associated with a bug', 'S', 0, 1, 1, 0, 0, NULL),
(213, 'release_id', 'SB', '', 'Release', 'The release (global version number) impacted by the bug', 'P', 0, 1, 1, 0, 0, NULL),
(214, 'release', 'TF', '10/40', 'Release', 'The release (global version number) impacted by the bug. Same as the other Release field <u>except</u> this one is free text.', 'S', 0, 1, 1, 0, 0, NULL),
(215, 'originator_name', 'TF', '20/40', 'Originator Name', 'The name of the person who reported the bug (if different from the submitter field)', 'S', 0, 1, 1, 0, 0, NULL),
(216, 'originator_email', 'TF', '20/40', 'Originator Email', 'Email address of the person who reported the bug. Automatically included in the bug email notification process.', 'S', 0, 1, 1, 0, 0, NULL),
(217, 'originator_phone', 'TF', '10/40', 'Originator Phone', 'Phone number of the person who reported the bug', 'S', 0, 1, 1, 0, 0, NULL),
(300, 'custom_tf1', 'TF', '10/15', 'Custom Text Field #1', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(301, 'custom_tf2', 'TF', '10/15', 'Custom Text Field #2', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(302, 'custom_tf3', 'TF', '10/15', 'Custom Text Field #3', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(303, 'custom_tf4', 'TF', '10/15', 'Custom Text Field #4', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(304, 'custom_tf5', 'TF', '10/15', 'Custom Text Field #5', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(305, 'custom_tf6', 'TF', '10/15', 'Custom Text Field #6', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(306, 'custom_tf7', 'TF', '10/15', 'Custom Text Field #7', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(307, 'custom_tf8', 'TF', '10/15', 'Custom Text Field #8', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(308, 'custom_tf9', 'TF', '10/15', 'Custom Text Field #9', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(309, 'custom_tf10', 'TF', '10/15', 'Custom Text Field #10', 'Customizable Text Field (one line, up to 255 characters', 'P', 0, 1, 1, 0, 1, NULL),
(400, 'custom_ta1', 'TA', '60/3', 'Custom Text Area #1', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(401, 'custom_ta2', 'TA', '60/3', 'Custom Text Area #2', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(402, 'custom_ta3', 'TA', '60/3', 'Custom Text Area #3', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(403, 'custom_ta4', 'TA', '60/3', 'Custom Text Area #4', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(404, 'custom_ta5', 'TA', '60/3', 'Custom Text Area #5', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(405, 'custom_ta6', 'TA', '60/3', 'Custom Text Area #6', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(406, 'custom_ta7', 'TA', '60/3', 'Custom Text Area #7', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(407, 'custom_ta8', 'TA', '60/3', 'Custom Text Area #8', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(408, 'custom_ta9', 'TA', '60/3', 'Custom Text Area #9', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(409, 'custom_ta10', 'TA', '60/3', 'Custom Text Area #10', 'Customizable Text Area (multi-line text)', 'P', 0, 1, 1, 0, 1, NULL),
(500, 'custom_sb1', 'SB', '', 'Custom Select Box #1', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(501, 'custom_sb2', 'SB', '', 'Custom Select Box #2', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(502, 'custom_sb3', 'SB', '', 'Custom Select Box #3', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(503, 'custom_sb4', 'SB', '', 'Custom Select Box #4', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(504, 'custom_sb5', 'SB', '', 'Custom Select Box #5', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(505, 'custom_sb6', 'SB', '', 'Custom Select Box #6', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(506, 'custom_sb7', 'SB', '', 'Custom Select Box #7', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(507, 'custom_sb8', 'SB', '', 'Custom Select Box #8', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(508, 'custom_sb9', 'SB', '', 'Custom Select Box #9', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(509, 'custom_sb10', 'SB', '', 'Custom Select Box #10', 'Customizable Select Box (pull down menu with predefined values)', 'P', 0, 1, 1, 0, 1, NULL),
(600, 'custom_df1', 'DF', '10/10', 'Custom Date Field #1', 'Customizable Date Field', 'P', 0, 1, 1, 0, 1, NULL),
(601, 'custom_df2', 'DF', '10/10', 'Custom Date Field #2', 'Customizable Date Field', 'P', 0, 1, 1, 0, 1, NULL),
(602, 'custom_df3', 'DF', '10/10', 'Custom Date Field #3', 'Customizable Date Field', 'P', 0, 1, 1, 0, 1, NULL),
(603, 'custom_df4', 'DF', '10/10', 'Custom Date Field #4', 'Customizable Date Field', 'P', 0, 1, 1, 0, 1, NULL),
(604, 'custom_df5', 'DF', '10/10', 'Custom Date Field #5', 'Customizable Date Field', 'P', 0, 1, 1, 0, 1, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_field_usage'
-- 

DROP TABLE IF EXISTS bug_field_usage;
CREATE TABLE IF NOT EXISTS bug_field_usage (
  bug_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  use_it int(11) NOT NULL default '0',
  show_on_add int(11) NOT NULL default '0',
  show_on_add_members int(11) NOT NULL default '0',
  place int(11) default NULL,
  custom_label varchar(255) default NULL,
  custom_description varchar(255) default NULL,
  custom_display_size varchar(255) default NULL,
  custom_empty_ok int(11) default NULL,
  custom_keep_history int(11) default NULL,
  custom_value_function varchar(255) default NULL,
  KEY idx_bug_fu_field_id (bug_field_id),
  KEY idx_bug_fu_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_field_usage'
-- 

INSERT INTO bug_field_usage (bug_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history, custom_value_function) VALUES (90, 100, 1, 0, 0, 10, NULL, NULL, NULL, NULL, NULL, NULL),
(91, 100, 1, 1, 1, 30, NULL, NULL, NULL, NULL, NULL, NULL),
(92, 100, 1, 0, 0, 20, NULL, NULL, NULL, NULL, NULL, NULL),
(93, 100, 1, 0, 0, 40, NULL, NULL, NULL, NULL, NULL, NULL),
(94, 100, 1, 0, 0, 50, NULL, NULL, NULL, NULL, NULL, NULL),
(101, 100, 1, 0, 0, 600, NULL, NULL, NULL, NULL, NULL, NULL),
(102, 100, 1, 0, 1, 200, NULL, NULL, NULL, NULL, NULL, NULL),
(103, 100, 1, 1, 1, 100, NULL, NULL, NULL, NULL, NULL, NULL),
(104, 100, 1, 0, 1, 500, NULL, NULL, NULL, NULL, NULL, NULL),
(105, 100, 1, 1, 1, 700000, NULL, NULL, NULL, NULL, NULL, NULL),
(106, 100, 1, 1, 1, 700001, NULL, NULL, NULL, NULL, NULL, NULL),
(107, 100, 1, 1, 1, 300, NULL, NULL, NULL, NULL, NULL, NULL),
(108, 100, 1, 0, 0, 400, NULL, NULL, NULL, NULL, NULL, NULL),
(200, 100, 0, 0, 0, 1000, NULL, NULL, NULL, NULL, NULL, NULL),
(201, 100, 0, 0, 0, 1100, NULL, NULL, NULL, NULL, NULL, NULL),
(202, 100, 0, 0, 0, 1200, NULL, NULL, NULL, NULL, NULL, NULL),
(203, 100, 0, 0, 0, 1300, NULL, NULL, NULL, NULL, NULL, NULL),
(204, 100, 0, 0, 0, 1400, NULL, NULL, NULL, NULL, NULL, NULL),
(205, 100, 1, 0, 0, 1500, NULL, NULL, NULL, NULL, NULL, NULL),
(206, 100, 0, 0, 0, 1700, NULL, NULL, NULL, NULL, NULL, NULL),
(207, 100, 0, 0, 0, 1600, NULL, NULL, NULL, NULL, NULL, NULL),
(208, 100, 0, 0, 0, 1800, NULL, NULL, NULL, NULL, NULL, NULL),
(209, 100, 0, 0, 0, 1900, NULL, NULL, NULL, NULL, NULL, NULL),
(210, 100, 0, 0, 0, 2000, NULL, NULL, NULL, NULL, NULL, NULL),
(211, 100, 0, 0, 0, 250, NULL, NULL, NULL, NULL, NULL, NULL),
(212, 100, 0, 0, 0, 3000, NULL, NULL, NULL, NULL, NULL, NULL),
(213, 100, 0, 0, 0, 800, NULL, NULL, NULL, NULL, NULL, NULL),
(214, 100, 0, 0, 0, 800, NULL, NULL, NULL, NULL, NULL, NULL),
(215, 100, 0, 0, 0, 550, NULL, NULL, NULL, NULL, NULL, NULL),
(216, 100, 0, 0, 0, 560, NULL, NULL, NULL, NULL, NULL, NULL),
(217, 100, 0, 0, 0, 570, NULL, NULL, NULL, NULL, NULL, NULL),
(300, 100, 0, 0, 0, 30000, NULL, NULL, NULL, NULL, NULL, NULL),
(301, 100, 0, 0, 0, 30100, NULL, NULL, NULL, NULL, NULL, NULL),
(302, 100, 0, 0, 0, 30200, NULL, NULL, NULL, NULL, NULL, NULL),
(303, 100, 0, 0, 0, 30300, NULL, NULL, NULL, NULL, NULL, NULL),
(304, 100, 0, 0, 0, 30400, NULL, NULL, NULL, NULL, NULL, NULL),
(305, 100, 0, 0, 0, 30500, NULL, NULL, NULL, NULL, NULL, NULL),
(306, 100, 0, 0, 0, 30600, NULL, NULL, NULL, NULL, NULL, NULL),
(307, 100, 0, 0, 0, 30700, NULL, NULL, NULL, NULL, NULL, NULL),
(308, 100, 0, 0, 0, 30800, NULL, NULL, NULL, NULL, NULL, NULL),
(309, 100, 0, 0, 0, 30900, NULL, NULL, NULL, NULL, NULL, NULL),
(400, 100, 0, 0, 0, 40000, NULL, NULL, NULL, NULL, NULL, NULL),
(401, 100, 0, 0, 0, 40100, NULL, NULL, NULL, NULL, NULL, NULL),
(402, 100, 0, 0, 0, 40200, NULL, NULL, NULL, NULL, NULL, NULL),
(403, 100, 0, 0, 0, 40300, NULL, NULL, NULL, NULL, NULL, NULL),
(404, 100, 0, 0, 0, 40400, NULL, NULL, NULL, NULL, NULL, NULL),
(405, 100, 0, 0, 0, 40500, NULL, NULL, NULL, NULL, NULL, NULL),
(406, 100, 0, 0, 0, 40600, NULL, NULL, NULL, NULL, NULL, NULL),
(407, 100, 0, 0, 0, 40700, NULL, NULL, NULL, NULL, NULL, NULL),
(408, 100, 0, 0, 0, 40800, NULL, NULL, NULL, NULL, NULL, NULL),
(409, 100, 0, 0, 0, 40900, NULL, NULL, NULL, NULL, NULL, NULL),
(500, 100, 0, 0, 0, 50000, NULL, NULL, NULL, NULL, NULL, NULL),
(501, 100, 0, 0, 0, 50100, NULL, NULL, NULL, NULL, NULL, NULL),
(502, 100, 0, 0, 0, 50200, NULL, NULL, NULL, NULL, NULL, NULL),
(503, 100, 0, 0, 0, 50300, NULL, NULL, NULL, NULL, NULL, NULL),
(504, 100, 0, 0, 0, 50400, NULL, NULL, NULL, NULL, NULL, NULL),
(505, 100, 0, 0, 0, 50500, NULL, NULL, NULL, NULL, NULL, NULL),
(506, 100, 0, 0, 0, 50600, NULL, NULL, NULL, NULL, NULL, NULL),
(507, 100, 0, 0, 0, 50700, NULL, NULL, NULL, NULL, NULL, NULL),
(508, 100, 0, 0, 0, 50800, NULL, NULL, NULL, NULL, NULL, NULL),
(509, 100, 0, 0, 0, 50900, NULL, NULL, NULL, NULL, NULL, NULL),
(600, 100, 0, 0, 0, 60000, NULL, NULL, NULL, NULL, NULL, NULL),
(601, 100, 0, 0, 0, 60100, NULL, NULL, NULL, NULL, NULL, NULL),
(602, 100, 0, 0, 0, 60200, NULL, NULL, NULL, NULL, NULL, NULL),
(603, 100, 0, 0, 0, 60300, NULL, NULL, NULL, NULL, NULL, NULL),
(604, 100, 0, 0, 0, 60400, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_field_value'
-- 

DROP TABLE IF EXISTS bug_field_value;
CREATE TABLE IF NOT EXISTS bug_field_value (
  bug_fv_id int(11) NOT NULL auto_increment,
  bug_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  `value` text NOT NULL,
  description text NOT NULL,
  order_id int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (bug_fv_id),
  KEY idx_bug_fv_field_id (bug_fv_id),
  KEY idx_bug_fv_group_id (group_id),
  KEY idx_bug_fv_value_id (value_id),
  KEY idx_bug_fv_status (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_field_value'
-- 

INSERT INTO bug_field_value (bug_fv_id, bug_field_id, group_id, value_id, value, description, order_id, status) VALUES (101, 101, 100, 1, 'Open', 'The bug has been submitted', 20, 'P'),
(102, 101, 100, 3, 'Closed', 'The bug is no longer active. See the Resolution field for details on how it was resolved.', 400, 'P'),
(104, 101, 100, 4, 'Analyzed', 'The cause of the bug has been identified and documented', 30, 'H'),
(105, 101, 100, 5, 'Accepted', 'The bug will be worked on. If it won''t be worked on, indicate why in the Resolution field and close it', 50, 'H'),
(106, 101, 100, 6, 'Ready for Review', 'Updated/Created non-software work product (e.g. documentation) is ready for review and approval.', 70, 'H'),
(107, 101, 100, 7, 'Ready for Test', 'Updated/Created software is ready to be included in the next build', 90, 'H'),
(108, 101, 100, 8, 'In Test', 'Updated/Created software is in the build and is ready to enter the test phase', 110, 'H'),
(109, 101, 100, 9, 'Approved', 'The bug fix has been succesfully tested. It is approved and awaiting release.', 130, 'H'),
(110, 101, 100, 10, 'Declined', 'The bug was not accepted. Alternatively, you can also Set the status to "Closed" and use the Resolution field to explain why it was declined', 150, 'H'),
(131, 102, 100, 1, '1 - Ordinary', '', 10, 'A'),
(132, 102, 100, 2, '2', '', 20, 'A'),
(133, 102, 100, 3, '3', '', 30, 'A'),
(134, 102, 100, 4, '4', '', 40, 'A'),
(135, 102, 100, 5, '5 - Major', '', 50, 'A'),
(136, 102, 100, 6, '6', '', 60, 'A'),
(137, 102, 100, 7, '7', '', 70, 'A'),
(138, 102, 100, 8, '8', '', 80, 'A'),
(139, 102, 100, 9, '9 - Critical', '', 90, 'A'),
(150, 103, 100, 100, 'None', '', 10, 'P'),
(160, 107, 100, 100, 'None', '', 10, 'P'),
(170, 108, 100, 100, 'None', '', 10, 'P'),
(171, 108, 100, 1, 'Fixed', 'The bug was resolved', 20, 'A'),
(172, 108, 100, 2, 'Invalid', 'The submitted bug is not valid for some reason (wrong description, using incorrect software version,...)', 30, 'A'),
(173, 108, 100, 3, 'Wont Fix', 'The bug won''t be fixed (probably because it is very minor)', 40, 'A'),
(174, 108, 100, 4, 'Later', 'The bug will be fixed later (no date given)', 50, 'A'),
(175, 108, 100, 5, 'Remind', 'The bug will be fixed later but keep in the remind state for easy identification', 60, 'A'),
(176, 108, 100, 6, 'Works for me', 'The project team was unable to reproduce the bug', 70, 'A'),
(177, 108, 100, 7, 'Duplicate', 'This bug is already covered by another bug description (see related bugs list)', 80, 'A'),
(200, 200, 100, 100, 'None', '', 10, 'P'),
(210, 201, 100, 100, 'None', '', 10, 'P'),
(220, 202, 100, 100, 'None', '', 10, 'P'),
(221, 202, 100, 110, 'Every Time', '', 20, 'P'),
(222, 202, 100, 120, 'Intermittent', '', 30, 'P'),
(223, 202, 100, 130, 'Once', '', 40, 'P'),
(240, 203, 100, 100, 'None', '', 10, 'P'),
(241, 203, 100, 110, 'Low <30', '', 20, 'A'),
(242, 203, 100, 120, 'Medium 30 - 200', '', 30, 'A'),
(243, 203, 100, 130, 'High >200', '', 40, 'A'),
(250, 204, 100, 100, 'None', '', 10, 'P'),
(260, 205, 100, 100, 'None', '', 10, 'P'),
(270, 207, 100, 100, 'None', '', 10, 'P'),
(280, 211, 100, 100, 'None', '', 10, 'P'),
(281, 211, 100, 120, 'Later', '', 20, 'A'),
(282, 211, 100, 130, 'Later+', '', 30, 'H'),
(283, 211, 100, 140, 'Later++', '', 40, 'H'),
(284, 211, 100, 150, 'Low', '', 50, 'A'),
(285, 211, 100, 160, 'Low+', '', 60, 'H'),
(286, 211, 100, 170, 'Low++', '', 70, 'H'),
(287, 211, 100, 180, 'Normal', '', 80, 'A'),
(288, 211, 100, 190, 'Normal+', '', 90, 'H'),
(289, 211, 100, 200, 'Normal++', '', 100, 'H'),
(290, 211, 100, 210, 'High', '', 110, 'A'),
(291, 211, 100, 220, 'High+', '', 120, 'H'),
(292, 211, 100, 230, 'High++', '', 130, 'H'),
(293, 211, 100, 240, 'Immediate', '', 140, 'A'),
(294, 211, 100, 250, 'Immediate+', '', 150, 'H'),
(295, 211, 100, 260, 'Immediate++', '', 160, 'H'),
(300, 213, 100, 100, 'None', '', 10, 'P'),
(400, 500, 100, 100, 'None', '', 10, 'P'),
(401, 501, 100, 100, 'None', '', 10, 'P'),
(402, 502, 100, 100, 'None', '', 10, 'P'),
(403, 503, 100, 100, 'None', '', 10, 'P'),
(404, 504, 100, 100, 'None', '', 10, 'P'),
(405, 505, 100, 100, 'None', '', 10, 'P'),
(406, 506, 100, 100, 'None', '', 10, 'P'),
(407, 507, 100, 100, 'None', '', 10, 'P'),
(408, 508, 100, 100, 'None', '', 10, 'P'),
(409, 509, 100, 100, 'None', '', 10, 'P');

-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_file'
-- 

DROP TABLE IF EXISTS bug_file;
CREATE TABLE IF NOT EXISTS bug_file (
  bug_file_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  description text NOT NULL,
  `file` longblob NOT NULL,
  filename text NOT NULL,
  filesize int(11) NOT NULL default '0',
  filetype text NOT NULL,
  PRIMARY KEY  (bug_file_id),
  KEY bug_id_idx (bug_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_file'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_filter'
-- 

DROP TABLE IF EXISTS bug_filter;
CREATE TABLE IF NOT EXISTS bug_filter (
  filter_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  sql_clause text NOT NULL,
  is_active int(11) NOT NULL default '0',
  PRIMARY KEY  (filter_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_filter'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_history'
-- 

DROP TABLE IF EXISTS bug_history;
CREATE TABLE IF NOT EXISTS bug_history (
  bug_history_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  `date` int(11) default NULL,
  `type` int(11) default NULL,
  PRIMARY KEY  (bug_history_id),
  KEY idx_bug_history_bug_id (bug_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_history'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_notification'
-- 

DROP TABLE IF EXISTS bug_notification;
CREATE TABLE IF NOT EXISTS bug_notification (
  user_id int(11) NOT NULL default '0',
  role_id int(11) NOT NULL default '0',
  event_id int(11) NOT NULL default '0',
  notify int(11) NOT NULL default '1',
  KEY user_id_idx (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_notification'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_notification_event'
-- 

DROP TABLE IF EXISTS bug_notification_event;
CREATE TABLE IF NOT EXISTS bug_notification_event (
  event_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY event_id_idx (event_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_notification_event'
-- 

INSERT INTO bug_notification_event (event_id, event_label, short_description, description, rank) VALUES (1, 'ROLE_CHANGE', 'Role has changed', 'I''m added to or removed from this role', 10),
(2, 'NEW_COMMENT', 'New comment', 'A new followup comment is added', 20),
(3, 'NEW_FILE', 'New attachment', 'A new file attachment is added', 30),
(4, 'CC_CHANGE', 'CC Change', 'A new CC address is added/removed', 40),
(5, 'CLOSED', 'Bug closed', 'The bug is closed', 50),
(6, 'PSS_CHANGE', 'PSS change', 'Priority,Status,Severity changes', 60),
(7, 'ANY_OTHER_CHANGE', 'Any other Changes', 'Any changes not mentioned above', 70),
(8, 'I_MADE_IT', 'I did it', 'I am the author of the change', 80),
(9, 'NEW_BUG', 'New Bug', 'A new bug has been submitted', 90);

-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_notification_role'
-- 

DROP TABLE IF EXISTS bug_notification_role;
CREATE TABLE IF NOT EXISTS bug_notification_role (
  role_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY role_id_idx (role_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_notification_role'
-- 

INSERT INTO bug_notification_role (role_id, role_label, short_description, description, rank) VALUES (1, 'SUBMITTER', 'Submitter', 'The person who submitted the bug', 10),
(2, 'ASSIGNEE', 'Assignee', 'The person to whom the bug was assigned', 20),
(3, 'CC', 'CC', 'The person who is in the CC list', 30),
(4, 'COMMENTER', 'Commenter', 'A person who once posted a follow-up comment', 40);

-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_report'
-- 

DROP TABLE IF EXISTS bug_report;
CREATE TABLE IF NOT EXISTS bug_report (
  report_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '100',
  user_id int(11) NOT NULL default '100',
  name varchar(80) default NULL,
  description varchar(255) default NULL,
  scope char(1) NOT NULL default 'I',
  PRIMARY KEY  (report_id),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY scope_idx (scope)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_report'
-- 

INSERT INTO bug_report (report_id, group_id, user_id, name, description, scope) VALUES (100, 100, 100, 'Default', 'The system default bug report', 'S');

-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_report_field'
-- 

DROP TABLE IF EXISTS bug_report_field;
CREATE TABLE IF NOT EXISTS bug_report_field (
  report_id int(11) NOT NULL default '100',
  field_name varchar(255) default NULL,
  show_on_query int(11) default NULL,
  show_on_result int(11) default NULL,
  place_query int(11) default NULL,
  place_result int(11) default NULL,
  col_width int(11) default NULL,
  KEY profile_id_idx (report_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_report_field'
-- 

INSERT INTO bug_report_field (report_id, field_name, show_on_query, show_on_result, place_query, place_result, col_width) VALUES (100, 'category_id', 1, 0, 10, NULL, NULL),
(100, 'bug_group_id', 1, 0, 20, NULL, NULL),
(100, 'assigned_to', 1, 1, 30, 40, NULL),
(100, 'status_id', 1, 0, 40, NULL, NULL),
(100, 'bug_id', 0, 1, NULL, 10, NULL),
(100, 'summary', 0, 1, NULL, 20, NULL),
(100, 'date', 0, 1, NULL, 30, NULL),
(100, 'submitted_by', 0, 1, NULL, 50, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_task_dependencies'
-- 

DROP TABLE IF EXISTS bug_task_dependencies;
CREATE TABLE IF NOT EXISTS bug_task_dependencies (
  bug_depend_id int(11) NOT NULL auto_increment,
  bug_id int(11) NOT NULL default '0',
  is_dependent_on_task_id int(11) NOT NULL default '0',
  PRIMARY KEY  (bug_depend_id),
  KEY idx_bug_task_dependencies_bug_id (bug_id),
  KEY idx_bug_task_is_dependent_on_task_id (is_dependent_on_task_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_task_dependencies'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'bug_watcher'
-- 

DROP TABLE IF EXISTS bug_watcher;
CREATE TABLE IF NOT EXISTS bug_watcher (
  user_id int(11) NOT NULL default '0',
  watchee_id int(11) NOT NULL default '0',
  KEY user_id_idx (user_id),
  KEY watchee_id_idx (watchee_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'bug_watcher'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'cvs_branches'
-- 

DROP TABLE IF EXISTS cvs_branches;
CREATE TABLE IF NOT EXISTS cvs_branches (
  id mediumint(9) NOT NULL auto_increment,
  branch varchar(64) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY branch (branch)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'cvs_branches'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'cvs_checkins'
-- 

DROP TABLE IF EXISTS cvs_checkins;
CREATE TABLE IF NOT EXISTS cvs_checkins (
  `type` enum('Change','Add','Remove') default NULL,
  ci_when datetime NOT NULL default '0000-00-00 00:00:00',
  whoid mediumint(9) NOT NULL default '0',
  repositoryid mediumint(9) NOT NULL default '0',
  dirid mediumint(9) NOT NULL default '0',
  fileid mediumint(9) NOT NULL default '0',
  revision varchar(32) character set latin1 collate latin1_bin default NULL,
  stickytag varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  branchid mediumint(9) NOT NULL default '0',
  addedlines int(11) NOT NULL default '999',
  removedlines int(11) NOT NULL default '999',
  commitid int(11) NOT NULL default '0',
  descid int(11) NOT NULL default '0',
  UNIQUE KEY repositoryid (repositoryid,dirid,fileid,revision),
  KEY ci_when (ci_when),
  KEY repositoryid_2 (repositoryid),
  KEY dirid (dirid),
  KEY fileid (fileid),
  KEY branchid (branchid)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'cvs_checkins'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'cvs_commits'
-- 

DROP TABLE IF EXISTS cvs_commits;
CREATE TABLE IF NOT EXISTS cvs_commits (
  id mediumint(9) NOT NULL auto_increment,
  comm_when timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  whoid mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY whoid (whoid)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'cvs_commits'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'cvs_descs'
-- 

DROP TABLE IF EXISTS cvs_descs;
CREATE TABLE IF NOT EXISTS cvs_descs (
  id mediumint(9) NOT NULL auto_increment,
  description text,
  `hash` bigint(20) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY `hash` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'cvs_descs'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'cvs_dirs'
-- 

DROP TABLE IF EXISTS cvs_dirs;
CREATE TABLE IF NOT EXISTS cvs_dirs (
  id mediumint(9) NOT NULL auto_increment,
  dir varchar(128) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY dir (dir)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'cvs_dirs'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'cvs_files'
-- 

DROP TABLE IF EXISTS cvs_files;
CREATE TABLE IF NOT EXISTS cvs_files (
  id mediumint(9) NOT NULL auto_increment,
  `file` varchar(128) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY `file` (`file`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'cvs_files'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'cvs_repositories'
-- 

DROP TABLE IF EXISTS cvs_repositories;
CREATE TABLE IF NOT EXISTS cvs_repositories (
  id mediumint(9) NOT NULL auto_increment,
  repository varchar(64) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY repository (repository)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'cvs_repositories'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'cvs_tags'
-- 

DROP TABLE IF EXISTS cvs_tags;
CREATE TABLE IF NOT EXISTS cvs_tags (
  repositoryid mediumint(9) NOT NULL default '0',
  branchid mediumint(9) NOT NULL default '0',
  dirid mediumint(9) NOT NULL default '0',
  fileid mediumint(9) NOT NULL default '0',
  revision varchar(32) character set latin1 collate latin1_bin NOT NULL default '',
  KEY repositoryid_2 (repositoryid),
  KEY dirid (dirid),
  KEY fileid (fileid),
  KEY branchid (branchid)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'cvs_tags'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'db_images'
-- 

DROP TABLE IF EXISTS db_images;
CREATE TABLE IF NOT EXISTS db_images (
  id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  description text NOT NULL,
  bin_data longblob NOT NULL,
  filename text NOT NULL,
  filesize int(11) NOT NULL default '0',
  filetype text NOT NULL,
  width int(11) NOT NULL default '0',
  height int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idx_db_images_group (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'db_images'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'doc_data'
-- 

DROP TABLE IF EXISTS doc_data;
CREATE TABLE IF NOT EXISTS doc_data (
  docid int(11) NOT NULL auto_increment,
  title varchar(255) NOT NULL default '',
  `data` longblob NOT NULL,
  updatedate int(11) NOT NULL default '0',
  createdate int(11) NOT NULL default '0',
  created_by int(11) NOT NULL default '0',
  doc_group int(11) NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  description text,
  filename text,
  filesize int(10) unsigned NOT NULL default '0',
  filetype text,
  PRIMARY KEY  (docid),
  KEY idx_doc_group_doc_group (doc_group)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'doc_data'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'doc_groups'
-- 

DROP TABLE IF EXISTS doc_groups;
CREATE TABLE IF NOT EXISTS doc_groups (
  doc_group int(12) NOT NULL auto_increment,
  groupname varchar(255) NOT NULL default '',
  group_rank int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (doc_group),
  KEY idx_doc_groups_group (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'doc_groups'
-- 

INSERT INTO doc_groups (doc_group, groupname, group_rank, group_id) VALUES (1, 'Documents', 10, 108),
(2, 'Documents', 10, 109),
(3, 'Documents', 10, 110),
(4, 'Documents', 10, 111),
(5, 'Documents', 10, 112),
(6, 'Documents', 10, 113),
(7, 'Documents', 10, 114),
(8, 'Documents', 10, 115),
(9, 'Documents', 10, 116),
(10, 'Documents', 10, 117);

-- --------------------------------------------------------

-- 
-- Table structure for table 'doc_log'
-- 

DROP TABLE IF EXISTS doc_log;
CREATE TABLE IF NOT EXISTS doc_log (
  user_id int(11) NOT NULL default '0',
  docid int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  KEY all_idx (user_id,docid),
  KEY time_idx (`time`),
  KEY docid_idx (docid)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'doc_log'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'feedback'
-- 

DROP TABLE IF EXISTS feedback;
CREATE TABLE IF NOT EXISTS feedback (
  session_hash varchar(32) NOT NULL default '',
  feedback text NOT NULL,
  created_at datetime NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (session_hash)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'feedback'
-- 

INSERT INTO feedback (session_hash, feedback, created_at) VALUES ('fdf4274c336dfa70ddb26f7be2198a41', 'O:8:"feedback":1:{s:4:"logs";a:0:{}}', '2007-03-09 10:43:04'),
('6f21ca9903ea81e07bed9674e4cad908', 'O:8:"feedback":1:{s:4:"logs";a:0:{}}', '2007-02-27 12:17:16');

-- --------------------------------------------------------

-- 
-- Table structure for table 'filedownload_log'
-- 

DROP TABLE IF EXISTS filedownload_log;
CREATE TABLE IF NOT EXISTS filedownload_log (
  user_id int(11) NOT NULL default '0',
  filerelease_id int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  KEY all_idx (user_id,filerelease_id),
  KEY time_idx (`time`),
  KEY filerelease_id_idx (filerelease_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'filedownload_log'
-- 

INSERT INTO filedownload_log (user_id, filerelease_id, time) VALUES (101, 2, 1172764581),
(101, 2, 1172766542);

-- --------------------------------------------------------

-- 
-- Table structure for table 'filemodule'
-- 

DROP TABLE IF EXISTS filemodule;
CREATE TABLE IF NOT EXISTS filemodule (
  filemodule_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  module_name varchar(40) default NULL,
  recent_filerelease varchar(20) NOT NULL default '',
  PRIMARY KEY  (filemodule_id),
  KEY idx_filemodule_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'filemodule'
-- 

INSERT INTO filemodule (filemodule_id, group_id, module_name, recent_filerelease) VALUES (1, 101, 'tda', ''),
(2, 102, 'tda1', ''),
(3, 103, 'tda2', ''),
(4, 104, 'tda3', ''),
(5, 105, 'tda4', ''),
(6, 106, 'tda5', ''),
(7, 107, 'tda6', ''),
(8, 108, 'tda7', ''),
(9, 109, 'tda8', ''),
(10, 110, 'test', ''),
(11, 111, 'testnews', ''),
(12, 112, 'testsvn', ''),
(13, 113, 'testsii', ''),
(14, 114, 'test-t-cvs', ''),
(15, 115, 'test-t-svn', ''),
(16, 116, 'test-t-docman', ''),
(17, 117, 'test-t-forum', '');

-- --------------------------------------------------------

-- 
-- Table structure for table 'filemodule_monitor'
-- 

DROP TABLE IF EXISTS filemodule_monitor;
CREATE TABLE IF NOT EXISTS filemodule_monitor (
  filemodule_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  KEY idx_filemodule_monitor_id (filemodule_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'filemodule_monitor'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'filerelease'
-- 

DROP TABLE IF EXISTS filerelease;
CREATE TABLE IF NOT EXISTS filerelease (
  filerelease_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  unix_box varchar(20) NOT NULL default 'remission',
  unix_partition int(11) NOT NULL default '0',
  text_notes text,
  text_changes text,
  release_version varchar(20) default NULL,
  filename varchar(80) default NULL,
  filemodule_id int(11) NOT NULL default '0',
  file_type varchar(50) default NULL,
  release_time int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  file_size int(11) default NULL,
  post_time int(11) NOT NULL default '0',
  text_format int(11) NOT NULL default '0',
  downloads_week int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'N',
  old_filename varchar(80) NOT NULL default '',
  PRIMARY KEY  (filerelease_id),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY unix_box_idx (unix_box),
  KEY post_time_idx (post_time),
  KEY idx_release_time (release_time)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'filerelease'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'forum'
-- 

DROP TABLE IF EXISTS forum;
CREATE TABLE IF NOT EXISTS forum (
  msg_id int(11) NOT NULL auto_increment,
  group_forum_id int(11) NOT NULL default '0',
  posted_by int(11) NOT NULL default '0',
  `subject` text NOT NULL,
  body text NOT NULL,
  `date` int(11) NOT NULL default '0',
  is_followup_to int(11) NOT NULL default '0',
  thread_id int(11) NOT NULL default '0',
  has_followups int(11) default '0',
  PRIMARY KEY  (msg_id),
  KEY idx_forum_group_forum_id (group_forum_id),
  KEY idx_forum_is_followup_to (is_followup_to),
  KEY idx_forum_thread_id (thread_id),
  KEY idx_forum_id_date (group_forum_id,`date`),
  KEY idx_forum_id_date_followup (group_forum_id,`date`,is_followup_to),
  KEY idx_forum_thread_date_followup (thread_id,`date`,is_followup_to)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'forum'
-- 

INSERT INTO forum (msg_id, group_forum_id, posted_by, subject, body, date, is_followup_to, thread_id, has_followups) VALUES (1, 1, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172501795, 0, 1, 0),
(2, 2, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172501795, 0, 2, 0),
(3, 3, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172501795, 0, 3, 0),
(4, 4, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172503405, 0, 4, 0),
(5, 5, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172503405, 0, 5, 0),
(6, 6, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172503405, 0, 6, 0),
(7, 7, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172503641, 0, 7, 0),
(8, 8, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172503641, 0, 8, 0),
(9, 9, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172503641, 0, 9, 0),
(10, 10, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172503918, 0, 10, 0),
(11, 11, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172503918, 0, 11, 0),
(12, 12, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172503918, 0, 12, 0),
(13, 13, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172504310, 0, 13, 0),
(14, 14, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172504310, 0, 14, 0),
(15, 15, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172504310, 0, 15, 0),
(16, 16, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172504867, 0, 16, 0),
(17, 17, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172504867, 0, 17, 0),
(18, 18, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172504867, 0, 18, 0),
(19, 19, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172506106, 0, 19, 0),
(20, 20, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172506106, 0, 20, 0),
(21, 21, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172506106, 0, 21, 0),
(22, 22, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172507025, 0, 22, 0),
(23, 23, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172507025, 0, 23, 0),
(24, 24, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172507025, 0, 24, 0),
(25, 25, 100, 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 'Welcome to Test Distributed Architecture (Tarisson) Open Discussion', 1172507146, 0, 25, 0),
(26, 26, 100, 'Welcome to Test Distributed Architecture (Tarisson) Help', 'Welcome to Test Distributed Architecture (Tarisson) Help', 1172507146, 0, 26, 0),
(27, 27, 100, 'Welcome to Test Distributed Architecture (Tarisson) Developers', 'Welcome to Test Distributed Architecture (Tarisson) Developers', 1172507146, 0, 27, 0),
(28, 28, 100, 'Welcome to test Open Discussion', 'Welcome to test Open Discussion', 1172761382, 0, 28, 0),
(29, 29, 100, 'Welcome to test Help', 'Welcome to test Help', 1172761382, 0, 29, 0),
(30, 30, 100, 'Welcome to test Developers', 'Welcome to test Developers', 1172761382, 0, 30, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'forum_agg_msg_count'
-- 

DROP TABLE IF EXISTS forum_agg_msg_count;
CREATE TABLE IF NOT EXISTS forum_agg_msg_count (
  group_forum_id int(11) NOT NULL default '0',
  count int(11) NOT NULL default '0',
  PRIMARY KEY  (group_forum_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'forum_agg_msg_count'
-- 

INSERT INTO forum_agg_msg_count (group_forum_id, count) VALUES (1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(11, 1),
(12, 1),
(13, 1),
(14, 1),
(15, 1),
(16, 1),
(17, 1),
(18, 1),
(19, 1),
(20, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'forum_group_list'
-- 

DROP TABLE IF EXISTS forum_group_list;
CREATE TABLE IF NOT EXISTS forum_group_list (
  group_forum_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  forum_name text NOT NULL,
  is_public int(11) NOT NULL default '0',
  description text,
  PRIMARY KEY  (group_forum_id),
  KEY idx_forum_group_list_group_id (group_id),
  FULLTEXT KEY description (description)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'forum_group_list'
-- 

INSERT INTO forum_group_list (group_forum_id, group_id, forum_name, is_public, description) VALUES (1, 101, 'Open Discussion', 1, 'General Discussion'),
(2, 101, 'Help', 1, 'Get Help'),
(3, 101, 'Developers', 0, 'Project Developer Discussion'),
(4, 102, 'Open Discussion', 1, 'General Discussion'),
(5, 102, 'Help', 1, 'Get Help'),
(6, 102, 'Developers', 0, 'Project Developer Discussion'),
(7, 103, 'Open Discussion', 1, 'General Discussion'),
(8, 103, 'Help', 1, 'Get Help'),
(9, 103, 'Developers', 0, 'Project Developer Discussion'),
(10, 104, 'Open Discussion', 1, 'General Discussion'),
(11, 104, 'Help', 1, 'Get Help'),
(12, 104, 'Developers', 0, 'Project Developer Discussion'),
(13, 105, 'Open Discussion', 1, 'General Discussion'),
(14, 105, 'Help', 1, 'Get Help'),
(15, 105, 'Developers', 0, 'Project Developer Discussion'),
(16, 106, 'Open Discussion', 1, 'General Discussion'),
(17, 106, 'Help', 1, 'Get Help'),
(18, 106, 'Developers', 0, 'Project Developer Discussion'),
(19, 107, 'Open Discussion', 1, 'General Discussion'),
(20, 107, 'Help', 1, 'Get Help'),
(21, 107, 'Developers', 0, 'Project Developer Discussion'),
(22, 108, 'Open Discussion', 1, 'General Discussion'),
(23, 108, 'Help', 1, 'Get Help'),
(24, 108, 'Developers', 0, 'Project Developer Discussion'),
(25, 109, 'Open Discussion', 1, 'General Discussion'),
(26, 109, 'Help', 1, 'Get Help'),
(27, 109, 'Developers', 0, 'Project Developer Discussion'),
(28, 110, 'Open Discussion', 1, 'General Discussion'),
(29, 110, 'Help', 1, 'Get Help'),
(30, 110, 'Developers', 0, 'Project Developer Discussion');

-- --------------------------------------------------------

-- 
-- Table structure for table 'forum_monitored_forums'
-- 

DROP TABLE IF EXISTS forum_monitored_forums;
CREATE TABLE IF NOT EXISTS forum_monitored_forums (
  monitor_id int(11) NOT NULL auto_increment,
  forum_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY  (monitor_id),
  KEY idx_forum_monitor_thread_id (forum_id),
  KEY idx_forum_monitor_combo_id (forum_id,user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'forum_monitored_forums'
-- 

INSERT INTO forum_monitored_forums (monitor_id, forum_id, user_id) VALUES (1, 1, 102),
(2, 2, 102),
(3, 3, 102),
(4, 4, 102),
(5, 5, 102),
(6, 6, 102),
(7, 7, 102),
(8, 8, 102),
(9, 9, 102),
(10, 10, 102),
(11, 11, 102),
(12, 12, 102),
(13, 13, 102),
(14, 14, 102),
(15, 15, 102),
(16, 16, 102),
(17, 17, 102),
(18, 18, 102),
(19, 19, 102),
(20, 20, 102),
(21, 21, 102),
(22, 22, 102),
(23, 23, 102),
(24, 24, 102),
(25, 25, 102),
(26, 26, 102),
(27, 27, 102),
(28, 28, 101),
(29, 29, 101),
(30, 30, 101);

-- --------------------------------------------------------

-- 
-- Table structure for table 'forum_saved_place'
-- 

DROP TABLE IF EXISTS forum_saved_place;
CREATE TABLE IF NOT EXISTS forum_saved_place (
  saved_place_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  forum_id int(11) NOT NULL default '0',
  save_date int(11) NOT NULL default '0',
  PRIMARY KEY  (saved_place_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'forum_saved_place'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'forum_thread_id'
-- 

DROP TABLE IF EXISTS forum_thread_id;
CREATE TABLE IF NOT EXISTS forum_thread_id (
  thread_id int(11) NOT NULL auto_increment,
  PRIMARY KEY  (thread_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'forum_thread_id'
-- 

INSERT INTO forum_thread_id (thread_id) VALUES (1),
(2),
(3),
(4),
(5),
(6),
(7),
(8),
(9),
(10),
(11),
(12),
(13),
(14),
(15),
(16),
(17),
(18),
(19),
(20),
(21),
(22),
(23),
(24),
(25),
(26),
(27),
(28),
(29),
(30);

-- --------------------------------------------------------

-- 
-- Table structure for table 'foundry_data'
-- 

DROP TABLE IF EXISTS foundry_data;
CREATE TABLE IF NOT EXISTS foundry_data (
  foundry_id int(11) NOT NULL auto_increment,
  freeform1_html text,
  freeform2_html text,
  sponsor1_html text,
  sponsor2_html text,
  guide_image_id int(11) NOT NULL default '0',
  logo_image_id int(11) NOT NULL default '0',
  trove_categories text,
  PRIMARY KEY  (foundry_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'foundry_data'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'foundry_news'
-- 

DROP TABLE IF EXISTS foundry_news;
CREATE TABLE IF NOT EXISTS foundry_news (
  foundry_news_id int(11) NOT NULL auto_increment,
  foundry_id int(11) NOT NULL default '0',
  news_id int(11) NOT NULL default '0',
  approve_date int(11) NOT NULL default '0',
  is_approved int(11) NOT NULL default '0',
  PRIMARY KEY  (foundry_news_id),
  KEY idx_foundry_news_foundry (foundry_id),
  KEY idx_foundry_news_foundry_approved_date (foundry_id,is_approved,approve_date),
  KEY idx_foundry_news_foundry_approved (foundry_id,is_approved)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'foundry_news'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'foundry_preferred_projects'
-- 

DROP TABLE IF EXISTS foundry_preferred_projects;
CREATE TABLE IF NOT EXISTS foundry_preferred_projects (
  foundry_project_id int(11) NOT NULL auto_increment,
  foundry_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  PRIMARY KEY  (foundry_project_id),
  KEY idx_foundry_project_group (group_id),
  KEY idx_foundry_project_group_rank (group_id,rank)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'foundry_preferred_projects'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'foundry_projects'
-- 

DROP TABLE IF EXISTS foundry_projects;
CREATE TABLE IF NOT EXISTS foundry_projects (
  id int(11) NOT NULL auto_increment,
  foundry_id int(11) NOT NULL default '0',
  project_id int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY idx_foundry_projects_foundry (foundry_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'foundry_projects'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_dlstats_agg'
-- 

DROP TABLE IF EXISTS frs_dlstats_agg;
CREATE TABLE IF NOT EXISTS frs_dlstats_agg (
  file_id int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  downloads_http int(11) NOT NULL default '0',
  downloads_ftp int(11) NOT NULL default '0',
  KEY file_id_idx (file_id),
  KEY day_idx (`day`),
  KEY downloads_http_idx (downloads_http),
  KEY downloads_ftp_idx (downloads_ftp)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_dlstats_agg'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_dlstats_file_agg'
-- 

DROP TABLE IF EXISTS frs_dlstats_file_agg;
CREATE TABLE IF NOT EXISTS frs_dlstats_file_agg (
  file_id int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_dlstats_file_file_id (file_id),
  KEY idx_dlstats_file_day (`day`),
  KEY idx_dlstats_file_down (downloads)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_dlstats_file_agg'
-- 

INSERT INTO frs_dlstats_file_agg (file_id, day, downloads) VALUES (0, 20070222, 0),
(0, 20070223, 0),
(0, 20070224, 0),
(0, 20070225, 0),
(0, 20070226, 0),
(0, 20070227, 0),
(0, 20070228, 0),
(0, 20070301, 0),
(2, 20070301, 2),
(0, 20070302, 0),
(0, 20070303, 0),
(0, 20070304, 0),
(0, 20070305, 0),
(0, 20070306, 0),
(0, 20070307, 0),
(0, 20070308, 0),
(0, 20070309, 0),
(0, 20070310, 0),
(0, 20070311, 0),
(0, 20070312, 0),
(0, 20070313, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_dlstats_filetotal_agg'
-- 

DROP TABLE IF EXISTS frs_dlstats_filetotal_agg;
CREATE TABLE IF NOT EXISTS frs_dlstats_filetotal_agg (
  file_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_stats_agr_tmp_fid (file_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_dlstats_filetotal_agg'
-- 

INSERT INTO frs_dlstats_filetotal_agg (file_id, downloads) VALUES (2, 2);

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_dlstats_group_agg'
-- 

DROP TABLE IF EXISTS frs_dlstats_group_agg;
CREATE TABLE IF NOT EXISTS frs_dlstats_group_agg (
  group_id int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY group_id_idx (group_id),
  KEY day_idx (`day`),
  KEY downloads_idx (downloads)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_dlstats_group_agg'
-- 

INSERT INTO frs_dlstats_group_agg (group_id, day, downloads) VALUES (109, 20070301, 2);

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_dlstats_grouptotal_agg'
-- 

DROP TABLE IF EXISTS frs_dlstats_grouptotal_agg;
CREATE TABLE IF NOT EXISTS frs_dlstats_grouptotal_agg (
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_stats_agr_tmp_gid (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_dlstats_grouptotal_agg'
-- 

INSERT INTO frs_dlstats_grouptotal_agg (group_id, downloads) VALUES (1, 0),
(46, 0),
(109, 2);

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_file'
-- 

DROP TABLE IF EXISTS frs_file;
CREATE TABLE IF NOT EXISTS frs_file (
  file_id int(11) NOT NULL auto_increment,
  filename text,
  release_id int(11) NOT NULL default '0',
  type_id int(11) NOT NULL default '0',
  processor_id int(11) NOT NULL default '0',
  release_time int(11) NOT NULL default '0',
  file_size int(11) NOT NULL default '0',
  post_date int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (file_id),
  KEY idx_frs_file_release_id (release_id),
  KEY idx_frs_file_type (type_id),
  KEY idx_frs_file_date (post_date),
  KEY idx_frs_file_processor (processor_id),
  KEY idx_frs_file_name (filename(45))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_file'
-- 

INSERT INTO frs_file (file_id, filename, release_id, type_id, processor_id, release_time, file_size, post_date, status) VALUES (1, 'p1_r1/Blazed.ttf', 1, 3900, 8000, 1172793600, 77200, 1172581847, 'A'),
(2, 'p1_r2/ghost.gif', 2, 9999, 8000, 1172763991, 1487, 1172763991, 'A');

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_filetype'
-- 

DROP TABLE IF EXISTS frs_filetype;
CREATE TABLE IF NOT EXISTS frs_filetype (
  type_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (type_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_filetype'
-- 

INSERT INTO frs_filetype (type_id, name) VALUES (2000, 'Binary .rpm'),
(3000, 'Binary .zip'),
(3001, 'Binary .bz2'),
(3002, 'Binary .gz'),
(3020, 'Binary .tar.gz, .tgz'),
(3100, 'Binary .jar'),
(3150, 'Binary installer'),
(3900, 'Other Binary File'),
(4000, 'Source .rpm'),
(5000, 'Source .zip'),
(5001, 'Source .bz2'),
(5002, 'Source .gz'),
(5020, 'Source .tar.gz, .tgz'),
(5900, 'Other Source File'),
(8000, '.Documentation (any format)'),
(8001, 'text'),
(8002, 'html'),
(8003, 'pdf'),
(9999, 'Other');

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_package'
-- 

DROP TABLE IF EXISTS frs_package;
CREATE TABLE IF NOT EXISTS frs_package (
  package_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  name text,
  status_id int(11) NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  approve_license tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (package_id),
  KEY idx_package_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_package'
-- 

INSERT INTO frs_package (package_id, group_id, name, status_id, rank, approve_license) VALUES (1, 109, 'P1', 1, 0, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_processor'
-- 

DROP TABLE IF EXISTS frs_processor;
CREATE TABLE IF NOT EXISTS frs_processor (
  processor_id int(11) NOT NULL auto_increment,
  name text,
  rank int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (processor_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_processor'
-- 

INSERT INTO frs_processor (processor_id, name, rank, group_id) VALUES (1000, 'i386', 10, 100),
(2000, 'PPC', 20, 100),
(3000, 'MIPS', 30, 100),
(4000, 'Sparc', 40, 100),
(5000, 'UltraSparc', 50, 100),
(6000, 'IA64', 60, 100),
(7000, 'Alpha', 70, 100),
(8000, 'Any', 80, 100),
(9999, 'Other', 90, 100);

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_release'
-- 

DROP TABLE IF EXISTS frs_release;
CREATE TABLE IF NOT EXISTS frs_release (
  release_id int(11) NOT NULL auto_increment,
  package_id int(11) NOT NULL default '0',
  name text,
  notes text,
  changes text,
  status_id int(11) NOT NULL default '0',
  preformatted int(11) NOT NULL default '0',
  release_date int(11) NOT NULL default '0',
  released_by int(11) NOT NULL default '0',
  PRIMARY KEY  (release_id),
  KEY idx_frs_release_by (released_by),
  KEY idx_frs_release_date (release_date),
  KEY idx_frs_release_package (package_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_release'
-- 

INSERT INTO frs_release (release_id, package_id, name, notes, changes, status_id, preformatted, release_date, released_by) VALUES (1, 1, 'R11', 'test', '', 1, 0, 1172534400, 102),
(2, 1, 'R2', 'hello', '', 1, 0, 1172707200, 101);

-- --------------------------------------------------------

-- 
-- Table structure for table 'frs_status'
-- 

DROP TABLE IF EXISTS frs_status;
CREATE TABLE IF NOT EXISTS frs_status (
  status_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (status_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'frs_status'
-- 

INSERT INTO frs_status (status_id, name) VALUES (1, 'status_active'),
(3, 'status_hidden');

-- --------------------------------------------------------

-- 
-- Table structure for table 'group_cvs_full_history'
-- 

DROP TABLE IF EXISTS group_cvs_full_history;
CREATE TABLE IF NOT EXISTS group_cvs_full_history (
  group_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  cvs_commits int(11) NOT NULL default '0',
  cvs_adds int(11) NOT NULL default '0',
  cvs_checkouts int(11) NOT NULL default '0',
  cvs_browse int(11) NOT NULL default '0',
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY day_idx (`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'group_cvs_full_history'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'group_cvs_history'
-- 

DROP TABLE IF EXISTS group_cvs_history;
CREATE TABLE IF NOT EXISTS group_cvs_history (
  group_id int(11) NOT NULL default '0',
  user_name varchar(80) NOT NULL default '',
  cvs_commits int(11) NOT NULL default '0',
  cvs_commits_wk int(11) NOT NULL default '0',
  cvs_adds int(11) NOT NULL default '0',
  cvs_adds_wk int(11) NOT NULL default '0',
  KEY group_id_idx (group_id),
  KEY user_name_idx (user_name)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'group_cvs_history'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'group_history'
-- 

DROP TABLE IF EXISTS group_history;
CREATE TABLE IF NOT EXISTS group_history (
  group_history_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  `date` int(11) default NULL,
  PRIMARY KEY  (group_history_id),
  KEY idx_group_history_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'group_history'
-- 

INSERT INTO group_history (group_history_id, group_id, field_name, old_value, mod_by, date) VALUES (1, 101, 'approved', 'x', 101, 1172501830),
(2, 109, 'approved', 'x', 101, 1172507212),
(3, 102, 'deleted', 'x', 101, 1172507214),
(4, 103, 'deleted', 'x', 101, 1172507217),
(5, 104, 'deleted', 'x', 101, 1172507219),
(6, 105, 'deleted', 'x', 101, 1172507219),
(7, 105, 'deleted', 'x', 101, 1172507222),
(8, 106, 'deleted', 'x', 101, 1172507223),
(9, 107, 'deleted', 'x', 101, 1172507225),
(10, 108, 'deleted', 'x', 101, 1172507226),
(11, 101, 'status', 'A', 101, 1172507483),
(12, 109, 'perm_granted_for_release %% R1', 'ugroup_registered_users_name_key', 102, 1172581847),
(13, 109, 'perm_granted_for_release %% R1', 'ugroup_registered_users_name_key', 102, 1172582052),
(14, 109, 'perm_granted_for_release %% R11', 'ugroup_registered_users_name_key', 102, 1172584939),
(15, 109, 'perm_granted_for_release %% R2', 'ugroup_registered_users_name_key', 101, 1172763991),
(16, 111, 'approved', 'x', 101, 1174566972),
(17, 111, 'added_user %% news_member', 'news_member', 103, 1174567447),
(18, 112, 'approved', 'x', 101, 1174580341),
(19, 112, 'added_user %% svn_member', 'svn_member', 106, 1174581532),
(20, 113, 'status', 'P', 101, 1174901588),
(21, 117, 'approved', 'x', 101, 1174910782),
(22, 114, 'approved', 'x', 101, 1174910785),
(23, 115, 'approved', 'x', 101, 1174910787),
(24, 116, 'approved', 'x', 101, 1174910788),
(25, 114, 'group_type', '1', 110, 1174911238),
(26, 116, 'group_type', '1', 110, 1174911247),
(27, 117, 'group_type', '1', 110, 1174911254),
(28, 115, 'group_type', '1', 110, 1174911261);

-- --------------------------------------------------------

-- 
-- Table structure for table 'group_svn_full_history'
-- 

DROP TABLE IF EXISTS group_svn_full_history;
CREATE TABLE IF NOT EXISTS group_svn_full_history (
  group_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  svn_commits int(11) NOT NULL default '0',
  svn_adds int(11) NOT NULL default '0',
  svn_deletes int(11) NOT NULL default '0',
  svn_checkouts int(11) NOT NULL default '0',
  svn_access_count int(11) NOT NULL default '0',
  svn_browse int(11) NOT NULL default '0',
  UNIQUE KEY accessid (group_id,user_id,`day`),
  KEY group_id_idx (group_id),
  KEY user_id_idx (user_id),
  KEY day_idx (`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'group_svn_full_history'
-- 

INSERT INTO group_svn_full_history (group_id, user_id, day, svn_commits, svn_adds, svn_deletes, svn_checkouts, svn_access_count, svn_browse) VALUES (109, 101, 20070301, 0, 0, 0, 0, 0, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'group_type'
-- 

DROP TABLE IF EXISTS group_type;
CREATE TABLE IF NOT EXISTS group_type (
  type_id int(11) NOT NULL default '0',
  name text NOT NULL,
  PRIMARY KEY  (type_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'group_type'
-- 

INSERT INTO group_type (type_id, name) VALUES (1, 'project'),
(2, 'template'),
(3, 'test_project');

-- --------------------------------------------------------

-- 
-- Table structure for table 'groups'
-- 

DROP TABLE IF EXISTS groups;
CREATE TABLE IF NOT EXISTS groups (
  group_id int(11) NOT NULL auto_increment,
  group_name varchar(40) default NULL,
  is_public int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  unix_group_name varchar(30) NOT NULL default '',
  unix_box varchar(20) NOT NULL default 'shell1',
  http_domain varchar(80) default NULL,
  short_description varchar(255) default NULL,
  cvs_box varchar(20) NOT NULL default 'cvs1',
  svn_box varchar(20) NOT NULL default 'svn1',
  license varchar(16) default NULL,
  register_purpose text,
  required_software text,
  patents_ips text,
  other_comments text,
  license_other text,
  register_time int(11) NOT NULL default '0',
  rand_hash text,
  hide_members int(11) NOT NULL default '0',
  new_bug_address text NOT NULL,
  new_patch_address text NOT NULL,
  new_support_address text NOT NULL,
  new_task_address text NOT NULL,
  `type` int(11) NOT NULL default '1',
  built_from_template int(11) NOT NULL default '100',
  send_all_bugs int(11) NOT NULL default '0',
  send_all_patches int(11) NOT NULL default '0',
  send_all_support int(11) NOT NULL default '0',
  send_all_tasks int(11) NOT NULL default '0',
  bug_preamble text NOT NULL,
  support_preamble text NOT NULL,
  patch_preamble text NOT NULL,
  pm_preamble text NOT NULL,
  xrx_export_ettm int(11) NOT NULL default '0',
  bug_allow_anon int(11) NOT NULL default '1',
  cvs_tracker int(11) NOT NULL default '1',
  cvs_watch_mode int(11) NOT NULL default '0',
  cvs_events_mailing_list text NOT NULL,
  cvs_events_mailing_header varchar(64) character set latin1 collate latin1_bin default NULL,
  cvs_preamble text NOT NULL,
  svn_tracker int(11) NOT NULL default '1',
  svn_events_mailing_list text NOT NULL,
  svn_events_mailing_header varchar(64) character set latin1 collate latin1_bin default NULL,
  svn_preamble text NOT NULL,
  PRIMARY KEY  (group_id),
  KEY idx_groups_status (`status`),
  KEY idx_groups_public (is_public),
  KEY idx_groups_unix (unix_group_name),
  KEY idx_groups_type (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'groups'
-- 

INSERT INTO groups (group_id, group_name, is_public, status, unix_group_name, unix_box, http_domain, short_description, cvs_box, svn_box, license, register_purpose, required_software, patents_ips, other_comments, license_other, register_time, rand_hash, hide_members, new_bug_address, new_patch_address, new_support_address, new_task_address, type, built_from_template, send_all_bugs, send_all_patches, send_all_support, send_all_tasks, bug_preamble, support_preamble, patch_preamble, pm_preamble, xrx_export_ettm, bug_allow_anon, cvs_tracker, cvs_watch_mode, cvs_events_mailing_list, cvs_events_mailing_header, cvs_preamble, svn_tracker, svn_events_mailing_list, svn_events_mailing_header, svn_preamble) VALUES (1, 'CodeX Administration Project', 1, 'A', 'codex', 'shell1', 'codex.cxtst2.xrce.xerox.com', 'CodeX Administration Project', 'cvs1', 'svn1', 'xrx', '', '', '', '', '', 940000000, '', 0, 'codex-admin@cxtst2.xrce.xerox.com', 'codex-admin@cxtst2.xrce.xerox.com', 'codex-admin@cxtst2.xrce.xerox.com', '', 1, 100, 1, 1, 1, 0, '', '', '', '', 0, 1, 1, 0, '', '', '', 1, '', '', ''),
(46, 'Site News', 0, 'A', 'sitenews', 'shell1', 'sitenews.cxtst2.xrce.xerox.com', 'Site News Private Project. All Site News should be posted from this project', 'cvs1', 'svn1', 'xrx', 'Site News Private Project\r\n\r\n', '', '', '', '', 940000000, '', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', '', '', 0, '', '', ''),
(100, 'Default Site Template', 0, 's', 'none', 'shell1', '', 'The default CodeX template', '', '', '', '', '', '', '', '', 940000000, '', 0, '', '', '', '', 2, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', '', '', 0, '', '', ''),
(101, 'TDA - BUGGY, DO NOT USE', 1, 'D', 'tda', 'shell1', 'tda.brame-farine.grenoble.xrce.xerox.com:8017', 'Test distributed architecture - BUGGY, DO NOT USE', 'cvs1', 'svn1', 'xrx', 'Test distributed architecture - BUGGY, DO NOT USE', '', '', '', '', 1172501794, '923085d606543f36a35b043b1e46142e', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(102, 'Test Distributed Architecture (Tarisson)', 1, 'D', 'tda1', 'shell1', 'tda1.brame-farine.grenoble.xrce.xerox.com:8017', 'tda1', 'cvs1', 'svn1', 'xrx', 'tda1', '', '', '', '', 1172503405, 'bfd6cb4626f680a04bd892e9503d4d84', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(103, 'Test Distributed Architecture (Tarisson)', 1, 'D', 'tda2', 'shell1', 'tda2.brame-farine.grenoble.xrce.xerox.com:8017', 'tda2', 'cvs1', 'svn1', 'xrx', 'tda2', '', '', '', '', 1172503641, '089d1658bda492b1f086efed48adb300', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(104, 'Test Distributed Architecture (Tarisson)', 1, 'D', 'tda3', 'shell1', 'tda3.brame-farine.grenoble.xrce.xerox.com:8017', 'tda3', 'cvs1', 'svn1', 'xrx', 'tda3', '', '', '', '', 1172503918, '1461fc250dfe259f577312140a30d36d', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(105, 'Test Distributed Architecture (Tarisson)', 1, 'D', 'tda4', 'shell1', 'tda4.brame-farine.grenoble.xrce.xerox.com:8017', 'tda4', 'cvs1', 'svn1', 'xrx', 'tda4', '', '', '', '', 1172504310, '41716ed59ab32de5fba11da116f7c06d', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(106, 'Test Distributed Architecture (Tarisson)', 1, 'D', 'tda5', 'shell1', 'tda5.brame-farine.grenoble.xrce.xerox.com:8017', 'tda5', 'cvs1', 'svn1', 'xrx', 'tda5', '', '', '', '', 1172504867, 'df4872f9c2ea4bacb5507ea50e6cfd59', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(107, 'Test Distributed Architecture (Tarisson)', 1, 'D', 'tda6', 'shell1', 'tda6.brame-farine.grenoble.xrce.xerox.com:8017', 'TDA6', 'cvs1', 'svn1', 'xrx', 'TDA6', '', '', '', '', 1172506106, 'bcb72197d70434e208953c51c81779f6', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(108, 'Test Distributed Architecture (Tarisson)', 1, 'D', 'tda7', 'shell1', 'tda7.brame-farine.grenoble.xrce.xerox.com:8017', 'tda7', 'cvs1', 'svn1', 'xrx', 'tda7', '', '', '', '', 1172507025, '180b4be975e99b480d408b6433961d8f', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(109, 'Test Distributed Architecture (Tarisson)', 1, 'A', 'tda8', 'shell1', 'tda8.brame-farine.grenoble.xrce.xerox.com:8017', 'tda8', 'cvs1', 'svn1', 'xrx', 'tda8', '', '', '', '', 1172507146, '8e91eb4010e766ccd7994a9876e51fc5', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(110, 'test', 1, 'P', 'test', 'shell1', 'test.brame-farine.grenoble.xrce.xerox.com:8017', 'roro', 'cvs1', 'svn1', 'xrx', 'roro', '', '', '', '', 1172761382, 'd81062596477ab94278483939b3e8782', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 1, 0, '', NULL, '', 1, '', NULL, ''),
(111, 'Test News', 1, 'A', 'testnews', 'shell1', 'testnews.brame-farine.grenoble.xrce.xerox.com:8017', 'This project is here to test the service ''News''', 'cvs1', 'svn1', 'xrx', ' ', '', '', '', '', 1174566921, 'aba8a2e8925233e0517dc0d7ac8ac9e1', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', NULL, '', 0, '', NULL, ''),
(112, 'Test SVN', 1, 'A', 'testsvn', 'shell1', 'testsvn.cxtst2.xrce.xerox.com', 'This project is here to test the service ''SVN''', 'cvs1', 'svn1', 'xrx', ' ', '', '', '', '', 1174580301, '8871cd454b1db54c106fea637f82d756', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', NULL, '', 0, '', NULL, ''),
(113, 'Test Service In Iframe', 1, 'A', 'testsii', 'shell1', 'testsii.brame-farine.grenoble.xrce.xerox.com:8017', 'Test service in iframe', 'cvs1', 'svn1', 'xrx', ' ', '', '', '', '', 1174901562, '27e9661e033a73a6ad8cefcde965c54d', 0, '', '', '', '', 1, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', NULL, '', 0, '', NULL, ''),
(114, 'Test Template CVS', 1, 'A', 'test-t-cvs', 'shell1', 'test-t-cvs.brame-farine.grenoble.xrce.xerox.com:8017', 'Test template cvs', 'cvs1', 'svn1', 'xrx', ' ', '', '', '', '', 1174910606, '7f39b0200b16745789c0746a83adefb9', 0, '', '', '', '', 2, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', NULL, '', 0, '', NULL, ''),
(115, 'Test Template SVN', 1, 'A', 'test-t-svn', 'shell1', 'test-t-svn.brame-farine.grenoble.xrce.xerox.com:8017', 'Test template SVN', 'cvs1', 'svn1', 'xrx', ' ', '', '', '', '', 1174910666, 'e0c1b67c22324a8f781f40585e762bb5', 0, '', '', '', '', 2, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', NULL, '', 0, '', NULL, ''),
(116, 'Test Template Docman', 1, 'A', 'test-t-docman', 'shell1', 'test-t-docman.brame-farine.grenoble.xrce.xerox.com:8017', 'Test Template Docman', 'cvs1', 'svn1', 'xrx', ' ', '', '', '', '', 1174910712, 'acf06cdd9c744f969958e1f085554c8b', 0, '', '', '', '', 2, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', NULL, '', 0, '', NULL, ''),
(117, 'Test Template Forum', 1, 'A', 'test-t-forum', 'shell1', 'test-t-forum.brame-farine.grenoble.xrce.xerox.com:8017', 'Test Template Forum', 'cvs1', 'svn1', 'xrx', ' ', '', '', '', '', 1174910767, 'f19102623795f38cd191ef83658163a1', 0, '', '', '', '', 2, 100, 0, 0, 0, 0, '', '', '', '', 0, 1, 0, 0, '', NULL, '', 0, '', NULL, '');

-- --------------------------------------------------------

-- 
-- Table structure for table 'image'
-- 

DROP TABLE IF EXISTS image;
CREATE TABLE IF NOT EXISTS image (
  image_id int(11) NOT NULL auto_increment,
  image_category int(11) NOT NULL default '1',
  image_type varchar(40) NOT NULL default '',
  image_data blob,
  group_id int(11) NOT NULL default '0',
  image_bytes int(11) NOT NULL default '0',
  image_caption text,
  organization_id int(11) NOT NULL default '0',
  PRIMARY KEY  (image_id),
  KEY image_category_idx (image_category),
  KEY image_type_idx (image_type),
  KEY group_id_idx (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'image'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'intel_agreement'
-- 

DROP TABLE IF EXISTS intel_agreement;
CREATE TABLE IF NOT EXISTS intel_agreement (
  user_id int(11) NOT NULL default '0',
  message text,
  is_approved int(11) NOT NULL default '0',
  PRIMARY KEY  (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'intel_agreement'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'mail_group_list'
-- 

DROP TABLE IF EXISTS mail_group_list;
CREATE TABLE IF NOT EXISTS mail_group_list (
  group_list_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  list_name text,
  is_public int(11) NOT NULL default '0',
  `password` varchar(16) default NULL,
  list_admin int(11) NOT NULL default '0',
  `status` int(11) NOT NULL default '0',
  description text,
  PRIMARY KEY  (group_list_id),
  KEY idx_mail_group_list_group (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'mail_group_list'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'news_bytes'
-- 

DROP TABLE IF EXISTS news_bytes;
CREATE TABLE IF NOT EXISTS news_bytes (
  id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  is_approved int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  forum_id int(11) NOT NULL default '0',
  summary text,
  details text,
  PRIMARY KEY  (id),
  KEY idx_news_bytes_forum (forum_id),
  KEY idx_news_bytes_group (group_id),
  KEY idx_news_bytes_approved (is_approved)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'news_bytes'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'notifications'
-- 

DROP TABLE IF EXISTS notifications;
CREATE TABLE IF NOT EXISTS notifications (
  user_id int(11) NOT NULL default '0',
  object_id int(11) NOT NULL default '0',
  `type` varchar(100) NOT NULL default '',
  PRIMARY KEY  (user_id,object_id,`type`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'notifications'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'patch'
-- 

DROP TABLE IF EXISTS patch;
CREATE TABLE IF NOT EXISTS patch (
  patch_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  patch_status_id int(11) NOT NULL default '0',
  patch_category_id int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  assigned_to int(11) NOT NULL default '0',
  open_date int(11) NOT NULL default '0',
  summary text,
  code longblob,
  close_date int(11) NOT NULL default '0',
  filename varchar(255) NOT NULL default '',
  filesize varchar(50) NOT NULL default '',
  filetype varchar(50) NOT NULL default '',
  PRIMARY KEY  (patch_id),
  KEY idx_patch_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'patch'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'patch_category'
-- 

DROP TABLE IF EXISTS patch_category;
CREATE TABLE IF NOT EXISTS patch_category (
  patch_category_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  category_name text NOT NULL,
  PRIMARY KEY  (patch_category_id),
  KEY idx_patch_group_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'patch_category'
-- 

INSERT INTO patch_category (patch_category_id, group_id, category_name) VALUES (100, 100, 'None');

-- --------------------------------------------------------

-- 
-- Table structure for table 'patch_history'
-- 

DROP TABLE IF EXISTS patch_history;
CREATE TABLE IF NOT EXISTS patch_history (
  patch_history_id int(11) NOT NULL auto_increment,
  patch_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  `date` int(11) default NULL,
  PRIMARY KEY  (patch_history_id),
  KEY idx_patch_history_patch_id (patch_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'patch_history'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'patch_status'
-- 

DROP TABLE IF EXISTS patch_status;
CREATE TABLE IF NOT EXISTS patch_status (
  patch_status_id int(11) NOT NULL auto_increment,
  status_name text,
  PRIMARY KEY  (patch_status_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'patch_status'
-- 

INSERT INTO patch_status (patch_status_id, status_name) VALUES (1, 'Open'),
(2, 'Closed'),
(3, 'Deleted'),
(4, 'Postponed'),
(100, 'None');

-- --------------------------------------------------------

-- 
-- Table structure for table 'people_skill'
-- 

DROP TABLE IF EXISTS people_skill;
CREATE TABLE IF NOT EXISTS people_skill (
  skill_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (skill_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'people_skill'
-- 

INSERT INTO people_skill (skill_id, name) VALUES (1, '3100 SQL'),
(2, '3110 C/C++'),
(3, '3120 Perl'),
(4, '3130 PHP'),
(5, '3140 Java'),
(6, '3900 Other Prog. Lang.'),
(7, '5100 Chinese'),
(8, '5110 Japanese'),
(9, '5120 Spanish'),
(10, '5130 French'),
(11, '5140 German'),
(12, '5900 Other Spoken Lang.'),
(13, '7100 UNIX Admin'),
(14, '7110 Networking'),
(15, '7120 Security'),
(16, '7130 Writing'),
(17, '7140 Editing'),
(18, '7150 Databases'),
(19, '7900 Other Skill Area');

-- --------------------------------------------------------

-- 
-- Table structure for table 'people_skill_inventory'
-- 

DROP TABLE IF EXISTS people_skill_inventory;
CREATE TABLE IF NOT EXISTS people_skill_inventory (
  skill_inventory_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  skill_id int(11) NOT NULL default '0',
  skill_level_id int(11) NOT NULL default '0',
  skill_year_id int(11) NOT NULL default '0',
  PRIMARY KEY  (skill_inventory_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'people_skill_inventory'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'people_skill_level'
-- 

DROP TABLE IF EXISTS people_skill_level;
CREATE TABLE IF NOT EXISTS people_skill_level (
  skill_level_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (skill_level_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'people_skill_level'
-- 

INSERT INTO people_skill_level (skill_level_id, name) VALUES (10, 'Want to Learn'),
(20, 'Familiar'),
(30, 'Competent'),
(40, 'Wizard'),
(50, 'Wrote The Book'),
(60, 'Wrote It');

-- --------------------------------------------------------

-- 
-- Table structure for table 'people_skill_year'
-- 

DROP TABLE IF EXISTS people_skill_year;
CREATE TABLE IF NOT EXISTS people_skill_year (
  skill_year_id int(11) NOT NULL auto_increment,
  name text,
  PRIMARY KEY  (skill_year_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'people_skill_year'
-- 

INSERT INTO people_skill_year (skill_year_id, name) VALUES (1, '< 6 Months'),
(2, '6 Mo - 2 yr'),
(3, '2 yr - 5 yr'),
(4, '5 yr - 10 yr'),
(5, '> 10 years');

-- --------------------------------------------------------

-- 
-- Table structure for table 'permissions'
-- 

DROP TABLE IF EXISTS permissions;
CREATE TABLE IF NOT EXISTS permissions (
  permission_type text NOT NULL,
  object_id text NOT NULL,
  ugroup_id int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'permissions'
-- 

INSERT INTO permissions (permission_type, object_id, ugroup_id) VALUES ('TRACKER_ACCESS_FULL', '1', 1),
('TRACKER_FIELD_SUBMIT', '1#3', 2),
('TRACKER_FIELD_SUBMIT', '1#4', 2),
('TRACKER_FIELD_SUBMIT', '1#5', 2),
('TRACKER_FIELD_SUBMIT', '1#8', 2),
('TRACKER_FIELD_SUBMIT', '1#9', 2),
('TRACKER_FIELD_SUBMIT', '1#20', 2),
('TRACKER_FIELD_READ', '1#1', 1),
('TRACKER_FIELD_READ', '1#2', 1),
('TRACKER_FIELD_READ', '1#3', 1),
('TRACKER_FIELD_READ', '1#4', 1),
('TRACKER_FIELD_READ', '1#5', 1),
('TRACKER_FIELD_READ', '1#6', 1),
('TRACKER_FIELD_READ', '1#7', 1),
('TRACKER_FIELD_READ', '1#8', 1),
('TRACKER_FIELD_READ', '1#9', 1),
('TRACKER_FIELD_READ', '1#10', 1),
('TRACKER_FIELD_READ', '1#11', 1),
('TRACKER_FIELD_READ', '1#12', 1),
('TRACKER_FIELD_READ', '1#13', 1),
('TRACKER_FIELD_READ', '1#14', 1),
('TRACKER_FIELD_READ', '1#15', 1),
('TRACKER_FIELD_READ', '1#16', 1),
('TRACKER_FIELD_READ', '1#17', 1),
('TRACKER_FIELD_READ', '1#18', 1),
('TRACKER_FIELD_READ', '1#19', 1),
('TRACKER_FIELD_READ', '1#20', 1),
('TRACKER_FIELD_READ', '1#22', 1),
('TRACKER_FIELD_READ', '1#23', 1),
('TRACKER_FIELD_READ', '1#24', 1),
('TRACKER_FIELD_READ', '1#26', 1),
('TRACKER_FIELD_READ', '1#27', 1),
('TRACKER_FIELD_READ', '1#28', 1),
('TRACKER_FIELD_READ', '1#29', 1),
('TRACKER_FIELD_READ', '1#30', 1),
('TRACKER_FIELD_UPDATE', '1#2', 3),
('TRACKER_FIELD_UPDATE', '1#3', 3),
('TRACKER_FIELD_UPDATE', '1#4', 3),
('TRACKER_FIELD_UPDATE', '1#5', 3),
('TRACKER_FIELD_UPDATE', '1#8', 3),
('TRACKER_FIELD_UPDATE', '1#9', 3),
('TRACKER_FIELD_UPDATE', '1#10', 3),
('TRACKER_FIELD_UPDATE', '1#11', 3),
('TRACKER_FIELD_UPDATE', '1#12', 3),
('TRACKER_FIELD_UPDATE', '1#13', 3),
('TRACKER_FIELD_UPDATE', '1#14', 3),
('TRACKER_FIELD_UPDATE', '1#15', 3),
('TRACKER_FIELD_UPDATE', '1#16', 3),
('TRACKER_FIELD_UPDATE', '1#17', 3),
('TRACKER_FIELD_UPDATE', '1#18', 3),
('TRACKER_FIELD_UPDATE', '1#19', 3),
('TRACKER_FIELD_UPDATE', '1#20', 3),
('TRACKER_FIELD_UPDATE', '1#22', 3),
('TRACKER_FIELD_UPDATE', '1#23', 3),
('TRACKER_FIELD_UPDATE', '1#24', 3),
('TRACKER_FIELD_UPDATE', '1#26', 3),
('TRACKER_FIELD_UPDATE', '1#27', 3),
('TRACKER_FIELD_UPDATE', '1#28', 3),
('TRACKER_FIELD_UPDATE', '1#29', 3),
('TRACKER_FIELD_UPDATE', '1#30', 3),
('TRACKER_ACCESS_FULL', '2', 1),
('TRACKER_FIELD_SUBMIT', '2#2', 3),
('TRACKER_FIELD_SUBMIT', '2#4', 3),
('TRACKER_FIELD_SUBMIT', '2#5', 3),
('TRACKER_FIELD_SUBMIT', '2#6', 3),
('TRACKER_FIELD_SUBMIT', '2#7', 3),
('TRACKER_FIELD_SUBMIT', '2#8', 3),
('TRACKER_FIELD_SUBMIT', '2#9', 3),
('TRACKER_FIELD_SUBMIT', '2#12', 3),
('TRACKER_FIELD_SUBMIT', '2#14', 3),
('TRACKER_FIELD_READ', '2#1', 1),
('TRACKER_FIELD_READ', '2#2', 1),
('TRACKER_FIELD_READ', '2#4', 1),
('TRACKER_FIELD_READ', '2#5', 1),
('TRACKER_FIELD_READ', '2#6', 1),
('TRACKER_FIELD_READ', '2#7', 1),
('TRACKER_FIELD_READ', '2#8', 1),
('TRACKER_FIELD_READ', '2#9', 1),
('TRACKER_FIELD_READ', '2#10', 1),
('TRACKER_FIELD_READ', '2#11', 1),
('TRACKER_FIELD_READ', '2#12', 1),
('TRACKER_FIELD_READ', '2#13', 1),
('TRACKER_FIELD_READ', '2#14', 1),
('TRACKER_FIELD_READ', '2#15', 1),
('TRACKER_FIELD_UPDATE', '2#2', 3),
('TRACKER_FIELD_UPDATE', '2#4', 3),
('TRACKER_FIELD_UPDATE', '2#5', 3),
('TRACKER_FIELD_UPDATE', '2#6', 3),
('TRACKER_FIELD_UPDATE', '2#7', 3),
('TRACKER_FIELD_UPDATE', '2#8', 3),
('TRACKER_FIELD_UPDATE', '2#9', 3),
('TRACKER_FIELD_UPDATE', '2#11', 3),
('TRACKER_FIELD_UPDATE', '2#12', 3),
('TRACKER_FIELD_UPDATE', '2#14', 3),
('TRACKER_FIELD_UPDATE', '2#15', 3),
('TRACKER_ACCESS_FULL', '3', 1),
('TRACKER_FIELD_SUBMIT', '3#2', 1),
('TRACKER_FIELD_SUBMIT', '3#3', 1),
('TRACKER_FIELD_SUBMIT', '3#5', 1),
('TRACKER_FIELD_SUBMIT', '3#11', 1),
('TRACKER_FIELD_READ', '3#1', 1),
('TRACKER_FIELD_READ', '3#2', 1),
('TRACKER_FIELD_READ', '3#3', 1),
('TRACKER_FIELD_READ', '3#4', 1),
('TRACKER_FIELD_READ', '3#5', 1),
('TRACKER_FIELD_READ', '3#6', 1),
('TRACKER_FIELD_READ', '3#7', 1),
('TRACKER_FIELD_READ', '3#9', 1),
('TRACKER_FIELD_READ', '3#10', 1),
('TRACKER_FIELD_READ', '3#11', 1),
('TRACKER_FIELD_READ', '3#12', 1),
('TRACKER_FIELD_UPDATE', '3#2', 3),
('TRACKER_FIELD_UPDATE', '3#3', 3),
('TRACKER_FIELD_UPDATE', '3#5', 3),
('TRACKER_FIELD_UPDATE', '3#6', 3),
('TRACKER_FIELD_UPDATE', '3#7', 3),
('TRACKER_FIELD_UPDATE', '3#10', 3),
('TRACKER_FIELD_UPDATE', '3#11', 3),
('TRACKER_FIELD_UPDATE', '3#12', 3),
('TRACKER_ACCESS_FULL', '4', 1),
('TRACKER_FIELD_SUBMIT', '4#4', 3),
('TRACKER_FIELD_SUBMIT', '4#7', 3),
('TRACKER_FIELD_SUBMIT', '4#8', 3),
('TRACKER_FIELD_SUBMIT', '4#9', 3),
('TRACKER_FIELD_SUBMIT', '4#10', 3),
('TRACKER_FIELD_READ', '4#1', 1),
('TRACKER_FIELD_READ', '4#2', 1),
('TRACKER_FIELD_READ', '4#3', 1),
('TRACKER_FIELD_READ', '4#4', 1),
('TRACKER_FIELD_READ', '4#5', 1),
('TRACKER_FIELD_READ', '4#6', 1),
('TRACKER_FIELD_READ', '4#7', 1),
('TRACKER_FIELD_READ', '4#8', 1),
('TRACKER_FIELD_READ', '4#9', 1),
('TRACKER_FIELD_READ', '4#10', 1),
('TRACKER_FIELD_READ', '4#11', 1),
('TRACKER_FIELD_UPDATE', '4#3', 3),
('TRACKER_FIELD_UPDATE', '4#4', 3),
('TRACKER_FIELD_UPDATE', '4#6', 3),
('TRACKER_FIELD_UPDATE', '4#7', 3),
('TRACKER_FIELD_UPDATE', '4#8', 3),
('TRACKER_FIELD_UPDATE', '4#9', 3),
('TRACKER_FIELD_UPDATE', '4#10', 3),
('TRACKER_FIELD_UPDATE', '4#11', 3),
('TRACKER_ACCESS_FULL', '5', 1),
('TRACKER_FIELD_SUBMIT', '5#3', 2),
('TRACKER_FIELD_SUBMIT', '5#5', 2),
('TRACKER_FIELD_SUBMIT', '5#7', 2),
('TRACKER_FIELD_SUBMIT', '5#8', 2),
('TRACKER_FIELD_SUBMIT', '5#10', 2),
('TRACKER_FIELD_READ', '5#1', 1),
('TRACKER_FIELD_READ', '5#2', 1),
('TRACKER_FIELD_READ', '5#3', 1),
('TRACKER_FIELD_READ', '5#4', 1),
('TRACKER_FIELD_READ', '5#5', 1),
('TRACKER_FIELD_READ', '5#6', 1),
('TRACKER_FIELD_READ', '5#7', 1),
('TRACKER_FIELD_READ', '5#8', 1),
('TRACKER_FIELD_READ', '5#9', 1),
('TRACKER_FIELD_READ', '5#10', 1),
('TRACKER_FIELD_READ', '5#11', 1),
('TRACKER_FIELD_READ', '5#12', 1),
('TRACKER_FIELD_UPDATE', '5#3', 3),
('TRACKER_FIELD_UPDATE', '5#5', 3),
('TRACKER_FIELD_UPDATE', '5#6', 3),
('TRACKER_FIELD_UPDATE', '5#7', 3),
('TRACKER_FIELD_UPDATE', '5#8', 3),
('TRACKER_FIELD_UPDATE', '5#9', 3),
('TRACKER_FIELD_UPDATE', '5#10', 3),
('TRACKER_FIELD_UPDATE', '5#11', 3),
('TRACKER_FIELD_UPDATE', '5#12', 3),
('PLUGIN_DOCMAN_READ', '1', 1),
('PLUGIN_DOCMAN_READ', '2', 1),
('PLUGIN_DOCMAN_READ', '3', 1),
('PLUGIN_DOCMAN_READ', '4', 1),
('PLUGIN_DOCMAN_READ', '5', 1),
('PLUGIN_DOCMAN_READ', '6', 1),
('PLUGIN_DOCMAN_READ', '7', 1),
('PLUGIN_DOCMAN_READ', '8', 1),
('PLUGIN_DOCMAN_READ', '9', 1),
('PLUGIN_DOCMAN_READ', '10', 1),
('PLUGIN_DOCMAN_READ', '11', 1),
('PLUGIN_DOCMAN_READ', '12', 1),
('PLUGIN_DOCMAN_READ', '13', 1),
('PLUGIN_DOCMAN_READ', '14', 1),
('PLUGIN_DOCMAN_READ', '15', 1),
('PLUGIN_DOCMAN_READ', '16', 1),
('PLUGIN_DOCMAN_READ', '17', 1),
('PLUGIN_DOCMAN_READ', '18', 1),
('PLUGIN_DOCMAN_READ', '19', 1),
('PLUGIN_DOCMAN_MANAGE', '1', 3),
('PLUGIN_DOCMAN_MANAGE', '2', 3),
('PLUGIN_DOCMAN_MANAGE', '3', 3),
('PLUGIN_DOCMAN_MANAGE', '4', 3),
('PLUGIN_DOCMAN_MANAGE', '5', 3),
('PLUGIN_DOCMAN_MANAGE', '6', 3),
('PLUGIN_DOCMAN_MANAGE', '7', 3),
('PLUGIN_DOCMAN_MANAGE', '8', 3),
('PLUGIN_DOCMAN_MANAGE', '9', 3),
('PLUGIN_DOCMAN_MANAGE', '10', 3),
('PLUGIN_DOCMAN_MANAGE', '11', 3),
('PLUGIN_DOCMAN_MANAGE', '12', 3),
('PLUGIN_DOCMAN_MANAGE', '13', 3),
('PLUGIN_DOCMAN_MANAGE', '14', 3),
('PLUGIN_DOCMAN_MANAGE', '15', 3),
('PLUGIN_DOCMAN_MANAGE', '16', 3),
('PLUGIN_DOCMAN_MANAGE', '17', 3),
('PLUGIN_DOCMAN_MANAGE', '18', 3),
('PLUGIN_DOCMAN_MANAGE', '19', 3),
('PLUGIN_DOCMAN_READ', '20', 2),
('PLUGIN_DOCMAN_WRITE', '20', 3),
('PLUGIN_DOCMAN_MANAGE', '20', 4),
('PLUGIN_DOCMAN_ADMIN', '100', 4),
('TRACKER_ACCESS_FULL', '101', 1),
('PLUGIN_DOCMAN_READ', '101', 1),
('PLUGIN_DOCMAN_MANAGE', '101', 3),
('TRACKER_FIELD_SUBMIT', '101#3', 2),
('TRACKER_FIELD_SUBMIT', '101#4', 2),
('TRACKER_FIELD_SUBMIT', '101#5', 2),
('TRACKER_FIELD_SUBMIT', '101#8', 2),
('TRACKER_FIELD_SUBMIT', '101#9', 2),
('TRACKER_FIELD_SUBMIT', '101#20', 2),
('TRACKER_FIELD_READ', '101#1', 1),
('TRACKER_FIELD_READ', '101#2', 1),
('TRACKER_FIELD_READ', '101#3', 1),
('TRACKER_FIELD_READ', '101#4', 1),
('TRACKER_FIELD_READ', '101#5', 1),
('TRACKER_FIELD_READ', '101#6', 1),
('TRACKER_FIELD_READ', '101#7', 1),
('TRACKER_FIELD_READ', '101#8', 1),
('TRACKER_FIELD_READ', '101#9', 1),
('TRACKER_FIELD_READ', '101#10', 1),
('TRACKER_FIELD_READ', '101#11', 1),
('TRACKER_FIELD_READ', '101#12', 1),
('TRACKER_FIELD_READ', '101#13', 1),
('TRACKER_FIELD_READ', '101#14', 1),
('TRACKER_FIELD_READ', '101#15', 1),
('TRACKER_FIELD_READ', '101#16', 1),
('TRACKER_FIELD_READ', '101#17', 1),
('TRACKER_FIELD_READ', '101#18', 1),
('TRACKER_FIELD_READ', '101#19', 1),
('TRACKER_FIELD_READ', '101#20', 1),
('TRACKER_FIELD_READ', '101#22', 1),
('TRACKER_FIELD_READ', '101#23', 1),
('TRACKER_FIELD_READ', '101#24', 1),
('TRACKER_FIELD_READ', '101#26', 1),
('TRACKER_FIELD_READ', '101#27', 1),
('TRACKER_FIELD_READ', '101#28', 1),
('TRACKER_FIELD_READ', '101#29', 1),
('TRACKER_FIELD_READ', '101#30', 1),
('TRACKER_FIELD_UPDATE', '101#2', 3),
('TRACKER_FIELD_UPDATE', '101#3', 3),
('TRACKER_FIELD_UPDATE', '101#4', 3),
('TRACKER_FIELD_UPDATE', '101#5', 3),
('TRACKER_FIELD_UPDATE', '101#8', 3),
('TRACKER_FIELD_UPDATE', '101#9', 3),
('TRACKER_FIELD_UPDATE', '101#10', 3),
('TRACKER_FIELD_UPDATE', '101#11', 3),
('TRACKER_FIELD_UPDATE', '101#12', 3),
('TRACKER_FIELD_UPDATE', '101#13', 3),
('TRACKER_FIELD_UPDATE', '101#14', 3),
('TRACKER_FIELD_UPDATE', '101#15', 3),
('TRACKER_FIELD_UPDATE', '101#16', 3),
('TRACKER_FIELD_UPDATE', '101#17', 3),
('TRACKER_FIELD_UPDATE', '101#18', 3),
('TRACKER_FIELD_UPDATE', '101#19', 3),
('TRACKER_FIELD_UPDATE', '101#20', 3),
('TRACKER_FIELD_UPDATE', '101#22', 3),
('TRACKER_FIELD_UPDATE', '101#23', 3),
('TRACKER_FIELD_UPDATE', '101#24', 3),
('TRACKER_FIELD_UPDATE', '101#26', 3),
('TRACKER_FIELD_UPDATE', '101#27', 3),
('TRACKER_FIELD_UPDATE', '101#28', 3),
('TRACKER_FIELD_UPDATE', '101#29', 3),
('TRACKER_FIELD_UPDATE', '101#30', 3),
('TRACKER_ACCESS_FULL', '102', 1),
('PLUGIN_DOCMAN_READ', '102', 1),
('PLUGIN_DOCMAN_MANAGE', '102', 3),
('TRACKER_FIELD_SUBMIT', '102#2', 3),
('TRACKER_FIELD_SUBMIT', '102#4', 3),
('TRACKER_FIELD_SUBMIT', '102#5', 3),
('TRACKER_FIELD_SUBMIT', '102#6', 3),
('TRACKER_FIELD_SUBMIT', '102#7', 3),
('TRACKER_FIELD_SUBMIT', '102#8', 3),
('TRACKER_FIELD_SUBMIT', '102#9', 3),
('TRACKER_FIELD_SUBMIT', '102#12', 3),
('TRACKER_FIELD_SUBMIT', '102#14', 3),
('TRACKER_FIELD_READ', '102#1', 1),
('TRACKER_FIELD_READ', '102#2', 1),
('TRACKER_FIELD_READ', '102#4', 1),
('TRACKER_FIELD_READ', '102#5', 1),
('TRACKER_FIELD_READ', '102#6', 1),
('TRACKER_FIELD_READ', '102#7', 1),
('TRACKER_FIELD_READ', '102#8', 1),
('TRACKER_FIELD_READ', '102#9', 1),
('TRACKER_FIELD_READ', '102#10', 1),
('TRACKER_FIELD_READ', '102#11', 1),
('TRACKER_FIELD_READ', '102#12', 1),
('TRACKER_FIELD_READ', '102#13', 1),
('TRACKER_FIELD_READ', '102#14', 1),
('TRACKER_FIELD_READ', '102#15', 1),
('TRACKER_FIELD_UPDATE', '102#2', 3),
('TRACKER_FIELD_UPDATE', '102#4', 3),
('TRACKER_FIELD_UPDATE', '102#5', 3),
('TRACKER_FIELD_UPDATE', '102#6', 3),
('TRACKER_FIELD_UPDATE', '102#7', 3),
('TRACKER_FIELD_UPDATE', '102#8', 3),
('TRACKER_FIELD_UPDATE', '102#9', 3),
('TRACKER_FIELD_UPDATE', '102#11', 3),
('TRACKER_FIELD_UPDATE', '102#12', 3),
('TRACKER_FIELD_UPDATE', '102#14', 3),
('TRACKER_FIELD_UPDATE', '102#15', 3),
('TRACKER_ACCESS_FULL', '103', 1),
('PLUGIN_DOCMAN_READ', '103', 1),
('PLUGIN_DOCMAN_MANAGE', '103', 3),
('TRACKER_FIELD_SUBMIT', '103#2', 1),
('TRACKER_FIELD_SUBMIT', '103#3', 1),
('TRACKER_FIELD_SUBMIT', '103#5', 1),
('TRACKER_FIELD_SUBMIT', '103#11', 1),
('TRACKER_FIELD_READ', '103#1', 1),
('TRACKER_FIELD_READ', '103#2', 1),
('TRACKER_FIELD_READ', '103#3', 1),
('TRACKER_FIELD_READ', '103#4', 1),
('TRACKER_FIELD_READ', '103#5', 1),
('TRACKER_FIELD_READ', '103#6', 1),
('TRACKER_FIELD_READ', '103#7', 1),
('TRACKER_FIELD_READ', '103#9', 1),
('TRACKER_FIELD_READ', '103#10', 1),
('TRACKER_FIELD_READ', '103#11', 1),
('TRACKER_FIELD_READ', '103#12', 1),
('TRACKER_FIELD_UPDATE', '103#2', 3),
('TRACKER_FIELD_UPDATE', '103#3', 3),
('TRACKER_FIELD_UPDATE', '103#5', 3),
('TRACKER_FIELD_UPDATE', '103#6', 3),
('TRACKER_FIELD_UPDATE', '103#7', 3),
('TRACKER_FIELD_UPDATE', '103#10', 3),
('TRACKER_FIELD_UPDATE', '103#11', 3),
('TRACKER_FIELD_UPDATE', '103#12', 3),
('TRACKER_ACCESS_FULL', '104', 1),
('PLUGIN_DOCMAN_READ', '104', 1),
('PLUGIN_DOCMAN_MANAGE', '104', 3),
('TRACKER_FIELD_SUBMIT', '104#3', 2),
('TRACKER_FIELD_SUBMIT', '104#5', 2),
('TRACKER_FIELD_SUBMIT', '104#7', 2),
('TRACKER_FIELD_SUBMIT', '104#8', 2),
('TRACKER_FIELD_SUBMIT', '104#10', 2),
('TRACKER_FIELD_READ', '104#1', 1),
('TRACKER_FIELD_READ', '104#2', 1),
('TRACKER_FIELD_READ', '104#3', 1),
('TRACKER_FIELD_READ', '104#4', 1),
('TRACKER_FIELD_READ', '104#5', 1),
('TRACKER_FIELD_READ', '104#6', 1),
('TRACKER_FIELD_READ', '104#7', 1),
('TRACKER_FIELD_READ', '104#8', 1),
('TRACKER_FIELD_READ', '104#9', 1),
('TRACKER_FIELD_READ', '104#10', 1),
('TRACKER_FIELD_READ', '104#11', 1),
('TRACKER_FIELD_READ', '104#12', 1),
('TRACKER_FIELD_UPDATE', '104#3', 3),
('TRACKER_FIELD_UPDATE', '104#5', 3),
('TRACKER_FIELD_UPDATE', '104#6', 3),
('TRACKER_FIELD_UPDATE', '104#7', 3),
('TRACKER_FIELD_UPDATE', '104#8', 3),
('TRACKER_FIELD_UPDATE', '104#9', 3),
('TRACKER_FIELD_UPDATE', '104#10', 3),
('TRACKER_FIELD_UPDATE', '104#11', 3),
('TRACKER_FIELD_UPDATE', '104#12', 3),
('PLUGIN_DOCMAN_ADMIN', '108', 4),
('PLUGIN_DOCMAN_READ', '21', 2),
('PLUGIN_DOCMAN_WRITE', '21', 3),
('PLUGIN_DOCMAN_MANAGE', '21', 4),
('TRACKER_ACCESS_FULL', '105', 1),
('PLUGIN_DOCMAN_READ', '105', 1),
('PLUGIN_DOCMAN_MANAGE', '105', 3),
('TRACKER_FIELD_SUBMIT', '105#3', 2),
('TRACKER_FIELD_SUBMIT', '105#4', 2),
('TRACKER_FIELD_SUBMIT', '105#5', 2),
('TRACKER_FIELD_SUBMIT', '105#8', 2),
('TRACKER_FIELD_SUBMIT', '105#9', 2),
('TRACKER_FIELD_SUBMIT', '105#20', 2),
('TRACKER_FIELD_READ', '105#1', 1),
('TRACKER_FIELD_READ', '105#2', 1),
('TRACKER_FIELD_READ', '105#3', 1),
('TRACKER_FIELD_READ', '105#4', 1),
('TRACKER_FIELD_READ', '105#5', 1),
('TRACKER_FIELD_READ', '105#6', 1),
('TRACKER_FIELD_READ', '105#7', 1),
('TRACKER_FIELD_READ', '105#8', 1),
('TRACKER_FIELD_READ', '105#9', 1),
('TRACKER_FIELD_READ', '105#10', 1),
('TRACKER_FIELD_READ', '105#11', 1),
('TRACKER_FIELD_READ', '105#12', 1),
('TRACKER_FIELD_READ', '105#13', 1),
('TRACKER_FIELD_READ', '105#14', 1),
('TRACKER_FIELD_READ', '105#15', 1),
('TRACKER_FIELD_READ', '105#16', 1),
('TRACKER_FIELD_READ', '105#17', 1),
('TRACKER_FIELD_READ', '105#18', 1),
('TRACKER_FIELD_READ', '105#19', 1),
('TRACKER_FIELD_READ', '105#20', 1),
('TRACKER_FIELD_READ', '105#22', 1),
('TRACKER_FIELD_READ', '105#23', 1),
('TRACKER_FIELD_READ', '105#24', 1),
('TRACKER_FIELD_READ', '105#26', 1),
('TRACKER_FIELD_READ', '105#27', 1),
('TRACKER_FIELD_READ', '105#28', 1),
('TRACKER_FIELD_READ', '105#29', 1),
('TRACKER_FIELD_READ', '105#30', 1),
('TRACKER_FIELD_UPDATE', '105#2', 3),
('TRACKER_FIELD_UPDATE', '105#3', 3),
('TRACKER_FIELD_UPDATE', '105#4', 3),
('TRACKER_FIELD_UPDATE', '105#5', 3),
('TRACKER_FIELD_UPDATE', '105#8', 3),
('TRACKER_FIELD_UPDATE', '105#9', 3),
('TRACKER_FIELD_UPDATE', '105#10', 3),
('TRACKER_FIELD_UPDATE', '105#11', 3),
('TRACKER_FIELD_UPDATE', '105#12', 3),
('TRACKER_FIELD_UPDATE', '105#13', 3),
('TRACKER_FIELD_UPDATE', '105#14', 3),
('TRACKER_FIELD_UPDATE', '105#15', 3),
('TRACKER_FIELD_UPDATE', '105#16', 3),
('TRACKER_FIELD_UPDATE', '105#17', 3),
('TRACKER_FIELD_UPDATE', '105#18', 3),
('TRACKER_FIELD_UPDATE', '105#19', 3),
('TRACKER_FIELD_UPDATE', '105#20', 3),
('TRACKER_FIELD_UPDATE', '105#22', 3),
('TRACKER_FIELD_UPDATE', '105#23', 3),
('TRACKER_FIELD_UPDATE', '105#24', 3),
('TRACKER_FIELD_UPDATE', '105#26', 3),
('TRACKER_FIELD_UPDATE', '105#27', 3),
('TRACKER_FIELD_UPDATE', '105#28', 3),
('TRACKER_FIELD_UPDATE', '105#29', 3),
('TRACKER_FIELD_UPDATE', '105#30', 3),
('TRACKER_ACCESS_FULL', '106', 1),
('PLUGIN_DOCMAN_READ', '106', 1),
('PLUGIN_DOCMAN_MANAGE', '106', 3),
('TRACKER_FIELD_SUBMIT', '106#2', 3),
('TRACKER_FIELD_SUBMIT', '106#4', 3),
('TRACKER_FIELD_SUBMIT', '106#5', 3),
('TRACKER_FIELD_SUBMIT', '106#6', 3),
('TRACKER_FIELD_SUBMIT', '106#7', 3),
('TRACKER_FIELD_SUBMIT', '106#8', 3),
('TRACKER_FIELD_SUBMIT', '106#9', 3),
('TRACKER_FIELD_SUBMIT', '106#12', 3),
('TRACKER_FIELD_SUBMIT', '106#14', 3),
('TRACKER_FIELD_READ', '106#1', 1),
('TRACKER_FIELD_READ', '106#2', 1),
('TRACKER_FIELD_READ', '106#4', 1),
('TRACKER_FIELD_READ', '106#5', 1),
('TRACKER_FIELD_READ', '106#6', 1),
('TRACKER_FIELD_READ', '106#7', 1),
('TRACKER_FIELD_READ', '106#8', 1),
('TRACKER_FIELD_READ', '106#9', 1),
('TRACKER_FIELD_READ', '106#10', 1),
('TRACKER_FIELD_READ', '106#11', 1),
('TRACKER_FIELD_READ', '106#12', 1),
('TRACKER_FIELD_READ', '106#13', 1),
('TRACKER_FIELD_READ', '106#14', 1),
('TRACKER_FIELD_READ', '106#15', 1),
('TRACKER_FIELD_UPDATE', '106#2', 3),
('TRACKER_FIELD_UPDATE', '106#4', 3),
('TRACKER_FIELD_UPDATE', '106#5', 3),
('TRACKER_FIELD_UPDATE', '106#6', 3),
('TRACKER_FIELD_UPDATE', '106#7', 3),
('TRACKER_FIELD_UPDATE', '106#8', 3),
('TRACKER_FIELD_UPDATE', '106#9', 3),
('TRACKER_FIELD_UPDATE', '106#11', 3),
('TRACKER_FIELD_UPDATE', '106#12', 3),
('TRACKER_FIELD_UPDATE', '106#14', 3),
('TRACKER_FIELD_UPDATE', '106#15', 3),
('TRACKER_ACCESS_FULL', '107', 1),
('PLUGIN_DOCMAN_READ', '107', 1),
('PLUGIN_DOCMAN_MANAGE', '107', 3),
('TRACKER_FIELD_SUBMIT', '107#2', 1),
('TRACKER_FIELD_SUBMIT', '107#3', 1),
('TRACKER_FIELD_SUBMIT', '107#5', 1),
('TRACKER_FIELD_SUBMIT', '107#11', 1),
('TRACKER_FIELD_READ', '107#1', 1),
('TRACKER_FIELD_READ', '107#2', 1),
('TRACKER_FIELD_READ', '107#3', 1),
('TRACKER_FIELD_READ', '107#4', 1),
('TRACKER_FIELD_READ', '107#5', 1),
('TRACKER_FIELD_READ', '107#6', 1),
('TRACKER_FIELD_READ', '107#7', 1),
('TRACKER_FIELD_READ', '107#9', 1),
('TRACKER_FIELD_READ', '107#10', 1),
('TRACKER_FIELD_READ', '107#11', 1),
('TRACKER_FIELD_READ', '107#12', 1),
('TRACKER_FIELD_UPDATE', '107#2', 3),
('TRACKER_FIELD_UPDATE', '107#3', 3),
('TRACKER_FIELD_UPDATE', '107#5', 3),
('TRACKER_FIELD_UPDATE', '107#6', 3),
('TRACKER_FIELD_UPDATE', '107#7', 3),
('TRACKER_FIELD_UPDATE', '107#10', 3),
('TRACKER_FIELD_UPDATE', '107#11', 3),
('TRACKER_FIELD_UPDATE', '107#12', 3),
('TRACKER_ACCESS_FULL', '108', 1),
('PLUGIN_DOCMAN_READ', '108', 1),
('PLUGIN_DOCMAN_MANAGE', '108', 3),
('TRACKER_FIELD_SUBMIT', '108#3', 2),
('TRACKER_FIELD_SUBMIT', '108#5', 2),
('TRACKER_FIELD_SUBMIT', '108#7', 2),
('TRACKER_FIELD_SUBMIT', '108#8', 2),
('TRACKER_FIELD_SUBMIT', '108#10', 2),
('TRACKER_FIELD_READ', '108#1', 1),
('TRACKER_FIELD_READ', '108#2', 1),
('TRACKER_FIELD_READ', '108#3', 1),
('TRACKER_FIELD_READ', '108#4', 1),
('TRACKER_FIELD_READ', '108#5', 1),
('TRACKER_FIELD_READ', '108#6', 1),
('TRACKER_FIELD_READ', '108#7', 1),
('TRACKER_FIELD_READ', '108#8', 1),
('TRACKER_FIELD_READ', '108#9', 1),
('TRACKER_FIELD_READ', '108#10', 1),
('TRACKER_FIELD_READ', '108#11', 1),
('TRACKER_FIELD_READ', '108#12', 1),
('TRACKER_FIELD_UPDATE', '108#3', 3),
('TRACKER_FIELD_UPDATE', '108#5', 3),
('TRACKER_FIELD_UPDATE', '108#6', 3),
('TRACKER_FIELD_UPDATE', '108#7', 3),
('TRACKER_FIELD_UPDATE', '108#8', 3),
('TRACKER_FIELD_UPDATE', '108#9', 3),
('TRACKER_FIELD_UPDATE', '108#10', 3),
('TRACKER_FIELD_UPDATE', '108#11', 3),
('TRACKER_FIELD_UPDATE', '108#12', 3),
('PLUGIN_DOCMAN_ADMIN', '109', 4),
('PLUGIN_DOCMAN_READ', '22', 2),
('PLUGIN_DOCMAN_WRITE', '22', 3),
('PLUGIN_DOCMAN_MANAGE', '22', 4),
('PACKAGE_READ', '1', 2),
('RELEASE_READ', '1', 2),
('TRACKER_ACCESS_FULL', '109', 1),
('PLUGIN_DOCMAN_READ', '109', 1),
('PLUGIN_DOCMAN_MANAGE', '109', 3),
('PACKAGE_READ', '109', 2),
('RELEASE_READ', '109', 2),
('TRACKER_FIELD_SUBMIT', '109#3', 2),
('TRACKER_FIELD_SUBMIT', '109#4', 2),
('TRACKER_FIELD_SUBMIT', '109#5', 2),
('TRACKER_FIELD_SUBMIT', '109#8', 2),
('TRACKER_FIELD_SUBMIT', '109#9', 2),
('TRACKER_FIELD_SUBMIT', '109#20', 2),
('TRACKER_FIELD_READ', '109#1', 1),
('TRACKER_FIELD_READ', '109#2', 1),
('TRACKER_FIELD_READ', '109#3', 1),
('TRACKER_FIELD_READ', '109#4', 1),
('TRACKER_FIELD_READ', '109#5', 1),
('TRACKER_FIELD_READ', '109#6', 1),
('TRACKER_FIELD_READ', '109#7', 1),
('TRACKER_FIELD_READ', '109#8', 1),
('TRACKER_FIELD_READ', '109#9', 1),
('TRACKER_FIELD_READ', '109#10', 1),
('TRACKER_FIELD_READ', '109#11', 1),
('TRACKER_FIELD_READ', '109#12', 1),
('TRACKER_FIELD_READ', '109#13', 1),
('TRACKER_FIELD_READ', '109#14', 1),
('TRACKER_FIELD_READ', '109#15', 1),
('TRACKER_FIELD_READ', '109#16', 1),
('TRACKER_FIELD_READ', '109#17', 1),
('TRACKER_FIELD_READ', '109#18', 1),
('TRACKER_FIELD_READ', '109#19', 1),
('TRACKER_FIELD_READ', '109#20', 1),
('TRACKER_FIELD_READ', '109#22', 1),
('TRACKER_FIELD_READ', '109#23', 1),
('TRACKER_FIELD_READ', '109#24', 1),
('TRACKER_FIELD_READ', '109#26', 1),
('TRACKER_FIELD_READ', '109#27', 1),
('TRACKER_FIELD_READ', '109#28', 1),
('TRACKER_FIELD_READ', '109#29', 1),
('TRACKER_FIELD_READ', '109#30', 1),
('TRACKER_FIELD_UPDATE', '109#2', 3),
('TRACKER_FIELD_UPDATE', '109#3', 3),
('TRACKER_FIELD_UPDATE', '109#4', 3),
('TRACKER_FIELD_UPDATE', '109#5', 3),
('TRACKER_FIELD_UPDATE', '109#8', 3),
('TRACKER_FIELD_UPDATE', '109#9', 3),
('TRACKER_FIELD_UPDATE', '109#10', 3),
('TRACKER_FIELD_UPDATE', '109#11', 3),
('TRACKER_FIELD_UPDATE', '109#12', 3),
('TRACKER_FIELD_UPDATE', '109#13', 3),
('TRACKER_FIELD_UPDATE', '109#14', 3),
('TRACKER_FIELD_UPDATE', '109#15', 3),
('TRACKER_FIELD_UPDATE', '109#16', 3),
('TRACKER_FIELD_UPDATE', '109#17', 3),
('TRACKER_FIELD_UPDATE', '109#18', 3),
('TRACKER_FIELD_UPDATE', '109#19', 3),
('TRACKER_FIELD_UPDATE', '109#20', 3),
('TRACKER_FIELD_UPDATE', '109#22', 3),
('TRACKER_FIELD_UPDATE', '109#23', 3),
('TRACKER_FIELD_UPDATE', '109#24', 3),
('TRACKER_FIELD_UPDATE', '109#26', 3),
('TRACKER_FIELD_UPDATE', '109#27', 3),
('TRACKER_FIELD_UPDATE', '109#28', 3),
('TRACKER_FIELD_UPDATE', '109#29', 3),
('TRACKER_FIELD_UPDATE', '109#30', 3),
('TRACKER_ACCESS_FULL', '110', 1),
('PLUGIN_DOCMAN_READ', '110', 1),
('PLUGIN_DOCMAN_MANAGE', '110', 3),
('TRACKER_FIELD_SUBMIT', '110#2', 3),
('TRACKER_FIELD_SUBMIT', '110#4', 3),
('TRACKER_FIELD_SUBMIT', '110#5', 3),
('TRACKER_FIELD_SUBMIT', '110#6', 3),
('TRACKER_FIELD_SUBMIT', '110#7', 3),
('TRACKER_FIELD_SUBMIT', '110#8', 3),
('TRACKER_FIELD_SUBMIT', '110#9', 3),
('TRACKER_FIELD_SUBMIT', '110#12', 3),
('TRACKER_FIELD_SUBMIT', '110#14', 3),
('TRACKER_FIELD_READ', '110#1', 1),
('TRACKER_FIELD_READ', '110#2', 1),
('TRACKER_FIELD_READ', '110#4', 1),
('TRACKER_FIELD_READ', '110#5', 1),
('TRACKER_FIELD_READ', '110#6', 1),
('TRACKER_FIELD_READ', '110#7', 1),
('TRACKER_FIELD_READ', '110#8', 1),
('TRACKER_FIELD_READ', '110#9', 1),
('TRACKER_FIELD_READ', '110#10', 1),
('TRACKER_FIELD_READ', '110#11', 1),
('TRACKER_FIELD_READ', '110#12', 1),
('TRACKER_FIELD_READ', '110#13', 1),
('TRACKER_FIELD_READ', '110#14', 1),
('TRACKER_FIELD_READ', '110#15', 1),
('TRACKER_FIELD_UPDATE', '110#2', 3),
('TRACKER_FIELD_UPDATE', '110#4', 3),
('TRACKER_FIELD_UPDATE', '110#5', 3),
('TRACKER_FIELD_UPDATE', '110#6', 3),
('TRACKER_FIELD_UPDATE', '110#7', 3),
('TRACKER_FIELD_UPDATE', '110#8', 3),
('TRACKER_FIELD_UPDATE', '110#9', 3),
('TRACKER_FIELD_UPDATE', '110#11', 3),
('TRACKER_FIELD_UPDATE', '110#12', 3),
('TRACKER_FIELD_UPDATE', '110#14', 3),
('TRACKER_FIELD_UPDATE', '110#15', 3),
('TRACKER_ACCESS_FULL', '111', 1),
('PLUGIN_DOCMAN_READ', '111', 1),
('PLUGIN_DOCMAN_MANAGE', '111', 3),
('TRACKER_FIELD_SUBMIT', '111#2', 1),
('TRACKER_FIELD_SUBMIT', '111#3', 1),
('TRACKER_FIELD_SUBMIT', '111#5', 1),
('TRACKER_FIELD_SUBMIT', '111#11', 1),
('TRACKER_FIELD_READ', '111#1', 1),
('TRACKER_FIELD_READ', '111#2', 1),
('TRACKER_FIELD_READ', '111#3', 1),
('TRACKER_FIELD_READ', '111#4', 1),
('TRACKER_FIELD_READ', '111#5', 1),
('TRACKER_FIELD_READ', '111#6', 1),
('TRACKER_FIELD_READ', '111#7', 1),
('TRACKER_FIELD_READ', '111#9', 1),
('TRACKER_FIELD_READ', '111#10', 1),
('TRACKER_FIELD_READ', '111#11', 1),
('TRACKER_FIELD_READ', '111#12', 1),
('TRACKER_FIELD_UPDATE', '111#2', 3),
('TRACKER_FIELD_UPDATE', '111#3', 3),
('TRACKER_FIELD_UPDATE', '111#5', 3),
('TRACKER_FIELD_UPDATE', '111#6', 3),
('TRACKER_FIELD_UPDATE', '111#7', 3),
('TRACKER_FIELD_UPDATE', '111#10', 3),
('TRACKER_FIELD_UPDATE', '111#11', 3),
('TRACKER_FIELD_UPDATE', '111#12', 3),
('TRACKER_ACCESS_FULL', '112', 1),
('PLUGIN_DOCMAN_READ', '112', 1),
('PLUGIN_DOCMAN_MANAGE', '112', 3),
('TRACKER_FIELD_SUBMIT', '112#3', 2),
('TRACKER_FIELD_SUBMIT', '112#5', 2),
('TRACKER_FIELD_SUBMIT', '112#7', 2),
('TRACKER_FIELD_SUBMIT', '112#8', 2),
('TRACKER_FIELD_SUBMIT', '112#10', 2),
('TRACKER_FIELD_READ', '112#1', 1),
('TRACKER_FIELD_READ', '112#2', 1),
('TRACKER_FIELD_READ', '112#3', 1),
('TRACKER_FIELD_READ', '112#4', 1),
('TRACKER_FIELD_READ', '112#5', 1),
('TRACKER_FIELD_READ', '112#6', 1),
('TRACKER_FIELD_READ', '112#7', 1),
('TRACKER_FIELD_READ', '112#8', 1),
('TRACKER_FIELD_READ', '112#9', 1),
('TRACKER_FIELD_READ', '112#10', 1),
('TRACKER_FIELD_READ', '112#11', 1),
('TRACKER_FIELD_READ', '112#12', 1),
('TRACKER_FIELD_UPDATE', '112#3', 3),
('TRACKER_FIELD_UPDATE', '112#5', 3),
('TRACKER_FIELD_UPDATE', '112#6', 3),
('TRACKER_FIELD_UPDATE', '112#7', 3),
('TRACKER_FIELD_UPDATE', '112#8', 3),
('TRACKER_FIELD_UPDATE', '112#9', 3),
('TRACKER_FIELD_UPDATE', '112#10', 3),
('TRACKER_FIELD_UPDATE', '112#11', 3),
('TRACKER_FIELD_UPDATE', '112#12', 3),
('PLUGIN_DOCMAN_ADMIN', '110', 4),
('PLUGIN_DOCMAN_READ', '23', 2),
('PLUGIN_DOCMAN_WRITE', '23', 3),
('PLUGIN_DOCMAN_MANAGE', '23', 4),
('RELEASE_READ', '2', 2),
('TRACKER_ACCESS_FULL', '113', 1),
('PLUGIN_DOCMAN_READ', '113', 1),
('PLUGIN_DOCMAN_MANAGE', '113', 3),
('PACKAGE_READ', '113', 2),
('RELEASE_READ', '113', 2),
('TRACKER_FIELD_SUBMIT', '113#3', 2),
('TRACKER_FIELD_SUBMIT', '113#4', 2),
('TRACKER_FIELD_SUBMIT', '113#5', 2),
('TRACKER_FIELD_SUBMIT', '113#8', 2),
('TRACKER_FIELD_SUBMIT', '113#9', 2),
('TRACKER_FIELD_SUBMIT', '113#20', 2),
('TRACKER_FIELD_READ', '113#1', 1),
('TRACKER_FIELD_READ', '113#2', 1),
('TRACKER_FIELD_READ', '113#3', 1),
('TRACKER_FIELD_READ', '113#4', 1),
('TRACKER_FIELD_READ', '113#5', 1),
('TRACKER_FIELD_READ', '113#6', 1),
('TRACKER_FIELD_READ', '113#7', 1),
('TRACKER_FIELD_READ', '113#8', 1),
('TRACKER_FIELD_READ', '113#9', 1),
('TRACKER_FIELD_READ', '113#10', 1),
('TRACKER_FIELD_READ', '113#11', 1),
('TRACKER_FIELD_READ', '113#12', 1),
('TRACKER_FIELD_READ', '113#13', 1),
('TRACKER_FIELD_READ', '113#14', 1),
('TRACKER_FIELD_READ', '113#15', 1),
('TRACKER_FIELD_READ', '113#16', 1),
('TRACKER_FIELD_READ', '113#17', 1),
('TRACKER_FIELD_READ', '113#18', 1),
('TRACKER_FIELD_READ', '113#19', 1),
('TRACKER_FIELD_READ', '113#20', 1),
('TRACKER_FIELD_READ', '113#22', 1),
('TRACKER_FIELD_READ', '113#23', 1),
('TRACKER_FIELD_READ', '113#24', 1),
('TRACKER_FIELD_READ', '113#26', 1),
('TRACKER_FIELD_READ', '113#27', 1),
('TRACKER_FIELD_READ', '113#28', 1),
('TRACKER_FIELD_READ', '113#29', 1),
('TRACKER_FIELD_READ', '113#30', 1),
('TRACKER_FIELD_UPDATE', '113#2', 3),
('TRACKER_FIELD_UPDATE', '113#3', 3),
('TRACKER_FIELD_UPDATE', '113#4', 3),
('TRACKER_FIELD_UPDATE', '113#5', 3),
('TRACKER_FIELD_UPDATE', '113#8', 3),
('TRACKER_FIELD_UPDATE', '113#9', 3),
('TRACKER_FIELD_UPDATE', '113#10', 3),
('TRACKER_FIELD_UPDATE', '113#11', 3),
('TRACKER_FIELD_UPDATE', '113#12', 3),
('TRACKER_FIELD_UPDATE', '113#13', 3),
('TRACKER_FIELD_UPDATE', '113#14', 3),
('TRACKER_FIELD_UPDATE', '113#15', 3),
('TRACKER_FIELD_UPDATE', '113#16', 3),
('TRACKER_FIELD_UPDATE', '113#17', 3),
('TRACKER_FIELD_UPDATE', '113#18', 3),
('TRACKER_FIELD_UPDATE', '113#19', 3),
('TRACKER_FIELD_UPDATE', '113#20', 3),
('TRACKER_FIELD_UPDATE', '113#22', 3),
('TRACKER_FIELD_UPDATE', '113#23', 3),
('TRACKER_FIELD_UPDATE', '113#24', 3),
('TRACKER_FIELD_UPDATE', '113#26', 3),
('TRACKER_FIELD_UPDATE', '113#27', 3),
('TRACKER_FIELD_UPDATE', '113#28', 3),
('TRACKER_FIELD_UPDATE', '113#29', 3),
('TRACKER_FIELD_UPDATE', '113#30', 3),
('TRACKER_ACCESS_FULL', '114', 1),
('PLUGIN_DOCMAN_READ', '114', 1),
('PLUGIN_DOCMAN_MANAGE', '114', 3),
('RELEASE_READ', '114', 2),
('TRACKER_FIELD_SUBMIT', '114#2', 3),
('TRACKER_FIELD_SUBMIT', '114#4', 3),
('TRACKER_FIELD_SUBMIT', '114#5', 3),
('TRACKER_FIELD_SUBMIT', '114#6', 3),
('TRACKER_FIELD_SUBMIT', '114#7', 3),
('TRACKER_FIELD_SUBMIT', '114#8', 3),
('TRACKER_FIELD_SUBMIT', '114#9', 3),
('TRACKER_FIELD_SUBMIT', '114#12', 3),
('TRACKER_FIELD_SUBMIT', '114#14', 3),
('TRACKER_FIELD_READ', '114#1', 1),
('TRACKER_FIELD_READ', '114#2', 1),
('TRACKER_FIELD_READ', '114#4', 1),
('TRACKER_FIELD_READ', '114#5', 1),
('TRACKER_FIELD_READ', '114#6', 1),
('TRACKER_FIELD_READ', '114#7', 1),
('TRACKER_FIELD_READ', '114#8', 1),
('TRACKER_FIELD_READ', '114#9', 1),
('TRACKER_FIELD_READ', '114#10', 1),
('TRACKER_FIELD_READ', '114#11', 1),
('TRACKER_FIELD_READ', '114#12', 1),
('TRACKER_FIELD_READ', '114#13', 1),
('TRACKER_FIELD_READ', '114#14', 1),
('TRACKER_FIELD_READ', '114#15', 1),
('TRACKER_FIELD_UPDATE', '114#2', 3),
('TRACKER_FIELD_UPDATE', '114#4', 3),
('TRACKER_FIELD_UPDATE', '114#5', 3),
('TRACKER_FIELD_UPDATE', '114#6', 3),
('TRACKER_FIELD_UPDATE', '114#7', 3),
('TRACKER_FIELD_UPDATE', '114#8', 3),
('TRACKER_FIELD_UPDATE', '114#9', 3),
('TRACKER_FIELD_UPDATE', '114#11', 3),
('TRACKER_FIELD_UPDATE', '114#12', 3),
('TRACKER_FIELD_UPDATE', '114#14', 3),
('TRACKER_FIELD_UPDATE', '114#15', 3),
('TRACKER_ACCESS_FULL', '115', 1),
('PLUGIN_DOCMAN_READ', '115', 1),
('PLUGIN_DOCMAN_MANAGE', '115', 3),
('TRACKER_FIELD_SUBMIT', '115#2', 1),
('TRACKER_FIELD_SUBMIT', '115#3', 1),
('TRACKER_FIELD_SUBMIT', '115#5', 1),
('TRACKER_FIELD_SUBMIT', '115#11', 1),
('TRACKER_FIELD_READ', '115#1', 1),
('TRACKER_FIELD_READ', '115#2', 1),
('TRACKER_FIELD_READ', '115#3', 1),
('TRACKER_FIELD_READ', '115#4', 1),
('TRACKER_FIELD_READ', '115#5', 1),
('TRACKER_FIELD_READ', '115#6', 1),
('TRACKER_FIELD_READ', '115#7', 1),
('TRACKER_FIELD_READ', '115#9', 1),
('TRACKER_FIELD_READ', '115#10', 1),
('TRACKER_FIELD_READ', '115#11', 1),
('TRACKER_FIELD_READ', '115#12', 1),
('TRACKER_FIELD_UPDATE', '115#2', 3),
('TRACKER_FIELD_UPDATE', '115#3', 3),
('TRACKER_FIELD_UPDATE', '115#5', 3),
('TRACKER_FIELD_UPDATE', '115#6', 3),
('TRACKER_FIELD_UPDATE', '115#7', 3),
('TRACKER_FIELD_UPDATE', '115#10', 3),
('TRACKER_FIELD_UPDATE', '115#11', 3),
('TRACKER_FIELD_UPDATE', '115#12', 3),
('TRACKER_ACCESS_FULL', '116', 1),
('PLUGIN_DOCMAN_READ', '116', 1),
('PLUGIN_DOCMAN_MANAGE', '116', 3),
('TRACKER_FIELD_SUBMIT', '116#3', 2),
('TRACKER_FIELD_SUBMIT', '116#5', 2),
('TRACKER_FIELD_SUBMIT', '116#7', 2),
('TRACKER_FIELD_SUBMIT', '116#8', 2),
('TRACKER_FIELD_SUBMIT', '116#10', 2),
('TRACKER_FIELD_READ', '116#1', 1),
('TRACKER_FIELD_READ', '116#2', 1),
('TRACKER_FIELD_READ', '116#3', 1),
('TRACKER_FIELD_READ', '116#4', 1),
('TRACKER_FIELD_READ', '116#5', 1),
('TRACKER_FIELD_READ', '116#6', 1),
('TRACKER_FIELD_READ', '116#7', 1),
('TRACKER_FIELD_READ', '116#8', 1),
('TRACKER_FIELD_READ', '116#9', 1),
('TRACKER_FIELD_READ', '116#10', 1),
('TRACKER_FIELD_READ', '116#11', 1),
('TRACKER_FIELD_READ', '116#12', 1),
('TRACKER_FIELD_UPDATE', '116#3', 3),
('TRACKER_FIELD_UPDATE', '116#5', 3),
('TRACKER_FIELD_UPDATE', '116#6', 3),
('TRACKER_FIELD_UPDATE', '116#7', 3),
('TRACKER_FIELD_UPDATE', '116#8', 3),
('TRACKER_FIELD_UPDATE', '116#9', 3),
('TRACKER_FIELD_UPDATE', '116#10', 3),
('TRACKER_FIELD_UPDATE', '116#11', 3),
('TRACKER_FIELD_UPDATE', '116#12', 3),
('PLUGIN_DOCMAN_ADMIN', '111', 4),
('PLUGIN_DOCMAN_READ', '24', 2),
('PLUGIN_DOCMAN_WRITE', '24', 3),
('PLUGIN_DOCMAN_MANAGE', '24', 4),
('TRACKER_ACCESS_FULL', '117', 1),
('PLUGIN_DOCMAN_READ', '117', 1),
('PLUGIN_DOCMAN_MANAGE', '117', 3),
('PACKAGE_READ', '117', 2),
('RELEASE_READ', '117', 2),
('TRACKER_FIELD_SUBMIT', '117#3', 2),
('TRACKER_FIELD_SUBMIT', '117#4', 2),
('TRACKER_FIELD_SUBMIT', '117#5', 2),
('TRACKER_FIELD_SUBMIT', '117#8', 2),
('TRACKER_FIELD_SUBMIT', '117#9', 2),
('TRACKER_FIELD_SUBMIT', '117#20', 2),
('TRACKER_FIELD_READ', '117#1', 1),
('TRACKER_FIELD_READ', '117#2', 1),
('TRACKER_FIELD_READ', '117#3', 1),
('TRACKER_FIELD_READ', '117#4', 1),
('TRACKER_FIELD_READ', '117#5', 1),
('TRACKER_FIELD_READ', '117#6', 1),
('TRACKER_FIELD_READ', '117#7', 1),
('TRACKER_FIELD_READ', '117#8', 1),
('TRACKER_FIELD_READ', '117#9', 1),
('TRACKER_FIELD_READ', '117#10', 1),
('TRACKER_FIELD_READ', '117#11', 1),
('TRACKER_FIELD_READ', '117#12', 1),
('TRACKER_FIELD_READ', '117#13', 1),
('TRACKER_FIELD_READ', '117#14', 1),
('TRACKER_FIELD_READ', '117#15', 1),
('TRACKER_FIELD_READ', '117#16', 1),
('TRACKER_FIELD_READ', '117#17', 1),
('TRACKER_FIELD_READ', '117#18', 1),
('TRACKER_FIELD_READ', '117#19', 1),
('TRACKER_FIELD_READ', '117#20', 1),
('TRACKER_FIELD_READ', '117#22', 1),
('TRACKER_FIELD_READ', '117#23', 1),
('TRACKER_FIELD_READ', '117#24', 1),
('TRACKER_FIELD_READ', '117#26', 1),
('TRACKER_FIELD_READ', '117#27', 1),
('TRACKER_FIELD_READ', '117#28', 1),
('TRACKER_FIELD_READ', '117#29', 1),
('TRACKER_FIELD_READ', '117#30', 1),
('TRACKER_FIELD_UPDATE', '117#2', 3),
('TRACKER_FIELD_UPDATE', '117#3', 3),
('TRACKER_FIELD_UPDATE', '117#4', 3),
('TRACKER_FIELD_UPDATE', '117#5', 3),
('TRACKER_FIELD_UPDATE', '117#8', 3),
('TRACKER_FIELD_UPDATE', '117#9', 3),
('TRACKER_FIELD_UPDATE', '117#10', 3),
('TRACKER_FIELD_UPDATE', '117#11', 3),
('TRACKER_FIELD_UPDATE', '117#12', 3),
('TRACKER_FIELD_UPDATE', '117#13', 3),
('TRACKER_FIELD_UPDATE', '117#14', 3),
('TRACKER_FIELD_UPDATE', '117#15', 3),
('TRACKER_FIELD_UPDATE', '117#16', 3),
('TRACKER_FIELD_UPDATE', '117#17', 3),
('TRACKER_FIELD_UPDATE', '117#18', 3),
('TRACKER_FIELD_UPDATE', '117#19', 3),
('TRACKER_FIELD_UPDATE', '117#20', 3),
('TRACKER_FIELD_UPDATE', '117#22', 3),
('TRACKER_FIELD_UPDATE', '117#23', 3),
('TRACKER_FIELD_UPDATE', '117#24', 3),
('TRACKER_FIELD_UPDATE', '117#26', 3),
('TRACKER_FIELD_UPDATE', '117#27', 3),
('TRACKER_FIELD_UPDATE', '117#28', 3),
('TRACKER_FIELD_UPDATE', '117#29', 3),
('TRACKER_FIELD_UPDATE', '117#30', 3),
('TRACKER_ACCESS_FULL', '118', 1),
('PLUGIN_DOCMAN_READ', '118', 1),
('PLUGIN_DOCMAN_MANAGE', '118', 3),
('RELEASE_READ', '118', 2),
('TRACKER_FIELD_SUBMIT', '118#2', 3),
('TRACKER_FIELD_SUBMIT', '118#4', 3),
('TRACKER_FIELD_SUBMIT', '118#5', 3),
('TRACKER_FIELD_SUBMIT', '118#6', 3),
('TRACKER_FIELD_SUBMIT', '118#7', 3),
('TRACKER_FIELD_SUBMIT', '118#8', 3),
('TRACKER_FIELD_SUBMIT', '118#9', 3),
('TRACKER_FIELD_SUBMIT', '118#12', 3),
('TRACKER_FIELD_SUBMIT', '118#14', 3),
('TRACKER_FIELD_READ', '118#1', 1),
('TRACKER_FIELD_READ', '118#2', 1),
('TRACKER_FIELD_READ', '118#4', 1),
('TRACKER_FIELD_READ', '118#5', 1),
('TRACKER_FIELD_READ', '118#6', 1),
('TRACKER_FIELD_READ', '118#7', 1),
('TRACKER_FIELD_READ', '118#8', 1),
('TRACKER_FIELD_READ', '118#9', 1),
('TRACKER_FIELD_READ', '118#10', 1),
('TRACKER_FIELD_READ', '118#11', 1),
('TRACKER_FIELD_READ', '118#12', 1),
('TRACKER_FIELD_READ', '118#13', 1),
('TRACKER_FIELD_READ', '118#14', 1),
('TRACKER_FIELD_READ', '118#15', 1),
('TRACKER_FIELD_UPDATE', '118#2', 3),
('TRACKER_FIELD_UPDATE', '118#4', 3),
('TRACKER_FIELD_UPDATE', '118#5', 3),
('TRACKER_FIELD_UPDATE', '118#6', 3),
('TRACKER_FIELD_UPDATE', '118#7', 3),
('TRACKER_FIELD_UPDATE', '118#8', 3),
('TRACKER_FIELD_UPDATE', '118#9', 3),
('TRACKER_FIELD_UPDATE', '118#11', 3),
('TRACKER_FIELD_UPDATE', '118#12', 3),
('TRACKER_FIELD_UPDATE', '118#14', 3),
('TRACKER_FIELD_UPDATE', '118#15', 3),
('TRACKER_ACCESS_FULL', '119', 1),
('PLUGIN_DOCMAN_READ', '119', 1),
('PLUGIN_DOCMAN_MANAGE', '119', 3),
('TRACKER_FIELD_SUBMIT', '119#2', 1),
('TRACKER_FIELD_SUBMIT', '119#3', 1),
('TRACKER_FIELD_SUBMIT', '119#5', 1),
('TRACKER_FIELD_SUBMIT', '119#11', 1),
('TRACKER_FIELD_READ', '119#1', 1),
('TRACKER_FIELD_READ', '119#2', 1),
('TRACKER_FIELD_READ', '119#3', 1),
('TRACKER_FIELD_READ', '119#4', 1),
('TRACKER_FIELD_READ', '119#5', 1),
('TRACKER_FIELD_READ', '119#6', 1),
('TRACKER_FIELD_READ', '119#7', 1),
('TRACKER_FIELD_READ', '119#9', 1),
('TRACKER_FIELD_READ', '119#10', 1),
('TRACKER_FIELD_READ', '119#11', 1),
('TRACKER_FIELD_READ', '119#12', 1),
('TRACKER_FIELD_UPDATE', '119#2', 3),
('TRACKER_FIELD_UPDATE', '119#3', 3),
('TRACKER_FIELD_UPDATE', '119#5', 3),
('TRACKER_FIELD_UPDATE', '119#6', 3),
('TRACKER_FIELD_UPDATE', '119#7', 3),
('TRACKER_FIELD_UPDATE', '119#10', 3),
('TRACKER_FIELD_UPDATE', '119#11', 3),
('TRACKER_FIELD_UPDATE', '119#12', 3),
('TRACKER_ACCESS_FULL', '120', 1),
('PLUGIN_DOCMAN_READ', '120', 1),
('PLUGIN_DOCMAN_MANAGE', '120', 3),
('TRACKER_FIELD_SUBMIT', '120#3', 2),
('TRACKER_FIELD_SUBMIT', '120#5', 2),
('TRACKER_FIELD_SUBMIT', '120#7', 2),
('TRACKER_FIELD_SUBMIT', '120#8', 2),
('TRACKER_FIELD_SUBMIT', '120#10', 2),
('TRACKER_FIELD_READ', '120#1', 1),
('TRACKER_FIELD_READ', '120#2', 1),
('TRACKER_FIELD_READ', '120#3', 1),
('TRACKER_FIELD_READ', '120#4', 1),
('TRACKER_FIELD_READ', '120#5', 1),
('TRACKER_FIELD_READ', '120#6', 1),
('TRACKER_FIELD_READ', '120#7', 1),
('TRACKER_FIELD_READ', '120#8', 1),
('TRACKER_FIELD_READ', '120#9', 1),
('TRACKER_FIELD_READ', '120#10', 1),
('TRACKER_FIELD_READ', '120#11', 1),
('TRACKER_FIELD_READ', '120#12', 1),
('TRACKER_FIELD_UPDATE', '120#3', 3),
('TRACKER_FIELD_UPDATE', '120#5', 3),
('TRACKER_FIELD_UPDATE', '120#6', 3),
('TRACKER_FIELD_UPDATE', '120#7', 3),
('TRACKER_FIELD_UPDATE', '120#8', 3),
('TRACKER_FIELD_UPDATE', '120#9', 3),
('TRACKER_FIELD_UPDATE', '120#10', 3),
('TRACKER_FIELD_UPDATE', '120#11', 3),
('TRACKER_FIELD_UPDATE', '120#12', 3),
('PLUGIN_DOCMAN_ADMIN', '112', 4),
('PLUGIN_DOCMAN_READ', '25', 2),
('PLUGIN_DOCMAN_WRITE', '25', 3),
('PLUGIN_DOCMAN_MANAGE', '25', 4),
('TRACKER_ACCESS_FULL', '121', 1),
('PLUGIN_DOCMAN_READ', '121', 1),
('PLUGIN_DOCMAN_MANAGE', '121', 3),
('PACKAGE_READ', '121', 2),
('RELEASE_READ', '121', 2),
('TRACKER_FIELD_SUBMIT', '121#3', 2),
('TRACKER_FIELD_SUBMIT', '121#4', 2),
('TRACKER_FIELD_SUBMIT', '121#5', 2),
('TRACKER_FIELD_SUBMIT', '121#8', 2),
('TRACKER_FIELD_SUBMIT', '121#9', 2),
('TRACKER_FIELD_SUBMIT', '121#20', 2),
('TRACKER_FIELD_READ', '121#1', 1),
('TRACKER_FIELD_READ', '121#2', 1),
('TRACKER_FIELD_READ', '121#3', 1),
('TRACKER_FIELD_READ', '121#4', 1),
('TRACKER_FIELD_READ', '121#5', 1),
('TRACKER_FIELD_READ', '121#6', 1),
('TRACKER_FIELD_READ', '121#7', 1),
('TRACKER_FIELD_READ', '121#8', 1),
('TRACKER_FIELD_READ', '121#9', 1),
('TRACKER_FIELD_READ', '121#10', 1),
('TRACKER_FIELD_READ', '121#11', 1),
('TRACKER_FIELD_READ', '121#12', 1),
('TRACKER_FIELD_READ', '121#13', 1),
('TRACKER_FIELD_READ', '121#14', 1),
('TRACKER_FIELD_READ', '121#15', 1),
('TRACKER_FIELD_READ', '121#16', 1),
('TRACKER_FIELD_READ', '121#17', 1),
('TRACKER_FIELD_READ', '121#18', 1),
('TRACKER_FIELD_READ', '121#19', 1),
('TRACKER_FIELD_READ', '121#20', 1),
('TRACKER_FIELD_READ', '121#22', 1),
('TRACKER_FIELD_READ', '121#23', 1),
('TRACKER_FIELD_READ', '121#24', 1),
('TRACKER_FIELD_READ', '121#26', 1),
('TRACKER_FIELD_READ', '121#27', 1),
('TRACKER_FIELD_READ', '121#28', 1),
('TRACKER_FIELD_READ', '121#29', 1),
('TRACKER_FIELD_READ', '121#30', 1),
('TRACKER_FIELD_UPDATE', '121#2', 3),
('TRACKER_FIELD_UPDATE', '121#3', 3),
('TRACKER_FIELD_UPDATE', '121#4', 3),
('TRACKER_FIELD_UPDATE', '121#5', 3),
('TRACKER_FIELD_UPDATE', '121#8', 3),
('TRACKER_FIELD_UPDATE', '121#9', 3),
('TRACKER_FIELD_UPDATE', '121#10', 3),
('TRACKER_FIELD_UPDATE', '121#11', 3),
('TRACKER_FIELD_UPDATE', '121#12', 3),
('TRACKER_FIELD_UPDATE', '121#13', 3),
('TRACKER_FIELD_UPDATE', '121#14', 3),
('TRACKER_FIELD_UPDATE', '121#15', 3),
('TRACKER_FIELD_UPDATE', '121#16', 3),
('TRACKER_FIELD_UPDATE', '121#17', 3),
('TRACKER_FIELD_UPDATE', '121#18', 3),
('TRACKER_FIELD_UPDATE', '121#19', 3),
('TRACKER_FIELD_UPDATE', '121#20', 3),
('TRACKER_FIELD_UPDATE', '121#22', 3),
('TRACKER_FIELD_UPDATE', '121#23', 3),
('TRACKER_FIELD_UPDATE', '121#24', 3),
('TRACKER_FIELD_UPDATE', '121#26', 3),
('TRACKER_FIELD_UPDATE', '121#27', 3),
('TRACKER_FIELD_UPDATE', '121#28', 3),
('TRACKER_FIELD_UPDATE', '121#29', 3),
('TRACKER_FIELD_UPDATE', '121#30', 3),
('TRACKER_ACCESS_FULL', '122', 1),
('PLUGIN_DOCMAN_READ', '122', 1),
('PLUGIN_DOCMAN_MANAGE', '122', 3),
('RELEASE_READ', '122', 2),
('TRACKER_FIELD_SUBMIT', '122#2', 3),
('TRACKER_FIELD_SUBMIT', '122#4', 3),
('TRACKER_FIELD_SUBMIT', '122#5', 3),
('TRACKER_FIELD_SUBMIT', '122#6', 3),
('TRACKER_FIELD_SUBMIT', '122#7', 3),
('TRACKER_FIELD_SUBMIT', '122#8', 3),
('TRACKER_FIELD_SUBMIT', '122#9', 3),
('TRACKER_FIELD_SUBMIT', '122#12', 3),
('TRACKER_FIELD_SUBMIT', '122#14', 3),
('TRACKER_FIELD_READ', '122#1', 1),
('TRACKER_FIELD_READ', '122#2', 1),
('TRACKER_FIELD_READ', '122#4', 1),
('TRACKER_FIELD_READ', '122#5', 1),
('TRACKER_FIELD_READ', '122#6', 1),
('TRACKER_FIELD_READ', '122#7', 1),
('TRACKER_FIELD_READ', '122#8', 1),
('TRACKER_FIELD_READ', '122#9', 1),
('TRACKER_FIELD_READ', '122#10', 1),
('TRACKER_FIELD_READ', '122#11', 1),
('TRACKER_FIELD_READ', '122#12', 1),
('TRACKER_FIELD_READ', '122#13', 1),
('TRACKER_FIELD_READ', '122#14', 1),
('TRACKER_FIELD_READ', '122#15', 1),
('TRACKER_FIELD_UPDATE', '122#2', 3),
('TRACKER_FIELD_UPDATE', '122#4', 3),
('TRACKER_FIELD_UPDATE', '122#5', 3),
('TRACKER_FIELD_UPDATE', '122#6', 3),
('TRACKER_FIELD_UPDATE', '122#7', 3),
('TRACKER_FIELD_UPDATE', '122#8', 3),
('TRACKER_FIELD_UPDATE', '122#9', 3),
('TRACKER_FIELD_UPDATE', '122#11', 3),
('TRACKER_FIELD_UPDATE', '122#12', 3),
('TRACKER_FIELD_UPDATE', '122#14', 3),
('TRACKER_FIELD_UPDATE', '122#15', 3),
('TRACKER_ACCESS_FULL', '123', 1),
('PLUGIN_DOCMAN_READ', '123', 1),
('PLUGIN_DOCMAN_MANAGE', '123', 3),
('TRACKER_FIELD_SUBMIT', '123#2', 1),
('TRACKER_FIELD_SUBMIT', '123#3', 1),
('TRACKER_FIELD_SUBMIT', '123#5', 1),
('TRACKER_FIELD_SUBMIT', '123#11', 1),
('TRACKER_FIELD_READ', '123#1', 1),
('TRACKER_FIELD_READ', '123#2', 1),
('TRACKER_FIELD_READ', '123#3', 1),
('TRACKER_FIELD_READ', '123#4', 1),
('TRACKER_FIELD_READ', '123#5', 1),
('TRACKER_FIELD_READ', '123#6', 1),
('TRACKER_FIELD_READ', '123#7', 1),
('TRACKER_FIELD_READ', '123#9', 1),
('TRACKER_FIELD_READ', '123#10', 1),
('TRACKER_FIELD_READ', '123#11', 1),
('TRACKER_FIELD_READ', '123#12', 1),
('TRACKER_FIELD_UPDATE', '123#2', 3),
('TRACKER_FIELD_UPDATE', '123#3', 3),
('TRACKER_FIELD_UPDATE', '123#5', 3),
('TRACKER_FIELD_UPDATE', '123#6', 3),
('TRACKER_FIELD_UPDATE', '123#7', 3),
('TRACKER_FIELD_UPDATE', '123#10', 3),
('TRACKER_FIELD_UPDATE', '123#11', 3),
('TRACKER_FIELD_UPDATE', '123#12', 3),
('TRACKER_ACCESS_FULL', '124', 1),
('PLUGIN_DOCMAN_READ', '124', 1),
('PLUGIN_DOCMAN_MANAGE', '124', 3),
('TRACKER_FIELD_SUBMIT', '124#3', 2),
('TRACKER_FIELD_SUBMIT', '124#5', 2),
('TRACKER_FIELD_SUBMIT', '124#7', 2),
('TRACKER_FIELD_SUBMIT', '124#8', 2),
('TRACKER_FIELD_SUBMIT', '124#10', 2),
('TRACKER_FIELD_READ', '124#1', 1),
('TRACKER_FIELD_READ', '124#2', 1),
('TRACKER_FIELD_READ', '124#3', 1),
('TRACKER_FIELD_READ', '124#4', 1),
('TRACKER_FIELD_READ', '124#5', 1),
('TRACKER_FIELD_READ', '124#6', 1),
('TRACKER_FIELD_READ', '124#7', 1),
('TRACKER_FIELD_READ', '124#8', 1),
('TRACKER_FIELD_READ', '124#9', 1),
('TRACKER_FIELD_READ', '124#10', 1),
('TRACKER_FIELD_READ', '124#11', 1),
('TRACKER_FIELD_READ', '124#12', 1),
('TRACKER_FIELD_UPDATE', '124#3', 3),
('TRACKER_FIELD_UPDATE', '124#5', 3),
('TRACKER_FIELD_UPDATE', '124#6', 3),
('TRACKER_FIELD_UPDATE', '124#7', 3),
('TRACKER_FIELD_UPDATE', '124#8', 3),
('TRACKER_FIELD_UPDATE', '124#9', 3),
('TRACKER_FIELD_UPDATE', '124#10', 3),
('TRACKER_FIELD_UPDATE', '124#11', 3),
('TRACKER_FIELD_UPDATE', '124#12', 3),
('PLUGIN_DOCMAN_ADMIN', '113', 4),
('PLUGIN_DOCMAN_READ', '26', 2),
('PLUGIN_DOCMAN_WRITE', '26', 3),
('PLUGIN_DOCMAN_MANAGE', '26', 4),
('TRACKER_ACCESS_FULL', '125', 1),
('PLUGIN_DOCMAN_READ', '125', 1),
('PLUGIN_DOCMAN_MANAGE', '125', 3),
('PACKAGE_READ', '125', 2),
('RELEASE_READ', '125', 2),
('TRACKER_FIELD_SUBMIT', '125#3', 2),
('TRACKER_FIELD_SUBMIT', '125#4', 2),
('TRACKER_FIELD_SUBMIT', '125#5', 2),
('TRACKER_FIELD_SUBMIT', '125#8', 2),
('TRACKER_FIELD_SUBMIT', '125#9', 2),
('TRACKER_FIELD_SUBMIT', '125#20', 2),
('TRACKER_FIELD_READ', '125#1', 1),
('TRACKER_FIELD_READ', '125#2', 1),
('TRACKER_FIELD_READ', '125#3', 1),
('TRACKER_FIELD_READ', '125#4', 1),
('TRACKER_FIELD_READ', '125#5', 1),
('TRACKER_FIELD_READ', '125#6', 1),
('TRACKER_FIELD_READ', '125#7', 1),
('TRACKER_FIELD_READ', '125#8', 1),
('TRACKER_FIELD_READ', '125#9', 1),
('TRACKER_FIELD_READ', '125#10', 1),
('TRACKER_FIELD_READ', '125#11', 1),
('TRACKER_FIELD_READ', '125#12', 1),
('TRACKER_FIELD_READ', '125#13', 1),
('TRACKER_FIELD_READ', '125#14', 1),
('TRACKER_FIELD_READ', '125#15', 1),
('TRACKER_FIELD_READ', '125#16', 1),
('TRACKER_FIELD_READ', '125#17', 1),
('TRACKER_FIELD_READ', '125#18', 1),
('TRACKER_FIELD_READ', '125#19', 1),
('TRACKER_FIELD_READ', '125#20', 1),
('TRACKER_FIELD_READ', '125#22', 1),
('TRACKER_FIELD_READ', '125#23', 1),
('TRACKER_FIELD_READ', '125#24', 1),
('TRACKER_FIELD_READ', '125#26', 1),
('TRACKER_FIELD_READ', '125#27', 1),
('TRACKER_FIELD_READ', '125#28', 1),
('TRACKER_FIELD_READ', '125#29', 1),
('TRACKER_FIELD_READ', '125#30', 1),
('TRACKER_FIELD_UPDATE', '125#2', 3),
('TRACKER_FIELD_UPDATE', '125#3', 3),
('TRACKER_FIELD_UPDATE', '125#4', 3),
('TRACKER_FIELD_UPDATE', '125#5', 3),
('TRACKER_FIELD_UPDATE', '125#8', 3),
('TRACKER_FIELD_UPDATE', '125#9', 3),
('TRACKER_FIELD_UPDATE', '125#10', 3),
('TRACKER_FIELD_UPDATE', '125#11', 3),
('TRACKER_FIELD_UPDATE', '125#12', 3),
('TRACKER_FIELD_UPDATE', '125#13', 3),
('TRACKER_FIELD_UPDATE', '125#14', 3),
('TRACKER_FIELD_UPDATE', '125#15', 3),
('TRACKER_FIELD_UPDATE', '125#16', 3),
('TRACKER_FIELD_UPDATE', '125#17', 3),
('TRACKER_FIELD_UPDATE', '125#18', 3),
('TRACKER_FIELD_UPDATE', '125#19', 3),
('TRACKER_FIELD_UPDATE', '125#20', 3),
('TRACKER_FIELD_UPDATE', '125#22', 3),
('TRACKER_FIELD_UPDATE', '125#23', 3),
('TRACKER_FIELD_UPDATE', '125#24', 3),
('TRACKER_FIELD_UPDATE', '125#26', 3),
('TRACKER_FIELD_UPDATE', '125#27', 3),
('TRACKER_FIELD_UPDATE', '125#28', 3),
('TRACKER_FIELD_UPDATE', '125#29', 3),
('TRACKER_FIELD_UPDATE', '125#30', 3),
('TRACKER_ACCESS_FULL', '126', 1),
('PLUGIN_DOCMAN_READ', '126', 1),
('PLUGIN_DOCMAN_MANAGE', '126', 3),
('RELEASE_READ', '126', 2),
('TRACKER_FIELD_SUBMIT', '126#2', 3),
('TRACKER_FIELD_SUBMIT', '126#4', 3),
('TRACKER_FIELD_SUBMIT', '126#5', 3),
('TRACKER_FIELD_SUBMIT', '126#6', 3),
('TRACKER_FIELD_SUBMIT', '126#7', 3),
('TRACKER_FIELD_SUBMIT', '126#8', 3),
('TRACKER_FIELD_SUBMIT', '126#9', 3),
('TRACKER_FIELD_SUBMIT', '126#12', 3),
('TRACKER_FIELD_SUBMIT', '126#14', 3),
('TRACKER_FIELD_READ', '126#1', 1),
('TRACKER_FIELD_READ', '126#2', 1),
('TRACKER_FIELD_READ', '126#4', 1),
('TRACKER_FIELD_READ', '126#5', 1),
('TRACKER_FIELD_READ', '126#6', 1),
('TRACKER_FIELD_READ', '126#7', 1),
('TRACKER_FIELD_READ', '126#8', 1),
('TRACKER_FIELD_READ', '126#9', 1),
('TRACKER_FIELD_READ', '126#10', 1),
('TRACKER_FIELD_READ', '126#11', 1),
('TRACKER_FIELD_READ', '126#12', 1),
('TRACKER_FIELD_READ', '126#13', 1),
('TRACKER_FIELD_READ', '126#14', 1),
('TRACKER_FIELD_READ', '126#15', 1),
('TRACKER_FIELD_UPDATE', '126#2', 3),
('TRACKER_FIELD_UPDATE', '126#4', 3),
('TRACKER_FIELD_UPDATE', '126#5', 3),
('TRACKER_FIELD_UPDATE', '126#6', 3),
('TRACKER_FIELD_UPDATE', '126#7', 3),
('TRACKER_FIELD_UPDATE', '126#8', 3),
('TRACKER_FIELD_UPDATE', '126#9', 3),
('TRACKER_FIELD_UPDATE', '126#11', 3),
('TRACKER_FIELD_UPDATE', '126#12', 3),
('TRACKER_FIELD_UPDATE', '126#14', 3),
('TRACKER_FIELD_UPDATE', '126#15', 3),
('TRACKER_ACCESS_FULL', '127', 1),
('PLUGIN_DOCMAN_READ', '127', 1),
('PLUGIN_DOCMAN_MANAGE', '127', 3),
('TRACKER_FIELD_SUBMIT', '127#2', 1),
('TRACKER_FIELD_SUBMIT', '127#3', 1),
('TRACKER_FIELD_SUBMIT', '127#5', 1),
('TRACKER_FIELD_SUBMIT', '127#11', 1),
('TRACKER_FIELD_READ', '127#1', 1),
('TRACKER_FIELD_READ', '127#2', 1),
('TRACKER_FIELD_READ', '127#3', 1),
('TRACKER_FIELD_READ', '127#4', 1),
('TRACKER_FIELD_READ', '127#5', 1),
('TRACKER_FIELD_READ', '127#6', 1),
('TRACKER_FIELD_READ', '127#7', 1),
('TRACKER_FIELD_READ', '127#9', 1),
('TRACKER_FIELD_READ', '127#10', 1),
('TRACKER_FIELD_READ', '127#11', 1),
('TRACKER_FIELD_READ', '127#12', 1),
('TRACKER_FIELD_UPDATE', '127#2', 3),
('TRACKER_FIELD_UPDATE', '127#3', 3),
('TRACKER_FIELD_UPDATE', '127#5', 3),
('TRACKER_FIELD_UPDATE', '127#6', 3),
('TRACKER_FIELD_UPDATE', '127#7', 3),
('TRACKER_FIELD_UPDATE', '127#10', 3),
('TRACKER_FIELD_UPDATE', '127#11', 3),
('TRACKER_FIELD_UPDATE', '127#12', 3),
('TRACKER_ACCESS_FULL', '128', 1),
('PLUGIN_DOCMAN_READ', '128', 1),
('PLUGIN_DOCMAN_MANAGE', '128', 3),
('TRACKER_FIELD_SUBMIT', '128#3', 2),
('TRACKER_FIELD_SUBMIT', '128#5', 2),
('TRACKER_FIELD_SUBMIT', '128#7', 2),
('TRACKER_FIELD_SUBMIT', '128#8', 2),
('TRACKER_FIELD_SUBMIT', '128#10', 2),
('TRACKER_FIELD_READ', '128#1', 1),
('TRACKER_FIELD_READ', '128#2', 1),
('TRACKER_FIELD_READ', '128#3', 1),
('TRACKER_FIELD_READ', '128#4', 1),
('TRACKER_FIELD_READ', '128#5', 1),
('TRACKER_FIELD_READ', '128#6', 1),
('TRACKER_FIELD_READ', '128#7', 1),
('TRACKER_FIELD_READ', '128#8', 1),
('TRACKER_FIELD_READ', '128#9', 1),
('TRACKER_FIELD_READ', '128#10', 1),
('TRACKER_FIELD_READ', '128#11', 1),
('TRACKER_FIELD_READ', '128#12', 1),
('TRACKER_FIELD_UPDATE', '128#3', 3),
('TRACKER_FIELD_UPDATE', '128#5', 3),
('TRACKER_FIELD_UPDATE', '128#6', 3),
('TRACKER_FIELD_UPDATE', '128#7', 3),
('TRACKER_FIELD_UPDATE', '128#8', 3),
('TRACKER_FIELD_UPDATE', '128#9', 3),
('TRACKER_FIELD_UPDATE', '128#10', 3),
('TRACKER_FIELD_UPDATE', '128#11', 3),
('TRACKER_FIELD_UPDATE', '128#12', 3),
('PLUGIN_DOCMAN_ADMIN', '114', 4),
('PLUGIN_DOCMAN_READ', '27', 2),
('PLUGIN_DOCMAN_WRITE', '27', 3),
('PLUGIN_DOCMAN_MANAGE', '27', 4),
('TRACKER_ACCESS_FULL', '129', 1),
('PLUGIN_DOCMAN_READ', '129', 1),
('PLUGIN_DOCMAN_MANAGE', '129', 3),
('PACKAGE_READ', '129', 2),
('RELEASE_READ', '129', 2),
('TRACKER_FIELD_SUBMIT', '129#3', 2),
('TRACKER_FIELD_SUBMIT', '129#4', 2),
('TRACKER_FIELD_SUBMIT', '129#5', 2),
('TRACKER_FIELD_SUBMIT', '129#8', 2),
('TRACKER_FIELD_SUBMIT', '129#9', 2),
('TRACKER_FIELD_SUBMIT', '129#20', 2),
('TRACKER_FIELD_READ', '129#1', 1),
('TRACKER_FIELD_READ', '129#2', 1),
('TRACKER_FIELD_READ', '129#3', 1),
('TRACKER_FIELD_READ', '129#4', 1),
('TRACKER_FIELD_READ', '129#5', 1),
('TRACKER_FIELD_READ', '129#6', 1),
('TRACKER_FIELD_READ', '129#7', 1),
('TRACKER_FIELD_READ', '129#8', 1),
('TRACKER_FIELD_READ', '129#9', 1),
('TRACKER_FIELD_READ', '129#10', 1),
('TRACKER_FIELD_READ', '129#11', 1),
('TRACKER_FIELD_READ', '129#12', 1),
('TRACKER_FIELD_READ', '129#13', 1),
('TRACKER_FIELD_READ', '129#14', 1),
('TRACKER_FIELD_READ', '129#15', 1),
('TRACKER_FIELD_READ', '129#16', 1),
('TRACKER_FIELD_READ', '129#17', 1),
('TRACKER_FIELD_READ', '129#18', 1),
('TRACKER_FIELD_READ', '129#19', 1),
('TRACKER_FIELD_READ', '129#20', 1),
('TRACKER_FIELD_READ', '129#22', 1),
('TRACKER_FIELD_READ', '129#23', 1),
('TRACKER_FIELD_READ', '129#24', 1),
('TRACKER_FIELD_READ', '129#26', 1),
('TRACKER_FIELD_READ', '129#27', 1),
('TRACKER_FIELD_READ', '129#28', 1),
('TRACKER_FIELD_READ', '129#29', 1),
('TRACKER_FIELD_READ', '129#30', 1),
('TRACKER_FIELD_UPDATE', '129#2', 3),
('TRACKER_FIELD_UPDATE', '129#3', 3),
('TRACKER_FIELD_UPDATE', '129#4', 3),
('TRACKER_FIELD_UPDATE', '129#5', 3),
('TRACKER_FIELD_UPDATE', '129#8', 3),
('TRACKER_FIELD_UPDATE', '129#9', 3),
('TRACKER_FIELD_UPDATE', '129#10', 3),
('TRACKER_FIELD_UPDATE', '129#11', 3),
('TRACKER_FIELD_UPDATE', '129#12', 3),
('TRACKER_FIELD_UPDATE', '129#13', 3),
('TRACKER_FIELD_UPDATE', '129#14', 3),
('TRACKER_FIELD_UPDATE', '129#15', 3),
('TRACKER_FIELD_UPDATE', '129#16', 3),
('TRACKER_FIELD_UPDATE', '129#17', 3),
('TRACKER_FIELD_UPDATE', '129#18', 3),
('TRACKER_FIELD_UPDATE', '129#19', 3),
('TRACKER_FIELD_UPDATE', '129#20', 3),
('TRACKER_FIELD_UPDATE', '129#22', 3),
('TRACKER_FIELD_UPDATE', '129#23', 3),
('TRACKER_FIELD_UPDATE', '129#24', 3),
('TRACKER_FIELD_UPDATE', '129#26', 3),
('TRACKER_FIELD_UPDATE', '129#27', 3),
('TRACKER_FIELD_UPDATE', '129#28', 3),
('TRACKER_FIELD_UPDATE', '129#29', 3),
('TRACKER_FIELD_UPDATE', '129#30', 3),
('TRACKER_ACCESS_FULL', '130', 1),
('PLUGIN_DOCMAN_READ', '130', 1),
('PLUGIN_DOCMAN_MANAGE', '130', 3),
('RELEASE_READ', '130', 2),
('TRACKER_FIELD_SUBMIT', '130#2', 3),
('TRACKER_FIELD_SUBMIT', '130#4', 3),
('TRACKER_FIELD_SUBMIT', '130#5', 3),
('TRACKER_FIELD_SUBMIT', '130#6', 3),
('TRACKER_FIELD_SUBMIT', '130#7', 3),
('TRACKER_FIELD_SUBMIT', '130#8', 3),
('TRACKER_FIELD_SUBMIT', '130#9', 3),
('TRACKER_FIELD_SUBMIT', '130#12', 3),
('TRACKER_FIELD_SUBMIT', '130#14', 3),
('TRACKER_FIELD_READ', '130#1', 1),
('TRACKER_FIELD_READ', '130#2', 1),
('TRACKER_FIELD_READ', '130#4', 1),
('TRACKER_FIELD_READ', '130#5', 1),
('TRACKER_FIELD_READ', '130#6', 1),
('TRACKER_FIELD_READ', '130#7', 1),
('TRACKER_FIELD_READ', '130#8', 1),
('TRACKER_FIELD_READ', '130#9', 1),
('TRACKER_FIELD_READ', '130#10', 1),
('TRACKER_FIELD_READ', '130#11', 1),
('TRACKER_FIELD_READ', '130#12', 1),
('TRACKER_FIELD_READ', '130#13', 1),
('TRACKER_FIELD_READ', '130#14', 1),
('TRACKER_FIELD_READ', '130#15', 1),
('TRACKER_FIELD_UPDATE', '130#2', 3),
('TRACKER_FIELD_UPDATE', '130#4', 3),
('TRACKER_FIELD_UPDATE', '130#5', 3),
('TRACKER_FIELD_UPDATE', '130#6', 3),
('TRACKER_FIELD_UPDATE', '130#7', 3),
('TRACKER_FIELD_UPDATE', '130#8', 3),
('TRACKER_FIELD_UPDATE', '130#9', 3);
INSERT INTO permissions (permission_type, object_id, ugroup_id) VALUES ('TRACKER_FIELD_UPDATE', '130#11', 3),
('TRACKER_FIELD_UPDATE', '130#12', 3),
('TRACKER_FIELD_UPDATE', '130#14', 3),
('TRACKER_FIELD_UPDATE', '130#15', 3),
('TRACKER_ACCESS_FULL', '131', 1),
('PLUGIN_DOCMAN_READ', '131', 1),
('PLUGIN_DOCMAN_MANAGE', '131', 3),
('TRACKER_FIELD_SUBMIT', '131#2', 1),
('TRACKER_FIELD_SUBMIT', '131#3', 1),
('TRACKER_FIELD_SUBMIT', '131#5', 1),
('TRACKER_FIELD_SUBMIT', '131#11', 1),
('TRACKER_FIELD_READ', '131#1', 1),
('TRACKER_FIELD_READ', '131#2', 1),
('TRACKER_FIELD_READ', '131#3', 1),
('TRACKER_FIELD_READ', '131#4', 1),
('TRACKER_FIELD_READ', '131#5', 1),
('TRACKER_FIELD_READ', '131#6', 1),
('TRACKER_FIELD_READ', '131#7', 1),
('TRACKER_FIELD_READ', '131#9', 1),
('TRACKER_FIELD_READ', '131#10', 1),
('TRACKER_FIELD_READ', '131#11', 1),
('TRACKER_FIELD_READ', '131#12', 1),
('TRACKER_FIELD_UPDATE', '131#2', 3),
('TRACKER_FIELD_UPDATE', '131#3', 3),
('TRACKER_FIELD_UPDATE', '131#5', 3),
('TRACKER_FIELD_UPDATE', '131#6', 3),
('TRACKER_FIELD_UPDATE', '131#7', 3),
('TRACKER_FIELD_UPDATE', '131#10', 3),
('TRACKER_FIELD_UPDATE', '131#11', 3),
('TRACKER_FIELD_UPDATE', '131#12', 3),
('TRACKER_ACCESS_FULL', '132', 1),
('PLUGIN_DOCMAN_READ', '132', 1),
('PLUGIN_DOCMAN_MANAGE', '132', 3),
('TRACKER_FIELD_SUBMIT', '132#3', 2),
('TRACKER_FIELD_SUBMIT', '132#5', 2),
('TRACKER_FIELD_SUBMIT', '132#7', 2),
('TRACKER_FIELD_SUBMIT', '132#8', 2),
('TRACKER_FIELD_SUBMIT', '132#10', 2),
('TRACKER_FIELD_READ', '132#1', 1),
('TRACKER_FIELD_READ', '132#2', 1),
('TRACKER_FIELD_READ', '132#3', 1),
('TRACKER_FIELD_READ', '132#4', 1),
('TRACKER_FIELD_READ', '132#5', 1),
('TRACKER_FIELD_READ', '132#6', 1),
('TRACKER_FIELD_READ', '132#7', 1),
('TRACKER_FIELD_READ', '132#8', 1),
('TRACKER_FIELD_READ', '132#9', 1),
('TRACKER_FIELD_READ', '132#10', 1),
('TRACKER_FIELD_READ', '132#11', 1),
('TRACKER_FIELD_READ', '132#12', 1),
('TRACKER_FIELD_UPDATE', '132#3', 3),
('TRACKER_FIELD_UPDATE', '132#5', 3),
('TRACKER_FIELD_UPDATE', '132#6', 3),
('TRACKER_FIELD_UPDATE', '132#7', 3),
('TRACKER_FIELD_UPDATE', '132#8', 3),
('TRACKER_FIELD_UPDATE', '132#9', 3),
('TRACKER_FIELD_UPDATE', '132#10', 3),
('TRACKER_FIELD_UPDATE', '132#11', 3),
('TRACKER_FIELD_UPDATE', '132#12', 3),
('PLUGIN_DOCMAN_ADMIN', '115', 4),
('PLUGIN_DOCMAN_READ', '28', 2),
('PLUGIN_DOCMAN_WRITE', '28', 3),
('PLUGIN_DOCMAN_MANAGE', '28', 4),
('TRACKER_ACCESS_FULL', '133', 1),
('PLUGIN_DOCMAN_READ', '133', 1),
('PLUGIN_DOCMAN_MANAGE', '133', 3),
('PACKAGE_READ', '133', 2),
('RELEASE_READ', '133', 2),
('TRACKER_FIELD_SUBMIT', '133#3', 2),
('TRACKER_FIELD_SUBMIT', '133#4', 2),
('TRACKER_FIELD_SUBMIT', '133#5', 2),
('TRACKER_FIELD_SUBMIT', '133#8', 2),
('TRACKER_FIELD_SUBMIT', '133#9', 2),
('TRACKER_FIELD_SUBMIT', '133#20', 2),
('TRACKER_FIELD_READ', '133#1', 1),
('TRACKER_FIELD_READ', '133#2', 1),
('TRACKER_FIELD_READ', '133#3', 1),
('TRACKER_FIELD_READ', '133#4', 1),
('TRACKER_FIELD_READ', '133#5', 1),
('TRACKER_FIELD_READ', '133#6', 1),
('TRACKER_FIELD_READ', '133#7', 1),
('TRACKER_FIELD_READ', '133#8', 1),
('TRACKER_FIELD_READ', '133#9', 1),
('TRACKER_FIELD_READ', '133#10', 1),
('TRACKER_FIELD_READ', '133#11', 1),
('TRACKER_FIELD_READ', '133#12', 1),
('TRACKER_FIELD_READ', '133#13', 1),
('TRACKER_FIELD_READ', '133#14', 1),
('TRACKER_FIELD_READ', '133#15', 1),
('TRACKER_FIELD_READ', '133#16', 1),
('TRACKER_FIELD_READ', '133#17', 1),
('TRACKER_FIELD_READ', '133#18', 1),
('TRACKER_FIELD_READ', '133#19', 1),
('TRACKER_FIELD_READ', '133#20', 1),
('TRACKER_FIELD_READ', '133#22', 1),
('TRACKER_FIELD_READ', '133#23', 1),
('TRACKER_FIELD_READ', '133#24', 1),
('TRACKER_FIELD_READ', '133#26', 1),
('TRACKER_FIELD_READ', '133#27', 1),
('TRACKER_FIELD_READ', '133#28', 1),
('TRACKER_FIELD_READ', '133#29', 1),
('TRACKER_FIELD_READ', '133#30', 1),
('TRACKER_FIELD_UPDATE', '133#2', 3),
('TRACKER_FIELD_UPDATE', '133#3', 3),
('TRACKER_FIELD_UPDATE', '133#4', 3),
('TRACKER_FIELD_UPDATE', '133#5', 3),
('TRACKER_FIELD_UPDATE', '133#8', 3),
('TRACKER_FIELD_UPDATE', '133#9', 3),
('TRACKER_FIELD_UPDATE', '133#10', 3),
('TRACKER_FIELD_UPDATE', '133#11', 3),
('TRACKER_FIELD_UPDATE', '133#12', 3),
('TRACKER_FIELD_UPDATE', '133#13', 3),
('TRACKER_FIELD_UPDATE', '133#14', 3),
('TRACKER_FIELD_UPDATE', '133#15', 3),
('TRACKER_FIELD_UPDATE', '133#16', 3),
('TRACKER_FIELD_UPDATE', '133#17', 3),
('TRACKER_FIELD_UPDATE', '133#18', 3),
('TRACKER_FIELD_UPDATE', '133#19', 3),
('TRACKER_FIELD_UPDATE', '133#20', 3),
('TRACKER_FIELD_UPDATE', '133#22', 3),
('TRACKER_FIELD_UPDATE', '133#23', 3),
('TRACKER_FIELD_UPDATE', '133#24', 3),
('TRACKER_FIELD_UPDATE', '133#26', 3),
('TRACKER_FIELD_UPDATE', '133#27', 3),
('TRACKER_FIELD_UPDATE', '133#28', 3),
('TRACKER_FIELD_UPDATE', '133#29', 3),
('TRACKER_FIELD_UPDATE', '133#30', 3),
('TRACKER_ACCESS_FULL', '134', 1),
('PLUGIN_DOCMAN_READ', '134', 1),
('PLUGIN_DOCMAN_MANAGE', '134', 3),
('RELEASE_READ', '134', 2),
('TRACKER_FIELD_SUBMIT', '134#2', 3),
('TRACKER_FIELD_SUBMIT', '134#4', 3),
('TRACKER_FIELD_SUBMIT', '134#5', 3),
('TRACKER_FIELD_SUBMIT', '134#6', 3),
('TRACKER_FIELD_SUBMIT', '134#7', 3),
('TRACKER_FIELD_SUBMIT', '134#8', 3),
('TRACKER_FIELD_SUBMIT', '134#9', 3),
('TRACKER_FIELD_SUBMIT', '134#12', 3),
('TRACKER_FIELD_SUBMIT', '134#14', 3),
('TRACKER_FIELD_READ', '134#1', 1),
('TRACKER_FIELD_READ', '134#2', 1),
('TRACKER_FIELD_READ', '134#4', 1),
('TRACKER_FIELD_READ', '134#5', 1),
('TRACKER_FIELD_READ', '134#6', 1),
('TRACKER_FIELD_READ', '134#7', 1),
('TRACKER_FIELD_READ', '134#8', 1),
('TRACKER_FIELD_READ', '134#9', 1),
('TRACKER_FIELD_READ', '134#10', 1),
('TRACKER_FIELD_READ', '134#11', 1),
('TRACKER_FIELD_READ', '134#12', 1),
('TRACKER_FIELD_READ', '134#13', 1),
('TRACKER_FIELD_READ', '134#14', 1),
('TRACKER_FIELD_READ', '134#15', 1),
('TRACKER_FIELD_UPDATE', '134#2', 3),
('TRACKER_FIELD_UPDATE', '134#4', 3),
('TRACKER_FIELD_UPDATE', '134#5', 3),
('TRACKER_FIELD_UPDATE', '134#6', 3),
('TRACKER_FIELD_UPDATE', '134#7', 3),
('TRACKER_FIELD_UPDATE', '134#8', 3),
('TRACKER_FIELD_UPDATE', '134#9', 3),
('TRACKER_FIELD_UPDATE', '134#11', 3),
('TRACKER_FIELD_UPDATE', '134#12', 3),
('TRACKER_FIELD_UPDATE', '134#14', 3),
('TRACKER_FIELD_UPDATE', '134#15', 3),
('TRACKER_ACCESS_FULL', '135', 1),
('PLUGIN_DOCMAN_READ', '135', 1),
('PLUGIN_DOCMAN_MANAGE', '135', 3),
('TRACKER_FIELD_SUBMIT', '135#2', 1),
('TRACKER_FIELD_SUBMIT', '135#3', 1),
('TRACKER_FIELD_SUBMIT', '135#5', 1),
('TRACKER_FIELD_SUBMIT', '135#11', 1),
('TRACKER_FIELD_READ', '135#1', 1),
('TRACKER_FIELD_READ', '135#2', 1),
('TRACKER_FIELD_READ', '135#3', 1),
('TRACKER_FIELD_READ', '135#4', 1),
('TRACKER_FIELD_READ', '135#5', 1),
('TRACKER_FIELD_READ', '135#6', 1),
('TRACKER_FIELD_READ', '135#7', 1),
('TRACKER_FIELD_READ', '135#9', 1),
('TRACKER_FIELD_READ', '135#10', 1),
('TRACKER_FIELD_READ', '135#11', 1),
('TRACKER_FIELD_READ', '135#12', 1),
('TRACKER_FIELD_UPDATE', '135#2', 3),
('TRACKER_FIELD_UPDATE', '135#3', 3),
('TRACKER_FIELD_UPDATE', '135#5', 3),
('TRACKER_FIELD_UPDATE', '135#6', 3),
('TRACKER_FIELD_UPDATE', '135#7', 3),
('TRACKER_FIELD_UPDATE', '135#10', 3),
('TRACKER_FIELD_UPDATE', '135#11', 3),
('TRACKER_FIELD_UPDATE', '135#12', 3),
('TRACKER_ACCESS_FULL', '136', 1),
('PLUGIN_DOCMAN_READ', '136', 1),
('PLUGIN_DOCMAN_MANAGE', '136', 3),
('TRACKER_FIELD_SUBMIT', '136#3', 2),
('TRACKER_FIELD_SUBMIT', '136#5', 2),
('TRACKER_FIELD_SUBMIT', '136#7', 2),
('TRACKER_FIELD_SUBMIT', '136#8', 2),
('TRACKER_FIELD_SUBMIT', '136#10', 2),
('TRACKER_FIELD_READ', '136#1', 1),
('TRACKER_FIELD_READ', '136#2', 1),
('TRACKER_FIELD_READ', '136#3', 1),
('TRACKER_FIELD_READ', '136#4', 1),
('TRACKER_FIELD_READ', '136#5', 1),
('TRACKER_FIELD_READ', '136#6', 1),
('TRACKER_FIELD_READ', '136#7', 1),
('TRACKER_FIELD_READ', '136#8', 1),
('TRACKER_FIELD_READ', '136#9', 1),
('TRACKER_FIELD_READ', '136#10', 1),
('TRACKER_FIELD_READ', '136#11', 1),
('TRACKER_FIELD_READ', '136#12', 1),
('TRACKER_FIELD_UPDATE', '136#3', 3),
('TRACKER_FIELD_UPDATE', '136#5', 3),
('TRACKER_FIELD_UPDATE', '136#6', 3),
('TRACKER_FIELD_UPDATE', '136#7', 3),
('TRACKER_FIELD_UPDATE', '136#8', 3),
('TRACKER_FIELD_UPDATE', '136#9', 3),
('TRACKER_FIELD_UPDATE', '136#10', 3),
('TRACKER_FIELD_UPDATE', '136#11', 3),
('TRACKER_FIELD_UPDATE', '136#12', 3),
('PLUGIN_DOCMAN_ADMIN', '116', 4),
('PLUGIN_DOCMAN_READ', '29', 2),
('PLUGIN_DOCMAN_WRITE', '29', 3),
('PLUGIN_DOCMAN_MANAGE', '29', 4),
('TRACKER_ACCESS_FULL', '137', 1),
('PLUGIN_DOCMAN_READ', '137', 1),
('PLUGIN_DOCMAN_MANAGE', '137', 3),
('PACKAGE_READ', '137', 2),
('RELEASE_READ', '137', 2),
('TRACKER_FIELD_SUBMIT', '137#3', 2),
('TRACKER_FIELD_SUBMIT', '137#4', 2),
('TRACKER_FIELD_SUBMIT', '137#5', 2),
('TRACKER_FIELD_SUBMIT', '137#8', 2),
('TRACKER_FIELD_SUBMIT', '137#9', 2),
('TRACKER_FIELD_SUBMIT', '137#20', 2),
('TRACKER_FIELD_READ', '137#1', 1),
('TRACKER_FIELD_READ', '137#2', 1),
('TRACKER_FIELD_READ', '137#3', 1),
('TRACKER_FIELD_READ', '137#4', 1),
('TRACKER_FIELD_READ', '137#5', 1),
('TRACKER_FIELD_READ', '137#6', 1),
('TRACKER_FIELD_READ', '137#7', 1),
('TRACKER_FIELD_READ', '137#8', 1),
('TRACKER_FIELD_READ', '137#9', 1),
('TRACKER_FIELD_READ', '137#10', 1),
('TRACKER_FIELD_READ', '137#11', 1),
('TRACKER_FIELD_READ', '137#12', 1),
('TRACKER_FIELD_READ', '137#13', 1),
('TRACKER_FIELD_READ', '137#14', 1),
('TRACKER_FIELD_READ', '137#15', 1),
('TRACKER_FIELD_READ', '137#16', 1),
('TRACKER_FIELD_READ', '137#17', 1),
('TRACKER_FIELD_READ', '137#18', 1),
('TRACKER_FIELD_READ', '137#19', 1),
('TRACKER_FIELD_READ', '137#20', 1),
('TRACKER_FIELD_READ', '137#22', 1),
('TRACKER_FIELD_READ', '137#23', 1),
('TRACKER_FIELD_READ', '137#24', 1),
('TRACKER_FIELD_READ', '137#26', 1),
('TRACKER_FIELD_READ', '137#27', 1),
('TRACKER_FIELD_READ', '137#28', 1),
('TRACKER_FIELD_READ', '137#29', 1),
('TRACKER_FIELD_READ', '137#30', 1),
('TRACKER_FIELD_UPDATE', '137#2', 3),
('TRACKER_FIELD_UPDATE', '137#3', 3),
('TRACKER_FIELD_UPDATE', '137#4', 3),
('TRACKER_FIELD_UPDATE', '137#5', 3),
('TRACKER_FIELD_UPDATE', '137#8', 3),
('TRACKER_FIELD_UPDATE', '137#9', 3),
('TRACKER_FIELD_UPDATE', '137#10', 3),
('TRACKER_FIELD_UPDATE', '137#11', 3),
('TRACKER_FIELD_UPDATE', '137#12', 3),
('TRACKER_FIELD_UPDATE', '137#13', 3),
('TRACKER_FIELD_UPDATE', '137#14', 3),
('TRACKER_FIELD_UPDATE', '137#15', 3),
('TRACKER_FIELD_UPDATE', '137#16', 3),
('TRACKER_FIELD_UPDATE', '137#17', 3),
('TRACKER_FIELD_UPDATE', '137#18', 3),
('TRACKER_FIELD_UPDATE', '137#19', 3),
('TRACKER_FIELD_UPDATE', '137#20', 3),
('TRACKER_FIELD_UPDATE', '137#22', 3),
('TRACKER_FIELD_UPDATE', '137#23', 3),
('TRACKER_FIELD_UPDATE', '137#24', 3),
('TRACKER_FIELD_UPDATE', '137#26', 3),
('TRACKER_FIELD_UPDATE', '137#27', 3),
('TRACKER_FIELD_UPDATE', '137#28', 3),
('TRACKER_FIELD_UPDATE', '137#29', 3),
('TRACKER_FIELD_UPDATE', '137#30', 3),
('TRACKER_ACCESS_FULL', '138', 1),
('PLUGIN_DOCMAN_READ', '138', 1),
('PLUGIN_DOCMAN_MANAGE', '138', 3),
('RELEASE_READ', '138', 2),
('TRACKER_FIELD_SUBMIT', '138#2', 3),
('TRACKER_FIELD_SUBMIT', '138#4', 3),
('TRACKER_FIELD_SUBMIT', '138#5', 3),
('TRACKER_FIELD_SUBMIT', '138#6', 3),
('TRACKER_FIELD_SUBMIT', '138#7', 3),
('TRACKER_FIELD_SUBMIT', '138#8', 3),
('TRACKER_FIELD_SUBMIT', '138#9', 3),
('TRACKER_FIELD_SUBMIT', '138#12', 3),
('TRACKER_FIELD_SUBMIT', '138#14', 3),
('TRACKER_FIELD_READ', '138#1', 1),
('TRACKER_FIELD_READ', '138#2', 1),
('TRACKER_FIELD_READ', '138#4', 1),
('TRACKER_FIELD_READ', '138#5', 1),
('TRACKER_FIELD_READ', '138#6', 1),
('TRACKER_FIELD_READ', '138#7', 1),
('TRACKER_FIELD_READ', '138#8', 1),
('TRACKER_FIELD_READ', '138#9', 1),
('TRACKER_FIELD_READ', '138#10', 1),
('TRACKER_FIELD_READ', '138#11', 1),
('TRACKER_FIELD_READ', '138#12', 1),
('TRACKER_FIELD_READ', '138#13', 1),
('TRACKER_FIELD_READ', '138#14', 1),
('TRACKER_FIELD_READ', '138#15', 1),
('TRACKER_FIELD_UPDATE', '138#2', 3),
('TRACKER_FIELD_UPDATE', '138#4', 3),
('TRACKER_FIELD_UPDATE', '138#5', 3),
('TRACKER_FIELD_UPDATE', '138#6', 3),
('TRACKER_FIELD_UPDATE', '138#7', 3),
('TRACKER_FIELD_UPDATE', '138#8', 3),
('TRACKER_FIELD_UPDATE', '138#9', 3),
('TRACKER_FIELD_UPDATE', '138#11', 3),
('TRACKER_FIELD_UPDATE', '138#12', 3),
('TRACKER_FIELD_UPDATE', '138#14', 3),
('TRACKER_FIELD_UPDATE', '138#15', 3),
('TRACKER_ACCESS_FULL', '139', 1),
('PLUGIN_DOCMAN_READ', '139', 1),
('PLUGIN_DOCMAN_MANAGE', '139', 3),
('TRACKER_FIELD_SUBMIT', '139#2', 1),
('TRACKER_FIELD_SUBMIT', '139#3', 1),
('TRACKER_FIELD_SUBMIT', '139#5', 1),
('TRACKER_FIELD_SUBMIT', '139#11', 1),
('TRACKER_FIELD_READ', '139#1', 1),
('TRACKER_FIELD_READ', '139#2', 1),
('TRACKER_FIELD_READ', '139#3', 1),
('TRACKER_FIELD_READ', '139#4', 1),
('TRACKER_FIELD_READ', '139#5', 1),
('TRACKER_FIELD_READ', '139#6', 1),
('TRACKER_FIELD_READ', '139#7', 1),
('TRACKER_FIELD_READ', '139#9', 1),
('TRACKER_FIELD_READ', '139#10', 1),
('TRACKER_FIELD_READ', '139#11', 1),
('TRACKER_FIELD_READ', '139#12', 1),
('TRACKER_FIELD_UPDATE', '139#2', 3),
('TRACKER_FIELD_UPDATE', '139#3', 3),
('TRACKER_FIELD_UPDATE', '139#5', 3),
('TRACKER_FIELD_UPDATE', '139#6', 3),
('TRACKER_FIELD_UPDATE', '139#7', 3),
('TRACKER_FIELD_UPDATE', '139#10', 3),
('TRACKER_FIELD_UPDATE', '139#11', 3),
('TRACKER_FIELD_UPDATE', '139#12', 3),
('TRACKER_ACCESS_FULL', '140', 1),
('PLUGIN_DOCMAN_READ', '140', 1),
('PLUGIN_DOCMAN_MANAGE', '140', 3),
('TRACKER_FIELD_SUBMIT', '140#3', 2),
('TRACKER_FIELD_SUBMIT', '140#5', 2),
('TRACKER_FIELD_SUBMIT', '140#7', 2),
('TRACKER_FIELD_SUBMIT', '140#8', 2),
('TRACKER_FIELD_SUBMIT', '140#10', 2),
('TRACKER_FIELD_READ', '140#1', 1),
('TRACKER_FIELD_READ', '140#2', 1),
('TRACKER_FIELD_READ', '140#3', 1),
('TRACKER_FIELD_READ', '140#4', 1),
('TRACKER_FIELD_READ', '140#5', 1),
('TRACKER_FIELD_READ', '140#6', 1),
('TRACKER_FIELD_READ', '140#7', 1),
('TRACKER_FIELD_READ', '140#8', 1),
('TRACKER_FIELD_READ', '140#9', 1),
('TRACKER_FIELD_READ', '140#10', 1),
('TRACKER_FIELD_READ', '140#11', 1),
('TRACKER_FIELD_READ', '140#12', 1),
('TRACKER_FIELD_UPDATE', '140#3', 3),
('TRACKER_FIELD_UPDATE', '140#5', 3),
('TRACKER_FIELD_UPDATE', '140#6', 3),
('TRACKER_FIELD_UPDATE', '140#7', 3),
('TRACKER_FIELD_UPDATE', '140#8', 3),
('TRACKER_FIELD_UPDATE', '140#9', 3),
('TRACKER_FIELD_UPDATE', '140#10', 3),
('TRACKER_FIELD_UPDATE', '140#11', 3),
('TRACKER_FIELD_UPDATE', '140#12', 3),
('PLUGIN_DOCMAN_ADMIN', '117', 4),
('PLUGIN_DOCMAN_READ', '30', 2),
('PLUGIN_DOCMAN_WRITE', '30', 3),
('PLUGIN_DOCMAN_MANAGE', '30', 4);

-- --------------------------------------------------------

-- 
-- Table structure for table 'permissions_values'
-- 

DROP TABLE IF EXISTS permissions_values;
CREATE TABLE IF NOT EXISTS permissions_values (
  permission_type text NOT NULL,
  ugroup_id int(11) NOT NULL default '0',
  is_default int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'permissions_values'
-- 

INSERT INTO permissions_values (permission_type, ugroup_id, is_default) VALUES ('PACKAGE_READ', 100, 0),
('PACKAGE_READ', 2, 1),
('PACKAGE_READ', 3, 0),
('PACKAGE_READ', 4, 0),
('PACKAGE_READ', 11, 0),
('RELEASE_READ', 100, 0),
('RELEASE_READ', 2, 0),
('RELEASE_READ', 3, 0),
('RELEASE_READ', 4, 0),
('RELEASE_READ', 11, 0),
('DOCGROUP_READ', 100, 0),
('DOCGROUP_READ', 1, 1),
('DOCGROUP_READ', 2, 0),
('DOCGROUP_READ', 3, 0),
('DOCGROUP_READ', 4, 0),
('DOCGROUP_READ', 12, 0),
('DOCGROUP_READ', 13, 0),
('DOCUMENT_READ', 100, 0),
('DOCUMENT_READ', 1, 0),
('DOCUMENT_READ', 2, 0),
('DOCUMENT_READ', 3, 0),
('DOCUMENT_READ', 4, 0),
('DOCUMENT_READ', 12, 0),
('DOCUMENT_READ', 13, 0),
('WIKI_READ', 100, 0),
('WIKI_READ', 1, 0),
('WIKI_READ', 2, 1),
('WIKI_READ', 3, 0),
('WIKI_READ', 4, 0),
('WIKI_READ', 14, 0),
('WIKIPAGE_READ', 100, 0),
('WIKIPAGE_READ', 1, 0),
('WIKIPAGE_READ', 2, 1),
('WIKIPAGE_READ', 3, 0),
('WIKIPAGE_READ', 4, 0),
('WIKIPAGE_READ', 14, 0),
('WIKIATTACHMENT_READ', 100, 0),
('WIKIATTACHMENT_READ', 1, 0),
('WIKIATTACHMENT_READ', 2, 1),
('WIKIATTACHMENT_READ', 3, 0),
('WIKIATTACHMENT_READ', 4, 0),
('NEWS_READ', 1, 1),
('TRACKER_ACCESS_FULL', 1, 1),
('TRACKER_ACCESS_FULL', 2, 0),
('TRACKER_ACCESS_FULL', 3, 0),
('TRACKER_ACCESS_FULL', 4, 0),
('TRACKER_ACCESS_FULL', 15, 0),
('TRACKER_ACCESS_SUBMITTER', 3, 0),
('TRACKER_ACCESS_SUBMITTER', 4, 0),
('TRACKER_ACCESS_SUBMITTER', 15, 0),
('TRACKER_ACCESS_ASSIGNEE', 3, 0),
('TRACKER_ACCESS_ASSIGNEE', 4, 0),
('TRACKER_ACCESS_ASSIGNEE', 15, 0),
('TRACKER_FIELD_SUBMIT', 1, 0),
('TRACKER_FIELD_SUBMIT', 2, 0),
('TRACKER_FIELD_SUBMIT', 3, 0),
('TRACKER_FIELD_SUBMIT', 4, 0),
('TRACKER_FIELD_SUBMIT', 15, 0),
('TRACKER_FIELD_READ', 1, 0),
('TRACKER_FIELD_READ', 2, 0),
('TRACKER_FIELD_READ', 3, 0),
('TRACKER_FIELD_READ', 4, 0),
('TRACKER_FIELD_READ', 15, 0),
('TRACKER_FIELD_UPDATE', 3, 0),
('TRACKER_FIELD_UPDATE', 4, 0),
('TRACKER_FIELD_UPDATE', 15, 0),
('PLUGIN_DOCMAN_READ', 1, 0),
('PLUGIN_DOCMAN_READ', 2, 1),
('PLUGIN_DOCMAN_READ', 3, 0),
('PLUGIN_DOCMAN_READ', 4, 0),
('PLUGIN_DOCMAN_WRITE', 1, 0),
('PLUGIN_DOCMAN_WRITE', 2, 0),
('PLUGIN_DOCMAN_WRITE', 3, 1),
('PLUGIN_DOCMAN_WRITE', 4, 0),
('PLUGIN_DOCMAN_MANAGE', 1, 0),
('PLUGIN_DOCMAN_MANAGE', 2, 0),
('PLUGIN_DOCMAN_MANAGE', 3, 0),
('PLUGIN_DOCMAN_MANAGE', 4, 1),
('PLUGIN_DOCMAN_ADMIN', 1, 0),
('PLUGIN_DOCMAN_ADMIN', 2, 0),
('PLUGIN_DOCMAN_ADMIN', 3, 0),
('PLUGIN_DOCMAN_ADMIN', 4, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin'
-- 

DROP TABLE IF EXISTS plugin;
CREATE TABLE IF NOT EXISTS plugin (
  id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL default '',
  available tinyint(4) NOT NULL default '0',
  prj_restricted tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin'
-- 

INSERT INTO plugin (id, name, available, prj_restricted) VALUES (1, 'pluginsadministration', 1, 0),
(2, 'docman', 1, 0),
(3, 'serverupdate', 1, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_approval'
-- 

DROP TABLE IF EXISTS plugin_docman_approval;
CREATE TABLE IF NOT EXISTS plugin_docman_approval (
  item_id int(11) unsigned NOT NULL default '0',
  table_owner int(11) unsigned NOT NULL default '0',
  `date` int(11) unsigned default NULL,
  description text,
  `status` tinyint(4) NOT NULL default '0',
  notification tinyint(4) NOT NULL default '0',
  UNIQUE KEY item_id_2 (item_id),
  KEY item_id (item_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_approval'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_approval_user'
-- 

DROP TABLE IF EXISTS plugin_docman_approval_user;
CREATE TABLE IF NOT EXISTS plugin_docman_approval_user (
  item_id int(11) unsigned NOT NULL default '0',
  reviewer_id int(11) unsigned NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  `date` int(11) unsigned default NULL,
  state tinyint(4) NOT NULL default '0',
  `comment` text,
  version int(11) unsigned default NULL,
  PRIMARY KEY  (item_id,reviewer_id),
  KEY rank (rank)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_approval_user'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_item'
-- 

DROP TABLE IF EXISTS plugin_docman_item;
CREATE TABLE IF NOT EXISTS plugin_docman_item (
  item_id int(11) unsigned NOT NULL auto_increment,
  parent_id int(11) unsigned default NULL,
  group_id int(11) unsigned default NULL,
  title text,
  description text,
  create_date int(11) unsigned default NULL,
  update_date int(11) unsigned default NULL,
  delete_date int(11) unsigned default NULL,
  user_id int(11) unsigned default NULL,
  `status` tinyint(4) NOT NULL default '0',
  obsolescence_date int(11) NOT NULL default '0',
  rank int(11) NOT NULL default '0',
  item_type int(11) unsigned default NULL,
  link_url text,
  wiki_page text,
  file_is_embedded int(11) unsigned default NULL,
  PRIMARY KEY  (item_id),
  KEY idx_group_id (group_id),
  KEY parent_id (parent_id),
  KEY rank (rank)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_item'
-- 

INSERT INTO plugin_docman_item (item_id, parent_id, group_id, title, description, create_date, update_date, delete_date, user_id, status, obsolescence_date, rank, item_type, link_url, wiki_page, file_is_embedded) VALUES (1, 0, 1, 'Documentation du projet', '', 1172236374, 1172236374, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(2, 1, 1, 'English Documentation', '', 1172236374, 1172236374, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(3, 2, 1, 'CodeX User Guide', 'A comprehensive guide describing all the CodeX services and how to use them in an optimal way. Also provides a lot of useful tips and guidelines to manage your CodeX project efficiently.', 1172236374, 1172236374, NULL, 101, 0, 0, -1, 1, NULL, NULL, NULL),
(4, 3, 1, 'PDF Version', '', 1172236374, 1172236374, NULL, 101, 0, 0, -1, 3, '/documentation/user_guide/pdf/en_US/CodeX_User_Guide.pdf', '', NULL),
(5, 3, 1, 'Multi-page HTML Version', '', 1172236374, 1172236374, NULL, 101, 0, 0, 1, 3, '/documentation/user_guide/html/en_US/index.html', '', NULL),
(6, 3, 1, 'Single-page HTML (2.7 MB) Version', '', 1172236374, 1172236374, NULL, 101, 0, 0, 2, 3, '/documentation/user_guide/html/en_US/CodeX_User_Guide.html', '', NULL),
(7, 2, 1, 'Command-Line Interface', 'A comprehensive guide describing all the functions of the CodeX Command-Line Interface.', 1172236374, 1172236374, NULL, 101, 0, 0, 1, 1, NULL, NULL, NULL),
(8, 7, 1, 'PDF Version', '', 1172236374, 1172236374, NULL, 101, 0, 0, -3, 3, '/documentation/cli/pdf/en_US/CodeX_CLI.pdf', '', NULL),
(9, 7, 1, 'Multi-page HTML Version', '', 1172236374, 1172236374, NULL, 101, 0, 0, -2, 3, '/documentation/cli/html/en_US/index.html', '', NULL),
(10, 7, 1, 'Single-page HTML Version', '', 1172236374, 1172236374, NULL, 101, 0, 0, 0, 3, '/documentation/cli/html/en_US/CodeX_CLI.html', '', NULL),
(11, 1, 1, 'Documentation en français', '', 1172236374, 1172236374, NULL, 101, 0, 0, 1, 1, NULL, NULL, NULL),
(12, 11, 1, 'Guide de l''Utilisateur CodeX', 'Un guide complet décrivant tous les services de CodeX et comment les utiliser de manière optimale. Fournit également de nombreuses astuces et explications pour gérer efficacement votre projet CodeX.', 1172236374, 1172236374, NULL, 101, 0, 0, -1, 1, NULL, NULL, NULL),
(13, 12, 1, 'Version PDF', '', 1172236374, 1172236374, NULL, 101, 0, 0, -1, 3, '/documentation/user_guide/pdf/fr_FR/CodeX_User_Guide.pdf', '', NULL),
(14, 12, 1, 'Version HTML multi-pages', '', 1172236374, 1172236374, NULL, 101, 0, 0, 1, 3, '/documentation/user_guide/html/fr_FR/index.html', '', NULL),
(15, 12, 1, 'Version HTML une page (4,2 Mo)', '', 1172236374, 1172236374, NULL, 101, 0, 0, 2, 3, '/documentation/user_guide/html/fr_FR/CodeX_User_Guide.html', '', NULL),
(16, 11, 1, 'Interface de Commande en Ligne', 'Un guide complet décrivant toutes les fonctions de l''Interface de Commande en Ligne de CodeX.', 1172236374, 1172236374, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(17, 16, 1, 'Version PDF', '', 1172236374, 1172236374, NULL, 101, 0, 0, 3, 3, '/documentation/cli/pdf/fr_FR/CodeX_CLI.pdf', '', NULL),
(18, 16, 1, 'Version HTML multi-pages', '', 1172236374, 1172236374, NULL, 101, 0, 0, 4, 3, '/documentation/cli/html/fr_FR/index.html', '', NULL),
(19, 16, 1, 'Version HTML une page', '', 1172236374, 1172236374, NULL, 101, 0, 0, 5, 3, '/documentation/cli/html/fr_FR/CodeX_CLI.html', '', NULL),
(20, 0, 100, 'roottitle_lbl_key', '', 1172236374, 1172236374, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(21, 0, 108, 'roottitle_lbl_key', '', 1172507026, 1172507026, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(22, 0, 109, 'roottitle_lbl_key', '', 1172507146, 1172507146, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(23, 0, 110, 'roottitle_lbl_key', '', 1172761384, 1172761384, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(24, 0, 111, 'roottitle_lbl_key', '', 1174566922, 1174566922, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(25, 0, 112, 'roottitle_lbl_key', '', 1174580302, 1174580302, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(26, 0, 113, 'roottitle_lbl_key', '', 1174901563, 1174901563, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(27, 0, 114, 'roottitle_lbl_key', '', 1174910607, 1174910607, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(28, 0, 115, 'roottitle_lbl_key', '', 1174910667, 1174910667, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(29, 0, 116, 'roottitle_lbl_key', '', 1174910713, 1174910713, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL),
(30, 0, 117, 'roottitle_lbl_key', '', 1174910767, 1174910767, NULL, 101, 0, 0, 0, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_log'
-- 

DROP TABLE IF EXISTS plugin_docman_log;
CREATE TABLE IF NOT EXISTS plugin_docman_log (
  `time` int(11) unsigned NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  item_id int(11) unsigned NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  `type` tinyint(4) NOT NULL default '0',
  old_value text,
  new_value text,
  field text,
  KEY `time` (`time`),
  KEY item_id (item_id),
  KEY group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_log'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_metadata'
-- 

DROP TABLE IF EXISTS plugin_docman_metadata;
CREATE TABLE IF NOT EXISTS plugin_docman_metadata (
  field_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  data_type int(11) NOT NULL default '0',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  default_value text NOT NULL,
  use_it tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (field_id,group_id),
  KEY idx_group_id (group_id),
  KEY idx_name (name(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_metadata'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_metadata_love'
-- 

DROP TABLE IF EXISTS plugin_docman_metadata_love;
CREATE TABLE IF NOT EXISTS plugin_docman_metadata_love (
  value_id int(11) NOT NULL auto_increment,
  name text NOT NULL,
  description text NOT NULL,
  rank int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (value_id),
  KEY idx_fv_status (`status`),
  KEY rank (rank),
  KEY name (name(10))
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_metadata_love'
-- 

INSERT INTO plugin_docman_metadata_love (value_id, name, description, rank, status) VALUES (100, 'love_special_none_name_key', 'love_special_none_desc_key', 0, 'P');

-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_metadata_love_md'
-- 

DROP TABLE IF EXISTS plugin_docman_metadata_love_md;
CREATE TABLE IF NOT EXISTS plugin_docman_metadata_love_md (
  field_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  PRIMARY KEY  (field_id,value_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_metadata_love_md'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_metadata_value'
-- 

DROP TABLE IF EXISTS plugin_docman_metadata_value;
CREATE TABLE IF NOT EXISTS plugin_docman_metadata_value (
  field_id int(11) NOT NULL default '0',
  item_id int(11) NOT NULL default '0',
  valueInt int(11) default NULL,
  valueText text,
  valueDate int(11) default NULL,
  valueString text,
  KEY idx_field_item_id (field_id,item_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_metadata_value'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_project_settings'
-- 

DROP TABLE IF EXISTS plugin_docman_project_settings;
CREATE TABLE IF NOT EXISTS plugin_docman_project_settings (
  group_id int(11) NOT NULL default '0',
  view varchar(255) default NULL,
  use_obsolescence_date tinyint(4) NOT NULL default '0',
  use_status tinyint(4) NOT NULL default '0',
  KEY group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_project_settings'
-- 

INSERT INTO plugin_docman_project_settings (group_id, view, use_obsolescence_date, use_status) VALUES (100, 'Tree', 0, 0),
(108, 'Tree', 0, 0),
(109, 'Tree', 0, 0),
(110, 'Tree', 0, 0),
(111, 'Tree', 0, 0),
(112, 'Tree', 0, 0),
(113, 'Tree', 0, 0),
(114, 'Tree', 0, 0),
(115, 'Tree', 0, 0),
(116, 'Tree', 0, 0),
(117, 'Tree', 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_tokens'
-- 

DROP TABLE IF EXISTS plugin_docman_tokens;
CREATE TABLE IF NOT EXISTS plugin_docman_tokens (
  user_id int(11) NOT NULL default '0',
  token varchar(32) NOT NULL default '',
  url text NOT NULL,
  PRIMARY KEY  (user_id,token)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_tokens'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_docman_version'
-- 

DROP TABLE IF EXISTS plugin_docman_version;
CREATE TABLE IF NOT EXISTS plugin_docman_version (
  id int(11) unsigned NOT NULL auto_increment,
  item_id int(11) unsigned default NULL,
  number int(11) unsigned default NULL,
  user_id int(11) unsigned default NULL,
  label text,
  changelog text,
  `date` int(11) unsigned default NULL,
  filename text,
  filesize int(11) unsigned default NULL,
  filetype text,
  path text,
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_docman_version'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'plugin_serverupdate_upgrade'
-- 

DROP TABLE IF EXISTS plugin_serverupdate_upgrade;
CREATE TABLE IF NOT EXISTS plugin_serverupdate_upgrade (
  `date` int(11) unsigned NOT NULL default '0',
  script varchar(64) NOT NULL default '',
  execution_mode varchar(32) NOT NULL default '',
  success tinyint(4) NOT NULL default '0',
  error text NOT NULL,
  PRIMARY KEY  (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'plugin_serverupdate_upgrade'
-- 

INSERT INTO plugin_serverupdate_upgrade (date, script, execution_mode, success, error) VALUES (1172243507, 'update_001', 'web', 1, ''),
(1172243691, 'update_002', 'web', 1, '');

-- --------------------------------------------------------

-- 
-- Table structure for table 'priority_plugin_hook'
-- 

DROP TABLE IF EXISTS priority_plugin_hook;
CREATE TABLE IF NOT EXISTS priority_plugin_hook (
  plugin_id int(11) NOT NULL default '0',
  hook varchar(100) NOT NULL default '',
  priority int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'priority_plugin_hook'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_assigned_to'
-- 

DROP TABLE IF EXISTS project_assigned_to;
CREATE TABLE IF NOT EXISTS project_assigned_to (
  project_assigned_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  assigned_to_id int(11) NOT NULL default '0',
  PRIMARY KEY  (project_assigned_id),
  KEY idx_project_assigned_to_task_id (project_task_id),
  KEY idx_project_assigned_to_assigned_to (assigned_to_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_assigned_to'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_cc'
-- 

DROP TABLE IF EXISTS project_cc;
CREATE TABLE IF NOT EXISTS project_cc (
  project_cc_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  email varchar(255) NOT NULL default '',
  added_by int(11) NOT NULL default '0',
  `comment` text NOT NULL,
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (project_cc_id),
  KEY project_id_idx (project_task_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_cc'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_counts_tmp'
-- 

DROP TABLE IF EXISTS project_counts_tmp;
CREATE TABLE IF NOT EXISTS project_counts_tmp (
  group_id int(11) default NULL,
  `type` text,
  count float(8,5) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_counts_tmp'
-- 

INSERT INTO project_counts_tmp (group_id, type, count) VALUES (101, 'forum', 2.19722),
(102, 'forum', 2.19722),
(103, 'forum', 2.19722),
(104, 'forum', 2.19722),
(105, 'forum', 2.19722),
(106, 'forum', 2.19722),
(107, 'forum', 2.19722),
(108, 'forum', 2.19722),
(109, 'forum', 2.19722),
(110, 'forum', 2.19722),
(100, 'tasks', 1.38629),
(100, 'bugs', 1.09861),
(100, 'support', 1.60944),
(109, 'svn', NULL),
(1, 'developers', 1.60944),
(101, 'developers', 1.60944),
(102, 'developers', 1.60944),
(103, 'developers', 1.60944),
(104, 'developers', 1.60944),
(105, 'developers', 1.60944),
(106, 'developers', 1.60944),
(107, 'developers', 1.60944),
(108, 'developers', 1.60944),
(109, 'developers', 1.60944),
(110, 'developers', 1.60944);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_counts_weekly_tmp'
-- 

DROP TABLE IF EXISTS project_counts_weekly_tmp;
CREATE TABLE IF NOT EXISTS project_counts_weekly_tmp (
  group_id int(11) default NULL,
  `type` text,
  count float(8,5) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_counts_weekly_tmp'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_dependencies'
-- 

DROP TABLE IF EXISTS project_dependencies;
CREATE TABLE IF NOT EXISTS project_dependencies (
  project_depend_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  is_dependent_on_task_id int(11) NOT NULL default '0',
  PRIMARY KEY  (project_depend_id),
  KEY idx_project_dependencies_task_id (project_task_id),
  KEY idx_project_is_dependent_on_task_id (is_dependent_on_task_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_dependencies'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_field'
-- 

DROP TABLE IF EXISTS project_field;
CREATE TABLE IF NOT EXISTS project_field (
  project_field_id int(11) NOT NULL auto_increment,
  field_name varchar(255) NOT NULL default '',
  display_type varchar(255) NOT NULL default '',
  display_size varchar(255) NOT NULL default '',
  label varchar(255) NOT NULL default '',
  description text NOT NULL,
  scope char(1) NOT NULL default '',
  required int(11) NOT NULL default '0',
  empty_ok int(11) NOT NULL default '0',
  keep_history int(11) NOT NULL default '0',
  special int(11) NOT NULL default '0',
  custom int(11) NOT NULL default '0',
  PRIMARY KEY  (project_field_id),
  KEY idx_project_field_name (field_name)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_field'
-- 

INSERT INTO project_field (project_field_id, field_name, display_type, display_size, label, description, scope, required, empty_ok, keep_history, special, custom) VALUES (90, 'project_task_id', 'TF', '6/10', 'Project Task ID', 'Unique project task identifier', 'S', 1, 0, 0, 1, 0),
(91, 'group_id', 'TF', '', 'Group ID', 'Unique project identifier', 'S', 1, 0, 0, 1, 0),
(92, 'created_by', 'SB', '', 'Created by', 'User who originally created the task', 'S', 1, 0, 0, 1, 0),
(94, 'start_date', 'DF', '10/15', 'Start date', 'Date and time when the task starts', 'S', 1, 0, 1, 0, 0),
(95, 'end_date', 'DF', '10/15', 'End date', 'Date and time when the task is finish', 'S', 1, 0, 1, 0, 0),
(96, 'group_project_id', 'SB', '', 'Subproject', 'Subproject', 'P', 1, 0, 1, 0, 0),
(97, 'percent_complete', 'SB', '', 'Percent complete', 'The percent completion', 'S', 1, 0, 1, 0, 0),
(98, 'hours', 'TF', '5/10', 'Effort', 'The estimation to do the task', 'S', 1, 0, 1, 0, 0),
(99, 'priority', 'SB', '', 'Priority', 'Level of priority for this task', 'S', 1, 0, 1, 0, 0),
(100, 'status_id', 'SB', '', 'Status', 'Task status', 'S', 1, 0, 1, 0, 0),
(102, 'summary', 'TF', '60/120', 'Summary', 'One line description of the task', 'S', 1, 0, 1, 1, 0),
(103, 'details', 'TA', '60/7', 'Original Submission', 'A full description of the task', 'S', 1, 1, 1, 1, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_field_usage'
-- 

DROP TABLE IF EXISTS project_field_usage;
CREATE TABLE IF NOT EXISTS project_field_usage (
  project_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  use_it int(11) NOT NULL default '0',
  show_on_add int(11) NOT NULL default '0',
  show_on_add_members int(11) NOT NULL default '0',
  place int(11) default NULL,
  custom_label varchar(255) default NULL,
  custom_description varchar(255) default NULL,
  custom_display_size varchar(255) default NULL,
  custom_empty_ok int(11) default NULL,
  custom_keep_history int(11) default NULL,
  KEY idx_project_fu_field_id (project_field_id),
  KEY idx_project_fu_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_field_usage'
-- 

INSERT INTO project_field_usage (project_field_id, group_id, use_it, show_on_add, show_on_add_members, place, custom_label, custom_description, custom_display_size, custom_empty_ok, custom_keep_history) VALUES (90, 100, 1, 1, 1, 10, NULL, NULL, NULL, NULL, NULL),
(91, 100, 1, 1, 1, 20, NULL, NULL, NULL, NULL, NULL),
(92, 100, 1, 1, 1, 30, NULL, NULL, NULL, NULL, NULL),
(94, 100, 1, 1, 1, 40, NULL, NULL, NULL, NULL, NULL),
(95, 100, 1, 1, 1, 50, NULL, NULL, NULL, NULL, NULL),
(96, 100, 1, 1, 1, 60, NULL, NULL, NULL, NULL, NULL),
(97, 100, 1, 1, 1, 70, NULL, NULL, NULL, NULL, NULL),
(98, 100, 1, 1, 1, 80, NULL, NULL, NULL, NULL, NULL),
(99, 100, 1, 1, 1, 80, NULL, NULL, NULL, NULL, NULL),
(100, 100, 1, 1, 1, 90, NULL, NULL, NULL, NULL, NULL),
(102, 100, 1, 1, 1, 110, NULL, NULL, NULL, NULL, NULL),
(103, 100, 1, 1, 1, 120, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_field_value'
-- 

DROP TABLE IF EXISTS project_field_value;
CREATE TABLE IF NOT EXISTS project_field_value (
  project_fv_id int(11) NOT NULL auto_increment,
  project_field_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  value_id int(11) NOT NULL default '0',
  `value` text NOT NULL,
  description text NOT NULL,
  order_id int(11) NOT NULL default '0',
  `status` char(1) NOT NULL default 'A',
  PRIMARY KEY  (project_fv_id),
  KEY idx_project_fv_field_id (project_fv_id),
  KEY idx_project_fv_group_id (group_id),
  KEY idx_project_fv_value_id (value_id),
  KEY idx_project_fv_status (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_field_value'
-- 

INSERT INTO project_field_value (project_fv_id, project_field_id, group_id, value_id, value, description, order_id, status) VALUES (101, 97, 100, 1000, 'Not started', 'The task is not started', 1, 'P'),
(102, 97, 100, 1005, '5%', '', 5, 'P'),
(103, 97, 100, 1015, '15%', '', 15, 'P'),
(104, 97, 100, 1020, '20%', '', 20, 'P'),
(105, 97, 100, 1025, '25%', '', 25, 'P'),
(106, 97, 100, 1030, '30%', '', 30, 'P'),
(107, 97, 100, 1035, '35%', '', 35, 'P'),
(108, 97, 100, 1040, '40%', '', 40, 'P'),
(109, 97, 100, 1045, '45%', '', 45, 'P'),
(110, 97, 100, 1050, '50%', '', 50, 'P'),
(111, 97, 100, 1055, '55%', '', 55, 'P'),
(112, 97, 100, 1060, '60%', '', 60, 'P'),
(113, 97, 100, 1065, '65%', '', 65, 'P'),
(114, 97, 100, 1070, '70%', '', 70, 'P'),
(115, 97, 100, 1075, '75%', '', 75, 'P'),
(116, 97, 100, 1080, '80%', '', 80, 'P'),
(117, 97, 100, 1085, '85%', '', 85, 'P'),
(118, 97, 100, 1090, '90%', '', 90, 'P'),
(119, 97, 100, 1095, '95%', '', 95, 'P'),
(120, 97, 100, 1100, '100%', '', 100, 'P'),
(121, 99, 100, 1, '1 - Lowest', '', 1, 'P'),
(122, 99, 100, 2, '2', '', 2, 'P'),
(123, 99, 100, 3, '3', '', 3, 'P'),
(124, 99, 100, 4, '4', '', 4, 'P'),
(125, 99, 100, 5, '5 - Medium', '', 5, 'P'),
(126, 99, 100, 6, '6', '', 6, 'P'),
(127, 99, 100, 7, '7', '', 7, 'P'),
(128, 99, 100, 8, '8', '', 8, 'P'),
(129, 99, 100, 9, '9 - Highest', '', 9, 'P'),
(130, 100, 100, 100, 'None', '', 1, 'P'),
(131, 100, 100, 1, 'Open', '', 2, 'P'),
(132, 100, 100, 2, 'Closed', '', 3, 'P'),
(133, 100, 100, 3, 'Deleted', '', 4, 'P'),
(134, 100, 100, 4, 'Suspended', '', 5, 'P');

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_file'
-- 

DROP TABLE IF EXISTS project_file;
CREATE TABLE IF NOT EXISTS project_file (
  project_file_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  description text NOT NULL,
  `file` longblob NOT NULL,
  filename text NOT NULL,
  filesize int(11) NOT NULL default '0',
  filetype text NOT NULL,
  PRIMARY KEY  (project_file_id),
  KEY project_task_id_idx (project_task_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_file'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_group_list'
-- 

DROP TABLE IF EXISTS project_group_list;
CREATE TABLE IF NOT EXISTS project_group_list (
  group_project_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  project_name text NOT NULL,
  is_public int(11) NOT NULL default '0',
  description text,
  order_id int(11) NOT NULL default '0',
  PRIMARY KEY  (group_project_id),
  KEY idx_project_group_list_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_group_list'
-- 

INSERT INTO project_group_list (group_project_id, group_id, project_name, is_public, description, order_id) VALUES (100, 100, 'none', 0, NULL, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_history'
-- 

DROP TABLE IF EXISTS project_history;
CREATE TABLE IF NOT EXISTS project_history (
  project_history_id int(11) NOT NULL auto_increment,
  project_task_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (project_history_id),
  KEY idx_project_history_task_id (project_task_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_history'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_metric'
-- 

DROP TABLE IF EXISTS project_metric;
CREATE TABLE IF NOT EXISTS project_metric (
  ranking int(11) NOT NULL auto_increment,
  percentile float(8,2) default NULL,
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (ranking),
  KEY idx_project_metric_group (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_metric'
-- 

INSERT INTO project_metric (ranking, percentile, group_id) VALUES (1, 100.00, 100),
(2, 91.67, 101),
(3, 83.33, 102),
(4, 75.00, 103),
(5, 66.67, 104),
(6, 58.33, 105),
(7, 50.00, 106),
(8, 41.67, 107),
(9, 33.33, 108),
(10, 25.00, 109),
(11, 16.67, 110),
(12, 8.33, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_metric_tmp1'
-- 

DROP TABLE IF EXISTS project_metric_tmp1;
CREATE TABLE IF NOT EXISTS project_metric_tmp1 (
  ranking int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  `value` float(8,5) default NULL,
  PRIMARY KEY  (ranking)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_metric_tmp1'
-- 

INSERT INTO project_metric_tmp1 (ranking, group_id, value) VALUES (1, 100, 4.09434),
(2, 101, 3.80666),
(3, 102, 3.80666),
(4, 103, 3.80666),
(5, 104, 3.80666),
(6, 105, 3.80666),
(7, 106, 3.80666),
(8, 107, 3.80666),
(9, 108, 3.80666),
(10, 109, 3.80666),
(11, 110, 3.80666),
(12, 1, 1.60944);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_metric_weekly_tmp1'
-- 

DROP TABLE IF EXISTS project_metric_weekly_tmp1;
CREATE TABLE IF NOT EXISTS project_metric_weekly_tmp1 (
  ranking int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  `value` float(8,5) default NULL,
  PRIMARY KEY  (ranking)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_metric_weekly_tmp1'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_notification'
-- 

DROP TABLE IF EXISTS project_notification;
CREATE TABLE IF NOT EXISTS project_notification (
  user_id int(11) NOT NULL default '0',
  role_id int(11) NOT NULL default '0',
  event_id int(11) NOT NULL default '0',
  notify int(11) NOT NULL default '1',
  KEY user_id_idx (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_notification'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_notification_event'
-- 

DROP TABLE IF EXISTS project_notification_event;
CREATE TABLE IF NOT EXISTS project_notification_event (
  event_id int(11) NOT NULL default '0',
  event_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY event_id_idx (event_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_notification_event'
-- 

INSERT INTO project_notification_event (event_id, event_label, short_description, description, rank) VALUES (1, 'ROLE_CHANGE', 'Role has changed', 'I''m added to or removed from this role', 10),
(2, 'NEW_COMMENT', 'New comment', 'A new followup comment is added', 20),
(3, 'NEW_FILE', 'New attachment', 'A new file attachment is added', 30),
(4, 'CC_CHANGE', 'CC Change', 'A new CC address is added/removed', 40),
(5, 'CLOSED', 'Task closed', 'The task is closed', 50),
(6, 'PSS_CHANGE', 'PSS change', 'Priority,Status changes', 60),
(7, 'ANY_OTHER_CHANGE', 'Any other Changes', 'Any changes not mentioned above', 70),
(8, 'I_MADE_IT', 'I did it', 'I am the author of the change', 80),
(9, 'NEW_TASK', 'New Task', 'A new task has been submitted', 90);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_notification_role'
-- 

DROP TABLE IF EXISTS project_notification_role;
CREATE TABLE IF NOT EXISTS project_notification_role (
  role_id int(11) NOT NULL default '0',
  role_label varchar(255) default NULL,
  short_description varchar(40) default NULL,
  description varchar(255) default NULL,
  rank int(11) NOT NULL default '0',
  KEY role_id_idx (role_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_notification_role'
-- 

INSERT INTO project_notification_role (role_id, role_label, short_description, description, rank) VALUES (1, 'SUBMITTER', 'Submitter', 'The person who submitted the task', 10),
(2, 'ASSIGNEE', 'Assignee', 'The person to whom the task was assigned', 20),
(3, 'CC', 'CC', 'The person who is in the CC list', 30),
(4, 'COMMENTER', 'Commenter', 'A person who once posted a follow-up comment', 40);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_plugin'
-- 

DROP TABLE IF EXISTS project_plugin;
CREATE TABLE IF NOT EXISTS project_plugin (
  project_id int(11) NOT NULL default '0',
  plugin_id int(11) NOT NULL default '0',
  UNIQUE KEY project_plugin (project_id,plugin_id),
  KEY project_id_idx (project_id),
  KEY plugin_id_idx (plugin_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_plugin'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_status'
-- 

DROP TABLE IF EXISTS project_status;
CREATE TABLE IF NOT EXISTS project_status (
  status_id int(11) NOT NULL auto_increment,
  status_name text NOT NULL,
  PRIMARY KEY  (status_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_status'
-- 

INSERT INTO project_status (status_id, status_name) VALUES (1, 'Open'),
(2, 'Closed'),
(100, 'None'),
(3, 'Deleted'),
(4, 'Suspended');

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_task'
-- 

DROP TABLE IF EXISTS project_task;
CREATE TABLE IF NOT EXISTS project_task (
  project_task_id int(11) NOT NULL auto_increment,
  group_project_id int(11) NOT NULL default '0',
  summary text NOT NULL,
  details text NOT NULL,
  percent_complete int(11) NOT NULL default '0',
  priority int(11) NOT NULL default '0',
  hours float(10,2) NOT NULL default '0.00',
  start_date int(11) NOT NULL default '0',
  end_date int(11) NOT NULL default '0',
  created_by int(11) NOT NULL default '0',
  status_id int(11) NOT NULL default '0',
  PRIMARY KEY  (project_task_id),
  KEY idx_project_task_group_project_id (group_project_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_task'
-- 

INSERT INTO project_task (project_task_id, group_project_id, summary, details, percent_complete, priority, hours, start_date, end_date, created_by, status_id) VALUES (100, 100, 'None', '', 0, 0, 0.00, 0, 0, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'project_watcher'
-- 

DROP TABLE IF EXISTS project_watcher;
CREATE TABLE IF NOT EXISTS project_watcher (
  user_id int(11) NOT NULL default '0',
  watchee_id int(11) NOT NULL default '0',
  KEY user_id_idx (user_id),
  KEY watchee_id_idx (watchee_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_watcher'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'project_weekly_metric'
-- 

DROP TABLE IF EXISTS project_weekly_metric;
CREATE TABLE IF NOT EXISTS project_weekly_metric (
  ranking int(11) NOT NULL auto_increment,
  percentile float(8,2) default NULL,
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (ranking),
  KEY idx_project_metric_weekly_group (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'project_weekly_metric'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'reference'
-- 

DROP TABLE IF EXISTS reference;
CREATE TABLE IF NOT EXISTS reference (
  id int(11) NOT NULL auto_increment,
  keyword varchar(25) NOT NULL default '',
  description text NOT NULL,
  link text NOT NULL,
  scope char(1) NOT NULL default 'P',
  service_short_name text,
  PRIMARY KEY  (id),
  KEY keyword_idx (keyword),
  KEY scope_idx (scope)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'reference'
-- 

INSERT INTO reference (id, keyword, description, link, scope, service_short_name) VALUES (1, 'art', 'reference_art_desc_key', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'S', 'tracker'),
(2, 'artifact', 'reference_art_desc_key', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'S', 'tracker'),
(3, 'commit', 'reference_cvs_desc_key', '/cvs/?func=detailcommit&commit_id=$1&group_id=$group_id', 'S', 'cvs'),
(4, 'cvs', 'reference_cvs_desc_key', '/cvs/?func=detailcommit&commit_id=$1&group_id=$group_id', 'S', 'cvs'),
(5, 'rev', 'reference_svn_desc_key', '/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', 'S', 'svn'),
(6, 'revision', 'reference_svn_desc_key', '/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', 'S', 'svn'),
(7, 'svn', 'reference_svn_desc_key', '/svn/?func=detailrevision&rev_id=$1&group_id=$group_id', 'S', 'svn'),
(8, 'wiki', 'reference_wiki_desc_key', '/wiki/index.php?group_id=$group_id&pagename=$1', 'S', 'wiki'),
(9, 'wiki', 'reference_wikiversion_desc_key', '/wiki/index.php?group_id=$group_id&pagename=$1&version=$2', 'S', 'wiki'),
(12, 'news', 'reference_news_desc_key', '/forum/forum.php?forum_id=$1', 'S', 'news'),
(13, 'forum', 'reference_forum_desc_key', '/forum/forum.php?forum_id=$1', 'S', 'forum'),
(14, 'msg', 'reference_msg_desc_key', '/forum/message.php?msg_id=$1', 'S', 'forum'),
(15, 'file', 'reference_file_desc_key', '/file/confirm_download.php?group_id=$group_id&file_id=$1', 'S', 'file'),
(16, 'release', 'reference_release_desc_key', '/file/showfiles.php?group_id=$group_id6&release_id=$1', 'S', 'file'),
(90, 'bug', 'reference_bug_desc_key', '/tracker/?func=gotoid&group_id=$group_id&aid=$1&atn=bug', 'S', 'bugs'),
(91, 'task', 'reference_task_desc_key', '/tracker/?func=gotoid&group_id=$group_id&aid=$1&atn=task', 'S', 'task'),
(92, 'sr', 'reference_sr_desc_key', '/tracker/?func=gotoid&group_id=$group_id&aid=$1&atn=sr', 'S', 'support'),
(93, 'patch', 'reference_patch_desc_key', '/tracker/?func=gotoid&group_id=$group_id&aid=$1&atn=patch', 'S', 'patch'),
(100, '', 'Empty reference', '', 'S', ''),
(10, 'doc', 'reference_doc_desc_key', '/plugins/docman/?group_id=$group_id&action=show&id=$1', 'S', 'docman'),
(11, 'document', 'reference_doc_desc_key', '/plugins/docman/?group_id=$group_id&action=show&id=$1', 'S', 'docman'),
(17, 'folder', 'reference_doc_desc_key', '/plugins/docman/?group_id=$group_id&action=show&id=$1', 'S', 'docman'),
(18, 'dossier', 'reference_doc_desc_key', '/plugins/docman/?group_id=$group_id&action=show&id=$1', 'S', 'docman'),
(101, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(102, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(103, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(104, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(105, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(106, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(107, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(108, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(109, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(110, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(111, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(112, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(113, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(114, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(115, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(116, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(117, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(118, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(119, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(120, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(121, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(122, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(123, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(124, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(125, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(126, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(127, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(128, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(129, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(130, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(131, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(132, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(133, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(134, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(135, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(136, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(137, 'bug', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(138, 'task', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(139, 'sr', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', ''),
(140, 'patch', 'Tracker Artifact', '/tracker/?func=detail&aid=$1&group_id=$group_id', 'P', '');

-- --------------------------------------------------------

-- 
-- Table structure for table 'reference_group'
-- 

DROP TABLE IF EXISTS reference_group;
CREATE TABLE IF NOT EXISTS reference_group (
  id int(11) NOT NULL auto_increment,
  reference_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  is_active tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY group_id_idx (group_id,is_active)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'reference_group'
-- 

INSERT INTO reference_group (id, reference_id, group_id, is_active) VALUES (1, 1, 100, 1),
(2, 2, 100, 1),
(3, 3, 100, 1),
(4, 4, 100, 1),
(5, 5, 100, 1),
(6, 6, 100, 1),
(7, 7, 100, 1),
(8, 8, 100, 1),
(9, 9, 100, 1),
(10, 12, 100, 1),
(11, 13, 100, 1),
(12, 14, 100, 1),
(13, 15, 100, 1),
(14, 16, 100, 1),
(15, 1, 1, 1),
(16, 2, 1, 1),
(17, 3, 1, 1),
(18, 4, 1, 1),
(19, 5, 1, 1),
(20, 6, 1, 1),
(21, 7, 1, 1),
(22, 8, 1, 1),
(23, 9, 1, 1),
(24, 12, 1, 1),
(25, 13, 1, 1),
(26, 14, 1, 1),
(27, 15, 1, 1),
(28, 16, 1, 1),
(29, 12, 46, 1),
(30, 13, 46, 1),
(31, 14, 46, 1),
(32, 10, 100, 1),
(33, 11, 100, 1),
(34, 17, 100, 1),
(35, 18, 100, 1),
(36, 10, 1, 1),
(37, 11, 1, 1),
(38, 17, 1, 1),
(39, 18, 1, 1),
(40, 13, 108, 1),
(41, 14, 108, 1),
(42, 12, 108, 1),
(43, 3, 108, 1),
(44, 4, 108, 1),
(45, 15, 108, 1),
(46, 16, 108, 1),
(47, 1, 108, 1),
(48, 2, 108, 1),
(49, 5, 108, 1),
(50, 6, 108, 1),
(51, 7, 108, 1),
(52, 8, 108, 1),
(53, 9, 108, 1),
(54, 10, 108, 1),
(55, 11, 108, 1),
(56, 17, 108, 1),
(57, 18, 108, 1),
(58, 101, 108, 1),
(59, 102, 108, 1),
(60, 103, 108, 1),
(61, 104, 108, 1),
(62, 13, 109, 1),
(63, 14, 109, 1),
(64, 12, 109, 1),
(65, 3, 109, 1),
(66, 4, 109, 1),
(67, 15, 109, 1),
(68, 16, 109, 1),
(69, 1, 109, 1),
(70, 2, 109, 1),
(71, 5, 109, 1),
(72, 6, 109, 1),
(73, 7, 109, 1),
(74, 8, 109, 1),
(75, 9, 109, 1),
(76, 10, 109, 1),
(77, 11, 109, 1),
(78, 17, 109, 1),
(79, 18, 109, 1),
(80, 105, 109, 1),
(81, 106, 109, 1),
(82, 107, 109, 1),
(83, 108, 109, 1),
(84, 13, 110, 1),
(85, 14, 110, 1),
(86, 12, 110, 1),
(87, 3, 110, 1),
(88, 4, 110, 1),
(89, 15, 110, 1),
(90, 16, 110, 1),
(91, 1, 110, 1),
(92, 2, 110, 1),
(93, 5, 110, 1),
(94, 6, 110, 1),
(95, 7, 110, 1),
(96, 8, 110, 1),
(97, 9, 110, 1),
(98, 10, 110, 1),
(99, 11, 110, 1),
(100, 17, 110, 1),
(101, 18, 110, 1),
(102, 109, 110, 1),
(103, 110, 110, 1),
(104, 111, 110, 1),
(105, 112, 110, 1),
(106, 13, 111, 1),
(107, 14, 111, 1),
(108, 12, 111, 1),
(109, 3, 111, 1),
(110, 4, 111, 1),
(111, 15, 111, 1),
(112, 16, 111, 1),
(113, 1, 111, 1),
(114, 2, 111, 1),
(115, 5, 111, 1),
(116, 6, 111, 1),
(117, 7, 111, 1),
(118, 8, 111, 1),
(119, 9, 111, 1),
(120, 10, 111, 1),
(121, 11, 111, 1),
(122, 17, 111, 1),
(123, 18, 111, 1),
(124, 113, 111, 1),
(125, 114, 111, 1),
(126, 115, 111, 1),
(127, 116, 111, 1),
(128, 13, 112, 1),
(129, 14, 112, 1),
(130, 12, 112, 1),
(131, 3, 112, 1),
(132, 4, 112, 1),
(133, 15, 112, 1),
(134, 16, 112, 1),
(135, 1, 112, 1),
(136, 2, 112, 1),
(137, 5, 112, 1),
(138, 6, 112, 1),
(139, 7, 112, 1),
(140, 8, 112, 1),
(141, 9, 112, 1),
(142, 10, 112, 1),
(143, 11, 112, 1),
(144, 17, 112, 1),
(145, 18, 112, 1),
(146, 117, 112, 1),
(147, 118, 112, 1),
(148, 119, 112, 1),
(149, 120, 112, 1),
(150, 13, 113, 1),
(151, 14, 113, 1),
(152, 12, 113, 1),
(153, 3, 113, 1),
(154, 4, 113, 1),
(155, 15, 113, 1),
(156, 16, 113, 1),
(157, 1, 113, 1),
(158, 2, 113, 1),
(159, 5, 113, 1),
(160, 6, 113, 1),
(161, 7, 113, 1),
(162, 8, 113, 1),
(163, 9, 113, 1),
(164, 10, 113, 1),
(165, 11, 113, 1),
(166, 17, 113, 1),
(167, 18, 113, 1),
(168, 121, 113, 1),
(169, 122, 113, 1),
(170, 123, 113, 1),
(171, 124, 113, 1),
(172, 13, 114, 1),
(173, 14, 114, 1),
(174, 12, 114, 1),
(175, 3, 114, 1),
(176, 4, 114, 1),
(177, 15, 114, 1),
(178, 16, 114, 1),
(179, 1, 114, 1),
(180, 2, 114, 1),
(181, 5, 114, 1),
(182, 6, 114, 1),
(183, 7, 114, 1),
(184, 8, 114, 1),
(185, 9, 114, 1),
(186, 10, 114, 1),
(187, 11, 114, 1),
(188, 17, 114, 1),
(189, 18, 114, 1),
(190, 125, 114, 1),
(191, 126, 114, 1),
(192, 127, 114, 1),
(193, 128, 114, 1),
(194, 13, 115, 1),
(195, 14, 115, 1),
(196, 12, 115, 1),
(197, 3, 115, 1),
(198, 4, 115, 1),
(199, 15, 115, 1),
(200, 16, 115, 1),
(201, 1, 115, 1),
(202, 2, 115, 1),
(203, 5, 115, 1),
(204, 6, 115, 1),
(205, 7, 115, 1),
(206, 8, 115, 1),
(207, 9, 115, 1),
(208, 10, 115, 1),
(209, 11, 115, 1),
(210, 17, 115, 1),
(211, 18, 115, 1),
(212, 129, 115, 1),
(213, 130, 115, 1),
(214, 131, 115, 1),
(215, 132, 115, 1),
(216, 13, 116, 1),
(217, 14, 116, 1),
(218, 12, 116, 1),
(219, 3, 116, 1),
(220, 4, 116, 1),
(221, 15, 116, 1),
(222, 16, 116, 1),
(223, 1, 116, 1),
(224, 2, 116, 1),
(225, 5, 116, 1),
(226, 6, 116, 1),
(227, 7, 116, 1),
(228, 8, 116, 1),
(229, 9, 116, 1),
(230, 10, 116, 1),
(231, 11, 116, 1),
(232, 17, 116, 1),
(233, 18, 116, 1),
(234, 133, 116, 1),
(235, 134, 116, 1),
(236, 135, 116, 1),
(237, 136, 116, 1),
(238, 13, 117, 1),
(239, 14, 117, 1),
(240, 12, 117, 1),
(241, 3, 117, 1),
(242, 4, 117, 1),
(243, 15, 117, 1),
(244, 16, 117, 1),
(245, 1, 117, 1),
(246, 2, 117, 1),
(247, 5, 117, 1),
(248, 6, 117, 1),
(249, 7, 117, 1),
(250, 8, 117, 1),
(251, 9, 117, 1),
(252, 10, 117, 1),
(253, 11, 117, 1),
(254, 17, 117, 1),
(255, 18, 117, 1),
(256, 137, 117, 1),
(257, 138, 117, 1),
(258, 139, 117, 1),
(259, 140, 117, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'server'
-- 

DROP TABLE IF EXISTS server;
CREATE TABLE IF NOT EXISTS server (
  id int(11) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  description text NOT NULL,
  http text NOT NULL,
  https text NOT NULL,
  is_master tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'server'
-- 

INSERT INTO server (id, name, description, http, https, is_master) VALUES (1, 'Machine A', 'Bureau 104', 'http://brame-farine.grenoble.xrce.xerox.com:8017', '', 1),
(2, 'Machine B (salle serveurs)', '', 'http://cxtst2.xrce.xerox.com', '', 0),
(3, 'Berger', 'NG''s office', 'http://berger.grenoble.xrce.xerox.com:8888', '', 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'service'
-- 

DROP TABLE IF EXISTS service;
CREATE TABLE IF NOT EXISTS service (
  service_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  label text,
  description text,
  short_name text,
  link text,
  is_active int(11) NOT NULL default '0',
  is_used int(11) NOT NULL default '0',
  scope text NOT NULL,
  rank int(11) NOT NULL default '0',
  location enum('master','same','satellite') NOT NULL default 'same',
  server_id int(11) unsigned default NULL,
  is_in_iframe tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (service_id),
  KEY idx_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'service'
-- 

INSERT INTO service (service_id, group_id, label, description, short_name, link, is_active, is_used, scope, rank, location, server_id, is_in_iframe) VALUES (1, 100, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/$projectname/', 1, 1, 'system', 10, 'same', NULL, 0),
(2, 100, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=$group_id', 1, 1, 'system', 20, 'same', NULL, 0),
(3, 100, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://$projectname.$sys_default_domain', 1, 1, 'system', 30, 'same', NULL, 0),
(4, 100, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=$group_id', 1, 1, 'system', 40, 'same', NULL, 0),
(5, 100, 'service_bugs_lbl_key', 'service_bugs_desc_key', 'bugs', '/bugs/?group_id=$group_id', 0, 0, 'system', 50, 'same', NULL, 0),
(6, 100, 'service_support_lbl_key', 'service_support_desc_key', 'support', '/support/?group_id=$group_id', 0, 0, 'system', 60, 'same', NULL, 0),
(7, 100, 'service_patch_lbl_key', 'service_patch_desc_key', 'patch', '/patch/?group_id=$group_id', 0, 0, 'system', 70, 'same', NULL, 0),
(8, 100, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=$group_id', 1, 1, 'system', 80, 'same', NULL, 0),
(9, 100, 'service_task_lbl_key', 'service_task_desc_key', 'task', '/pm/?group_id=$group_id', 0, 0, 'system', 90, 'same', NULL, 0),
(10, 100, 'service_doc_lbl_key', 'service_doc_desc_key', 'doc', '/docman/?group_id=$group_id', 0, 0, 'system', 100, 'same', NULL, 0),
(11, 100, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=$group_id', 1, 1, 'system', 110, 'same', NULL, 0),
(12, 100, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=$group_id', 1, 1, 'system', 120, 'same', NULL, 0),
(13, 100, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=$group_id', 1, 1, 'system', 130, 'same', NULL, 0),
(14, 100, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=$group_id', 1, 1, 'system', 140, 'satellite', 1, 0),
(15, 100, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=$group_id', 1, 1, 'system', 150, 'same', NULL, 0),
(16, 100, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=$group_id', 1, 1, 'system', 135, 'same', NULL, 0),
(17, 100, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=$group_id', 1, 1, 'system', 105, 'same', NULL, 0),
(31, 1, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/codex/', 1, 1, 'system', 10, 'same', NULL, 0),
(32, 1, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=1', 1, 1, 'system', 20, 'same', NULL, 0),
(33, 1, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://codex.cxtst2.xrce.xerox.com', 1, 1, 'system', 30, 'same', NULL, 0),
(34, 1, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=1', 1, 1, 'system', 40, 'same', NULL, 0),
(35, 1, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=1', 1, 1, 'system', 80, 'same', NULL, 0),
(36, 1, 'service_doc_lbl_key', 'service_doc_desc_key', 'doc', '/docman/?group_id=1', 0, 0, 'system', 100, 'same', NULL, 0),
(37, 1, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=1', 1, 1, 'system', 110, 'same', NULL, 0),
(38, 1, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=1', 1, 1, 'system', 120, 'same', NULL, 0),
(39, 1, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=1', 1, 1, 'system', 130, 'same', NULL, 0),
(40, 1, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=1', 1, 1, 'system', 140, 'same', NULL, 0),
(41, 1, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=1', 1, 1, 'system', 150, 'same', NULL, 0),
(42, 1, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=1', 1, 1, 'system', 135, 'same', NULL, 0),
(43, 1, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=1', 1, 1, 'system', 105, 'same', NULL, 0),
(51, 46, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/sitenews/', 1, 1, 'system', 10, 'same', NULL, 0),
(52, 46, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=46', 1, 1, 'system', 20, 'same', NULL, 0),
(53, 46, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://sitenews.cxtst2.xrce.xerox.com', 1, 1, 'system', 30, 'same', NULL, 0),
(54, 46, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=46', 1, 1, 'system', 40, 'same', NULL, 0),
(55, 46, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=46', 1, 1, 'system', 120, 'same', NULL, 0),
(56, 46, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=46', 1, 1, 'system', 140, 'same', NULL, 0),
(57, 46, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=46', 1, 1, 'system', 150, 'same', NULL, 0),
(100, 0, 'None', 'None', '', '', 0, 0, 'project', 0, 'same', NULL, 0),
(101, 100, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=$group_id', 1, 1, 'system', 95, 'same', NULL, 0),
(102, 1, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=1', 1, 1, 'system', 95, 'same', NULL, 0),
(103, 0, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=0', 1, 0, 'system', 95, 'same', NULL, 0),
(104, 46, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=46', 1, 0, 'system', 95, 'same', NULL, 0),
(105, 104, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/tda3/', 1, 1, 'system', 10, 'same', NULL, 0),
(106, 104, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=104', 1, 1, 'system', 20, 'same', NULL, 0),
(107, 105, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/tda4/', 1, 1, 'system', 10, 'same', NULL, 0),
(108, 105, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=105', 1, 1, 'system', 20, 'same', NULL, 0),
(109, 106, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/tda5/', 1, 1, 'system', 10, 'same', NULL, 0),
(110, 106, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=106', 1, 1, 'system', 20, 'same', NULL, 0),
(111, 107, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/tda6/', 1, 1, 'system', 10, 'same', NULL, 0),
(112, 107, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=107', 1, 1, 'system', 20, 'same', NULL, 0),
(113, 108, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/tda7/', 1, 1, 'system', 10, 'same', NULL, 0),
(114, 108, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=108', 1, 1, 'system', 20, 'same', NULL, 0),
(115, 108, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://tda7.brame-farine.grenoble.xrce.xerox.com:8017', 1, 0, 'system', 30, 'same', NULL, 0),
(116, 108, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=108', 1, 0, 'system', 40, 'same', NULL, 0),
(117, 108, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=108', 1, 0, 'system', 80, 'same', NULL, 0),
(118, 108, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=108', 1, 1, 'system', 110, 'same', NULL, 0),
(119, 108, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=108', 1, 0, 'system', 120, 'same', NULL, 0),
(120, 108, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=108', 1, 0, 'system', 130, 'same', NULL, 0),
(121, 108, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=108', 1, 1, 'system', 140, 'same', NULL, 0),
(122, 108, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=108', 1, 0, 'system', 150, 'same', NULL, 0),
(123, 108, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=108', 1, 1, 'system', 135, 'same', NULL, 0),
(124, 108, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=108', 1, 0, 'system', 105, 'same', NULL, 0),
(125, 108, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=108', 1, 1, 'system', 95, 'same', NULL, 0),
(126, 109, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/tda8/', 1, 1, 'system', 10, 'same', NULL, 0),
(127, 109, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=109', 1, 1, 'system', 20, 'same', NULL, 0),
(128, 109, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://tda8.brame-farine.grenoble.xrce.xerox.com:8017', 1, 0, 'system', 30, 'same', NULL, 0),
(129, 109, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=109', 1, 0, 'system', 40, 'same', NULL, 0),
(130, 109, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=109', 1, 0, 'system', 80, 'same', NULL, 0),
(131, 109, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=109', 1, 1, 'system', 110, 'same', NULL, 0),
(132, 109, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=109', 1, 0, 'system', 120, 'same', NULL, 0),
(133, 109, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=109', 1, 0, 'system', 130, 'same', NULL, 0),
(134, 109, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=109', 1, 1, 'system', 140, 'satellite', 2, 0),
(135, 109, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=109', 1, 0, 'system', 150, 'same', NULL, 0),
(136, 109, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=109', 1, 1, 'system', 135, 'satellite', 2, 0),
(137, 109, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=109', 1, 0, 'system', 105, 'same', NULL, 0),
(138, 109, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=109', 1, 1, 'system', 95, 'same', NULL, 0),
(139, 110, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/test/', 1, 1, 'system', 10, 'same', NULL, 0),
(140, 110, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=110', 1, 1, 'system', 20, 'same', NULL, 0),
(141, 110, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://test.brame-farine.grenoble.xrce.xerox.com:8017', 1, 1, 'system', 30, 'same', NULL, 0),
(142, 110, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=110', 1, 1, 'system', 40, 'same', NULL, 0),
(143, 110, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=110', 1, 1, 'system', 80, 'same', NULL, 0),
(144, 110, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=110', 1, 1, 'system', 110, 'same', NULL, 0),
(145, 110, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=110', 1, 1, 'system', 120, 'same', NULL, 0),
(146, 110, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=110', 1, 1, 'system', 130, 'same', NULL, 0),
(147, 110, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=110', 1, 1, 'system', 140, 'satellite', 2, 0),
(148, 110, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=110', 1, 1, 'system', 150, 'same', NULL, 0),
(149, 110, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=110', 1, 1, 'system', 135, 'same', NULL, 0),
(150, 110, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=110', 1, 1, 'system', 105, 'same', NULL, 0),
(151, 110, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=110', 1, 1, 'system', 95, 'same', NULL, 0),
(152, 111, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/testnews/', 1, 1, 'system', 10, 'same', NULL, 0),
(153, 111, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=111', 1, 1, 'system', 20, 'same', NULL, 0),
(154, 111, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://testnews.brame-farine.grenoble.xrce.xerox.com:8017', 1, 0, 'system', 30, 'same', NULL, 0),
(155, 111, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=111', 1, 1, 'system', 40, 'same', NULL, 0),
(156, 111, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=111', 1, 0, 'system', 80, 'same', NULL, 0),
(157, 111, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=111', 1, 0, 'system', 110, 'same', NULL, 0),
(158, 111, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=111', 1, 1, 'system', 120, 'same', NULL, 0),
(159, 111, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=111', 1, 0, 'system', 130, 'same', NULL, 0),
(160, 111, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=111', 1, 0, 'system', 140, 'satellite', 1, 0),
(161, 111, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=111', 1, 0, 'system', 150, 'same', NULL, 0),
(162, 111, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=111', 1, 0, 'system', 135, 'same', NULL, 0),
(163, 111, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=111', 1, 0, 'system', 105, 'same', NULL, 0),
(164, 111, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=111', 1, 0, 'system', 95, 'same', NULL, 0),
(165, 112, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/testsvn/', 1, 1, 'system', 10, 'same', NULL, 0),
(166, 112, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=112', 1, 1, 'system', 20, 'same', NULL, 0),
(167, 112, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://testsvn.cxtst2.xrce.xerox.com', 1, 0, 'system', 30, 'same', NULL, 0),
(168, 112, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=112', 1, 0, 'system', 40, 'same', NULL, 0),
(169, 112, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=112', 1, 0, 'system', 80, 'same', NULL, 0),
(170, 112, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=112', 1, 0, 'system', 110, 'same', NULL, 0),
(171, 112, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=112', 1, 0, 'system', 120, 'same', NULL, 0),
(172, 112, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=112', 1, 0, 'system', 130, 'same', NULL, 0),
(173, 112, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=112', 1, 0, 'system', 140, 'satellite', 1, 0),
(174, 112, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=112', 1, 0, 'system', 150, 'same', NULL, 0),
(175, 112, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=112', 1, 1, 'system', 135, 'same', NULL, 0),
(176, 112, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=112', 1, 0, 'system', 105, 'same', NULL, 0),
(177, 112, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=112', 1, 0, 'system', 95, 'same', NULL, 0),
(178, 113, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/testsii/', 1, 1, 'system', 10, 'same', NULL, 0),
(179, 113, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=113', 1, 1, 'system', 20, 'same', NULL, 0),
(180, 113, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://testsii.brame-farine.grenoble.xrce.xerox.com:8017', 1, 0, 'system', 30, 'same', NULL, 0),
(181, 113, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=113', 1, 0, 'system', 40, 'same', NULL, 0),
(182, 113, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=113', 1, 1, 'system', 80, 'same', NULL, 0),
(183, 113, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=113', 1, 0, 'system', 110, 'same', NULL, 0),
(184, 113, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=113', 1, 0, 'system', 120, 'same', NULL, 0),
(185, 113, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=113', 1, 0, 'system', 130, 'same', NULL, 0),
(186, 113, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=113', 1, 0, 'system', 140, 'satellite', 1, 0),
(187, 113, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=113', 1, 0, 'system', 150, 'same', NULL, 0),
(188, 113, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=113', 1, 0, 'system', 135, 'same', NULL, 0),
(189, 113, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=113', 1, 0, 'system', 105, 'same', NULL, 0),
(190, 113, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=113', 1, 0, 'system', 95, 'same', NULL, 0),
(191, 114, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/test-t-cvs/', 1, 1, 'system', 10, 'same', NULL, 0),
(192, 114, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=114', 1, 1, 'system', 20, 'same', NULL, 0),
(193, 114, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://test-t-cvs.brame-farine.grenoble.xrce.xerox.com:8017', 1, 0, 'system', 30, 'same', NULL, 0),
(194, 114, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=114', 1, 0, 'system', 40, 'same', NULL, 0),
(195, 114, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=114', 1, 0, 'system', 80, 'same', NULL, 0),
(196, 114, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=114', 1, 0, 'system', 110, 'same', NULL, 0),
(197, 114, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=114', 1, 0, 'system', 120, 'same', NULL, 0),
(198, 114, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=114', 1, 1, 'system', 130, 'same', NULL, 0),
(199, 114, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=114', 1, 0, 'system', 140, 'satellite', 2, 0),
(200, 114, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=114', 1, 0, 'system', 150, 'same', NULL, 0),
(201, 114, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=114', 1, 0, 'system', 135, 'same', NULL, 0),
(202, 114, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=114', 1, 0, 'system', 105, 'same', NULL, 0),
(203, 114, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=114', 1, 0, 'system', 95, 'same', NULL, 0),
(204, 115, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/test-t-svn/', 1, 1, 'system', 10, 'same', NULL, 0),
(205, 115, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=115', 1, 1, 'system', 20, 'same', NULL, 0),
(206, 115, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://test-t-svn.brame-farine.grenoble.xrce.xerox.com:8017', 1, 0, 'system', 30, 'same', NULL, 0),
(207, 115, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=115', 1, 0, 'system', 40, 'same', NULL, 0),
(208, 115, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=115', 1, 0, 'system', 80, 'same', NULL, 0),
(209, 115, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=115', 1, 0, 'system', 110, 'same', NULL, 0),
(210, 115, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=115', 1, 0, 'system', 120, 'same', NULL, 0),
(211, 115, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=115', 1, 0, 'system', 130, 'same', NULL, 0),
(212, 115, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=115', 1, 0, 'system', 140, 'satellite', 1, 0),
(213, 115, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=115', 1, 0, 'system', 150, 'same', NULL, 0),
(214, 115, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=115', 1, 1, 'system', 135, 'same', NULL, 0),
(215, 115, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=115', 1, 0, 'system', 105, 'same', NULL, 0),
(216, 115, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=115', 1, 0, 'system', 95, 'same', NULL, 0),
(217, 116, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/test-t-docman/', 1, 1, 'system', 10, 'same', NULL, 0),
(218, 116, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=116', 1, 1, 'system', 20, 'same', NULL, 0),
(219, 116, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://test-t-docman.brame-farine.grenoble.xrce.xerox.com:8017', 1, 0, 'system', 30, 'same', NULL, 0),
(220, 116, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=116', 1, 0, 'system', 40, 'same', NULL, 0),
(221, 116, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=116', 1, 0, 'system', 80, 'same', NULL, 0),
(222, 116, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=116', 1, 0, 'system', 110, 'same', NULL, 0),
(223, 116, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=116', 1, 0, 'system', 120, 'same', NULL, 0),
(224, 116, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=116', 1, 0, 'system', 130, 'same', NULL, 0),
(225, 116, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=116', 1, 0, 'system', 140, 'satellite', 1, 0),
(226, 116, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=116', 1, 0, 'system', 150, 'same', NULL, 0),
(227, 116, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=116', 1, 0, 'system', 135, 'same', NULL, 0),
(228, 116, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=116', 1, 0, 'system', 105, 'same', NULL, 0),
(229, 116, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=116', 1, 1, 'system', 95, 'same', NULL, 0),
(230, 117, 'service_summary_lbl_key', 'service_summary_desc_key', 'summary', '/projects/test-t-forum/', 1, 1, 'system', 10, 'same', NULL, 0),
(231, 117, 'service_admin_lbl_key', 'service_admin_desc_key', 'admin', '/project/admin/?group_id=117', 1, 1, 'system', 20, 'same', NULL, 0),
(232, 117, 'service_homepage_lbl_key', 'service_homepage_desc_key', 'homepage', 'http://test-t-forum.brame-farine.grenoble.xrce.xerox.com:8017', 1, 0, 'system', 30, 'same', NULL, 0),
(233, 117, 'service_forum_lbl_key', 'service_forum_desc_key', 'forum', '/forum/?group_id=117', 1, 1, 'system', 40, 'same', NULL, 0),
(234, 117, 'service_mail_lbl_key', 'service_mail_desc_key', 'mail', '/mail/?group_id=117', 1, 0, 'system', 80, 'same', NULL, 0),
(235, 117, 'service_survey_lbl_key', 'service_survey_desc_key', 'survey', '/survey/?group_id=117', 1, 0, 'system', 110, 'same', NULL, 0),
(236, 117, 'service_news_lbl_key', 'service_news_desc_key', 'news', '/news/?group_id=117', 1, 0, 'system', 120, 'same', NULL, 0),
(237, 117, 'service_cvs_lbl_key', 'service_cvs_desc_key', 'cvs', '/cvs/?group_id=117', 1, 0, 'system', 130, 'same', NULL, 0),
(238, 117, 'service_file_lbl_key', 'service_file_desc_key', 'file', '/file/showfiles.php?group_id=117', 1, 0, 'system', 140, 'satellite', 2, 0),
(239, 117, 'service_tracker_lbl_key', 'service_tracker_desc_key', 'tracker', '/tracker/index.php?group_id=117', 1, 0, 'system', 150, 'same', NULL, 0),
(240, 117, 'service_svn_lbl_key', 'service_svn_desc_key', 'svn', '/svn/?group_id=117', 1, 0, 'system', 135, 'same', NULL, 0),
(241, 117, 'service_wiki_lbl_key', 'service_wiki_desc_key', 'wiki', '/wiki/?group_id=117', 1, 0, 'system', 105, 'same', NULL, 0),
(242, 117, 'plugin_docman:service_lbl_key', 'plugin_docman:service_desc_key', 'docman', '/plugins/docman/?group_id=117', 1, 0, 'system', 95, 'same', NULL, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'session'
-- 

DROP TABLE IF EXISTS session;
CREATE TABLE IF NOT EXISTS `session` (
  user_id int(11) NOT NULL default '0',
  session_hash char(32) NOT NULL default '',
  ip_addr char(15) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (session_hash),
  KEY idx_session_user_id (user_id),
  KEY time_idx (`time`),
  KEY idx_session_time (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'session'
-- 

INSERT INTO session (user_id, session_hash, ip_addr, time) VALUES (102, 'd4a947b74da157138d6bbc59845b44e4', '13.202.220.133', 1172507520),
(102, 'be045850f26e73a1e5a920598e0d6793', '13.202.220.133', 1172507667),
(102, '6f21ca9903ea81e07bed9674e4cad908', '13.202.220.133', 1172569459),
(101, '57e9eb2f18d74f5a8026780a1e8427e8', '13.202.220.133', 1172744363),
(102, '201bb418cbed74b99f8cdd755d3a08cb', '13.202.220.42', 1172584652),
(101, '677d453d7a32153c85454784cfb2251c', '13.202.220.42', 1172746605),
(101, '6ae702fea12c0fc7459bf8ffa8a36f0f', '13.202.220.133', 1172830627),
(104, '0ecadbec46a1c4d4131fe085feaa4563', '13.202.220.133', 1174567502),
(110, '8e3fc1eb0c99efcaceb52113453445a7', '13.202.220.133', 1174911232);

-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet'
-- 

DROP TABLE IF EXISTS snippet;
CREATE TABLE IF NOT EXISTS snippet (
  snippet_id int(11) NOT NULL auto_increment,
  created_by int(11) NOT NULL default '0',
  name text,
  description text,
  `type` int(11) NOT NULL default '0',
  language int(11) NOT NULL default '0',
  license text NOT NULL,
  category int(11) NOT NULL default '0',
  PRIMARY KEY  (snippet_id),
  KEY idx_snippet_language (language),
  KEY idx_snippet_category (category)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet_category'
-- 

DROP TABLE IF EXISTS snippet_category;
CREATE TABLE IF NOT EXISTS snippet_category (
  category_id int(11) NOT NULL default '0',
  category_name varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet_category'
-- 

INSERT INTO snippet_category (category_id, category_name) VALUES (100, 'None'),
(1, 'UNIX Admin'),
(2, 'HTML Manipulation'),
(3, 'Text Processing'),
(4, 'Print Processing'),
(5, 'Calendars'),
(6, 'Database'),
(7, 'Data Structure Manipulation'),
(8, 'File Management'),
(9, 'Scientific Computation'),
(10, 'Office Utilities'),
(11, 'User Interface'),
(12, 'Other'),
(13, 'Network'),
(14, 'Data Acquisition and Control');

-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet_language'
-- 

DROP TABLE IF EXISTS snippet_language;
CREATE TABLE IF NOT EXISTS snippet_language (
  language_id int(11) NOT NULL default '0',
  language_name varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet_language'
-- 

INSERT INTO snippet_language (language_id, language_name) VALUES (100, 'None'),
(1, 'Awk'),
(2, 'C'),
(3, 'C++'),
(4, 'Perl'),
(5, 'PHP'),
(6, 'Python'),
(7, 'Unix Shell'),
(8, 'Java'),
(9, 'AppleScript'),
(10, 'Visual Basic'),
(11, 'TCL'),
(12, 'Lisp'),
(13, 'Mixed'),
(14, 'JavaScript'),
(15, 'SQL'),
(16, 'MatLab'),
(17, 'Other Language'),
(18, 'LabView'),
(19, 'C#'),
(20, 'Postscript');

-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet_license'
-- 

DROP TABLE IF EXISTS snippet_license;
CREATE TABLE IF NOT EXISTS snippet_license (
  license_id int(11) NOT NULL default '0',
  license_name varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet_license'
-- 

INSERT INTO snippet_license (license_id, license_name) VALUES (100, 'None'),
(1, 'Code eXchange Policy'),
(2, 'Other');

-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet_package'
-- 

DROP TABLE IF EXISTS snippet_package;
CREATE TABLE IF NOT EXISTS snippet_package (
  snippet_package_id int(11) NOT NULL auto_increment,
  created_by int(11) NOT NULL default '0',
  name text,
  description text,
  category int(11) NOT NULL default '0',
  language int(11) NOT NULL default '0',
  PRIMARY KEY  (snippet_package_id),
  KEY idx_snippet_package_language (language),
  KEY idx_snippet_package_category (category)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet_package'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet_package_item'
-- 

DROP TABLE IF EXISTS snippet_package_item;
CREATE TABLE IF NOT EXISTS snippet_package_item (
  snippet_package_item_id int(11) NOT NULL auto_increment,
  snippet_package_version_id int(11) NOT NULL default '0',
  snippet_version_id int(11) NOT NULL default '0',
  PRIMARY KEY  (snippet_package_item_id),
  KEY idx_snippet_package_item_pkg_ver (snippet_package_version_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet_package_item'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet_package_version'
-- 

DROP TABLE IF EXISTS snippet_package_version;
CREATE TABLE IF NOT EXISTS snippet_package_version (
  snippet_package_version_id int(11) NOT NULL auto_increment,
  snippet_package_id int(11) NOT NULL default '0',
  changes text,
  version text,
  submitted_by int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  PRIMARY KEY  (snippet_package_version_id),
  KEY idx_snippet_package_version_pkg_id (snippet_package_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet_package_version'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet_type'
-- 

DROP TABLE IF EXISTS snippet_type;
CREATE TABLE IF NOT EXISTS snippet_type (
  type_id int(11) NOT NULL default '0',
  type_name varchar(255) NOT NULL default ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet_type'
-- 

INSERT INTO snippet_type (type_id, type_name) VALUES (100, 'None'),
(1, 'Function'),
(2, 'Full Script'),
(3, 'Sample Code (HOWTO)'),
(4, 'README'),
(5, 'Class'),
(6, 'Full Program'),
(7, 'Macros');

-- --------------------------------------------------------

-- 
-- Table structure for table 'snippet_version'
-- 

DROP TABLE IF EXISTS snippet_version;
CREATE TABLE IF NOT EXISTS snippet_version (
  snippet_version_id int(11) NOT NULL auto_increment,
  snippet_id int(11) NOT NULL default '0',
  changes text,
  version text,
  submitted_by int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  code longblob,
  filename varchar(255) NOT NULL default '',
  filesize varchar(50) NOT NULL default '',
  filetype varchar(50) NOT NULL default '',
  PRIMARY KEY  (snippet_version_id),
  KEY idx_snippet_version_snippet_id (snippet_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'snippet_version'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agg_logo_by_day'
-- 

DROP TABLE IF EXISTS stats_agg_logo_by_day;
CREATE TABLE IF NOT EXISTS stats_agg_logo_by_day (
  `day` int(11) default NULL,
  count int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agg_logo_by_day'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agg_logo_by_group'
-- 

DROP TABLE IF EXISTS stats_agg_logo_by_group;
CREATE TABLE IF NOT EXISTS stats_agg_logo_by_group (
  `day` int(11) default NULL,
  group_id int(11) default NULL,
  count int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agg_logo_by_group'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agg_pages_by_browser'
-- 

DROP TABLE IF EXISTS stats_agg_pages_by_browser;
CREATE TABLE IF NOT EXISTS stats_agg_pages_by_browser (
  browser varchar(8) default NULL,
  count int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agg_pages_by_browser'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agg_pages_by_day'
-- 

DROP TABLE IF EXISTS stats_agg_pages_by_day;
CREATE TABLE IF NOT EXISTS stats_agg_pages_by_day (
  `day` int(11) NOT NULL default '0',
  count int(11) NOT NULL default '0',
  KEY idx_pages_by_day_day (`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agg_pages_by_day'
-- 

INSERT INTO stats_agg_pages_by_day (day, count) VALUES (20070223, 53),
(20070226, 221),
(20070227, 211),
(20070228, 187),
(20070301, 256),
(20070302, 199),
(20070309, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agg_pages_by_day_old'
-- 

DROP TABLE IF EXISTS stats_agg_pages_by_day_old;
CREATE TABLE IF NOT EXISTS stats_agg_pages_by_day_old (
  `day` int(11) default NULL,
  count int(11) default NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agg_pages_by_day_old'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agg_site_by_day'
-- 

DROP TABLE IF EXISTS stats_agg_site_by_day;
CREATE TABLE IF NOT EXISTS stats_agg_site_by_day (
  `day` int(11) NOT NULL default '0',
  count int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agg_site_by_day'
-- 

INSERT INTO stats_agg_site_by_day (day, count) VALUES (20070223, 53),
(20070226, 221),
(20070227, 211),
(20070228, 187),
(20070301, 256),
(20070302, 199),
(20070309, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agg_site_by_group'
-- 

DROP TABLE IF EXISTS stats_agg_site_by_group;
CREATE TABLE IF NOT EXISTS stats_agg_site_by_group (
  `day` int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  count int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agg_site_by_group'
-- 

INSERT INTO stats_agg_site_by_group (day, group_id, count) VALUES (20070223, 0, 53),
(20070226, 0, 179),
(20070226, 101, 9),
(20070226, 102, 1),
(20070226, 103, 1),
(20070226, 104, 1),
(20070226, 105, 2),
(20070226, 106, 1),
(20070226, 107, 1),
(20070226, 108, 1),
(20070226, 109, 24),
(20070226, 186, 1),
(20070227, 0, 125),
(20070227, 109, 86),
(20070228, 0, 68),
(20070228, 109, 119),
(20070301, 0, 124),
(20070301, 1, 3),
(20070301, 100, 3),
(20070301, 109, 126),
(20070302, 0, 187),
(20070302, 109, 9),
(20070302, 110, 3),
(20070309, 0, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agr_filerelease'
-- 

DROP TABLE IF EXISTS stats_agr_filerelease;
CREATE TABLE IF NOT EXISTS stats_agr_filerelease (
  filerelease_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_stats_agr_tmp_fid (filerelease_id),
  KEY idx_stats_agr_tmp_gid (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agr_filerelease'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_agr_project'
-- 

DROP TABLE IF EXISTS stats_agr_project;
CREATE TABLE IF NOT EXISTS stats_agr_project (
  group_id int(11) NOT NULL default '0',
  group_ranking int(11) NOT NULL default '0',
  group_metric float(8,5) NOT NULL default '0.00000',
  developers smallint(6) NOT NULL default '0',
  file_releases smallint(6) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  site_views int(11) NOT NULL default '0',
  logo_views int(11) NOT NULL default '0',
  msg_posted smallint(6) NOT NULL default '0',
  msg_uniq_auth smallint(6) NOT NULL default '0',
  bugs_opened smallint(6) NOT NULL default '0',
  bugs_closed smallint(6) NOT NULL default '0',
  support_opened smallint(6) NOT NULL default '0',
  support_closed smallint(6) NOT NULL default '0',
  patches_opened smallint(6) NOT NULL default '0',
  patches_closed smallint(6) NOT NULL default '0',
  tasks_opened smallint(6) NOT NULL default '0',
  tasks_closed smallint(6) NOT NULL default '0',
  cvs_checkouts smallint(6) NOT NULL default '0',
  cvs_commits smallint(6) NOT NULL default '0',
  cvs_adds smallint(6) NOT NULL default '0',
  svn_commits smallint(6) NOT NULL default '0',
  svn_adds smallint(6) NOT NULL default '0',
  svn_deletes smallint(6) NOT NULL default '0',
  svn_checkouts smallint(6) NOT NULL default '0',
  svn_access_count smallint(6) NOT NULL default '0',
  KEY idx_project_agr_log_group (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_agr_project'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_ftp_downloads'
-- 

DROP TABLE IF EXISTS stats_ftp_downloads;
CREATE TABLE IF NOT EXISTS stats_ftp_downloads (
  `day` int(11) NOT NULL default '0',
  filerelease_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_ftpdl_day (`day`),
  KEY idx_ftpdl_fid (filerelease_id),
  KEY idx_ftpdl_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_ftp_downloads'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_http_downloads'
-- 

DROP TABLE IF EXISTS stats_http_downloads;
CREATE TABLE IF NOT EXISTS stats_http_downloads (
  `day` int(11) NOT NULL default '0',
  filerelease_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  KEY idx_httpdl_day (`day`),
  KEY idx_httpdl_fid (filerelease_id),
  KEY idx_httpdl_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_http_downloads'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_project'
-- 

DROP TABLE IF EXISTS stats_project;
CREATE TABLE IF NOT EXISTS stats_project (
  `month` int(11) NOT NULL default '0',
  week int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  group_ranking int(11) NOT NULL default '0',
  group_metric float(8,5) NOT NULL default '0.00000',
  developers smallint(6) NOT NULL default '0',
  file_releases smallint(6) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  site_views int(11) NOT NULL default '0',
  subdomain_views int(11) NOT NULL default '0',
  msg_posted smallint(6) NOT NULL default '0',
  msg_uniq_auth smallint(6) NOT NULL default '0',
  bugs_opened smallint(6) NOT NULL default '0',
  bugs_closed smallint(6) NOT NULL default '0',
  support_opened smallint(6) NOT NULL default '0',
  support_closed smallint(6) NOT NULL default '0',
  patches_opened smallint(6) NOT NULL default '0',
  patches_closed smallint(6) NOT NULL default '0',
  tasks_opened smallint(6) NOT NULL default '0',
  tasks_closed smallint(6) NOT NULL default '0',
  cvs_checkouts smallint(6) NOT NULL default '0',
  cvs_commits smallint(6) NOT NULL default '0',
  cvs_adds smallint(6) NOT NULL default '0',
  svn_commits smallint(6) NOT NULL default '0',
  svn_adds smallint(6) NOT NULL default '0',
  svn_deletes smallint(6) NOT NULL default '0',
  svn_checkouts smallint(6) NOT NULL default '0',
  svn_access_count smallint(6) NOT NULL default '0',
  artifacts_opened smallint(6) NOT NULL default '0',
  artifacts_closed smallint(6) NOT NULL default '0',
  KEY idx_project_log_group (group_id),
  KEY idx_archive_project_month (`month`),
  KEY idx_archive_project_week (week),
  KEY idx_archive_project_day (`day`),
  KEY idx_archive_project_monthday (`month`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_project'
-- 

INSERT INTO stats_project (month, week, day, group_id, group_ranking, group_metric, developers, file_releases, downloads, site_views, subdomain_views, msg_posted, msg_uniq_auth, bugs_opened, bugs_closed, support_opened, support_closed, patches_opened, patches_closed, tasks_opened, tasks_closed, cvs_checkouts, cvs_commits, cvs_adds, svn_commits, svn_adds, svn_deletes, svn_checkouts, svn_access_count, artifacts_opened, artifacts_closed) VALUES (200702, 7, 22, 1, 2, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 7, 22, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 7, 23, 1, 2, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 7, 23, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 7, 24, 1, 2, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 7, 24, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 104, 5, 64.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 102, 3, 82.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 108, 9, 27.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 107, 8, 36.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 109, 10, 18.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 1, 11, 9.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 103, 4, 73.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 106, 7, 45.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 101, 2, 91.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 25, 105, 6, 55.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 104, 5, 64.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 102, 3, 82.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 108, 9, 27.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 107, 8, 36.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 109, 10, 18.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 1, 11, 9.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 103, 4, 73.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 106, 7, 45.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 101, 2, 91.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 26, 105, 6, 55.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 104, 3, 82.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 102, 10, 18.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 108, 7, 45.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 107, 6, 55.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 109, 8, 36.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 1, 11, 9.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 103, 2, 91.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 106, 5, 64.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 101, 9, 27.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 27, 105, 4, 73.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 104, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 102, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 108, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 107, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 109, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 103, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 106, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 101, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 105, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200702, 8, 28, 110, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 104, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 102, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 108, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 107, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 109, 8, 42.00000, 1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 103, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 106, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 101, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 105, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 1, 110, 9, 33.00000, 1, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 104, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 102, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 108, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 107, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 109, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 103, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 106, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 101, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 105, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 2, 110, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 104, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 102, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 108, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 107, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 109, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 103, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 106, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 101, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 105, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 8, 3, 110, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 104, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 102, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 108, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 107, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 109, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 103, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 106, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 101, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 105, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 4, 110, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 104, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 102, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 108, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 107, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 109, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 103, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 106, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 101, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 105, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 5, 110, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 104, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 102, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 108, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 107, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 109, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 103, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 106, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 101, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 105, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 6, 110, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 104, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 102, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 108, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 107, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 109, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 103, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 106, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 101, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 105, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 7, 110, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 104, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 102, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 108, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 107, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 109, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 103, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 106, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 101, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 105, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 8, 110, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 104, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 102, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 108, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 107, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 109, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 103, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 106, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 101, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 105, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 9, 110, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 104, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 102, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 108, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 107, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 109, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 103, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 106, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 101, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 105, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 9, 10, 110, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 104, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 102, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 108, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 107, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 109, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 103, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 106, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 101, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 105, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 11, 110, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 104, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 102, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 108, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 107, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 109, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 103, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 106, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 101, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 105, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 12, 110, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 104, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 102, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 108, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 107, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 109, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 103, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 106, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 101, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 105, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 110, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_project_tmp'
-- 

DROP TABLE IF EXISTS stats_project_tmp;
CREATE TABLE IF NOT EXISTS stats_project_tmp (
  `month` int(11) NOT NULL default '0',
  week int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  group_ranking int(11) NOT NULL default '0',
  group_metric float(8,5) NOT NULL default '0.00000',
  developers smallint(6) NOT NULL default '0',
  file_releases smallint(6) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  site_views int(11) NOT NULL default '0',
  subdomain_views int(11) NOT NULL default '0',
  msg_posted smallint(6) NOT NULL default '0',
  msg_uniq_auth smallint(6) NOT NULL default '0',
  bugs_opened smallint(6) NOT NULL default '0',
  bugs_closed smallint(6) NOT NULL default '0',
  support_opened smallint(6) NOT NULL default '0',
  support_closed smallint(6) NOT NULL default '0',
  patches_opened smallint(6) NOT NULL default '0',
  patches_closed smallint(6) NOT NULL default '0',
  tasks_opened smallint(6) NOT NULL default '0',
  tasks_closed smallint(6) NOT NULL default '0',
  cvs_checkouts smallint(6) NOT NULL default '0',
  cvs_commits smallint(6) NOT NULL default '0',
  cvs_adds smallint(6) NOT NULL default '0',
  svn_commits smallint(6) NOT NULL default '0',
  svn_adds smallint(6) NOT NULL default '0',
  svn_deletes smallint(6) NOT NULL default '0',
  svn_checkouts smallint(6) NOT NULL default '0',
  svn_access_count smallint(6) NOT NULL default '0',
  artifacts_opened smallint(6) NOT NULL default '0',
  artifacts_closed smallint(6) NOT NULL default '0',
  KEY idx_project_log_group (group_id),
  KEY idx_project_stats_day (`day`),
  KEY idx_project_stats_week (week),
  KEY idx_project_stats_month (`month`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_project_tmp'
-- 

INSERT INTO stats_project_tmp (month, week, day, group_id, group_ranking, group_metric, developers, file_releases, downloads, site_views, subdomain_views, msg_posted, msg_uniq_auth, bugs_opened, bugs_closed, support_opened, support_closed, patches_opened, patches_closed, tasks_opened, tasks_closed, cvs_checkouts, cvs_commits, cvs_adds, svn_commits, svn_adds, svn_deletes, svn_checkouts, svn_access_count, artifacts_opened, artifacts_closed) VALUES (200703, 10, 13, 104, 5, 67.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 102, 3, 83.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 108, 9, 33.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 107, 8, 42.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 109, 10, 25.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 1, 12, 8.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 103, 4, 75.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 106, 7, 50.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 101, 2, 92.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 100, 1, 100.00000, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 105, 6, 58.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(200703, 10, 13, 110, 11, 17.00000, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'stats_site'
-- 

DROP TABLE IF EXISTS stats_site;
CREATE TABLE IF NOT EXISTS stats_site (
  `month` int(11) NOT NULL default '0',
  week int(11) NOT NULL default '0',
  `day` int(11) NOT NULL default '0',
  site_views int(11) NOT NULL default '0',
  subdomain_views int(11) NOT NULL default '0',
  downloads int(11) NOT NULL default '0',
  uniq_users int(11) NOT NULL default '0',
  sessions int(11) NOT NULL default '0',
  total_users int(11) NOT NULL default '0',
  new_users int(11) NOT NULL default '0',
  new_projects int(11) NOT NULL default '0',
  KEY idx_stats_site_month (`month`),
  KEY idx_stats_site_week (week),
  KEY idx_stats_site_day (`day`),
  KEY idx_stats_site_monthday (`month`,`day`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'stats_site'
-- 

INSERT INTO stats_site (month, week, day, site_views, subdomain_views, downloads, uniq_users, sessions, total_users, new_users, new_projects) VALUES (200702, 7, 22, 0, 0, 0, 0, 0, 1, 0, 0),
(200702, 7, 23, 0, 0, 0, 0, 0, 1, 1, 0),
(200702, 7, 24, 0, 0, 0, 0, 0, 1, 0, 0),
(200702, 8, 25, 0, 0, 0, 0, 0, 2, 0, 0),
(200702, 8, 26, 0, 0, 0, 1, 2, 2, 0, 9),
(200702, 8, 27, 0, 0, 0, 1, 3, 2, 0, 0),
(200702, 8, 28, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 8, 1, 0, 0, 2, 1, 2, 2, 0, 1),
(200703, 8, 2, 0, 0, 0, 1, 1, 2, 0, 0),
(200703, 8, 3, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 9, 4, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 9, 5, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 9, 6, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 9, 7, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 9, 8, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 9, 9, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 9, 10, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 10, 11, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 10, 12, 0, 0, 0, 0, 0, 2, 0, 0),
(200703, 10, 13, 0, 0, 0, 0, 0, 2, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'support'
-- 

DROP TABLE IF EXISTS support;
CREATE TABLE IF NOT EXISTS support (
  support_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  support_status_id int(11) NOT NULL default '0',
  support_category_id int(11) NOT NULL default '0',
  priority int(11) NOT NULL default '0',
  submitted_by int(11) NOT NULL default '0',
  assigned_to int(11) NOT NULL default '0',
  open_date int(11) NOT NULL default '0',
  summary text,
  close_date int(11) NOT NULL default '0',
  PRIMARY KEY  (support_id),
  KEY idx_support_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'support'
-- 

INSERT INTO support (support_id, group_id, support_status_id, support_category_id, priority, submitted_by, assigned_to, open_date, summary, close_date) VALUES (100, 100, 0, 0, 0, 0, 0, 0, NULL, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'support_canned_responses'
-- 

DROP TABLE IF EXISTS support_canned_responses;
CREATE TABLE IF NOT EXISTS support_canned_responses (
  support_canned_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  title text,
  body text,
  PRIMARY KEY  (support_canned_id),
  KEY idx_support_canned_response_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'support_canned_responses'
-- 

INSERT INTO support_canned_responses (support_canned_id, group_id, title, body) VALUES (100, 100, NULL, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table 'support_category'
-- 

DROP TABLE IF EXISTS support_category;
CREATE TABLE IF NOT EXISTS support_category (
  support_category_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  category_name text NOT NULL,
  PRIMARY KEY  (support_category_id),
  KEY idx_support_group_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'support_category'
-- 

INSERT INTO support_category (support_category_id, group_id, category_name) VALUES (100, 100, 'None');

-- --------------------------------------------------------

-- 
-- Table structure for table 'support_history'
-- 

DROP TABLE IF EXISTS support_history;
CREATE TABLE IF NOT EXISTS support_history (
  support_history_id int(11) NOT NULL auto_increment,
  support_id int(11) NOT NULL default '0',
  field_name text NOT NULL,
  old_value text NOT NULL,
  mod_by int(11) NOT NULL default '0',
  `date` int(11) default NULL,
  PRIMARY KEY  (support_history_id),
  KEY idx_support_history_support_id (support_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'support_history'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'support_messages'
-- 

DROP TABLE IF EXISTS support_messages;
CREATE TABLE IF NOT EXISTS support_messages (
  support_message_id int(11) NOT NULL auto_increment,
  support_id int(11) NOT NULL default '0',
  from_email text,
  `date` int(11) NOT NULL default '0',
  body text,
  PRIMARY KEY  (support_message_id),
  KEY idx_support_messages_support_id (support_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'support_messages'
-- 

INSERT INTO support_messages (support_message_id, support_id, from_email, date, body) VALUES (100, 100, NULL, 0, NULL);

-- --------------------------------------------------------

-- 
-- Table structure for table 'support_status'
-- 

DROP TABLE IF EXISTS support_status;
CREATE TABLE IF NOT EXISTS support_status (
  support_status_id int(11) NOT NULL auto_increment,
  status_name text,
  PRIMARY KEY  (support_status_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'support_status'
-- 

INSERT INTO support_status (support_status_id, status_name) VALUES (1, 'Open'),
(2, 'Closed'),
(3, 'Deleted');

-- --------------------------------------------------------

-- 
-- Table structure for table 'supported_languages'
-- 

DROP TABLE IF EXISTS supported_languages;
CREATE TABLE IF NOT EXISTS supported_languages (
  language_id int(11) NOT NULL auto_increment,
  name text,
  filename text,
  language_code varchar(15) default NULL,
  language_charset varchar(32) default NULL,
  active int(11) NOT NULL default '1',
  PRIMARY KEY  (language_id),
  KEY idx_supported_languages_language_code (language_code)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'supported_languages'
-- 

INSERT INTO supported_languages (language_id, name, filename, language_code, language_charset, active) VALUES (1, 'English', 'English_US.tab', 'en_US', 'ISO-8859-1', 1),
(2, 'Français', 'French_FR.tab', 'fr_FR', 'ISO-8859-1', 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'survey_question_types'
-- 

DROP TABLE IF EXISTS survey_question_types;
CREATE TABLE IF NOT EXISTS survey_question_types (
  id int(11) NOT NULL auto_increment,
  `type` text NOT NULL,
  rank int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'survey_question_types'
-- 

INSERT INTO survey_question_types (id, type, rank) VALUES (1, 'radio_buttons_1_5', 21),
(2, 'text_area', 30),
(3, 'radio_buttons_yes_no', 22),
(4, 'comment_only', 10),
(5, 'text_field', 31),
(6, 'radio_buttons', 20),
(7, 'select_box', 23),
(100, 'none', 40);

-- --------------------------------------------------------

-- 
-- Table structure for table 'survey_questions'
-- 

DROP TABLE IF EXISTS survey_questions;
CREATE TABLE IF NOT EXISTS survey_questions (
  question_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  question text NOT NULL,
  question_type int(11) NOT NULL default '0',
  PRIMARY KEY  (question_id),
  KEY idx_survey_questions_group (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'survey_questions'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'survey_radio_choices'
-- 

DROP TABLE IF EXISTS survey_radio_choices;
CREATE TABLE IF NOT EXISTS survey_radio_choices (
  choice_id int(11) NOT NULL auto_increment,
  question_id int(11) NOT NULL default '0',
  choice_rank int(11) NOT NULL default '0',
  radio_choice text NOT NULL,
  PRIMARY KEY  (choice_id),
  KEY idx_survey_radio_choices_question_id (question_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'survey_radio_choices'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'survey_rating_aggregate'
-- 

DROP TABLE IF EXISTS survey_rating_aggregate;
CREATE TABLE IF NOT EXISTS survey_rating_aggregate (
  `type` int(11) NOT NULL default '0',
  id int(11) NOT NULL default '0',
  response float NOT NULL default '0',
  count int(11) NOT NULL default '0',
  KEY idx_survey_rating_aggregate_type_id (`type`,id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'survey_rating_aggregate'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'survey_rating_response'
-- 

DROP TABLE IF EXISTS survey_rating_response;
CREATE TABLE IF NOT EXISTS survey_rating_response (
  user_id int(11) NOT NULL default '0',
  `type` int(11) NOT NULL default '0',
  id int(11) NOT NULL default '0',
  response int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  KEY idx_survey_rating_responses_user_type_id (user_id,`type`,id),
  KEY idx_survey_rating_responses_type_id (`type`,id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'survey_rating_response'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'survey_responses'
-- 

DROP TABLE IF EXISTS survey_responses;
CREATE TABLE IF NOT EXISTS survey_responses (
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  survey_id int(11) NOT NULL default '0',
  question_id int(11) NOT NULL default '0',
  response text NOT NULL,
  `date` int(11) NOT NULL default '0',
  KEY idx_survey_responses_user_survey (user_id,survey_id),
  KEY idx_survey_responses_user_survey_question (user_id,survey_id,question_id),
  KEY idx_survey_responses_survey_question (survey_id,question_id),
  KEY idx_survey_responses_group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'survey_responses'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'surveys'
-- 

DROP TABLE IF EXISTS surveys;
CREATE TABLE IF NOT EXISTS surveys (
  survey_id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  survey_title text NOT NULL,
  survey_questions text NOT NULL,
  is_active int(11) NOT NULL default '1',
  is_anonymous int(11) NOT NULL default '0',
  PRIMARY KEY  (survey_id),
  KEY idx_surveys_group (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'surveys'
-- 

INSERT INTO surveys (survey_id, group_id, survey_title, survey_questions, is_active, is_anonymous) VALUES (1, 1, 'dev_survey_title_key', '', 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'svn_checkins'
-- 

DROP TABLE IF EXISTS svn_checkins;
CREATE TABLE IF NOT EXISTS svn_checkins (
  id int(11) NOT NULL auto_increment,
  `type` enum('Change','Add','Delete') default NULL,
  commitid int(11) NOT NULL default '0',
  dirid int(11) NOT NULL default '0',
  fileid int(11) NOT NULL default '0',
  addedlines int(11) NOT NULL default '999',
  removedlines int(11) NOT NULL default '999',
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_checkins_idx (commitid,dirid,fileid),
  KEY dirid (dirid),
  KEY fileid (fileid)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'svn_checkins'
-- 

INSERT INTO svn_checkins (id, type, commitid, dirid, fileid, addedlines, removedlines) VALUES (1, 'Add', 1, 1, 1, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'svn_commits'
-- 

DROP TABLE IF EXISTS svn_commits;
CREATE TABLE IF NOT EXISTS svn_commits (
  id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  repositoryid int(11) NOT NULL default '0',
  revision int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  whoid int(11) NOT NULL default '0',
  description text,
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_commits_idx (repositoryid,revision),
  KEY whoid (whoid),
  KEY revision (revision),
  FULLTEXT KEY description (description)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'svn_commits'
-- 

INSERT INTO svn_commits (id, group_id, repositoryid, revision, date, whoid, description) VALUES (1, 109, 1, 1, 1172766643, 102, 'First import\n\n');

-- --------------------------------------------------------

-- 
-- Table structure for table 'svn_dirs'
-- 

DROP TABLE IF EXISTS svn_dirs;
CREATE TABLE IF NOT EXISTS svn_dirs (
  id int(11) NOT NULL auto_increment,
  dir varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_dir_idx (dir)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'svn_dirs'
-- 

INSERT INTO svn_dirs (id, dir) VALUES (1, 0x2e2f);

-- --------------------------------------------------------

-- 
-- Table structure for table 'svn_files'
-- 

DROP TABLE IF EXISTS svn_files;
CREATE TABLE IF NOT EXISTS svn_files (
  id int(11) NOT NULL auto_increment,
  `file` varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_file_idx (`file`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'svn_files'
-- 

INSERT INTO svn_files (id, file) VALUES (1, 0x524541444d452e747874);

-- --------------------------------------------------------

-- 
-- Table structure for table 'svn_repositories'
-- 

DROP TABLE IF EXISTS svn_repositories;
CREATE TABLE IF NOT EXISTS svn_repositories (
  id int(11) NOT NULL auto_increment,
  repository varchar(255) character set latin1 collate latin1_bin NOT NULL default '',
  PRIMARY KEY  (id),
  UNIQUE KEY uniq_repository_idx (repository)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'svn_repositories'
-- 

INSERT INTO svn_repositories (id, repository) VALUES (1, 0x2f7661722f6c69622f636f6465782f73766e726f6f742f74646138);

-- --------------------------------------------------------

-- 
-- Table structure for table 'temp_trove_treesums'
-- 

DROP TABLE IF EXISTS temp_trove_treesums;
CREATE TABLE IF NOT EXISTS temp_trove_treesums (
  trove_treesums_id int(11) NOT NULL auto_increment,
  trove_cat_id int(11) NOT NULL default '0',
  limit_1 int(11) NOT NULL default '0',
  subprojects int(11) NOT NULL default '0',
  PRIMARY KEY  (trove_treesums_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'temp_trove_treesums'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'tmp_projs_releases_tmp'
-- 

DROP TABLE IF EXISTS tmp_projs_releases_tmp;
CREATE TABLE IF NOT EXISTS tmp_projs_releases_tmp (
  `year` int(11) NOT NULL default '0',
  `month` int(11) NOT NULL default '0',
  total_proj int(11) NOT NULL default '0',
  total_releases int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'tmp_projs_releases_tmp'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'top_group'
-- 

DROP TABLE IF EXISTS top_group;
CREATE TABLE IF NOT EXISTS top_group (
  group_id int(11) NOT NULL default '0',
  group_name varchar(40) default NULL,
  downloads_all int(11) NOT NULL default '0',
  rank_downloads_all int(11) NOT NULL default '0',
  rank_downloads_all_old int(11) NOT NULL default '0',
  downloads_week int(11) NOT NULL default '0',
  rank_downloads_week int(11) NOT NULL default '0',
  rank_downloads_week_old int(11) NOT NULL default '0',
  userrank int(11) NOT NULL default '0',
  rank_userrank int(11) NOT NULL default '0',
  rank_userrank_old int(11) NOT NULL default '0',
  forumposts_week int(11) NOT NULL default '0',
  rank_forumposts_week int(11) NOT NULL default '0',
  rank_forumposts_week_old int(11) NOT NULL default '0',
  pageviews_proj int(11) NOT NULL default '0',
  rank_pageviews_proj int(11) NOT NULL default '0',
  rank_pageviews_proj_old int(11) NOT NULL default '0',
  KEY rank_downloads_all_idx (rank_downloads_all),
  KEY rank_downloads_week_idx (rank_downloads_week),
  KEY rank_userrank_idx (rank_userrank),
  KEY rank_forumposts_week_idx (rank_forumposts_week),
  KEY pageviews_proj_idx (pageviews_proj)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'top_group'
-- 

INSERT INTO top_group (group_id, group_name, downloads_all, rank_downloads_all, rank_downloads_all_old, downloads_week, rank_downloads_week, rank_downloads_week_old, userrank, rank_userrank, rank_userrank_old, forumposts_week, rank_forumposts_week, rank_forumposts_week_old, pageviews_proj, rank_pageviews_proj, rank_pageviews_proj_old) VALUES (2, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(3, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(4, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(5, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(6, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(7, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(8, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(9, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(10, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(11, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(12, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(13, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(14, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(15, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(16, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(17, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(18, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(19, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(20, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(21, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(22, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(23, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(24, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(25, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(26, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(27, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(28, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(29, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(30, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(31, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(32, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(33, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(34, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(35, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(36, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(37, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(38, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(39, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(40, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(41, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(42, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(43, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(44, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(45, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(46, NULL, 0, 3, 3, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(47, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(48, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(49, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(50, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(51, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(52, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(53, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(54, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(55, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(56, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(57, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(58, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(59, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(60, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(61, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(62, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(63, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(64, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(65, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(66, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(67, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(68, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(69, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(70, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(71, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(72, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(73, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(74, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(75, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(76, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(77, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(78, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(79, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(80, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(81, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(82, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(83, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(84, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(85, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(86, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(87, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(88, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(89, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(90, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(91, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(92, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(93, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(94, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(95, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(96, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(97, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(98, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(99, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(100, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(1, 'CodeX Administration Project', 0, 2, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0),
(101, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 1, 3, 0, 0, 0),
(102, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 2, 4, 0, 0, 0),
(103, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 3, 5, 0, 0, 0),
(104, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 4, 6, 0, 0, 0),
(105, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 5, 7, 0, 0, 0),
(106, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 6, 8, 0, 0, 0),
(107, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 7, 9, 0, 0, 0),
(108, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 8, 10, 0, 0, 0);

-- --------------------------------------------------------

-- 
-- Table structure for table 'trove_cat'
-- 

DROP TABLE IF EXISTS trove_cat;
CREATE TABLE IF NOT EXISTS trove_cat (
  trove_cat_id int(11) NOT NULL auto_increment,
  version int(11) NOT NULL default '0',
  parent int(11) NOT NULL default '0',
  root_parent int(11) NOT NULL default '0',
  shortname varchar(80) default NULL,
  fullname varchar(80) default NULL,
  description varchar(255) default NULL,
  count_subcat int(11) NOT NULL default '0',
  count_subproj int(11) NOT NULL default '0',
  fullpath text NOT NULL,
  fullpath_ids text,
  PRIMARY KEY  (trove_cat_id),
  KEY parent_idx (parent),
  KEY root_parent_idx (root_parent),
  KEY version_idx (version)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'trove_cat'
-- 

INSERT INTO trove_cat (trove_cat_id, version, parent, root_parent, shortname, fullname, description, count_subcat, count_subproj, fullpath, fullpath_ids) VALUES (1, 2000031601, 0, 0, 'audience', 'Intended Audience', 'The main class of people likely to be interested in this resource.', 0, 0, 'Intended Audience', '1'),
(2, 2000032401, 1, 1, 'endusers', 'End Users/Desktop', 'Programs and resources for software end users. Software for the desktop.', 0, 0, 'Intended Audience :: End Users/Desktop', '1 :: 2'),
(3, 2000041101, 1, 1, 'developers', 'Developers', 'Programs and resources for software developers, to include libraries.', 0, 0, 'Intended Audience :: Developers', '1 :: 3'),
(4, 2000031601, 1, 1, 'sysadmins', 'System Administrators', 'Programs and resources for people who administer computers and networks.', 0, 0, 'Intended Audience :: System Administrators', '1 :: 4'),
(5, 2000040701, 1, 1, 'other', 'Other Audience', 'Programs and resources for an unlisted audience.', 0, 0, 'Intended Audience :: Other Audience', '1 :: 5'),
(6, 2000031601, 0, 0, 'developmentstatus', 'Development Status', 'An indication of the development status of the software or resource.', 0, 0, 'Development Status', '6'),
(7, 2000040701, 6, 6, 'planning', '1 - Planning', 'This resource is in the planning stages only. There is no code.', 0, 0, 'Development Status :: 1 - Planning', '6 :: 7'),
(8, 2000040701, 6, 6, 'prealpha', '2 - Pre-Alpha', 'There is code for this project, but it is not usable except for further development.', 0, 0, 'Development Status :: 2 - Pre-Alpha', '6 :: 8'),
(9, 2000041101, 6, 6, 'alpha', '3 - Alpha', 'Resource is in early development, and probably incomplete and/or extremely buggy.', 0, 0, 'Development Status :: 3 - Alpha', '6 :: 9'),
(10, 2000040701, 6, 6, 'beta', '4 - Beta', 'Resource is in late phases of development. Deliverables are essentially complete, but may still have significant bugs.', 0, 0, 'Development Status :: 4 - Beta', '6 :: 10'),
(11, 2000040701, 6, 6, 'production', '5 - Production/Stable', 'Deliverables are complete and usable by the intended audience.', 0, 0, 'Development Status :: 5 - Production/Stable', '6 :: 11'),
(12, 2000040701, 6, 6, 'mature', '6 - Mature', 'This resource has an extensive history of successful use and has probably undergone several stable revisions.', 0, 0, 'Development Status :: 6 - Mature', '6 :: 12'),
(13, 2000031601, 0, 0, 'license', 'License', 'License terms under which the resource is distributed.', 0, 0, 'License', '13'),
(14, 2000111301, 13, 13, 'xrx', 'Xerox Code eXchange Policy', 'The default Policy ruling the code sharing attitude in Xerox.', 0, 0, 'License :: Xerox Code eXchange Policy', '13 :: 14'),
(274, 2001061501, 154, 18, 'printservices', 'Print Services', 'XAC/DDA/Print Services Projects', 0, 0, 'Topic :: Printing :: Print Services', '18 :: 154 :: 274'),
(275, 2001062601, 160, 160, 'JSP', 'JSP', 'Java Server Pages: Sun''s Java language embedded in HTML pages', 0, 0, 'Programming Language :: JSP', '160 :: 275'),
(18, 2000031601, 0, 0, 'topic', 'Topic', 'Topic categorization.', 0, 0, 'Topic', '18'),
(20, 2000111301, 18, 18, 'communications', 'Internet/Intranet Connectivity', 'Protocols, Languages, Applications intended to facilitate communication between people nad/or computers', 0, 0, 'Topic :: Internet/Intranet Connectivity', '18 :: 20'),
(22, 2000111301, 18, 18, 'docmgt', 'Document Management', 'All document related software (e.g. Doct services, repository, design/creation, encoding like Glyph or Barcode, formatting and document output like printing)', 0, 0, 'Topic :: Document Management', '18 :: 22'),
(37, 2000111301, 20, 18, 'wireless', 'Wireless Communication', 'Tools supporting wireless communication (radio, IR,...)', 0, 0, 'Topic :: Internet/Intranet Connectivity :: Wireless Communication', '18 :: 20 :: 37'),
(43, 2000111301, 18, 18, 'imagemgt', 'Image Management', 'Software to help capture, manipulate, transform, render images (e.g. image processing, color management, printing/marking, image capture, image compression technics, etc.)', 0, 0, 'Topic :: Image Management', '18 :: 43'),
(45, 2000111301, 18, 18, 'development', 'Software Development', 'Software used to aid software development ( e.g. language interpreters, compilers, debuggers, project management tools, build tools, Devt Environment, Devt Framework,etc.)', 0, 0, 'Topic :: Software Development', '18 :: 45'),
(97, 2000111301, 18, 18, 'scientific', 'Scientific/Engineering', 'Scientific or Engineering applications, to include research on non computer related sciences. (e.g. Physics and Mathematics in general, Xerography, Data Visualization Tools, etc.)', 0, 0, 'Topic :: Scientific/Engineering', '18 :: 97'),
(132, 2000111301, 18, 18, 'it', 'Information Technology', 'Applications related to information management and computer science in general (User Interface, Distributed Systems, Knowledge Mgt, Information Retrieval, Natural Language Processing, Security, Globalisation, etc.)', 0, 0, 'Topic :: Information Technology', '18 :: 132'),
(136, 2000111301, 18, 18, 'system', 'System', 'Operating system core and administration utilities (e.g Drivers, Printers drivers, Emulators, Networking, Kernels, File Systems, Clustering, Benchmark, etc...', 0, 0, 'Topic :: System', '18 :: 136'),
(154, 2000032001, 18, 18, 'printing', 'Printing', 'Tools, daemons, and utilities for printer control.', 0, 0, 'Topic :: Printing', '18 :: 154'),
(160, 2000032001, 0, 0, 'language', 'Programming Language', 'Language in which this program was written, or was meant to support.', 0, 0, 'Programming Language', '160'),
(161, 2000032001, 160, 160, 'apl', 'APL', 'APL', 0, 0, 'Programming Language :: APL', '160 :: 161'),
(164, 2000032001, 160, 160, 'c', 'C', 'C', 0, 0, 'Programming Language :: C', '160 :: 164'),
(162, 2000032001, 160, 160, 'assembly', 'Assembly', 'Assembly-level programs. Platform specific.', 0, 0, 'Programming Language :: Assembly', '160 :: 162'),
(163, 2000051001, 160, 160, 'ada', 'Ada', 'Ada', 0, 0, 'Programming Language :: Ada', '160 :: 163'),
(165, 2000032001, 160, 160, 'cpp', 'C++', 'C++', 0, 0, 'Programming Language :: C++', '160 :: 165'),
(166, 2000032401, 160, 160, 'eiffel', 'Eiffel', 'Eiffel', 0, 0, 'Programming Language :: Eiffel', '160 :: 166'),
(167, 2000032001, 160, 160, 'euler', 'Euler', 'Euler', 0, 0, 'Programming Language :: Euler', '160 :: 167'),
(168, 2000032001, 160, 160, 'forth', 'Forth', 'Forth', 0, 0, 'Programming Language :: Forth', '160 :: 168'),
(169, 2000032001, 160, 160, 'fortran', 'Fortran', 'Fortran', 0, 0, 'Programming Language :: Fortran', '160 :: 169'),
(170, 2000032001, 160, 160, 'lisp', 'Lisp', 'Lisp', 0, 0, 'Programming Language :: Lisp', '160 :: 170'),
(171, 2000041101, 160, 160, 'logo', 'Logo', 'Logo', 0, 0, 'Programming Language :: Logo', '160 :: 171'),
(172, 2000032001, 160, 160, 'ml', 'ML', 'ML', 0, 0, 'Programming Language :: ML', '160 :: 172'),
(173, 2000032001, 160, 160, 'modula', 'Modula', 'Modula-2 or Modula-3', 0, 0, 'Programming Language :: Modula', '160 :: 173'),
(174, 2000032001, 160, 160, 'objectivec', 'Objective C', 'Objective C', 0, 0, 'Programming Language :: Objective C', '160 :: 174'),
(175, 2000032001, 160, 160, 'pascal', 'Pascal', 'Pascal', 0, 0, 'Programming Language :: Pascal', '160 :: 175'),
(176, 2000032001, 160, 160, 'perl', 'Perl', 'Perl', 0, 0, 'Programming Language :: Perl', '160 :: 176'),
(177, 2000032001, 160, 160, 'prolog', 'Prolog', 'Prolog', 0, 0, 'Programming Language :: Prolog', '160 :: 177'),
(178, 2000032001, 160, 160, 'python', 'Python', 'Python', 0, 0, 'Programming Language :: Python', '160 :: 178'),
(179, 2000032001, 160, 160, 'rexx', 'Rexx', 'Rexx', 0, 0, 'Programming Language :: Rexx', '160 :: 179'),
(180, 2000032001, 160, 160, 'simula', 'Simula', 'Simula', 0, 0, 'Programming Language :: Simula', '160 :: 180'),
(181, 2000032001, 160, 160, 'smalltalk', 'Smalltalk', 'Smalltalk', 0, 0, 'Programming Language :: Smalltalk', '160 :: 181'),
(182, 2000032001, 160, 160, 'tcl', 'Tcl', 'Tcl', 0, 0, 'Programming Language :: Tcl', '160 :: 182'),
(183, 2000032001, 160, 160, 'php', 'PHP', 'PHP', 0, 0, 'Programming Language :: PHP', '160 :: 183'),
(184, 2000032001, 160, 160, 'asp', 'ASP', 'Active Server Pages', 0, 0, 'Programming Language :: ASP', '160 :: 184'),
(185, 2000032001, 160, 160, 'shell', 'Unix Shell', 'Unix Shell', 0, 0, 'Programming Language :: Unix Shell', '160 :: 185'),
(186, 2000032001, 160, 160, 'visualbasic', 'Visual Basic', 'Visual Basic', 0, 0, 'Programming Language :: Visual Basic', '160 :: 186'),
(276, 2001122001, 160, 160, 'rebol', 'Rebol', 'The Rebol programming language', 0, 0, 'Programming Language :: Rebol', '160 :: 276'),
(278, 2002051501, 6, 6, 'endoflife', '7 - End of Life', 'The software project has come to an end and it is not expected to evolve in the future', 0, 0, 'Development Status :: 7 - End of Life', '6 :: 278'),
(194, 2000111301, 13, 13, 'osi', 'Open Source Approved license', 'Open Source approved licenses. Use one of these only if Open Sourcing your Xerox software has been explicitely approved by the Xerox COMIP.', 0, 0, 'License :: Open Source Approved license', '13 :: 194'),
(196, 2000040701, 13, 13, 'other', 'Other/Proprietary License', 'Non OSI-Approved/Proprietary license.', 0, 0, 'License :: Other/Proprietary License', '13 :: 196'),
(272, 2000120801, 132, 18, 'ui', 'User Interface', 'Everything dealing with Computer UI such as new user interface paradigm, Graphical Toolkit, Widgets library,...', 0, 0, 'Topic :: Information Technology :: User Interface', '18 :: 132 :: 272'),
(198, 2000032001, 160, 160, 'java', 'Java', 'Java', 0, 0, 'Programming Language :: Java', '160 :: 198'),
(199, 2000032101, 0, 0, 'os', 'Operating System', 'What operating system the program requires to run, if any.', 0, 0, 'Operating System', '199'),
(200, 2000032101, 199, 199, 'posix', 'POSIX', 'POSIX plus standard Berkeley socket facilities. Don''t list a more specific OS unless your program requires it.', 0, 0, 'Operating System :: POSIX', '199 :: 200'),
(201, 2000032101, 200, 199, 'linux', 'Linux', 'Any version of Linux. Don''t specify a subcategory unless the program requires a particular distribution.', 0, 0, 'Operating System :: POSIX :: Linux', '199 :: 200 :: 201'),
(202, 2000111301, 200, 199, 'bsd', 'BSD', 'Any variant of BSD (FreeBSD, NetBSD, Open BSD, etc.)', 0, 0, 'Operating System :: POSIX :: BSD', '199 :: 200 :: 202'),
(207, 2000032101, 200, 199, 'sun', 'SunOS/Solaris', 'Any Sun Microsystems OS.', 0, 0, 'Operating System :: POSIX :: SunOS/Solaris', '199 :: 200 :: 207'),
(208, 2000032101, 200, 199, 'sco', 'SCO', 'SCO', 0, 0, 'Operating System :: POSIX :: SCO', '199 :: 200 :: 208'),
(209, 2000032101, 200, 199, 'hpux', 'HP-UX', 'HP-UX', 0, 0, 'Operating System :: POSIX :: HP-UX', '199 :: 200 :: 209'),
(210, 2000032101, 200, 199, 'aix', 'AIX', 'AIX', 0, 0, 'Operating System :: POSIX :: AIX', '199 :: 200 :: 210'),
(211, 2000032101, 200, 199, 'irix', 'IRIX', 'IRIX', 0, 0, 'Operating System :: POSIX :: IRIX', '199 :: 200 :: 211'),
(212, 2000032101, 200, 199, 'other', 'Other', 'Other specific POSIX OS, specified in description.', 0, 0, 'Operating System :: POSIX :: Other', '199 :: 200 :: 212'),
(213, 2000032101, 160, 160, 'other', 'Other', 'Other programming language, specified in description.', 0, 0, 'Programming Language :: Other', '160 :: 213'),
(214, 2000032101, 199, 199, 'microsoft', 'Microsoft', 'Microsoft operating systems.', 0, 0, 'Operating System :: Microsoft', '199 :: 214'),
(215, 2000032101, 214, 199, 'msdos', 'MS-DOS', 'Microsoft Disk Operating System (DOS)', 0, 0, 'Operating System :: Microsoft :: MS-DOS', '199 :: 214 :: 215'),
(216, 2000032101, 214, 199, 'windows', 'Windows', 'Windows software, not specific to any particular version of Windows.', 0, 0, 'Operating System :: Microsoft :: Windows', '199 :: 214 :: 216'),
(217, 2000032101, 216, 199, 'win31', 'Windows 3.1 or Earlier', 'Windows 3.1 or Earlier', 0, 0, 'Operating System :: Microsoft :: Windows :: Windows 3.1 or Earlier', '199 :: 214 :: 216 :: 217'),
(218, 2000032101, 216, 199, 'win95', 'Windows 95/98/2000', 'Windows 95, Windows 98, and Windows 2000.', 0, 0, 'Operating System :: Microsoft :: Windows :: Windows 95/98/2000', '199 :: 214 :: 216 :: 218'),
(219, 2000041101, 216, 199, 'winnt', 'Windows NT/2000', 'Windows NT and Windows 2000.', 0, 0, 'Operating System :: Microsoft :: Windows :: Windows NT/2000', '199 :: 214 :: 216 :: 219'),
(220, 2000032101, 199, 199, 'os2', 'OS/2', 'OS/2', 0, 0, 'Operating System :: OS/2', '199 :: 220'),
(221, 2000032101, 199, 199, 'macos', 'MacOS', 'MacOS', 0, 0, 'Operating System :: MacOS', '199 :: 221'),
(222, 2000032101, 216, 199, 'wince', 'Windows CE', 'Windows CE', 0, 0, 'Operating System :: Microsoft :: Windows :: Windows CE', '199 :: 214 :: 216 :: 222'),
(223, 2000032101, 199, 199, 'palmos', 'PalmOS', 'PalmOS (for Palm Pilot)', 0, 0, 'Operating System :: PalmOS', '199 :: 223'),
(224, 2000032101, 199, 199, 'beos', 'BeOS', 'BeOS', 0, 0, 'Operating System :: BeOS', '199 :: 224'),
(225, 2000032101, 0, 0, 'environment', 'Environment', 'Run-time environment required for this program.', 0, 0, 'Environment', '225'),
(226, 2000041101, 225, 225, 'console', 'Console (Text Based)', 'Console-based programs.', 0, 0, 'Environment :: Console (Text Based)', '225 :: 226'),
(227, 2000032401, 226, 225, 'curses', 'Curses', 'Curses-based software.', 0, 0, 'Environment :: Console (Text Based) :: Curses', '225 :: 226 :: 227'),
(228, 2000040701, 226, 225, 'newt', 'Newt', 'Newt', 0, 0, 'Environment :: Console (Text Based) :: Newt', '225 :: 226 :: 228'),
(229, 2000040701, 225, 225, 'x11', 'X11 Applications', 'Programs that run in an X windowing environment.', 0, 0, 'Environment :: X11 Applications', '225 :: 229'),
(230, 2000040701, 225, 225, 'win32', 'Win32 (MS Windows)', 'Programs designed to run in a graphical Microsoft Windows environment.', 0, 0, 'Environment :: Win32 (MS Windows)', '225 :: 230'),
(231, 2000040701, 229, 225, 'gnome', 'Gnome', 'Programs designed to run in a Gnome environment.', 0, 0, 'Environment :: X11 Applications :: Gnome', '225 :: 229 :: 231'),
(232, 2000040701, 229, 225, 'kde', 'KDE', 'Programs designed to run in a KDE environment.', 0, 0, 'Environment :: X11 Applications :: KDE', '225 :: 229 :: 232'),
(233, 2000040701, 225, 225, 'other', 'Other Environment', 'Programs designed to run in an environment other than one listed.', 0, 0, 'Environment :: Other Environment', '225 :: 233'),
(234, 2000040701, 18, 18, 'other', 'Other/Nonlisted Topic', 'Topic does not fit into any listed category.', 0, 0, 'Topic :: Other/Nonlisted Topic', '18 :: 234'),
(235, 2000041001, 199, 199, 'independent', 'OS Independent', 'This software does not depend on any particular operating system.', 0, 0, 'Operating System :: OS Independent', '199 :: 235'),
(236, 2000040701, 199, 199, 'other', 'Other OS', 'Program is designe for a nonlisted operating system.', 0, 0, 'Operating System :: Other OS', '199 :: 236'),
(237, 2000041001, 225, 225, 'web', 'Web Environment', 'This software is designed for a web environment.', 0, 0, 'Environment :: Web Environment', '225 :: 237'),
(238, 2000041101, 225, 225, 'daemon', 'No Input/Output (Daemon)', 'This program has no input or output, but is intended to run in the background as a daemon.', 0, 0, 'Environment :: No Input/Output (Daemon)', '225 :: 238'),
(240, 2000041301, 200, 199, 'gnuhurd', 'GNU Hurd', 'GNU Hurd', 0, 0, 'Operating System :: POSIX :: GNU Hurd', '199 :: 200 :: 240'),
(242, 2000042701, 160, 160, 'scheme', 'Scheme', 'Scheme programming language.', 0, 0, 'Programming Language :: Scheme', '160 :: 242'),
(254, 2000071101, 160, 160, 'plsql', 'PL/SQL', 'PL/SQL Programming Language', 0, 0, 'Programming Language :: PL/SQL', '160 :: 254'),
(255, 2000071101, 160, 160, 'progress', 'PROGRESS', 'PROGRESS Programming Language', 0, 0, 'Programming Language :: PROGRESS', '160 :: 255'),
(258, 2000071101, 160, 160, 'objectpascal', 'Object Pascal', 'Object Pascal', 0, 0, 'Programming Language :: Object Pascal', '160 :: 258'),
(261, 2000072501, 160, 160, 'xbasic', 'XBasic', 'XBasic programming language', 0, 0, 'Programming Language :: XBasic', '160 :: 261'),
(262, 2000073101, 160, 160, 'coldfusion', 'Cold Fusion', 'Cold Fusion Language', 0, 0, 'Programming Language :: Cold Fusion', '160 :: 262'),
(263, 2000080401, 160, 160, 'euphoria', 'Euphoria', 'Euphoria programming language - http://www.rapideuphoria.com/', 0, 0, 'Programming Language :: Euphoria', '160 :: 263'),
(264, 2000080701, 160, 160, 'erlang', 'Erlang', 'Erlang - developed by Ericsson - http://www.erlang.org/', 0, 0, 'Programming Language :: Erlang', '160 :: 264'),
(265, 2000080801, 160, 160, 'Delphi', 'Delphi', 'Borland/Inprise Delphi', 0, 0, 'Programming Language :: Delphi', '160 :: 265'),
(267, 2000082001, 160, 160, 'zope', 'Zope', 'Zope Object Publishing', 0, 0, 'Programming Language :: Zope', '160 :: 267'),
(269, 2001010901, 160, 160, 'ruby', 'Ruby', 'A pragmatic, purely OO, extremelly elegant programming language offering the best of Perl, Python, Smalltalk and Eiffel. Worth a try ! (See http://www.ruby-lang.org)', 0, 0, 'Programming Language :: Ruby', '160 :: 269'),
(273, 2001011601, 160, 160, 'matlab', 'Matlab', 'The Matlab (Matrix Laboratory) programming language for scientific and engineering numeric computation', 0, 0, 'Programming Language :: Matlab', '160 :: 273'),
(279, 2002081301, 154, 18, 'printdrivers', 'Drivers', 'Printer drivers', 0, 0, 'Topic :: Printing :: Drivers', '18 :: 154 :: 279');

-- --------------------------------------------------------

-- 
-- Table structure for table 'trove_group_link'
-- 

DROP TABLE IF EXISTS trove_group_link;
CREATE TABLE IF NOT EXISTS trove_group_link (
  trove_group_id int(11) NOT NULL auto_increment,
  trove_cat_id int(11) NOT NULL default '0',
  trove_cat_version int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  trove_cat_root int(11) NOT NULL default '0',
  PRIMARY KEY  (trove_group_id),
  KEY idx_trove_group_link_group_id (group_id),
  KEY idx_trove_group_link_cat_id (trove_cat_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'trove_group_link'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'trove_treesums'
-- 

DROP TABLE IF EXISTS trove_treesums;
CREATE TABLE IF NOT EXISTS trove_treesums (
  trove_treesums_id int(11) NOT NULL auto_increment,
  trove_cat_id int(11) NOT NULL default '0',
  limit_1 int(11) NOT NULL default '0',
  subprojects int(11) NOT NULL default '0',
  PRIMARY KEY  (trove_treesums_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'trove_treesums'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'ugroup'
-- 

DROP TABLE IF EXISTS ugroup;
CREATE TABLE IF NOT EXISTS ugroup (
  ugroup_id int(11) NOT NULL auto_increment,
  name text NOT NULL,
  description text NOT NULL,
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (ugroup_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'ugroup'
-- 

INSERT INTO ugroup (ugroup_id, name, description, group_id) VALUES (100, 'ugroup_nobody_name_key', 'ugroup_nobody_desc_key', 100),
(1, 'ugroup_anonymous_users_name_key', 'ugroup_anonymous_users_desc_key', 100),
(2, 'ugroup_registered_users_name_key', 'ugroup_registered_users_desc_key', 100),
(3, 'ugroup_project_members_name_key', 'ugroup_project_members_desc_key', 100),
(4, 'ugroup_project_admins_name_key', 'ugroup_project_admins_desc_key', 100),
(11, 'ugroup_file_manager_admin_name_key', 'ugroup_file_manager_admin_desc_key', 100),
(12, 'ugroup_document_tech_name_key', 'ugroup_document_tech_desc_key', 100),
(13, 'ugroup_document_admin_name_key', 'ugroup_document_admin_desc_key', 100),
(14, 'ugroup_wiki_admin_name_key', 'ugroup_wiki_admin_desc_key', 100),
(15, 'ugroup_tracker_admins_name_key', 'ugroup_tracker_admins_desc_key', 100);

-- --------------------------------------------------------

-- 
-- Table structure for table 'ugroup_mapping'
-- 

DROP TABLE IF EXISTS ugroup_mapping;
CREATE TABLE IF NOT EXISTS ugroup_mapping (
  to_group_id int(11) NOT NULL default '0',
  src_ugroup_id int(11) NOT NULL default '0',
  dst_ugroup_id int(11) NOT NULL default '0',
  PRIMARY KEY  (to_group_id,src_ugroup_id,dst_ugroup_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'ugroup_mapping'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'ugroup_user'
-- 

DROP TABLE IF EXISTS ugroup_user;
CREATE TABLE IF NOT EXISTS ugroup_user (
  ugroup_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'ugroup_user'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user'
-- 

DROP TABLE IF EXISTS user;
CREATE TABLE IF NOT EXISTS `user` (
  user_id int(11) NOT NULL auto_increment,
  user_name text NOT NULL,
  email text NOT NULL,
  user_pw varchar(32) NOT NULL default '',
  realname varchar(32) NOT NULL default '',
  register_purpose text,
  `status` char(1) NOT NULL default 'A',
  shell varchar(50) NOT NULL default '/bin/bash',
  unix_pw varchar(40) NOT NULL default '',
  unix_status char(1) NOT NULL default 'N',
  unix_uid int(11) NOT NULL default '0',
  unix_box varchar(10) NOT NULL default 'shell1',
  ldap_id text,
  add_date int(11) NOT NULL default '0',
  confirm_hash varchar(32) default NULL,
  mail_siteupdates int(11) NOT NULL default '0',
  mail_va int(11) NOT NULL default '0',
  sticky_login int(11) NOT NULL default '0',
  authorized_keys text,
  email_new text,
  people_view_skills int(11) NOT NULL default '0',
  people_resume text NOT NULL,
  timezone varchar(64) default 'GMT',
  windows_pw varchar(80) NOT NULL default '',
  fontsize int(10) unsigned NOT NULL default '0',
  theme varchar(50) default NULL,
  language_id int(11) NOT NULL default '1',
  PRIMARY KEY  (user_id),
  KEY idx_user_user (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user'
-- 

INSERT INTO user (user_id, user_name, email, user_pw, realname, register_purpose, status, shell, unix_pw, unix_status, unix_uid, unix_box, ldap_id, add_date, confirm_hash, mail_siteupdates, mail_va, sticky_login, authorized_keys, email_new, people_view_skills, people_resume, timezone, windows_pw, fontsize, theme, language_id) VALUES (100, 'None', 'noreply@cxtst2.xrce.xerox.com', '*********34343', '0', NULL, 'S', '0', '0', '0', 0, '0', NULL, 940000000, NULL, 1, 0, 0, NULL, NULL, 0, '', 'GMT', '', 0, '', 1),
(101, 'admin', 'codex-admin@cxtst2.xrce.xerox.com', '6f3cac6213ffceee27cc85414f458caa', 'Site Administrator', NULL, 'A', '/bin/bash', '$1$Sn;W@$PXu/wJEYCCN2.BmF2uSfT/', 'A', 1, 'shell1', NULL, 940000000, NULL, 0, 0, 0, NULL, NULL, 0, '', 'GMT', 'AD3682DB98997A758E5D533411003C5C:2E17AE860AC9D678CD6B9C16DBBA6006', 0, 'CodeX', 2),
(102, 'nicolas', 'nicolas.terray@xerox.com', 'deb97a759ee7b8ba42e02dddf2b412fe', 'Nicolas Terray', '', 'A', '/bin/bash', '$1$MC$0fqnMTanWxLH7qLuQDlzO0', 'A', 2, 'shell1', '', 1172244263, 'dfd6fa5193cd7a02', 0, 0, 0, NULL, NULL, 0, '', 'Zulu', '98B243DC240F6D21AAD3B435B51404EE:AEFADF1FFA93F264F38AA8D4BF9F7F51', 0, NULL, 1),
(103, 'news_admin', 'nicolas.terray@xerox.com', '2dec015e396104d9476b6e893d92663c', 'News Admin', '', 'A', '/bin/bash', '$1$NJ$OnK1voHvQMThiAWXakmt70', 'A', 3, 'shell1', '', 1174566454, 'a7374f03cbb056c5', 0, 0, 0, NULL, NULL, 0, '', 'Europe/Paris', '97AD672EF896DEA148D7645CD4E30C86:9BFB94BD2A9E5E515847FB0D075C2719', 0, NULL, 1),
(104, 'news_member', 'nicolas.terray@xerox.com', 'e9a152baeea021b57b334916846f4680', 'News Member', '', 'A', '/bin/bash', '$1$tD$zLaO89UOoFSJWnhXL/Rx81', 'A', 4, 'shell1', '', 1174566502, '6d4132c37f39461c', 0, 0, 0, NULL, NULL, 0, '', 'Europe/Paris', 'C78D70AFAD5956D8A1C640D31E01F52C:8A3DAA929935EBC116526379C140D815', 0, NULL, 1),
(105, 'registered_user', 'nicolas.terray@xerox.com', 'efc80384db0ab913acf5b1a66dfa717f', 'Registered User', '', 'A', '/bin/bash', '$1$Td$I9lx3OKxBCIOPfHblBVTm0', 'A', 5, 'shell1', '', 1174566573, '8b2812f458925b43', 0, 0, 0, NULL, NULL, 0, '', 'Europe/Paris', 'BEE3ED9C338F90FB8C12603C15E9B509:859AA558F5B83E61E70BB38C56249325', 0, NULL, 1),
(106, 'svn_admin', 'nicolas.terray@xerox.com', '0ebc6351e736aa6dd85b4cf48748f19c', 'SVN Admin', '', 'A', '/bin/bash', '$1$Kd$EDC72kBx/zlhvsiPMJJjc.', 'A', 6, 'shell1', '', 1174580028, 'aeeaa88d203d5b9b', 0, 0, 0, NULL, NULL, 0, '', 'Europe/Paris', '0045DBD750CF06D28E5D533411003C5C:9AB0B62650D2B5E6ABECEC6A093ADF10', 0, NULL, 1),
(107, 'svn_member', 'nicolas.terray@xerox.com', '300b7127d682e9ece52b04d817f65ec2', 'SVN Member', '', 'A', '/bin/bash', '$1$mH$SFqa8q15o.U05BhL3nEeN.', 'A', 7, 'shell1', '', 1174580059, '70d3760a6906a783', 0, 0, 0, NULL, NULL, 0, '', 'Europe/Paris', '65E9842A97119E9A2A12811AE4879910:671D5378D32580BD8B2C74ECCA95F758', 0, NULL, 1),
(108, 'newproject_admin', 'nicolas.terray@xerox.com', 'd9dbc747d7c1d6a4af59c350b59bd4fd', 'Newproject Admin', '', 'A', '/bin/bash', '$1$tj$h0uNDwgwp8olijfkJ.MAu/', 'A', 8, 'shell1', '', 1174582138, 'a49ab30a54934350', 0, 0, 0, NULL, NULL, 0, '', 'Europe/Paris', 'CC4CA9C9C6E44EFB116720427083D29E:E807E071A2F1DF06A1ABA94879F5EC33', 0, NULL, 1),
(109, 'serviceiniframe_admin', 'nicolas.terray@xrce.xerox.com', 'a1d8fda4db8a95bf9ec3fa1aa595c592', 'Service In Iframe Admin', '', 'A', '/bin/bash', '$1$4q$6gXHcjKIcWOT64P6i9yJe.', 'A', 9, 'shell1', '', 1174901422, '9f5f33c1e9e6fc9c', 0, 0, 0, NULL, NULL, 0, '', 'Europe/Paris', '52AE2AA5C82C72D56B43BE3E766EE304:EA11A8A7EC1D7A00FEA6B89B71F6BD7B', 0, NULL, 1),
(110, 'template_admin', 'nicolas.terray@xrce.xerox.com', '51412753efd0ff6680f90dd6082fccf1', 'Template Admin', '', 'A', '/bin/bash', '$1$Cf$WEXZihOuNjZqU3Yc/KIKU/', 'A', 10, 'shell1', '', 1174910473, '9d6f3574123cd81e', 0, 0, 0, NULL, NULL, 0, '', 'Europe/Paris', 'E6CE0058EFE79CCEDA8376CE7D6EAB2C:926013BCBD70078E4FCBD481D7AD9C87', 0, NULL, 1);

-- --------------------------------------------------------

-- 
-- Table structure for table 'user_bookmarks'
-- 

DROP TABLE IF EXISTS user_bookmarks;
CREATE TABLE IF NOT EXISTS user_bookmarks (
  bookmark_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  bookmark_url text,
  bookmark_title text,
  PRIMARY KEY  (bookmark_id),
  KEY idx_user_bookmark_user_id (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_bookmarks'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user_diary'
-- 

DROP TABLE IF EXISTS user_diary;
CREATE TABLE IF NOT EXISTS user_diary (
  id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  date_posted int(11) NOT NULL default '0',
  summary text,
  details text,
  PRIMARY KEY  (id),
  KEY idx_user_diary_user_date (user_id,date_posted),
  KEY idx_user_diary_date (date_posted),
  KEY idx_user_diary_user (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_diary'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user_diary_monitor'
-- 

DROP TABLE IF EXISTS user_diary_monitor;
CREATE TABLE IF NOT EXISTS user_diary_monitor (
  monitor_id int(11) NOT NULL auto_increment,
  user_monitored int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  PRIMARY KEY  (monitor_id),
  KEY idx_user_diary_monitor_user (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_diary_monitor'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user_group'
-- 

DROP TABLE IF EXISTS user_group;
CREATE TABLE IF NOT EXISTS user_group (
  user_group_id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  admin_flags char(16) NOT NULL default '',
  bug_flags int(11) NOT NULL default '0',
  forum_flags int(11) NOT NULL default '0',
  project_flags int(11) NOT NULL default '2',
  patch_flags int(11) NOT NULL default '1',
  support_flags int(11) NOT NULL default '1',
  doc_flags int(11) NOT NULL default '0',
  file_flags int(11) NOT NULL default '0',
  wiki_flags int(11) NOT NULL default '0',
  svn_flags int(11) NOT NULL default '0',
  news_flags int(11) NOT NULL default '0',
  PRIMARY KEY  (user_group_id),
  KEY idx_user_group_user_id (user_id),
  KEY idx_user_group_group_id (group_id),
  KEY bug_flags_idx (bug_flags),
  KEY forum_flags_idx (forum_flags),
  KEY project_flags_idx (project_flags),
  KEY admin_flags_idx (admin_flags)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_group'
-- 

INSERT INTO user_group (user_group_id, user_id, group_id, admin_flags, bug_flags, forum_flags, project_flags, patch_flags, support_flags, doc_flags, file_flags, wiki_flags, svn_flags, news_flags) VALUES (1, 101, 1, 'A', 2, 2, 2, 2, 2, 1, 2, 2, 2, 0),
(2, 102, 101, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(3, 102, 102, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(4, 102, 103, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(5, 102, 104, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(6, 102, 105, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(7, 102, 106, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(8, 102, 107, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(9, 102, 108, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(10, 102, 109, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(11, 101, 110, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 0, 0),
(12, 103, 111, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2),
(13, 104, 111, '', 0, 0, 2, 1, 1, 0, 0, 0, 0, 0),
(14, 106, 112, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2),
(15, 107, 112, '', 0, 0, 2, 1, 1, 0, 0, 0, 0, 0),
(16, 109, 113, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2),
(17, 110, 114, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2),
(18, 110, 115, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2),
(19, 110, 116, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2),
(20, 110, 117, 'A', 2, 2, 2, 2, 2, 2, 2, 2, 2, 2);

-- --------------------------------------------------------

-- 
-- Table structure for table 'user_metric0'
-- 

DROP TABLE IF EXISTS user_metric0;
CREATE TABLE IF NOT EXISTS user_metric0 (
  ranking int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  times_ranked int(11) NOT NULL default '0',
  avg_raters_importance float(10,8) NOT NULL default '0.00000000',
  avg_rating float(10,8) NOT NULL default '0.00000000',
  metric float(10,8) NOT NULL default '0.00000000',
  percentile float(10,8) NOT NULL default '0.00000000',
  importance_factor float(10,8) NOT NULL default '0.00000000',
  PRIMARY KEY  (ranking),
  KEY idx_user_metric0_user_id (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_metric0'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user_metric1'
-- 

DROP TABLE IF EXISTS user_metric1;
CREATE TABLE IF NOT EXISTS user_metric1 (
  ranking int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  times_ranked int(11) NOT NULL default '0',
  avg_raters_importance float(10,8) NOT NULL default '0.00000000',
  avg_rating float(10,8) NOT NULL default '0.00000000',
  metric float(10,8) NOT NULL default '0.00000000',
  percentile float(10,8) NOT NULL default '0.00000000',
  importance_factor float(10,8) NOT NULL default '0.00000000',
  PRIMARY KEY  (ranking)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_metric1'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user_metric_tmp1_1'
-- 

DROP TABLE IF EXISTS user_metric_tmp1_1;
CREATE TABLE IF NOT EXISTS user_metric_tmp1_1 (
  user_id int(11) NOT NULL default '0',
  times_ranked int(11) NOT NULL default '0',
  avg_raters_importance float(10,8) NOT NULL default '0.00000000',
  avg_rating float(10,8) NOT NULL default '0.00000000',
  metric float(10,8) NOT NULL default '0.00000000'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_metric_tmp1_1'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user_plugin'
-- 

DROP TABLE IF EXISTS user_plugin;
CREATE TABLE IF NOT EXISTS user_plugin (
  user_id int(11) NOT NULL default '0',
  plugin_id int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_plugin'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user_preferences'
-- 

DROP TABLE IF EXISTS user_preferences;
CREATE TABLE IF NOT EXISTS user_preferences (
  user_id int(11) NOT NULL default '0',
  preference_name varchar(255) NOT NULL default '',
  preference_value text,
  PRIMARY KEY  (user_id,preference_name)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_preferences'
-- 

INSERT INTO user_preferences (user_id, preference_name, preference_value) VALUES (101, 'my_artifacts_show', 'AS'),
(102, 'my_artifacts_show', 'AS'),
(102, 'my_hide_forum101', '0|3'),
(102, 'my_hide_forum109', '0|3'),
(102, 'svn_commits_browcust109', '|0|||15'),
(101, 'svn_commits_browcust109', '|100|||15'),
(101, 'user_csv_separator', 'comma'),
(103, 'my_artifacts_show', 'AS'),
(104, 'my_artifacts_show', 'AS'),
(105, 'my_artifacts_show', 'AS'),
(106, 'my_artifacts_show', 'AS'),
(109, 'my_artifacts_show', 'AS'),
(110, 'my_artifacts_show', 'AS');

-- --------------------------------------------------------

-- 
-- Table structure for table 'user_ratings'
-- 

DROP TABLE IF EXISTS user_ratings;
CREATE TABLE IF NOT EXISTS user_ratings (
  rated_by int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  rate_field int(11) NOT NULL default '0',
  rating int(11) NOT NULL default '0',
  KEY idx_user_ratings_rated_by (rated_by),
  KEY idx_user_ratings_user_id (user_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_ratings'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'user_trust_metric'
-- 

DROP TABLE IF EXISTS user_trust_metric;
CREATE TABLE IF NOT EXISTS user_trust_metric (
  ranking int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL default '0',
  times_ranked int(11) NOT NULL default '0',
  avg_raters_importance float(10,8) NOT NULL default '0.00000000',
  avg_rating float(10,8) NOT NULL default '0.00000000',
  metric float(10,8) NOT NULL default '0.00000000',
  percentile float(10,8) NOT NULL default '0.00000000',
  importance_factor float(10,8) NOT NULL default '0.00000000',
  PRIMARY KEY  (ranking)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'user_trust_metric'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_attachment'
-- 

DROP TABLE IF EXISTS wiki_attachment;
CREATE TABLE IF NOT EXISTS wiki_attachment (
  id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_attachment'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_attachment_log'
-- 

DROP TABLE IF EXISTS wiki_attachment_log;
CREATE TABLE IF NOT EXISTS wiki_attachment_log (
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  wiki_attachment_id int(11) NOT NULL default '0',
  wiki_attachment_revision_id int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  KEY all_idx (user_id,group_id),
  KEY time_idx (`time`),
  KEY group_id_idx (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_attachment_log'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_attachment_revision'
-- 

DROP TABLE IF EXISTS wiki_attachment_revision;
CREATE TABLE IF NOT EXISTS wiki_attachment_revision (
  id int(11) NOT NULL auto_increment,
  attachment_id int(11) NOT NULL default '0',
  user_id int(11) NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  revision int(11) NOT NULL default '0',
  mimetype varchar(255) NOT NULL default '',
  size int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_attachment_revision'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_group_list'
-- 

DROP TABLE IF EXISTS wiki_group_list;
CREATE TABLE IF NOT EXISTS wiki_group_list (
  id int(11) NOT NULL auto_increment,
  group_id int(11) NOT NULL default '0',
  wiki_name varchar(255) NOT NULL default '',
  wiki_link varchar(255) NOT NULL default '',
  description varchar(255) NOT NULL default '',
  rank int(11) NOT NULL default '0',
  language_id int(11) NOT NULL default '1',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_group_list'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_link'
-- 

DROP TABLE IF EXISTS wiki_link;
CREATE TABLE IF NOT EXISTS wiki_link (
  linkfrom int(11) NOT NULL default '0',
  linkto int(11) NOT NULL default '0',
  KEY linkfrom (linkfrom),
  KEY linkto (linkto)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_link'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_log'
-- 

DROP TABLE IF EXISTS wiki_log;
CREATE TABLE IF NOT EXISTS wiki_log (
  user_id int(11) NOT NULL default '0',
  group_id int(11) NOT NULL default '0',
  pagename varchar(255) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  KEY all_idx (user_id,group_id),
  KEY time_idx (`time`),
  KEY group_id_idx (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_log'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_nonempty'
-- 

DROP TABLE IF EXISTS wiki_nonempty;
CREATE TABLE IF NOT EXISTS wiki_nonempty (
  id int(11) NOT NULL default '0',
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_nonempty'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_page'
-- 

DROP TABLE IF EXISTS wiki_page;
CREATE TABLE IF NOT EXISTS wiki_page (
  id int(11) NOT NULL auto_increment,
  pagename varchar(100) character set latin1 collate latin1_bin NOT NULL default '',
  hits int(11) NOT NULL default '0',
  pagedata mediumtext NOT NULL,
  cached_html mediumblob,
  group_id int(11) NOT NULL default '0',
  PRIMARY KEY  (id),
  KEY group_id (group_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_page'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_recent'
-- 

DROP TABLE IF EXISTS wiki_recent;
CREATE TABLE IF NOT EXISTS wiki_recent (
  id int(11) NOT NULL default '0',
  latestversion int(11) default NULL,
  latestmajor int(11) default NULL,
  latestminor int(11) default NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_recent'
-- 


-- --------------------------------------------------------

-- 
-- Table structure for table 'wiki_version'
-- 

DROP TABLE IF EXISTS wiki_version;
CREATE TABLE IF NOT EXISTS wiki_version (
  id int(11) NOT NULL default '0',
  version int(11) NOT NULL default '0',
  mtime int(11) NOT NULL default '0',
  minor_edit tinyint(4) default '0',
  content mediumtext NOT NULL,
  versiondata mediumtext NOT NULL,
  PRIMARY KEY  (id,version),
  KEY mtime (mtime)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dumping data for table 'wiki_version'
-- 

