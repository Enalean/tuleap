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
use Tuleap\Request\CSRFSynchronizerTokenInterface;

/**
 * @psalm-immutable
 */
final readonly class DisplayPdfTemplateCreationOrUpdateFormPresenter
{
    public CSRFSynchronizerTokenPresenter $csrf;

    private function __construct(
        public string $title,
        public string $icon,
        public string $index_url,
        public string $save_url,
        public PdfTemplatePresenter $template,
        CSRFSynchronizerTokenInterface $token,
    ) {
        $this->csrf = CSRFSynchronizerTokenPresenter::fromToken($token);
    }

    public static function forCreation(CSRFSynchronizerTokenInterface $token): self
    {
        return new self(
            dgettext('tuleap-pdftemplate', 'Template creation'),
            'fa-solid fa-plus',
            IndexPdfTemplateController::ROUTE,
            DisplayPdfTemplateCreationFormController::ROUTE,
            PdfTemplatePresenter::forCreation(),
            $token,
        );
    }

    public static function forUpdate(
        PdfTemplate $template,
        CSRFSynchronizerTokenInterface $token,
    ): self {
        return new self(
            dgettext('tuleap-pdftemplate', 'Update template'),
            'fa-solid fa-pencil',
            IndexPdfTemplateController::ROUTE,
            DisplayPdfTemplateUpdateFormController::ROUTE,
            PdfTemplatePresenter::fromPdfTemplate($template),
            $token,
        );
    }
}
