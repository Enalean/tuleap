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

namespace Tuleap\Tracker\Template;

use Psr\EventDispatcher\EventDispatcherInterface;
use SimpleXMLElement;
use Tuleap\Project\Registration\Template\IssuesTemplateDashboardDefinition;
use Tuleap\Tracker\FormElement\Container\Column\XML\XMLColumn;
use Tuleap\Tracker\FormElement\Container\Fieldset\XML\XMLFieldset;
use Tuleap\Tracker\FormElement\Field\ArtifactId\XML\XMLArtifactIdField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\XML\XMLArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\CrossReference\XML\XMLCrossReferenceField;
use Tuleap\Tracker\FormElement\Field\Date\XML\XMLDateField;
use Tuleap\Tracker\FormElement\Field\File\XML\XMLFileField;
use Tuleap\Tracker\FormElement\Field\LastModifiedBy\XML\XMLLastModifiedByField;
use Tuleap\Tracker\FormElement\Field\LastUpdateDate\XML\XMLLastUpdateDateField;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUsers\XML\XMLBindUsersValue;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\XML\XMLBindValueReferenceById;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField;
use Tuleap\Tracker\FormElement\Field\StringField\XML\XMLStringField;
use Tuleap\Tracker\FormElement\Field\SubmittedBy\XML\XMLSubmittedByField;
use Tuleap\Tracker\FormElement\Field\SubmittedOn\XML\XMLSubmittedOnField;
use Tuleap\Tracker\FormElement\Field\Text\XML\XMLTextField;
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
use Tuleap\Tracker\Semantic\Status\Done\XML\XMLDoneSemantic;
use Tuleap\Tracker\Semantic\Status\XML\XMLStatusSemantic;
use Tuleap\Tracker\Semantic\Title\XML\XMLTitleSemantic;
use Tuleap\Tracker\Semantic\XML\XMLFieldsBasedSemantic;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\Workflow\XML\XMLSimpleWorkflow;
use Tuleap\Tracker\XML\XMLTracker;
use Tuleap\Widget\XML\XMLPreference;
use Tuleap\Widget\XML\XMLPreferenceValue;
use Tuleap\Widget\XML\XMLWidget;

/**
 * @psalm-immutable
 */
final class IssuesTemplate
{
    public const PRIORITY_FIELD_NAME    = 'priority';
    public const STATUS_FIELD_NAME      = 'status';
    public const ASSIGNED_TO_FIELD_NAME = 'assigned_to';

    private const ISSUE_NUMBER_FIELD_NAME     = 'issue_number';
    private const TITLE_FIELD_NAME            = 'title';
    private const SUBMITTED_ON_FIELD_NAME     = 'submitted_on';
    private const OPEN_ISSUES_RENDERER_ID     = 'Open_Issues_Table_Renderer';
    private const CRITICAL_ISSUES_RENDERER_ID = 'Critical_Issues_Table_Renderer';

