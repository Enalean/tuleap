-- 
-- Database: `salome`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `ACTION_ATTACHEMENT`
-- 

CREATE TABLE `ACTION_ATTACHEMENT` (
  `ACTION_TEST_id_action` int(10) unsigned NOT NULL default '0',
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ACTION_TEST_id_action`,`ATTACHEMENT_id_attach`),
  KEY `ACTION_ATTACHEMENT_FKIndex1` (`ACTION_TEST_id_action`),
  KEY `ACTION_ATTACHEMENT_FKIndex2` (`ATTACHEMENT_id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `ACTION_PARAM_TEST`
-- 

CREATE TABLE `ACTION_PARAM_TEST` (
  `ACTION_TEST_id_action` int(10) unsigned NOT NULL default '0',
  `PARAM_TEST_id_param_test` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ACTION_TEST_id_action`,`PARAM_TEST_id_param_test`),
  KEY `ACTION_PARAM_TEST_FKIndex1` (`ACTION_TEST_id_action`),
  KEY `ACTION_PARAM_TEST_FKIndex2` (`PARAM_TEST_id_param_test`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `ACTION_TEST`
-- 

CREATE TABLE `ACTION_TEST` (
  `id_action` int(10) unsigned NOT NULL auto_increment,
  `CAS_TEST_id_cas` int(10) unsigned NOT NULL default '0',
  `nom_action` varchar(255) NOT NULL default '',
  `description_action` text,
  `res_attendu_action` text,
  `num_step_action` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_action`),
  KEY `ACTION_TEST_FKIndex1` (`CAS_TEST_id_cas`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `ATTACHEMENT`
-- 

CREATE TABLE `ATTACHEMENT` (
  `id_attach` int(10) unsigned NOT NULL auto_increment,
  `url_attach` tinytext,
  `nom_attach` text,
  `contenu_attach` longblob,
  `description_attach` text,
  `taille_attachement` bigint(20) default NULL,
  `date_attachement` date default '2003-05-07',
  PRIMARY KEY  (`id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `CAMPAGNE_ATTACHEMENT`
-- 

CREATE TABLE `CAMPAGNE_ATTACHEMENT` (
  `CAMPAGNE_TEST_id_camp` int(10) unsigned NOT NULL default '0',
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`CAMPAGNE_TEST_id_camp`,`ATTACHEMENT_id_attach`),
  KEY `CAMPAGNE_ATTACHEMENT_FKIndex1` (`CAMPAGNE_TEST_id_camp`),
  KEY `CAMPAGNE_ATTACHEMENT_FKIndex2` (`ATTACHEMENT_id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `CAMPAGNE_CAS`
-- 
-- Note:`assigned_user_id` is not present in original Salomé file
--

CREATE TABLE `CAMPAGNE_CAS` (
  `CAMPAGNE_TEST_id_camp` int(10) unsigned NOT NULL default '0',
  `CAS_TEST_id_cas` int(10) unsigned NOT NULL default '0',
  `ordre_cas_camp` int(10) unsigned default '0',
  `assigned_user_id` int(4) default NULL,
  PRIMARY KEY  (`CAMPAGNE_TEST_id_camp`,`CAS_TEST_id_cas`),
  KEY `CAMPAGNE_CAS_FKIndex1` (`CAMPAGNE_TEST_id_camp`),
  KEY `CAMPAGNE_CAS_FKIndex2` (`CAS_TEST_id_cas`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `CAMPAGNE_TEST`
-- 

CREATE TABLE `CAMPAGNE_TEST` (
  `id_camp` int(10) unsigned NOT NULL auto_increment,
  `PROJET_VOICE_TESTING_id_projet` int(10) unsigned NOT NULL default '0',
  `PERSONNE_id_personne` int(10) unsigned NOT NULL default '0',
  `nom_camp` varchar(255) NOT NULL default '',
  `date_creation_camp` date default NULL,
  `heure_creation_camp` time default NULL,
  `description_camp` text,
  `verrou_camp` tinyint(1) default NULL,
  `ordre_camp` int(10) unsigned default '0',
  PRIMARY KEY  (`id_camp`),
  KEY `CAMPAGNE_TEST_FKIndex1` (`PERSONNE_id_personne`),
  KEY `CAMPAGNE_TEST_FKIndex2` (`PROJET_VOICE_TESTING_id_projet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `CAS_ATTACHEMENT`
-- 

CREATE TABLE `CAS_ATTACHEMENT` (
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  `CAS_TEST_id_cas` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ATTACHEMENT_id_attach`,`CAS_TEST_id_cas`),
  KEY `CAS_ATTACHEMENT_FKIndex1` (`ATTACHEMENT_id_attach`),
  KEY `CAS_ATTACHEMENT_FKIndex2` (`CAS_TEST_id_cas`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `CAS_PARAM_TEST`
-- 

CREATE TABLE `CAS_PARAM_TEST` (
  `CAS_TEST_id_cas` int(10) unsigned NOT NULL default '0',
  `PARAM_TEST_id_param_test` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`CAS_TEST_id_cas`,`PARAM_TEST_id_param_test`),
  KEY `CAS_PARAM_TEST_FKIndex1` (`CAS_TEST_id_cas`),
  KEY `CAS_PARAM_TEST_FKIndex2` (`PARAM_TEST_id_param_test`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `CAS_TEST`
-- 

CREATE TABLE `CAS_TEST` (
  `id_cas` int(10) unsigned NOT NULL auto_increment,
  `PERSONNE_id_personne` int(10) unsigned NOT NULL default '0',
  `SUITE_TEST_id_suite` int(10) unsigned NOT NULL default '0',
  `SCRIPT_id_script` int(10) unsigned default NULL,
  `nom_cas` varchar(255) NOT NULL default '',
  `date_creation_cas` date default NULL,
  `heure_creation_cas` time default NULL,
  `type_cas` varchar(10) default 'MANUAL',
  `description_cas` text,
  `verrou_cas` tinyint(1) default NULL,
  `ordre_cas` int(10) unsigned default NULL,
  `plug_ext` varchar(255) default NULL,
  PRIMARY KEY  (`id_cas`),
  KEY `CAS_TEST_FKIndex1` (`SCRIPT_id_script`),
  KEY `CAS_TEST_FKIndex2` (`PERSONNE_id_personne`),
  KEY `CAS_TEST_FKIndex3` (`SUITE_TEST_id_suite`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `CONFIG`
-- 

CREATE TABLE `CONFIG` (
  `CLE` varchar(20) NOT NULL default '',
  `id_projet` int(10) unsigned zerofill NOT NULL default '0000000000',
  `id_personne` int(10) unsigned zerofill NOT NULL default '0000000000',
  `VALEUR` text NOT NULL,
  PRIMARY KEY  (`CLE`,`id_projet`,`id_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `ENVIRONNEMENT`
-- 

CREATE TABLE `ENVIRONNEMENT` (
  `id_env` int(10) unsigned NOT NULL auto_increment,
  `PROJET_VOICE_TESTING_id_projet` int(10) unsigned NOT NULL default '0',
  `nom_env` varchar(255) NOT NULL default '',
  `description_env` text,
  PRIMARY KEY  (`id_env`),
  KEY `ENVIRONNEMENT_FKIndex1` (`PROJET_VOICE_TESTING_id_projet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `ENV_ATTACHEMENT`
-- 

CREATE TABLE `ENV_ATTACHEMENT` (
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  `ENVIRONNEMENT_id_env` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ATTACHEMENT_id_attach`,`ENVIRONNEMENT_id_env`),
  KEY `ENV_ATTACHEMENT_FKIndex1` (`ATTACHEMENT_id_attach`),
  KEY `ENV_ATTACHEMENT_FKIndex2` (`ENVIRONNEMENT_id_env`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `EXEC_ACTION`
-- 

CREATE TABLE `EXEC_ACTION` (
  `id_exec_action` int(10) unsigned NOT NULL auto_increment,
  `EXEC_CAS_id_exec_cas` int(10) unsigned NOT NULL default '0',
  `ACTION_TEST_id_action` int(10) unsigned NOT NULL default '0',
  `res_exec_action` varchar(12) default NULL,
  `effectiv_res_action` text,
  `ACTION_TEST_description_action` text,
  `ACTION_TEST_res_attendu_action` text,
  PRIMARY KEY  (`id_exec_action`),
  KEY `EXEC_ACTION_FKIndex1` (`ACTION_TEST_id_action`),
  KEY `EXEC_ACTION_FKIndex2` (`EXEC_CAS_id_exec_cas`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `EXEC_ACTION_ATTACH`
-- 

CREATE TABLE `EXEC_ACTION_ATTACH` (
  `EXEC_ACTION_id_exec_action` int(10) unsigned NOT NULL default '0',
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`EXEC_ACTION_id_exec_action`,`ATTACHEMENT_id_attach`),
  KEY `EXEC_ACTION_ATTACH_FKIndex1` (`EXEC_ACTION_id_exec_action`),
  KEY `EXEC_ACTION_ATTACH_FKIndex2` (`ATTACHEMENT_id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `EXEC_CAMP`
-- 

CREATE TABLE `EXEC_CAMP` (
  `id_exec_camp` int(10) unsigned NOT NULL auto_increment,
  `ENVIRONNEMENT_id_env` int(10) unsigned NOT NULL default '0',
  `JEU_DONNEES_id_jeu_donnees` int(10) unsigned NOT NULL default '0',
  `PERSONNE_id_personne` int(10) unsigned NOT NULL default '0',
  `CAMPAGNE_TEST_id_camp` int(10) unsigned NOT NULL default '0',
  `nom_exec_camp` varchar(255) NOT NULL default '',
  `date_exec_camp` date default NULL,
  `heure_exec_camp` time default NULL,
  `desc_exec_camp` text,
  `last_exec_date` date default '2003-05-07',
  PRIMARY KEY  (`id_exec_camp`),
  KEY `EXEC_CAMP_FKIndex1` (`CAMPAGNE_TEST_id_camp`),
  KEY `EXEC_CAMP_FKIndex2` (`ENVIRONNEMENT_id_env`),
  KEY `EXEC_CAMP_FKIndex3` (`PERSONNE_id_personne`),
  KEY `EXEC_CAMP_FKIndex4` (`JEU_DONNEES_id_jeu_donnees`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `EXEC_CAMP_ATTACH`
-- 

CREATE TABLE `EXEC_CAMP_ATTACH` (
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  `EXEC_CAMP_id_exec_camp` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ATTACHEMENT_id_attach`,`EXEC_CAMP_id_exec_camp`),
  KEY `EXEC_CAMP_ATTACH_FKIndex1` (`ATTACHEMENT_id_attach`),
  KEY `EXEC_CAMP_ATTACH_FKIndex2` (`EXEC_CAMP_id_exec_camp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `EXEC_CAS`
-- 

CREATE TABLE `EXEC_CAS` (
  `id_exec_cas` int(10) unsigned NOT NULL auto_increment,
  `RES_EXEC_CAMP_id_res_exec_camp` int(10) unsigned NOT NULL default '0',
  `CAS_TEST_id_cas` int(10) unsigned NOT NULL default '0',
  `res_exec_cas` varchar(12) default NULL,
  `ordre_exec_cas` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_exec_cas`),
  KEY `EXEC_CAS_FKIndex1` (`CAS_TEST_id_cas`),
  KEY `EXEC_CAS_FKIndex2` (`RES_EXEC_CAMP_id_res_exec_camp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `EXEC_CAS_ATTACH`
-- 

CREATE TABLE `EXEC_CAS_ATTACH` (
  `EXEC_CAS_id_exec_cas` int(10) unsigned NOT NULL default '0',
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`EXEC_CAS_id_exec_cas`,`ATTACHEMENT_id_attach`),
  KEY `EXEC_CAS_ATTACH_FKIndex1` (`EXEC_CAS_id_exec_cas`),
  KEY `EXEC_CAS_ATTACH_FKIndex2` (`ATTACHEMENT_id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `FAMILLE_TEST`
-- 

CREATE TABLE `FAMILLE_TEST` (
  `id_famille` int(10) unsigned NOT NULL auto_increment,
  `PROJET_VOICE_TESTING_id_projet` int(10) unsigned NOT NULL default '0',
  `nom_famille` varchar(255) NOT NULL default '',
  `description_famille` text,
  `ordre_famille` int(10) unsigned default NULL,
  PRIMARY KEY  (`id_famille`),
  KEY `FAMILLE_TEST_FKIndex1` (`PROJET_VOICE_TESTING_id_projet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `FAMILLE_TEST_ATTACHEMENT`
-- 

CREATE TABLE `FAMILLE_TEST_ATTACHEMENT` (
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  `FAMILLE_TEST_id_famille` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ATTACHEMENT_id_attach`,`FAMILLE_TEST_id_famille`),
  KEY `ATTACHEMENT_has_FAMILLE_TEST_FKIndex1` (`ATTACHEMENT_id_attach`),
  KEY `ATTACHEMENT_has_FAMILLE_TEST_FKIndex2` (`FAMILLE_TEST_id_famille`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `GROUPE`
-- 

CREATE TABLE `GROUPE` (
  `id_groupe` int(10) unsigned NOT NULL auto_increment,
  `PROJET_VOICE_TESTING_id_projet` int(10) unsigned NOT NULL default '0',
  `nom_groupe` varchar(40) default NULL,
  `desc_groupe` text,
  `permission` int(10) unsigned zerofill default NULL,
  PRIMARY KEY  (`id_groupe`),
  KEY `GROUPE_FKIndex1` (`PROJET_VOICE_TESTING_id_projet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `JEU_DONNEES`
-- 

CREATE TABLE `JEU_DONNEES` (
  `id_jeu_donnees` int(10) unsigned NOT NULL auto_increment,
  `CAMPAGNE_TEST_id_camp` int(10) unsigned default NULL,
  `nom_jeu_donnees` varchar(255) NOT NULL default '',
  `desc_jeu_donnees` text,
  PRIMARY KEY  (`id_jeu_donnees`),
  KEY `JEU_DONNEES_FKIndex2` (`CAMPAGNE_TEST_id_camp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `LAST_DB_ACTION`
-- 

CREATE TABLE `LAST_DB_ACTION` (
  `pid` int(10) unsigned NOT NULL default '0',
  `action_type` int(10) unsigned NOT NULL default '0',
  `action_date` date NOT NULL default '2003-05-07',
  `action_time` time NOT NULL default '10:00:00',
  `projet` tinytext NOT NULL,
  PRIMARY KEY  (`pid`,`action_type`,`action_date`,`action_time`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `PARAM_TEST`
-- 

CREATE TABLE `PARAM_TEST` (
  `id_param_test` int(10) unsigned NOT NULL auto_increment,
  `PROJET_VOICE_TESTING_id_projet` int(10) unsigned NOT NULL default '0',
  `nom_param_test` varchar(255) NOT NULL default '',
  `desc_param_test` text,
  PRIMARY KEY  (`id_param_test`),
  KEY `PARAM_TEST_FKIndex1` (`PROJET_VOICE_TESTING_id_projet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `PERSONNE`
-- 

CREATE TABLE `PERSONNE` (
  `id_personne` int(10) unsigned NOT NULL auto_increment,
  `login_personne` varchar(40) NOT NULL default 'ERROR',
  `nom_personne` varchar(40) default NULL,
  `prenom_personne` varchar(40) default NULL,
  `desc_personne` text,
  `email_personne` text,
  `tel_personne` varchar(20) default NULL,
  `date_creation_personne` date default '2003-05-07',
  `heure_creation_personne` time default '01:00:00',
  `mot_de_passe` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `PERSONNE_GROUPE`
-- 

CREATE TABLE `PERSONNE_GROUPE` (
  `PERSONNE_id_personne` int(10) unsigned NOT NULL default '0',
  `GROUPE_id_groupe` int(10) unsigned NOT NULL default '0',
  `description` text,
  PRIMARY KEY  (`PERSONNE_id_personne`,`GROUPE_id_groupe`),
  KEY `PERSONNE_GROUPE_FKIndex1` (`PERSONNE_id_personne`),
  KEY `PERSONNE_GROUPE_FKIndex2` (`GROUPE_id_groupe`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `PROJET_VOICE_TESTING`
-- 

CREATE TABLE `PROJET_VOICE_TESTING` (
  `id_projet` int(10) unsigned NOT NULL auto_increment,
  `nom_projet` varchar(255) default NULL,
  `description_projet` text,
  `date_creation_projet` date default NULL,
  `verrou_projet` tinyint(1) default NULL,
  PRIMARY KEY  (`id_projet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `PROJET_VOICE_TESTING_ATTACHEMENT`
-- 

CREATE TABLE `PROJET_VOICE_TESTING_ATTACHEMENT` (
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  `PROJET_VOICE_TESTING_id_projet` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ATTACHEMENT_id_attach`,`PROJET_VOICE_TESTING_id_projet`),
  KEY `ATTACHEMENT_has_PROJET_VOICE_TESTING_FKIndex1` (`ATTACHEMENT_id_attach`),
  KEY `ATTACHEMENT_has_PROJET_VOICE_TESTING_FKIndex2` (`PROJET_VOICE_TESTING_id_projet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `REQUIREMENTS`
-- 

CREATE TABLE `REQUIREMENTS` (
  `id_req` int(10) unsigned NOT NULL auto_increment,
  `id_project` int(10) unsigned NOT NULL default '0',
  `req_name` varchar(255) NOT NULL default '',
  `req_description` text,
  `id_req_parent` int(10) unsigned NOT NULL default '0',
  `req_type` int(1) unsigned NOT NULL default '0',
  `priority` int(4) NOT NULL default '100',
  `version` varchar(50) NOT NULL default '1',
  `cat` int(1) NOT NULL default '0',
  `complexe` int(4) NOT NULL default '10',
  `origine` varchar(255) NOT NULL default 'Marketing',
  `state` int(1) NOT NULL default '0',
  `verif` varchar(50) default NULL,
  `reference` varchar(50) default NULL,
  PRIMARY KEY  (`id_req`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `REQ_ACTION_LINK`
-- 

CREATE TABLE `REQ_ACTION_LINK` (
  `REQUIREMENTS_id_req` int(10) unsigned NOT NULL default '0',
  `ACTION_id_action` int(10) unsigned NOT NULL default '0',
  `temp` int(1) unsigned NOT NULL default '0',
  PRIMARY KEY  (`REQUIREMENTS_id_req`,`ACTION_id_action`),
  KEY `REQ_ACTION_LINK_FKIndex1` (`REQUIREMENTS_id_req`),
  KEY `REQ_ACTION_LINK_FKIndex2` (`ACTION_id_action`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `REQ_ATTACHEMENT`
-- 

CREATE TABLE `REQ_ATTACHEMENT` (
  `REQUIREMENTS_id_req` int(10) unsigned NOT NULL default '0',
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`REQUIREMENTS_id_req`,`ATTACHEMENT_id_attach`),
  KEY `REQ_ATTACHEMENT_FKIndex1` (`REQUIREMENTS_id_req`),
  KEY `REQ_ATTACHEMENT_FKIndex2` (`ATTACHEMENT_id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `REQ_HISTORY`
-- 

CREATE TABLE `REQ_HISTORY` (
  `id_hist` int(10) unsigned NOT NULL auto_increment,
  `REQUIREMENTS_id_req` int(10) unsigned NOT NULL default '0',
  `PERSONNE_id_personne` int(10) unsigned NOT NULL default '0',
  `date` date default '2003-05-07',
  `code` int(4) NOT NULL default '0',
  `valeur` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id_hist`,`REQUIREMENTS_id_req`),
  KEY `REQ_ATTACHEMENT_FKIndex1` (`id_hist`),
  KEY `REQ_ATTACHEMENT_FKIndex2` (`REQUIREMENTS_id_req`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `REQ_REFERENCE`
-- 

CREATE TABLE `REQ_REFERENCE` (
  `REQUIREMENTS_id_req_source` int(10) unsigned NOT NULL default '0',
  `REQUIREMENTS_id_req_target` int(10) unsigned NOT NULL default '0',
  `code` int(4) NOT NULL default '0',
  PRIMARY KEY  (`REQUIREMENTS_id_req_source`,`REQUIREMENTS_id_req_target`,`code`),
  KEY `REQ_ATTACHEMENT_FKIndex1` (`REQUIREMENTS_id_req_source`),
  KEY `REQ_ATTACHEMENT_FKIndex2` (`REQUIREMENTS_id_req_target`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `REQ_TEST_LINK`
-- 

CREATE TABLE `REQ_TEST_LINK` (
  `REQUIREMENTS_id_req` int(10) unsigned NOT NULL default '0',
  `CAS_TEST_id_cas` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`REQUIREMENTS_id_req`,`CAS_TEST_id_cas`),
  KEY `REQ_TEST_LINK_FKIndex1` (`REQUIREMENTS_id_req`),
  KEY `REQ_TEST_LINK_FKIndex2` (`CAS_TEST_id_cas`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `RES_EXEC_ATTACH`
-- 

CREATE TABLE `RES_EXEC_ATTACH` (
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  `RES_EXEC_CAMP_id_res_exec_camp` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`ATTACHEMENT_id_attach`,`RES_EXEC_CAMP_id_res_exec_camp`),
  KEY `RES_EXEC_ATTACH_FKIndex1` (`RES_EXEC_CAMP_id_res_exec_camp`),
  KEY `RES_EXEC_ATTACH_FKIndex2` (`ATTACHEMENT_id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `RES_EXEC_CAMP`
-- 

CREATE TABLE `RES_EXEC_CAMP` (
  `id_res_exec_camp` int(10) unsigned NOT NULL auto_increment,
  `PERSONNE_id_personne` int(10) unsigned NOT NULL default '0',
  `EXEC_CAMP_id_exec_camp` int(10) unsigned NOT NULL default '0',
  `nom_res_exec_camp` varchar(255) default NULL,
  `desc_res_exec_camp` text,
  `date_res_exec_camp` date default '2003-05-07',
  `heure_res_exec_camp` time default '10:00:00',
  `statut_res_exec_camp` varchar(12) default NULL,
  `resultat_res_exec_camp` varchar(12) default NULL,
  PRIMARY KEY  (`id_res_exec_camp`),
  KEY `RES_EXEC_CAMP_FKIndex1` (`EXEC_CAMP_id_exec_camp`),
  KEY `RES_EXEC_CAMP_FKIndex2` (`PERSONNE_id_personne`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `SALOME_LOCK`
-- 

CREATE TABLE `SALOME_LOCK` (
  `id_projet` int(11) NOT NULL default '0',
  `id_personne` int(11) NOT NULL default '0',
  `pid` int(11) NOT NULL default '0',
  `lock_code` int(11) NOT NULL default '0',
  `action_code` int(11) NOT NULL default '0',
  `info` varchar(255) default NULL,
  `lock_date` date default '2003-05-07',
  PRIMARY KEY  (`pid`),
  KEY `id_projet` (`id_projet`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `SCRIPT`
-- 

CREATE TABLE `SCRIPT` (
  `id_script` int(10) unsigned NOT NULL auto_increment,
  `ENVIRONNEMENT_id_env` int(10) unsigned NOT NULL default '0',
  `EXEC_CAMP_id_exec_camp` int(10) unsigned NOT NULL default '0',
  `url_script` tinytext,
  `classe_autom_script` tinytext,
  `classpath_script` tinytext,
  `type_script` varchar(12) NOT NULL default '',
  PRIMARY KEY  (`id_script`),
  KEY `SCRIPT_TEST_FKIndex1` (`ENVIRONNEMENT_id_env`),
  KEY `SCRIPT_TEST_FKIndex2` (`EXEC_CAMP_id_exec_camp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `SCRIPT_ATTACHEMENT`
-- 

CREATE TABLE `SCRIPT_ATTACHEMENT` (
  `SCRIPT_id_script` int(10) unsigned NOT NULL default '0',
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`SCRIPT_id_script`,`ATTACHEMENT_id_attach`),
  KEY `SCRIPT_ATTACHEMENT_FKIndex1` (`SCRIPT_id_script`),
  KEY `SCRIPT_ATTACHEMENT_FKIndex2` (`ATTACHEMENT_id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `SESSION`
-- 

CREATE TABLE `SESSION` (
  `id_connection` int(10) unsigned NOT NULL auto_increment,
  `project_connec` varchar(255) NOT NULL default '',
  `login_connec` varchar(40) NOT NULL default '',
  `host_connec` varchar(255) NOT NULL default '',
  `date_connec` date NOT NULL default '2003-05-07',
  `hour_connec` time NOT NULL default '10:00:00',
  PRIMARY KEY  (`id_connection`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `SUITE_ATTACHEMENT`
-- 

CREATE TABLE `SUITE_ATTACHEMENT` (
  `SUITE_TEST_id_suite` int(10) unsigned NOT NULL default '0',
  `ATTACHEMENT_id_attach` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`SUITE_TEST_id_suite`,`ATTACHEMENT_id_attach`),
  KEY `SUITE_ATTACHEMENT_FKIndex1` (`SUITE_TEST_id_suite`),
  KEY `SUITE_ATTACHEMENT_FKIndex2` (`ATTACHEMENT_id_attach`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `SUITE_TEST`
-- 

CREATE TABLE `SUITE_TEST` (
  `id_suite` int(10) unsigned NOT NULL auto_increment,
  `FAMILLE_TEST_id_famille` int(10) unsigned NOT NULL default '0',
  `nom_suite` varchar(255) NOT NULL default '',
  `description_suite` text,
  `verrou_suite` tinyint(1) default NULL,
  `ordre_suite` int(10) unsigned default NULL,
  PRIMARY KEY  (`id_suite`),
  KEY `SUITE_TEST_FKIndex2` (`FAMILLE_TEST_id_famille`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `VALEUR_PARAM`
-- 
-- was: `ENVIRONNEMENT_id_env` int(10) unsigned default NULL,
-- why is it different?

CREATE TABLE `VALEUR_PARAM` (
  `id_valeur` int(10) unsigned NOT NULL auto_increment,
  `ENVIRONNEMENT_id_env` int(10) unsigned NOT NULL default '0',
  `JEU_DONNEES_id_jeu_donnees` int(10) unsigned default NULL,
  `PARAM_TEST_id_param_test` int(10) unsigned NOT NULL default '0',
  `valeur` tinytext,
  `desc_valeur` text,
  PRIMARY KEY  (`id_valeur`),
  KEY `VALEUR_PARAM_FKIndex1` (`PARAM_TEST_id_param_test`),
  KEY `VALEUR_PARAM_FKIndex2` (`JEU_DONNEES_id_jeu_donnees`),
  KEY `VALEUR_PARAM_FKIndex3` (`ENVIRONNEMENT_id_env`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
