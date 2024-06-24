<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\ChangesetValue\Text;

use Codendi_HTMLPurifier;
use Tuleap\Markdown\ContentInterpretor;

final readonly class TextValueInterpreter
{
    public function __construct(
        private Codendi_HTMLPurifier $purifier,
        private ContentInterpretor $content_interpreter,
    ) {
    }

    public function interpretValueAccordingToFormat(string $format, string $text_to_interpret, int $project_id): string
    {
        if ($format === \Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            return $this->purifier->purifyHTMLWithReferences($text_to_interpret, $project_id);
        } elseif ($format === \Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT) {
            return $this->content_interpreter->getInterpretedContentWithReferences(
                $text_to_interpret,
                $project_id
            );
        }
        return $this->purifier->purifyTextWithReferences($text_to_interpret, $project_id);
    }
}
