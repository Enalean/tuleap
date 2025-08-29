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

namespace Tuleap\Artidoc\Adapter\Document;

use Override;
use Tuleap\Artidoc\Document\SearchArtidocDocument;
use Tuleap\Artidoc\Domain\Document\Artidoc;
use Tuleap\Artidoc\Domain\Document\RetrieveArtidoc;
use Tuleap\Docman\Item\GetItemFromRow;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final readonly class ArtidocRetriever implements RetrieveArtidoc
{
    public function __construct(
        private SearchArtidocDocument $dao,
        private GetItemFromRow $item_factory,
    ) {
    }

    #[Override]
    public function retrieveArtidoc(int $id): Ok|Err
    {
        $row = $this->dao->searchByItemId($id);
        if ($row === null || count($row) === 0) {
            return Result::err(Fault::fromMessage('Unable to find document'));
        }

        $item = $this->item_factory->getItemFromRow($row);
        if (! $item instanceof Artidoc) {
            return Result::err(Fault::fromMessage('Item is not an artidoc document'));
        }

        /**
         * Ideally we should just have to `return Result::ok($item);` but
         * the intersection of a Docman_Item and Artidoc does not play well with the type inference
         */
        return $this->wrap($item);
    }

    private function wrap(Artidoc $item): Ok
    {
        return Result::ok($item);
    }
}
