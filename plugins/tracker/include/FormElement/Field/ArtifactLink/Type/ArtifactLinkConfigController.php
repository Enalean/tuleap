<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Tracker\FormElement\FieldSpecificProperties\ArtifactLinkFieldSpecificPropertiesDAO;

final readonly class ArtifactLinkConfigController
{
    private const TEMPLATE = 'siteadmin-config/artifact-links';
    private const URL      = '/plugins/tracker/config.php?action=artifact-links';

    public const ACTION_ACTIVATE_NEW_INTERFACE = 'activate-new-artifact-links-interface';

    public function __construct(
        private TypeCreator $creator,
        private TypeEditor $editor,
        private TypeDeletor $deletor,
        private TypePresenterFactory $type_presenter_factory,
        private TypeUsagePresenterFactory $type_usage_presenter_factory,
        private ArtifactLinkFieldSpecificPropertiesDAO $new_artifact_link_interface,
        private AdminPageRenderer $admin_page_rendered,
    ) {
    }

    public function index(CSRFSynchronizerToken $csrf, BaseLayout $base_layout): void
    {
        $title = dgettext('tuleap-tracker', 'Trackers');

        $base_layout->addJavascriptAsset(new JavascriptViteAsset(
            new IncludeViteAssets(
                __DIR__ . '/../../../../../scripts/site-admin/frontend-assets',
                '/assets/trackers/site-admin'
            ),
            'src/admin-type.js'
        ));

        $this->admin_page_rendered->renderANoFramedPresenter(
            $title,
            TRACKER_TEMPLATE_DIR,
            self::TEMPLATE,
            $this->getPresenter($title, $csrf)
        );
    }

    public function createType(Codendi_Request $request, BaseLayout $layout): void
    {
        try {
            $this->creator->create(
                $request->get('shortname'),
                $request->get('forward_label'),
                $request->get('reverse_label')
            );

            $layout->addFeedback(
                Feedback::INFO,
                sprintf(dgettext('tuleap-tracker', 'The type %1$s has been successfully created.'), $request->get('shortname'))
            );
        } catch (TypeManagementException $exception) {
            $layout->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-tracker', 'Unable to create the requested type: %1$s'), $exception->getMessage())
            );
        }
        $layout->redirect(self::URL);
    }

    public function activateNewInterface(\HTTPRequest $request, BaseLayout $layout): void
    {
        $this->new_artifact_link_interface->massActivateForActiveTrackers();
        $layout->addFeedback(
            Feedback::SUCCESS,
            dgettext('tuleap-tracker', 'The new interface has been successfully activated for all trackers.')
        );
        $layout->redirect(self::URL);
    }

    public function editType(Codendi_Request $request, BaseLayout $layout): void
    {
        try {
            $this->editor->edit(
                $request->get('shortname'),
                $request->get('forward_label'),
                $request->get('reverse_label')
            );

            $layout->addFeedback(
                Feedback::INFO,
                sprintf(dgettext('tuleap-tracker', 'The type %1$s has been successfully updated.'), $request->get('shortname'))
            );
        } catch (TypeManagementException $exception) {
            $layout->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-tracker', 'Unable to edit the requested type: %1$s'), $exception->getMessage())
            );
        }
        $layout->redirect(self::URL);
    }

    public function deleteType(Codendi_Request $request, BaseLayout $layout): void
    {
        try {
            $this->deletor->delete($request->get('shortname'));

            $layout->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-tracker', 'The type has been successfully deleted.')
            );
        } catch (TypeManagementException $exception) {
            $layout->addFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-tracker', 'An error has occurred during the deletion of the type: %1$s'), $exception->getMessage())
            );
        }
        $layout->redirect(self::URL);
    }

    private function getPresenter(string $title, CSRFSynchronizerToken $csrf): ArtifactLinkPresenter
    {
        $types = $this->type_presenter_factory->getAllTypes();

        $types_usage = $this->type_usage_presenter_factory->getTypesUsagePresenters($types);

        return new ArtifactLinkPresenter(
            new TypeConfigPresenter($title, $types_usage),
            new NewInterfacePresenter(
                $this->new_artifact_link_interface->countNumberOfTrackersWithoutTheFeature(),
            ),
            CSRFSynchronizerTokenPresenter::fromToken($csrf),
        );
    }
}
