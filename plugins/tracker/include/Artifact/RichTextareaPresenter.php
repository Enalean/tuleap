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
    public string $id;
    public string $name;
    public int $rows;
    public int $cols;
    public string $value;
    public bool $is_required;
    /**
     * @var list<array{name: string, value: string}> $data_attributes
     */
    public array $data_attributes;
    public bool $is_dragndrop_allowed;
    public string $help_id;
    public int $maxlength;
    public bool $allows_mentions;

    /**
     * @param list<array{name: string, value: string}> $data_attributes
     */
    public function __construct(
        RichTextareaConfiguration $configuration,
        bool $is_image_upload_allowed,
        array $data_attributes,
    ) {
        $this->id                   = $configuration->id;
        $this->name                 = $configuration->name;
        $this->rows                 = $configuration->number_of_rows;
        $this->cols                 = $configuration->number_of_columns;
        $this->value                = $configuration->content;
        $this->is_required          = $configuration->is_required;
        $this->allows_mentions      = $configuration->allows_mentions;
        $this->help_id              = $configuration->id . '-help';
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
