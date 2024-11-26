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

use Tuleap\Artidoc\Document\ArtidocDocumentInformation;
use Tuleap\Artidoc\Document\RetrieveArtidoc;
use Tuleap\Artidoc\Domain\Document\Order\ReorderSections;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrderBuilder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class PATCHSectionsHandler
{
    public function __construct(
        private RetrieveArtidoc $retrieve_artidoc,
        private SectionOrderBuilder $section_order_builder,
        private ReorderSections $dao,
    ) {
    }

    /**
     * @return Ok<ArtidocSectionRepresentation>|Err<Fault>
     */
    public function handle(int $id, OrderRepresentation $order, \PFUser $user): Ok|Err
    {
        return $this->retrieve_artidoc
            ->retrieveArtidoc($id, $user)
            ->andThen(fn (ArtidocDocumentInformation $document_information) => $this->ensureThatUserCanWriteDocument($document_information, $user))
            ->andThen(fn (ArtidocDocumentInformation $document_information) => $this->reorder($id, $order));
    }

    /**
     * @return Ok<ArtidocDocumentInformation>|Err<Fault>
     */
    private function ensureThatUserCanWriteDocument(ArtidocDocumentInformation $document_information, \PFUser $user): Ok|Err
    {
        $permissions_manager = \Docman_PermissionsManager::instance($document_information->document->getProjectId());
        if (! $permissions_manager->userCanWrite($user, $document_information->document->getId())) {
            return Result::err(Fault::fromMessage('User cannot write document'));
        }

        return Result::ok($document_information);
    }

    private function reorder(int $id, OrderRepresentation $order): Ok|Err
    {
        return $this->section_order_builder
            ->buildFromRest($order->ids, $order->direction, $order->compared_to)
            ->andThen(fn (SectionOrder $order) => $this->dao->reorder($id, $order));
    }
}