    public static function defineTemplate(
        SimpleXMLElement $project_template,
        EventDispatcherInterface $dispatcher,
    ): void {
        $tracker = (new XMLTracker('T_issue', 'issue'))
            ->withName('Issues')
            ->withPromoted()
            ->withColor(TrackerColor::fromName('lake-placid-blue'))
            ->withDescription('requests, bugs, tasks, activities');

        $issue_tracker = $tracker
            ->withFormElement(
                XMLFieldset::fromTrackerAndName($tracker, 'access_information')
                    ->withLabel('Access Information')
                    ->withRank(6)
                    ->withFormElements(
                        XMLColumn::fromTrackerAndName($tracker, 'column_0')
                            ->withLabel('Access information left column')
                            ->withRank(0)
                            ->withFormElements(
                                XMLSubmittedOnField::fromTrackerAndName($tracker, self::SUBMITTED_ON_FIELD_NAME)
                                    ->withLabel('Submitted on')
                                    ->withRank(1)
                                    ->withPermissions(
                                        new ReadPermission('UGROUP_ANONYMOUS')
                                    ),
                                XMLSubmittedByField::fromTrackerAndName($tracker, 'submitted_by')
                                    ->withLabel('Submitted by')
                                    ->withRank(3)
                                    ->withPermissions(
                                        new ReadPermission('UGROUP_ANONYMOUS')
                                    )
                            ),
                        XMLColumn::fromTrackerAndName($tracker, 'column_1')
                            ->withLabel('Access information right column')
                            ->withRank(1)
                            ->withFormElements(
                                XMLLastUpdateDateField::fromTrackerAndName(
                                    $tracker,
                                    'last_update_on'
                                )->withLabel('Last Update On')->withRank(0)->withPermissions(
                                    new ReadPermission('UGROUP_ANONYMOUS')
                                ),
                                XMLLastModifiedByField::fromTrackerAndName(
                                    $tracker,
                                    'last_update_by'
                                )->withLabel('Last Update By')->withRank(3)->withPermissions(
                                    new ReadPermission('UGROUP_ANONYMOUS')
                                )
                            )
                    ),
                XMLFieldset::fromTrackerAndName($tracker, 'description')
                    ->withLabel('Description')
                    ->withRank(28)
                    ->withFormElements(
                        XMLColumn::fromTrackerAndName($tracker, 'description_col1')
                            ->withLabel('description_col1')
                            ->withRank(0)
                            ->withFormElements(
                                XMLArtifactIdField::fromTrackerAndName($tracker, self::ISSUE_NUMBER_FIELD_NAME)
                                    ->withLabel('Issue Number')
                                    ->withRank(0)
                                    ->withPermissions(
                                        new ReadPermission('UGROUP_ANONYMOUS')
                                    )
                            ),
                        XMLColumn::fromTrackerAndName($tracker, 'description_col2')
                            ->withLabel('description_col2')
                            ->withRank(1)
                            ->withFormElements(
                                XMLSelectBoxField::fromTrackerAndName($tracker, 'type')
                                    ->withLabel('Type')
                                    ->withRank(0)
                                    ->withPermissions(
                                        new ReadPermission('UGROUP_ANONYMOUS'),
                                        new SubmitPermission('UGROUP_REGISTERED'),
                                        new UpdatePermission('UGROUP_PROJECT_MEMBERS')
                                    )
                                    ->withStaticValues(
                                        (new XMLBindStaticValue('V13787', 'Request'))
                                            ->withDecorator('clockwork-orange'),
                                        (new XMLBindStaticValue('V13788', 'Bug'))
                                            ->withDecorator('fiesta-red'),
                                        (new XMLBindStaticValue('V13789', 'Task'))
                                            ->withDecorator('acid-green'),
                                        (new XMLBindStaticValue('V13790', 'Activity'))
                                            ->withDecorator('daphne-blue')
                                    )
                            ),
                        XMLStringField::fromTrackerAndName($tracker, self::TITLE_FIELD_NAME)
                            ->withLabel('Title')
                            ->withRank(2)
                            ->withPermissions(
                                new ReadPermission('UGROUP_ANONYMOUS'),
                                new SubmitPermission('UGROUP_REGISTERED'),
                                new UpdatePermission('UGROUP_REGISTERED')
                            ),
                        XMLTextField::fromTrackerAndName($tracker, 'details')
                            ->withLabel('Description')
                            ->withRank(5)
                            ->withPermissions(
                                new ReadPermission('UGROUP_ANONYMOUS'),
                                new SubmitPermission('UGROUP_REGISTERED'),
                                new UpdatePermission('UGROUP_REGISTERED')
                            )
                            ->withRows(7)
                            ->withCols(60)
                    ),
                XMLFieldset::fromTrackerAndName($tracker, 'attachment_1')
                    ->withLabel('Attachment')
                    ->withRank(29)
                    ->withFormElements(
                        XMLFileField::fromTrackerAndName($tracker, 'attachment')
                            ->withLabel('Attachments')
                            ->withRank(0)
                            ->withPermissions(
                                new ReadPermission('UGROUP_ANONYMOUS'),
                                new SubmitPermission('UGROUP_REGISTERED'),
                                new UpdatePermission('UGROUP_REGISTERED')
                            )
                    ),
                XMLFieldset::fromTrackerAndName($tracker, 'fieldset_2')
                    ->withLabel('fieldset_status_bugs_lbl_key')
                    ->withRank(432)
                    ->withFormElements(
                        XMLColumn::fromTrackerAndName($tracker, 'column_0_1')
                            ->withLabel('c0')
                            ->withRank(0)
                            ->withFormElements(
                                XMLSelectBoxField::fromTrackerAndName($tracker, self::STATUS_FIELD_NAME)
                                    ->withLabel('Status')
                                    ->withRank(0)
                                    ->withPermissions(
                                        new ReadPermission('UGROUP_ANONYMOUS'),
                                        new UpdatePermission('UGROUP_REGISTERED'),
                                        new SubmitPermission('UGROUP_REGISTERED'),
                                    )
                                    ->withStaticValues(
                                        (new XMLBindStaticValue('V13617', 'New'))
                                            ->withDescription('New entry, no progress done so far')
                                            ->withIsDefault()
                                            ->withDecorator(
                                                'firemist-silver'
                                            ),
                                        (new XMLBindStaticValue('V13618', 'In progress'))
                                            ->withDescription('The team is working on the subject')
                                            ->withDecorator('acid-green'),
                                        (new XMLBindStaticValue('V13621', 'Under review'))
                                            ->withDescription('The issue is under review')
                                            ->withDecorator('neon-green'),
                                        (new XMLBindStaticValue('V13620', 'Canceled'))
                                            ->withDescription('The issue won\'t be done.')
                                            ->withDecorator('teddy-brown'),
                                        (new XMLBindStaticValue('V13623', 'Done'))
                                            ->withDescription('The issue is done, achieved or fixed.')
                                            ->withDecorator('army-green')
                                    ),
                                XMLSelectBoxField::fromTrackerAndName($tracker, self::PRIORITY_FIELD_NAME)
                                    ->withLabel('Priority')
                                    ->withRank(6)
                                    ->withPermissions(
                                        new ReadPermission('UGROUP_ANONYMOUS'),
                                        new SubmitPermission('UGROUP_REGISTERED'),
                                        new UpdatePermission('UGROUP_PROJECT_MEMBERS')
                                    )
                                    ->withStaticValues(
                                        (new XMLBindStaticValue('V13624', 'Low impact'))
                                            ->withIsDefault()
                                            ->withDecorator('graffiti-yellow'),
                                        (new XMLBindStaticValue('V13625', 'Major impact'))
                                            ->withDecorator('clockwork-orange'),
                                        (new XMLBindStaticValue('V13626', 'Critical impact'))
                                            ->withDecorator('fiesta-red')
                                    )
                            ),
                        XMLColumn::fromTrackerAndName($tracker, 'column_1_1')
                            ->withLabel('c1')
                            ->withRank(3)
                            ->withFormElements(
                                XMLSelectBoxField::fromTrackerAndName($tracker, self::ASSIGNED_TO_FIELD_NAME)
                                    ->withLabel('Assigned to')
                                    ->withRank(0)
                                    ->withPermissions(
                                        new ReadPermission('UGROUP_ANONYMOUS'),
                                        new UpdatePermission('UGROUP_PROJECT_MEMBERS'),
                                        new SubmitPermission('UGROUP_PROJECT_MEMBERS')
                                    )
                                    ->withUsersValues(
                                        new XMLBindUsersValue(
                                            'group_members'
                                        )
                                    )
                            )
                    ),
                XMLFieldset::fromTrackerAndName($tracker, 'fieldset_5')
                    ->withLabel('Links')
                    ->withRank(447)
                    ->withFormElements(
                        XMLArtifactLinkField::fromTrackerAndName($tracker, 'linked_issues')
                            ->withLabel('Linked Issues')
                            ->withRank(0)
                            ->withPermissions(
                                new ReadPermission('UGROUP_ANONYMOUS'),
                                new UpdatePermission('UGROUP_REGISTERED'),
                                new ReadPermission('UGROUP_ANONYMOUS')
                            ),
                        XMLCrossReferenceField::fromTrackerAndName($tracker, 'references')
                            ->withLabel('Cross References')
                            ->withRank(1)
                            ->withPermissions(
                                new ReadPermission('UGROUP_ANONYMOUS')
                            )
                    ),
                XMLFieldset::fromTrackerAndName($tracker, 'achievement')
                    ->withLabel('Achievement')
                    ->withRank(448)
                    ->withFormElements(
                        XMLDateField::fromTrackerAndName($tracker, 'close_date')
                            ->withLabel('Close Date')
                            ->withRank(0)
                            ->withPermissions(
                                new ReadPermission('UGROUP_ANONYMOUS'),
                                new UpdatePermission('UGROUP_PROJECT_MEMBERS')
                            ),
                        XMLTextField::fromTrackerAndName($tracker, 'achievement_details')
                            ->withLabel('Achievement Details')
                            ->withRank(1)
                            ->withPermissions(new UpdatePermission('UGROUP_PROJECT_MEMBERS'))
                            ->withRows(10)
                            ->withCols(50)
                    )
            )
            ->withSemantics(
                new XMLTitleSemantic(
                    new XMLReferenceByName(self::TITLE_FIELD_NAME)
                ),
                new XMLDescriptionSemantic(
                    new XMLReferenceByName('details')
                ),
                (new XMLStatusSemantic(new XMLReferenceByName(self::STATUS_FIELD_NAME)))
                    ->withOpenValues(
                        new XMLBindValueReferenceById('V13617'),
                        new XMLBindValueReferenceById('V13618'),
                        new XMLBindValueReferenceById('V13621')
                    ),
                new XMLDoneSemantic(),
                new XMLContributorSemantic(new XMLReferenceByName(self::ASSIGNED_TO_FIELD_NAME)),
                (new XMLFieldsBasedSemantic('tooltip'))
                    ->withFields(
                        new XMLReferenceByName(self::TITLE_FIELD_NAME),
                        new XMLReferenceByName(self::STATUS_FIELD_NAME),
                        new XMLReferenceByName('details'),
                        new XMLReferenceByName(self::PRIORITY_FIELD_NAME)
                    ),
                (new XMLFieldsBasedSemantic('plugin_cardwall_card_fields'))
                    ->withFields(
                        new XMLReferenceByName(self::PRIORITY_FIELD_NAME),
                        new XMLReferenceByName(self::ASSIGNED_TO_FIELD_NAME)
                    )
            )
            ->withReports(
                ...self::getReports($dispatcher)
            )
            ->withWorkflow(
                (new XMLSimpleWorkflow())
                    ->withField(new XMLReferenceByName(self::STATUS_FIELD_NAME))
            );

        $project_template->addChild('trackers');
        $issue_tracker->export($project_template->trackers);
    }

