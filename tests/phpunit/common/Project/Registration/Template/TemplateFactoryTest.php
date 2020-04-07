<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template;

use Mockery as M;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\XML\ProjectXMLMerger;

class TemplateFactoryTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var TemplateFactory
     */
    private $factory;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ConsistencyChecker
     */
    private $consistency_checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\ProjectManager
     */
    private $project_manager;

    protected function setUp(): void
    {
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup('TemplateFactoryTest')->url());

        $this->consistency_checker = M::mock(ConsistencyChecker::class);
        $this->consistency_checker->shouldReceive('areAllServicesAvailable')->andReturnTrue()->byDefault();

        $this->project_manager = M::mock(\ProjectManager::class);

        $this->factory  = new TemplateFactory(
            new GlyphFinder(new \EventManager()),
            new ProjectXMLMerger(),
            $this->consistency_checker,
            M::mock(TemplateDao::class),
            $this->project_manager
        );
    }

    public function testItReturnsTemplates(): void
    {
        $templates = $this->factory->getValidTemplates();
        $this->assertCount(4, $templates);
        $this->assertInstanceOf(AgileALMTemplate::class, $templates[0]);
        $this->assertInstanceOf(ScrumTemplate::class, $templates[1]);
        $this->assertInstanceOf(KanbanTemplate::class, $templates[2]);
        $this->assertInstanceOf(IssuesTemplate::class, $templates[3]);
    }

    public function testItReturnsScrumTemplate(): void
    {
        $template = $this->factory->getTemplate(ScrumTemplate::NAME);
        $this->assertInstanceOf(ScrumTemplate::class, $template);
    }

    public function testItReturnsEmptyTemplate(): void
    {
        $template = $this->factory->getTemplate(EmptyTemplate::NAME);
        $this->assertInstanceOf(EmptyTemplate::class, $template);
    }

    public function testItReturnsScrumTemplateXML(): void
    {
        $template = $this->factory->getTemplate(ScrumTemplate::NAME);
        $xml = simplexml_load_string(file_get_contents($template->getXMLPath()));
        $this->assertNotEmpty($xml->services);
        $this->assertNotEmpty($xml->agiledashboard);
        $this->assertNotEmpty($xml->trackers);
    }

    public function testItThrowsAnExceptionWhenTemplateDoesntExist(): void
    {
        $this->expectException(InvalidXMLTemplateNameException::class);

        $this->factory->getTemplate('stuff');
    }

    public function testItDoesntReturnEmptyTemplateWhenNoTemplatesAreAvailable(): void
    {
        $this->consistency_checker->shouldReceive('areAllServicesAvailable')->andReturnFalse();

        $glyph_finder   = new GlyphFinder(\Mockery::mock(\EventManager::class));
        $empty_template = new EmptyTemplate($glyph_finder);

        $available_templates = $this->factory->getValidTemplates();

        $this->assertEquals($empty_template->getId(), $available_templates[0]->getId());
        $this->assertEquals($empty_template->getTitle(), $available_templates[0]->getTitle());
    }

    public function testItDoesntReturnTheTemplateThatIsNotAvailable(): void
    {
        $this->consistency_checker->shouldReceive('areAllServicesAvailable')->andReturnFalse();

        $this->expectException(InvalidXMLTemplateNameException::class);

        $this->factory->getTemplate(ScrumTemplate::NAME);
    }

    public function testItReturnsCompanyTemplateWhenTheTemplateIdIsNot100(): void
    {
        $template100 = \Mockery::mock(\Project::class);
        $template100->shouldReceive('getGroupId')->andReturn("100")->once();
        $template100->shouldReceive('getUnixNameLowerCase')->never();
        $template100->shouldReceive('getDescription')->never();
        $template100->shouldReceive('getPublicName')->never();


        $template110 = \Mockery::mock(\Project::class);
        $template110->shouldReceive('getGroupId')->andReturn("110")->atLeast()->twice();
        $template110->shouldReceive('getUnixNameLowerCase')->andReturn("hustler-company");
        $template110->shouldReceive('getDescription')->andReturn("New Jack City");
        $template110->shouldReceive('getPublicName')->andReturn("Hustler Company");

        $template120 = \Mockery::mock(\Project::class);
        $template120->shouldReceive('getGroupId')->andReturn("120")->atLeast()->twice();
        $template120->shouldReceive('getUnixNameLowerCase')->andReturn("lyudi-invalidy-company");
        $template120->shouldReceive('getDescription')->andReturn("All about us");
        $template120->shouldReceive('getPublicName')->andReturn("Lyudi Invalidy Company");

        $site_templates = [$template100, $template110, $template120];
        $this->project_manager->shouldReceive('getSiteTemplates')->andReturn($site_templates);


        $glyph_finder      = new GlyphFinder(\Mockery::mock(\EventManager::class));
        $hustler_template  = new CompanyTemplate($template110, $glyph_finder);
        $invalidy_template = new CompanyTemplate($template120, $glyph_finder);

        $expected_company_templates = [$hustler_template, $invalidy_template];

        $this->assertEquals($expected_company_templates, $this->factory->getCompanyTemplateList());
    }

    public function testItReturnsTheDefaultProjectTemplateIfTheProjectIsActive(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('isDeleted')->andReturn(false);
        $project->shouldReceive('isSuspended')->andReturn(false);
        $project->shouldReceive('getGroupId')->andReturn("100");
        $project->shouldReceive('getUnixNameLowerCase')->andReturn("none");
        $project->shouldReceive('getDescription')->andReturn("The default Tuleap template");
        $project->shouldReceive('getPublicName')->andReturn("Default Site Template");

        $this->project_manager->shouldReceive('getProject')->andReturn($project);
        $this->project_manager->shouldReceive('getGroupId')->andReturn("100");

        $glyph_finder = new GlyphFinder(\Mockery::mock(\EventManager::class));

        $expected_project_template = new DefaultProjectTemplate($project, $glyph_finder);

        $this->assertEquals($expected_project_template, $this->factory->getDefaultProjectTemplate());
    }

    public function testItReturnsNullIfTheDefaultProjectIsNotActive(): void
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('isDeleted')->andReturn(true);
        $project->shouldReceive('isSuspended')->andReturn(false);
        $project->shouldReceive('getGroupId')->andReturn("100")->never();
        $project->shouldReceive('getUnixNameLowerCase')->andReturn("none")->never();
        $project->shouldReceive('getDescription')->andReturn("The default Tuleap template")->never();
        $project->shouldReceive('getPublicName')->andReturn("Default Site Template")->never();

        $this->project_manager->shouldReceive('getProject')->andReturn($project);
        $this->project_manager->shouldReceive('getGroupId')->andReturn("100");

        $this->assertNull($this->factory->getDefaultProjectTemplate());
    }
}
