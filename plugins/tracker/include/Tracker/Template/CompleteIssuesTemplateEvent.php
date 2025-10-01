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

namespace Tuleap\Tracker\Template;

use Tuleap\Event\Dispatchable;
use Tuleap\Tracker\Report\Renderer\XML\XMLRenderer;

final class CompleteIssuesTemplateEvent implements Dispatchable
{
    public const NAME = 'completeIssuesTemplate';

    /**
     * @param XMLRenderer[] $all_issues_renderers
     * @param XMLRenderer[] $my_issues_renderers
     * @param XMLRenderer[] $open_issues_renderers
     */
    public function __construct(
        private array $all_issues_renderers,
        private array $my_issues_renderers,
        private array $open_issues_renderers,
    ) {
    }

    /**
     * @return XMLRenderer[]
     */
    public function getAllIssuesRenderers(): array
    {
        return $this->all_issues_renderers;
    }

    /**
     * @return XMLRenderer[]
     */
    public function getMyIssuesRenderers(): array
    {
        return $this->my_issues_renderers;
    }

    /**
     * @return XMLRenderer[]
     */
    public function getOpenIssuesRenderers(): array
    {
        return $this->open_issues_renderers;
    }

    public function addAllIssuesRenderers(XMLRenderer ...$renderers): void
    {
        $this->all_issues_renderers = array_merge($this->all_issues_renderers, $renderers);
    }

    public function addMyIssuesRenderers(XMLRenderer ...$renderers): void
    {
        $this->my_issues_renderers = array_merge($this->my_issues_renderers, $renderers);
    }

    public function addOpenIssuesRenderers(XMLRenderer ...$renderers): void
    {
        $this->open_issues_renderers = array_merge($this->open_issues_renderers, $renderers);
    }
}
