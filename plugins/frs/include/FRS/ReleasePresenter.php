<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\FRS;

use ForgeConfig;

class ReleasePresenter
{

    /**
     * @var Tuleap\FRS\REST\v1\ReleaseRepresentation
     */
    public $release_representation;

    /** @var string */
    public $language;

    /** @var array */
    public $platform_license_info;

    public function __construct($release_representation, $language)
    {
        $this->release_representation = json_encode($release_representation);
        $this->language               = $language;

        $platform_license_info = array(
            "exchange_policy_url" => ForgeConfig::get('sys_exchange_policy_url'),
            "organisation_name"   => ForgeConfig::get('sys_org_name'),
            "contact_email"       => ForgeConfig::get('sys_email_contact')
        );

        $this->platform_license_info  = json_encode($platform_license_info);
    }

    public function getTemplateName()
    {
        return 'release';
    }
}
