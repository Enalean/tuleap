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
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
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
use Tuleap\Project\DescriptionFieldsDao;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForPlatformException;
use Tuleap\Project\Registration\ProjectRegistrationChecker;
use Tuleap\Project\Registration\RestrictedUsersNotAllowedException;
use Tuleap\Project\Registration\Template\NoTemplateProvidedFault;
use Tuleap\Project\Registration\Template\ScrumTemplate;
use Tuleap\Project\Registration\Template\TemplateDao;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\Upload\ProjectFileToUploadCreator;
use Tuleap\Project\Registration\Template\Upload\SaveFileUploadStub;
use Tuleap\Project\REST\v1\Project\PostProjectCreated;
use Tuleap\Project\REST\v1\Project\ProjectFilePOSTRepresentation;
use Tuleap\Project\REST\v1\Project\ProjectRepresentationBuilder;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\ConsistencyChecker;
use Tuleap\Project\XML\Import\ArchiveInterface;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\Project\Registration\ProjectRegistrationCheckerStub;
use Tuleap\XML\ProjectXMLMerger;
use URLVerification;
use UserManager;

final class RestProjectCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private ServiceEnableForXmlImportRetriever&MockObject $retriever;
    private \EventManager&MockObject $event_manager;
    private ServiceManager&MockObject $service_manager;
    private ProjectManager&MockObject $project_manager;
    private \PFUser $user;
    private ProjectPostRepresentation $project_post_representation;
    private ProjectCreator&MockObject $project_creator;
    private ProjectXMLImporter&MockObject $project_XML_importer;
    private TemplateDao&MockObject $template_dao;
    private \PluginFactory&MockObject $plugin_factory;
    private UserManager&MockObject $user_manager;
    private URLVerification&MockObject $url_verification;

    protected function setUp(): void
    {
        \ForgeConfig::set('codendi_cache_dir', vfsStream::setup('RestProjectCreatorTest')->url());

        $this->project_manager      = $this->createMock(ProjectManager::class);
        $this->project_creator      = $this->createMock(ProjectCreator::class);
        $this->service_manager      = $this->createMock(ServiceManager::class);
        $this->project_XML_importer = $this->createMock(ProjectXMLImporter::class);
        $this->template_dao         = $this->createMock(TemplateDao::class);

        $this->event_manager  = $this->createMock(\EventManager::class);
        $this->retriever      = $this->createMock(ServiceEnableForXmlImportRetriever::class);
        $this->plugin_factory = $this->createMock(\PluginFactory::class);

        $this->user_manager     = $this->createMock(UserManager::class);
        $this->url_verification = $this->createMock(URLVerification::class);

        $this->user                        = UserTestBuilder::anActiveUser()->build();
        $this->project_post_representation = ProjectPostRepresentation::build(101);
    }

    public function testCreateThrowExceptionWhenUserCannotCreateProjects(): void
    {
        $creator                                             = $this->getCreator(ProjectRegistrationCheckerStub::withoutException());
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

        $template_project = ProjectTestBuilder::aProject()
            ->withStatusSuspended()
            ->withTypeTemplate()
            ->build();

        $this->project_manager->method('getProject')->with($this->project_post_representation->template_id)->willReturn($template_project);

        $this->project_creator->method('processProjectCreation')->willThrowException(
            new MaxNumberOfProjectReachedForPlatformException()
        );

        self::expectException(RestException::class);

        $creator->create(
            $this->project_post_representation,
            new ProjectCreationData(
                new DefaultProjectVisibilityRetriever(),
                new NullLogger()
            ),
            $this->user
        );
    }

    public function testFaultWhenNeitherTemplateIdNorTemplateNameIsProvided(): void
    {
        $creator                                              = $this->getCreator(ProjectRegistrationCheckerStub::withoutException());
        $this->project_post_representation->template_id       = null;
        $this->project_post_representation->xml_template_name = null;
        $this->project_post_representation->from_archive      = null;

        $creator->create(
            $this->project_post_representation,
            new ProjectCreationData(
                new DefaultProjectVisibilityRetriever(),
                new NullLogger(),
            ),
            $this->user
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
        $creator                                        = $this->getCreator(ProjectRegistrationCheckerStub::withoutException());
        $this->project_post_representation->template_id = 100;
        $this->project_post_representation->shortname   = 'gpig';
        $this->project_post_representation->label       = 'Guinea Pig';
        $this->project_post_representation->description = 'foo';
        $this->project_post_representation->is_public   = false;

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $template_project = ProjectTestBuilder::aProject()
            ->withStatusSuspended()
            ->withTypeTemplate()
            ->build();
        $this->project_manager->method('getProject')->with($this->project_post_representation->template_id)->willReturn($template_project);
        $this->project_creator->expects(self::atLeastOnce())->method('processProjectCreation')->with($project_creation_data);

        $creator->create(
            $this->project_post_representation,
            $project_creation_data,
            $this->user
        );
    }

    public function testCreateWithDefaultProjectTemplateAndExcludeRestrictedUsers(): void
    {
        $creator                                             = $this->getCreator(ProjectRegistrationCheckerStub::withoutException());
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

        $template_project = ProjectTestBuilder::aProject()
            ->withStatusSuspended()
            ->withTypeTemplate()
            ->build();
        $this->project_manager->method('getProject')->with($this->project_post_representation->template_id)->willReturn($template_project);
        $this->project_creator->expects(self::atLeastOnce())->method('processProjectCreation')->with($project_creation_data);

        $creator->create(
            $this->project_post_representation,
            $project_creation_data,
            $this->user
        );
    }

    public function testCreateFromXMLTemplate(): void
    {
        $creator = $this->getCreator(ProjectRegistrationCheckerStub::withoutException());
        ForgeConfig::set(ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->project_post_representation->template_id       = null;
        $this->project_post_representation->xml_template_name = ScrumTemplate::NAME;
        $this->project_post_representation->shortname         = 'gpig';
        $this->project_post_representation->label             = 'Guinea Pig';
        $this->project_post_representation->description       = 'foo';
        $this->project_post_representation->is_public         = false;
        $this->project_post_representation->allow_restricted  = false;
        $this->project_post_representation->from_archive      = null;

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $service1 = $this->createMock(Service::class);
        $service1->method('getShortName')->willReturn("summary");
        $service1->method('getId')->willReturn(12);
        $service2 = $this->createMock(Service::class);
        $service2->method('getShortName')->willReturn("admin");
        $service2->method('getId')->willReturn(13);
        $service3 = $this->createMock(Service::class);
        $service3->method('getShortName')->willReturn(\AgileDashboardPlugin::PLUGIN_SHORTNAME);
        $service3->method('getId')->willReturn(14);
        $service4 = $this->createMock(Service::class);
        $service4->method('getShortName')->willReturn(\trackerPlugin::SERVICE_SHORTNAME);
        $service4->method('getId')->willReturn(15);

        $services = [
            $service1,
            $service2,
            $service3,
            $service4,
        ];

        $this->plugin_factory->method('getPluginByName')->willReturn(new \Plugin());
        $this->plugin_factory->method('isPluginEnabled')->willReturn(true);

        $this->retriever->method('addServiceByName');
        $this->retriever->method('getAvailableServices')->willReturn(
            [
                "summary"                               => true,
                "admin"                                 => true,
                \AgileDashboardPlugin::PLUGIN_SHORTNAME => true,
                \trackerPlugin::SERVICE_SHORTNAME       => true,
            ]
        );
        $this->event_manager->method('processEvent');
        $this->service_manager->method('getListOfAllowedServicesForProject')
            ->with(self::callback(
                static function (Project $project): bool {
                    return $project->getID() === Project::DEFAULT_TEMPLATE_PROJECT_ID;
                }
            ))
            ->willReturn($services);

        $new_project = new \Project(['group_id' => 201]);

        $this->project_XML_importer->method('importWithProjectData')->with(
            self::isInstanceOf(ImportConfig::class),
            self::callback(function (ArchiveInterface $archive) {
                return realpath($archive->getExtractionPath()) === realpath(dirname((new ScrumTemplate(
                    $this->createMock(GlyphFinder::class),
                    new ProjectXMLMerger(),
                    $this->createMock(ConsistencyChecker::class)
                ))->getXMLPath()));
            }),
            self::isInstanceOf(SystemEventRunnerForProjectCreationFromXMLTemplate::class),
            $project_creation_data
        )->willReturn(Result::ok($new_project));

        $this->template_dao->expects(self::once())->method('saveTemplate')->with($new_project, ScrumTemplate::NAME);

        $creator->create(
            $this->project_post_representation,
            $project_creation_data,
            $this->user
        )->match(
            function (PostProjectCreated $project) use ($new_project) {
                self::assertSame($new_project, $project->getProject());
            },
            function () {
                self::fail("Unexpected fault");
            }
        );
    }

    public function testItCreatesFromArchive(): void
    {
        $creator = $this->getCreator(ProjectRegistrationCheckerStub::withoutException());

        $this->project_post_representation->template_id      = null;
        $this->project_post_representation->shortname        = 'gpig';
        $this->project_post_representation->label            = 'Guinea Pig';
        $this->project_post_representation->description      = 'foo';
        $this->project_post_representation->is_public        = false;
        $this->project_post_representation->allow_restricted = false;
        $this->project_post_representation->from_archive     = new ProjectFilePOSTRepresentation("test.zip", 123);

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $creator->create(
            $this->project_post_representation,
            $project_creation_data,
            $this->user
        )->match(
            function (PostProjectCreated $project) {
                self::assertSame("/uploads/project/file/1", $project->getProjectFromArchiveRepresentation()->upload_href);
            },
            function () {
                self::fail("Unexpected fault");
            }
        );
    }

    public function testItCollectsErrorFromArchive(): void
    {
        $creator                                             = $this->getCreator(ProjectRegistrationCheckerStub::withException(new RestrictedUsersNotAllowedException()));
        $this->project_post_representation->template_id      = null;
        $this->project_post_representation->shortname        = 'gpig';
        $this->project_post_representation->label            = 'Guinea Pig';
        $this->project_post_representation->description      = 'foo';
        $this->project_post_representation->is_public        = false;
        $this->project_post_representation->allow_restricted = false;
        $this->project_post_representation->from_archive     = new ProjectFilePOSTRepresentation("test.zip", 123);

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $creator->create(
            $this->project_post_representation,
            $project_creation_data,
            $this->user
        )->match(
            function (PostProjectCreated $project) {
                self::assertSame("/uploads/project/file/1", $project->getProjectFromArchiveRepresentation()->upload_href);
            },
            function (Fault $fault) {
                self::assertStringContainsString("Restricted users cannot create projects.", (string) $fault);
            }
        );
    }

    private function getCreator(ProjectRegistrationChecker $checker): RestProjectCreator
    {
        return new RestProjectCreator(
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
                $this->project_manager,
                new \EventManager(),
                $this->user_manager,
                $this->url_verification,
            ),
            new ProjectFileToUploadCreator(SaveFileUploadStub::withASavedFile(1)),
            new ProjectRepresentationBuilder(
                $this->event_manager,
                new DescriptionFieldsFactory(new DescriptionFieldsDao())
            ),
            $checker
        );
    }
}
