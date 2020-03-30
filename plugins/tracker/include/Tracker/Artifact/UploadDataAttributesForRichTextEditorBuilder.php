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
use Tracker;
use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

class UploadDataAttributesForRichTextEditorBuilder
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var FrozenFieldDetector
     */
    private $frozen_field_detector;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        FrozenFieldDetector $frozen_field_detector
    ) {
        $this->form_element_factory      = $form_element_factory;
        $this->frozen_field_detector     = $frozen_field_detector;
    }

    public function getDataAttributes(Tracker $tracker, PFUser $user, ?Tracker_Artifact $artifact): array
    {
        $data_attributes = [];

        $fields = $this->form_element_factory->getUsedFileFields($tracker);
        foreach ($fields as $field) {
            if (! $field->userCanUpdate($user)) {
                continue;
            }

            if ($artifact !== null && $this->frozen_field_detector->isFieldFrozen($artifact, $field)) {
                continue;
            }

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

        return $data_attributes;
    }
}
