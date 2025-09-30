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

final readonly class VariableMisusageCollector
{
    /**
     * @return list<string>
     */
    public function getMisusages(string $text): array
    {
        preg_match_all(
            '/\$\{(?P<spacebefore>\s*)(?P<possiblevariable>[A-Z_]+)(?P<spaceafter>\s*)\}/i',
            $text,
            $matches
        );

        $misusages = [];
        foreach ($matches['possiblevariable'] as $key => $possiblevariable) {
            $variable = Variable::tryFrom(strtoupper($possiblevariable));
            if ($variable === null) {
                $misusages[] = sprintf(
                    dgettext('tuleap-pdftemplate', 'Unknown variable %s, the variable will not be interpreted.'),
                    $matches[0][$key],
                );

                continue;
            }

            if (strlen($matches['spacebefore'][$key]) > 0 || strlen($matches['spaceafter'][$key]) > 0) {
                $misusages[] = sprintf(
                    dgettext('tuleap-pdftemplate', 'Syntax error with variable %s: spaces are not allowed, the variable will not be interpreted. Expected: %s.'),
                    $matches[0][$key],
                    VariablePresenter::fromVariable($variable)->name,
                );

                continue;
            }

            if (strtoupper($possiblevariable) !== $possiblevariable) {
                $misusages[] = sprintf(
                    dgettext('tuleap-pdftemplate', 'Syntax error with variable %s, the variable must be in uppercase. Expected: %s.'),
                    $matches[0][$key],
                    VariablePresenter::fromVariable($variable)->name,
                );
            }
        }

        return $misusages;
    }
}
