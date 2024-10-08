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

use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\PdfTemplate\Admin\Image\IndexImagesController;
use Tuleap\PdfTemplate\Image\DisplayImagePresenter;
use Tuleap\PdfTemplate\Variable\Variable;
use Tuleap\PdfTemplate\Variable\VariablePresenter;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\User\Avatar\ProvideUserAvatarUrl;

/**
 * @psalm-immutable
 */
final readonly class DisplayPdfTemplateCreationOrUpdateFormPresenter
{
    public CSRFSynchronizerTokenPresenter $csrf;
    public bool $has_images;
    /**
     * @var list<VariablePresenter>
     */
    public array $variables;
    public string $variables_for_preview;

    /**
     * @param list<DisplayImagePresenter> $images
     */
    private function __construct(
        public Navigation $navigation,
        public string $title,
        public string $icon,
        public string $index_url,
        public string $save_url,
        public string $images_url,
        public PdfTemplatePresenter $template,
        CSRFSynchronizerTokenInterface $token,
        public array $images,
    ) {
        $this->csrf                  = CSRFSynchronizerTokenPresenter::fromToken($token);
        $this->has_images            = count($images) > 0;
        $this->variables             = Variable::getPresenters();
        $this->variables_for_preview = json_encode(Variable::getDefaultForPreview());
    }

    /**
     * @param list<DisplayImagePresenter> $images
     */
    public static function forCreation(
        CSRFSynchronizerTokenInterface $token,
        \PFUser $user,
        array $images,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ): self {
        return self::createFromTemplate(
            PdfTemplatePresenter::forCreation($user, $provide_user_avatar_url),
            $token,
            $images,
        );
    }

    /**
     * @param list<DisplayImagePresenter> $images
     */
    public static function forDuplication(
        PdfTemplate $template,
        CSRFSynchronizerTokenInterface $token,
        \PFUser $user,
        array $images,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ): self {
        return self::createFromTemplate(
            PdfTemplatePresenter::forDuplication($template, $user, $provide_user_avatar_url),
            $token,
            $images,
        );
    }

    /**
     * @param list<DisplayImagePresenter> $images
     */
    private static function createFromTemplate(
        PdfTemplatePresenter $template,
        CSRFSynchronizerTokenInterface $token,
        array $images,
    ): self {
        return new self(
            Navigation::inTemplates(),
            dgettext('tuleap-pdftemplate', 'Template creation'),
            'fa-solid fa-plus',
            IndexPdfTemplateController::ROUTE,
            DisplayPdfTemplateCreationFormController::ROUTE,
            IndexImagesController::ROUTE,
            $template,
            $token,
            $images,
        );
    }

    /**
     * @param list<DisplayImagePresenter> $images
     */
    public static function forUpdate(
        PdfTemplate $template,
        CSRFSynchronizerTokenInterface $token,
        \PFUser $user,
        array $images,
        ProvideUserAvatarUrl $provide_user_avatar_url,
    ): self {
        $presenter = PdfTemplatePresenter::fromPdfTemplate($template, $user, $provide_user_avatar_url);

        return new self(
            Navigation::inTemplates(),
            dgettext('tuleap-pdftemplate', 'Update template'),
            'fa-solid fa-pencil',
            IndexPdfTemplateController::ROUTE,
            $presenter->update_url,
            IndexImagesController::ROUTE,
            $presenter,
            $token,
            $images,
        );
    }
}
