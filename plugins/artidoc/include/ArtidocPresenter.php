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

namespace Tuleap\Artidoc;

use Tuleap\Artidoc\Document\Tracker\DocumentTrackerRepresentation;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use function Psl\Json\encode;

final readonly class ArtidocPresenter
{
    public string $allowed_trackers;
    public string $selected_tracker;
    public string $pdf_templates;
    public string $selected_readonly_fields;

    /**
     * @param list<DocumentTrackerRepresentation> $allowed_trackers
     * @param list<PdfTemplate> $pdf_templates
     */
    public function __construct(
        public int $item_id,
        public int $project_id,
        public bool $can_user_edit_document,
        public string $title,
        ?DocumentTrackerRepresentation $selected_tracker,
        array $allowed_trackers,
        array $selected_readonly_fields,
        public int $upload_max_size,
        ?array $pdf_templates,
        public string $relative_date_display,
        public bool $can_user_display_versions,
    ) {
        $this->selected_tracker         = encode($selected_tracker);
        $this->allowed_trackers         = encode($allowed_trackers);
        $this->pdf_templates            = encode($pdf_templates);
        $this->selected_readonly_fields = encode($selected_readonly_fields);
    }
}
