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
use Tuleap\Project\Registration\RegistrationErrorException;
use Tuleap\Project\Registration\RegistrationForbiddenException;
use Tuleap\Project\Registration\Template\InvalidTemplateException;
use Tuleap\Project\Registration\Template\NoTemplateProvidedFault;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\SystemEventRunnerForProjectCreationFromXMLTemplate;
use Tuleap\Project\XML\Import\DirectoryArchive;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ImportNotValidException;

class RestProjectCreator
{
    /**
     * @var \ProjectCreator
     */
    private $project_creator;
    /**
     * @var \ProjectXMLImporter
     */
    private $project_XML_importer;
    /**
     * @var TemplateFactory
     */
    private $template_factory;

    public function __construct(
        \ProjectCreator $project_creator,
        \ProjectXMLImporter $project_XML_importer,
        TemplateFactory $template_factory,
    ) {
        $this->project_creator      = $project_creator;
        $this->project_XML_importer = $project_XML_importer;
        $this->template_factory     = $template_factory;
    }

    /**
     * @throws RestException
     * @throws \Project_Creation_Exception
     * @throws ProjectInvalidFullNameException
     * @throws ProjectInvalidShortNameException
     * @throws ProjectDescriptionMandatoryException
     * @throws InvalidTemplateException
     *
     * @return Ok<Project>|Err<Fault>
     */
    public function create(
        ProjectPostRepresentation $post_representation,
        ProjectCreationData $creation_data,
    ): Ok|Err {
        try {
            return $this->createProjectWithSelectedTemplate($post_representation, $creation_data);
        } catch (MaxNumberOfProjectReachedForPlatformException | MaxNumberOfProjectReachedForUserException $exception) {
            throw new RestException(429, $exception->getMessage());
        } catch (RegistrationForbiddenException $exception) {
            throw new RestException(403, $exception->getMessage());
        } catch (ProjectCategoriesException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (FieldDoesNotExistException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (MissingMandatoryFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (ImportNotValidException | RegistrationErrorException $exception) {
            throw new RestException(400, $exception->getMessage());
        }
    }

    /**
     * @throws ImportNotValidException
     * @throws InvalidTemplateException
     * @throws \Project_Creation_Exception
     * @throws ProjectInvalidFullNameException
     * @throws ProjectInvalidShortNameException
     * @throws ProjectDescriptionMandatoryException
     *
     * @return Ok<Project>|Err<Fault>
     */
    private function createProjectWithSelectedTemplate(
        ProjectPostRepresentation $post_representation,
        ProjectCreationData $creation_data,
    ): Ok|Err {
        if ($post_representation->template_id !== null) {
            return Result::ok($this->project_creator->processProjectCreation(
                $creation_data
            ));
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
            )->andThen(function (Project $project) use ($template) {
                    return Result::ok($this->template_factory->recordUsedTemplate($project, $template));
            });
        }

        return Result::err(NoTemplateProvidedFault::build());
    }
}
