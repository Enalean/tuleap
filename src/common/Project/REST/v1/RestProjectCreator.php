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

use DateTimeImmutable;
use Luracast\Restler\RestException;
use Project;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Project\Admin\Categories\ProjectCategoriesException;
use Tuleap\Project\Admin\DescriptionFields\FieldDoesNotExistException;
use Tuleap\Project\Admin\DescriptionFields\MissingMandatoryFieldException;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForPlatformException;
use Tuleap\Project\Registration\MaxNumberOfProjectReachedForUserException;
use Tuleap\Project\Registration\ProjectDescriptionMandatoryException;
use Tuleap\Project\Registration\ProjectInvalidFullNameException;
use Tuleap\Project\Registration\ProjectInvalidShortNameException;
use Tuleap\Project\Registration\ProjectRegistrationChecker;
use Tuleap\Project\Registration\RegistrationErrorException;
use Tuleap\Project\Registration\RegistrationForbiddenException;
use Tuleap\Project\Registration\Template\InvalidTemplateException;
use Tuleap\Project\Registration\Template\NoTemplateProvidedFault;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\Upload\ProjectFileToUploadCreator;
use Tuleap\Project\REST\v1\Project\ProjectFromArchiveRepresentation;
use Tuleap\Project\REST\v1\Project\PostProjectCreated;
use Tuleap\Project\REST\v1\Project\ProjectRepresentationBuilder;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\Import\DirectoryArchive;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ImportNotValidException;

class RestProjectCreator
{
    public function __construct(
        private readonly \ProjectCreator $project_creator,
        private readonly \ProjectXMLImporter $project_XML_importer,
        private readonly TemplateFactory $template_factory,
        private readonly ProjectFileToUploadCreator $creator,
        private readonly ProjectRepresentationBuilder $builder,
        private readonly ProjectRegistrationChecker $checker,
    ) {
    }

    /**
     * @return Ok<PostProjectCreated>|Err<Fault>
     *@throws \Project_Creation_Exception
     * @throws ProjectInvalidFullNameException
     * @throws ProjectInvalidShortNameException
     * @throws ProjectDescriptionMandatoryException
     * @throws InvalidTemplateException
     *
     * @throws RestException
     */
    public function create(
        ProjectPostRepresentation $post_representation,
        ProjectCreationData $creation_data,
        \PFUser $user,
    ): Ok|Err {
        try {
            return $this->createProjectWithSelectedTemplate($post_representation, $creation_data, $user);
        } catch (MaxNumberOfProjectReachedForPlatformException | MaxNumberOfProjectReachedForUserException $exception) {
            throw new RestException(429, $exception->getMessage());
        } catch (RegistrationForbiddenException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (ProjectCategoriesException | FieldDoesNotExistException | MissingMandatoryFieldException | ImportNotValidException | RegistrationErrorException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    /**
     * @return Ok<PostProjectCreated>|Err<Fault>
     * @throws InvalidTemplateException
     * @throws \Project_Creation_Exception
     * @throws ProjectInvalidFullNameException
     * @throws ProjectInvalidShortNameException
     * @throws ProjectDescriptionMandatoryException
     *
     * @throws ImportNotValidException
     */
    private function createProjectWithSelectedTemplate(
        ProjectPostRepresentation $post_representation,
        ProjectCreationData $creation_data,
        \PFUser $current_user,
    ): Ok|Err {
        if ($post_representation->template_id !== null) {
            return Result::ok(PostProjectCreated::fromProject($this->builder, $current_user, $this->project_creator->processProjectCreation($creation_data)));
        }

        if ($post_representation->xml_template_name !== null) {
            $template = $this->template_factory->getTemplate($post_representation->xml_template_name);
            $xml_path = $template->getXMLPath();

            $archive = new DirectoryArchive(dirname($xml_path));

            return $this->project_XML_importer->importWithProjectData(
                new ImportConfig(),
                $archive,
                new SystemEventRunnerForProjectCreationFromXMLTemplate(),
                $creation_data
            )->andThen(function (Project $project) use ($template, $current_user) {
                return Result::ok(PostProjectCreated::fromProject($this->builder, $current_user, $this->template_factory->recordUsedTemplate($project, $template)));
            });
        }

        if ($post_representation->from_archive !== null) {
            $errors_collection = $this->checker->collectAllErrorsForProjectRegistration($current_user, $creation_data);
            if (count($errors_collection->getErrors()) > 0) {
                return Result::err(
                    Fault::fromMessage(implode(',', $errors_collection->getI18nErrorsMessages()))
                );
            }

            $file_creator = $this->creator->creatFileToUpload(
                $post_representation->from_archive,
                $current_user,
                new DateTimeImmutable()
            );

            return Result::ok(PostProjectCreated::fromArchive($this->builder, $current_user, new ProjectFromArchiveRepresentation($file_creator->getUploadHref())));
        }

        return Result::err(NoTemplateProvidedFault::build());
    }
}
