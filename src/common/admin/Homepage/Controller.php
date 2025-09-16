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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Config\ConfigDao;
use Tuleap\Layout\HomePage\StatisticsCollectionBuilder;

class Admin_Homepage_Controller
{
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

    public const TEMPLATE = 'admin';
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var ConfigDao
     */
    private $config_dao;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        Admin_Homepage_Dao $dao,
        Codendi_Request $request,
        Response $response,
        AdminPageRenderer $admin_page_renderer,
        ConfigDao $config_dao,
    ) {
        $this->csrf                = $csrf;
        $this->dao                 = $dao;
        $this->request             = $request;
        $this->response            = $response;
        $this->admin_page_renderer = $admin_page_renderer;
        $this->config_dao          = $config_dao;
    }

    public function index()
    {
        $include_assets = new \Tuleap\Layout\IncludeCoreAssets();
        $this->response->includeFooterJavascriptFile($include_assets->getFileURL('ckeditor.js'));
        $this->response->includeFooterJavascriptFile('/scripts/tuleap/tuleap-ckeditor-toolbar.js');
        $this->response->includeFooterJavascriptFile('/scripts/tuleap/admin-homepage.js');

        $title     = _('Configure homepage');
        $headlines = $this->getHeadlines();
        $presenter = new Admin_Homepage_Presenter(
            $this->csrf,
            $title,
            ForgeConfig::get(StatisticsCollectionBuilder::CONFIG_DISPLAY_STATISTICS),
            $headlines
        );

        $this->admin_page_renderer->renderAPresenter(
            $title,
            $this->getTemplateDir(),
            self::TEMPLATE,
            $presenter
        );
    }

    public function update()
    {
        $this->csrf->check();

        if ($this->request->get('use_statistics_homepage')) {
            $this->config_dao->saveBool(StatisticsCollectionBuilder::CONFIG_DISPLAY_STATISTICS, true);
        } else {
            $this->config_dao->saveBool(StatisticsCollectionBuilder::CONFIG_DISPLAY_STATISTICS, false);
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
                _('Successfully updated.')
            );
        }
        $this->redirectToIndex();
    }

    public function notSiteAdmin()
    {
        $this->response->redirect(\Tuleap\ServerHostname::HTTPSUrl());
    }

    private function getTemplateDir()
    {
        return __DIR__ . '/../../../templates/homepage/';
    }

    /**
     * @return Admin_Homepage_HeadlinePresenter[]
     */
    private function getHeadlines(): array
    {
        $supported_languages = array_map('trim', explode(',', ForgeConfig::get('sys_supported_languages')));
        $headlines           = [];
        foreach ($supported_languages as $supported_language) {
            $headlines[$supported_language] = new Admin_Homepage_HeadlinePresenter(
                $supported_language,
                ''
            );
        }
        foreach ($this->dao->searchHeadlines() as $row) {
            $headlines[$row['language_id']] = new Admin_Homepage_HeadlinePresenter(
                $row['language_id'],
                $row['headline']
            );
        }

        return array_values($headlines);
    }

    private function redirectToIndex()
    {
        $this->response->redirect($_SERVER['SCRIPT_NAME']);
    }

    private function removeCustomLogo()
    {
        $filename = Admin_Homepage_LogoFinder::getCustomPath();
        if (is_file($filename)) {
            unlink($filename);
        }
    }

    private function moveUploadedLogo()
    {
        if (! isset($_FILES['logo'])) {
            return;
        }
        $uploaded_logo = $_FILES['logo'];

        switch ($uploaded_logo['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $this->response->addFeedback(
                    Feedback::ERROR,
                    _('Uploaded file is too big')
                );
                return;
            default:
                $this->response->addFeedback(
                    Feedback::ERROR,
                    sprintf(_('File upload error (error code: %1$s)'), $uploaded_logo['error'])
                );
                return;
        }

        $imageinfo = getimagesize($uploaded_logo['tmp_name']);
        if (! $imageinfo || $imageinfo['mime'] !== 'image/png') {
            $this->response->addFeedback(
                Feedback::ERROR,
                _('You should send a png image')
            );
            return;
        }

        $height_index = 1;
        if ($imageinfo[$height_index] > 100) {
            $this->response->addFeedback(
                Feedback::ERROR,
                _('You should send an image with height smaller than 100px')
            );
            return;
        }

        return move_uploaded_file($uploaded_logo['tmp_name'], Admin_Homepage_LogoFinder::getCustomPath());
    }
}
