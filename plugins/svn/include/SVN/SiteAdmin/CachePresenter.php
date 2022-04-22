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
use Tuleap\SVNCore\Cache\Parameters;

/**
 * @psalm-immutable
 */
final class CachePresenter extends AdminPresenter
{
    /**
     * @var bool
     */
    public $is_cache_pane_active = true;
    /**
     * @var int
     */
    public $maximum_credentials;
    /**
     * @var int
     */
    public $lifetime;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var string
     */
    public $update_url = UpdateTuleapPMParamsController::URL;

    public function __construct(Parameters $parameters, CSRFSynchronizerToken $csrf_token)
    {
        $this->maximum_credentials = $parameters->getMaximumCredentials();
        $this->lifetime            = $parameters->getLifetime();
        $this->csrf_token          = $csrf_token;
    }
}
