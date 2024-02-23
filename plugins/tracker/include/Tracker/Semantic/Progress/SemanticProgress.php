<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress;

use Codendi_Request;
use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_FormElement_Field;
use Tracker_SemanticManager;
use TrackerManager;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Semantic\Progress\Administration\SemanticProgressAdminPresenterBuilder;
use Tuleap\Tracker\Semantic\Progress\Administration\SemanticProgressIntroductionPresenter;
use Tuleap\Tracker\Semantic\Progress\Events\GetSemanticProgressUsageEvent;

class SemanticProgress extends \Tracker_Semantic
{
    public const NAME = 'progress';
    /**
     * @var IComputeProgression
     */
    private $method;

    public function __construct(
        Tracker $tracker,
        IComputeProgression $method,
    ) {
        parent::__construct($tracker);
        $this->method = $method;
    }

    public function getShortName(): string
    {
        return self::NAME;
    }

    public function getLabel(): string
    {
        return dgettext('tuleap-tracker', 'Progress');
    }

    public function getDescription(): string
    {
        return dgettext(
            'tuleap-tracker',
            'Define the method and the fields to use to compute artifacts progression.'
        );
    }

    public function fetchForSemanticsHomepage(): string
    {
        $is_semantic_defined = $this->isDefined();
        $renderer            = $this->getTemplateRenderer();
        $presenter           = new SemanticProgressIntroductionPresenter(
            $this->getSemanticUsage(),
            $is_semantic_defined,
            $this->method->getCurrentConfigurationDescription()
        );

        return $renderer->renderToString(
            'semantic-progress-introduction',
            $presenter
        );
    }

    public function displayAdmin(
        Tracker_SemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user,
    ): void {
        $this->tracker->displayAdminItemHeaderBurningParrot(
            $tracker_manager,
            'editsemantic',
            $this->getLabel()
        );

        $renderer = $this->getTemplateRenderer();
        $builder  = new SemanticProgressAdminPresenterBuilder(
            \Tracker_FormElementFactory::instance()
        );

        $GLOBALS['HTML']->addJavascriptAsset(new JavascriptAsset(
            new IncludeAssets(__DIR__ . '/../../../../scripts/tracker-admin/frontend-assets', '/assets/trackers/tracker-admin'),
            'progress-semantic.js'
        ));

        $renderer->renderToPage(
            'semantic-progress-admin',
            $builder->build(
                $this->tracker,
                $this->getSemanticUsage(),
                $this->isDefined(),
                $this->getUrl(),
                $this->getCSRFToken(),
                $this->method
            )
        );

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    public function process(
        Tracker_SemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user,
    ) {
        if ($request->exist('update-semantic-progress')) {
            $this->getCSRFToken()->check();
            $this->updateSemantic($request);
            $this->reloadSemanticProgressAdmin();
            return;
        }
        if ($request->exist('reset-semantic-progress')) {
            $this->getCSRFToken()->check();
            $this->resetSemantic();
            $this->reloadSemanticProgressAdmin();
            return;
        }

        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    private function reloadSemanticProgressAdmin(): void
    {
        $GLOBALS['Response']->redirect($this->getUrl());
    }

    public function exportToXml(SimpleXMLElement $root, $xml_mapping): void
    {
        $this->method->exportToXMl($root, $xml_mapping);
    }

    public function exportToREST(PFUser $user): ?IRepresentSemanticProgress
    {
        return $this->method->exportToREST($user);
    }

    public function isUsedInSemantics(Tracker_FormElement_Field $field): bool
    {
        return $this->method->isFieldUsedInComputation($field);
    }

    public function save(): bool
    {
        return $this->method->saveSemanticForTracker($this->tracker);
    }

    public function isDefined(): bool
    {
        return $this->method->isConfiguredAndValid();
    }

    public function getComputationMethod(): IComputeProgression
    {
        return $this->method;
    }

    private function getSemanticUsage(): string
    {
        $event         = new GetSemanticProgressUsageEvent($this->tracker);
        $event_manager = \EventManager::instance();

        $event_manager->processEvent($event);

        return $event->getSemanticUsage();
    }

    private function getTemplateRenderer(): \TemplateRenderer
    {
        $renderer = \TemplateRendererFactory::build()->getRenderer(
            __DIR__ . '/../../../../templates/semantic-progress'
        );
        return $renderer;
    }

    private function updateSemantic(Codendi_Request $request): void
    {
        $method_builder = new MethodBuilder(
            \Tracker_FormElementFactory::instance(),
            new SemanticProgressDao(),
            new TypePresenterFactory(
                new TypeDao(),
                new ArtifactLinksUsageDao()
            )
        );

        $new_method = $method_builder->buildMethodFromRequest($this->tracker, $request);
        if (! $new_method->saveSemanticForTracker($this->tracker)) {
            $GLOBALS['Response']->addFeedback(\Feedback::ERROR, $new_method->getErrorMessage());
            return;
        }

        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Semantic has been saved successfully')
        );
    }

    private function resetSemantic(): void
    {
        if (! $this->method->deleteSemanticForTracker($this->tracker)) {
            $GLOBALS['Response']->addFeedback(
                \Feedback::ERROR,
                dgettext(
                    'tuleap-tracker',
                    'An error occurred while deleting the semantic'
                )
            );
            return;
        }

        $GLOBALS['Response']->addFeedback(
            \Feedback::INFO,
            dgettext('tuleap-tracker', 'Semantic has been deleted successfully')
        );
    }
}
