<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\SVN\Repository;

use ForgeConfig;
use HTTPRequest;
use Project;

class Repository
{

    private $id;
    private $name;
    private $project;
    private $backup_path;
    private $deletion_date;

    public function __construct($id, $name, $backup_path, $deletion_date, Project $project)
    {
        $this->id            = $id;
        $this->project       = $project;
        $this->name          = $name;
        $this->deletion_date = $deletion_date;
        $this->backup_path   = $backup_path;
    }

    public function getSettingUrl()
    {
        return SVN_BASE_URL . '/?' . http_build_query(
            array(
                'group_id' => $this->project->getID(),
                'action'   => 'settings',
                'repo_id'  => $this->id
            )
        );
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getProject()
    {
        return $this->project;
    }

    public function getPublicPath()
    {
        return '/svnplugin/' . $this->getFullName();
    }

    public function getFullName()
    {
        return $this->getProject()->getUnixNameMixedCase() . '/' . $this->getName();
    }

    public function getSystemName()
    {
        return $this->getProject()->getId() . '/' . $this->getName();
    }

    public function getSystemPath()
    {
        return ForgeConfig::get('sys_data_dir') . '/svn_plugin/' . $this->getProject()->getId() . '/' . $this->getName();
    }

    public function isRepositoryCreated()
    {
        return is_dir($this->getSystemPath());
    }

    public function getSvnUrl()
    {
        return $this->getSvnDomain() . $this->getPublicPath();
    }

    public function getSvnDomain()
    {
        // Domain name must be lowercase (issue with some SVN clients)
        return strtolower(HTTPRequest::instance()->getServerUrl());
    }

    public function canBeDeleted()
    {
        return $this->isRepositoryCreated();
    }

    public function getBackupPath()
    {
        return $this->backup_path;
    }

    public function getSystemBackupPath()
    {
        return ForgeConfig::get('sys_project_backup_path') . '/svn';
    }

    public function getBackupFileName()
    {
        return $this->getName() . $this->getDeletionDate() . '.svn';
    }

    public function getDeletionDateHumanReadable()
    {
        return date($GLOBALS['Language']->getText('system', 'datefmt'), $this->deletion_date);
    }

    public function getDeletionDate()
    {
        return $this->deletion_date;
    }

    public function setDeletionDate($deletion_date)
    {
        $this->deletion_date = $deletion_date;
    }

    public function isDeleted()
    {
        return !empty($this->deletion_date);
    }
}
