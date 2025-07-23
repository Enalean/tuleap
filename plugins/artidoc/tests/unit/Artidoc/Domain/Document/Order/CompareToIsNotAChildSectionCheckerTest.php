<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

use Tuleap\Artidoc\Adapter\Document\Section\Identifier\UUIDSectionIdentifierFactory;
use Tuleap\Artidoc\Domain\Document\Section\Level;
use Tuleap\Artidoc\Domain\Document\Section\RetrievedSection;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CompareToIsNotAChildSectionCheckerTest extends TestCase
{
    private RetrievedSection $section_AA;
    private RetrievedSection $section_AAA;
    private RetrievedSection $section_B;

    #[\Override]
    protected function setUp(): void
    {
        $identifier_factory = new UUIDSectionIdentifierFactory(new DatabaseUUIDV7Factory());

        $this->section_AA  = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::Two->value,
                'item_id'     => 1,
                'artifact_id' => 202,
                'rank'        => 2,
            ]
        );
        $this->section_AAA = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::Three->value,
                'item_id'     => 1,
                'artifact_id' => 203,
                'rank'        => 3,
            ]
        );
        $this->section_B   = RetrievedSection::fromArtifact(
            [
                'id'          => $identifier_factory->buildIdentifier(),
                'level'       => Level::One->value,
                'item_id'     => 1,
                'artifact_id' => 204,
                'rank'        => 4,
            ]
        );
    }

    public function getChecker(): CompareToIsNotAChildSectionChecker
    {
        return new CompareToIsNotAChildSectionChecker();
    }

    public function testCheckThatCompareToIsAChild(): void
    {
        $section_A_children = [$this->section_AA->id, $this->section_AAA->id];

        $result_with_AA = $this->getChecker()->checkCompareToIsNotAChildSection($section_A_children, $this->section_AA->id);
        self::assertTrue(Result::isErr($result_with_AA));

        $result_with_AAA = $this->getChecker()->checkCompareToIsNotAChildSection($section_A_children, $this->section_AAA->id);
        self::assertTrue(Result::isErr($result_with_AAA));
    }

    public function testCheckThatCompareToIsNotAChild(): void
    {
        $section_A_children = [$this->section_AA->id, $this->section_AAA->id];

        $result = $this->getChecker()->checkCompareToIsNotAChildSection($section_A_children, $this->section_B->id);
        self::assertTrue(Result::isOk($result));
    }
}
