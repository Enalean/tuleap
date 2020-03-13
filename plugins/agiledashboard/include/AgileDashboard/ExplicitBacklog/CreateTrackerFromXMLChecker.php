<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\ExplicitBacklog;

use Project;
use SimpleXMLElement;
use TrackerFromXmlException;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklog;
use Tuleap\XML\PHPCast;

class CreateTrackerFromXMLChecker
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    public function __construct(ExplicitBacklogDao $explicit_backlog_dao)
    {
        $this->explicit_backlog_dao = $explicit_backlog_dao;
    }

    /**
     * @throws TrackerFromXmlException
     */
    public function checkTrackerCanBeCreatedInTrackerCreationContext(Project $project, SimpleXMLElement $tracker_xml): void
    {
        if (! $this->areAddToTopBacklogXMLTagsDefined($tracker_xml)) {
            return;
        }

        $project_id = (int) $project->getID();
        if ($this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            return;
        }

        throw new TrackerFromXmlException(
            dgettext(
                'tuleap-agiledashboard',
                '"AddToTopBacklog" post action cannot be used because the project does not use the explicit top backlog management.'
            )
        );
    }

    /**
     * @throws ProjectNotUsingExplicitBacklogException
     */
    public function checkTrackersCanBeCreatedInProjectImportContext(SimpleXMLElement $xml): void
    {
        if (! $this->areAddToTopBacklogXMLTagsDefined($xml)) {
            return;
        }

        if ($this->willExplicitBacklogBeUsedInProject($xml)) {
            return;
        }

        throw new ProjectNotUsingExplicitBacklogException();
    }

    private function areAddToTopBacklogXMLTagsDefined(SimpleXMLElement $xml): bool
    {
        $add_to_top_backlog_tags = $xml->xpath('//' . AddToTopBacklog::XML_TAG_NAME);
        return is_array($add_to_top_backlog_tags) && count($add_to_top_backlog_tags) > 0;
    }

    private function willExplicitBacklogBeUsedInProject(SimpleXMLElement $xml): bool
    {
        if (! isset($xml->agiledashboard) || ! isset($xml->agiledashboard->admin)) {
            return false;
        }

        return PHPCast::toBoolean($xml->agiledashboard->admin->scrum->explicit_backlog['is_used']);
    }
}
