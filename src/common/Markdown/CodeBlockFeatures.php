<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Markdown;

final class CodeBlockFeatures implements CodeBlockFeaturesInterface
{
    /**
     * @var bool
     */
    private $is_mermaid_needed = false;

    /**
     * @var bool
     */
    private $is_syntax_highlight_needed = false;

    #[\Override]
    public function needsMermaid(): void
    {
        $this->is_mermaid_needed = true;
    }

    #[\Override]
    public function isMermaidNeeded(): bool
    {
        return $this->is_mermaid_needed;
    }

    #[\Override]
    public function needsSyntaxHighlight(): void
    {
        $this->is_syntax_highlight_needed = true;
    }

    #[\Override]
    public function isSyntaxHighlightNeeded(): bool
    {
        return $this->is_syntax_highlight_needed;
    }
}
