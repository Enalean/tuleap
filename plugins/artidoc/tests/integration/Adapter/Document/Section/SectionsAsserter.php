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

use Tuleap\Artidoc\Adapter\Document\Section\Freetext\Identifier\UUIDFreetextIdentifierFactory;
use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\ArtidocWithContext;
use Tuleap\Artidoc\Domain\Document\Section\Freetext\RetrievedSectionContentFreetext;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\NeverThrow\Result;
use function PHPUnit\Framework\assertSame;

/**
 * @psalm-immutable
 */
final readonly class SectionsAsserter
{
    /**
     * @param list<int|string> $expected_content
     */
    public static function assertSectionsForDocument(
        ArtidocWithContext $artidoc,
        array $expected_content,
    ): void {
        $dao = new RetrieveArtidocSectionDao(
            new UUIDSectionIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory()),
            new UUIDFreetextIdentifierFactory(new \Tuleap\DB\DatabaseUUIDV7Factory()),
        );

        $paginated_retrieved_sections = $dao->searchPaginatedRetrievedSections($artidoc, 50, 0);

        assertSame(count($expected_content), $paginated_retrieved_sections->total);
        assertSame($expected_content, array_map(
            self::getContentForAssertion(...),
            $paginated_retrieved_sections->rows,
        ));
    }

    private static function getContentForAssertion(RetrievedSection $retrieved_sections): int|string|null
    {
        return $retrieved_sections->content->apply(
            static fn (int $id) => Result::ok($id),
            static fn (RetrievedSectionContentFreetext $freetext) => Result::ok($freetext->content->title),
        )->unwrapOr(null);
    }
}
