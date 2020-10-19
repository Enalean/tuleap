<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

class FileUploadDataProvider
{
    /**
     * @var FrozenFieldDetector
     */
    private $frozen_field_detector;
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    public function __construct(FrozenFieldDetector $frozen_field_detector, Tracker_FormElementFactory $formelement_factory)
    {
        $this->frozen_field_detector = $frozen_field_detector;
        $this->formelement_factory   = $formelement_factory;
    }

    /**
     * @return FileUploadData | null
     */
    public function getFileUploadData(Tracker $tracker, ?Artifact $artifact, PFUser $user)
    {
        $file_fields = $this->formelement_factory->getUsedFileFields($tracker);
        foreach ($file_fields as $field) {
            if (! $field->userCanUpdate($user)) {
                continue;
            }

            if ($artifact !== null && $this->frozen_field_detector->isFieldFrozen($artifact, $field)) {
                continue;
            }

            return new FileUploadData($field);
        }
        return null;
    }
}
