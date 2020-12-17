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

namespace Tuleap\Reference;

use Psr\EventDispatcher\EventDispatcherInterface;

class CrossReferenceByDirectionPresenterBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var \ReferenceManager
     */
    private $reference_manager;
    /**
     * @var CrossReferencePresenterFactory
     */
    private $factory;

    public function __construct(
        EventDispatcherInterface $event_dispatcher,
        \ReferenceManager $reference_manager,
        CrossReferencePresenterFactory $factory
    ) {
        $this->event_dispatcher  = $event_dispatcher;
        $this->reference_manager = $reference_manager;
        $this->factory           = $factory;
    }

    public function build(
        string $entity_id,
        string $entity_type,
        int $entity_project_id,
        \PFUser $current_user
    ): CrossReferenceByDirectionPresenter {
        $available_natures = $this->reference_manager->getAvailableNatures();
        $source_presenters = $this->factory->getSourcesOfEntity($entity_id, $entity_type, $entity_project_id);
        $target_presenters = $this->factory->getTargetsOfEntity($entity_id, $entity_type, $entity_project_id);

        return new CrossReferenceByDirectionPresenter(
            $this->getNaturesFromCrossReferences($source_presenters, $available_natures, $current_user),
            $this->getNaturesFromCrossReferences($target_presenters, $available_natures, $current_user),
        );
    }

    /**
     * @param CrossReferencePresenter[] $cross_references
     * @param array<string, array{keyword: string, icon: string, label: string}> $available_natures
     *
     * @return CrossReferenceNaturePresenter[]
     */
    private function getNaturesFromCrossReferences(
        array $cross_references,
        array $available_natures,
        \PFUser $current_user
    ): array {
        $organizer = $this->event_dispatcher->dispatch(
            new CrossReferenceByNatureOrganizer($cross_references, $available_natures, $current_user)
        );
        assert($organizer instanceof CrossReferenceByNatureOrganizer);

        $organizer->organizeRemainingCrossReferences();

        return $organizer->getNatures();
    }
}
