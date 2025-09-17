<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

namespace Tuleap\Docman\FilenamePattern;

use Tuleap\Docman\REST\v1\Metadata\HardCodedMetadataException;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;

final class FilenameBuilder
{
    public const  string TITLE_VARIABLE        = '${TITLE}';
    public const  string ITEM_ID_VARIABLE      = '${ID}';
    private const string STATUS_VARIABLE       = '${STATUS}';
    private const string VERSION_NAME_VARIABLE = '${VERSION_NAME}';

    public function __construct(
        private RetrieveFilenamePattern $filename_pattern_retriever,
        private ItemStatusMapper $item_status_mapper,
    ) {
    }

    public function buildFilename(
        string $original_filename,
        int $project_id,
        string $title,
        int $status,
        string $version_name,
        int $item_id,
    ): string {
        $filename_pattern = $this->filename_pattern_retriever->getPattern($project_id);
        if (! $filename_pattern->isEnforcedAndNonEmpty()) {
            return $original_filename;
        }

        $pattern = $filename_pattern->getPattern();

        $file_info = pathinfo($original_filename);

        $new_filename = $pattern;
        if (str_contains($pattern, self::TITLE_VARIABLE)) {
            $new_filename = str_replace(self::TITLE_VARIABLE, $title, $new_filename);
        }

        if (str_contains($pattern, self::STATUS_VARIABLE)) {
            try {
                $converted_status = $this->item_status_mapper->getItemStatusFromItemStatusNumber($status);
            } catch (HardCodedMetadataException $e) {
                $converted_status = ItemStatusMapper::ITEM_STATUS_NONE;
            }
            $new_filename = str_replace(self::STATUS_VARIABLE, $converted_status, $new_filename);
        }

        if (str_contains($pattern, self::VERSION_NAME_VARIABLE)) {
            $new_filename = str_replace(self::VERSION_NAME_VARIABLE, $version_name, $new_filename);
        }

        if (str_contains($pattern, self::ITEM_ID_VARIABLE)) {
            $new_filename = str_replace(self::ITEM_ID_VARIABLE, (string) $item_id, $new_filename);
        }


        if (array_key_exists('extension', $file_info)) {
            $new_filename .=  '.' . $file_info['extension'];
        }

        return $new_filename;
    }
}
