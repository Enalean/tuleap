<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__.'/../../lib/FilterTestCase.php';
require_once __DIR__.'/../../lib/FilterTestDirectory.php';

class FindCompatibleTests
{
    const COMPATIBLE_TESTS_FILE = __DIR__.'/../../php7compatibletests.list';

    private $excluded_tests = [
        'tests/simpletest/common/include/CookieManagerTest.php', // Need to fix php7 warnings before adding it otherwise headers() will trig errors
    ];

    public function main(array $argv)
    {
        $compatible_files = [];
        foreach ($this->collectFiles($argv[1]) as $file) {
            $output       = [];
            $return_value = null;
            exec('/opt/rh/rh-php70/root/usr/bin/php /tuleap/tests/bin/php7-run.php blind-exec '.$file->getPathname().' 2>&1 >/dev/null', $output, $return_value);
            if ($return_value == '0') {
                $compatible_files[] = realpath($file->getPathname());
            }
        }

        file_put_contents(self::COMPATIBLE_TESTS_FILE, implode(PHP_EOL, $compatible_files));
    }

    private function collectFiles($path)
    {
        $rii = new FilterTestCase(
            new RecursiveIteratorIterator(
                new FilterTestDirectory(
                    new RecursiveDirectoryIterator($path)
                ),
                RecursiveIteratorIterator::SELF_FIRST
            )
        );
        foreach ($rii as $file) {
            if (! in_array($file->getPathname(), $this->excluded_tests)) {
                yield $file;
            }
        }
    }
}
