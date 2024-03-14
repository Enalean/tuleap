<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Fault;
use Tuleap\Project\ImportFromArchive;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Project\XML\Import\ImportConfig;
use Tuleap\Project\XML\Import\ZipArchive;
use Tuleap\Queue\WorkerEvent;
use Tuleap\Queue\WorkerEventProcessor;
use Tuleap\User\ForceLogin;
use Tuleap\User\RetrieveUserById;

final readonly class ExtractArchiveAndCreateProject implements WorkerEventProcessor
{
    public const TOPIC = 'tuleap.project.create-from-archive';

    private function __construct(
        private ImportFromArchive $importer,
        private ActivateProjectAfterArchiveImport $activator,
        private ProjectByIDFactory $project_manager,
        private RetrieveUserById $user_manager,
        private ForceLogin $force_login,
        private LoggerInterface $logger,
        private int $project_id,
        private string $filename,
        private int $user_id,
    ) {
    }

    public static function fromEvent(
        WorkerEvent $event,
        ImportFromArchive $importer,
        ActivateProjectAfterArchiveImport $activator,
        ProjectByIDFactory $project_manager,
        RetrieveUserById $user_manager,
        ForceLogin $force_login,
    ): WorkerEventProcessor {
        $payload = $event->getPayload();
        if (! isset($payload['project_id']) || ! is_int($payload['project_id'])) {
            throw new \Exception(sprintf('Payload doesnt have project_id or project_id is not integer: %s', var_export($payload, true)));
        }

        if (! isset($payload['filename']) || ! is_string($payload['filename'])) {
            throw new \Exception(sprintf('Payload doesnt have filename or filename is not string: %s', var_export($payload, true)));
        }

        if (! isset($payload['user_id']) || ! is_int($payload['user_id'])) {
            throw new \Exception(sprintf('Payload doesnt have user_id or user_id is not integer: %s', var_export($payload, true)));
        }

        return new self(
            $importer,
            $activator,
            $project_manager,
            $user_manager,
            $force_login,
            $event->getLogger(),
            $payload['project_id'],
            $payload['filename'],
            $payload['user_id'],
        );
    }

    public function process(): void
    {
        $project = $this->project_manager->getValidProjectById($this->project_id);

        $user = $this->user_manager->getUserById($this->user_id);
        if (! $user) {
            $this->logger->error("Cannot find user #{$this->user_id} to continue import of project");
            throw new \Exception("Cannot find user #{$this->user_id} to continue import of project");
        }
        $this->force_login->forceLogin($user->getUserName());

        $this->importer->importFromArchive(
            new ImportConfig(),
            (int) $project->getID(),
            new ZipArchive($this->filename, \ForgeConfig::get('tmp_dir')),
        )->match(
            function () use ($project): void {
                $this->activator->activateProject($project);
                $this->logger->info("Successfully imported archive into project #{$project->getID()}");
            },
            function (Fault $fault) use ($project): void {
                $this->logger->error("Unable to import archive into project #{$project->getID()}");
                Fault::writeToLogger($fault, $this->logger);
            }
        );
        unlink($this->filename);
    }
}
