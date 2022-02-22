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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Cardwall\XML\Template;

use Tuleap\Cardwall\XML\XMLCardwallRenderer;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Report\Renderer\XML\XMLRenderer;
use Tuleap\Tracker\Template\IssuesTemplate;

/**
 * @psalm-immutable
 */
final class CompleteIssuesTemplate
{
    public static function getAllIssuesRenderer(): XMLRenderer
    {
        return (new XMLCardwallRenderer('Cardwall'))
            ->withField(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME));
    }

    public static function getMyIssuesRenderer(): XMLRenderer
    {
        return (new XMLCardwallRenderer('My cardwall'))
            ->withField(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME));
    }

    public static function getOpenIssuesRenderer(): XMLRenderer
    {
        return (new XMLCardwallRenderer('Cardwall'))
            ->withField(new XMLReferenceByName(IssuesTemplate::STATUS_FIELD_NAME));
    }
}
