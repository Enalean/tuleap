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

namespace Tuleap\Git\GitPHP;

final class BlobDataReader
{
    /**
     * @return string[]
     */
    public function getDataLinesInUTF8(Blob $blob): array
    {
        return explode("\n", $this->getDataStringInUTF8($blob));
    }

    public function getDataStringInUTF8(Blob $blob): string
    {
        $data = $blob->GetData();

        return $this->convertToUTF8($data);
    }

    public function convertToUTF8(string $data): string
    {
        $encoding = mb_detect_encoding($data, ['UTF-8', 'ISO-8859-1']);
        if ($encoding !== 'UTF-8') {
            $data = mb_convert_encoding($data, 'UTF-8', $encoding);
        }

        return $data;
    }
}
