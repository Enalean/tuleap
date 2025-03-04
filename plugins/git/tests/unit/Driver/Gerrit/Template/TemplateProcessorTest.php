<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Driver\Gerrit\Template;

use Git_Driver_Gerrit_Template_Template;
use Git_Driver_Gerrit_Template_TemplateProcessor;
use Project;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TemplateProcessorTest extends TestCase
{
    private const PROJECT_NAME = 'some_project';

    private Git_Driver_Gerrit_Template_TemplateProcessor $template_processor;
    private Git_Driver_Gerrit_Template_Template $template;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->template_processor = new Git_Driver_Gerrit_Template_TemplateProcessor();
        $this->template           = new Git_Driver_Gerrit_Template_Template(1, 2, 'wathevername', 'whateverecontent');
        $this->project            = ProjectTestBuilder::aProject()->withUnixName(self::PROJECT_NAME)->build();
    }

    public function testItDoesntChangeAnythingIfTemplateHasNoVariable(): void
    {
        $template_content = 'this is some template content without variables';

        $this->template->setContent($template_content);

        $processed = $this->template_processor->processTemplate($this->template, $this->project);

        self::assertEquals($template_content, $processed);
    }

    public function testItReplacesTheProjectNameByTheAppropriateVariable(): void
    {
        $template_content = 'this %projectname% should be replaced by the project name.

            this one %projectname% too!';

        $expected = 'this some_project should be replaced by the project name.

            this one some_project too!';

        $this->template->setContent($template_content);

        $processed = $this->template_processor->processTemplate($this->template, $this->project);

        self::assertEquals($expected, $processed);
    }

    public function testItDoesntReplaceIrrevelantVariables(): void
    {
        $template_content = 'this %projectid% should be replaced by the project name.

            this one %projectid% too!';

        $this->template->setContent($template_content);

        $processed = $this->template_processor->processTemplate($this->template, $this->project);

        self::assertEquals($template_content, $processed);
    }
}
