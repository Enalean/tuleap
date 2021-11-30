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

namespace Tuleap\Reference;

use ProjectManager;
use Tuleap\Event\Dispatchable;
use Tuleap\Project\ProjectAccessChecker;

class CrossReferenceByNatureOrganizer implements Dispatchable
{
    public const NAME = "crossReferenceByNatureOrganizer";

    /**
     * @var CrossReferencePresenter[]
     */
    private $cross_reference_presenters;
    /**
     * @var array<string, CrossReferenceNaturePresenter>
     */
    private $natures;
    /**
     * @var NatureCollection
     */
    private $available_nature_collection;
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var ProjectAccessChecker
     */
    private $project_access_checker;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @param CrossReferencePresenter[] $cross_reference_presenters
     */
    public function __construct(
        ProjectManager $project_manager,
        ProjectAccessChecker $project_access_checker,
        array $cross_reference_presenters,
        NatureCollection $available_nature_collection,
        \PFUser $current_user,
    ) {
        $this->project_manager             = $project_manager;
        $this->project_access_checker      = $project_access_checker;
        $this->cross_reference_presenters  = $cross_reference_presenters;
        $this->available_nature_collection = $available_nature_collection;
        $this->current_user                = $current_user;

        $this->natures = [];
    }

    /**
     * @return CrossReferencePresenter[]
     */
    public function getCrossReferencePresenters(): array
    {
        return $this->cross_reference_presenters;
    }

    public function removeUnreadableCrossReference(CrossReferencePresenter $cross_ref_presenter_to_remove): void
    {
        foreach ($this->cross_reference_presenters as $index => $cross_reference_presenter) {
            if ($cross_reference_presenter->id === $cross_ref_presenter_to_remove->id) {
                unset($this->cross_reference_presenters[$index]);
                break;
            }
        }

        $this->cross_reference_presenters = array_values($this->cross_reference_presenters);
    }

    public function moveCrossReferenceToSection(
        CrossReferencePresenter $cross_reference_presenter,
        string $section_label,
    ): void {
        foreach ($this->cross_reference_presenters as $key => $xref) {
            if ($xref->id !== $cross_reference_presenter->id) {
                continue;
            }

            unset($this->cross_reference_presenters[$key]);
            $this->addCrossReferenceToItsNature($cross_reference_presenter, $section_label);
        }

        $this->cross_reference_presenters = array_values($this->cross_reference_presenters);
    }

    private function doWeAlreadyHaveNaturePresenter(string $nature_identifier): bool
    {
        return isset($this->natures[$nature_identifier]);
    }

    private function addCrossReferencePresenterToExistingNaturePresenter(
        string $nature_identifier,
        CrossReferencePresenter $cross_reference_presenter,
        string $section_label,
    ): void {
        $this->setNature(
            $nature_identifier,
            $this->natures[$nature_identifier]->withAdditionalCrossReferencePresenter(
                $section_label,
                $cross_reference_presenter
            )
        );
    }

    private function addCrossReferencePresenterToNewNaturePresenter(
        string $nature_identifier,
        CrossReferencePresenter $cross_reference_presenter,
        string $section_label,
    ): void {
        $available_nature = $this->available_nature_collection->getNatureFromIdentifier($nature_identifier);

        if (! $available_nature) {
            return;
        }

        $this->setNature(
            $nature_identifier,
            new CrossReferenceNaturePresenter(
                $available_nature->label,
                $available_nature->icon,
                [
                    new CrossReferenceSectionPresenter(
                        $section_label,
                        [$cross_reference_presenter]
                    ),
                ]
            )
        );
    }

    private function setNature(string $nature_identifier, CrossReferenceNaturePresenter $nature_presenter): void
    {
        $this->natures[$nature_identifier] = $nature_presenter;
    }

    /**
     * @return CrossReferenceNaturePresenter[]
     */
    public function getNatures(): array
    {
        return array_values($this->natures);
    }

    public function organizeRemainingCrossReferences(): void
    {
        foreach ($this->cross_reference_presenters as $key => $cross_reference_presenter) {
            unset($this->cross_reference_presenters[$key]);
            $this->addCrossReferenceToItsNature($cross_reference_presenter, CrossReferenceSectionPresenter::UNLABELLED);
        }
    }

    public function getCurrentUser(): \PFUser
    {
        return $this->current_user;
    }

    private function addCrossReferenceToItsNature(
        CrossReferencePresenter $cross_reference_presenter,
        string $section_label,
    ): void {
        $project = $this->project_manager->getProject($cross_reference_presenter->target_gid);
        try {
            $this->project_access_checker->checkUserCanAccessProject($this->getCurrentUser(), $project);
        } catch (\Project_AccessException $e) {
            return;
        }

        $nature_identifier = $cross_reference_presenter->type;
        if ($this->doWeAlreadyHaveNaturePresenter($nature_identifier)) {
            $this->addCrossReferencePresenterToExistingNaturePresenter(
                $nature_identifier,
                $cross_reference_presenter,
                $section_label
            );
        } else {
            $this->addCrossReferencePresenterToNewNaturePresenter(
                $nature_identifier,
                $cross_reference_presenter,
                $section_label
            );
        }
    }
}
