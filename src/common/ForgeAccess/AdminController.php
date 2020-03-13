<?php
/**
  * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\ForgeAccess\UnknownForgeAccessValueException;
use Tuleap\User\UserGroup\NameTranslator;

class ForgeAccess_AdminController
{
    public const TEMPLATE                  = 'access_choice';
    public const ACCESS_KEY                = ForgeAccess::CONFIG;
    public const SUPER_PUBLIC_PROJECTS_KEY = ForgeAccess::SUPER_PUBLIC_PROJECTS;

    /**
     * @var UserDao
     */
    private $user_dao;

    /**
     * @var Config_LocalIncFinder
     */
    private $localincfinder;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var ForgeAccess_ForgePropertiesManager
     */
    private $manager;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        ForgeAccess_ForgePropertiesManager $manager,
        Config_LocalIncFinder $localincfinder,
        UserDao $user_dao,
        Codendi_Request $request,
        Response $response
    ) {
        $this->csrf           = $csrf;
        $this->manager        = $manager;
        $this->request        = $request;
        $this->response       = $response;
        $this->user_dao       = $user_dao;
        $this->localincfinder = $localincfinder;
    }

    public function index()
    {
        $title      = $GLOBALS['Language']->getText('admin_main', 'configure_access_controls');
        $admin_page = new AdminPageRenderer();

        $this->response->includeFooterJavascriptFile('/scripts/tuleap/admin-access-mode.js');

        $admin_presenter = new ForgeAccess_AdminPresenter(
            $this->csrf,
            $title,
            $this->localincfinder->getLocalIncPath(),
            ForgeConfig::get(ForgeAccess::CONFIG),
            count($this->user_dao->searchByStatus(PFUser::STATUS_RESTRICTED)),
            ForgeConfig::get(NameTranslator::CONFIG_AUTHENTICATED_LABEL),
            ForgeConfig::get(NameTranslator::CONFIG_REGISTERED_LABEL),
            ForgeConfig::get(ForgeAccess::ANONYMOUS_CAN_SEE_SITE_HOMEPAGE),
            ForgeConfig::get(ForgeAccess::ANONYMOUS_CAN_SEE_CONTACT)
        );

        $admin_page->renderAPresenter(
            $title,
            $this->getTemplateDir(),
            self::TEMPLATE,
            $admin_presenter
        );
    }

    public function update()
    {
        $this->csrf->check();

        $updated  = false;
        $updated |= $this->updateAccessValue();

        if ($updated) {
            $this->response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('admin_main', 'successfully_updated')
            );
        }
        $this->redirectToIndex();
    }

    public function notSiteAdmin(HTTPRequest $request)
    {
        $this->response->redirect($request->getServerUrl());
    }

    private function getTemplateDir()
    {
        return ForgeConfig::get('codendi_dir') . '/src/templates/admin/anonymous/';
    }

    private function redirectToIndex()
    {
        $this->response->redirect($_SERVER['SCRIPT_NAME']);
    }

    /** @return bool true if updated */
    private function updateAccessValue()
    {
        $new_access_value = $this->request->get(self::ACCESS_KEY);
        try {
            $this->updateAccess($new_access_value);
        } catch (UnknownForgeAccessValueException $exception) {
            return false;
        }
        $this->updateLabels($new_access_value);

        return true;
    }

    /**
     * @throws UnknownForgeAccessValueException
     */
    private function updateAccess($new_access_value)
    {
        $old_access_value = ForgeConfig::get(ForgeAccess::CONFIG);
        $this->manager->updateAccess($new_access_value, $old_access_value);
    }

    private function updateLabels($new_access_value)
    {
        if ($new_access_value === ForgeAccess::RESTRICTED) {
            $this->manager->updateLabels(
                trim($this->request->getValidated('ugroup_authenticated_users', 'string', '')),
                trim($this->request->getValidated('ugroup_registered_users', 'string', ''))
            );
        } else {
            $this->manager->updateLabels('', '');
        }

        return true;
    }

    public function updateAnonymousAccess()
    {
        $this->csrf->check();

        $updated  = false;
        $updated |= $this->updateAnonymousForSiteHomePage();
        $updated |= $this->updateAnonymousForContact();

        if ($updated) {
            $this->response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('admin_main', 'successfully_updated')
            );
        }
        $this->redirectToIndex();
    }

    private function updateAnonymousForSiteHomePage()
    {
        $new_value = $this->getToggleValue(ForgeAccess::ANONYMOUS_CAN_SEE_SITE_HOMEPAGE);
        if ($new_value !== -1) {
            return $this->manager->updateAnonymousCanSeeSiteHomePage($new_value);
        }
        return false;
    }

    private function updateAnonymousForContact()
    {
        $new_value = $this->getToggleValue(ForgeAccess::ANONYMOUS_CAN_SEE_CONTACT);
        if ($new_value !== -1) {
            return $this->manager->updateAnonymousCanSeeContact($new_value);
        }
        return false;
    }

    private function getToggleValue($key)
    {
        $validator = new Valid_WhiteList(
            $key,
            array("0", "1")
        );
        if (! $this->request->valid($validator)) {
            return -1;
        }

        $new_value = $this->request->get($key);
        $old_value = ForgeConfig::get($key);

        if ($new_value == $old_value) {
            return -1;
        }

        return $new_value;
    }
}
