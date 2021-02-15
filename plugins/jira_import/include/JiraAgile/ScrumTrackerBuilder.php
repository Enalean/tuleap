<?php
/*
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\JiraAgile;

use Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn;
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;
use Tuleap\Tracker\FormElement\Field\Date\XML\XMLDateField;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField;
use Tuleap\Tracker\FormElement\Field\XML\ReadPermission;
use Tuleap\Tracker\FormElement\Field\XML\SubmitPermission;
use Tuleap\Tracker\FormElement\Field\XML\UpdatePermission;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Report\XML\XMLReport;
use Tuleap\Tracker\Report\XML\XMLReportCriterion;
use Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable;
use Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;

final class ScrumTrackerBuilder
{
    public const NAME_FIELD_NAME           = 'name';
    public const START_DATE_FIELD_NAME     = 'start_date';
    public const END_DATE_FIELD_NAME       = 'end_date';
    public const COMPLETED_DATE_FIELD_NAME = 'completed_date';

    public function get(IDGenerator $id_generator): XMLTracker
    {
        $default_permissions = [
            new ReadPermission('UGROUP_ANONYMOUS'),
            new SubmitPermission('UGROUP_REGISTERED'),
            new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
        ];

        return (new XMLTracker($id_generator, 'sprint'))
            ->withName('Sprints')
            ->withColor(TrackerColor::fromName('acid-green'))
            ->withFormElement(
                (new XMLFieldset($id_generator, 'details'))
                    ->withLabel('Details')
                    ->withFormElements(
                        (new XMLStringField($id_generator, self::NAME_FIELD_NAME))
                            ->withLabel('Name')
                            ->withRank(1)
                            ->withPermissions(...$default_permissions),
                        (new XMLColumn($id_generator, 'col1'))
                            ->withRank(2)
                            ->withFormElements(
                                (new XMLDateField($id_generator, self::START_DATE_FIELD_NAME))
                                ->withLabel('Start Date')
                                ->withDateTime()
                                ->withPermissions(...$default_permissions),
                            ),
                        (new XMLColumn($id_generator, 'col2'))
                            ->withRank(3)
                            ->withFormElements(
                                (new XMLDateField($id_generator, self::END_DATE_FIELD_NAME))
                                    ->withLabel('End Date')
                                    ->withDateTime()
                                    ->withPermissions(...$default_permissions),
                            ),
                        (new XMLColumn($id_generator, 'col3'))
                            ->withRank(4)
                            ->withFormElements(
                                (new XMLDateField($id_generator, self::COMPLETED_DATE_FIELD_NAME))
                                    ->withLabel('Completed Date')
                                    ->withDateTime()
                                    ->withPermissions(...$default_permissions),
                            )
                    )
            )
            ->withReports(
                (new XMLReport('Active sprints'))
                    ->withIsDefault(true)
                    ->withCriteria(
                        new XMLReportCriterion(new XMLReferenceByName(self::NAME_FIELD_NAME)),
                        new XMLReportCriterion(new XMLReferenceByName(self::START_DATE_FIELD_NAME)),
                        new XMLReportCriterion(new XMLReferenceByName(self::END_DATE_FIELD_NAME)),
                        new XMLReportCriterion(new XMLReferenceByName(self::COMPLETED_DATE_FIELD_NAME)),
                    )
                    ->withRenderers(
                        (new XMLTable('Table'))
                            ->withColumns(
                                new XMLTableColumn(new XMLReferenceByName(self::NAME_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::START_DATE_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::END_DATE_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::COMPLETED_DATE_FIELD_NAME)),
                            )
                    )
            );
    }
}
