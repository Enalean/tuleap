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

namespace Tuleap\PdfTemplate\Admin;

use Tuleap\Export\Pdf\Template\PdfTemplate;

/**
 * @psalm-immutable
 */
final readonly class UpdateTemplateRequest
{
    public function __construct(
        public PdfTemplate $original,
        public PdfTemplate $submitted,
    ) {
    }

    /**
     * @return string[]
     */
    public function getChangedFields(): array
    {
        $changes = [];
        if ($this->submitted->label !== $this->original->label) {
            $changes[] = 'label';
        }
        if ($this->submitted->description !== $this->original->description) {
            $changes[] = 'description';
        }
        if ($this->submitted->user_style !== $this->original->user_style) {
            $changes[] = 'style';
        }
        if ($this->submitted->title_page_content !== $this->original->title_page_content) {
            $changes[] = 'title_page_content';
        }
        if ($this->submitted->header_content !== $this->original->header_content) {
            $changes[] = 'header_content';
        }
        if ($this->submitted->footer_content !== $this->original->footer_content) {
            $changes[] = 'footer_content';
        }

        return $changes;
    }
}
