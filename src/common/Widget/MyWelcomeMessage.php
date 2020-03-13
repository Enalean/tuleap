<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Widget;

use Tuleap\Templating\Mustache\SiteContentRenderer;
use Widget;
use PFUser;
use ForgeConfig;

class MyWelcomeMessage extends Widget
{
    public const NAME = 'mywelcomemessage';

    /**
     * @var PFUser
     */
    private $user;

    public function __construct(PFUser $user)
    {
        parent::__construct(self::NAME);
        $this->user = $user;
    }

    public function getTitle()
    {
        return _('Welcome aboard');
    }

    public function getDescription()
    {
        return _('Welcome message and information for users');
    }

    public function getContent()
    {
        $renderer = new SiteContentRenderer();
        return $renderer->renderMarkdown(
            $this->user,
            $this->getFileNameForUser($this->user),
            array(
                'site_name'               => ForgeConfig::get('sys_name'),
                'sys_long_org_name'       => ForgeConfig::get('sys_long_org_name'),
                'site_name_is_not_tuleap' => ! $this->isSiteNameTuleap(),
            )
        );
    }

    private function isSiteNameTuleap()
    {
        return strtolower(ForgeConfig::get('sys_name')) == 'tuleap';
    }

    private function getFileNameForUser(PFUser $user)
    {
        $file_name = 'widget/my_welcome_message';
        if ($user->isSuperUser()) {
            return $file_name . '_admin';
        }
        return $file_name;
    }

    public function isUnique()
    {
        return true;
    }
}
