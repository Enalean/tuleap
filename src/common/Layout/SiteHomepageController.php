<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use Admin_Homepage_Dao;
use CSRFSynchronizerToken;
use EventManager;
use HTTPRequest;
use ForgeConfig;
use Event;
use ProjectManager;
use SVN_LogDao;
use TemplateRendererFactory;
use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Layout\HomePage\NewsCollection;
use Tuleap\Layout\HomePage\NewsCollectionBuilder;
use Tuleap\Layout\HomePage\StatisticsCollectionBuilder;
use Tuleap\News\NewsDao;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Theme\BurningParrot\HomePagePresenter;
use Tuleap\User\Account\RegistrationGuardEvent;
use User_LoginPresenterBuilder;
use UserManager;

class SiteHomepageController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var Admin_Homepage_Dao
     */
    private $dao;
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(Admin_Homepage_Dao $dao, ProjectManager $project_manager, UserManager $user_manager, EventManager $event_manager)
    {
        $this->dao             = $dao;
        $this->project_manager = $project_manager;
        $this->user_manager    = $user_manager;
        $this->event_manager   = $event_manager;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $event_manager = EventManager::instance();

        $event_manager->processEvent(Event::DISPLAYING_HOMEPAGE, []);

        $registration_guard = $event_manager->dispatch(new RegistrationGuardEvent());

        $login_url = '';
        $event_manager->processEvent(\Event::GET_LOGIN_URL, ['return_to' => '', 'login_url' => &$login_url]);

        $news_collection_builder = new NewsCollectionBuilder(new NewsDao(), $this->project_manager, $this->user_manager, \Codendi_HTMLPurifier::instance());
        $news_collection         = $news_collection_builder->build();

        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons(
            new IncludeCoreAssets(),
            'homepage-style'
        ));

        $layout->header(
            HeaderConfigurationBuilder::get($GLOBALS['Language']->getText('homepage', 'title'))
                ->withBodyClass(['homepage'])
                ->build()
        );
        $this->displayStandardHomepage(
            $registration_guard->isRegistrationPossible(),
            $login_url,
            $news_collection
        );
        $layout->footer([]);
        $this->includeRelativeDatesAssetsIfNeeded($news_collection, $layout);
    }

    private function displayStandardHomepage(bool $display_new_account_button, string $login_url, NewsCollection $news_collection): void
    {
        $current_user = UserManager::instance()->getCurrentUser();

        $headline = $this->dao->getHeadlineByLanguage($current_user->getLocale());
        if ($headline === null || $headline === '') {
            $headline = gettext(
                "Tuleap helps teams to deliver awesome applications, better, faster, and easier.\n" .
                'Here you plan, track, code, and collaborate on software projects.'
            );
        }

        $login_presenter_builder = new User_LoginPresenterBuilder($this->event_manager);
        $login_csrf              = new CSRFSynchronizerToken('/account/login.php');
        $login_presenter         = $login_presenter_builder->buildForHomepage($login_csrf);

        $display_new_account_button = ($current_user->isAnonymous() && $display_new_account_button);

        $statistics_collection_builder = new StatisticsCollectionBuilder(
            $this->project_manager,
            $this->user_manager,
            $this->event_manager,
            new SVN_LogDao()
        );

        $statistics_collection = $statistics_collection_builder->build();

        $templates_dir = ForgeConfig::get('codendi_dir') . '/src/templates/homepage/';
        $renderer      = TemplateRendererFactory::build()->getRenderer($templates_dir);
        $presenter     = new HomePagePresenter(
            $headline,
            $current_user,
            $login_presenter,
            $display_new_account_button,
            $login_url,
            $statistics_collection,
            $news_collection
        );
        $renderer->renderToPage('homepage', $presenter);
    }

    private function includeRelativeDatesAssetsIfNeeded(NewsCollection $news_collection, BaseLayout $layout): void
    {
        if (! $news_collection->hasNews()) {
            return;
        }
        $layout->addJavascriptAsset(RelativeDatesAssetsRetriever::getAsJavascriptAssets());
    }
}
