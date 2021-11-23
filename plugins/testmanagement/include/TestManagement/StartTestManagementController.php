<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use Feedback;
use HTTPRequest;
use Project;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\TestManagement\Breadcrumbs\NoCrumb;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;

class StartTestManagementController
{
    /**
     * @var ArtifactLinksUsageUpdater
     */
    private $artifact_link_usage_updater;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var FirstConfigCreator
     */
    private $config_creator;
    /**
     * @var DBTransactionExecutorWithConnection
     */
    private $transaction_executor;

    public function __construct(
        ArtifactLinksUsageUpdater $artifact_link_usage_updater,
        \CSRFSynchronizerToken $csrf_token,
        DBTransactionExecutorWithConnection $transaction_executor,
        FirstConfigCreator $config_creator
    ) {
        $this->artifact_link_usage_updater = $artifact_link_usage_updater;
        $this->csrf_token                  = $csrf_token;
        $this->config_creator              = $config_creator;
        $this->transaction_executor        = $transaction_executor;
    }

    public function misconfiguration(HTTPRequest $request): string
    {
        $current_user  = $request->getCurrentUser();
        $project_id    = (int) $request->getProject()->getID();
        $is_user_admin = $current_user->isAdmin($project_id);

        return $this->getRenderer()->renderToString(
            'misconfiguration',
            new StartTestManagementPresenter(
                $is_user_admin,
                $this->csrf_token,
                $project_id
            )
        );
    }

    public function createConfig(\HTTPRequest $request): void
    {
        $this->csrf_token->check();
        $project = $request->getProject();

        try {
            $this->transaction_executor->execute(
                function () use ($project): void {
                    $this->config_creator->createConfigForProjectFromXML($project);
                }
            );
            $this->allowProjectToUseType(
                $project,
                $project
            );

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-testmanagement', 'We configured Test Management for you. Enjoy!')
            );
        } catch (TrackerComesFromLegacyEngineException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext('tuleap-testmanagement', 'We tried to configure Test Management for you but an existing tracker (%1$s) is using Tracker Engine v3 and prevented it.'),
                    $exception->getTrackerShortname()
                )
            );

            $this->redirectToTestManagementHomepage($project);
        } catch (TrackerNotCreatedException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                dgettext('tuleap-testmanagement', 'We tried to configure Test Management for you but an internal error prevented it.')
            );

            $this->redirectToTestManagementHomepage($project);
        }
    }

    private function redirectToTestManagementHomepage(Project $project): void
    {
        $GLOBALS['Response']->redirect(TESTMANAGEMENT_BASE_URL . '/?group_id=' . urlencode((string) $project->getID()));
    }

    public function getBreadcrumbs(): NoCrumb
    {
        return new NoCrumb();
    }

    private function allowProjectToUseType(
        Project $template,
        Project $project
    ): void {
        if (! $this->artifact_link_usage_updater->isProjectAllowedToUseArtifactLinkTypes($template)) {
            $this->artifact_link_usage_updater->forceUsageOfArtifactLinkTypes($project);
        }
    }

    private function getRenderer(): \TemplateRenderer
    {
        $templates_path = join(
            '/',
            [
                TESTMANAGEMENT_BASE_DIR,
                'templates'
            ]
        );

        return \TemplateRendererFactory::build()->getRenderer($templates_path);
    }
}
