<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\SVN\AccessControl;

use Tuleap\NeverThrow\Fault;
use Tuleap\SVNCore\CollectionOfSVNAccessFileFaults;
use Tuleap\SVNCore\SvnAccessFileContent;

final class DuplicateSectionDetector
{
    private const GROUPS = 'groups';

    public function inspect(SvnAccessFileContent $svn_access_file): CollectionOfSVNAccessFileFaults
    {
        $faults  = new CollectionOfSVNAccessFileFaults();
        $matches = [];
        if (preg_match_all('/^\s*\[(.*)\]\s*(?:#.*)?$/m', $svn_access_file->getFullContent(), $matches)) {
            $acc = [];
            foreach ($matches[1] as $match) {
                if (! isset($acc[$match])) {
                    $acc[$match] = 0;
                } else {
                    if ($match === self::GROUPS) {
                        $faults->add(Fault::fromMessage(dgettext('tuleap-svn', '[groups] is already defined, this will not be supported in the future (Subversion 1.10 & upper).')));
                    } else {
                        $faults->add(Fault::fromMessage(sprintf(dgettext('tuleap-svn', 'Path `%s` is already defined, this will not be supported in the future (Subversion 1.10 & upper).'), $match)));
                    }
                    $acc[$match]++;
                }
            }
        }
        return $faults;
    }
}
