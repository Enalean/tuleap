<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\JiraImport\Project\Components;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\XML\XMLArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsers\XML\XMLBindUsersValue;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField;
use Tuleap\Tracker\FormElement\Field\XML\ReadPermission;
use Tuleap\Tracker\FormElement\Field\XML\SubmitPermission;
use Tuleap\Tracker\FormElement\Field\XML\UpdatePermission;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn;
use Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable;
use Tuleap\Tracker\Report\XML\XMLReport;
use Tuleap\Tracker\Report\XML\XMLReportCriterion;
use Tuleap\Tracker\Semantic\Contributor\XML\XMLContributorSemantic;
use Tuleap\Tracker\Semantic\Description\XML\XMLDescriptionSemantic;
use Tuleap\Tracker\Semantic\Title\XML\XMLTitleSemantic;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;

final class ComponentsTrackerBuilder
{
    public const NAME_FIELD_NAME           = 'name';
    public const DESCRIPTION_FIELD_NAME    = 'description';
    public const ARTIFACT_LINK_FIELD_NAME  = 'linked_issues';
    public const COMPONENT_LEAD_FIELD_NAME = 'component_lead';

    public function get(IDGenerator $id_generator): XMLTracker
    {
        $default_permissions = [
            new ReadPermission('UGROUP_ANONYMOUS'),
            new SubmitPermission('UGROUP_REGISTERED'),
            new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
        ];

        $tracker = (new XMLTracker($id_generator, 'components'))
            ->withName('Components')
            ->withColor(TrackerColor::fromName('acid-green'))
            ->withFormElement(
                (new XMLStringField($id_generator, self::NAME_FIELD_NAME))
                    ->withLabel('Name')
                    ->withRank(1)
                    ->withPermissions(...$default_permissions),
                (new XMLStringField($id_generator, self::DESCRIPTION_FIELD_NAME))
                    ->withLabel('Description')
                    ->withRank(2)
                    ->withPermissions(...$default_permissions),
                (new XMLArtifactLinkField($id_generator, self::ARTIFACT_LINK_FIELD_NAME))
                    ->withRank(3)
                    ->withLabel('Links')
                    ->withPermissions(...$default_permissions),
                (new XMLSelectBoxField($id_generator, self::COMPONENT_LEAD_FIELD_NAME))
                    ->withRank(4)
                    ->withLabel('Component Lead')
                    ->withUsersValues(
                        new XMLBindUsersValue('group_members')
                    )
                    ->withPermissions(...$default_permissions),
            )
            ->withSemantics(
                new XMLTitleSemantic(
                    new XMLReferenceByName(self::NAME_FIELD_NAME)
                ),
                new XMLDescriptionSemantic(
                    new XMLReferenceByName(self::DESCRIPTION_FIELD_NAME)
                ),
                new XMLContributorSemantic(
                    new XMLReferenceByName(self::COMPONENT_LEAD_FIELD_NAME)
                ),
            )
            ->withReports(
                (new XMLReport('Components'))
                    ->withIsDefault(true)
                    ->withCriteria(
                        (new XMLReportCriterion(new XMLReferenceByName(self::NAME_FIELD_NAME)))->withRank(1),
                        (new XMLReportCriterion(new XMLReferenceByName(self::DESCRIPTION_FIELD_NAME)))->withRank(2),
                        (new XMLReportCriterion(new XMLReferenceByName(self::COMPONENT_LEAD_FIELD_NAME)))->withRank(3),
                    )
                    ->withRenderers(
                        (new XMLTable('Table'))
                            ->withColumns(
                                new XMLTableColumn(new XMLReferenceByName(self::NAME_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::DESCRIPTION_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::COMPONENT_LEAD_FIELD_NAME)),
                            )
                    )
            );

        return $tracker;
    }
}
