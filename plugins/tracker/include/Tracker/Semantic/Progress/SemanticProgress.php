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
use Tuleap\Tracker\Semantic\Progress\Administration\SemanticProgressAdminPresenter;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Progress\Administration\SemanticProgressIntroductionPresenter;
use Tuleap\Tracker\Semantic\Progress\Events\GetSemanticProgressUsageEvent;

class SemanticProgress extends \Tracker_Semantic
{
    public const NAME = 'progress';
    /**
     * @var IComputeProgression | null
     */
    private $method;

    public function __construct(
        Tracker $tracker,
        ?IComputeProgression $method
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

    public function display(): void
    {
        $is_semantic_defined = $this->isDefined();
        $renderer            = $this->getTemplateRenderer();
        $presenter           = new SemanticProgressIntroductionPresenter(
            $this->getSemanticUsage(),
            $is_semantic_defined,
            $this->isDefined() ? $this->method->getCurrentConfigurationDescription() : ''
        );

        $renderer->renderToPage(
            'semantic-progress-introduction',
            $presenter
        );
    }

    public function displayAdmin(
        Tracker_SemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ): void {
        $semantic_manager->displaySemanticHeader($this, $tracker_manager);

        $renderer = $this->getTemplateRenderer();
        $renderer->renderToPage(
            'semantic-progress-admin',
            new SemanticProgressAdminPresenter(
                $this->tracker,
                $this->getSemanticUsage()
            )
        );

        $semantic_manager->displaySemanticFooter($this, $tracker_manager);
    }

    public function process(
        Tracker_SemanticManager $semantic_manager,
        TrackerManager $tracker_manager,
        Codendi_Request $request,
        PFUser $current_user
    ) {
        $this->displayAdmin($semantic_manager, $tracker_manager, $request, $current_user);
    }

    public function exportToXml(SimpleXMLElement $root, $xml_mapping): void
    {
    }

    public function exportToREST(PFUser $user): bool
    {
        return false;
    }

    public function isUsedInSemantics(Tracker_FormElement_Field $field): bool
    {
        if (! $this->isDefined()) {
            return false;
        }

        return $this->method->isFieldUsedInComputation($field);
    }

    public function save(): bool
    {
        return false;
    }

    /**
     * @psalm-assert-if-true !null $this->method
     */
    public function isDefined(): bool
    {
        return $this->method !== null;
    }

    public function getComputationMethod(): ?IComputeProgression
    {
        return $this->method;
    }

    public function getProgress(Artifact $artifact, \PFUser $user): ?float
    {
        if (! $this->isDefined()) {
            return null;
        }

        return $this->method->computeProgression($artifact, $user);
    }

    private function getSemanticUsage(): string
    {
        $event         = new GetSemanticProgressUsageEvent();
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
}
