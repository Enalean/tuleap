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

use Luracast\Restler\RestException;
use PFUser;
use ProjectManager;
use Psr\Log\LoggerInterface;
use ServiceManager;
use Tuleap\Project\Admin\Categories\CategoryCollection;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollection;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\ProjectCreationDataServiceFromXmlInheritor;
use Tuleap\Project\Registration\Template\CustomProjectArchiveFeatureFlag;
use Tuleap\Project\Registration\Template\InsufficientPermissionToUseProjectAsTemplateException;
use Tuleap\Project\Registration\Template\ProjectIDTemplateNotProvidedException;
use Tuleap\Project\Registration\Template\ProjectTemplateIDInvalidException;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\REST\v1\ProjectPostRepresentation;
use Tuleap\Project\XML\XMLFileContentRetriever;
use URLVerification;
use XML_RNGValidator;

readonly class ProjectCreationDataPOSTProjectBuilder
{
    public function __construct(
        private ProjectManager $project_manager,
        private TemplateFactory $template_factory,
        private XMLFileContentRetriever $xml_file_content_retriever,
        private ServiceManager $service_manager,
        private ProjectCreationDataServiceFromXmlInheritor $from_xml_inheritor,
        private LoggerInterface $logger,
        private URLVerification $url_verification,
    ) {
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
            return $this->buildFromAnOtherProjectId($post_representation, $user);
        }

        if ($post_representation->xml_template_name !== null) {
            return $this->buildFromTemplateName($post_representation);
        }

        if ($post_representation->from_archive !== null) {
            return $this->buildFromArchive($post_representation);
        }

        return null;
    }

    private function buildFromAnOtherProjectId(ProjectPostRepresentation $post_representation, PFUser $user): ProjectCreationData
    {
        return ProjectCreationData::buildFromFormArray(
            new DefaultProjectVisibilityRetriever(),
            TemplateFromProjectForCreation::fromRESTRepresentation(
                $post_representation,
                $user,
                $this->project_manager,
                $this->url_verification
            ),
            $this->extractDataAsArray($post_representation)
        );
    }

    private function buildFromTemplateName(ProjectPostRepresentation $post_representation): ProjectCreationData
    {
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
                            'is_public' => $post_representation->is_public,
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
                    throw new \LogicException("should no t end here");
                }
            );
    }

    private function buildFromArchive(ProjectPostRepresentation $post_representation): ProjectCreationData
    {
        if (! CustomProjectArchiveFeatureFlag::canCreateFromCustomArchive()) {
            throw new RestException(400, 'Create from archive is not available on your platform');
        }

        return ProjectCreationData::buildFromArchive(
            new DefaultProjectVisibilityRetriever(),
            $this->extractDataAsArray($post_representation)
        );
    }

    private function extractDataAsArray(ProjectPostRepresentation $post_representation): array
    {
        $data = [
            'project' => [
                'form_short_description' => $post_representation->description,
                'is_test' => false,
                'is_public' => $post_representation->is_public,
                'trove' => CategoryCollection::buildFromRESTProjectCreation($post_representation),
                'form_unix_name' => $post_representation->shortname,
                'form_full_name' => $post_representation->label,
                'data_fields' => ProjectRegistrationSubmittedFieldsCollection::buildFromRESTProjectCreation($post_representation),
            ],
        ];

        if ($post_representation->allow_restricted !== null) {
            $data['project']['allow_restricted'] = $post_representation->allow_restricted;
        }
        return $data;
    }
}
