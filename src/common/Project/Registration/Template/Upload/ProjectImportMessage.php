<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

/**
 * @psalm-immutable
 */
final readonly class ProjectImportMessage implements NotifyProjectImportMessage
{
    private function __construct(private string $subject, private string $html_template_name, private string $text_template_name, private array $presenter)
    {
    }

    public static function build(string $subject, string $html_template_name, string $text_template_name, array $presenter): self
    {
        return new self($subject, $html_template_name, $text_template_name, $presenter);
    }

    #[\Override]
    public function getSubject(): string
    {
        return $this->subject;
    }

    #[\Override]
    public function getHTMLTemplateName(): string
    {
        return $this->html_template_name;
    }

    #[\Override]
    public function getTextTemplateName(): string
    {
        return $this->text_template_name;
    }

    #[\Override]
    public function getPresenter(): array
    {
        return $this->presenter;
    }
}
