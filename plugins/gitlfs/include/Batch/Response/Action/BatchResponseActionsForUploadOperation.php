<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Batch\Response\Action;

final class BatchResponseActionsForUploadOperation implements BatchResponseActions
{
    /**
     * @var BatchResponseActionContent
     */
    private $upload_action;
    /**
     * @var BatchResponseActionContent
     */
    private $verify_operation;

    public function __construct(BatchResponseActionContent $upload_action, BatchResponseActionContent $verify_operation)
    {
        $this->upload_action    = $upload_action;
        $this->verify_operation = $verify_operation;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        return [
            'upload' => $this->upload_action,
            'verify' => $this->verify_operation,
        ];
    }
}
