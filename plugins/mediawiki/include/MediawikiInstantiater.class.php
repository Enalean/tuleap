<?php
/*
 * Copyright (C) 2010  Olaf Lenz
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 *
 * This file is part of FusionForge.
 *
 * FusionForge is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * FusionForge is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */


class MediaWikiInstantiater
{

    public const MW_123_PATH = '/usr/share/mediawiki-tuleap-123';

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /** @var string */
    private $project_name;

    /** @var int */
    private $project_id;

    /** @var string */
    private $project_name_dir;

    /** @var Project */
    private $project;

    /** @var MediawikiDao */
    private $dao;

    /** @var MediawikiSiteAdminResourceRestrictor */
    private $resource_restrictor;

    /** @var MediawikiManager */
    private $mediawiki_manager;

    /** @var MediawikiLanguageManager */
    private $language_manager;

    /** @var MediawikiVersionManager */
    private $version_manager;

    /** @var Backend */
    private $backend;
    /**
     * @var MediawikiMLEBExtensionManager
     */
    private $mleb_manager;

    public function __construct(
        Project $project,
        MediawikiManager $mediawiki_manager,
        MediawikiLanguageManager $language_manager,
        MediawikiVersionManager $version_manager,
        MediawikiMLEBExtensionManager $mleb_manager
    ) {
        $this->logger              = BackendLogger::getDefaultLogger();
        $this->project             = $project;
        $this->project_name        = $project->getUnixName();
        $this->project_id          = $project->getID();
        $this->mediawiki_manager   = $mediawiki_manager;
        $this->dao                 = $mediawiki_manager->getDao();
        $this->language_manager    = $language_manager;
        $this->version_manager     = $version_manager;
        $this->mleb_manager        = $mleb_manager;
        $this->resource_restrictor = new MediawikiSiteAdminResourceRestrictor(
            new MediawikiSiteAdminResourceRestrictorDao(),
            ProjectManager::instance()
        );
        $this->backend = Backend::instance();
    }

    /**
     * Creates a mediawiki plugin instance for the project
     */
    public function instantiate()
    {
        if ($this->initMediawiki()) {
            $this->seedUGroupMapping();
        }
    }

    public function instantiateFromTemplate(array $ugroup_mapping)
    {
        if ($this->initMediawiki()) {
            $this->seedUGroupMappingFromTemplate($ugroup_mapping);
            $this->setReadWritePermissionsFromTemplate($ugroup_mapping);
            $this->setLanguageFromTemplate();
        }
    }

    private function setLanguageFromTemplate()
    {
        $template_project = ProjectManager::instance()->getProject($this->project->getTemplate());

        if (! $template_project) {
            return;
        }

        $this->language_manager->saveLanguageOption(
            $this->project,
            $this->language_manager->getUsedLanguageForProject($template_project)
        );
    }

