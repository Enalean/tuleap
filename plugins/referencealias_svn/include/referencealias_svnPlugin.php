<?php
/**
 * Copyright (c) Enalean SAS, 2016 - 2018. All Rights Reserved.
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
 */

require_once __DIR__ . '/../../svn/include/svnPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

use Tuleap\ReferenceAliasSVN\Dao;
use Tuleap\ReferenceAliasSVN\ReferencesBuilder;
use Tuleap\ReferenceAliasSVN\ReferencesImporter;
use Tuleap\SVN\AccessControl\AccessFileHistoryDao;
use Tuleap\SVN\AccessControl\AccessFileHistoryFactory;
use Tuleap\SVN\Repository\Destructor;
use Tuleap\SVN\Dao as SVNPluginDao;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\SvnAdmin;
use Tuleap\SVN\XMLRepositoryImporter;

class referencealias_svnPlugin extends Plugin //phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        $this->addHook(Event::IMPORT_COMPAT_REF_XML);
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::GET_PLUGINS_EXTRA_REFERENCES);
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('svn');
    }

    /**
     * @return Tuleap\ReferenceAliasSVN\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\ReferenceAliasSVN\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    /** @see Event::IMPORT_COMPAT_REF_XML */
    public function import_compat_ref_xml($params) //phpcs:ignore
    {
        if ($params['service_name'] === XMLRepositoryImporter::SERVICE_NAME) {
            $repository = $params['created_refs']['repository'];
            $logger     = new WrapperLogger($params['logger'], 'ReferenceAliasSVNImporter');
            $importer   = new ReferencesImporter($this->getCompatDao(), $logger);

            $importer->importCompatRefXML($params['configuration'], $params['project'], $params['xml_content'], $repository);
        }
    }

    /** @see Event::GET_REFERENCE */
    public function get_reference($params) //phpcs:ignore
    {
        $reference = $this->getReferencesBuilder()->getReference($params['keyword'], $params['value']);

        if (! empty($reference)) {
            $params['reference'] = $reference;
        }
    }

    /** @see Event::GET_PLUGINS_EXTRA_REFERENCES */
    public function get_plugins_extra_references($params) //phpcs:ignore
    {
        foreach ($this->getReferencesBuilder()->getExtraReferenceSpecs() as $refspec) {
            $params['refs'][] = $refspec;
        }
    }

    private function getReferencesBuilder()
    {
        $project_manager = ProjectManager::instance();

        return new ReferencesBuilder(
            $this->getCompatDao(),
            $project_manager,
            new RepositoryManager(
                $this->getSVNDao(),
                $project_manager,
                $this->getSvnAdmin(),
                $this->getLogger(),
                $this->getSystemCommand(),
                $this->getDestructor(),
                EventManager::instance(),
                Backend::instance(Backend::SVN),
                $this->getAccessFileHistoryFactory()
            )
        );
    }

    private function getAccessFileHistoryFactory()
    {
        return new AccessFileHistoryFactory(new AccessFileHistoryDao());
    }

    private function getSystemCommand()
    {
        return new System_Command();
    }

    private function getDestructor()
    {
        return new Destructor(
            $this->getSVNDao(),
            $this->getLogger()
        );
    }

    private function getLogger()
    {
        return SvnPlugin::getLogger();
    }

    private function getSvnAdmin()
    {
        return new SvnAdmin($this->getSystemCommand(), $this->getLogger(), Backend::instance(Backend::SVN));
    }

    private function getSVNDao()
    {
        return new SVNPluginDao();
    }

    private function getCompatDao()
    {
        return new Dao();
    }
}
