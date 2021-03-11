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

use League\CommonMark\Block\Renderer\BlockRendererInterface;

final class CustomBlockRenderer
{
    /**
     * @var string
     */
    private $block_class;
    /**
     * @var BlockRendererInterface
     */
    private $block_renderer;

    public function __construct(string $block_class, BlockRendererInterface $block_renderer)
    {
        $this->block_class    = $block_class;
        $this->block_renderer = $block_renderer;
    }

    public function getBlockRenderer(): BlockRendererInterface
    {
        return $this->block_renderer;
    }

    public function getBlockClass(): string
    {
        return $this->block_class;
    }
}
