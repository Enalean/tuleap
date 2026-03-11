<?php
/**
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Codendi_HTMLPurifier;

final readonly class ChartConfigurationWarningWithLinks implements ChartConfigurationWarningInterface
{
    /**
     * @param ChartConfigurationWarningLink[] $links
     */
    private function __construct(public string $message, public array $links)
    {
    }

    public static function fromMessageAndLinks(string $message, ChartConfigurationWarningLink $first_link, ChartConfigurationWarningLink ...$other_links): self
    {
        return new self($message, [$first_link, ...$other_links]);
    }

    #[\Override]
    public function getAsHTML(): string
    {
        $purified_message = Codendi_HTMLPurifier::instance()->purify($this->message);
        $html_links       = implode(
            ', ',
            array_map(
                static fn ($link) => $link->getAsHTML(),
                $this->links,
            ),
        );

        return "<li>$purified_message $html_links</li>";
    }
}
