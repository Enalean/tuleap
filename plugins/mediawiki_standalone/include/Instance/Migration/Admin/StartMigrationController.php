<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration\Admin;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\MediawikiStandalone\Instance\Migration\MigrateInstanceTask;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationsState;
use Tuleap\Plugin\IsProjectAllowedToUsePlugin;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Queue\EnqueueTaskInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class StartMigrationController extends DispatchablePSR15Compatible
{
    public const string URL = '/mediawiki_standalone/admin/migrations';

    public function __construct(
        private readonly CSRFSynchronizerTokenProvider $token_provider,
        private readonly ProjectReadyToBeMigratedVerifier $to_migrate_dao,
        private readonly IsProjectAllowedToUsePlugin $plugin,
        private readonly ProjectByIDFactory $project_manager,
        private readonly EnqueueTaskInterface $enqueue_task,
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly OngoingInitializationsState $ongoing_initializations_state,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->token_provider->getCSRF()->check();

        $body = $request->getParsedBody();
        if (! is_array($body)) {
            throw new NotFoundException();
        }

        if (! isset($body['project'])) {
            throw new NotFoundException();
        }

        try {
            $project = $this->project_manager->getValidProjectById((int) $body['project']);
        } catch (\Project_NotFoundException $e) {
            throw new NotFoundException();
        }

        if (! $this->plugin->isAllowed((int) $project->getID())) {
            throw new ForbiddenException(
                dgettext(
                    'tuleap-mediawiki_standalone',
                    'The project is not allowed to use MediaWiki standalone.'
                )
            );
        }

        if (! $this->to_migrate_dao->isProjectReadyToBeMigrated((int) $project->getID())) {
            throw new ForbiddenException(
                dgettext(
                    'tuleap-mediawiki_standalone',
                    'Cannot start MediaWiki standalone migration. Make sure that project is using legacy MediaWiki service and migration is not already started.'
                )
            );
        }

        $this->ongoing_initializations_state->startInitialization($project);
        $this->enqueue_task->enqueue(new MigrateInstanceTask($project));

        $user = $request->getAttribute(\PFUser::class);
        assert($user instanceof \PFUser);

        return $this->redirect_with_feedback_factory->createResponseForUser(
            $user,
            DisplayMigrationController::URL,
            new NewFeedback(
                \Feedback::SUCCESS,
                sprintf(
                    dgettext(
                        'tuleap-mediawiki_standalone',
                        'MediaWiki migration has been started for %s. It might take some time to be effective.'
                    ),
                    $project->getIconAndPublicName(),
                ),
            ),
        );
    }
}
