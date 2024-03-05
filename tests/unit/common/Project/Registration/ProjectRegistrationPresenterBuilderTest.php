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

declare(strict_types=1);

namespace Tuleap\Project\Registration;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Registration\Template\CategorisedTemplate;
use Tuleap\Project\Registration\Template\CompanyTemplate;
use Tuleap\Project\Registration\Template\ScrumTemplate;
use Tuleap\Project\Registration\Template\TemplateCategory;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\XML\ProjectXMLMerger;

final class ProjectRegistrationPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private ProjectRegistrationPresenterBuilder $builder;
    private DefaultProjectVisibilityRetriever $default_project_visibility_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TemplateFactory
     */
    private $template_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TroveCatFactory
     */
    private $trove_cat_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&DescriptionFieldsFactory
     */
    private $fields_factory;

    protected function setUp(): void
    {
        $this->template_factory                     = $this->createMock(TemplateFactory::class);
        $this->default_project_visibility_retriever = new DefaultProjectVisibilityRetriever();
        $this->trove_cat_factory                    = $this->createMock(\TroveCatFactory::class);
        $this->fields_factory                       = $this->createMock(DescriptionFieldsFactory::class);

        $this->builder = new ProjectRegistrationPresenterBuilder(
            $this->template_factory,
            $this->default_project_visibility_retriever,
            $this->trove_cat_factory,
            $this->fields_factory
        );
        \ForgeConfig::set('sys_org_name', "My orga");
    }

    public function testItShouldBuildPresenter(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('getGroupId')->willReturn(101);
        $project->method('getDescription')->willReturn('My awesome project');
        $project->method('getPublicName')->willReturn('project-shortname');

        $glyph_finder = $this->createMock(GlyphFinder::class);
        $glyph        = $this->createMock(Glyph::class);
        $glyph->method('getInlineString')->willReturn('<svg>');
        $glyph_finder->method('get')->willReturn($glyph);

        $company_templates = [new CompanyTemplate($project, $glyph_finder)];
        $this->template_factory->method('getCompanyTemplateList')->willReturn(
            $company_templates
        );
        $this->template_factory->method('getCategorisedExternalTemplates')->willReturn(
            [
                new class implements CategorisedTemplate {
                    public function getTemplateCategory(): TemplateCategory
                    {
                        return new class implements TemplateCategory {
                            public string $shortname = 'some-category';
                            public string $label     = 'Some category';
                        };
                    }

                    public function getId(): string
                    {
                        return 'external_template';
                    }

                    public function getTitle(): string
                    {
                        return 'External template';
                    }

                    public function getDescription(): string
                    {
                        return 'It is a template, it is external';
                    }

                    public function getGlyph(): Glyph
                    {
                        return new Glyph('');
                    }

                    public function isBuiltIn(): bool
                    {
                        return true;
                    }

                    public function getXMLPath(): string
                    {
                        return 'path/to/xml';
                    }

                    public function isAvailable(): bool
                    {
                        return true;
                    }
                },
            ]
        );

        $fields = [
            [
                'group_desc_id'    => '1',
                'desc_name'        => 'Custom field',
                'desc_type'        => 'text',
                'desc_description' => 'Custom description',
                'desc_required'    => 1,
            ],
        ];
        $this->fields_factory->method('getAllDescriptionFields')->willReturn(
            $fields
        );

        $this->trove_cat_factory->method(
            'getMandatoryParentCategoriesUnderRootOnlyWhenCategoryHasChildren'
        )->willReturn([]);

        $tuleap_templates = [
            new ScrumTemplate(
                $glyph_finder,
                $this->createMock(ProjectXMLMerger::class),
                $this->createMock(ConsistencyChecker::class)
            ),
        ];
        $this->template_factory->method('getValidTemplates')->willReturn(
            $tuleap_templates
        );

        $result = $this->builder->buildPresenter();

        self::assertJsonStringEqualsJsonString(
            '[{"title":"project-shortname","description":"My awesome project","id":"101","glyph":"<svg>","is_built_in":false}]',
            $result->company_templates
        );

        self::assertJsonStringEqualsJsonString(
            '[{"title":"Scrum","description":"Collect stories, plan releases, monitor sprints with a ready-to-use Scrum area","id":"scrum","glyph":"<svg>","is_built_in":true}]',
            $result->tuleap_templates
        );

        self::assertJsonStringEqualsJsonString(
            '[{"template_category":{"shortname":"some-category","label":"Some category"},"title":"External template","description":"It is a template, it is external","id":"external_template","glyph":"","is_built_in":true}]',
            $result->external_templates
        );

        self::assertJsonStringEqualsJsonString(
            '[{"group_desc_id":"1","desc_name":"Custom field","desc_type":"text","desc_description":"Custom description","desc_required":"1"}]',
            $result->field_list
        );
    }
}
