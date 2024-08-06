<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate\Variable;

use Tuleap\Export\Pdf\Template\PdfTemplate;

final readonly class VariableMisusageInTemplateDetector
{
    public function __construct(private VariableMisusageCollector $collector)
    {
    }

    /**
     * @return list<string>
     */
    public function detectVariableMisusages(PdfTemplate $template): array
    {
        $misusages_in_title_page = $this->collector->getMisusages($template->title_page_content);
        $misusages_in_header     = $this->collector->getMisusages($template->header_content);
        $misusages_in_footer     = $this->collector->getMisusages($template->footer_content);

        return [
            ...$this->prefix(dgettext('tuleap-pdftemplate', 'Cover page: %s'), $misusages_in_title_page),
            ...$this->prefix(dgettext('tuleap-pdftemplate', 'Header: %s'), $misusages_in_header),
            ...$this->prefix(dgettext('tuleap-pdftemplate', 'Footer: %s'), $misusages_in_footer),
        ];
    }

    /**
     * @param list<string> $misusages
     * @return list<string>
     */
    private function prefix(string $prefix, array $misusages): array
    {
        return array_map(static fn (string $misusage) => sprintf($prefix, $misusage), $misusages);
    }
}
