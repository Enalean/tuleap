<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\MailingList;

use CSRFSynchronizerToken;
use HTTPRequest;
use MailingListDao;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\NotFoundException;

class MailingListHomepageController implements DispatchableWithBurningParrot, DispatchableWithRequest
{
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var MailingListDao
     */
    private $dao;
    /**
     * @var MailingListPresenterCollectionBuilder
     */
    private $presenter_collection_builder;
    /**
     * @var \BaseLanguage
     */
    private $base_language;

    public function __construct(
        \TemplateRenderer $renderer,
        MailingListDao $dao,
        MailingListPresenterCollectionBuilder $presenter_collection_builder,
        \BaseLanguage $base_language,
    ) {
        $this->renderer                     = $renderer;
        $this->dao                          = $dao;
        $this->presenter_collection_builder = $presenter_collection_builder;
        $this->base_language                = $base_language;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $request->getProject();
        if ($project->isError()) {
            throw new NotFoundException();
        }
        if (! $project->usesMail()) {
            throw new NotFoundException();
        }
        $service = $project->getService(\Service::ML);
        if (! ($service instanceof ServiceMailingList)) {
            throw new NotFoundException();
        }

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new \Tuleap\Layout\IncludeCoreAssets(),
                'mailing-lists-homepage.js'
            )
        );

        $current_user = $request->getCurrentUser();

        $mailing_list_presenters = $this->getMailingListPresenters($project, $request, $current_user);

        $purified_overridable_intro = '';
        if ($this->base_language->hasText('mail_index', 'mail_list_via_gnu')) {
            $purified_overridable_intro = \Codendi_HTMLPurifier::instance()->purify(
                $this->base_language->getOverridableText('mail_index', 'mail_list_via_gnu'),
                \Codendi_HTMLPurifier::CONFIG_LIGHT,
            );
        }

        $service->displayMailingListHeader($current_user, _('Mailing lists'));
        $this->renderer->renderToPage(
            'mailing-lists-homepage',
            new MailingListHomepagePresenter(
                $mailing_list_presenters,
                $current_user->isAdmin((int) $project->getID()),
                MailingListCreationController::getUrl($project),
                $purified_overridable_intro,
            )
        );
        $service->displayFooter();
    }

    public static function getCSRF(\Project $project): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken('/mail/?group_id=' . urlencode((string) $project->getID()));
    }

    public static function getUrl(\Project $project): string
    {
        return '/project/' . urlencode((string) $project->getID()) . '/admin/mailing-lists';
    }

    /**
     * @return MailingListPresenter[]
     */
    private function getMailingListPresenters(\Project $project, HTTPRequest $request, \PFUser $user): array
    {
        if (! $user->isAnonymous() && $user->isMember((int) $project->getID())) {
            $data_access_result = $this->dao->searchActiveListsInProject((int) $project->getID());
        } else {
            $data_access_result = $this->dao->searchPublicListsInProject((int) $project->getID());
        }
        if (! $data_access_result) {
            return [];
        }

        return $this->presenter_collection_builder->build($data_access_result, $project, $request);
    }
}
