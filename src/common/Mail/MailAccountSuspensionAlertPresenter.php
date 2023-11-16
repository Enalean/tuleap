<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Mail;

use BaseLanguage;
use DateTimeImmutable;
use MailOutlinePresenter;
use ForgeConfig;
use Tuleap\Date\DateHelper;

class MailAccountSuspensionAlertPresenter extends MailOutlinePresenter
{
    public $last_access_date;
    public $suspension_date;
    public $organization_name;
    public $tuleap_server_name;

    /**
     * Constructor
     *
     * @param string $logo_url URL of the organization logo
     * @param string $color_logo Logo/email tone color in hex notation
     */
    public function __construct(string $logo_url, string $color_logo, DateTimeImmutable $last_access_date, DateTimeImmutable $suspension_date, BaseLanguage $language)
    {
        parent::__construct($logo_url, '', '', '', $color_logo);
        $this->last_access_date   =  DateHelper::formatForLanguage($language, $last_access_date->getTimeStamp(), true);
        $this->suspension_date    = DateHelper::formatForLanguage($language, $suspension_date->getTimeStamp(), true);
        $this->organization_name  = ForgeConfig::get('sys_org_name');
        $this->tuleap_server_name = ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME);
    }
}
