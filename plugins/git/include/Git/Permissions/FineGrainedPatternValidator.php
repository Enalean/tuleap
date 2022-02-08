<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\Permissions;

class FineGrainedPatternValidator
{
    public function isPatternValid($pattern)
    {
        return $this->hasOnlyAuthorizedCharacters($pattern) && ! $this->hasALineBreak($pattern);
    }

    private function hasOnlyAuthorizedCharacters($pattern)
    {
        $regex = '#^(?:\*|[a-zA-Z0-9_\-/\.]+(/\*)?)$#';

        return preg_match($regex, $pattern) === 1;
    }

    private function hasALineBreak($pattern)
    {
        return preg_match('/[\s\v]/', $pattern);
    }
}
