<?php
/*
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Theme\BurningParrot;

use Admin_Homepage_Dao;
use CSRFSynchronizerToken;
use Event;
use EventManager;
use Layout;
use URLRedirect;
use User_LoginPresenterBuilder;
use UserManager;
use Widget_Static;
use TemplateRendererFactory;
use HTTPRequest;
use PFUser;
use ForgeConfig;
use Tuleap\Theme\BurningParrot\Navbar\PresenterBuilder as NavbarPresenterBuilder;

class BurningParrotTheme extends Layout
{
    /** @var \MustacheRenderer */
    private $renderer;

    /** @var PFUser */
    private $user;

    /** @var HTTPRequest */
    private $request;

    public function __construct($root, PFUser $user)
    {
        parent::__construct($root);
        $this->user     = $user;
        $this->request  = HTTPRequest::instance();
        $this->renderer = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $this->includeFooterJavascriptFile('/themes/common/tlp/dist/tlp.'. $user->getLocale() .'.min.js');
        $this->includeFooterJavascriptFile($this->include_asset->getFileURL('burningparrot.js'));
    }

    public function includeCalendarScripts()
    {
    }

    public function getDatePicker()
    {
    }

    public function header(array $params)
    {
        $url_redirect             = new URLRedirect(EventManager::instance());
        $header_presenter_builder = new HeaderPresenterBuilder();
        $body_classes             = $this->getArrayOfClassnamesForBodyTag($params);
        $main_classes             = isset($params['main_classes']) ? $params['main_classes'] : array();
        $sidebar                  = isset($params['sidebar']) ? $params['sidebar'] : array();

        $header_presenter = $header_presenter_builder->build(
            new NavbarPresenterBuilder(),
            $this->request,
            $this->user,
            $this->imgroot,
            $params['title'],
            $this->_feedback->logs,
            $body_classes,
            $main_classes,
            $sidebar,
            $url_redirect
        );

        $this->renderer->renderToPage('header', $header_presenter);
    }

    private function getArrayOfClassnamesForBodyTag($params)
    {
        return isset($params['body_class']) ? $params['body_class'] : array();
    }

    public function footer(array $params)
    {
        $javascript_files = array();
        EventManager::instance()->processEvent(
            Event::BURNING_PARROT_GET_JAVASCRIPT_FILES,
            array(
                'javascript_files' => &$javascript_files
            )
        );

        foreach ($javascript_files as $javascript_file) {
            $this->includeFooterJavascriptFile($javascript_file);
        }

        $footer = new FooterPresenter(
            $this->javascript_in_footer,
            $this->getTuleapVersion()
        );
        $this->renderer->renderToPage('footer', $footer);

        if ($this->isInDebugMode()) {
            $this->showDebugInfo();
        }
    }

    public function displayStaticWidget(Widget_Static $widget)
    {
        $this->renderer->renderToPage('widget', $widget);
    }

    private function getTemplateDir()
    {
        return __DIR__ . '/templates/';
    }

    private function isInDebugMode()
    {
        return (ForgeConfig::get('DEBUG_MODE') && (ForgeConfig::get('DEBUG_DISPLAY_FOR_ALL') || user_ismember(1, 'A')));
    }

    public function displayStandardHomepage(
        $display_homepage_news,
        $display_homepage_login_form,
        $is_secure
    ) {
        $homepage_dao = $this->getAdminHomepageDao();
        $current_user = UserManager::instance()->getCurrentUser();

        $headline = $homepage_dao->getHeadlineByLanguage($current_user->getLocale());

        $most_secure_url = '';
        if (ForgeConfig::get('sys_https_host')) {
            $most_secure_url = 'https://'. ForgeConfig::get('sys_https_host');
        }

        $login_presenter_builder = new User_LoginPresenterBuilder();
        $login_csrf              = new CSRFSynchronizerToken('/account/login.php');
        $login_presenter         = $login_presenter_builder->buildForHomepage($is_secure, $login_csrf);

        $templates_dir = ForgeConfig::get('codendi_dir') . '/src/templates/homepage/';
        $renderer      = TemplateRendererFactory::build()->getRenderer($templates_dir);
        $presenter     = new HomePagePresenter(
            $headline,
            $current_user,
            $most_secure_url,
            $login_presenter,
            $display_homepage_login_form
        );
        $renderer->renderToPage('homepage', $presenter);
    }

    private function getAdminHomepageDao()
    {
        return new Admin_Homepage_Dao();
    }

    private function getTuleapVersion()
    {
        return trim(file_get_contents($GLOBALS['tuleap_dir'].'/VERSION'));
    }
}
