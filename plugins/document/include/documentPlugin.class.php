<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\Document\Items\ItemDao;
use Tuleap\Document\REST\v1\ItemRepresentationBuilder;

require_once __DIR__ . '/../vendor/autoload.php';

class documentPlugin extends Plugin // phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-document', __DIR__.'/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REST_PROJECT_ADDITIONAL_INFORMATIONS);

        return parent::getHooksAndCallbacks();
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Document\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return ['docman'];
    }

    /**
     * @see Event::REST_PROJECT_ADDITIONAL_INFORMATIONS
     */
    public function rest_project_additional_informations($params) // phpcs:ignore
    {
        /**
         * @var $project Project
         */
        $project = $params['project'];
        if (! $this->isAllowed($project->getID())) {
            return;
        }

        $item_representation_builder = new ItemRepresentationBuilder(new ItemDao());
        $item_representation = $item_representation_builder->build($project);

        if (! $item_representation) {
            return;
        }

        $params['informations'][$this->getName()]['root_item'] = $item_representation;
    }
}
