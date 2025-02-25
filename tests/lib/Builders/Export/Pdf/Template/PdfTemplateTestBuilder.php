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

namespace Tuleap\Test\Builders\Export\Pdf\Template;

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifier;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\Export\Pdf\Template\PdfTemplate;
use Tuleap\Test\Builders\UserTestBuilder;

final class PdfTemplateTestBuilder
{
    private string $label;
    private string $description;
    private string $style;
    private string $title_page_content;
    private string $header_content;
    private string $footer_content;
    private \PFUser $last_updated_by;
    private \DateTimeImmutable $last_updated_date;
    private PdfTemplateIdentifier $identifier;

    public function __construct()
    {
        $this->identifier         = (new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()))->buildIdentifier();
        $this->label              = 'Black template';
        $this->description        = '';
        $this->style              = 'body { color: black; }';
        $this->title_page_content = '<h1>Document title</h1>';
        $this->header_content     = '<em>For authorized eyes only</em>';
        $this->footer_content     = '<em>Do not share this document</em>';
        $this->style              = 'body { color: black; }';
        $this->last_updated_by    = UserTestBuilder::buildWithDefaults();
        $this->last_updated_date  = new \DateTimeImmutable();
    }

    public static function aTemplate(): self
    {
        return new self();
    }

    public function withLabel(string $label): self
    {
        $self        = clone $this;
        $self->label = $label;

        return $self;
    }

    public function withDescription(string $description): self
    {
        $self              = clone $this;
        $self->description = $description;

        return $self;
    }

    public function withStyle(string $style): self
    {
        $self        = clone $this;
        $self->style = $style;

        return $self;
    }

    public function withLastUpdatedBy(\PFUser $last_updated_by): self
    {
        $self                  = clone $this;
        $self->last_updated_by = $last_updated_by;

        return $self;
    }

    public function withLastUpdatedDate(\DateTimeImmutable $last_updated_date): self
    {
        $self                    = clone $this;
        $self->last_updated_date = $last_updated_date;

        return $self;
    }

    public function withIdentifier(PdfTemplateIdentifier $identifier): self
    {
        $self             = clone $this;
        $self->identifier = $identifier;

        return $self;
    }

    public function withTitlePageContent(string $title_page_content): self
    {
        $self                     = clone $this;
        $self->title_page_content = $title_page_content;

        return $self;
    }

    public function withHeaderContent(string $header_content): self
    {
        $self                 = clone $this;
        $self->header_content = $header_content;

        return $self;
    }

    public function withFooterContent(string $footer_content): self
    {
        $self                 = clone $this;
        $self->footer_content = $footer_content;

        return $self;
    }

    public function build(): PdfTemplate
    {
        return new PdfTemplate(
            $this->identifier,
            $this->label,
            $this->description,
            '',
            $this->style,
            $this->title_page_content,
            $this->header_content,
            $this->footer_content,
            $this->last_updated_by,
            $this->last_updated_date,
        );
    }
}
