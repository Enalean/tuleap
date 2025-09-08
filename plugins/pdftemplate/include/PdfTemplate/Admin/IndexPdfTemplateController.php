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

namespace Tuleap\PdfTemplate\Admin;

use HTTPRequest;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\PdfTemplate\RetrieveAllTemplates;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

final readonly class IndexPdfTemplateController implements DispatchableWithBurningParrot, DispatchableWithRequest
{
    public const ROUTE = '/pdftemplate/admin';

    public function __construct(
        private RenderAPresenter $admin_page_renderer,
        private UserCanManageTemplatesChecker $can_manage_templates_checker,
        private RetrieveAllTemplates $templates_retriever,
        private CSRFTokenProvider $token_provider,
        private ProvideUserAvatarUrl $provide_user_avatar_url,
    ) {
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        $this->can_manage_templates_checker->checkUserCanManageTemplates($current_user);

        $layout->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/admin/frontend-assets',
                    '/assets/pdftemplate/admin'
                ),
                'src/index.ts'
            )
        );
        $layout->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());

        $this->admin_page_renderer->renderAPresenter(
            $layout,
            $current_user,
            dgettext('tuleap-pdftemplate', 'PDF Template'),
            __DIR__,
            'index',
            new IndexPdfTemplatePresenter(
                Navigation::inTemplates(),
                DisplayPdfTemplateCreationFormController::ROUTE,
                DeletePdfTemplateController::ROUTE,
                $this->templates_retriever->retrieveAll(),
                $this->token_provider->getToken(),
                $current_user,
                $this->provide_user_avatar_url,
            ),
        );
    }
}
