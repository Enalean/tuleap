<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

$opcache = opcache_get_status(true);

if (! isset($opcache['scripts'])) {
    fwrite(STDERR, "No scripts cached, preloading did not succeed\n");
    exit(1);
}

echo 'There are ' . count($opcache['scripts']) . " scripts cached\n";
echo 'Memory footprint: ' . round($opcache['preload_statistics']['memory_consumption'] / 1024 / 1024, 2) . "MB\n";
