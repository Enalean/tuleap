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

use ForgeConfig;
use PFUser;
use TemplateRendererFactory;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

class RichTextareaProvider
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;
    /**
     * @var FrozenFieldDetector
     */
    private $frozen_field_detector;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        TemplateRendererFactory $template_renderer_factory,
        FrozenFieldDetector $frozen_field_detector
    ) {
        $this->form_element_factory      = $form_element_factory;
        $this->template_renderer_factory = $template_renderer_factory;
        $this->frozen_field_detector     = $frozen_field_detector;
    }

    public function getTextarea(
        Tracker $tracker,
        ?Tracker_Artifact $artifact,
        PFUser $user,
        string $id,
        string $name,
        int $rows,
        int $cols,
        string $value,
        bool $is_required,
        array $data_attributes
    ): string {
        $renderer = $this->template_renderer_factory->getRenderer(__DIR__ . '/../../../templates/artifact');

        $is_dragndrop_allowed = false;
        $help_id              = $id . '-help';

        $fields = $this->form_element_factory->getUsedFileFields($tracker);
        foreach ($fields as $field) {
            if (! $field->userCanUpdate($user)) {
                continue;
            }

            if ($artifact !== null && $this->frozen_field_detector->isFieldFrozen($artifact, $field)) {
                continue;
            }

            $is_dragndrop_allowed = true;
            $data_attributes[]    = [
                'name'  => 'help-id',
                'value' => $help_id
            ];
            $data_attributes[]    = [
                'name'  => 'upload-url',
                'value' => '/api/v1/tracker_fields/' . (int) $field->getId() . '/files'
            ];
            $data_attributes[]    = [
                'name'  => 'upload-field-name',
                'value' => 'artifact[' . (int) $field->getId() . '][][tus-uploaded-id]'
            ];
            $data_attributes[]    = [
                'name'  => 'upload-max-size',
                'value' => ForgeConfig::get('sys_max_size_upload')
            ];
            break;
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
                'data_attributes'      => $data_attributes,
                'is_dragndrop_allowed' => $is_dragndrop_allowed,
                'help_id'              => $help_id
            ]
        );
    }
}
