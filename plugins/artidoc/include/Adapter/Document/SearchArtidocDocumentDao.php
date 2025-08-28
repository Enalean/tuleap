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
use Tuleap\DB\DataAccessObject;

final class SearchArtidocDocumentDao extends DataAccessObject implements SearchArtidocDocument
{
    #[Override]
    public function searchByItemId(int $item_id): ?array
    {
        return $this->getDB()->row(
            <<<EOS
            SELECT *
            FROM plugin_docman_item
            WHERE item_id = ?
              AND item_type = ?
              AND other_type = ?
              AND delete_date IS NULL
            EOS,
            $item_id,
            \Docman_Item::TYPE_OTHER,
            ArtidocDocument::TYPE,
        );
    }
}
