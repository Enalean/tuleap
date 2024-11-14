<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;

/**
 * @psalm-immutable
 */
final readonly class RichTextareaPresenter
{
    public int $rows;
    public int $cols;
    /**
     * @var list<array{name: string, value: string}> $data_attributes
     */
    public array $data_attributes;
    public bool $is_dragndrop_allowed;
    public string $help_id;
    public int $maxlength;

    /**
     * @param list<array{name: string, value: string}> $data_attributes
     */
    public function __construct(
        public string $id,
        public string $name,
        int $number_of_rows,
        int $number_of_cols,
        public string $value,
        public bool $is_required,
        bool $is_image_upload_allowed,
        array $data_attributes,
    ) {
        $this->help_id              = $id . '-help';
        $this->rows                 = $number_of_rows;
        $this->cols                 = $number_of_cols;
        $this->is_dragndrop_allowed = $is_image_upload_allowed;

        $final_data_attributes = $data_attributes;
        if ($is_image_upload_allowed) {
            $final_data_attributes[] = [
                'name'  => 'help-id',
                'value' => $this->help_id,
            ];
        }
        $this->data_attributes = $final_data_attributes;
        $this->maxlength       = TextValueValidator::MAX_TEXT_SIZE;
    }
}
