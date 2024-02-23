<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\REST\v1\Project;

use PFUser;
use ProjectManager;
use Psr\Log\LoggerInterface;
use ServiceManager;
use Tuleap\Project\Admin\Categories\CategoryCollection;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollection;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\ProjectCreationDataServiceFromXmlInheritor;
use Tuleap\Project\Registration\Template\InsufficientPermissionToUseProjectAsTemplateException;
use Tuleap\Project\Registration\Template\ProjectIDTemplateNotProvidedException;
use Tuleap\Project\Registration\Template\ProjectTemplateIDInvalidException;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\REST\v1\ProjectPostRepresentation;
use Tuleap\Project\XML\XMLFileContentRetriever;
use URLVerification;
use XML_RNGValidator;

class ProjectCreationDataPOSTProjectBuilder
{
    private ProjectManager $project_manager;
    private LoggerInterface $logger;
    private TemplateFactory $template_factory;
    private XMLFileContentRetriever $xml_file_content_retriever;
    private ServiceManager $service_manager;
    private ProjectCreationDataServiceFromXmlInheritor $from_xml_inheritor;

    public function __construct(
        ProjectManager $project_manager,
        TemplateFactory $template_factory,
        XMLFileContentRetriever $xml_file_content_retriever,
        ServiceManager $service_manager,
        ProjectCreationDataServiceFromXmlInheritor $from_xml_inheritor,
        LoggerInterface $logger,
        private URLVerification $url_verification,
    ) {
        $this->project_manager            = $project_manager;
        $this->template_factory           = $template_factory;
        $this->xml_file_content_retriever = $xml_file_content_retriever;
        $this->service_manager            = $service_manager;
        $this->from_xml_inheritor         = $from_xml_inheritor;
        $this->logger                     = $logger;
    }

    /**
     * @throws InsufficientPermissionToUseProjectAsTemplateException
     * @throws ProjectIDTemplateNotProvidedException
     * @throws ProjectTemplateIDInvalidException
     * @throws \Tuleap\Project\Registration\Template\InvalidTemplateException
     */
    public function buildProjectCreationDataFromPOSTRepresentation(
        ProjectPostRepresentation $post_representation,
        PFUser $user,
    ): ?ProjectCreationData {
        if ($post_representation->template_id !== null) {
            $data = [
                'project' => [
                    'form_short_description' => $post_representation->description,
                    'is_test'                => false,
                    'is_public'              => $post_representation->is_public,
                    'trove'                  => CategoryCollection::buildFromRESTProjectCreation($post_representation),
                ],
            ];

            if ($post_representation->allow_restricted !== null) {
                $data['project']['allow_restricted'] = $post_representation->allow_restricted;
            }

            $creation_data = ProjectCreationData::buildFromFormArray(
                new DefaultProjectVisibilityRetriever(),
                TemplateFromProjectForCreation::fromRESTRepresentation(
                    $post_representation,
                    $user,
                    $this->project_manager,
                    $this->url_verification
                ),
                $data
            );

            $creation_data->setUnixName($post_representation->shortname);
            $creation_data->setFullName($post_representation->label);
            $creation_data->setDataFields(
                ProjectRegistrationSubmittedFieldsCollection::buildFromRESTProjectCreation($post_representation)
            );

            return $creation_data;
        }

        if ($post_representation->xml_template_name !== null) {
            $template = $this->template_factory->getTemplate($post_representation->xml_template_name);
            $xml_path = $template->getXMLPath();

            return $this->xml_file_content_retriever->getSimpleXMLElementFromFilePath($xml_path)
                ->match(
                    function (\SimpleXMLElement $xml_element) use ($post_representation): ProjectCreationData {
                        $creation_data = ProjectCreationData::buildFromXML(
                            $xml_element,
                            new XML_RNGValidator(),
                            $this->service_manager,
                            $this->logger,
                            null,
                            null,
                            $this->from_xml_inheritor
                        );

                        $creation_data->setUnixName($post_representation->shortname);
                        $creation_data->setFullName($post_representation->label);
                        $creation_data->setShortDescription($post_representation->description);
                        $creation_data->setAccessFromProjectData(
                            [
                                'is_public'        => $post_representation->is_public,
                                'allow_restricted' => $post_representation->allow_restricted,
                            ]
                        );
                        $creation_data->setTroveData(
                            CategoryCollection::buildFromRESTProjectCreation($post_representation)
                        );

                        $creation_data->setDataFields(
                            ProjectRegistrationSubmittedFieldsCollection::buildFromRESTProjectCreation($post_representation)
                        );

                        return $creation_data;
                    },
                    function () {
                        return null;
                    }
                );
        }

        return null;
    }
}
