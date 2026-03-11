<?php
/**
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\FormElement;

use Luracast\Restler\RestException;
use Override;
use PFUser;
use Tuleap\REST\v1\TrackerFieldRepresentations\TrackerFieldPatchRepresentation;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\TrackerFormElement;

final readonly class BasicPropertiesHandler implements PatchHandler
{
    public function __construct(private FieldDao $dao)
    {
    }

    #[Override]
    public function handle(TrackerFormElement $field, TrackerFieldPatchRepresentation $patch, PFUser $current_user): void
    {
        if ($patch->label !== null) {
            $label = trim($patch->label);
            if ($label === '') {
                throw new RestException(400, 'Label cannot be empty.');
            }
            $field->label = $label;
            $this->dao->save($field);
        }
    }
}
