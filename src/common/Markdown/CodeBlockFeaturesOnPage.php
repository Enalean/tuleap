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

final class CodeBlockFeaturesOnPage implements CodeBlockFeatures
{
    /**
     * @var bool
     */
    private $is_mermaid_needed = false;

    /**
     * @var self|null
     */
    private static $instance;

    public static function getInstance(): self
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    public function needsMermaid(): void
    {
        $this->is_mermaid_needed = true;
    }

    public function isMermaidNeeded(): bool
    {
        return $this->is_mermaid_needed;
    }
}
