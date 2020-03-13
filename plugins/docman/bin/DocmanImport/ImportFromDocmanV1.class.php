<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'XMLDocmanImport.class.php';

class Docman_ImportFromDocmanV1
{

    private $temporary_directory;
    private $wsdl_url;
    private $user_login;
    private $user_password;

    public function __construct($wsdl_url, $login, $password)
    {
        $this->temporary_directory = tempnam(ForgeConfig::get('tmp_dir'), 'docmanv1-docmanv2-');
        $this->wsdl_url            = $wsdl_url;
        $this->user_login          = $login;
        $this->user_password       = $password;
        $xml_security = new XML_Security();
        $xml_security->enableExternalLoadOfEntities();
    }

    public function migrate(Project $project)
    {
        $this->createTemporaryDirectory();
        $this->dumpDocmanV1($project);
        $folder_id = $this->createTarget($project);
        $this->importDump($project, $folder_id);
        $this->removeTemporaryDirectory();
    }

    private function dumpDocmanV1(Project $project)
    {
        $XMLExport = new DocmanV1_XMLExport(
            $project,
            $this->temporary_directory,
            $this->user_login
        );

        $XMLExport->dumpPackage();
    }

    private function createTarget(Project $project)
    {
        $client = new SoapClient($this->wsdl_url);

        // Establish connection to the server
        $session_hash = $client->login($this->user_login, $this->user_password)->session_hash;

        $root_folder_id = (int) $client->getRootFolder($session_hash, $project->getID());

        return (int) $client->createDocmanFolder(
            $session_hash,
            $project->getID(),
            $root_folder_id,
            'Docman v1 import',
            'Documents imported from legacy documentation system on ' . date('c', $_SERVER['REQUEST_TIME']),
            'begin',
            'none',
            array(
                array(
                    'type'      => DocmanV1_XMLExportData::V2_SOAP_PERM_NONE,
                    'ugroup_id' => ProjectUGroup::ANONYMOUS,
                ),
                array(
                    'type'      => DocmanV1_XMLExportData::V2_SOAP_PERM_NONE,
                    'ugroup_id' => ProjectUGroup::REGISTERED,
                ),
                array(
                    'type'      => DocmanV1_XMLExportData::V2_SOAP_PERM_NONE,
                    'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
                ),
                array(
                    'type'      => DocmanV1_XMLExportData::V2_SOAP_PERM_MANAGE,
                    'ugroup_id' => ProjectUGroup::PROJECT_ADMIN,
                ),
            ),
            array(),
            'admin',
            $_SERVER['REQUEST_TIME'],
            $_SERVER['REQUEST_TIME']
        );
    }

    private function importDump(Project $project, $folder_id)
    {
        $logger = new WrapperLogger(new Log_ConsoleLogger(), 'Import Docman');
        $xml_import = new XMLDocmanImport(
            'import:',
            $project->getUnixNameLowerCase(),
            $project->getID(),
            $this->wsdl_url,
            $this->user_login,
            $this->user_password,
            false,
            false,
            '',
            true,
            $logger
        );

        $xml_import->importPath($this->temporary_directory, $folder_id, '/' . DocmanV1_XMLExportData::ROOT_FOLDER_NAME);
    }

    private function createTemporaryDirectory()
    {
        unlink($this->temporary_directory);
        mkdir($this->temporary_directory, 0700);
    }

    private function removeTemporaryDirectory()
    {
        $system = Backend::instance(Backend::SYSTEM);
        $system->recurseDeleteInDir($this->temporary_directory);
        rmdir($this->temporary_directory);
    }
}
