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

namespace Tuleap\ProgramManagement\Templates;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\XML\ConsistencyChecker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PortfolioTemplateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private vfsStreamDirectory $temp_dir;

    protected function setUp(): void
    {
        $this->temp_dir = vfsStream::setup();
        \ForgeConfig::set('codendi_cache_dir', $this->temp_dir->url());
    }

    private function getTemplate(): PortfolioTemplate
    {
        return new PortfolioTemplate(
            $this->createMock(GlyphFinder::class),
            $this->createMock(ConsistencyChecker::class)
        );
    }

    public function testItCreatesATempDirectoryAndCopiesTheXMLTemplateFileInIt(): void
    {
        self::assertSame(
            $this->temp_dir->url() . '/program_management_template/portfolio/project.xml',
            $this->getTemplate()->getXMLPath()
        );
        self::assertTrue($this->temp_dir->hasChild('program_management_template/portfolio/project.xml'));
    }

    public function testItThrowsWhenCacheFolderIsNotWritable(): void
    {
        $this->temp_dir->chmod(600);
        $this->expectException(\RuntimeException::class);
        $this->getTemplate()->getXMLPath();
    }
}
