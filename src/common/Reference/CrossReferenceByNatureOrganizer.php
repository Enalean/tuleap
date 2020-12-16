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

use Tuleap\Event\Dispatchable;

class CrossReferenceByNatureOrganizer implements Dispatchable
{
    public const NAME = "crossReferenceByNatureOrganizer";

    /**
     * @var CrossReferencePresenter[]
     */
    private $cross_references;
    /**
     * @var array<string, CrossReferenceNaturePresenter>
     */
    private $natures;
    /**
     * @var array<string, Nature>
     */
    private $available_natures;
    /**
     * @var \PFUser
     */
    private $current_user;

    /**
     * @param CrossReferencePresenter[] $cross_references
     * @param array<string, Nature> $available_natures
     */
    public function __construct(array $cross_references, array $available_natures, \PFUser $current_user)
    {
        $this->cross_references  = $cross_references;
        $this->available_natures = $available_natures;
        $this->current_user      = $current_user;

        $this->natures = [];
    }

    /**
     * @return CrossReferencePresenter[]
     */
    public function getCrossReferences(): array
    {
        return $this->cross_references;
    }

    public function removeUnreadableCrossReference(CrossReferencePresenter $cross_reference_to_remove): void
    {
        foreach ($this->cross_references as $index => $cross_reference) {
            if ($cross_reference === $cross_reference_to_remove) {
                unset($this->cross_references[$index]);
                break;
            }
        }

        $this->cross_references = array_values($this->cross_references);
    }

    public function moveCrossReferenceToSection(
        CrossReferencePresenter $cross_reference,
        string $section_label
    ): void {
        foreach ($this->cross_references as $key => $xref) {
            if ($xref !== $cross_reference) {
                continue;
            }

            unset($this->cross_references[$key]);

            $nature_identifier = $cross_reference->type;
            if ($this->doWeAlreadyHaveNaturePresenter($nature_identifier)) {
                $this->addCrossReferencePresenterToExistingNaturePresenter(
                    $nature_identifier,
                    $cross_reference,
                    $section_label
                );
            } else {
                $this->addCrossReferencePresenterToNewNaturePresenter(
                    $nature_identifier,
                    $cross_reference,
                    $section_label
                );
            }
        }

        $this->cross_references = array_values($this->cross_references);
    }

    private function doWeAlreadyHaveNaturePresenter(string $nature_identifier): bool
    {
        return isset($this->natures[$nature_identifier]);
    }

    private function addCrossReferencePresenterToExistingNaturePresenter(
        string $nature_identifier,
        CrossReferencePresenter $cross_reference_presenter,
        string $section_label
    ): void {
        $this->setNature(
            $nature_identifier,
            $this->natures[$nature_identifier]->withAdditionalCrossReference(
                $section_label,
                $cross_reference_presenter
            )
        );
    }

    private function addCrossReferencePresenterToNewNaturePresenter(
        string $nature_identifier,
        CrossReferencePresenter $cross_reference_presenter,
        string $section_label
    ): void {
        if (! isset($this->available_natures[$nature_identifier])) {
            return;
        }
        $available_nature = $this->available_natures[$nature_identifier];

        $this->setNature(
            $nature_identifier,
            new CrossReferenceNaturePresenter(
                $available_nature->label,
                $available_nature->icon,
                [
                    new CrossReferenceSectionPresenter(
                        $section_label,
                        [$cross_reference_presenter]
                    )
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
        foreach ($this->cross_references as $cross_reference) {
            $this->moveCrossReferenceToSection($cross_reference, '');
        }
    }

    public function getCurrentUser(): \PFUser
    {
        return $this->current_user;
    }
}
