<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

use Tuleap\Tracker\Report\Renderer\Table\GetExportOptionsMenuItemsEvent;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class document_generationPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-document_generation', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\DocumentGeneration\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker'];
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(GetExportOptionsMenuItemsEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function getExportOptionsMenuItems(GetExportOptionsMenuItemsEvent $event): void
    {
        $current_user = UserManager::instance()->getCurrentUser();
        if ($current_user->isAnonymous()) {
            return;
        }

        if (! $this->_getPluginManager()->isPluginAllowedForProject($this, $event->getReport()->getTracker()->getProject())) {
            return;
        }

        $event->addExportItem(
            '<li>' .
            '<a id="tracker-report-action-generate-document" href="#">' . dgettext('tuleap-document_generation', 'Generate document') . '</a>' .
            '</li>'
        );
    }
}
