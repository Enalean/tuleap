<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Dashboard\XML\XMLDashboard;
use Tuleap\Event\Dispatchable;

final class ScrumTemplateDashboardDefinition implements Dispatchable
{
    private ?XMLDashboard $enforced_unique_dashboard = null;

    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function enforceUniqueDashboard(XMLDashboard $enforced_unique_dashboard): void
    {
        $this->enforced_unique_dashboard = $enforced_unique_dashboard;
    }

    /**
     * @param non-empty-string $source_path
     * @param non-empty-string $target_path
     */
    public function overwriteProjectDashboards(string $source_path, string $target_path): void
    {
        $this->dispatcher->dispatch($this);

        if ($this->enforced_unique_dashboard === null) {
            \Psl\Filesystem\copy($source_path, $target_path);
            return;
        }

        $project_xml = simplexml_load_string(\Psl\File\read($source_path));
        if ($project_xml === false) {
            throw new \Exception("Failed to parse project XML string $source_path");
        }

        if ($project_xml->dashboards) {
            unset($project_xml->dashboards);
        }

        $dashboards = $project_xml->addChild('dashboards');
        if ($dashboards === null) {
            throw new \Exception('Failed to add dashboard to project XML');
        }
        $this->enforced_unique_dashboard->export($dashboards);

        $project_xml->asXML($target_path);
    }
}
