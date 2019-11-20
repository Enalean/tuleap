<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1;

use ForgeConfig;
use Luracast\Restler\RestException;
use Project;
use ProjectCreationData;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\XMLFileContentRetriever;

class RestProjectCreator
{
    public const SCRUM_TEMPLATE = 'scrum';

    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var \ProjectCreator
     */
    private $project_creator;
    /**
     * @var XMLFileContentRetriever
     */
    private $XML_file_content_retriever;
    /**
     * @var \ServiceManager
     */
    private $service_manager;
    /**
     * @var \Logger
     */
    private $logger;
    /**
     * @var \XML_RNGValidator
     */
    private $validator;
    /**
     * @var \ProjectXMLImporter
     */
    private $project_XML_importer;

    public function __construct(
        \ProjectManager $project_manager,
        \ProjectCreator $project_creator,
        XMLFileContentRetriever $XML_file_content_retriever,
        \ServiceManager $service_manager,
        \Logger $logger,
        \XML_RNGValidator $validator,
        \ProjectXMLImporter $project_XML_importer
    ) {
        $this->project_manager            = $project_manager;
        $this->project_creator            = $project_creator;
        $this->XML_file_content_retriever = $XML_file_content_retriever;
        $this->service_manager            = $service_manager;
        $this->logger                     = $logger;
        $this->validator                  = $validator;
        $this->project_XML_importer       = $project_XML_importer;
    }

    /**
     * @throws RestException
     * @throws \Project_Creation_Exception
     * @throws \Project_InvalidFullName_Exception
     * @throws \Project_InvalidShortName_Exception
     * @throws \Tuleap\Project\ProjectDescriptionMandatoryException
     * @throws \Tuleap\Project\ProjectInvalidTemplateException
     * @throws InvalidTemplateException
     * @throws \Tuleap\Project\ProjectRegistrationDisabledException
     */
    public function create(\PFUser $user, ProjectPostRepresentation $post_representation): Project
    {
        if (! $this->project_manager->userCanCreateProject($user)) {
            throw new RestException(429, 'Too many projects were created');
        }

        if ($post_representation->template_id !== null) {
            return $this->createProjectFromTemplateId($post_representation);
        }

        if ($post_representation->xml_template_name !== null) {
            return $this->createProjectFromSystemTemplate($post_representation);
        }

        throw new InvalidTemplateException();
    }

    /**
     * @param ProjectPostRepresentation $post_representation
     *
     * @return Project
     * @throws \Project_Creation_Exception
     * @throws \Project_InvalidFullName_Exception
     * @throws \Project_InvalidShortName_Exception
     * @throws \Tuleap\Project\ProjectDescriptionMandatoryException
     * @throws \Tuleap\Project\ProjectInvalidTemplateException
     */
    private function createProjectFromTemplateId(ProjectPostRepresentation $post_representation): Project
    {
        $data = [
            'project' => [
                'form_short_description' => $post_representation->description,
                'is_test'                => false,
                'is_public'              => $post_representation->is_public,
                'built_from_template'    => $post_representation->template_id,
            ]
        ];

        if ($post_representation->allow_restricted !== null) {
            $data['project']['allow_restricted'] = $post_representation->allow_restricted;
        }

        return $this->project_creator->createFromRest(
            $post_representation->shortname,
            $post_representation->label,
            $data
        );
    }

    /**
     * @throws \Project_Creation_Exception
     * @throws \Project_InvalidFullName_Exception
     * @throws \Project_InvalidShortName_Exception
     * @throws \Tuleap\Project\ProjectDescriptionMandatoryException
     * @throws \Tuleap\Project\ProjectInvalidTemplateException
     * @throws \Tuleap\Project\ProjectRegistrationDisabledException
     * @throws InvalidTemplateException
     */
    private function createProjectFromSystemTemplate(ProjectPostRepresentation $post_representation): Project
    {
        $xml_path   = $this->getSystemTemplatePath($post_representation);
        $xml_element = $this->XML_file_content_retriever->getSimpleXMLElementFromFilePath(
            $xml_path
        );

        $data = ProjectCreationData::buildFromXML(
            $xml_element,
            100,
            $this->validator,
            $this->service_manager,
            $this->project_manager,
            $this->logger
        );

        $data->setUnixName($post_representation->shortname);
        $data->setFullName($post_representation->label);
        $data->setShortDescription($post_representation->description);
        $data->setAccessFromProjectData(
            [
                'is_public'        => $post_representation->is_public,
                'allow_restricted' => $post_representation->allow_restricted
            ]
        );

        $project = $this->project_creator->build($data);
        $configuration = new ImportConfig();
        $this->project_XML_importer->import($configuration, $project->getGroupId(), $xml_path);

        return $project;
    }

    private function getSystemTemplatePath(ProjectPostRepresentation $post_representation): string
    {
        if ($post_representation->xml_template_name === self::SCRUM_TEMPLATE) {
            return __DIR__ . '/../../../../../tools/utils/setup_templates/scrum/project.xml';
        }

        throw new InvalidTemplateException();
    }
}
