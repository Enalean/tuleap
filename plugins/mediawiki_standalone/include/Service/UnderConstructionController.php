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

namespace Tuleap\MediawikiStandalone\Service;

use HTTPRequest;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\MediawikiStandalone\Instance\CheckOngoingInitializationStatus;
use Tuleap\Plugin\IsProjectAllowedToUsePlugin;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final class UnderConstructionController implements DispatchableWithRequest, DispatchableWithBurningParrot, DispatchableWithProject
{
    public const string PROJECT_NAME_VARIABLE_NAME = 'project_name';

    public function __construct(
        private readonly ProjectByUnixNameFactory $project_retriever,
        private readonly IsProjectAllowedToUsePlugin $plugin,
        private readonly \TemplateRendererFactory $renderer_factory,
        private readonly CheckOngoingInitializationStatus $check_ongoing_initializations_status,
    ) {
    }

    /**
     * @throws \Tuleap\Request\NotFoundException
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);

        $service = $project->getService(MediawikiStandaloneService::SERVICE_SHORTNAME);
        if (! $service instanceof MediawikiStandaloneService) {
            throw new NotFoundException();
        }

        $ongoing_initialization_status = $this->check_ongoing_initializations_status->getStatus($project);
        if (! $ongoing_initialization_status->isOngoing() && ! $ongoing_initialization_status->isError()) {
            $layout->permanentRedirect($service->getUrl());
        }

        $service->displayMediawikiHeader($request->getCurrentUser());
        $this->renderer_factory
            ->getRenderer(__DIR__ . '/../../templates')
            ->renderToPage(
                $ongoing_initialization_status->isError() ? 'under-construction-error' : 'under-construction',
                [
                    'project_icon' => EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat($project->getIconUnicodeCodepoint()),
                    'project_name' => $project->getPublicName(),
                ],
            );
        $service->displayFooter();
    }

    /**
     * @throws \Tuleap\Request\NotFoundException
     */
    #[\Override]
    public function getProject(array $variables): Project
    {
        if (! isset($variables[self::PROJECT_NAME_VARIABLE_NAME])) {
            throw new NotFoundException();
        }

        $project = $this->project_retriever->getProjectByCaseInsensitiveUnixName($variables[self::PROJECT_NAME_VARIABLE_NAME]);
        if (! $project || $project->isError()) {
            throw new NotFoundException();
        }

        if (! $this->plugin->isAllowed($project->getID())) {
            throw new NotFoundException();
        }

        return $project;
    }
}
