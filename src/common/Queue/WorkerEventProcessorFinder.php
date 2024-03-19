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

namespace Tuleap\Queue;

use ForgeConfig;
use MailPresenterFactory;
use TemplateRendererFactory;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Option\Option;
use Tuleap\Project\ProjectCreationNotifier;
use Tuleap\Project\Registration\Template\Upload\ExtractArchiveAndCreateProject;
use Tuleap\Project\Registration\Template\Upload\ProjectAfterArchiveImportActivation;
use Tuleap\Project\Registration\Template\Upload\UploadedArchiveForProjectArchiver;
use Tuleap\Project\Registration\Template\Upload\UploadedArchiveForProjectDao;
use TuleapRegisterMail;
use UserManager;
use XMLImportHelper;

final readonly class WorkerEventProcessorFinder implements FindWorkerEventProcessor
{
    public function findFromWorkerEvent(WorkerEvent $worker_event): Option
    {
        $project_manager = \ProjectManager::instance();
        $user_manager    = UserManager::instance();

        return match ($worker_event->getEventName()) {
            ExtractArchiveAndCreateProject::TOPIC =>
                Option::fromValue(
                    ExtractArchiveAndCreateProject::fromEvent(
                        $worker_event,
                        \ProjectXMLImporter::build(
                            new XMLImportHelper(\UserManager::instance()),
                            \ProjectCreator::buildSelfByPassValidation(),
                        ),
                        new ProjectAfterArchiveImportActivation(
                            new \ProjectDao(),
                            new ProjectCreationNotifier(
                                new TuleapRegisterMail(
                                    new MailPresenterFactory(),
                                    TemplateRendererFactory::build()->getRenderer(
                                        ForgeConfig::get('codendi_dir') . '/src/templates/mail/'
                                    ),
                                    $user_manager,
                                    new LocaleSwitcher(),
                                    "mail-project-register-admin"
                                ),
                                $worker_event->getLogger(),
                            ),
                            $project_manager,
                        ),
                        $project_manager,
                        $user_manager,
                        $user_manager,
                        new UploadedArchiveForProjectArchiver(ForgeConfig::get('sys_data_dir')),
                        new UploadedArchiveForProjectDao(),
                    ),
                ),
            default =>
                Option::nothing(WorkerEventProcessor::class),
        };
    }
}