    private function initMediawiki()
    {
        try {
            $exists = $this->checkForExistingProject();
        } catch (MediawikiInstantiaterException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        if ($exists) {
            $this->logger->info('Project dir ' . $this->project_name_dir . ' exists, so I assume the project already exists.');
            return false;
        } else {
            $this->createDirectory();
            $this->createDatabase(self::MW_123_PATH);
            $this->version_manager->saveVersionForProject($this->project, MediawikiVersionManager::MEDIAWIKI_123_VERSION);
            $this->mleb_manager->activateMLEBForProject($this->project);
            $this->resource_restrictor->allowProject($this->project);
            return true;
        }
    }

    /**
     * @return bool
     * @throws MediawikiInstantiaterException
     */
    private function checkForExistingProject()
    {
        $this->logger->info('Checking project dir for: ' . $this->project_name);

        $dir_exists = $this->doesDirectoryExist();
        $db_name    = $this->dao->findSchemaForExistingProject($this->project);

        if (! $dir_exists && ! $db_name) {
            return false;
        }

        if ($dir_exists && ! $db_name) {
            throw new MediawikiInstantiaterException('Project dir ' . $this->project_name_dir . ' exists, but database ' . $db_name . ' cannot be found');
        }

        if (! $dir_exists && $db_name) {
            throw new MediawikiInstantiaterException('Project dir ' . $this->project_name_dir . ' does not exist, but database ' . $db_name . ' found');
        }

        $this->ensureDatabaseIsCorrect($db_name);
        return true;
    }

    private function ensureDatabaseIsCorrect($db_name)
    {
        $this->dao->updateDatabaseName($this->project_id, $db_name);
    }

    /**
     * @return bool
     */
    private function doesDirectoryExist()
    {
        $data_dir = new \Tuleap\Mediawiki\MediawikiDataDir();
        $this->project_name_dir = $data_dir->getMediawikiDir($this->project);

        if (is_dir($this->project_name_dir)) {
            return true;
        }
        return false;
    }

    private function createDirectory()
    {
        $this->logger->info('Creating project dir ' . $this->project_name_dir);
        mkdir($this->project_name_dir, 0775, true);
        $owner = ForgeConfig::get('sys_http_user');
        $this->backend->chown($this->project_name_dir, $owner);
        $this->backend->chgrp($this->project_name_dir, $owner);
    }

    private function createDatabase($mediawiki_path)
    {
        $this->logger->info('Creating database ');
        try {
            $database = $this->dao->getDatabaseNameForCreation($this->project);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }

        $this->logger->info('Using database: ' . $database);
        $mediawiki_db_connection = \Tuleap\DB\DBFactory::getDBConnection($database);
        try {
            $this->logger->info('Updating mediawiki database.');
            $table_file   = $mediawiki_path . '/maintenance/tables.sql';
            if (! file_exists($table_file)) {
                throw new Exception('Error: Couldn\'t find Mediawiki Database Creation File ' . $table_file);
            }

            $mediawiki_db_connection->getDB()->beginTransaction();
            $this->dao->startTransaction();

            $this->logger->info('Creating tables from tables.sql');
            $table_prefix = $this->dao->getTablePrefixForCreation($this->project);
            $add_tables = $this->createTablesFromFile($mediawiki_db_connection->getDB(), $table_file, $table_prefix);
            if (! $add_tables) {
                throw new Exception('Error: Mediawiki Database Creation Failed');
            }

            $this->logger->info('Updating list of mediawiki databases (' . $database . ')');
            $update = $this->dao->addDatabase($database, $this->project_id);
            if (! $update) {
                throw new Exception('Error: Mediawiki Database list update failed: ' . db_error());
            }
        } catch (Exception $e) {
             $this->dao->rollBack();
             $mediawiki_db_connection->getDB()->rollBack();

            $this->logger->error($e->getMessage());
        }

        $this->dao->commit();
        $mediawiki_db_connection->getDB()->commit();
    }

    /**
     *  Query the database, from a file.
     *
     *  @param string $file File that contains the SQL statements.
     *  @param string $table_prefix Prefix for tables
     *  @return int result set handle.
     */
    private function createTablesFromFile(\ParagonIE\EasyDB\EasyDB $db, $file, $table_prefix)
    {
        // inspired from /usr/share/mediawiki115/includes/db/Database.php
        $fp = fopen($file, 'r');
        if (false === $fp) {
            $this->logger->error("createTablesFromFile: Cannot read file $file!");
            fclose($fp);
            return false;
        }

        $cmd = "";
        $done = false;
        $dollarquote = false;

        while (! feof($fp)) {
            $line = trim(fgets($fp, 1024));
            $sl = strlen($line) - 1;

            if ($sl < 0) {
                continue;
            }
            if ('-' == $line[0] && '-' == $line[1]) {
                continue;
            }

            // Allow dollar quoting for function declarations
            if (substr($line, 0, 4) == '$mw$') {
                if ($dollarquote) {
                    $dollarquote = false;
                    $done = true;
                } else {
                    $dollarquote = true;
                }
            } elseif (!$dollarquote) {
                if (';' == $line[$sl] && ($sl < 2 || ';' != $line[$sl - 1])) {
                    $done = true;
                    $line = substr($line, 0, $sl);
                }
            }

            if ('' != $cmd) {
                $cmd .= ' ';
            }
            $cmd .= "$line\n";

            if ($done) {
                $cmd = str_replace(';;', ";", $cmd);
                // next 2 lines are for mediawiki subst
                $cmd = preg_replace(":/\*_\*/:", $table_prefix, $cmd);
                // TOCHECK WITH CHRISTIAN: Do not change indexes for mediawiki (doesn't seems well supported)
                //$cmd = preg_replace(":/\*i\*/:","mw",$cmd );
                try {
                    $db->query($cmd);
                } catch (PDOException $ex) {
                    $this->logger->error('SQL: ' . preg_replace('/\n\t+/', ' ', $cmd));
                    throw $ex;
                }

                $cmd = '';
                $done = false;
            }
        }
        fclose($fp);
        return true;
    }

    private function seedUGroupMappingFromTemplate(array $ugroup_mapping)
    {
        $template         = ProjectManager::instance()->getProject($this->project->getTemplate());
        $mapper           = new MediawikiUserGroupsMapper($this->dao, new User_ForgeUserGroupPermissionsDao());
        $template_mapping = $mapper->getCurrentUserGroupMapping($template);
        $new_mapping      = array();
        foreach ($template_mapping as $mw_group => $tuleap_groups) {
            foreach ($tuleap_groups as $grp) {
                if ($grp < ProjectUGroup::DYNAMIC_UPPER_BOUNDARY) {
                    $new_mapping[$mw_group][] = $grp;
                } elseif (isset($ugroup_mapping[$grp])) {
                    $new_mapping[$mw_group][] = $ugroup_mapping[$grp];
                }
            }
        }
        db_query($this->seedProjectUGroupMappings($this->project->getID(), $new_mapping));
    }

    private function seedUGroupMapping()
    {
        if ($this->project->isPublic()) {
            db_query($this->seedProjectUGroupMappings($this->project->getID(), MediawikiUserGroupsMapper::$DEFAULT_MAPPING_PUBLIC_PROJECT));
        } else {
            db_query($this->seedProjectUGroupMappings($this->project->getID(), MediawikiUserGroupsMapper::$DEFAULT_MAPPING_PRIVATE_PROJECT));
        }
    }

    private function seedProjectUGroupMappings($group_id, array $mappings)
    {
        $query  = "INSERT INTO plugin_mediawiki_ugroup_mapping(group_id, ugroup_id, mw_group_name) VALUES ";

        return $query . implode(",", $this->getFormattedDefaultValues($group_id, $mappings));
    }

    private function getFormattedDefaultValues($group_id, array $mappings)
    {
        $values = array();

        foreach ($mappings as $group_name => $mapping) {
            foreach ($mapping as $ugroup_id) {
                $values[] = "($group_id, $ugroup_id, '$group_name')";
            }
        }

        return $values;
    }

    private function setReadWritePermissionsFromTemplate(array $ugroup_mapping)
    {
        $template                = ProjectManager::instance()->getProject($this->project->getTemplate());
        $template_read_accesses  = $this->mediawiki_manager->getReadAccessControl($template);
        $template_write_accesses = $this->mediawiki_manager->getWriteAccessControl($template);

        $this->mediawiki_manager->saveReadAccessControl($this->project, $this->getUgroupsForProjectFromMapping($template_read_accesses, $ugroup_mapping));
        $this->mediawiki_manager->saveWriteAccessControl($this->project, $this->getUgroupsForProjectFromMapping($template_write_accesses, $ugroup_mapping));
    }

    private function getUgroupsForProjectFromMapping(array $original_ugroups, array $ugroup_mapping)
    {
        $ugroups = array();

        foreach ($original_ugroups as $upgroup) {
            if (isset($ugroup_mapping[$upgroup])) {
                $ugroups[] = $ugroup_mapping[$upgroup];
                continue;
            }

            $ugroups[] = $upgroup;
        }

        return $ugroups;
    }
}
