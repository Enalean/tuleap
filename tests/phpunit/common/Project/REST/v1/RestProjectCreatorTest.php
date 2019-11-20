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

declare(strict_types = 1);

namespace Tuleap\Project\REST\v1;

use ForgeAccess;
use ForgeConfig;
use Logger;
use Luracast\Restler\RestException;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ProjectCreator;
use ProjectManager;
use ProjectXMLImporter;
use ServiceManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\XMLFileContentRetriever;

class RestProjectCreatorTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration, ForgeConfigSandbox;

    private $project_manager;
    private $creator;
    private $user;
    private $project;
    private $project_creator;
    private $service_manager;
    private $project_XML_importer;

    protected function setUp(): void
    {
        $this->project_manager      = M::mock(ProjectManager::class);
        $this->project_creator      = M::mock(ProjectCreator::class);
        $this->service_manager      = M::mock(ServiceManager::class);
        $this->project_XML_importer = M::mock(ProjectXMLImporter::class);
        $this->creator              = new RestProjectCreator(
            $this->project_manager,
            $this->project_creator,
            new XMLFileContentRetriever(),
            $this->service_manager,
            M::spy(Logger::class),
            new \XML_RNGValidator(),
            $this->project_XML_importer,
        );

        $this->user = new \PFUser(['language_id' => 'en_US']);
        $this->project_manager->shouldReceive('userCanCreateProject')->with($this->user)->andReturnTrue()->byDefault();
        $this->project = new ProjectPostRepresentation();
    }

    public function testCreateThrowExceptionWhenUserCannotCreateProjects()
    {
        $this->project_manager->shouldReceive('userCanCreateProject')->with($this->user)->andReturnFalse();

        $this->expectException(RestException::class);

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateThrowExceptionWhenNeitherTemplateIdNorTemplateNameIsProvided()
    {
        $this->expectException(InvalidTemplateException::class);

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateWithDefaultProjectTemplate()
    {
        $this->project->template_id = 100;
        $this->project->shortname = 'gpig';
        $this->project->label = 'Guinea Pig';
        $this->project->description = 'foo';
        $this->project->is_public = false;

        $this->project_creator->shouldReceive('createFromRest')->with(
            'gpig',
            'Guinea Pig',
            [
                'project' => [
                    'form_short_description' => 'foo',
                    'is_test' => false,
                    'is_public' => false,
                    'built_from_template' => 100,
                ]
            ],
        );

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateWithDefaultProjectTemplateAndExcludeRestrictedUsers()
    {
        $this->project->template_id = 100;
        $this->project->shortname = 'gpig';
        $this->project->label = 'Guinea Pig';
        $this->project->description = 'foo';
        $this->project->is_public = false;
        $this->project->allow_restricted = false;

        $this->project_creator->shouldReceive('createFromRest')->with(
            'gpig',
            'Guinea Pig',
            [
                'project' => [
                    'form_short_description' => 'foo',
                    'is_test' => false,
                    'is_public' => false,
                    'built_from_template' => 100,
                    'allow_restricted' => false,
                ]
            ],
        );

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateFromXMLTemplate()
    {
        ForgeConfig::set('sys_user_can_choose_project_privacy', 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->project->xml_template_name = RestProjectCreator::SCRUM_TEMPLATE;
        $this->project->shortname         = 'gpig';
        $this->project->label             = 'Guinea Pig';
        $this->project->description       = 'foo';
        $this->project->is_public         = false;
        $this->project->allow_restricted  = false;

        $template_project = new \Project(['group_id' => 100]);
        $this->project_manager->shouldReceive('getProject')->with(100)->andReturn($template_project);

        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')->with($template_project)->andReturn([]);

        $new_project = new \Project(['group_id' => 2000]);
        $this->project_creator->shouldReceive('build')->with(M::on(function (\ProjectCreationData $data) {
            return $data->getUnixName() === 'gpig' &&
                $data->getFullName() === 'Guinea Pig' &&
                $data->getShortDescription() === 'foo' &&
                $data->getAccess() === \Project::ACCESS_PRIVATE_WO_RESTRICTED;
        }))->andReturn($new_project);

        $this->project_XML_importer->shouldReceive('import')->with(
            \Hamcrest\Core\IsEqual::equalTo(new ImportConfig()),
            2000,
            M::any()
        );

        $this->creator->create($this->user, $this->project);
    }
}
