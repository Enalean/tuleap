<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

use PFUser;
use TemplateRendererFactory;
use Tracker;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;

final class RichTextareaProvider
{
    public function __construct(
        private readonly TemplateRendererFactory $template_renderer_factory,
        private readonly UploadDataAttributesForRichTextEditorBuilder $upload_data_attributes_for_rich_text_editor_builder,
    ) {
    }

    public function getTextarea(
        Tracker $tracker,
        ?Artifact $artifact,
        PFUser $user,
        string $id,
        string $name,
        int $rows,
        int $cols,
        string $value,
        bool $is_required,
        bool $is_artifact_copy,
    ): string {
        $renderer = $this->template_renderer_factory->getRenderer(__DIR__ . '/../../../templates/artifact');

        $help_id = $id . '-help';

        $data_attributes               = [
            ['name' => 'project-id', 'value' => $tracker->getGroupId()],
        ];
        $data_attributes_for_dragndrop = [];

        if (! $is_artifact_copy) {
            $data_attributes_for_dragndrop = $this->upload_data_attributes_for_rich_text_editor_builder
                ->getDataAttributes($tracker, $user, $artifact);
        }

        $is_dragndrop_allowed =  ! empty($data_attributes_for_dragndrop) && ! $is_artifact_copy;
        if ($is_dragndrop_allowed) {
            $data_attributes[] = [
                'name'  => 'help-id',
                'value' => $help_id,
            ];
        }

        return $renderer->renderToString(
            'rich-textarea',
            [
                'id'                   => $id,
                'name'                 => $name,
                'rows'                 => $rows,
                'cols'                 => $cols,
                'value'                => $value,
                'is_required'          => $is_required,
                'data_attributes'      => array_merge($data_attributes_for_dragndrop, $data_attributes),
                'is_dragndrop_allowed' => $is_dragndrop_allowed,
                'help_id'              => $help_id,
                'maxlength'            => TextValueValidator::MAX_TEXT_SIZE,
            ]
        );
    }
}
