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

use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\File\Size\HumanReadableFilesize;
use Tuleap\PdfTemplate\Image\PdfTemplateImage;
use Tuleap\PdfTemplate\Image\PdfTemplateImageHrefBuilder;
use Tuleap\User\Admin\UserPresenter;

final readonly class PdfTemplateImagePresenter
{
    private function __construct(
        public string $id,
        public string $filename,
        public string $filesize,
        public string $href,
        public UserPresenter $last_updated_by,
        public TlpRelativeDatePresenter $last_updated_date,
    ) {
    }

    public static function fromImage(
        PdfTemplateImage $image,
        \PFUser $user,
    ): self {
        $href_builder = new PdfTemplateImageHrefBuilder();
        $date_builder = new TlpRelativeDatePresenterBuilder();

        return new self(
            $image->identifier->toString(),
            $image->filename,
            HumanReadableFilesize::convert($image->filesize),
            $href_builder->getImageHref($image),
            UserPresenter::fromUser($image->last_updated_by),
            $date_builder->getTlpRelativeDatePresenterInBlockContext($image->last_updated_date, $user),
        );
    }
}
