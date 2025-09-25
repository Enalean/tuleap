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

namespace Tuleap\Tracker\Report\Renderer\Table;

use HTTPRequest;
use Override;
use PFUser;
use Tracker_Report;
use Tracker_Report_Renderer;
use Tracker_Report_Renderer_Table;
use TrackerManager;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Project\MappingRegistry;
use Tuleap\Test\PHPUnit\TestCase;
use Widget;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TableRendererForReportRetrieverTest extends TestCase
{
    public function testItReturnsTheTableRenderersOfAReport(): void
    {
        $retriever = new TableRendererForReportRetriever();
        $report    = $this->createMock(Tracker_Report::class);

        $table_renderer   = new Tracker_Report_Renderer_Table(
            1,
            $report,
            'Table',
            'Table desc',
            0,
            1,
            false
        );
        $another_renderer = new class extends Tracker_Report_Renderer {
            public function __construct()
            {
                //override construct
            }

            #[\Override]
            public function getIcon()
            {
                // TODO: Implement getIcon() method.
            }

            #[\Override]
            public function delete()
            {
                // TODO: Implement delete() method.
            }

            #[Override]
            public function fetch(array $matching_ids, HTTPRequest $request, bool $report_can_be_modified, PFUser $user): string
            {
                // TODO: Implement fetch() method.
            }

            #[\Override]
            public function processRequest(TrackerManager $tracker_manager, $request, PFUser $current_user)
            {
                // TODO: Implement processRequest() method.
            }

            #[\Override]
            public function fetchWidget(PFUser $user, Widget $widget): string
            {
                // TODO: Implement fetchWidget() method.
            }

            #[\Override]
            public function getType()
            {
                return 'whatever';
            }

            #[\Override]
            public function initiateSession()
            {
                // TODO: Implement initiateSession() method.
            }

            #[\Override]
            public function update()
            {
                // TODO: Implement update() method.
            }

            #[\Override]
            public function afterSaveObject(Tracker_Report_Renderer $renderer)
            {
                // TODO: Implement afterSaveObject() method.
            }

            #[\Override]
            public function create()
            {
                // TODO: Implement create() method.
            }

            #[\Override]
            public function duplicate($from_report_id, $field_mapping, MappingRegistry $mapping_registry): void
            {
                // TODO: Implement duplicate() method.
            }

            #[\Override]
            public function getJavascriptDependencies()
            {
                // TODO: Implement getJavascriptDependencies() method.
            }

            #[\Override]
            public function getStylesheetDependencies(): CssAssetCollection
            {
                return new CssAssetCollection([]);
            }
        };

        $report->method('getRenderers')->willReturn(
            [
                $table_renderer,
                $another_renderer,
            ]
        );

        $renderers = $retriever->getTableReportRendererForReport($report);

        self::assertCount(1, $renderers);
        self::assertSame($table_renderer, $renderers[0]);
    }
}
