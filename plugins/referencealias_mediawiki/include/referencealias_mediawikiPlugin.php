<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

require_once __DIR__ . '/../../mediawiki/include/mediawikiPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

use Tuleap\ReferenceAliasMediawiki\ReferencesBuilder;
use Tuleap\ReferenceAliasMediawiki\CompatibilityDao;
use Tuleap\ReferenceAliasMediawiki\ReferencesImporter;

class referencealias_mediawikiPlugin extends Plugin //phpcs:ignore
{

    public function __construct($id)
    {
        parent::__construct($id);

        $this->setScope(self::SCOPE_SYSTEM);
        $this->addHook(Event::IMPORT_COMPAT_REF_XML);
        $this->addHook(Event::GET_REFERENCE);
        $this->addHook(Event::GET_PLUGINS_EXTRA_REFERENCES);

        $this->dao = new CompatibilityDao();
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return ['mediawiki'];
    }

    /**
     * @return Tuleap\ReferenceAliasMediawiki\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\ReferenceAliasMediawiki\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function import_compat_ref_xml($params) //phpcs:ignore
    {
        $targeted_service_name = $params['service_name'];

        if ($targeted_service_name === 'mediawiki') {
            $xml          = $params['xml_content'];
            $project      = $params['project'];
            $logger       = new WrapperLogger($params['logger'], 'ReferenceAliasMediawikiImporter');
            $created_refs = $params['created_refs'];
            $importer     = new ReferencesImporter($this->dao, $logger);
            $importer->importCompatRefXML($params['configuration'], $project, $xml, $created_refs);
        }
    }

    public function get_plugins_extra_references($params) //phpcs:ignore
    {
        foreach ($this->getReferencesBuilder()->getExtraReferenceSpecs() as $refspec) {
            $params['refs'][] = $refspec;
        }
    }

    public function get_reference($params) //phpcs:ignore
    {
        $reference = $this->getReferencesBuilder()->getReference($params['project'], $params['keyword'], $params['value']);
        if (! empty($reference)) {
            $params['reference'] = $reference;
        }
    }

    private function getReferencesBuilder()
    {
        return new ReferencesBuilder($this->dao, ProjectManager::instance());
    }
}
