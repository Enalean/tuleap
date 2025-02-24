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

namespace Tuleap\PdfTemplate\Admin\Image;

use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\PdfTemplate\Image\PdfTemplateImage;
use Tuleap\PdfTemplate\Image\PdfTemplateImageHrefBuilder;
use Tuleap\PdfTemplate\RetrieveAllTemplates;

final readonly class UsageDetector
{
    public function __construct(
        private RetrieveAllTemplates $retriever,
        private PdfTemplateImageHrefBuilder $href_builder,
    ) {
    }

    /**
     * @return list<PdfTemplate>
     */
    public function getUsages(PdfTemplateImage $image): array
    {
        $templates = $this->retriever->retrieveAll();

        $usages = [];
        foreach ($templates as $template) {
            if ($this->doesTemplateSeemToUseThisImage($template, $image)) {
                $usages[] = $template;
            }
        }

        return $usages;
    }

    private function doesTemplateSeemToUseThisImage(PdfTemplate $template, PdfTemplateImage $image): bool
    {
        $href = $this->href_builder->getImageHref($image);

        return strpos($template->header_content, $href) !== false
            || strpos($template->footer_content, $href) !== false
            || strpos($template->user_style, $href) !== false;
    }
}
