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

namespace Tuleap\AgileDashboard\Workflow\REST\v1;

use Tuleap\AgileDashboard\ExplicitBacklog\ExplicitBacklogDao;
use Tuleap\AgileDashboard\Workflow\AddToTopBacklog;
use Tuleap\AgileDashboard\Workflow\PostAction\Update\AddToTopBacklogValue;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\PostActionUpdateJsonParser;
use Tuleap\Tracker\Workflow\Update\PostAction;
use Workflow;

class AddToTopBacklogJsonParser implements PostActionUpdateJsonParser
{
    /**
     * @var ExplicitBacklogDao
     */
    private $explicit_backlog_dao;

    public function __construct(ExplicitBacklogDao $explicit_backlog_dao)
    {
        $this->explicit_backlog_dao = $explicit_backlog_dao;
    }

    public function accept(array $json): bool
    {
        return isset($json['type']) && $json['type'] === AddToTopBacklog::SHORT_NAME;
    }

    /**
     * @throws I18NRestException
     */
    public function parse(Workflow $workflow, array $json): PostAction
    {
        $project_id = (int) $workflow->getTracker()->getGroupId();

        if (! $this->explicit_backlog_dao->isProjectUsingExplicitBacklog($project_id)) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        "tuleap-agiledashboard",
                        "PostAction of type %s cannot be defined without explicit backlog management."
                    ),
                    AddToTopBacklog::SHORT_NAME
                )
            );
        }

        return new AddToTopBacklogValue();
    }
}
