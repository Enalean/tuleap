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

namespace Tuleap\Tracker\Artifact;

use Tuleap\Markdown\CodeBlockFeatures;
use Tuleap\Markdown\CodeBlockFeaturesInterface;

final class CodeBlockFeaturesOnArtifact implements CodeBlockFeaturesInterface
{
    /**
     * @var CodeBlockFeaturesInterface
     */
    private $code_block_features;

    private function __construct(CodeBlockFeaturesInterface $code_block_features)
    {
        $this->code_block_features = $code_block_features;
    }

    /**
     * @var self|null
     */
    private static $instance;

    public static function getInstance(): self
    {
        if (! self::$instance) {
            self::$instance = new self(new CodeBlockFeatures());
        }

        return self::$instance;
    }

    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    #[\Override]
    public function needsMermaid(): void
    {
        $this->code_block_features->needsMermaid();
    }

    #[\Override]
    public function isMermaidNeeded(): bool
    {
        return $this->code_block_features->isMermaidNeeded();
    }

    #[\Override]
    public function needsSyntaxHighlight(): void
    {
        $this->code_block_features->needsSyntaxHighlight();
    }

    #[\Override]
    public function isSyntaxHighlightNeeded(): bool
    {
        return $this->code_block_features->isSyntaxHighlightNeeded();
    }
}
