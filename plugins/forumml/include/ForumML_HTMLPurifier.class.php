<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

define('CODENDI_PURIFIER_FORUMML', 20);

class ForumML_HTMLPurifier extends Codendi_HTMLPurifier
{

    /**
     * Hold an instance of the class
     */
    private static $ForumML_HTMLPurifier_instance;

    /**
     * Constructor
     */
    protected function __construct()
    {
    }

    /**
     * Singleton access.
     * Override parent method
     * @access: static
     */
    public static function instance()
    {
     //static $purifier;
        if (!isset(self::$ForumML_HTMLPurifier_instance)) {
            $c = self::class;
            self::$ForumML_HTMLPurifier_instance = new $c();
        }
        return self::$ForumML_HTMLPurifier_instance;
    }

    /**
     * No basic HTML markups, no forms, no javascript
     * Allow urls, auto-magic links, <blockquote> and CSS styles
     */
    public function getForumMLConfig()
    {
        $config = $this->getCodendiConfig();
        // allow <blockquote> html tag, used to display ForumML messages replies
        $config->set('HTML', 'AllowedElements', 'blockquote');
        // support CSS
        $config->set('CSS', 'DefinitionRev', 1);
        return $config;
    }

    /**
     * HTML Purifier configuration factory
     */
    public function getHPConfig($level)
    {
        $config = null;
        switch ($level) {
            case CODENDI_PURIFIER_FORUMML:
                $config = $this->getForumMLConfig();
                break;

            default:
                $config = parent::getHPConfig($level);
        }
        return $config;
    }

    /**
    * Perform HTML purification depending of level purification required and create links.
    */
    public function purify($html, $level = 0, $groupId = 0)
    {
        $clean = '';
        switch ($level) {
            case CODENDI_PURIFIER_FORUMML:
                $hp = HTMLPurifier::getInstance();
                $config = $this->getHPConfig($level);
                $clean = parent::purify($hp->purify($html, $config), CODENDI_PURIFIER_BASIC_NOBR, $groupId);
                break;
            default:
                $clean = parent::purify($html, $level, $groupId);
        }
        return $clean;
    }
}
