<?php
/**
  * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class ForgeAccess_AdminPresenter
{
    /** @var int */
    public $nb_restricted_users;

    /** @var string */
    public $current_access_mode;

    /** @var string */
    public $localinc_path;

    /** @var bool */
    public $access_restricted;

    /** @var bool */
    public $access_regular;

    /** @var bool */
    public $access_anonymous;

    /** @var string */
    public $title;

    /** @var string */
    public $btn_submit;

    /** @var string html */
    public $csrf_token;

    /** @var string */
    public $ugroup_authenticated_users = '';

    /** @var string */
    public $ugroup_registered_users = '';

    /** @var string */
    public $ugroup_authenticated_users_placeholder;

    /** @var string */
    public $ugroup_registered_users_placeholder;

    /** @var string */
    public $ugroup_authenticated_users_label;

    /** @var string */
    public $ugroup_registered_users_label;

    /** @var string */
    public $customize_ugroups_label_info;

    /** @var string */
    public $who_can_access;

    /** @var string */
    public $platform_access_control_label;

    /** @var string */
    public $anonymous_can_see_site_homepage;

    /** @var string */
    public $anonymous_can_see_contact;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $title,
        $localinc_path,
        $current_access_mode,
        $nb_restricted_users,
        $ugroup_authenticated_users,
        $ugroup_registered_users,
        $anonymous_can_see_site_homepage,
        $anonymous_can_see_contact,
    ) {
        $this->title                           = $title;
        $this->csrf_token                      = $csrf;
        $this->localinc_path                   = $localinc_path;
        $this->current_access_mode             = $current_access_mode;
        $this->nb_restricted_users             = $nb_restricted_users;
        $this->anonymous_can_see_site_homepage = $anonymous_can_see_site_homepage;
        $this->anonymous_can_see_contact       = $anonymous_can_see_contact;

        $this->is_localinc_obsolete = $this->isLocalIncObsolete();

        $this->access_anonymous  = ($current_access_mode === ForgeAccess::ANONYMOUS);
        $this->access_regular    = ($current_access_mode === ForgeAccess::REGULAR);
        $this->access_restricted = ($current_access_mode === ForgeAccess::RESTRICTED);

        $this->btn_submit                = _('Update information');
        $this->localinc_obsolete_message = sprintf(_('<h4><i class="fa fa-exclamation-triangle"></i> Your local.inc file is outdated!</h4><p>It appears that your local.inc file contains definitions of variables that are unused and it may lead to confusion.</p><p>Please edit <code>%1$s</code> and remove the following variables: <code>$sys_allow_anon</code> and <code>$sys_allow_restricted_users</code>.</p>'), $this->localinc_path);

        $this->access_anonymous_label           = _('Anonymous users can access the platform');
        $this->access_anonymous_desc            = _('Anonymous users can browse the public part of the site without logging in.');
        $this->access_regular_label             = _('All users must be logged in');
        $this->access_regular_desc              = _('Users must first login before going further.');
        $this->access_restricted_label          = _('All users must be logged in and restricted users are enabled');
        $this->access_restricted_desc           = _('Users must first login before going further. Some users may be restricted (restricted users have to be specifically granted access to projects and services).');
        $this->current_restricted_users_message = sprintf(_('<h4>Heads up!</h4><p>Currently there are <a href="/admin/userlist.php?status_values[]=R">%1$s user(s)</a> that have restricted status. Changing current access mode will not change their status, they will need to be dealt with manually.</p>'), $this->nb_restricted_users);

        if ($ugroup_authenticated_users != false) {
            $this->ugroup_authenticated_users = $ugroup_authenticated_users;
        }
        if ($ugroup_registered_users != false) {
            $this->ugroup_registered_users = $ugroup_registered_users;
        }
        $this->ugroup_authenticated_users_placeholder = sprintf(_('E.g %1$s employees & subco'), ForgeConfig::get('sys_org_name'));
        $this->ugroup_registered_users_placeholder    = sprintf(_('E.g %1$s employees'), ForgeConfig::get('sys_org_name'));

        $this->ugroup_authenticated_users_label = _('Authenticated users (Registered + Restricted) label');
        $this->ugroup_registered_users_label    = _('Registered users label');

        $this->customize_ugroups_label_info = _('You can override default names for the two special groups. This only applies for git permissions at the moment.');

        $this->platform_access_control_label = _('Platform access control');
    }

    private function isLocalIncObsolete()
    {
        include($this->localinc_path);
        $variables_in_local_inc = get_defined_vars();

        return isset($variables_in_local_inc['sys_allow_anon'])
            || isset($variables_in_local_inc['sys_allow_restricted_users']);
    }
}
