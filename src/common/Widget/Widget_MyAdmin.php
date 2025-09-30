<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
* Widget_MyAdmin
*
* Personal Admin
*/
class Widget_MyAdmin extends Widget //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    private $user_is_super_admin;

    public function __construct($user_is_super_admin)
    {
        parent::__construct('myadmin');
        $this->user_is_super_admin = $user_is_super_admin;
    }

    #[\Override]
    public function getTitle()
    {
        if ($this->user_is_super_admin) {
            return $GLOBALS['Language']->getText('my_index', 'my_admin');
        } else {
            return $GLOBALS['Language']->getText('my_index', 'my_admin_non_super');
        }
    }

    #[\Override]
    public function getDescription()
    {
        return $GLOBALS['Language']->getText('my_index', 'my_admin_description');
    }

    #[\Override]
    public function getContent(): string
    {
        $html_my_admin = '<table width="100%" class="tlp-table">';

        if ($this->user_is_super_admin) {
            $html_my_admin .= $this->getHTMLForSuperAdmin();
        } else {
            $row_colour_id  = 0;
            $html_my_admin .= $this->getHTMLForNonSuperAdmin($row_colour_id);
        }

        $html_my_admin .= '</table>';

        return $html_my_admin;
    }

    private function getHTMLForSuperAdmin(): string
    {
        require_once __DIR__ . '/../../www/project/admin/ugroup_utils.php';

        $html_my_admin = '';
        // Get the number of pending users and projects

        if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1) {
            db_query("SELECT count(*) AS count FROM user WHERE status='P'");
            $row           = db_fetch_array();
            $pending_users = $row['count'];
        } else {
            db_query("SELECT count(*) AS count FROM user WHERE status='P' OR status='V' OR status='W'");
            $row           = db_fetch_array();
            $pending_users = $row['count'];
        }
        db_query("SELECT count(*) AS count FROM user WHERE status='V' OR status='W'");
        $row             = db_fetch_array();
        $validated_users = $row['count'];

        $i              = 0;
        $html_my_admin .= $this->getAdminRow(
            $i++,
            sprintf(_('Users in <a href="%1$s"><B>P</B> (pending) status</a>'), '/admin/approve_pending_users.php?page=pending'),
            $pending_users,
            $this->getColor($pending_users)
        );

        if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 1) {
            $html_my_admin .= $this->getAdminRow(
                $i++,
                sprintf(_('Validated users <a href="%1$s"><B>pending email activation</B></a>'), '/admin/approve_pending_users.php?page=validated'),
                $validated_users,
                $this->getColor($validated_users)
            );
        }

        $html_my_admin .= $this->getHTMLForNonSuperAdmin($i);

        $pendings = [];
        $em       = EventManager::instance();
        $em->processEvent('widget_myadmin', ['result' => &$pendings]);
        foreach ($pendings as $entry) {
            $html_my_admin .= $this->getAdminRow(
                $i++,
                $entry['text'],
                $entry['value'],
                $entry['bgcolor'],
                isset($entry['textcolor']) ? $entry['textcolor'] : 'white'
            );
        }

        return $html_my_admin;
    }

    private function getHTMLForNonSuperAdmin($i)
    {
        db_query("SELECT count(*) AS count FROM `groups` WHERE status='P'");
        $row              = db_fetch_array();
        $pending_projects = $row['count'];

        return $this->getAdminRow(
            $i++,
            sprintf(_('Projects in <a href="%1$s"><B>P</B> (pending) status</A>'), '/admin/approve-pending.php'),
            $pending_projects,
            $this->getColor($pending_projects)
        );
    }

    private function getColor($nb): string
    {
        return $nb == 0 ? 'green' : 'orange';
    }

    private function getAdminRow($i, $text, $value, $bgcolor, $textcolor = 'white'): string
    {
        return '<tr class="' . util_get_alt_row_color($i++) . '"><td>' . $text . '</td><td nowrap="nowrap" style="width:20%; background:' . $bgcolor . '; color:' . $textcolor . '; padding: 2px 8px; font-weight:bold; text-align:center;">' . $value . '</td></tr>';
    }
}
