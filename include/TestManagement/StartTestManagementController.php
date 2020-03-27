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
use Psr\Log\LoggerInterface;
use TrackerFactory;
use TrackerXmlImport;
use Tuleap\TestManagement\Administration\TrackerChecker;
use Tuleap\TestManagement\Breadcrumbs\NoCrumb;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;

class StartTestManagementController
{
    /**
     * @var LoggerInterface
     */
    private $backend_logger;

    /**
     * @var TrackerXmlImport
     */
    private $tracker_xml_import;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var ArtifactLinksUsageUpdater
     */
    private $artifact_link_usage_updater;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    /**
     * @var TrackerChecker
     */
    private $tracker_checker;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        TrackerFactory $tracker_factory,
        LoggerInterface $backend_logger,
        TrackerXmlImport $tracker_xml_import,
        ArtifactLinksUsageUpdater $artifact_link_usage_updater,
        \CSRFSynchronizerToken $csrf_token,
        TrackerChecker $tracker_checker,
        Config $config
    ) {
        $this->tracker_factory             = $tracker_factory;
        $this->tracker_xml_import          = $tracker_xml_import;
        $this->backend_logger              = $backend_logger;
        $this->artifact_link_usage_updater = $artifact_link_usage_updater;
        $this->csrf_token                  = $csrf_token;
        $this->tracker_checker             = $tracker_checker;
        $this->config                      = $config;
    }

    public function misconfiguration(HTTPRequest $request): string
    {
        $current_user   = $request->getCurrentUser();
        $project_id     = $request->getProject()->getID();
        $is_user_admin  = $current_user->isAdmin($project_id);

        return $this->getRenderer()->renderToString(
            'misconfiguration',
            new StartTestManagementPresenter(
                $is_user_admin,
                $this->csrf_token
            )
        );
    }

    public function createConfig(\HTTPRequest $request): void
    {
        $this->csrf_token->check();
        $project = $request->getProject();

        $config_creator = new FirstConfigCreator(
            $this->config,
            $this->tracker_factory,
            $this->tracker_xml_import,
            $this->tracker_checker,
            $this->backend_logger
        );

        try {
            $config_creator->createConfigForProjectFromXML($project);

            $this->allowProjectToUseNature(
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

    private function allowProjectToUseNature(
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
            array(
                TESTMANAGEMENT_BASE_DIR,
                'templates'
            )
        );

        return \TemplateRendererFactory::build()->getRenderer($templates_path);
    }
}
