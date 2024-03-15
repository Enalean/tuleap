<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration;

final class LegacyMediawikiLanguageRetrieverStub implements LegacyMediawikiLanguageRetriever
{
    private function __construct(private readonly string|false $language)
    {
    }

    public static function withoutLanguage(): self
    {
        return new self(false);
    }

    public static function withLanguage(string $language): self
    {
        return new self($language);
    }

    public function getLanguageFor(int $project_id): string|false
    {
        return $this->language;
    }
}
