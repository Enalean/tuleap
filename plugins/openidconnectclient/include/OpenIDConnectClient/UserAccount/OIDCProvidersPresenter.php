<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\UserAccount;

use DateTime;
use Tuleap\User\Account\AccountTabPresenterCollection;

/**
 * @pslam-immutable
 */
final class OIDCProvidersPresenter
{
    public $unlink_oidc_url = OIDCProvidersController::URL;
    /**
     * @var AccountTabPresenterCollection
     */
    public $tabs;
    /**
     * @var array
     */
    public $user_mappings = [];
    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;
    /**
     * @var bool
     */
    public $no_mappings;
    /**
     * @var bool
     */
    public $unique_authentication_endpoint;

    public function __construct(AccountTabPresenterCollection $tabs, \CSRFSynchronizerToken $csrf_token, array $user_mappings_usage, bool $unique_authentication_endpoint)
    {
        $this->tabs = $tabs;
        $this->csrf_token = $csrf_token;

        foreach ($user_mappings_usage as $user_mapping_usage) {
            $last_usage = DateTime::createFromFormat('U', $user_mapping_usage->getLastUsage());
            $this->user_mappings[] = array(
                'user_mapping_id'                         => $user_mapping_usage->getUserMappingId(),
                'provider_name'                           => $user_mapping_usage->getProviderName(),
                'provider_icon'                           => $user_mapping_usage->getProviderIcon(),
                'last_usage'                              => $last_usage->format(
                    $GLOBALS['Language']->getText('system', 'datefmt')
                )
            );
        }
        $this->no_mappings = count($this->user_mappings) === 0;
        $this->unique_authentication_endpoint = $unique_authentication_endpoint;
    }
}
