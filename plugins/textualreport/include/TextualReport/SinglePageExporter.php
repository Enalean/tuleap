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

use PFUser;
use TemplateRenderer;
use Tracker;

class SinglePageExporter
{
    /**
     * @var TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var SinglePagePresenterBuilder
     */
    private $presenter_builder;

    public function __construct(SinglePagePresenterBuilder $presenter_builder, TemplateRenderer $template_renderer)
    {
        $this->template_renderer = $template_renderer;
        $this->presenter_builder = $presenter_builder;
    }

    /**
     * @param array   $ordered_artifact_rows
     * @param string  $server_url
     */
    public function exportAsSinglePage(
        Tracker $tracker,
        array $ordered_artifact_rows,
        PFUser $current_user,
        $server_url
    ) {
        $this->sendFileDownloadHeaders($tracker);

        $this->template_renderer->renderToPage(
            'single-page',
            $this->presenter_builder->exportAsSinglePage($ordered_artifact_rows, $current_user, $server_url)
        );
        die();
    }

    private function sendFileDownloadHeaders(Tracker $tracker)
    {
        $file_name = str_replace(' ', '_', 'artifact_' . $tracker->getItemName());
        $file_name .= '_' . $tracker->getProject()->getUnixName() . '.html';
        header('Content-Disposition: attachment; filename="' . $this->purifyFileName($file_name) . '"');
        header('Content-type: text/html');
        header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'none\'; form-action \'none\'');
        header('X-DNS-Prefetch-Control: off');
    }

    /**
     * @return string
     */
    private function purifyFileName($file_name)
    {
        return str_replace('"', '\\"', $this->removeNonPrintableASCIIChars($file_name));
    }

    /**
     * @return string
     */
    private function removeNonPrintableASCIIChars($str)
    {
        return preg_replace('/[^(\x20-\x7F)]*/', '', $str);
    }
}
