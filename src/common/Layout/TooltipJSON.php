<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Layout;

/**
 * @psalm-immutable
 */
final class TooltipJSON
{
    private function __construct(
        public readonly string $title_as_html,
        public readonly string $body_as_html,
        public readonly string $accent_color,
    ) {
    }

    public static function fromHtmlBody(string $body_as_html): self
    {
        return new self('', $body_as_html, '');
    }

    public static function fromHtmlTitleAndHtmlBody(string $title_as_html, string $body_as_html): self
    {
        return new self($title_as_html, $body_as_html, '');
    }

    public function withAccentColor(string $accent_color): self
    {
        return new self($this->title_as_html, $this->body_as_html, $accent_color);
    }
}
