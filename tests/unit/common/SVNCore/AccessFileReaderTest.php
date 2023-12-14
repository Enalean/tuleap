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

use Project;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class AccessFileReaderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private Repository $repository;
    private AccessFileReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $fixtures_dir = __DIR__ . '/_fixtures';

        \ForgeConfig::set('sys_data_dir', $fixtures_dir);

        $this->repository = SvnRepository::buildActiveRepository(-1, 'foo', ProjectTestBuilder::aProject()->build());

        $default_block_generator = new class implements SvnAccessFileDefaultBlockGeneratorInterface {
            public function getDefaultBlock(Project $project): SvnAccessFileDefaultBlock
            {
                return new SvnAccessFileDefaultBlock(<<<EOT

                le default

                EOT);
            }
        };

        $this->reader = new AccessFileReader($default_block_generator);
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
