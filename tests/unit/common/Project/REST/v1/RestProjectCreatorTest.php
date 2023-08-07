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

namespace Tuleap\Project\REST\v1;

use ForgeAccess;
use ForgeConfig;
use Luracast\Restler\RestException;
use Mockery as M;
use org\bovigo\vfs\vfsStream;
use Project;
use ProjectCreationData;
use ProjectCreator;
use ProjectManager;
use ProjectXMLImporter;
use Psr\Log\NullLogger;
use Service;
use ServiceManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForPlatformException;
use Tuleap\Project\Registration\Template\NoTemplateProvidedFault;
use Tuleap\Project\Registration\Template\ScrumTemplate;
use Tuleap\Project\Registration\Template\TemplateDao;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\XML\ProjectXMLMerger;
use URLVerification;
use UserManager;

final class RestProjectCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

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

    private $project_manager;
    private RestProjectCreator $creator;
    private \PFUser $user;
    private ProjectPostRepresentation $project_post_representation;
    private $project_creator;
    private $project_XML_importer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|TemplateDao
     */
    private $template_dao;
    private \PHPUnit\Framework\MockObject\MockObject|\PluginFactory $plugin_factory;
    private $user_manager;
    private $url_verification;

    protected function setUp(): void
    {
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup('RestProjectCreatorTest')->url());

        $this->project_manager      = M::mock(ProjectManager::class);
        $this->project_creator      = M::mock(ProjectCreator::class);
        $this->service_manager      = M::mock(ServiceManager::class);
        $this->project_XML_importer = M::mock(ProjectXMLImporter::class);
        $this->template_dao         = M::mock(TemplateDao::class);

        $this->event_manager  = \Mockery::mock(\EventManager::class);
        $this->retriever      = \Mockery::mock(ServiceEnableForXmlImportRetriever::class);
        $this->plugin_factory = $this->createMock(\PluginFactory::class);

        $this->user_manager     = $this->createMock(UserManager::class);
        $this->url_verification = $this->createMock(URLVerification::class);

        $this->creator = new RestProjectCreator(
            $this->project_creator,
            $this->project_XML_importer,
            new TemplateFactory(
                new GlyphFinder(
                    new \EventManager()
                ),
                new ProjectXMLMerger(),
                new ConsistencyChecker(
                    new XMLFileContentRetriever(),
                    $this->event_manager,
                    $this->retriever,
                    $this->plugin_factory,
                ),
                $this->template_dao,
                M::mock(ProjectManager::class),
                new \EventManager(),
                $this->user_manager,
                $this->url_verification
            )
        );

        $this->user = new \PFUser(['language_id' => 'en_US']);
        $this->project_manager->shouldReceive('userCanCreateProject')->with($this->user)->andReturnTrue()->byDefault();
        $this->project_post_representation = ProjectPostRepresentation::build(101);
    }

    public function testCreateThrowExceptionWhenUserCannotCreateProjects(): void
    {
        $this->project_post_representation->template_id      = 100;
        $this->project_post_representation->shortname        = 'gpig';
        $this->project_post_representation->label            = 'Guinea Pig';
        $this->project_post_representation->description      = 'foo';
        $this->project_post_representation->is_public        = false;
        $this->project_post_representation->allow_restricted = false;
        $this->project_post_representation->categories       = [
            CategoryPostRepresentation::build(14, 89),
            CategoryPostRepresentation::build(18, 53),
        ];

        $template_project = M::mock(Project::class, ['isError' => false, 'isActive' => false, 'isTemplate' => true]);

        $this->project_manager->shouldReceive('getProject')->with($this->project_post_representation->template_id)->andReturn($template_project);

        $this->project_creator->shouldReceive('processProjectCreation')->andThrow(
            new MaxNumberOfProjectReachedForPlatformException()
        );

        $this->expectException(RestException::class);

        $this->creator->create(
            $this->project_post_representation,
            new ProjectCreationData(
                new DefaultProjectVisibilityRetriever(),
                new NullLogger()
            )
        );
    }

    public function testFaultWhenNeitherTemplateIdNorTemplateNameIsProvided(): void
    {
        $this->project_post_representation->template_id       = null;
        $this->project_post_representation->xml_template_name = null;

        $this->creator->create(
            $this->project_post_representation,
            new ProjectCreationData(
                new DefaultProjectVisibilityRetriever(),
                new NullLogger()
            )
        )->match(
            function () {
                self::fail("No project should be created if no template");
            },
            function (Fault $fault) {
                self::assertInstanceOf(NoTemplateProvidedFault::class, $fault);
            }
        );
    }

    public function testCreateWithDefaultProjectTemplate(): void
    {
        $this->project_post_representation->template_id = 100;
        $this->project_post_representation->shortname   = 'gpig';
        $this->project_post_representation->label       = 'Guinea Pig';
        $this->project_post_representation->description = 'foo';
        $this->project_post_representation->is_public   = false;

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $template_project = \Mockery::mock(Project::class);
        $template_project->shouldReceive('isError')->andReturnFalse();
        $template_project->shouldReceive('isActive')->andReturnFalse();
        $template_project->shouldReceive('isTemplate')->andReturnTrue();
        $this->project_manager->shouldReceive('getProject')->with($this->project_post_representation->template_id)->andReturn($template_project);
        $this->project_creator->shouldReceive('processProjectCreation')->with($project_creation_data)->atLeast()->once();

        $this->creator->create(
            $this->project_post_representation,
            $project_creation_data
        );
    }

    public function testCreateWithDefaultProjectTemplateAndExcludeRestrictedUsers(): void
    {
        $this->project_post_representation->template_id      = 100;
        $this->project_post_representation->shortname        = 'gpig';
        $this->project_post_representation->label            = 'Guinea Pig';
        $this->project_post_representation->description      = 'foo';
        $this->project_post_representation->is_public        = false;
        $this->project_post_representation->allow_restricted = false;

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $template_project = \Mockery::mock(Project::class);
        $template_project->shouldReceive('isError')->andReturnFalse();
        $template_project->shouldReceive('isActive')->andReturnFalse();
        $template_project->shouldReceive('isTemplate')->andReturnTrue();
        $this->project_manager->shouldReceive('getProject')->with($this->project_post_representation->template_id)->andReturn($template_project);
        $this->project_creator->shouldReceive('processProjectCreation')->with($project_creation_data)->atLeast()->once();

        $this->creator->create(
            $this->project_post_representation,
            $project_creation_data
        );
    }

    public function testCreateFromXMLTemplate(): void
    {
        ForgeConfig::set(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->project_post_representation->template_id       = null;
        $this->project_post_representation->xml_template_name = ScrumTemplate::NAME;
        $this->project_post_representation->shortname         = 'gpig';
        $this->project_post_representation->label             = 'Guinea Pig';
        $this->project_post_representation->description       = 'foo';
        $this->project_post_representation->is_public         = false;
        $this->project_post_representation->allow_restricted  = false;

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $services = [
            M::mock(Service::class, ['getShortName' => "summary", 'getId' => 12]),
            M::mock(Service::class, ['getShortName' => "admin", 'getId' => 13]),
            M::mock(Service::class, ['getShortName' => \AgileDashboardPlugin::PLUGIN_SHORTNAME, 'getId' => 14]),
            M::mock(Service::class, ['getShortName' => \trackerPlugin::SERVICE_SHORTNAME, 'getId' => 15]),
        ];

        $this->plugin_factory->method('getPluginByName')->willReturn(new \Plugin());
        $this->plugin_factory->method('isPluginEnabled')->willReturn(true);

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
                    return $project->getID() === Project::DEFAULT_TEMPLATE_PROJECT_ID;
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
            $project_creation_data
        )->andReturn(Result::ok($new_project));

        $this->template_dao->shouldReceive('saveTemplate')->with($new_project, ScrumTemplate::NAME)->once();

        $this->creator->create(
            $this->project_post_representation,
            $project_creation_data
        )->match(
            function (Project $project) use ($new_project) {
                self::assertSame($new_project, $project);
            },
            function () {
                self::fail("Unexpected fault");
            }
        );
    }
}
