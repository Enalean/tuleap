<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Admin\Export;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Dashboard\Project\DashboardXMLExporter;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\Banner\BannerDao;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\ProjectIsInactiveException;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Project\XML\ArchiveInterface;
use Tuleap\Project\XML\Export\ExportOptions;
use Tuleap\Project\XML\Export\ZipArchive;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;
use Tuleap\User\XML\UserXMLExportedDevNullCollection;
use Tuleap\Widget\WidgetFactory;

final class ProjectXmlExportController extends DispatchablePSR15Compatible implements DispatchableWithProject
{
    private string $archive_name;

    public function __construct(
        private BinaryFileResponseBuilder $binary_file_response_builder,
        private ProjectRetriever $project_retriever,
        private ProjectAdministratorChecker $administrator_checker,
        private ProjectAccessChecker $project_access_checker,
        private \UserManager $user_manager,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);

        $this->archive_name = (string) tempnam(\ForgeConfig::get('tmp_dir'), 'project-export');
        if (! $this->archive_name) {
            throw new CannotCreateTmpFileToExportProjectException();
        }
    }

    public function __destruct()
    {
        if (is_file($this->archive_name)) {
            unlink($this->archive_name);
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $request_variables = $request->getAttributes();
        $project           = $this->getProject($request_variables);
        $user              = $this->user_manager->getCurrentUser();

        try {
            $this->project_access_checker->checkUserCanAccessProject($user, $project);
        } catch (\Exception $e) {
            throw new ForbiddenException();
        }
        $this->administrator_checker->checkUserIsProjectAdministrator($user, $project);

        $this->buildArchive($project, $user);

        return $this->binary_file_response_builder->fromFilePath(
            $request,
            $this->archive_name,
            $project->getUnixNameMixedCase() . '.zip',
            'application/zip'
        );
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(array $variables): \Project
    {
        if (! isset($variables['project_id'])) {
            throw new NotFoundException();
        }

        return $this->project_retriever->getProjectFromId($variables['project_id']);
    }

    private function buildArchive(\Project $project, \PFUser $user): void
    {
        $rng_validator    = new \XML_RNGValidator();
        $users_collection = new UserXMLExportedDevNullCollection($rng_validator, new \XML_SimpleXMLCDATAFactory());

        $widget_factory = new WidgetFactory(
            $this->user_manager,
            new \User_ForgeUserGroupPermissionsManager(new \User_ForgeUserGroupPermissionsDao()),
            \EventManager::instance(),
        );
        $widget_dao     = new DashboardWidgetDao($widget_factory);
        $xml_exporter   = new \ProjectXMLExporter(
            \EventManager::instance(),
            new \UGroupManager(),
            $rng_validator,
            new \UserXMLExporter($this->user_manager, $users_collection),
            new DashboardXMLExporter(
                new ProjectDashboardRetriever(
                    new ProjectDashboardDao(
                        $widget_dao
                    )
                ),
                new \Tuleap\Dashboard\Widget\DashboardWidgetRetriever($widget_dao),
                $widget_factory,
                \ProjectXMLExporter::getLogger()
            ),
            new SynchronizedProjectMembershipDetector(new SynchronizedProjectMembershipDao()),
            \ProjectXMLExporter::getLogger(),
            new BannerRetriever(new BannerDao()),
        );

        $archive = new ZipArchive($this->archive_name);

        $temporary_dump_path_on_filesystem = $archive->getArchivePath() . time();

        try {
            $xml_content = $xml_exporter->export(
                $project,
                new ExportOptions(
                    ExportOptions::MODE_STRUCTURE,
                    false,
                    []
                ),
                $user,
                $archive,
                $temporary_dump_path_on_filesystem
            );

            $users_xml_content = $users_collection->toXML();

            $archive->addFromString(ArchiveInterface::PROJECT_FILE, $xml_content);
            $archive->addFromString(ArchiveInterface::USER_FILE, $users_xml_content);

            $archive->close();
        } catch (ProjectIsInactiveException $exception) {
            throw new ForbiddenException('Only active projects can be exported.');
        } finally {
            $system_command = new \System_Command();
            $command        = "rm -rf $temporary_dump_path_on_filesystem";
            $system_command->exec($command);
        }
    }
}
