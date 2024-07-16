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

use Tuleap\Date\TlpRelativeDatePresenter;
use Tuleap\Date\TlpRelativeDatePresenterBuilder;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\User\Admin\UserPresenter;

/**
 * @psalm-immutable
 */
final readonly class PdfTemplatePresenter
{
    private const DUMMY_ID_FOR_CREATION = '';
    public string $update_url;
    public string $duplicate_url;
    public bool $is_update;

    private function __construct(
        public string $id,
        public string $label,
        public string $description,
        public string $style,
        public UserPresenter $last_updated_by,
        public TlpRelativeDatePresenter $last_updated_date,
    ) {
        $this->update_url    = DisplayPdfTemplateUpdateFormController::ROUTE . '/' . urlencode($id);
        $this->duplicate_url = DisplayPdfTemplateDuplicateFormController::ROUTE . '/' . urlencode($id);
        $this->is_update     = $id !== self::DUMMY_ID_FOR_CREATION;
    }

    public static function fromPdfTemplate(PdfTemplate $template, \PFUser $user): self
    {
        $builder = new TlpRelativeDatePresenterBuilder();

        return new self(
            $template->identifier->toString(),
            $template->label,
            $template->description,
            $template->style,
            UserPresenter::fromUser($template->last_updated_by),
            $builder->getTlpRelativeDatePresenterInInlineContext($template->last_updated_date, $user),
        );
    }

    public static function forCreation(\PFUser $user): self
    {
        $builder = new TlpRelativeDatePresenterBuilder();

        return new self(
            self::DUMMY_ID_FOR_CREATION,
            '',
            '',
            file_get_contents(__DIR__ . '/../Default/pdf-template-default.css'),
            UserPresenter::fromUser($user),
            $builder->getTlpRelativeDatePresenterInInlineContext(new \DateTimeImmutable(), $user),
        );
    }

    public static function forDuplication(PdfTemplate $source, \PFUser $user): self
    {
        $builder = new TlpRelativeDatePresenterBuilder();

        return new self(
            self::DUMMY_ID_FOR_CREATION,
            '',
            '',
            $source->style,
            UserPresenter::fromUser($user),
            $builder->getTlpRelativeDatePresenterInInlineContext(new \DateTimeImmutable(), $user),
        );
    }
}
