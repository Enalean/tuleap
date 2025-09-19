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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\REST\v1\LinkWithDirectionRepresentation;

final class LinkWithDirectionRepresentationBuilder
{
    /**
     * @param positive-int $id
     */
    public function __construct(
        private int $id,
        private LinkDirection $direction,
        private ?string $type = ArtifactLinkField::DEFAULT_LINK_TYPE,
    ) {
    }

    /**
     * @param positive-int $id
     */
    public static function aReverseLink(int $id): self
    {
        return new self($id, LinkDirection::REVERSE);
    }

    /**
     * @param positive-int $id
     */
    public static function aReverseLinkWithNullType(int $id): LinkWithDirectionRepresentation
    {
        return (new self($id, LinkDirection::REVERSE, null))->build();
    }

    /**
     * @param positive-int $id
     */
    public static function aForwardLink(int $id): self
    {
        return new self($id, LinkDirection::FORWARD);
    }

    public function withType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function build(): LinkWithDirectionRepresentation
    {
        return new LinkWithDirectionRepresentation($this->id, $this->direction->value, $this->type);
    }
}
