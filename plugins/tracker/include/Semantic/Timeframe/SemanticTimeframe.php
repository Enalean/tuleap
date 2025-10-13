<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Codendi_Request;
use PFUser;
use SimpleXMLElement;
use TrackerManager;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\FormElement\Field\NumericField;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Notifications\Settings\CalendarEventConfigDao;
use Tuleap\Tracker\Semantic\Timeframe\Administration\SemanticTimeframeAdministrationPresenterBuilder;
use Tuleap\Tracker\Semantic\Timeframe\Administration\SemanticTimeframeCurrentConfigurationPresenterBuilder;
use Tuleap\Tracker\Semantic\TrackerSemantic;
use Tuleap\Tracker\Semantic\TrackerSemanticManager;
use Tuleap\Tracker\Tracker;

class SemanticTimeframe extends TrackerSemantic
{
    public const string NAME = 'timeframe';

    /**
     * @var IComputeTimeframes
     */
    private $timeframe;

    public function __construct(
        Tracker $tracker,
        IComputeTimeframes $timeframe,
    ) {
        parent::__construct($tracker);
        $this->timeframe = $timeframe;
    }

    #[\Override]
    public function getShortName(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getLabel(): string
    {
        return dgettext('tuleap-tracker', 'Timeframe');
    }

    #[\Override]
    public function getDescription(): string
    {
        return dgettext('tuleap-tracker', 'Define the field to use to compute artifacts timeframes.');
    }

    #[\Override]
    public function fetchForSemanticsHomepage(): string
    {
        $presenter = $this->getCurrentConfigurationPresenter();
        return $this->getRenderer()->renderToString('semantic-timeframe-current-configuration', $presenter);
    }

    #[\Override]
    public function displayAdmin(
        TrackerSemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user,
    ): void {
        $GLOBALS['HTML']->addJavascriptAsset(
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/semantics-timeframe/frontend-assets',
                    '/assets/trackers/semantics-timeframe'
                ),
                'src/index.ts'
            )
        );
        $this->tracker->displayAdminItemHeaderBurningParrot(
            $tracker_manager,
            'editsemantic',
            $this->getLabel()
        );

        $builder = new SemanticTimeframeAdministrationPresenterBuilder(
            \Tracker_FormElementFactory::instance(),
            new SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever(
                new SemanticTimeframeDao(),
                \TrackerFactory::instance(),
                \Tracker_FormElementFactory::instance(),
            ),
            \EventManager::instance(),
            new CalendarEventConfigDao(),
        );

        $presenter = $builder->build(
            $this->getCSRFSynchronizerToken(),
            $this->tracker,
            $this->getUrl(),
            $this->getCurrentConfigurationPresenter(),
            $this->timeframe
        );

        $this->getRenderer()->renderToPage('timeframe-semantic-admin', $presenter);

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    #[\Override]
    public function process(
        TrackerSemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user,
    ): void {
        if ($request->exist('update-semantic-timeframe')) {
            $this->getCSRFSynchronizerToken()->check();

            $this->getSemanticTimeframeUpdator()->update($this->tracker, $request);

            $this->redirectToSemanticTimeframeAdmin();
        } elseif ($request->exist('reset-semantic-timeframe')) {
            $this->getCSRFSynchronizerToken()->check();

            $this->getSemanticTimeframeUpdator()->reset($this->tracker);

            $this->redirectToSemanticTimeframeAdmin();
        }

        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    private function redirectToSemanticTimeframeAdmin()
    {
        $GLOBALS['Response']->redirect($this->getUrl());
    }

    #[\Override]
    public function exportToXml(SimpleXMLElement $root, $xml_mapping): void
    {
        $this->timeframe->exportToXML($root, $xml_mapping);
    }

    #[\Override]
    public function isUsedInSemantics(TrackerField $field): bool
    {
        return $this->timeframe->isFieldUsed($field);
    }

    #[\Override]
    public function save(): bool
    {
        return $this->timeframe->save($this->tracker, new SemanticTimeframeDao());
    }

    public function getStartDateField(): ?DateField
    {
        return $this->timeframe->getStartDateField();
    }

    public function getDurationField(): ?NumericField
    {
        return $this->timeframe->getDurationField();
    }

    public function getEndDateField(): ?DateField
    {
        return $this->timeframe->getEndDateField();
    }

    public function getTimeframeCalculator(): IComputeTimeframes
    {
        return $this->timeframe;
    }

    public function isDefined(): bool
    {
        return $this->timeframe->isDefined();
    }

    #[\Override]
    public function exportToREST(PFUser $user): ?IRepresentSemanticTimeframe
    {
        return $this->timeframe->exportToREST($user);
    }

    private function getCSRFSynchronizerToken(): \CSRFSynchronizerToken
    {
        return new \CSRFSynchronizerToken(
            TRACKER_BASE_URL . '/?' . http_build_query(
                [
                    'semantic' => self::NAME,
                    'func'     => 'admin-semantic',
                ]
            )
        );
    }

    private function getRenderer(): \TemplateRenderer
    {
        return \TemplateRendererFactory::build()->getRenderer(
            __DIR__ . '/../../../templates/timeframe-semantic'
        );
    }

    private function getCurrentConfigurationPresenter(): Administration\SemanticTimeframeCurrentConfigurationPresenter
    {
        return (
            new SemanticTimeframeCurrentConfigurationPresenterBuilder(
                $this->tracker,
                $this->timeframe,
                new SemanticTimeframeDao(),
                \TrackerFactory::instance()
            )
        )->build();
    }

    private function getSemanticTimeframeUpdator(): SemanticTimeframeUpdator
    {
        $form_element_factory = \Tracker_FormElementFactory::instance();
        return new SemanticTimeframeUpdator(
            new SemanticTimeframeDao(),
            $form_element_factory,
            new SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever(
                new SemanticTimeframeDao(),
                \TrackerFactory::instance(),
                $form_element_factory
            ),
            new CalendarEventConfigDao(),
        );
    }

    public function isTimeframeNotConfiguredNorImplied(): bool
    {
        return $this->getTimeframeCalculator()->getName() === TimeframeImpliedFromAnotherTracker::NAME ||
               $this->getTimeframeCalculator()->getName() === TimeframeNotConfigured::NAME;
    }
}
