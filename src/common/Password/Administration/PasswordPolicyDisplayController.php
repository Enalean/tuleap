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

namespace Tuleap\Password\Administration;

use HTTPRequest;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\Request\DispatchableWithRequest;

final class PasswordPolicyDisplayController implements DispatchableWithRequest
{
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var TemplateRenderer
     */
    private $template_renderer;
    /**
     * @var PasswordConfigurationRetriever
     */
    private $password_configuration_retriever;

    public function __construct(
        AdminPageRenderer $admin_page_renderer,
        TemplateRendererFactory $template_renderer_factory,
        PasswordConfigurationRetriever $password_configuration_retriever,
    ) {
        $this->admin_page_renderer              = $admin_page_renderer;
        $this->template_renderer                = $template_renderer_factory->getRenderer(
            __DIR__ . '/../../../templates/admin/password/'
        );
        $this->password_configuration_retriever = $password_configuration_retriever;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $request->checkUserIsSuperUser();

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../scripts/site-admin/frontend-assets', '/assets/core/site-admin'),
                'site-admin-password-policy.js'
            )
        );

        $this->admin_page_renderer->header('Password requirements');
        $this->template_renderer->renderToPage(
            'password_policy',
            new PasswordPolicyPresenter(
                new \CSRFSynchronizerToken($request->getFromServer('REQUEST_URI')),
                $this->password_configuration_retriever->getPasswordConfiguration()->isBreachedPasswordVerificationEnabled()
            )
        );
        $this->admin_page_renderer->footer();
    }
}
