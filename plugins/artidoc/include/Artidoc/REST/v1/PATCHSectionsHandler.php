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

namespace Tuleap\Artidoc\REST\v1;

use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Order\ReorderSections;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;

final readonly class PATCHSectionsHandler
{
    public function __construct(
        private RetrieveArtidocWithContext $retrieve_artidoc,
        private SectionOrderBuilder $section_order_builder,
        private ReorderSections $dao,
    ) {
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    public function handle(int $id, OrderRepresentation $order): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidocUserCanWrite($id)
            ->andThen(fn (ArtidocWithContext $document_information) => $this->reorder($id, $order));
    }

    private function reorder(int $id, OrderRepresentation $order): Ok|Err
    {
        return $this->section_order_builder
            ->buildFromRest($order->ids, $order->direction, $order->compared_to)
            ->andThen(fn (SectionOrder $order) => $this->dao->reorder($id, $order));
    }
}
