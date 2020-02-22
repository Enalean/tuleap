<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature;

use CSRFSynchronizerToken;
use Codendi_Request;
use Response;
use Feedback;
use Tuleap\Admin\AdminPageRenderer;

class NatureConfigController
{
    private static $TEMPLATE = 'siteadmin-config/natures';
    private static $URL      = '/plugins/tracker/config.php?action=natures';

    /** @var NatureCreator */
    private $nature_creator;

    /** @var NaturePresenterFactory */
    private $nature_presenter_factory;

    /** @var NatureEditor */
    private $nature_editor;

    /** @var NatureDeletor */
    private $nature_deletor;

    /** @var AdminPageRenderer */
    private $admin_page_rendered;

    /** @var NatureUsagePresenterFactory */
    private $nature_usage_presenter_factory;

    public function __construct(
        NatureCreator $nature_creator,
        NatureEditor $nature_editor,
        NatureDeletor $nature_deletor,
        NaturePresenterFactory $nature_presenter_factory,
        NatureUsagePresenterFactory $nature_usage_presenter_factory,
        AdminPageRenderer $admin_page_rendered
    ) {
        $this->nature_creator                 = $nature_creator;
        $this->nature_presenter_factory       = $nature_presenter_factory;
        $this->nature_editor                  = $nature_editor;
        $this->nature_deletor                 = $nature_deletor;
        $this->admin_page_rendered            = $admin_page_rendered;
        $this->nature_usage_presenter_factory = $nature_usage_presenter_factory;
    }

    public function index(CSRFSynchronizerToken $csrf, Response $response)
    {
        $title  = $GLOBALS['Language']->getText('plugin_tracker_config', 'title');

        $this->admin_page_rendered->renderANoFramedPresenter(
            $title,
            TRACKER_TEMPLATE_DIR,
            self::$TEMPLATE,
            $this->getNatureConfigPresenter($title, $csrf)
        );
    }

    public function createNature(Codendi_Request $request, Response $response)
    {
        try {
            $this->nature_creator->create(
                $request->get('shortname'),
                $request->get('forward_label'),
                $request->get('reverse_label')
            );

            $response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'create_success',
                    $request->get('shortname')
                )
            );
        } catch (NatureManagementException $exception) {
            $response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'create_error',
                    $exception->getMessage()
                )
            );
        }
        $response->redirect(self::$URL);
    }

    public function editNature(Codendi_Request $request, Response $response)
    {
        try {
            $this->nature_editor->edit(
                $request->get('shortname'),
                $request->get('forward_label'),
                $request->get('reverse_label')
            );

            $response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'edit_success',
                    $request->get('shortname')
                )
            );
        } catch (NatureManagementException $exception) {
            $response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText(
                    'plugin_tracker_artifact_links_natures',
                    'edit_error',
                    $exception->getMessage()
                )
            );
        }
        $response->redirect(self::$URL);
    }

    public function deleteNature(Codendi_Request $request, Response $response)
    {
        try {
            $this->nature_deletor->delete($request->get('shortname'));

            $response->addFeedback(
                Feedback::INFO,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'delete_success')
            );
        } catch (NatureManagementException $exception) {
            $response->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'delete_error', $exception->getMessage())
            );
        }
        $response->redirect(self::$URL);
    }

    /** @return NatureConfigPresenter */
    private function getNatureConfigPresenter($title, CSRFSynchronizerToken $csrf)
    {
        $natures = $this->nature_presenter_factory->getAllNatures();

        $natures_usage = $this->nature_usage_presenter_factory->getNaturesUsagePresenters($natures);

        return new NatureConfigPresenter($title, $natures_usage, $csrf);
    }
}
