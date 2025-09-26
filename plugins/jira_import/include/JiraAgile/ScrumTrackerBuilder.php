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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\JiraAgile;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\FormElement\Burnup\XML\XMLBurnupField;
use Tuleap\Color\ColorName;
use Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn;
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;
use Tuleap\Tracker\FormElement\Field\ArtifactId\XML\XMLArtifactIdField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\XML\XMLArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\Burndown\XML\XMLBurndownField;
use Tuleap\Tracker\FormElement\Field\CrossReference\XML\XMLCrossReferenceField;
use Tuleap\Tracker\FormElement\Field\Date\XML\XMLDateField;
use Tuleap\Tracker\FormElement\Field\Integer\XML\XMLIntegerField;
use Tuleap\Tracker\FormElement\Field\LastModifiedBy\XML\XMLLastModifiedByField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\XML\XMLLastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceByLabel;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\XML\XMLSubmittedByField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\XML\XMLSubmittedOnField;
use Tuleap\Tracker\FormElement\Field\XML\ReadPermission;
use Tuleap\Tracker\FormElement\Field\XML\SubmitPermission;
use Tuleap\Tracker\FormElement\Field\XML\UpdatePermission;
use Tuleap\Tracker\FormElement\XML\XMLReferenceByName;
use Tuleap\Tracker\Report\Renderer\Table\Column\XML\XMLTableColumn;
use Tuleap\Tracker\Report\Renderer\Table\XML\XMLTable;
use Tuleap\Tracker\Report\XML\XMLReport;
use Tuleap\Tracker\Report\XML\XMLReportCriterion;
use Tuleap\Tracker\Semantic\Status\Done\XML\XMLDoneSemantic;
use Tuleap\Tracker\Semantic\Status\XML\XMLStatusSemantic;
use Tuleap\Tracker\Semantic\Timeframe\XML\XMLTimeframeSemantic;
use Tuleap\Tracker\Semantic\Title\XML\XMLTitleSemantic;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;

final class ScrumTrackerBuilder
{
    public const string DETAILS_RIGHT_COLUMN_NAME    = 'details2';
    public const string NAME_FIELD_NAME              = 'name';
    public const string START_DATE_FIELD_NAME        = 'start_date';
    public const string END_DATE_FIELD_NAME          = 'end_date';
    public const string COMPLETED_DATE_FIELD_NAME    = 'completed_date';
    public const string STATUS_FIELD_NAME            = 'status';
    public const string ARTIFACT_LINK_FIELD_NAME     = 'links';
    private const string CAPACITY_FIELD_NAME         = 'capacity';
    private const string CROSS_REFERENCES_FIELD_NAME = 'references';
    private const string BURNDOWN_FIELD_NAME         = 'burndown';
    private const string BURNUP_FIELD_NAME           = 'burnup';

    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;

    public function __construct(EventDispatcherInterface $event_dispatcher)
    {
        $this->event_dispatcher = $event_dispatcher;
    }

