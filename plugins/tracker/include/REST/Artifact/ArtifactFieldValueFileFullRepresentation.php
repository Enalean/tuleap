<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\Tracker\FormElement\Field\Files\FilesField;

class ArtifactFieldValueFileFullRepresentation extends ArtifactFieldValueRepresentationData
{
    /**
     * @var string Type of the field
     */
    public $type;

    /**
     * @var \Tuleap\Tracker\REST\Artifact\FileInfoRepresentation[]
     */
    public $file_descriptions = [];

    /**
     * @param FileInfoRepresentation[] $values
     */
    public static function fromValues(FilesField $file, array $values): self
    {
        $representation = new self();

        $representation->field_id          = $file->getId();
        $representation->type              = \Tracker_FormElementFactory::FIELD_FILE_TYPE;
        $representation->label             = $file->getLabel();
        $representation->file_descriptions = $values;

        return $representation;
    }

    public static function fromEmptyValues(FilesField $file): self
    {
        return self::fromValues($file, []);
    }
}
