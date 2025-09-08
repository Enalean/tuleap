<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Project\Admin\Reference;

use EventManager;
use HTTPRequest;
use ReferenceManager;
use TemplateRendererFactory;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\Reference\Creation\CreateReferencePresenterBuilder;
use Tuleap\Project\Admin\Reference\Edition\EditReferencePresenterBuilder;
use Tuleap\Project\Admin\Reference\Edition\ReferenceIsReadOnlyChecker;
use Tuleap\Project\Service\ServiceDao;
use Views;
use Tuleap\Reference\NatureCollection;

class ReferenceAdministrationViews extends Views
{
    private const TEMPLATE_PAH = __DIR__ . '/../../../../templates/project/admin/references';
    private NatureCollection $nature_collection;
    private ReferenceManager $reference_manager;
    private \UserManager $user_manager;
    private TemplateRendererFactory $renderer_factory;
    private CreateReferencePresenterBuilder $create_reference_presenter_builder;
    private EditReferencePresenterBuilder $edit_reference_presenter_builder;

    public function __construct($controler, $view = null)
    {
        $this->View($controler, $view);
        $this->reference_manager                  = ReferenceManager::instance();
        $this->nature_collection                  = $this->reference_manager->getAvailableNatures();
        $this->user_manager                       = \UserManager::instance();
        $this->renderer_factory                   = TemplateRendererFactory::build();
        $service_dao                              = new ServiceDao();
        $this->create_reference_presenter_builder = new CreateReferencePresenterBuilder(
            $service_dao
        );
        $this->edit_reference_presenter_builder   = new EditReferencePresenterBuilder(
            $service_dao,
            new ReferenceIsReadOnlyChecker(EventManager::instance())
        );
    }

    #[\Override]
    public function header(): void
    {
        if (
            isset($_SERVER['REQUEST_URI']) &&
            (
                strpos($_SERVER['REQUEST_URI'], '/project/admin/reference.php?view=creation') === 0 ||
                strpos($_SERVER['REQUEST_URI'], '/project/admin/reference.php?view=edit') === 0
            )
        ) {
            $request          = HTTPRequest::instance();
            $project          = $request->getProject();
            $header_displayer = new HeaderNavigationDisplayer();
            $header_displayer->displayBurningParrotNavigation(_('Editing reference patterns'), $project, NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME);
            return;
        }
        project_admin_header(
            _('Editing reference patterns'),
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME
        );
    }

    #[\Override]
    public function footer(): void
    {
        project_admin_footer([]);
    }

    public function creation(): void
    {
        $request  = HTTPRequest::instance();
        $group_id = (int) $request->get('group_id');

        $user                        = $this->user_manager->getCurrentUser();
        $is_super_user               = $user->isSuperUser();
        $is_in_default_site_template = $group_id === 100;

        $is_super_user_in_default_template = $is_super_user && $is_in_default_site_template;

        $url        = '/project/admin/reference.php?group_id=' . urlencode((string) $group_id);
        $csrf_token = new \CSRFSynchronizerToken($url);
        $presenter  = $this->create_reference_presenter_builder->buildReferencePresenter(
            $group_id,
            $this->nature_collection->getNatures(),
            $is_super_user_in_default_template,
            $url,
            $csrf_token,
            $user
        );

        echo $this->renderer_factory->getRenderer(self::TEMPLATE_PAH)->renderToString('add-reference', $presenter);
    }

    public function edit(): void
    {
        $request  = HTTPRequest::instance();
        $group_id = $request->get('group_id');

        $user         = $this->user_manager->getCurrentUser();
        $reference_id = $request->get('reference_id');

        if (! $reference_id) {
            exit_error(
                _('Error'),
                _('A parameter is missing, please press the "Back" button and complete the form')
            );
        }

        $ref = $this->reference_manager->loadReference($reference_id, $group_id);
        if (! $ref) {
            echo '<p class="alert alert-error"> ' . _('This reference does not exist') . '</p>';

            return;
        }

        $url        = '/project/admin/reference.php?group_id=' . urlencode($group_id);
        $csrf_token = new \CSRFSynchronizerToken($url);
        $presenter  = $this->edit_reference_presenter_builder->buildReferencePresenter(
            $group_id,
            $this->nature_collection->getNatures(),
            $user,
            $url,
            $csrf_token,
            $ref,
        );

        echo $this->renderer_factory->getRenderer(self::TEMPLATE_PAH)->renderToString('edit-reference', $presenter);
    }
}
