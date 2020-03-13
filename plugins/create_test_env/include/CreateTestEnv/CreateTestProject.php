<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\CreateTestEnv;

use Rule_ProjectFullName;
use Rule_ProjectName;
use SimpleXMLElement;
use Tuleap\CreateTestEnv\XMLDateUpdater\DateUpdater;

class CreateTestProject
{
    /**
     * @var Rule_ProjectName
     */
    private $rule_project_name;
    /**
     * @var Rule_ProjectFullName
     */
    private $rule_project_full_name;

    private $user_name;
    private $full_name;
    private $unix_name;
    private $archive_base_dir;

    public function __construct(
        string $user_name,
        string $archive_base_dir,
        Rule_ProjectName $rule_project_name,
        Rule_ProjectFullName $rule_project_full_name
    ) {
        $this->user_name              = $user_name;
        $this->archive_base_dir       = $archive_base_dir;
        $this->rule_project_name      = $rule_project_name;
        $this->rule_project_full_name = $rule_project_full_name;
    }

    /**
     * @return SimpleXMLElement
     * @throws Exception\InvalidProjectFullNameException
     * @throws Exception\InvalidProjectUnixNameException
     */
    public function generateXML()
    {
        $xml = $this->getXMLBasedOnTemplate();

        if (file_exists($this->archive_base_dir . '/reference_date.txt')) {
            $updater = new DateUpdater(
                new \DateTimeImmutable(trim(file_get_contents($this->archive_base_dir . '/reference_date.txt'))),
                new \DateTimeImmutable()
            );

            $updater->updateDateValuesInXML($xml);
        }

        return $xml;
    }

    /**
     * @throws Exception\InvalidProjectFullNameException
     * @throws Exception\InvalidProjectUnixNameException
     */
    private function getXMLBasedOnTemplate(): SimpleXMLElement
    {
        $xml_str = str_replace(
            [
                '{{ project_unix_name }}',
                '{{ project_full_name }}',
                '{{ username }}',
                '{{ current_date }}',
            ],
            [
                htmlentities($this->getProjectUnixName(), ENT_XML1, 'UTF-8'),
                htmlentities($this->getProjectFullName(), ENT_XML1, 'UTF-8'),
                htmlentities($this->user_name, ENT_XML1, 'UTF-8'),
                htmlentities(date('c'), ENT_XML1, 'UTF-8'),
            ],
            file_get_contents($this->getProjectXMLFilePath())
        );
        return simplexml_load_string($xml_str);
    }

    public function getProjectXMLFilePath()
    {
        return $this->archive_base_dir . '/project.xml';
    }

    /**
     * @return string
     * @throws Exception\InvalidProjectFullNameException
     */
    public function getProjectFullName()
    {
        if ($this->full_name === null) {
            $full_name = $this->generateProjectFullName();
            if (! $this->rule_project_full_name->isValid($full_name)) {
                throw new Exception\InvalidProjectFullNameException($this->rule_project_full_name->getErrorMessage());
            }
            $this->full_name = $full_name;
        }
        return $this->full_name;
    }

    public function generateProjectFullName()
    {
        return substr('Test project for ' . $this->user_name, 0, 40);
    }

    /**
     * @return string
     * @throws Exception\InvalidProjectUnixNameException
     */
    public function getProjectUnixName()
    {
        if ($this->unix_name === null) {
            $unix_name = $this->generateProjectUnixName();
            if (! $this->rule_project_name->isValid($unix_name)) {
                throw new Exception\InvalidProjectUnixNameException($this->rule_project_name->getErrorMessage());
            }
            $this->unix_name = $unix_name;
        }
        return $this->unix_name;
    }

    public function generateProjectUnixName()
    {
        return 'test-for-' . strtr($this->user_name, '_.', '--');
    }
}
