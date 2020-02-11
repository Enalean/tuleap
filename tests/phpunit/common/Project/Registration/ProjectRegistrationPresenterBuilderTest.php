<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\Project\Registration;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\Template\CompanyTemplate;
use Tuleap\Project\Registration\Template\DefaultProjectTemplate;
use Tuleap\Project\Registration\Template\ScrumTemplate;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\XML\ProjectXMLMerger;

final class ProjectRegistrationPresenterBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, ForgeConfigSandbox;

    /**
     * @var ProjectRegistrationPresenterBuilder
     */
    private $builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DescriptionFieldsFactory
     */
    private $fields_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\TroveCatFactory
     */
    private $trove_cat_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|DefaultProjectVisibilityRetriever
     */
    private $default_project_visibility_retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TemplateFactory
     */
    private $template_factory;

    protected function setUp(): void
    {
        $this->template_factory                     = \Mockery::mock(TemplateFactory::class);
        $this->default_project_visibility_retriever = new DefaultProjectVisibilityRetriever();
        $this->trove_cat_factory                    = Mockery::mock(\TroveCatFactory::class);
        $this->fields_factory                       = \Mockery::mock(DescriptionFieldsFactory::class);

        $this->builder = new ProjectRegistrationPresenterBuilder(
            $this->template_factory,
            $this->default_project_visibility_retriever,
            $this->trove_cat_factory,
            $this->fields_factory
        );
    }

    public function testItShouldBuildPresenter(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getGroupId')->andReturn(101);
        $project->shouldReceive('getDescription')->andReturn('My awesome project');
        $project->shouldReceive('getPublicName')->andReturn('project-shortname');

        $glyph_finder = Mockery::mock(GlyphFinder::class);
        $glyph        = Mockery::mock(Glyph::class);
        $glyph->shouldReceive('getInlineString')->andReturn('<svg>');
        $glyph_finder->shouldReceive('get')->andReturn($glyph);

        $this->template_factory->shouldReceive('getDefaultProjectTemplate')->andReturn(null);

        $company_templates = [new CompanyTemplate($project, $glyph_finder)];
        $this->template_factory->shouldReceive('getCompanyTemplateList')->andReturn(
            $company_templates
        );

        $fields = [
            [
                'group_desc_id'    => '1',
                'desc_name'        => 'Custom field',
                'desc_type'        => 'text',
                'desc_description' => 'Custom description',
                'desc_required'    => true
            ]
        ];
        $this->fields_factory->shouldReceive('getAllDescriptionFields')->andReturn(
            $fields
        );

        $this->trove_cat_factory->shouldReceive(
            'getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren'
        )->andReturn([]);

        $tuleap_templates = [
            new ScrumTemplate(
                $glyph_finder,
                Mockery::mock(ProjectXMLMerger::class),
                Mockery::mock(ConsistencyChecker::class)
            )
        ];
        $this->template_factory->shouldReceive('getValidTemplates')->andReturn(
            $tuleap_templates
        );

        $result = $this->builder->buildPresenter();

        $this->assertEquals(
            '[{"title":"project-shortname","description":"My awesome project","id":"101","glyph":"<svg>","is_built_in":false}]',
            $result->company_templates
        );

        $this->assertEquals(
            '[{"title":"Scrum","description":"Collect stories, plan releases, monitor sprints with a ready-to-use Scrum area","id":"scrum","glyph":"<svg>","is_built_in":true}]',
            $result->tuleap_templates
        );

        $this->assertEquals(
            '[{"group_desc_id":"1","desc_name":"Custom field","desc_type":"text","desc_description":"Custom description","desc_required":true}]',
            $result->field_list
        );
    }

    public function testItShouldAddTheDefaultSiteTemplateIfOptionIsSet(): void
    {
        \ForgeConfig::set(ProjectRegistrationPresenterBuilder::FORGECONFIG_CAN_USE_DEFAULT_SITE_TEMPLATE, true);

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getGroupId')->andReturn(101);
        $project->shouldReceive('getDescription')->andReturn('My awesome project');
        $project->shouldReceive('getPublicName')->andReturn('project-shortname');

        $glyph_finder = Mockery::mock(GlyphFinder::class);
        $glyph        = Mockery::mock(Glyph::class);
        $glyph->shouldReceive('getInlineString')->andReturn('<svg>');
        $glyph_finder->shouldReceive('get')->andReturn($glyph);

        $default_template_project = Mockery::mock(DefaultProjectTemplate::class);
        $default_template_project->shouldReceive('getTitle')->andReturn('Default Site Template');
        $default_template_project->shouldReceive('getDescription')->andReturn('Default Site Template');
        $default_template_project->shouldReceive('getId')->andReturn(100);
        $default_template_project->shouldReceive('getGlyph')->andReturn($glyph);
        $default_template_project->shouldReceive('isBuiltIn')->andReturn(true);
        $this->template_factory->shouldReceive('getDefaultProjectTemplate')->andReturn($default_template_project);

        $this->template_factory->shouldReceive('getCompanyTemplateList')->andReturn([]);
        $this->fields_factory->shouldReceive('getAllDescriptionFields')->andReturn([]);
        $this->trove_cat_factory->shouldReceive(
            'getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren'
        )->andReturn([]);
        $this->template_factory->shouldReceive('getValidTemplates')->andReturn([]);

        $result = $this->builder->buildPresenter();

        $this->assertNotNull($result->default_project_template);
    }

    public function testItShouldNotAddTheDefaultSiteTemplateIfOptionIsNotSet(): void
    {
        \ForgeConfig::set(ProjectRegistrationPresenterBuilder::FORGECONFIG_CAN_USE_DEFAULT_SITE_TEMPLATE, false);

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getGroupId')->andReturn(101);
        $project->shouldReceive('getDescription')->andReturn('My awesome project');
        $project->shouldReceive('getPublicName')->andReturn('project-shortname');

        $glyph_finder = Mockery::mock(GlyphFinder::class);
        $glyph        = Mockery::mock(Glyph::class);
        $glyph->shouldReceive('getInlineString')->andReturn('<svg>');
        $glyph_finder->shouldReceive('get')->andReturn($glyph);

        $this->template_factory->shouldReceive('getDefaultProjectTemplate')->andReturn(null);

        $this->template_factory->shouldReceive('getCompanyTemplateList')->andReturn([]);
        $this->fields_factory->shouldReceive('getAllDescriptionFields')->andReturn([]);
        $this->trove_cat_factory->shouldReceive(
            'getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren'
        )->andReturn([]);
        $this->template_factory->shouldReceive('getValidTemplates')->andReturn([]);

        $result = $this->builder->buildPresenter();

        $this->assertNull($result->default_project_template);
    }
}
