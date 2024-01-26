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

namespace Tuleap\TrackerFunctions\Administration;

use HTTPRequest;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeCoreAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;
use Tuleap\REST\ExplorerEndpointAvailableEvent;
use Tuleap\Tracker\Artifact\RetrieveTracker;
use Tuleap\TrackerFunctions\Logs\LogLinePresenterBuilder;
use Tuleap\TrackerFunctions\Logs\FunctionLogLineWithArtifact;
use Tuleap\TrackerFunctions\Logs\RetrieveLogsForTracker;
use Tuleap\TrackerFunctions\WASM\WASMFunctionPathHelper;

final class AdministrationController implements DispatchableWithRequest, DispatchableWithBurningParrot, DispatchableWithProject
{
    public function __construct(
        private readonly RetrieveTracker $retrieve_tracker,
        private readonly \Tracker_IDisplayTrackerLayout $tracker_layout,
        private readonly \TemplateRendererFactory $renderer_factory,
        private readonly TrackerCSRFTokenProvider $token_provider,
        private readonly WASMFunctionPathHelper $function_path_helper,
        private readonly CheckFunctionIsActivated $check_function_is_activated,
        private readonly RetrieveLogsForTracker $logs_for_tracker,
        private readonly LogLinePresenterBuilder $log_line_presenter_builder,
        private readonly EventDispatcherInterface $event_dispatcher,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $tracker = $this->getTracker((int) $variables['id']);

        $current_user = $request->getCurrentUser();
        if (! $tracker->userIsAdmin($current_user)) {
            throw new NotFoundException();
        }

        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/admin/frontend-assets',
                    '/assets/tracker_functions/admin'
                ),
                'src/index.ts'
            )
        );

        $logs = $this->logs_for_tracker->searchLogsByTrackerId($tracker->getId());
        if (count($logs) > 0) {
            $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons(new IncludeCoreAssets(), 'syntax-highlight'));
            $layout->addJavascriptAsset(
                new JavascriptAsset(
                    new IncludeCoreAssets(),
                    'syntax-highlight.js'
                )
            );
            $layout->includeFooterJavascriptFile(RelativeDatesAssetsRetriever::retrieveAssetsUrl());
        }

        $tracker->displayAdminItemHeaderBurningParrot(
            $this->tracker_layout,
            'editworkflow',
            dgettext('tuleap-tracker_functions', 'Tuleap Functions for Trackers'),
        );

        $wasm_function_path    = $this->function_path_helper->getPathForTracker($tracker);
        $has_uploaded_function = is_readable($wasm_function_path);

        $renderer = $this->renderer_factory->getRenderer(__DIR__);
        $renderer->renderToPage(
            'administration',
            new AdministrationPresenter(
                UpdateFunctionController::getUrl($tracker),
                RemoveFunctionController::getUrl($tracker),
                ActivateFunctionController::getUrl($tracker),
                CSRFSynchronizerTokenPresenter::fromToken($this->token_provider->getToken($tracker)),
                $has_uploaded_function,
                $this->check_function_is_activated->isFunctionActivated($tracker->getId()),
                array_map(
                    fn (FunctionLogLineWithArtifact $log) => $this->log_line_presenter_builder->getPresenter($log, $current_user),
                    $logs,
                ),
                $this->event_dispatcher->dispatch(new ExplorerEndpointAvailableEvent())->getEndpointURL(),
            )
        );

        $tracker->displayFooter($this->tracker_layout);
    }

    public static function getUrl(\Tracker $tracker): string
    {
        return '/tracker_functions/' . urlencode((string) $tracker->getId()) . '/admin';
    }

    public function getProject(array $variables): Project
    {
        return $this
            ->getTracker((int) $variables['id'])
            ->getProject();
    }

    private function getTracker(int $id): \Tracker
    {
        $tracker = $this->retrieve_tracker->getTrackerById($id);
        if (! $tracker) {
            throw new NotFoundException();
        }

        if ($tracker->isDeleted()) {
            throw new NotFoundException();
        }

        return $tracker;
    }
}
