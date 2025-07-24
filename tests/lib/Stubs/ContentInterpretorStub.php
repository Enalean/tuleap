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
    private function __construct(private string $return_value)
    {
    }

    public static function withInterpretedText(string $interpreted_text): self
    {
        return new self($interpreted_text);
    }

    #[\Override]
    public function getInterpretedContent(string $content): string
    {
        return $this->return_value;
    }

    #[\Override]
    public function getInterpretedContentWithReferences(string $content, int $project_id): string
    {
        return $this->return_value;
    }

    #[\Override]
    public function getContentStrippedOfTags(string $content): string
    {
        return $this->return_value;
    }
}
