<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Admin\ArtifactDeletion;

use CSRFSynchronizerToken;
use Tuleap\Tracker\Config\ArtifactsDeletionPresenter;
use Tuleap\Tracker\Config\SectionsPresenter;

class ArtifactsDeletionConfigPresenter
{
    /**
     * @var SectionsPresenter
     */
    public $sections;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var int
     */
    public $artifacts_limit;

    /**
     * @var bool
     */
    public $is_archiving_enabled;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $artifacts_limit,
        $is_archiving_enabled,
    ) {
        $this->sections             = new ArtifactsDeletionPresenter();
        $this->csrf_token           = $csrf;
        $this->artifacts_limit      = $artifacts_limit;
        $this->is_archiving_enabled = $is_archiving_enabled;
    }
}
