<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Reference;

/**
 * @psalm-immutable
 */
final class CrossReferenceSectionPresenter
{
    public const UNLABELLED = '';

    /**
     * @var string
     */
    public $label;
    /**
     * @var CrossReferencePresenter[]
     */
    public $cross_references;

    /**
     * @param CrossReferencePresenter[] $cross_references
     */
    public function __construct(string $label, array $cross_references)
    {
        $this->label            = $label;
        $this->cross_references = $cross_references;
    }

    public function withAdditionalCrossReference(CrossReferencePresenter $cross_reference): self
    {
        return new self(
            $this->label,
            array_merge(
                $this->cross_references,
                [$cross_reference]
            )
        );
    }
}
