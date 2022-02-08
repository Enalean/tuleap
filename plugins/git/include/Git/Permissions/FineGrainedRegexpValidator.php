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

use System_Command_CommandException;

class FineGrainedRegexpValidator
{
    public function isPatternValid($pattern)
    {
        return ! $this->isEmpty($pattern) && ! $this->hasALineBreak($pattern) && $this->hasOnlyAuthorizedCharacters($pattern);
    }

    /**
     * @return bool
     */
    private function hasOnlyAuthorizedCharacters($pattern)
    {
        $system_command = new \System_Command();
        try {
            $system_command->exec('/usr/share/tuleap/plugins/git/bin/gitolite-test-ref-pattern.pl ' . escapeshellarg($pattern));
        } catch (System_Command_CommandException $ex) {
            return false;
        }
        return true;
    }

    private function hasALineBreak($pattern)
    {
        return preg_match('/[\s\v]/', $pattern);
    }

    /**
     * @return bool
     */
    private function isEmpty($pattern)
    {
        return trim($pattern) === '';
    }
}
