<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog\Workflow;

use Tuleap\ProgramManagement\Domain\Program\Plan\VerifyIsPlannable;
use Tuleap\REST\I18NRestException;
use Tuleap\Tracker\REST\v1\Workflow\PostAction\Update\PostActionUpdateJsonParser;
use Tuleap\Tracker\Workflow\Update\PostAction;
use Workflow;

final class AddToTopBacklogPostActionJSONParser implements PostActionUpdateJsonParser
{
    public function __construct(private VerifyIsPlannable $verify_is_plannable)
    {
    }

    #[\Override]
    public function accept(array $json): bool
    {
        return isset($json['type']) && $json['type'] === AddToTopBacklogPostAction::SHORT_NAME;
    }

    #[\Override]
    public function parse(Workflow $workflow, array $json): PostAction
    {
        $tracker_id = $workflow->getTrackerId();

        if (! $this->verify_is_plannable->isPlannable($tracker_id)) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-program_management',
                        'Post action of type %s can only be defined for plannable tracker of a plan.'
                    ),
                    AddToTopBacklogPostAction::SHORT_NAME
                )
            );
        }

        return new AddToTopBacklogPostActionValue();
    }
}
