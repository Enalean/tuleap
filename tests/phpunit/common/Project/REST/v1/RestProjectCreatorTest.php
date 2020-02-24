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
use Luracast\Restler\RestException;
use Mockery as M;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectCreator;
use ProjectManager;
use ProjectXMLImporter;
use Service;
use ServiceManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\Admin\Categories\CategoryCollection;
use Tuleap\Project\Admin\Categories\MissingMandatoryCategoriesException;
use Tuleap\Project\Admin\Categories\ProjectCategoriesUpdater;
use Tuleap\Project\Admin\DescriptionFields\FieldUpdator;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\Template\InvalidXMLTemplateNameException;
use Tuleap\Project\Registration\Template\ScrumTemplate;
use Tuleap\Project\Registration\Template\TemplateDao;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\XML\ProjectXMLMerger;

class RestProjectCreatorTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration, ForgeConfigSandbox;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ServiceEnableForXmlImportRetriever
     */
    private $retriever;
    /**
     * @var \EventManager|M\LegacyMockInterface|M\MockInterface
     */
    private $event_manager;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ServiceManager
     */
    private $service_manager;

    /**
     * @var M\LegacyMockInterface|M\MockInterface|FieldUpdator
     */
    private $field_updator;
    private $project_manager;
    private $creator;
    private $user;
    private $project;
    private $project_creator;
    private $project_XML_importer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectRegistrationUserPermissionChecker
     */
    private $permissions_checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|TemplateDao
     */
    private $template_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectCategoriesUpdater
     */
    private $categories_updater;

    protected function setUp(): void
    {
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup('RestProjectCreatorTest')->url());

        $this->project_manager      = M::mock(ProjectManager::class);
        $this->project_creator      = M::mock(ProjectCreator::class);
        $this->service_manager      = M::mock(ServiceManager::class);
        $this->project_XML_importer = M::mock(ProjectXMLImporter::class);
        $this->permissions_checker  = M::mock(ProjectRegistrationUserPermissionChecker::class);
        $this->permissions_checker->shouldReceive('checkUserCreateAProject')->byDefault();
        $this->template_dao       = M::mock(TemplateDao::class);
        $this->categories_updater = M::mock(ProjectCategoriesUpdater::class);
        $this->categories_updater->shouldReceive('update')->byDefault();
        $this->categories_updater->shouldReceive('checkCollectionConsistency')->byDefault();
        $this->field_updator = \Mockery::mock(FieldUpdator::class);
        $this->field_updator->shouldReceive('updateFromArray')->byDefault();
        $this->field_updator->shouldReceive('checkFieldConsistency')->byDefault();

        $this->event_manager = \Mockery::mock(\EventManager::class);
        $this->retriever     = \Mockery::mock(ServiceEnableForXmlImportRetriever::class);
        $this->creator       = new RestProjectCreator(
            $this->project_manager,
            $this->project_creator,
            new XMLFileContentRetriever(),
            $this->service_manager,
            M::spy(\Psr\Log\LoggerInterface::class),
            new \XML_RNGValidator(),
            $this->project_XML_importer,
            new TemplateFactory(
                new GlyphFinder(
                    new \EventManager()
                ),
                new ProjectXMLMerger(),
                new ConsistencyChecker(
                    new XMLFileContentRetriever(),
                    $this->event_manager,
                    $this->retriever
                ),
                $this->template_dao,
                M::mock(ProjectManager::class)
            ),
            $this->permissions_checker,
            $this->categories_updater,
            $this->field_updator
        );

        $this->user = new \PFUser(['language_id' => 'en_US']);
        $this->project_manager->shouldReceive('userCanCreateProject')->with($this->user)->andReturnTrue()->byDefault();
        $this->project = new ProjectPostRepresentation();
    }

    public function testCreateThrowExceptionWhenUserCannotCreateProjects()
    {
        $this->permissions_checker->shouldReceive('checkUserCreateAProject')->with($this->user)->andThrow(new MaxNumberOfProjectReachedException());

        $this->expectException(RestException::class);

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateThrowExceptionWhenNeitherTemplateIdNorTemplateNameIsProvided()
    {
        $this->expectException(InvalidXMLTemplateNameException::class);


        $this->creator->create($this->user, $this->project);
    }

    public function testCreateWithDefaultProjectTemplate()
    {
        $this->project->template_id = 100;
        $this->project->shortname = 'gpig';
        $this->project->label = 'Guinea Pig';
        $this->project->description = 'foo';
        $this->project->is_public = false;

        $template_project = \Mockery::mock(Project::class);
        $template_project->shouldReceive('isError')->andReturnFalse();
        $template_project->shouldReceive('isActive')->andReturnFalse();
        $template_project->shouldReceive('isTemplate')->andReturnTrue();
        $this->project_manager->shouldReceive('getProject')->with($this->project->template_id)->andReturn($template_project);
        $this->project_creator->shouldReceive('createFromRest')->with(
            'gpig',
            'Guinea Pig',
            \Mockery::on(static function (TemplateFromProjectForCreation $template_from_project_for_creation) use ($template_project) {
                return $template_from_project_for_creation->getProject() === $template_project;
            }),
            [
                'project' => [
                    'form_short_description' => 'foo',
                    'is_test' => false,
                    'is_public' => false
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

        $template_project = \Mockery::mock(Project::class);
        $template_project->shouldReceive('isError')->andReturnFalse();
        $template_project->shouldReceive('isActive')->andReturnFalse();
        $template_project->shouldReceive('isTemplate')->andReturnTrue();
        $this->project_manager->shouldReceive('getProject')->with($this->project->template_id)->andReturn($template_project);
        $this->project_creator->shouldReceive('createFromRest')->with(
            'gpig',
            'Guinea Pig',
            \Mockery::on(static function (TemplateFromProjectForCreation $template_from_project_for_creation) use ($template_project) {
                return $template_from_project_for_creation->getProject() === $template_project;
            }),
            [
                'project' => [
                    'form_short_description' => 'foo',
                    'is_test' => false,
                    'is_public' => false,
                    'allow_restricted' => false,
                ]
            ],
        );

        $this->creator->create($this->user, $this->project);
    }

    public function testCreateFromXMLTemplate()
    {
        ForgeConfig::set(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->project->xml_template_name = ScrumTemplate::NAME;
        $this->project->shortname         = 'gpig';
        $this->project->label             = 'Guinea Pig';
        $this->project->description       = 'foo';
        $this->project->is_public         = false;
        $this->project->allow_restricted  = false;

        $services = [
            M::mock(Service::class, ['getShortName' => "summary", 'getId' => 12]),
            M::mock(Service::class, ['getShortName' => "admin", 'getId' => 13]),
            M::mock(Service::class, ['getShortName' => \AgileDashboardPlugin::PLUGIN_SHORTNAME, 'getId' => 14]),
            M::mock(Service::class, ['getShortName' => \trackerPlugin::SERVICE_SHORTNAME, 'getId' => 15]),
        ];

        $this->retriever->shouldReceive('addServiceByName');
        $this->retriever->shouldReceive('getAvailableServices')->andReturn(
            [
                "summary"                               => true,
                "admin"                                 => true,
                \AgileDashboardPlugin::PLUGIN_SHORTNAME => true,
                \trackerPlugin::SERVICE_SHORTNAME       => true,
            ]
        );
        $this->event_manager->shouldReceive('processEvent');
        $this->service_manager->shouldReceive('getListOfAllowedServicesForProject')
            ->with(\Mockery::on(
                static function (Project $project): bool {
                    return $project->getID() === Project::ADMIN_PROJECT_ID;
                }
            ))
            ->andReturns($services);

        $new_project = new \Project(['group_id' => 201]);

        $this->project_XML_importer->shouldReceive('importWithProjectData')->with(
            \Hamcrest\Core\IsEqual::equalTo(new ImportConfig()),
            M::on(static function (ArchiveInterface $archive) {
                return realpath($archive->getExtractionPath()) === realpath(dirname((new ScrumTemplate(M::mock(GlyphFinder::class), new ProjectXMLMerger(), M::mock(ConsistencyChecker::class)))->getXMLPath()));
            }),
            \Hamcrest\Core\IsEqual::equalTo(new SystemEventRunnerForProjectCreationFromXMLTemplate()),
            M::on(static function (\ProjectCreationData $data) {
                return $data->getUnixName() === 'gpig' &&
                    $data->getFullName() === 'Guinea Pig' &&
                    $data->getShortDescription() === 'foo' &&
                    $data->getAccess() === Project::ACCESS_PRIVATE_WO_RESTRICTED;
            })
        )->andReturn($new_project);

        $this->template_dao->shouldReceive('saveTemplate')->with($new_project, ScrumTemplate::NAME)->once();

        $this->assertSame($new_project, $this->creator->create($this->user, $this->project));
    }

    public function testItCreatesWithSelectedCategories()
    {
        $this->project->template_id = 100;
        $this->project->shortname = 'gpig';
        $this->project->label = 'Guinea Pig';
        $this->project->description = 'foo';
        $this->project->is_public = false;
        $this->project->allow_restricted = false;
        $this->project->categories = [
            CategoryPostRepresentation::build(14, 89),
            CategoryPostRepresentation::build(18, 53)
        ];

        $template_project = M::mock(Project::class, ['isError' => false, 'isActive' => false, 'isTemplate' => true]);
        $new_project = new \Project(['group_id' => 201]);

        $this->project_manager->shouldReceive('getProject')->with($this->project->template_id)->andReturn($template_project);
        $this->project_creator->shouldReceive('createFromRest')->with('gpig', 'Guinea Pig', M::andAnyOtherArgs())->once()->andReturn($new_project);

        $verify_category_collection = static function (CategoryCollection $categories) {
            [$category1, $category2] = $categories->getRootCategories();
            $category1_child1 = $category1->getChildren()[0];
            $category2_child1 = $category2->getChildren()[0];
            return $category1->getId() === 14 && $category2->getId() === 18 &&
                $category1_child1->getId() === 89 && $category2_child1->getId() === 53;
        };

        $this->categories_updater->shouldReceive('checkCollectionConsistency')->once()->with(M::on($verify_category_collection));
        $this->categories_updater->shouldReceive('update')->once()->with($new_project, M::on($verify_category_collection));

        $this->creator->create($this->user, $this->project);
    }


    public function testItThrowsAnExceptionWhenMandatoryCategoryIsMissing()
    {
        $this->project->template_id = 100;
        $this->project->shortname = 'gpig';
        $this->project->label = 'Guinea Pig';
        $this->project->description = 'foo';
        $this->project->is_public = false;
        $this->project->allow_restricted = false;
        $this->project->categories = [
            CategoryPostRepresentation::build(14, 89),
            CategoryPostRepresentation::build(18, 53)
        ];

        $template_project = M::mock(Project::class, ['isError' => false, 'isActive' => false, 'isTemplate' => true]);

        $this->project_manager->shouldReceive('getProject')->with($this->project->template_id)->andReturn($template_project);
        $this->project_creator->shouldNotReceive('createFromRest');

        $this->categories_updater->shouldReceive('checkCollectionConsistency')->once()->andThrow(new MissingMandatoryCategoriesException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->create($this->user, $this->project);
    }

    public function testItThrowsAnExceptionWhenFieldCollectionIsInvalid()
    {
        $this->project->template_id = 100;
        $this->project->shortname = 'gpig';
        $this->project->label = 'Guinea Pig';
        $this->project->description = 'foo';
        $this->project->is_public = false;
        $this->project->allow_restricted = false;
        $this->project->categories = [
            CategoryPostRepresentation::build(14, 89),
            CategoryPostRepresentation::build(18, 53)
        ];

        $template_project = M::mock(Project::class, ['isError' => false, 'isActive' => false, 'isTemplate' => true]);

        $this->project_manager->shouldReceive('getProject')->with($this->project->template_id)->andReturn($template_project);
        $this->project_creator->shouldNotReceive('createFromRest');

        $this->categories_updater->shouldReceive('checkCollectionConsistency')->once()->andThrow(new MissingMandatoryCategoriesException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->creator->create($this->user, $this->project);
    }
}
