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

namespace Tuleap\PdfTemplate\Admin\Image;

use HTTPRequest;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\PdfTemplate\Admin\CSRFTokenProvider;
use Tuleap\PdfTemplate\Admin\Navigation;
use Tuleap\PdfTemplate\Admin\RenderAPresenter;
use Tuleap\PdfTemplate\Admin\UserCanManageTemplatesChecker;
use Tuleap\PdfTemplate\Image\PdfTemplateImage;
use Tuleap\PdfTemplate\Image\RetrieveAllImages;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

final class IndexImagesController implements DispatchableWithBurningParrot, DispatchableWithRequest
{
    public const ROUTE = '/pdftemplate/admin/images';

    public function __construct(
        private RenderAPresenter $admin_page_renderer,
        private UserCanManageTemplatesChecker $can_manage_templates_checker,
        private CSRFTokenProvider $token_provider,
        private RetrieveAllImages $retriever,
        private UsageDetector $usage_detector,
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
                    __DIR__ . '/../../../../scripts/admin/frontend-assets',
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
            dirname(__DIR__),
            'Image/index',
            new IndexImagesPresenter(
                Navigation::inImages(),
                $this->token_provider->getToken(),
                array_map(
                    fn (PdfTemplateImage $image) => PdfTemplateImagePresenter::fromImage($image, $current_user, $this->usage_detector, $this->provide_user_avatar_url),
                    $this->retriever->retrieveAll(),
                ),
            ),
        );
    }
}
