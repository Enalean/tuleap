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

namespace Tuleap\Artidoc\Adapter\Document\Section;

use Override;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Order\Direction;
use Tuleap\Artidoc\Domain\Document\Order\ReorderSections;
use Tuleap\Artidoc\Domain\Document\Order\SectionOrder;
use Tuleap\Artidoc\Domain\Document\Order\UnableToReorderSectionOutsideOfDocumentFault;
use Tuleap\Artidoc\Domain\Document\Order\UnknownSectionToMoveFault;
use Tuleap\DB\DataAccessObject;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class ReorderSectionsDao extends DataAccessObject implements ReorderSections
{
    #[Override]
    public function reorder(ArtidocWithContext $artidoc, SectionOrder $order): Ok|Err
    {
        return $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($artidoc, $order): Ok|Err {
            $current_order = array_values($db->col(
                'SELECT id
                FROM plugin_artidoc_section AS section
                    INNER JOIN plugin_artidoc_section_version AS section_version
                        ON (section.id = section_version.section_id)
                WHERE item_id = ?
                ORDER BY `rank`',
                0,
                $artidoc->document->getId(),
            ));

            $index_to_move = array_search($order->identifier->getBytes(), $current_order, true);
            if ($index_to_move === false) {
                return Result::err(UnknownSectionToMoveFault::build());
            }

            array_splice($current_order, $index_to_move, 1);

            $index_compared_to = array_search($order->compared_to->getBytes(), $current_order, true);
            if ($index_compared_to === false) {
                return Result::err(UnableToReorderSectionOutsideOfDocumentFault::build());
            }
            if ($order->direction === Direction::Before) {
                if ($index_compared_to === 0) {
                    array_unshift($current_order, $order->identifier->getBytes());
                } else {
                    array_splice($current_order, $index_compared_to, 0, [$order->identifier->getBytes()]);
                }
            } else {
                if ($index_compared_to === count($current_order) - 1) {
                    $current_order[] = $order->identifier->getBytes();
                } else {
                    array_splice($current_order, $index_compared_to + 1, 0, [$order->identifier->getBytes()]);
                }
            }

            $when   = '';
            $values = [];
            foreach ($current_order as $index => $value) {
                $when    .= ' WHEN id = ? THEN ? ';
                $values[] = $value;
                $values[] = $index;
            }

            $sql = <<<EOS
                UPDATE plugin_artidoc_section AS section
                    INNER JOIN plugin_artidoc_section_version AS section_version
                        ON (section.id = section_version.section_id)
                SET `rank` = CASE $when ELSE `rank` END
                WHERE item_id = ?
                EOS;
            $db->safeQuery($sql, [...$values, $artidoc->document->getId()]);

            return Result::ok(null);
        });
    }
}