    private static function getReports(EventDispatcherInterface $dispatcher): array
    {
        $all_issues_renderers = [
            (new XMLTable('All Issues'))
                ->withChunkSize(15)
                ->withColumns(
                    new XMLTableColumn(
                        new XMLReferenceByName(self::ISSUE_NUMBER_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::TITLE_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::STATUS_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::PRIORITY_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::ASSIGNED_TO_FIELD_NAME)
                    )
                ),
        ];

        $my_issues_renderers = [
            (new XMLTable('My Issues'))
                ->withChunkSize(15)
                ->withColumns(
                    new XMLTableColumn(
                        new XMLReferenceByName(self::ISSUE_NUMBER_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::TITLE_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::STATUS_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::PRIORITY_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::SUBMITTED_ON_FIELD_NAME)
                    )
                ),
        ];

        $open_issues_renderers = [
            (new XMLTable('Open Issues'))
                ->withId(self::OPEN_ISSUES_RENDERER_ID)
                ->withChunkSize(15)
                ->withColumns(
                    new XMLTableColumn(
                        new XMLReferenceByName(self::ISSUE_NUMBER_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::TITLE_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::STATUS_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::PRIORITY_FIELD_NAME)
                    ),
                    new XMLTableColumn(
                        new XMLReferenceByName(self::ASSIGNED_TO_FIELD_NAME)
                    )
                ),
        ];

        $renderers = $dispatcher->dispatch(
            new CompleteIssuesTemplateEvent($all_issues_renderers, $my_issues_renderers, $open_issues_renderers)
        );

        return [
            (new XMLReport('All issues'))
                ->withDescription('Bugs Report')
                ->withCriteria(
                    (new XMLReportCriterion(new XMLReferenceByName(self::ISSUE_NUMBER_FIELD_NAME)))
                        ->withRank(0),
                    (new XMLReportCriterion(new XMLReferenceByName(self::TITLE_FIELD_NAME)))
                        ->withRank(1),
                    (new XMLReportCriterion(new XMLReferenceByName(self::STATUS_FIELD_NAME)))
                        ->withRank(2)->withIsAdvanced(),
                    (new XMLReportCriterion(new XMLReferenceByName(self::PRIORITY_FIELD_NAME)))
                        ->withRank(3)
                )->withRenderers(
                    ...$renderers->getAllIssuesRenderers(),
                ),
            (new XMLReport('Critical issues'))
                ->withDescription('Bugs Report')
                ->withCriteria(
                    (new XMLReportCriterion(new XMLReferenceByName(self::ISSUE_NUMBER_FIELD_NAME)))
                        ->withRank(0),
                    (new XMLReportCriterion(new XMLReferenceByName(self::TITLE_FIELD_NAME)))
                        ->withRank(1),
                    (new XMLReportCriterion(new XMLReferenceByName(self::PRIORITY_FIELD_NAME)))
                        ->withRank(3),
                    (new XMLReportCriterion(new XMLReferenceByName(self::STATUS_FIELD_NAME)))
                        ->withRank(2)
                        ->withIsAdvanced()
                        ->withNoneSelected()
                        ->withSelectedValues(
                            new XMLBindValueReferenceById('V13617'),
                            new XMLBindValueReferenceById('V13618'),
                            new XMLBindValueReferenceById('V13621')
                        ),
                    (new XMLReportCriterion(new XMLReferenceByName(self::PRIORITY_FIELD_NAME)))
                        ->withRank(3)
                        ->withSelectedValues(new XMLBindValueReferenceById('V13626'))
                )->withRenderers(
                    (new XMLTable('Critical Issues'))
                        ->withId(self::CRITICAL_ISSUES_RENDERER_ID)
                        ->withChunkSize(15)
                        ->withColumns(
                            new XMLTableColumn(
                                new XMLReferenceByName(self::ISSUE_NUMBER_FIELD_NAME)
                            ),
                            new XMLTableColumn(
                                new XMLReferenceByName(self::TITLE_FIELD_NAME)
                            ),
                            new XMLTableColumn(
                                new XMLReferenceByName(self::STATUS_FIELD_NAME)
                            ),
                            new XMLTableColumn(
                                new XMLReferenceByName(self::PRIORITY_FIELD_NAME)
                            ),
                            new XMLTableColumn(
                                new XMLReferenceByName(self::ASSIGNED_TO_FIELD_NAME)
                            )
                        )
                ),
            (new XMLReport('My issues'))
                ->withExpertMode()
                ->withExpertQuery('status IN (\'New\', \'In progress\', \'Under review\') AND assigned_to = MYSELF()')
                ->withDescription('Bugs Report')
                ->withCriteria(
                    (new XMLReportCriterion(new XMLReferenceByName(self::ISSUE_NUMBER_FIELD_NAME)))
                        ->withRank(0),
                    (new XMLReportCriterion(new XMLReferenceByName(self::TITLE_FIELD_NAME)))
                        ->withRank(1),
                    (new XMLReportCriterion(new XMLReferenceByName(self::STATUS_FIELD_NAME)))
                        ->withRank(2)
                        ->withIsAdvanced(),
                    (new XMLReportCriterion(new XMLReferenceByName(self::PRIORITY_FIELD_NAME)))
                        ->withRank(3)
                )->withRenderers(
                    ...$renderers->getMyIssuesRenderers()
                ),
            (new XMLReport('Open Issues'))
                ->withDescription('Bugs Report')
                ->withCriteria(
                    (new XMLReportCriterion(new XMLReferenceByName(self::ISSUE_NUMBER_FIELD_NAME)))
                        ->withRank(0),
                    (new XMLReportCriterion(new XMLReferenceByName(self::TITLE_FIELD_NAME)))
                        ->withRank(1),
                    (new XMLReportCriterion(new XMLReferenceByName(self::STATUS_FIELD_NAME)))
                        ->withRank(2)
                        ->withIsAdvanced()
                        ->withNoneSelected()
                        ->withSelectedValues(
                            new XMLBindValueReferenceById('V13617'),
                            new XMLBindValueReferenceById('V13618'),
                            new XMLBindValueReferenceById('V13621')
                        ),
                    (new XMLReportCriterion(new XMLReferenceByName(self::PRIORITY_FIELD_NAME)))
                        ->withRank(3)
                )->withRenderers(
                    ...$renderers->getOpenIssuesRenderers()
                ),
        ];
    }

    public static function defineDashboards(IssuesTemplateDashboardDefinition $dashboard_definition): void
    {
        $dashboard_definition->withWidgetInLeftColumnOfTeamDashboard(
            (new XMLWidget('plugin_tracker_projectrenderer'))
                ->withPreference((new XMLPreference('renderer'))
                    ->withValue(XMLPreferenceValue::ref('id', self::OPEN_ISSUES_RENDERER_ID))
                    ->withValue(XMLPreferenceValue::text('title', 'Open Issues')))
        );

        $critical_issues_widget = (new XMLWidget('plugin_tracker_projectrenderer'))
            ->withPreference(
                (new XMLPreference('renderer'))
                    ->withValue(XMLPreferenceValue::ref('id', self::CRITICAL_ISSUES_RENDERER_ID))
                    ->withValue(XMLPreferenceValue::text('title', 'Critical Issues'))
            );

        $dashboard_definition->withWidgetInLeftColumnOfTeamDashboard($critical_issues_widget);
        $dashboard_definition->withWidgetInMainColumnOfManagerDashboard(clone $critical_issues_widget);
    }
}
