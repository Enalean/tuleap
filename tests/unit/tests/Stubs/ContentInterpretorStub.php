<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs;

use Tuleap\Markdown\ContentInterpretor;

final class ContentInterpretorStub implements ContentInterpretor
{
    private function __construct(private $interpreted_content_count, private $interpreted_content_with_references_count, private $interpreted_stripped_of_tag_content_count)
    {
    }

    public static function build(): self
    {
        return new self(0, 0, 0);
    }

    public function getInterpretedContent(string $content): string
    {
        $this->interpreted_content_count++;
        return "";
    }

    public function getInterpretedContentWithReferences(string $content, int $project_id): string
    {
        $this->interpreted_content_with_references_count++;
        return "";
    }

    public function getContentStrippedOfTags(string $content): string
    {
        $this->interpreted_stripped_of_tag_content_count++;
        return "";
    }

    public function getInterpretedContentWithReferencesCount(): int
    {
        return $this->interpreted_content_with_references_count;
    }
}
