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

use Tuleap\Password\PasswordSanityChecker;

class CreateTestEnvironment
{
    private $output_dir;
    /**
     * @var \PFUser
     */
    private $user;
    /**
     * @var \Project
     */
    private $project;
    /**
     * @var Notifier
     */
    private $notifier;
    /**
     * @var PasswordSanityChecker
     */
    private $password_sanity_checker;

    public function __construct(Notifier $notifier, PasswordSanityChecker $password_sanity_checker, $output_dir)
    {
        $this->notifier                = $notifier;
        $this->password_sanity_checker = $password_sanity_checker;
        $this->output_dir              = $output_dir;
    }

    /**
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @param string $login
     * @param string $password
     * @param string $archive
     *
     * @throws Exception\EmailNotUniqueException
     * @throws Exception\InvalidLoginException
     * @throws Exception\InvalidPasswordException
     * @throws Exception\InvalidProjectFullNameException
     * @throws Exception\InvalidProjectUnixNameException
     * @throws Exception\InvalidRealNameException
     * @throws Exception\ProjectImportFailureException
     * @throws Exception\ProjectNotCreatedException
     * @throws Exception\UnableToCreateTemporaryDirectoryException
     * @throws Exception\UnableToWriteFileException
     */
    public function main($firstname, $lastname, $email, $login, $password, $archive)
    {
        if (! $this->password_sanity_checker->check($password)) {
            throw new Exception\InvalidPasswordException($this->password_sanity_checker->getErrors());
        }

        $archive_base_dir = $this->getArchiveBaseDir($archive);

        $create_test_user = new CreateTestUser($firstname, $lastname, $email, $login);
        $this->serializeXmlIntoFile($create_test_user->generateXML(), 'users.xml');

        $create_test_project = new CreateTestProject(
            $create_test_user->getUserName(),
            $archive_base_dir,
            new \Rule_ProjectName(),
            new \Rule_ProjectFullName()
        );
        $this->serializeXmlIntoFile($create_test_project->generateXML(), 'project.xml');

        $this->copyExtraFiles($archive_base_dir);

        $this->execImport();

        $user_manager = \UserManager::instance();
        $this->user   = $user_manager->getUserByUserName($create_test_user->getUserName());
        $this->user->setPassword($password);
        $this->user->setExpiryDate(strtotime('+3 week'));
        $user_manager->updateDb($this->user);

        $this->project = \ProjectManager::instance()->getProjectByUnixName($create_test_project->getProjectUnixName());
        if (! $this->project instanceof \Project || $this->project->isError()) {
            throw new Exception\ProjectNotCreatedException();
        }

        $base_url = \HTTPRequest::instance()->getServerUrl();
        $this->notifier->notify("New project created for {$this->user->getRealName()} ({$this->user->getEmail()}): $base_url/projects/{$this->project->getUnixNameLowerCase()}. #{$this->user->getUnixName()}");
    }

    private function getArchiveBaseDir($archive_dir_name)
    {
        $etc_base_dir = \ForgeConfig::get('sys_custompluginsroot') . '/' . \create_test_envPlugin::NAME . '/resources';
        $project_xml_path = $etc_base_dir . '/' . $archive_dir_name . '/project.xml';
        if (file_exists($project_xml_path)) {
            return $etc_base_dir . '/' . $archive_dir_name;
        }
        return __DIR__ . '/../../resources/sample-project';
    }

    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param $filename
     * @throws Exception\UnableToCreateTemporaryDirectoryException
     * @throws Exception\UnableToWriteFileException
     */
    private function serializeXmlIntoFile(\SimpleXMLElement $xml, $filename)
    {
        if (!is_dir($this->output_dir) && !mkdir($this->output_dir, 0770, true) && !is_dir($this->output_dir)) {
            throw new Exception\UnableToCreateTemporaryDirectoryException(sprintf('Directory "%s" was not created', $this->output_dir));
        }
        if ($xml->saveXML($this->output_dir . DIRECTORY_SEPARATOR . $filename) !== true) {
            throw new Exception\UnableToWriteFileException("Unable to write file " . $this->output_dir . DIRECTORY_SEPARATOR . $filename);
        }
    }

    private function copyExtraFiles($archive_base_dir)
    {
        $iterator = new \DirectoryIterator($archive_base_dir);
        foreach ($iterator as $file) {
            if ($file->isFile() && ! in_array($file->getBasename(), ['project.xml', 'users.xml'])) {
                copy($file->getPathname(), $this->output_dir . '/' . $file->getBasename());
            }
        }
    }

    /**
     * @throws Exception\ProjectImportFailureException
     */
    private function execImport()
    {
        try {
            $cmd = sprintf('sudo -u root /usr/share/tuleap/src/utils/tuleap import-project-xml -u admin --automap=no-email,create:A -i %s', escapeshellarg($this->output_dir));
            $exec = new \System_Command();
            $exec->exec($cmd);
        } catch (\System_Command_CommandException $exception) {
            throw new Exception\ProjectImportFailureException($exception->getMessage(), 0, $exception);
        }
    }
}
