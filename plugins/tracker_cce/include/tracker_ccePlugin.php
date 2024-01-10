<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationTaskCollectorEvent;
use Tuleap\TrackerCCE\CustomCodeExecutionTask;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Tracker\Workflow\WorkflowMenuItem;
use Tuleap\Tracker\Workflow\WorkflowMenuItemCollection;
use Tuleap\TrackerCCE\Administration\AdministrationController;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../tracker/vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class tracker_ccePlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-tracker_cce', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): Tuleap\TrackerCCE\Plugin\PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new Tuleap\TrackerCCE\Plugin\PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker'];
    }

    #[ListeningToEventClass]
    public function collectPostCreationTask(PostCreationTaskCollectorEvent $event): void
    {
        $event->addAsyncTask(new CustomCodeExecutionTask($event->getLogger()));
    }

    #[ListeningToEventClass]
    public function workflowMenuItemCollection(WorkflowMenuItemCollection $collection): void
    {
        $collection->addItem(
            new WorkflowMenuItem(
                '/tracker_cce/' . urlencode((string) $collection->tracker->getId()) . '/admin',
                dgettext('tuleap-tracker_cce', 'Custom code execution'),
                'tracker-cce',
            ),
        );
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->get('/tracker_cce/{id:\d+}/admin', $this->getRouteHandler('routeTrackerAdministration'));
    }

    public function routeTrackerAdministration(): DispatchableWithRequest
    {
        return new AdministrationController(TrackerFactory::instance(), new TrackerManager(), TemplateRendererFactory::build());
    }
}
