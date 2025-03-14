<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Domain\Document\Order;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

final readonly class ChangeSectionOrder
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private ReorderSections $reorder_sections,
        private SectionChildrenBuilder $section_children_builder,
        private CompareToIsNotAChildSectionChecker $compare_to_checker,
    ) {
    }

    /**
     * @return Ok<void>|Err<Fault>
     */
    public function reorder(int $id, SectionOrder $order): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanWrite($id)
            ->andThen(function (ArtidocWithContext $artidoc) use ($order) {
                return $this->section_children_builder->getSectionChildren($order->identifier, $artidoc)
                    ->andThen(fn (array $children) => $this->compare_to_checker->checkCompareToIsNotAChildSection($children, $order->compared_to))
                    ->andThen(function (array $children) use ($artidoc, $order) {
                        $previous_section = $order->identifier;
                        return $this->reorder_sections->reorder($artidoc, $order)
                            ->map(function () use ($previous_section, $children, $artidoc) {
                                foreach ($children as $child_section_id) {
                                    $child_order      = SectionOrder::build($child_section_id, Direction::After, $previous_section);
                                    $previous_section = $child_section_id;
                                    $this->reorder_sections->reorder($artidoc, $child_order);
                                }
                            });
                    });
            });
    }
}