    public function get(IDGenerator $id_generator): XMLTracker
    {
        $default_permissions   = [
            new ReadPermission('UGROUP_ANONYMOUS'),
            new SubmitPermission('UGROUP_REGISTERED'),
            new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
        ];
        $read_only_permissions = [
            new ReadPermission('UGROUP_ANONYMOUS'),
        ];

        $tracker = (new XMLTracker($id_generator, 'sprint'))
            ->withName('Sprints')
            ->withColor(ColorName::ACID_GREEN)
            ->withFormElement(
                (new XMLFieldset($id_generator, 'details'))
                    ->withLabel('Details')
                    ->withFormElements(
                        (new XMLStringField($id_generator, self::NAME_FIELD_NAME))
                            ->withLabel('Name')
                            ->withRank(1)
                            ->withPermissions(...$default_permissions),
                        (new XMLColumn($id_generator, 'details1'))
                            ->withRank(2)
                            ->withFormElements(
                                (new XMLDateField($id_generator, self::START_DATE_FIELD_NAME))
                                    ->withLabel('Start Date')
                                    ->withDateTime()
                                    ->withRank(1)
                                    ->withPermissions(...$default_permissions),
                                (new XMLDateField($id_generator, self::END_DATE_FIELD_NAME))
                                    ->withLabel('End Date')
                                    ->withDateTime()
                                    ->withRank(2)
                                    ->withPermissions(...$default_permissions),
                                (new XMLDateField($id_generator, self::COMPLETED_DATE_FIELD_NAME))
                                    ->withLabel('Completed Date')
                                    ->withDateTime()
                                    ->withRank(3)
                                    ->withPermissions(...$default_permissions),
                                (new XMLIntegerField($id_generator, self::CAPACITY_FIELD_NAME))
                                    ->withLabel('Capacity')
                                    ->withRank(4)
                                    ->withPermissions(...$default_permissions),
                            ),
                        (new XMLColumn($id_generator, self::DETAILS_RIGHT_COLUMN_NAME))
                            ->withRank(3)
                            ->withFormElements(
                                (new XMLSelectBoxField($id_generator, self::STATUS_FIELD_NAME))
                                    ->withRank(1)
                                    ->withLabel('Status')
                                    ->withStaticValues(
                                        new XMLBindStaticValue($id_generator, JiraSprint::STATE_FUTURE),
                                        new XMLBindStaticValue($id_generator, JiraSprint::STATE_ACTIVE),
                                        new XMLBindStaticValue($id_generator, JiraSprint::STATE_CLOSED),
                                    )
                                    ->withPermissions(...$default_permissions),
                            ),
                    ),
                (new XMLFieldset($id_generator, 'links_fieldset'))
                    ->withLabel('Content')
                    ->withFormElements(
                        (new XMLArtifactLinkField($id_generator, self::ARTIFACT_LINK_FIELD_NAME))
                            ->withRank(1)
                            ->withLabel('Links')
                            ->withPermissions(...$default_permissions),
                        (new XMLCrossReferenceField($id_generator, self::CROSS_REFERENCES_FIELD_NAME))
                            ->withRank(2)
                            ->withLabel('References')
                            ->withPermissions(...$default_permissions),
                        (new XMLBurndownField($id_generator, self::BURNDOWN_FIELD_NAME))
                            ->withRank(3)
                            ->withLabel('Burndown')
                            ->withPermissions(...$default_permissions),
                        (new XMLBurnupField($id_generator, self::BURNUP_FIELD_NAME))
                            ->withRank(4)
                            ->withLabel('Burnup')
                            ->withPermissions(...$default_permissions),
                    ),
                (new XMLFieldset($id_generator, 'access_information'))
                    ->withLabel('Access Information')
                    ->withFormElements(
                        (new XMLColumn($id_generator, 'access_information_left_column'))
                            ->withRank(1)
                            ->withFormElements(
                                (new XMLArtifactIdField($id_generator, 'artifact_id'))
                                    ->withRank(1)
                                    ->withLabel('Artifact ID')
                                    ->withPermissions(...$read_only_permissions),
                                (new XMLSubmittedOnField($id_generator, 'submitted_on'))
                                    ->withRank(2)
                                    ->withLabel('Submitted on')
                                    ->withPermissions(...$read_only_permissions),
                                (new XMLSubmittedByField($id_generator, 'submitted_by'))
                                    ->withRank(3)
                                    ->withLabel('Submitted by')
                                    ->withPermissions(...$read_only_permissions),
                            ),
                        (new XMLColumn($id_generator, 'access_information_right_column'))
                            ->withRank(2)
                            ->withFormElements(
                                (new XMLLastUpdateDateField($id_generator, 'last_update_date'))
                                    ->withRank(1)
                                    ->withLabel('Last update date')
                                    ->withPermissions(...$read_only_permissions),
                                (new XMLLastModifiedByField($id_generator, 'last_updated_by'))
                                    ->withRank(2)
                                    ->withLabel('Last updated by')
                                    ->withPermissions(...$read_only_permissions),
                            ),
                    ),
            )
            ->withSemantics(
                new XMLTitleSemantic(
                    new XMLReferenceByName(self::NAME_FIELD_NAME)
                ),
                (new XMLStatusSemantic(new XMLReferenceByName(self::STATUS_FIELD_NAME)))
                ->withOpenValues(
                    new XMLBindValueReferenceByLabel(self::STATUS_FIELD_NAME, JiraSprint::STATE_FUTURE),
                    new XMLBindValueReferenceByLabel(self::STATUS_FIELD_NAME, JiraSprint::STATE_ACTIVE),
                ),
                new XMLTimeframeSemantic(
                    new XMLReferenceByName(self::START_DATE_FIELD_NAME),
                    new XMLReferenceByName(self::END_DATE_FIELD_NAME)
                ),
                (new XMLDoneSemantic())
                ->withDoneValues(
                    new XMLBindValueReferenceByLabel(self::STATUS_FIELD_NAME, JiraSprint::STATE_CLOSED)
                )
            )
            ->withReports(
                (new XMLReport('Active sprints'))
                    ->withIsDefault(true)
                    ->withCriteria(
                        (new XMLReportCriterion(new XMLReferenceByName(self::NAME_FIELD_NAME)))
                            ->withRank(1),
                        (new XMLReportCriterion(new XMLReferenceByName(self::STATUS_FIELD_NAME)))
                            ->withRank(2)
                            ->withSelectedValues(
                                new XMLBindValueReferenceByLabel(self::STATUS_FIELD_NAME, JiraSprint::STATE_ACTIVE),
                            ),
                        (new XMLReportCriterion(new XMLReferenceByName(self::START_DATE_FIELD_NAME)))
                            ->withRank(3),
                        (new XMLReportCriterion(new XMLReferenceByName(self::END_DATE_FIELD_NAME)))
                            ->withRank(4),
                        (new XMLReportCriterion(new XMLReferenceByName(self::COMPLETED_DATE_FIELD_NAME)))
                            ->withRank(5),
                    )
                    ->withRenderers(
                        (new XMLTable('Table'))
                            ->withColumns(
                                new XMLTableColumn(new XMLReferenceByName(self::NAME_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::START_DATE_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::END_DATE_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::COMPLETED_DATE_FIELD_NAME)),
                                new XMLTableColumn(new XMLReferenceByName(self::STATUS_FIELD_NAME)),
                            )
                    )
            );

        $event = $this->event_dispatcher->dispatch(new ScrumTrackerStructureEvent($tracker, $id_generator));
        return $event->tracker;
    }
}
