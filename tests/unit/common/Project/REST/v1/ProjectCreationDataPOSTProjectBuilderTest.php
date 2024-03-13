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

namespace Tuleap\Project\REST\v1;

use PFUser;
use Project;
use ProjectManager;
use Psr\Log\NullLogger;
use ServiceManager;
use SimpleXMLElement;
use Tuleap\Glyph\Glyph;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\ProjectCreationDataServiceFromXmlInheritor;
use Tuleap\Project\Registration\Template\CustomProjectArchiveFeatureFlag;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\REST\v1\Project\ProjectCreationDataPOSTProjectBuilder;
use Tuleap\Project\REST\v1\Project\ProjectFilePOSTRepresentation;
use Tuleap\Project\XML\XMLFileContentRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Project\Registration\Template\VerifyProjectCreationFromArchiveIsAllowedStub;

final class ProjectCreationDataPOSTProjectBuilderTest extends TestCase
{
    private ProjectCreationDataPOSTProjectBuilder $builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectManager
     */
    private $project_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&TemplateFactory
     */
    private $template_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&XMLFileContentRetriever
     */
    private $xml_file_content_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ServiceManager
     */
    private $service_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectCreationDataServiceFromXmlInheritor
     */
    private $from_xml_inheritor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager            = $this->createMock(ProjectManager::class);
        $this->template_factory           = $this->createMock(TemplateFactory::class);
        $this->xml_file_content_retriever = $this->createMock(XMLFileContentRetriever::class);
        $this->service_manager            = $this->createMock(ServiceManager::class);
        $this->from_xml_inheritor         = $this->createMock(ProjectCreationDataServiceFromXmlInheritor::class);

        $this->builder = new ProjectCreationDataPOSTProjectBuilder(
            $this->project_manager,
            $this->template_factory,
            $this->xml_file_content_retriever,
            $this->service_manager,
            $this->from_xml_inheritor,
            new NullLogger(),
            new \URLVerification(),
            VerifyProjectCreationFromArchiveIsAllowedStub::buildAllowed(),
        );
    }

    public function testItBuildsProjectCreationDataWithTemplateId(): void
    {
        $post_representation = ProjectPostRepresentation::build(101);

        $post_representation->shortname         = 'shortname';
        $post_representation->label             = 'Project 01';
        $post_representation->description       = 'desc';
        $post_representation->is_public         = true;
        $post_representation->xml_template_name = null;

        $this->project_manager
            ->expects(self::once())
            ->method('getProject')
            ->with(101)
            ->willReturn(
                new Project([
                    'group_id' => '101',
                    'status'   => 'A',
                    'type'     => 1,
                ])
            );

        $user = $this->createMock(PFUser::class);
        $user
            ->expects(self::once())
            ->method('isAdmin')
            ->with('101')
            ->willReturn(true);

        $creation_data = $this->builder->buildProjectCreationDataFromPOSTRepresentation(
            $post_representation,
            $user
        );

        self::assertNotNull($creation_data);
        self::assertEquals('Project 01', $creation_data->getFullName());
        self::assertEquals('shortname', $creation_data->getUnixName());
        self::assertEquals('desc', $creation_data->getShortDescription());
        self::assertEquals('public', $creation_data->getAccess());
        self::assertNull($creation_data->isTemplate());
        self::assertFalse($creation_data->isTest());
        self::assertFalse($creation_data->isIsBuiltFromXml());
    }

    public function testItBuildsProjectCreationDataWithXMLTemplate(): void
    {
        $post_representation = ProjectPostRepresentation::build(101);

        $post_representation->shortname         = 'shortname-xml';
        $post_representation->label             = 'Project 02';
        $post_representation->description       = 'desc xml';
        $post_representation->is_public         = true;
        $post_representation->template_id       = null;
        $post_representation->xml_template_name = 'template';

        $user = UserTestBuilder::aUser()->build();

        $this->template_factory
            ->expects(self::once())
            ->method('getTemplate')
            ->with('template')
            ->willReturn(
                new class implements \Tuleap\Project\Registration\Template\TuleapTemplate
                {
                    public function getId(): string
                    {
                        return 'xmltemplate';
                    }

                    public function getTitle(): string
                    {
                        return 'XML Template';
                    }

                    public function getDescription(): string
                    {
                        return 'XML Template desc';
                    }

                    public function getGlyph(): Glyph
                    {
                        return new Glyph('');
                    }

                    public function isBuiltIn(): bool
                    {
                        return false;
                    }

                    public function getXMLPath(): string
                    {
                        return 'path/to/xml/template';
                    }

                    public function isAvailable(): bool
                    {
                        return true;
                    }
                }
            );

        $xml_content = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <project unix-name="test-create-project" full-name="Test Create Project" description="" access="public">
                <long-description/>
                <services/>
            </project>
        ');

        $this->xml_file_content_retriever
            ->expects(self::once())
            ->method('getSimpleXMLElementFromFilePath')
            ->with('path/to/xml/template')
            ->willReturn(Result::ok($xml_content));

        $this->from_xml_inheritor
            ->expects(self::once())
            ->method('markUsedServicesFromXML');

        $creation_data = $this->builder->buildProjectCreationDataFromPOSTRepresentation(
            $post_representation,
            $user
        );

        self::assertNotNull($creation_data);
        self::assertEquals('Project 02', $creation_data->getFullName());
        self::assertEquals('shortname-xml', $creation_data->getUnixName());
        self::assertEquals('desc xml', $creation_data->getShortDescription());
        self::assertEquals('public', $creation_data->getAccess());
        self::assertNull($creation_data->isTemplate());
        self::assertFalse($creation_data->isTest());
        self::assertTrue($creation_data->isIsBuiltFromXml());
        self::assertEmpty($creation_data->getDataFields()->getSubmittedFields());
    }

    public function testItBuildsFromArchive(): void
    {
        $post_representation = ProjectPostRepresentation::build(101);

        \ForgeConfig::setFeatureFlag(CustomProjectArchiveFeatureFlag::FEATURE_FLAG_KEY, '1');

        $post_representation->template_id  = null;
        $post_representation->shortname    = 'test';
        $post_representation->label        = 'Project 01';
        $post_representation->description  = 'desc';
        $post_representation->is_public    = true;
        $post_representation->from_archive = new ProjectFilePOSTRepresentation("test.zip", 123);

        $user = UserTestBuilder::anActiveUser()->build();

        $creation_data = $this->builder->buildProjectCreationDataFromPOSTRepresentation(
            $post_representation,
            $user
        );

        self::assertNotNull($creation_data);
        self::assertEquals('Project 01', $creation_data->getFullName());
        self::assertEquals('test', $creation_data->getUnixName());
        self::assertEquals('desc', $creation_data->getShortDescription());
        self::assertEquals('public', $creation_data->getAccess());
        self::assertNull($creation_data->isTemplate());
        self::assertFalse($creation_data->isTest());
        self::assertFalse($creation_data->isIsBuiltFromXml());
    }
}
