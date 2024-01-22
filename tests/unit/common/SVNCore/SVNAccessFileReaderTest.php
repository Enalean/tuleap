<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\SVNCore;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class SVNAccessFileReaderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private Repository $repository;
    private SVNAccessFileReader $reader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = RepositoryStub::buildSelf(ProjectTestBuilder::aProject()->build())->withSystemPath(__DIR__ . '/_fixtures/svn_plugin/101/foo');

        $default_block_generator = new class implements SVNAccessFileDefaultBlockGeneratorInterface {
            public function getDefaultBlock(Repository $repository): SVNAccessFileDefaultBlock
            {
                return new SVNAccessFileDefaultBlock(<<<EOT

                le default

                EOT);
            }
        };

        $this->reader = new SVNAccessFileReader($default_block_generator);
    }

    public function testItReadsTheDefaultBlock(): void
    {
        self::assertMatchesRegularExpression(
            '/le default/',
            $this->reader->readDefaultBlock($this->repository)
        );
    }

    public function testItReadsTheContentBlock(): void
    {
        self::assertMatchesRegularExpression(
            '/le content/',
            $this->reader->readContentBlock($this->repository)
        );
    }

    public function testItDoesNotContainDelimiters(): void
    {
        self::assertDoesNotMatchRegularExpression(
            '/# BEGIN CODENDI DEFAULT SETTINGS/',
            $this->reader->readDefaultBlock($this->repository)
        );
    }
}
