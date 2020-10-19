<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestPlan\TestDefinition;

/**
 * @psalm-immutable
 */
final class BacklogItemTestCreationUpdateInformationLinkPresenter
{
    /**
     * @var string
     */
    public $uri;
    /**
     * @var string
     */
    public $color_name;
    /**
     * @var string
     */
    public $xref;
    /**
     * @var string
     */
    public $title;

    private function __construct(string $uri, string $color_name, string $xref, string $title)
    {
        $this->uri        = $uri;
        $this->color_name = $color_name;
        $this->xref       = $xref;
        $this->title      = $title;
    }

    public static function fromArtifact(\Tuleap\Tracker\Artifact\Artifact $artifact): self
    {
        return new self(
            $artifact->getUri(),
            $artifact->getTracker()->getColor()->getName(),
            $artifact->getXRef(),
            $artifact->getTitle() ?? ''
        );
    }
}
