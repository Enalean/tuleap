<?php
/**
  * Copyright (c) Enalean, 2015 - 2016. All Rights Reserved.
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

class Admin_Homepage_Controller {

    /**
     * @var Response
     */
    private $response;

    /**
     * @var Codendi_Request
     */
    private $request;

    /**
     * @var Admin_Homepage_Dao
     */
    private $dao;

    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf;

    const TEMPLATE = 'admin';
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        Admin_Homepage_Dao $dao,
        Codendi_Request $request,
        Response $response,
        AdminPageRenderer $admin_page_renderer
    ) {
        $this->csrf                = $csrf;
        $this->dao                 = $dao;
        $this->request             = $request;
        $this->response            = $response;
        $this->admin_page_renderer = $admin_page_renderer;
    }

    public function index()
    {
        $this->response->includeFooterJavascriptFile("/scripts/ckeditor-4.3.2/ckeditor.js");
        $this->response->includeFooterJavascriptFile('/scripts/tuleap/tuleap-ckeditor-toolbar.js');
        $this->response->includeFooterJavascriptFile('/scripts/tuleap/admin-homepage.js');

        $title     = $GLOBALS['Language']->getText('admin_main', 'configure_homepage');
        $headlines = $this->getHeadlines();
        $presenter = new Admin_Homepage_Presenter(
            $this->csrf,
            $title,
            $this->dao->isStandardHomepageUsed(),
            $this->dao->areStatisticsDisplayedOnHomePage(),
            $headlines
        );

        $this->admin_page_renderer->renderAPresenter(
            $title,
            $this->getTemplateDir(),
            self::TEMPLATE,
            $presenter
        );
    }

    public function update() {
        $this->csrf->check();

        if ($this->request->get('use_standard_homepage')) {
            $this->dao->useStandardHomepage();
        }

        if ($this->request->get('use_statistics_homepage')) {
            $this->dao->toggleStatisticsOnHomePage(true);
        } else {
            $this->dao->toggleStatisticsOnHomePage(false);
        }

        $headlines = $this->request->get('headlines');
        if (is_array($headlines)) {
            $this->dao->save($headlines);
        }

        if ($this->request->get('remove_custom_logo')) {
            $this->removeCustomLogo();
        }
        $this->moveUploadedLogo();

        if (! $this->response->feedbackHasWarningsOrErrors()) {
            $this->response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('admin_main', 'successfully_updated')
            );
        }
        $this->redirectToIndex();
    }

    public function notSiteAdmin(HTTPRequest $request) {
        $this->response->redirect($request->getServerUrl());
    }

    private function getTemplateDir() {
        return ForgeConfig::get('codendi_dir') .'/src/templates/homepage/';
    }

    private function getHeadlines() {
        $headlines = array();
        foreach ($this->dao->searchHeadlines() as $row) {
            $headlines[] = new Admin_Homepage_HeadlinePresenter(
                $row['language_id'],
                $row['headline']
            );
        }

        return $headlines;
    }

    private function redirectToIndex() {
        $this->response->redirect($_SERVER['SCRIPT_NAME']);
    }

    private function removeCustomLogo() {
        $filename = Admin_Homepage_LogoFinder::getCustomPath();
        if (is_file($filename)) {
            unlink($filename);
        }
    }

    private function moveUploadedLogo() {
        if (! isset($_FILES['logo'])) {
            return;
        }
        $uploaded_logo = $_FILES['logo'];

        switch ($uploaded_logo['error']) {
            case UPLOAD_ERR_OK:
                continue;
                break;
            case UPLOAD_ERR_NO_FILE:
                return;
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->response->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('admin_main', 'logo_too_big')
                );
                return;
                break;
            default:
                $this->response->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('admin_main', 'upload_error', $uploaded_logo['error'])
                );
                return;
        }

        $imageinfo = getimagesize($uploaded_logo['tmp_name']);
        if (! $imageinfo || $imageinfo['mime'] !== 'image/png') {
            $this->response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('admin_main', 'no_png')
            );
            return;
        }

        $height_index = 1;
        if ($imageinfo[$height_index] > 100) {
            $this->response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('admin_main', '100px')
            );
            return;
        }

        return move_uploaded_file($uploaded_logo['tmp_name'], Admin_Homepage_LogoFinder::getCustomPath());
    }
}
