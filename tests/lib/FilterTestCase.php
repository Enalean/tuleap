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

class FilterTestCase extends FilterIterator
{
    public function accept()
    {
        $file = $this->getInnerIterator()->current();
        if ($this->fileCanBeSelectedIntoTestSuite($file)) {
            return true;
        }
        return false;
    }

    private function fileCanBeSelectedIntoTestSuite($file)
    {
        return (strpos($file->getPathname(), '/_') === false &&
            $this->isNotATestsRestDirectory($file->getPathname()) &&
            $this->isNotATestsSoapDirectory($file->getPathname()) &&
            $this->isNotAVendorDirectory($file->getPathname()) &&
            (preg_match('/Test.php$/', $file->getFilename()))
        );
    }

    private function isNotATestsRestDirectory($pathName)
    {
        return !(preg_match("/^.*\/tests\/rest(\/.+|$)$/", $pathName));
    }

    private function isNotATestsSoapDirectory($pathName)
    {
        return !(preg_match("/^.*\/tests\/soap(\/.+|$)$/", $pathName));
    }

    private function isNotAVendorDirectory($pathName)
    {
        return !(preg_match("/^.*\/vendor(\/.+|$)$/", $pathName));
    }
}
