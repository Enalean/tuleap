<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Chart_TTFFactory {
    const RHEL6 = 'rhel6';
    const RHEL5 = 'rhel5';

    private static $dejavu_path = array(
        self::RHEL5 => 'dejavu-lgc',
        self::RHEL6 => 'dejavu',
    );

    public static function setUserFont($jpgraph_object) {
        if (is_dir(TTF_DIR.DIRECTORY_SEPARATOR.self::$dejavu_path[self::RHEL6])) {
            $dejavu_path = self::$dejavu_path[self::RHEL6];
        } else {
            $dejavu_path = self::$dejavu_path[self::RHEL5];
        }

        $jpgraph_object->SetUserFont(
            $dejavu_path.'/DejaVuLGCSans.ttf',
            $dejavu_path.'/DejaVuLGCSans-Bold.ttf',
            $dejavu_path.'/DejaVuLGCSans-Oblique.ttf',
            $dejavu_path.'/DejaVuLGCSans-BoldOblique.ttf'
        );
    }
}

