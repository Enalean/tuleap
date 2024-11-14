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

use TemplateRendererFactory;

final readonly class RichTextareaProvider
{
    public function __construct(
        private TemplateRendererFactory $template_renderer_factory,
        private UploadDataAttributesForRichTextEditorBuilder $data_attributes_builder,
    ) {
    }

    public function getTextarea(RichTextareaConfiguration $configuration, bool $is_artifact_copy): string
    {
        $renderer = $this->template_renderer_factory->getRenderer(__DIR__);

        $data_attributes                  = [
            ['name' => 'project-id', 'value' => (string) $configuration->tracker->getGroupId()],
        ];
        $data_attributes_for_image_upload = [];

        if (! $is_artifact_copy) {
            $data_attributes_for_image_upload = $this->data_attributes_builder->getDataAttributes(
                $configuration->tracker,
                $configuration->user,
                $configuration->artifact
            );
            $data_attributes                  = array_values(
                array_merge($data_attributes, $data_attributes_for_image_upload)
            );
        }
        $is_image_upload_allowed = $data_attributes_for_image_upload !== [] && ! $is_artifact_copy;

        return $renderer->renderToString(
            'rich-textarea',
            new RichTextareaPresenter(
                $configuration,
                $is_image_upload_allowed,
                $data_attributes
            )
        );
    }
}
