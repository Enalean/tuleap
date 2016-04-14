<?php
/**
 * Copyright (c) Sogilis, 2016. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

require_once 'autoload.php';
require_once 'constants.php';

use Tuleap\TeamforgeCompatTracker\TeamforgeCompatDao;
use Tuleap\TeamforgeCompatTracker\ReferencesImporter;
use Tuleap\TeamforgeCompatTracker\TeamforgeReferencesBuilder;

class teamforge_compat_trackerPlugin extends Plugin
{

    /**
     * @var TeamforgeCompatDao
     */
    private $dao;

    /**
     * @var TeamforgeReferencesBuilder;
     */
    private $teamforge_references_builder;

    public function __construct($id)
    {
        parent::__construct($id);
        $this->dao = new TeamforgeCompatDao();
        $this->teamforge_references_builder = new TeamforgeReferencesBuilder($this->dao, ProjectManager::instance());
        $this->setScope(self::SCOPE_SYSTEM);
        $this->addHook(Event::IMPORT_COMPAT_REF_XML, 'importCompatRefXML');
        $this->addHook(Event::GET_REFERENCE, 'getReference');
        $this->addHook(Event::GET_PLUGINS_EXTRA_REFERENCES, 'registerExtraReferences');
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('tracker');
    }

    /**
     * @return Tuleap\TeamforgeCompatTracker\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new Tuleap\TeamforgeCompatTracker\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function process()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TEAMFORGE_COMPAT_TRACKER_BASE_DIR.'/template');
        $renderer->renderToPage('index', array());
    }

    public function importCompatRefXML($params)
    {
        $targeted_service_name = $params['service_name'];

        if ($targeted_service_name === 'tracker') {
            $xml          = $params['xml_content'];
            $project      = $params['project'];
            $logger       = new WrapperLogger($params['logger'], 'TeamforgeReferencesTrackerImporter');
            $created_refs = $params['created_refs'];
            $importer     = new ReferencesImporter($this->dao, $logger);
            $importer->importCompatRefXML($project, $xml, $created_refs);
            echo "Import compat ref xml tracker";
        }
    }

    public function getReference($params)
    {
        $keyword   = $params['keyword'];
        $value     = $params['value'];
        $reference = $this->teamforge_references_builder->getReference($keyword, $value);

        if (!empty($reference)) {
            $params['reference'] = $reference;
        }
    }

    public function registerExtraReferences($params)
    {
        foreach ($this->teamforge_references_builder->getExtraReferenceSpecs() as $refspec) {
            $params['refs'][] = $refspec;
        }
    }
}
