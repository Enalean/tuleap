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

namespace Tuleap\SvnCore\Admin;

use Tuleap\SvnCore\Cache\Parameters;

class CachePresenter extends Presenter
{
    public $is_cache_pane_active = true;

    /**
     * @var Parameters
     */
    private $parameters;

    public function __construct(Parameters $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function settings_title()
    {
        return $GLOBALS['Language']->getText('svn_cache', 'settings_title');
    }

    /**
     * @return string
     */
    public function usage_information()
    {
        return $GLOBALS['Language']->getText('svn_cache', 'usage_information');
    }

    /**
     * @return string
     */
    public function maximum_credentials_label()
    {
        return $GLOBALS['Language']->getText('svn_cache', 'maximum_credentials_label');
    }

    /**
     * @return string
     */
    public function lifetime_label()
    {
        return $GLOBALS['Language']->getText('svn_cache', 'lifetime_label');
    }

    /**
     * @return string
     */
    public function save_settings()
    {
        return $GLOBALS['Language']->getText('svn_cache', 'save_settings');
    }

    /**
     * @return string
     */
    public function maximum_credentials()
    {
        return $this->parameters->getMaximumCredentials();
    }

    /**
     * @return string
     */
    public function lifetime()
    {
        return $this->parameters->getLifetime();
    }
}
