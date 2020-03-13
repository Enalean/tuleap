<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Docman\REST\v1\CopyItem;

final class DocmanValidateRepresentationForCopy
{
    private const COPY_PROPERTY_NAME = 'copy';

    public function isValidAsACopyRepresentation(CanContainACopyRepresentation $representation) : bool
    {
        return $this->isCopyPropertySet($representation) && $this->areNonCopyPropertySetToDefaultValues($representation);
    }

    public function isValidAsANonCopyRepresentation(CanContainACopyRepresentation $representation) : bool
    {
        return ! $this->isCopyPropertySet($representation) && $this->areRequiredObjectPropertiesNonEmpty($representation);
    }

    private function isCopyPropertySet(object $representation) : bool
    {
        return isset($representation->{self::COPY_PROPERTY_NAME});
    }

    private function areRequiredObjectPropertiesNonEmpty(CanContainACopyRepresentation $representation) : bool
    {
        foreach ($representation::getNonCopyRequiredObjectProperties() as $required_property) {
            if (! isset($representation->{$required_property}) || empty($representation->{$required_property})) {
                return false;
            }
        }

        return true;
    }

    private function areNonCopyPropertySetToDefaultValues(CanContainACopyRepresentation $representation) : bool
    {
        $representation_class_name = get_class($representation);
        /*
         * This can crash for a lot of reason but since looks but doing something safer
         * requires the usage of reflection. In our case we expect to only handle representations
         * instantiated by Restler so if something must go wrong, Restler will crash first.
         */
        $default_representation          = new $representation_class_name();
        $default_representation_as_array = (array) $default_representation;
        unset($default_representation_as_array[self::COPY_PROPERTY_NAME]);
        $representation_as_array = (array) $representation;
        unset($representation_as_array[self::COPY_PROPERTY_NAME]);

        return $representation_as_array === $default_representation_as_array;
    }
}
