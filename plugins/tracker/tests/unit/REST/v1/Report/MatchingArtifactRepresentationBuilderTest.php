<?php
/**
 * Copyright (c) Enalean, 2022 — Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1\Report;

use Luracast\Restler\RestException;
use Project;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field;
use Tracker_Report;
use Tracker_Report_Renderer_Table;
use Tuleap\Color\ItemColor;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Renderer\Table\TableRendererForReportRetriever;
use Tuleap\Tracker\Report\Renderer\Table\UsedFieldsRetriever;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation;
use Tuleap\Tracker\REST\v1\ArtifactMatchingReportCollection;
use Tuleap\Tracker\REST\v1\ReportArtifactFactory;
use Tuleap\Tracker\Semantic\Status\StatusColorForChangesetProvider;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MatchingArtifactRepresentationBuilderTest extends TestCase
{
    use GlobalLanguageMock;

    private MatchingArtifactRepresentationBuilder $representation_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReportArtifactFactory
     */
    private $report_artifact_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TableRendererForReportRetriever
     */
    private $table_renderer_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UsedFieldsRetriever
     */
    private $used_fields_retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->report_artifact_factory  = $this->createMock(ReportArtifactFactory::class);
        $this->table_renderer_retriever = $this->createMock(TableRendererForReportRetriever::class);
        $this->used_fields_retriever    = $this->createMock(UsedFieldsRetriever::class);
        $color_provider                 = $this->createMock(StatusColorForChangesetProvider::class);
        $color_provider->method('provideColor')->willReturn('flamingo-pink');

        $this->representation_builder = new MatchingArtifactRepresentationBuilder(
            $this->report_artifact_factory,
            $this->table_renderer_retriever,
            $this->used_fields_retriever,
            $color_provider,
            ProvideUserAvatarUrlStub::build(),
        );
    }

    public function testItBuildsAnArtifactRepresentationWithFieldsUsedInTableRenderer(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $report  = $this->createMock(Tracker_Report::class);
        $tracker = $this->createMock(Tracker::class);

        $project = new Project([
            'group_id' => 101,
            'group_name' => 'Project 1',
            'unix_group_name' => 'project_1',
            'icon_codepoint' => '',
        ]);

        $tracker->method('getId')->willReturn(52);
        $tracker->method('getName')->willReturn('Tracker01');
        $tracker->method('getColor')->willReturn(ItemColor::default());
        $tracker->method('getProject')->willReturn($project);

        $report->method('getTracker')->willReturn($tracker);

        $renderer_table = new Tracker_Report_Renderer_Table(
            1,
            $report,
            'Table',
            'Table desc',
            0,
            1,
            false
        );

        $this->table_renderer_retriever
            ->method('getTableReportRendererForReport')
            ->with($report)
            ->willReturn([$renderer_table]);

        $field_01       = $this->createMock(Tracker_FormElement_Field::class);
        $field_02       = $this->createMock(Tracker_FormElement_Field::class);
        $field_03       = $this->createMock(Tracker_FormElement_Field::class);
        $field_art_link = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);

        $field_01->method('getRESTValue')->willReturn(
            new ArtifactFieldValueFullRepresentation(
                1,
                'field_type',
                'Field 01',
                'Value 01'
            )
        );
        $field_02->method('getRESTValue')->willReturn(
            new ArtifactFieldValueFullRepresentation(
                2,
                'field_type',
                'Field 02',
                'Value 02'
            )
        );
        $field_03->method('getRESTValue')->willReturn(null);

        $this->used_fields_retriever
            ->method('getUsedFieldsInRendererUserCanSee')
            ->with(
                $user,
                $renderer_table,
            )->willReturn([
                $field_01,
                $field_02,
                $field_03,
                $field_art_link,
            ]);

        $artifact = $this->createMock(Artifact::class);
        $this->report_artifact_factory
            ->method('getArtifactsMatchingReport')
            ->with(
                $report,
                10,
                0
            )->willReturn(
                new ArtifactMatchingReportCollection(
                    [$artifact],
                    1
                )
            );

        $artifact->method('userCanView')->with($user)->willReturn(true);
        $artifact->method('getLastChangeset')->willReturn(
            $this->createMock(Tracker_Artifact_Changeset::class)
        );
        $artifact->method('getId')->willReturn(895);
        $artifact->method('getAssignedTo')->willReturn([]);
        $artifact->method('getXRef')->willReturn('xref');
        $artifact->method('getTracker')->willReturn($tracker);

        $submitter_user = UserTestBuilder::aUser()
            ->withId(245)
            ->withUserName('user_02')
            ->withRealName('User 02')
            ->build();
        $artifact->method('getSubmittedBy')->willReturn(245);
        $artifact->method('getSubmittedByUser')->willReturn($submitter_user);
        $artifact->method('getSubmittedOn')->willReturn(1646007489);
        $artifact->method('getUri')->willReturn('uri');
        $artifact->method('getLastUpdateDate')->willReturn(1647007489);
        $artifact->method('getStatus')->willReturn('');
        $artifact->method('isOpen')->willReturn(false);
        $artifact->method('getTitle')->willReturn('Artifact 01');

        $artifact_representations = $this->representation_builder->buildMatchingArtifactRepresentationCollection(
            $user,
            $report,
            null,
            10,
            0,
        );

        self::assertCount(1, $artifact_representations->getArtifactRepresentations());
        self::assertSame(1, $artifact_representations->getTotalSize());

        $artifact_representation = $artifact_representations->getArtifactRepresentations()[0];

        self::assertCount(2, $artifact_representation->values);
    }

    public function testItThrowsAnExceptionIfReportDoesNotHaveTableRenderer(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $report = $this->createMock(Tracker_Report::class);

        $this->table_renderer_retriever
            ->method('getTableReportRendererForReport')
            ->with($report)
            ->willReturn([]);

        $this->expectException(RestException::class);
        $this->expectExceptionMessage('The report does not have a table renderer');

        $this->representation_builder->buildMatchingArtifactRepresentationCollection(
            $user,
            $report,
            null,
            10,
            0,
        );
    }

    public function testItThrowsAnExceptionIfReportHaveMultipleTableRenderers(): void
    {
        $user   = UserTestBuilder::aUser()->build();
        $report = $this->createMock(Tracker_Report::class);

        $renderer_table_01 = new Tracker_Report_Renderer_Table(
            1,
            $report,
            'Table 01',
            'Table 01 desc',
            0,
            1,
            false
        );

        $renderer_table_02 = new Tracker_Report_Renderer_Table(
            2,
            $report,
            'Table 02',
            'Table 02 desc',
            1,
            1,
            false
        );

        $this->table_renderer_retriever
            ->method('getTableReportRendererForReport')
            ->with($report)
            ->willReturn([
                $renderer_table_01,
                $renderer_table_02,
            ]);

        $this->expectException(RestException::class);
        $this->expectExceptionMessage('The report has more than one table renderer');

        $this->representation_builder->buildMatchingArtifactRepresentationCollection(
            $user,
            $report,
            null,
            10,
            0,
        );
    }
}
