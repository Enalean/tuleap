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

use Tuleap\PdfTemplate\Admin\Image\IndexImagesController;

final readonly class Navigation
{
    private const PANE_IMAGES    = 'images';
    private const PANE_TEMPLATES = 'templates';

    private function __construct(public array $panes)
    {
    }

    public static function inTemplates(): self
    {
        return self::getPanes(self::PANE_TEMPLATES);
    }

    public static function inImages(): self
    {
        return self::getPanes(self::PANE_IMAGES);
    }

    /**
     * @param self::PANE_* $active
     */
    private static function getPanes(string $active): self
    {
        return new self(
            [
                [
                    'label'     => dgettext('tuleap_pdftemplate', 'Templates list'),
                    'href'      => IndexPdfTemplateController::ROUTE,
                    'is_active' => self::PANE_TEMPLATES === $active,
                ],
                [
                    'label'     => dgettext('tuleap_pdftemplate', 'Images library'),
                    'href'      => IndexImagesController::ROUTE,
                    'is_active' => self::PANE_IMAGES === $active,
                ],
            ],
        );
    }
}
