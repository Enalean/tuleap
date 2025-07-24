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

namespace Tuleap\PdfTemplate\Stubs;

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\PdfTemplate\CreateTemplate;
use Tuleap\PdfTemplate\PdfTemplateBuilder;

final class CreateTemplateStub implements CreateTemplate
{
    private bool $called = false;

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function isCalled(): bool
    {
        return $this->called;
    }

    #[\Override]
    public function create(
        string $label,
        string $description,
        string $style,
        string $title_page_content,
        string $header_content,
        string $footer_content,
        \PFUser $created_by,
        \DateTimeImmutable $created_date,
    ): PdfTemplate {
        $this->called = true;

        return PdfTemplateBuilder::build(
            (new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier(),
            $label,
            $description,
            $style,
            $title_page_content,
            $header_content,
            $footer_content,
            $created_by,
            $created_date,
        );
    }
}
