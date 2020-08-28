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
 */

namespace Tuleap\LDAP\Project\UGroup\Binding;

use CSRFSynchronizerToken;
use ForgeConfig;
use HTTPRequest;
use LDAP_GroupManager;
use LDAP_UserGroupManager;
use LDAPResult;
use ProjectUGroup;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Project\Admin\ProjectUGroup\BindingAdditionalModalPresenter;
use Tuleap\User\UserGroup\NameTranslator;

class AdditionalModalPresenterBuilder
{
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var LDAP_UserGroupManager
     */
    private $user_group_manager;
    /**
     * @var HTTPRequest
     */
    private $request;
    /**
     * @var string
     */
    private $ldap_server_common_name;

    public function __construct(LDAP_UserGroupManager $user_group_manager, HTTPRequest $request, string $ldap_server_common_name)
    {
        $this->renderer = TemplateRendererFactory::build()->getRenderer(
            LDAP_TEMPLATE_DIR . '/project/ugroup/binding'
        );

        $this->user_group_manager = $user_group_manager;
        $this->request            = $request;
        $this->ldap_server_common_name = $ldap_server_common_name;
    }

    public function build(ProjectUGroup $ugroup, $bind_option, $synchro, CSRFSynchronizerToken $csrf)
    {
        $ldap_group = $this->user_group_manager->getLdapGroupByGroupId($ugroup->getId());
        $title      = $this->getTitle($ldap_group);

        return new BindingAdditionalModalPresenter(
            $this->getButton($title),
            $this->getContent($title, $ugroup, $bind_option, $synchro, $csrf, $ldap_group)
        );
    }

    private function getButton($title)
    {
        return $this->renderer->renderToString(
            'modal-button',
            ['title' => $title]
        );
    }

    private function getContent(
        $title,
        ProjectUGroup $ugroup,
        $bind_option,
        $synchro,
        CSRFSynchronizerToken $csrf,
        ?LDAPResult $ldap_group = null
    ) {
        return $this->renderer->renderToString(
            'modal-content',
            [
                'title'                   => $title,
                'is_linked'               => (bool) $ldap_group,
                'ugroup_id'               => $ugroup->getId(),
                'ugroup_name'             => NameTranslator::getUserGroupDisplayName($ugroup->getName()),
                'ldap_group_name'         => $ldap_group ? $ldap_group->getGroupCommonName() : '',
                'ldap_group_display_name' => $ldap_group ? $ldap_group->getGroupDisplayName() : '',
                'sys_name'                => ForgeConfig::get('sys_name'),
                'is_preserved'            => $this->isPreserved($ugroup, $bind_option),
                'is_synchronized'         => $this->isSynchronized($ugroup, $synchro),
                'locale'                  => $this->request->getCurrentUser()->getLocale(),
                'csrf_token'              => $csrf,
                'ldap_server_common_name' => $this->ldap_server_common_name,
            ]
        );
    }

    private function getTitle(?LDAPResult $ldap_group = null)
    {
        $title = sprintf(dgettext('tuleap-ldap', 'Set %1$s group binding'), $this->ldap_server_common_name);

        if ($ldap_group !== null) {
            $name  = $ldap_group->getGroupDisplayName();
            $title = sprintf(
                dgettext(
                    'tuleap-ldap',
                    'Update %1$s group binding (%2$s)'
                ),
                $this->ldap_server_common_name,
                $name
            );
        }

        return $title;
    }

    private function isPreserved(ProjectUGroup $ugroup, $bind_option)
    {
        return $this->user_group_manager->isMembersPreserving($ugroup->getId())
            || $bind_option === LDAP_GroupManager::PRESERVE_MEMBERS_OPTION;
    }

    private function isSynchronized(ProjectUGroup $ugroup, $synchro)
    {
        return $this->user_group_manager->isSynchronizedUgroup($ugroup->getId())
            || $synchro === LDAP_GroupManager::AUTO_SYNCHRONIZATION;
    }
}
