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

declare(strict_types=1);

namespace Tuleap\Project\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Project;
use ProjectCreationData;
use Tuleap\Project\Admin\Categories\CategoryCollection;
use Tuleap\Project\Admin\Categories\ProjectCategoriesException;
use Tuleap\Project\Admin\Categories\ProjectCategoriesUpdater;
use Tuleap\Project\Admin\DescriptionFields\FieldDoesNotExistException;
use Tuleap\Project\Admin\DescriptionFields\FieldUpdator;
use Tuleap\Project\Admin\DescriptionFields\MissingMandatoryFieldException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedException;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Registration\RegistrationForbiddenException;
use Tuleap\Project\Registration\Template\InvalidTemplateException;
use Tuleap\Project\Registration\Template\InvalidXMLTemplateNameException;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\Import\DirectoryArchive;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ImportNotValidException;
use Tuleap\Project\XML\XMLFileContentRetriever;

class RestProjectCreator
{
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
     * @var \Psr\Log\LoggerInterface
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
    /**
     * @var TemplateFactory
     */
    private $template_factory;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;
    /**
     * @var ProjectCategoriesUpdater
     */
    private $categories_updater;
    /**
     * @var FieldUpdator
     */
    private $fields_updater;

    public function __construct(
        \ProjectManager $project_manager,
        \ProjectCreator $project_creator,
        XMLFileContentRetriever $XML_file_content_retriever,
        \ServiceManager $service_manager,
        \Psr\Log\LoggerInterface $logger,
        \XML_RNGValidator $validator,
        \ProjectXMLImporter $project_XML_importer,
        TemplateFactory $template_factory,
        ProjectRegistrationUserPermissionChecker $permission_checker,
        ProjectCategoriesUpdater $categories_updater,
        FieldUpdator $fields_updater
    ) {
        $this->project_manager            = $project_manager;
        $this->project_creator            = $project_creator;
        $this->XML_file_content_retriever = $XML_file_content_retriever;
        $this->service_manager            = $service_manager;
        $this->logger                     = $logger;
        $this->validator                  = $validator;
        $this->project_XML_importer       = $project_XML_importer;
        $this->template_factory           = $template_factory;
        $this->permission_checker         = $permission_checker;
        $this->categories_updater         = $categories_updater;
        $this->fields_updater = $fields_updater;
    }

    /**
     * @throws RestException
     * @throws \Project_Creation_Exception
     * @throws \Project_InvalidFullName_Exception
     * @throws \Project_InvalidShortName_Exception
     * @throws \Tuleap\Project\ProjectDescriptionMandatoryException
     * @throws InvalidTemplateException
     */
    public function create(PFUser $user, ProjectPostRepresentation $post_representation): Project
    {
        try {
            $this->permission_checker->checkUserCreateAProject($user);
            $category_collection = $this->getCategoryCollection($post_representation);
            $this->categories_updater->checkCollectionConsistency($category_collection);

            $field_collection = $this->getFieldCollection($post_representation);
            $this->fields_updater->checkFieldConsistency($field_collection);

            $project = $this->createProjectWithSelectedTemplate($user, $post_representation);
            $this->categories_updater->update($project, $category_collection);
            $this->fields_updater->updateFromArray($field_collection, $project);
            return $project;
        } catch (MaxNumberOfProjectReachedException $exception) {
            throw new RestException(429, 'Too many projects were created');
        } catch (RegistrationForbiddenException $exception) {
            throw new RestException(403, 'You are not allowed to create new projects');
        } catch (ProjectCategoriesException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (FieldDoesNotExistException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (MissingMandatoryFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (ImportNotValidException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    /**
     * @throws ImportNotValidException
     * @throws InvalidTemplateException
     * @throws \Project_Creation_Exception
     * @throws \Project_InvalidFullName_Exception
     * @throws \Project_InvalidShortName_Exception
     * @throws \Tuleap\Project\ProjectDescriptionMandatoryException
     */
    public function createProjectWithSelectedTemplate(PFUser $user, ProjectPostRepresentation $post_representation): Project
    {
        if ($post_representation->template_id !== null) {
            return $this->createProjectFromTemplateId($post_representation, $user);
        }

        if ($post_representation->xml_template_name !== null) {
            return $this->createProjectFromSystemTemplate($post_representation);
        }

        throw new InvalidXMLTemplateNameException();
    }

    /**
     * @throws \Project_Creation_Exception
     * @throws \Project_InvalidFullName_Exception
     * @throws \Project_InvalidShortName_Exception
     * @throws \Tuleap\Project\ProjectDescriptionMandatoryException
     * @throws InvalidTemplateException
     */
    private function createProjectFromTemplateId(
        ProjectPostRepresentation $post_representation,
        PFUser $current_user
    ): Project {
        $data = [
            'project' => [
                'form_short_description' => $post_representation->description,
                'is_test'                => false,
                'is_public'              => $post_representation->is_public,
            ]
        ];

        if ($post_representation->allow_restricted !== null) {
            $data['project']['allow_restricted'] = $post_representation->allow_restricted;
        }

        return $this->project_creator->createFromRest(
            $post_representation->shortname,
            $post_representation->label,
            TemplateFromProjectForCreation::fromRESTRepresentation($post_representation, $current_user, $this->project_manager),
            $data
        );
    }

    /**
     * @throws InvalidTemplateException
     * @throws ImportNotValidException
     */
    private function createProjectFromSystemTemplate(ProjectPostRepresentation $post_representation): Project
    {
        $template    = $this->template_factory->getTemplate($post_representation->xml_template_name);
        $xml_path    = $template->getXMLPath();
        $xml_element = $this->XML_file_content_retriever->getSimpleXMLElementFromFilePath($xml_path);

        $data = ProjectCreationData::buildFromXML(
            $xml_element,
            $this->validator,
            $this->service_manager,
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

        $archive = new DirectoryArchive(dirname($xml_path));

        return $this->template_factory->recordUsedTemplate(
            $this->project_XML_importer->importWithProjectData(
                new ImportConfig(),
                $archive,
                new SystemEventRunnerForProjectCreationFromXMLTemplate(),
                $data
            ),
            $template,
        );
    }

    private function getCategoryCollection(ProjectPostRepresentation $project_post_representation): CategoryCollection
    {
        $categories = [];
        if ($project_post_representation->categories !== null) {
            foreach ($project_post_representation->categories as $category_representation) {
                $category = new \TroveCat($category_representation->category_id, '', '');
                $category->addChildren(new \TroveCat($category_representation->value_id, '', ''));
                $categories[] = $category;
            }
        }
        return new CategoryCollection(...$categories);
    }

    private function getFieldCollection(ProjectPostRepresentation $post_representation): array
    {
        $fields = [];

        if ($post_representation->fields !== null) {
            foreach ($post_representation->fields as $field_representation) {
                $fields[$field_representation->field_id] = $field_representation->value;
            }
        }

        return $fields;
    }
}
