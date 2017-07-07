<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Language;

use PFUser;
use ForgeConfig;
use BaseLanguage;

class CustomizableContentLoader
{
    public function getContent(PFUser $user, $file)
    {
        $possible_locations = array(
            $this->getLocalPath($user->getLanguageID(), $file),
            $this->getDefaultPath($user->getLanguageID(), $file),
            $this->getLocalPath(BaseLanguage::DEFAULT_LANG, $file),
            $this->getDefaultPath(BaseLanguage::DEFAULT_LANG, $file),
        );

        foreach ($possible_locations as $location) {
            if ($location === realpath($location)) {
                return file_get_contents($location);
            }
        }

        throw new CustomContentNotFoundException();
    }

    private function getLocalPath($lang, $file)
    {
        return $this->getPathInSiteContent('sys_custom_incdir', $lang, $file);
    }

    private function getDefaultPath($lang, $file)
    {
        return $this->getPathInSiteContent('sys_incdir', $lang, $file);
    }

    private function getPathInSiteContent($base, $lang, $file)
    {
        return ForgeConfig::get($base) . '/' . $lang . '/' . $file . '.mustache';
    }
}
