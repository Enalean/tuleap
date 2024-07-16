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
use Tuleap\Export\Pdf\Template\Identifier\InvalidPdfTemplateIdentifierStringException;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\PdfTemplate\RetrieveTemplate;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

final readonly class DisplayPdfTemplateDuplicateFormController implements DispatchableWithBurningParrot, DispatchableWithRequest
{
    public const ROUTE = '/pdftemplate/admin/duplicate';

    public function __construct(
        private RenderAPresenter $admin_page_renderer,
        private UserCanManageTemplatesChecker $can_manage_templates_checker,
        private PdfTemplateIdentifierFactory $identifier_factory,
        private RetrieveTemplate $retriever,
        private CSRFTokenProvider $token_provider,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        $this->can_manage_templates_checker->checkUserCanManageTemplates($current_user);

        try {
            $identifier = $this->identifier_factory->buildFromHexadecimalString($variables['id']);
        } catch (InvalidPdfTemplateIdentifierStringException) {
            throw new NotFoundException();
        }

        $template = $this->retriever->retrieveTemplate($identifier);
        if (! $template) {
            throw new NotFoundException();
        }

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
            'create-or-update-template',
            DisplayPdfTemplateCreationOrUpdateFormPresenter::forDuplication(
                $template,
                $this->token_provider->getToken(),
                $current_user,
            ),
        );
    }
}
