<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman;

use CodendiDataAccess;
use Docman_SqlFilter;
use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SqlFilterChoiceTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        $data_access = $this->createMock(LegacyDataAccessInterface::class);
        $data_access->method('quoteLikeValueSurround')->willReturnCallback(static fn(string $value) => match ($value) {
            'codex'   => '"%codex%"',
            'c*od*ex' => '"%c*od*ex%"',
        });
        $data_access->method('quoteLikeValuePrefix')->willReturnCallback(static fn(string $value) => match ($value) {
            'codex'  => '"%codex"',
            '*codex' => '"%*codex"',
        });
        CodendiDataAccess::setInstance($data_access);
    }

    #[\Override]
    public function tearDown(): void
    {
        CodendiDataAccess::clearInstance();
    }

    public function testItTestSqlFilterChoicePerPattern(): void
    {
        $docmanSf = $this->createPartialMock(Docman_SqlFilter::class, []);

        self::assertEquals(['like' => true, 'pattern' => '"%codex%"'], $docmanSf->getSearchType('*codex*'));
        self::assertEquals(['like' => true, 'pattern' => '"%c*od*ex%"'], $docmanSf->getSearchType('*c*od*ex*'));
        self::assertEquals(['like' => true, 'pattern' => '"%codex"'], $docmanSf->getSearchType('*codex'));
        self::assertEquals(['like' => true, 'pattern' => '"%*codex"'], $docmanSf->getSearchType('**codex'));
        self::assertEquals(['like' => false], $docmanSf->getSearchType('codex*'));
        self::assertEquals(['like' => false], $docmanSf->getSearchType('cod*ex*'));
        self::assertEquals(['like' => false], $docmanSf->getSearchType('codex'));
    }
}
