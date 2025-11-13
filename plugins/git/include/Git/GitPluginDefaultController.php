<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Git;

use Git;
use GitPlugin;
use Override;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\ServiceInstrumentation;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\DispatchableWithThemeSelection;
use Tuleap\Request\NotFoundException;

final readonly class GitPluginDefaultController implements DispatchableWithRequest, DispatchableWithThemeSelection
{
    public function __construct(private RouterLink $router_link, private EventDispatcherInterface $event_manager)
    {
    }

    #[Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $request->getProject()->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Git service is disabled.'));
        }

        ServiceInstrumentation::increment('git');

        $this->event_manager->dispatch(
            new GitAdditionalActionEvent($request)
        );

        $this->router_link->process($request);
    }

    #[Override]
    public function isInABurningParrotPage(\Tuleap\HTTPRequest $request, array $variables): bool
    {
        return match ($request->get('action')) {
            Git::ADMIN_GIT_ADMINS_ACTION, Git::ADMIN_ACTION, Git::ADMIN_GERRIT_TEMPLATES_ACTION => true,
            default            => false,
        };
    }
}
