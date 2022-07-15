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
use ForgeConfig;
use Tuleap\SVNCore\AccessControl\SVNProjectAccessController;
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
     * @var string
     */
    public $update_url = UpdateTuleapPMParamsController::URL;
    public bool $is_php_based_auth_enabled;

    public function __construct(Parameters $parameters, public CSRFSynchronizerToken $csrf_token)
    {
        $this->maximum_credentials       = $parameters->getMaximumCredentials();
        $this->lifetime                  = $parameters->getLifetime();
        $this->is_php_based_auth_enabled = ForgeConfig::getFeatureFlag(SVNProjectAccessController::FEATURE_FLAG_DISABLE) !== '1';
    }
}
