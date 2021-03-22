<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\SVN\SiteAdmin;

use CSRFSynchronizerToken;

/**
 * @psalm-immutable
 */
final class MaxFileSizePresenter extends AdminPresenter
{
    /**
     * @var bool
     */
    public $is_max_file_size_pane_active = true;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $update_url = UpdateMaxFileSizeController::URL;
    /**
     * @var string
     */
    public $max_file_size = '';

    public function __construct(CSRFSynchronizerToken $csrf_token, ?int $max_file_size)
    {
        $this->csrf_token = $csrf_token;
        if ($max_file_size !== null) {
            $this->max_file_size = (string) $max_file_size;
        }
    }
}
