<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation;

class TrackerFieldPatchRepresentationTestBuilder
{
    private ?string $name        = null;
    private ?string $label       = null;
    private ?string $description = null;
    private ?bool $use_it        = null;

    private function __construct()
    {
    }

    public static function aPatch(): self
    {
        return new self();
    }

    public function withName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function withUseIt(bool $use_it): self
    {
        $this->use_it = $use_it;

        return $this;
    }

    public function build(): TrackerFieldPatchRepresentation
    {
        return new TrackerFieldPatchRepresentation(
            $this->name,
            $this->label,
            $this->description,
            [],
            $this->use_it,
            null,
        );
    }
}
