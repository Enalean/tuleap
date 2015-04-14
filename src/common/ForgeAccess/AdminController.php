<?php
/**
  * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class ForgeAccess_AdminController {

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

    const TEMPLATE   = 'access_choice';
    const ACCESS_KEY = ForgeAccess::CONFIG;

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

    public function index() {
        $title  = $GLOBALS['Language']->getText('admin_main', 'configure_anonymous');
        $params = array(
            'title' => $title
        );
        $renderer  = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());

        $this->response->includeFooterJavascriptFile('/scripts/tuleap/admin-access-mode.js');
        $this->response->header($params);
        $renderer->renderToPage(
            self::TEMPLATE,
            new ForgeAccess_AdminPresenter(
                $this->csrf,
                $title,
                $this->localincfinder->getLocalIncPath(),
                ForgeConfig::get(ForgeAccess::CONFIG),
                count($this->user_dao->searchByStatus(PFUser::STATUS_RESTRICTED))
            )
        );
        $this->response->footer($params);
    }

    public function update() {
        $this->csrf->check();

        $validator = new Valid_WhiteList(
            self::ACCESS_KEY,
            array(
                ForgeAccess::ANONYMOUS,
                ForgeAccess::REGULAR,
                ForgeAccess::RESTRICTED,
            )
        );

        if ($this->request->valid($validator)) {
            $new_access_value = $this->request->get(self::ACCESS_KEY);
            $old_access_value = ForgeConfig::get(ForgeAccess::CONFIG);
            $this->manager->updateAccess($new_access_value, $old_access_value);

            $this->response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('admin_main', 'successfully_updated')
            );
        }
        $this->redirectToIndex();
    }

    public function notSiteAdmin() {
        $this->response->redirect(get_server_url());
    }

    private function getTemplateDir() {
        return ForgeConfig::get('codendi_dir') .'/src/templates/admin/anonymous/';
    }

    private function redirectToIndex() {
        $this->response->redirect($_SERVER['SCRIPT_URL']);
    }
}
