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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

namespace Tuleap\reference;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\reference\Events\CrossReferenceGetNatureIconEvent;

class CrossReferenceByDirectionCollection
{
    /**
     * @var CrossReferenceCollection[]
     */
    private $cross_ref_target = [];

    /**
     * @var CrossReferenceCollection[]
     */
    private $cross_ref_source = [];

    /**
     * @psalm-param array<string, array{both?: \CrossReference[], target?: \CrossReference[], source?: \CrossReference[]}> $cross_reference_by_nature
     * @psalm-param array<string, array{label: string}> $available_natures
     */
    public function __construct(array $cross_reference_by_nature, array $available_natures, EventDispatcherInterface $event_manager)
    {
        foreach ($cross_reference_by_nature as $nature => $cross_reference_by_key) {
            $reference_get_icon_nature_event = new CrossReferenceGetNatureIconEvent($nature);
            $event_manager->dispatch($reference_get_icon_nature_event);

            if (isset($cross_reference_by_key['both'])) {
                $this->cross_ref_source[$nature] = $this->getSourceCrossReference(
                    $nature,
                    $available_natures,
                    $reference_get_icon_nature_event->getCrossReferencesNatureIcon(),
                    $this->transformAllBothCrossRefInSourceCrossReference($cross_reference_by_key['both'])
                );

                $this->cross_ref_target[$nature] = $this->getTargetCrossReference(
                    $nature,
                    $available_natures,
                    $reference_get_icon_nature_event->getCrossReferencesNatureIcon(),
                    $cross_reference_by_key['both']
                );
            }

            if (isset($cross_reference_by_key['target'])) {
                $this->cross_ref_target[$nature] = $this->getTargetCrossReference(
                    $nature,
                    $available_natures,
                    $reference_get_icon_nature_event->getCrossReferencesNatureIcon(),
                    $cross_reference_by_key['target']
                );
            }

            if (isset($cross_reference_by_key['source'])) {
                $this->cross_ref_source[$nature] = $this->getSourceCrossReference(
                    $nature,
                    $available_natures,
                    $reference_get_icon_nature_event->getCrossReferencesNatureIcon(),
                    $cross_reference_by_key['source']
                );
            }
        }
    }

    /**
     * @return CrossReferenceCollection[]
     */
    public function getAllCrossReferencesSource(): array
    {
        return $this->cross_ref_source;
    }

    /**
     * @return CrossReferenceCollection[]
     */
    public function getAllCrossReferencesTarget(): array
    {
        return $this->cross_ref_target;
    }

    /**
     * @param \CrossReference[] $both_cross_references
     * @return \CrossReference[]
     */
    public function transformAllBothCrossRefInSourceCrossReference(array $both_cross_references): array
    {
        $source_cross_references = [];

        foreach ($both_cross_references as $both_cross_ref) {
            $source_cross_references[] = new \CrossReference(
                $both_cross_ref->getRefTargetId(),
                $both_cross_ref->getRefTargetGid(),
                $both_cross_ref->getRefTargetType(),
                $both_cross_ref->getRefTargetKey(),
                $both_cross_ref->getRefSourceId(),
                $both_cross_ref->getRefSourceGid(),
                $both_cross_ref->getRefSourceType(),
                $both_cross_ref->getRefSourceKey(),
                $both_cross_ref->getUserId()
            );
        }
        return $source_cross_references;
    }

    /**
     * @psalm-param array<string, array{label: string}> $available_natures
     * @param \CrossReference[] $cross_references
     */
    public function getSourceCrossReference(
        string $nature,
        array $available_natures,
        ?CrossReferenceNatureIcon $icon,
        array $cross_references
    ): CrossReferenceCollection {
        if (isset($this->cross_ref_source[$nature])) {
            return new CrossReferenceCollection(
                $nature,
                $available_natures[$nature]['label'],
                [],
                [],
                array_merge(
                    $this->cross_ref_source[$nature]->getCrossReferencesSource(),
                    $cross_references
                ),
                $icon
            );
        }

        return new CrossReferenceCollection(
            $nature,
            $available_natures[$nature]['label'],
            [],
            [],
            $cross_references,
            $icon
        );
    }

    /**
     * @psalm-param array<string, array{label: string}> $available_natures
     * @param \CrossReference[] $cross_references
     */
    public function getTargetCrossReference(
        string $nature,
        array $available_natures,
        ?CrossReferenceNatureIcon $icon,
        array $cross_references
    ): CrossReferenceCollection {
        if (isset($this->cross_ref_target[$nature])) {
            return new CrossReferenceCollection(
                $nature,
                $available_natures[$nature]['label'],
                [],
                array_merge(
                    $this->cross_ref_target[$nature]->getCrossReferencesTarget(),
                    $cross_references
                ),
                [],
                $icon
            );
        }

        return new CrossReferenceCollection(
            $nature,
            $available_natures[$nature]['label'],
            [],
            $cross_references,
            [],
            $icon
        );
    }
}
