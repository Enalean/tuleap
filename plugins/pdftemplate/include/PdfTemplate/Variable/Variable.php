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

namespace Tuleap\PdfTemplate\Variable;

enum Variable: string
{
    case DocumentTitle = 'DOCUMENT_TITLE';

    /**
     * @return list<VariablePresenter>
     */
    public static function getPresenters(): array
    {
        $variable_presenters = array_map(
            static fn(self $variable) => VariablePresenter::fromVariable($variable),
            self::cases(),
        );
        usort(
            $variable_presenters,
            static fn (VariablePresenter $a, VariablePresenter $b) => strnatcasecmp($a->name, $b->name),
        );

        return $variable_presenters;
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DocumentTitle => dgettext('tuleap-pdftemplate', 'Title of the generated document'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function getDefaultForPreview(): array
    {
        $variables = self::cases();

        return array_combine(
            array_map(
                static fn (self $variable) => $variable->value,
                $variables,
            ),
            array_map(
                static fn(self $variable) => match ($variable) {
                    self::DocumentTitle => dgettext('tuleap-pdftemplate', 'Document title'),
                },
                $variables,
            ),
        );
    }
}
