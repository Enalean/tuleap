<?php
/**
  * Copyright (c) Enalean, 2016. All rights reserved
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
namespace Tuleap\Svn\Repository;

use Project;
use ForgeConfig;

class Repository {

    private $id;
    private $name;
    private $project;

    public function __construct($id, $name, Project $project) {
        $this->id      = $id;
        $this->project = $project;
        $this->name    = $name;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getProject() {
        return $this->project;
    }

    public function getPublicPath() {
        return '/svnroot/'. $this->getFullName();
    }

    public function getFullName() {
      return $this->getProject()->getUnixNameMixedCase().'/'.$this->getName();
    }

    public function getSystemPath() {
        return ForgeConfig::get('sys_data_dir').'/svn_plugin/'. $this->getProject()->getId().'/'.$this->getName();
    }

    public function getSvnUrl() {
        $host = ForgeConfig::get('sys_default_domain');
        if (ForgeConfig::get('sys_force_ssl')) {
            $svn_url = 'https://'. $host;
        } else {
            $svn_url = 'http://'. $host;
        }
        // Domain name must be lowercase (issue with some SVN clients)
        $svn_url = strtolower($svn_url);
        $svn_url .= $this->getPublicPath();

        return $svn_url;
    }
}