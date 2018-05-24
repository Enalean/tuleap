<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TextualReport;

use TemplateRenderer;
use Tuleap\Tracker\Report\Renderer\Table\GetExportOptionsMenuItemsEvent;

class ExportOptionsMenuItemsAppender
{
    const EXPORT_SINGLE_PAGE = 'export_single_page';
    /**
     * @var TemplateRenderer
     */
    private $renderer;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function appendTextualReportDownloadLink(GetExportOptionsMenuItemsEvent $event)
    {
        $export_single_page_url = TRACKER_BASE_URL . '/?' .
            http_build_query(
                [
                    'report'         => $event->getReport()->getId(),
                    'renderer'       => $event->getRendererTable()->getId(),
                    'func'           => 'renderer',
                    'renderer_table' => [
                        'export'                 => 1,
                        self::EXPORT_SINGLE_PAGE => 1,
                    ],
                ]
            );

        $event->addExportItem(
            $this->renderer->renderToString(
                'export-options-menu-items',
                [
                    'export_single_page_url' => $export_single_page_url,
                ]
            )
        );
    }
}
