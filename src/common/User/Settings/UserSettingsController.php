<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\User\Settings;

use HTTPRequest;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;

final class UserSettingsController implements DispatchableWithRequest
{
    private TemplateRenderer $template_renderer;

    public function __construct(
        private readonly AdminPageRenderer $admin_page_renderer,
        TemplateRendererFactory $template_renderer_factory,
    ) {
        $this->template_renderer = $template_renderer_factory->getRenderer(
            __DIR__ . '/../../../templates/admin/users/'
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $request->checkUserIsSuperUser();

        $this->admin_page_renderer->header(_('User settings'));
        $this->template_renderer->renderToPage(
            'moderation',
            new UserSettingsPresenter(
                new \CSRFSynchronizerToken("/admin/user-settings/"),
            )
        );
        $this->admin_page_renderer->footer();
    }
}
