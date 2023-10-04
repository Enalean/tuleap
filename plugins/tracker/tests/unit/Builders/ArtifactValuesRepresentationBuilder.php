<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;

final class ArtifactValuesRepresentationBuilder
{
    private mixed $value = null;
    /**
     * @var list<int | string>|null
     */
    private ?array $bind_value_ids = null;
    private ?float $manual_value   = null;
    /**
     * @var list<array>|null
     */
    private ?array $links            = null;
    private ?int $parent_artifact_id = null;
    /**
     * @var LinkWithDirectionRepresentation[]|null
     */
    private ?array $all_links = null;

    private function __construct(private int $field_id)
    {
    }

    public static function aRepresentation(int $field_id): self
    {
        return new self($field_id);
    }

    public function withValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param list<int|string> ...$other_bind_value_ids
     * @no-named-arguments
     */
    public function withBindValueIds(int|string $first_bind_value_id, array ...$other_bind_value_ids): self
    {
        $this->bind_value_ids = [$first_bind_value_id, ...$other_bind_value_ids];
        return $this;
    }

    public function withManualValue(float $manual_value): self
    {
        $this->manual_value = $manual_value;
        return $this;
    }

    /**
     * @param list<array> $other_links
     * @no-named-arguments
     */
    public function withLinks(array $first_link, array ...$other_links): self
    {
        $this->links = [$first_link, ...$other_links];
        return $this;
    }

    public function withParent(int $parent_artifact_id): self
    {
        $this->parent_artifact_id = $parent_artifact_id;
        return $this;
    }

    /**
     * @no-named-arguments
     */
    public function withAllLinks(LinkWithDirectionRepresentation $first_link, LinkWithDirectionRepresentation ...$other_links): self
    {
        $this->all_links = [$first_link, ...$other_links];
        return $this;
    }

    public function build(): ArtifactValuesRepresentation
    {
        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = $this->field_id;
        if ($this->value !== null) {
            $representation->value = $this->value;
        }
        if ($this->bind_value_ids) {
            $representation->bind_value_ids = $this->bind_value_ids;
        }
        if ($this->manual_value !== null) {
            $representation->manual_value    = $this->manual_value;
            $representation->is_autocomputed = false;
        }
        if ($this->links) {
            $representation->links = $this->links;
        }
        if ($this->parent_artifact_id !== null) {
            $representation->parent = ['id' => $this->parent_artifact_id];
        }
        if ($this->all_links) {
            $representation->all_links = $this->all_links;
        }
        return $representation;
    }
}
