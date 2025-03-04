<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Git;

use Git_Backend_Gitolite;
use Git_GitoliteDriver;
use GitRepositoryCreator;
use Psr\Log\NullLogger;
use Tuleap\Git\Gitolite\GitoliteAccessURLGenerator;
use Tuleap\Git\Tests\Stub\DefaultBranch\DefaultBranchUpdateExecutorStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitRepositoryImplTest extends TestCase
{
    public function testNameIsValid(): void
    {
        $creator = $this->newCreator();
        self::assertFalse($creator->isNameValid(''));
        self::assertFalse($creator->isNameValid('/'));
        self::assertFalse($creator->isNameValid('/jambon'));
        self::assertFalse($creator->isNameValid('jambon/'));
        self::assertTrue($creator->isNameValid('jambon'));
        self::assertTrue($creator->isNameValid('jambon.beurre'));
        self::assertTrue($creator->isNameValid('jambon-beurre'));
        self::assertTrue($creator->isNameValid('jambon_beurre'));
        self::assertFalse($creator->isNameValid('jambon/.beurre'));
        self::assertFalse($creator->isNameValid('jambon..beurre'));
        self::assertFalse($creator->isNameValid('jambon...beurre'));
        self::assertFalse($creator->isNameValid(str_pad('name_with_more_than_255_chars_', 256, '_')));
        self::assertFalse($creator->isNameValid('repo.git'));
        self::assertFalse($creator->isNameValid('u/toto'));
        self::assertTrue($creator->isNameValid('jambon/beurre'));
    }

    public function testItAllowsLettersNumbersDotsUnderscoresSlashesAndDashes(): void
    {
        $creator = $this->newCreator();
        self::assertEquals('a-zA-Z0-9/_.-', $creator->getAllowedCharsInNamePattern());
    }

    private function newCreator(): GitRepositoryCreator
    {
        return new Git_Backend_Gitolite(
            $this->createMock(Git_GitoliteDriver::class),
            $this->createMock(GitoliteAccessURLGenerator::class),
            new DefaultBranchUpdateExecutorStub(),
            new NullLogger(),
        );
    }
}
