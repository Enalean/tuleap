<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\SVN\Commit;

use Psr\Log\NullLogger;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Repository\Repository;
use Tuleap\Test\PHPUnit\TestCase;

final class ImmutableTagCommitValidatorTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&ImmutableTagFactory
     */
    private $immutable_tag_factory;
    private ImmutableTagCommitValidator $immutable_tag_validator;

    protected function setUp(): void
    {
        $this->immutable_tag_factory   = $this->createStub(ImmutableTagFactory::class);
        $this->immutable_tag_validator = new ImmutableTagCommitValidator(new NullLogger(), $this->immutable_tag_factory);
    }

    public function testImmutableTagWithAPercentCharacterInPathIsCorrectlyProcessed(): void
    {
        $repository = $this->createStub(Repository::class);
        $this->immutable_tag_factory->method('getByRepositoryId')->willReturn(new ImmutableTag($repository, 'foo%bar', 'something%else'));

        $this->expectNotToPerformAssertions();

        $this->immutable_tag_validator->assertPathIsValid($repository, 'txn', 'my/path');
    }
}
