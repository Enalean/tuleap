<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
final class ProgramTemplateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private string $template_cache_directory;

    private ProgramTemplate $program_template;
    private vfsStreamDirectory $root_dir;

    public function setUp(): void
    {
        $this->root_dir = vfsStream::setup();

        \ForgeConfig::set('codendi_cache_dir', $this->root_dir->url());

        $this->template_cache_directory = $this->root_dir->url() . '/program_management_template';
        $this->program_template         = new ProgramTemplate(
            $this->createMock(GlyphFinder::class),
            $this->createMock(ConsistencyChecker::class),
        );
    }

    public function testItShouldBuildTheXMLPathAndCopyTheXMLFilesInsideIt(): void
    {
        $xml_path             = $this->program_template->getXMLPath();
        $program_template_dir = $this->template_cache_directory . '/program';

        self::assertEquals($program_template_dir . '/project.xml', $xml_path);
        self::assertTrue($this->root_dir->hasChild('program_management_template/program/project.xml'));
        self::assertTrue($this->root_dir->hasChild('program_management_template/program/program-management-config.xml'));
    }
}
