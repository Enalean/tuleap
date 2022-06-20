<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata;

use Docman_MetadataFactory;
use Tuleap\Docman\ResponseFeedbackWrapper;

final class CreationMetadataValidator
{
    public function __construct(private Docman_MetadataFactory $metadata_factory)
    {
    }

    public function validateNewMetadata(string $name, ResponseFeedbackWrapper $feedback): bool
    {
        $name = trim($name);
        if ($name === '') {
            $feedback->log('error', dgettext('tuleap-docman', 'Property name is required, please fill this field.'));

            return false;
        }

        if ($this->metadata_factory->findByName($name)->count() > 0) {
            $feedback->log('error', sprintf(dgettext('tuleap-docman', 'There is already a property with the name \'%1$s\'.'), $name));
            return false;
        }

        return true;
    }
}
