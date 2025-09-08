<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced;

use PFUser;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\FieldTypeIsNotSupportedFault;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Selectable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SelectableVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidSelectablesCollection;
use Tuleap\Tracker\Tracker;

/**
 * @template-implements SelectableVisitor<InvalidSelectableCollectorParameters, void>
 */
final readonly class InvalidSelectablesCollectorVisitor implements SelectableVisitor
{
    public function __construct(
        private DuckTypedFieldChecker $field_checker,
        private MetadataChecker $metadata_checker,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function collectErrors(
        Selectable $selectable,
        InvalidSelectablesCollection $invalid_selectables_collection,
        array $trackers,
        PFUser $user,
    ): void {
        $selectable->acceptSelectableVisitor(
            $this,
            new InvalidSelectableCollectorParameters($invalid_selectables_collection, $trackers, $user),
        );
    }

    /**
     * @param InvalidSelectableCollectorParameters $parameters
     */
    #[\Override]
    public function visitField(Field $field, $parameters): void
    {
        $this->field_checker->checkFieldIsValidForSelect($field, $parameters)
            ->match(
                static function () {
                    // Do nothing
                },
                static function (Fault $fault) use ($field, $parameters) {
                    if ($fault instanceof FieldTypeIsNotSupportedFault) {
                        $parameters->invalid_selectables_collection->addNonExistentSelectable($field->getName());
                        return;
                    }
                    $parameters->invalid_selectables_collection->addInvalidSelectableError((string) $fault);
                }
            );
    }

    #[\Override]
    public function visitMetaData(Metadata $metadata, $parameters): void
    {
        try {
            $this->metadata_checker->checkMetadataIsValidForSelect($metadata, $parameters);
        } catch (InvalidQueryException $exception) {
            $parameters->invalid_selectables_collection->addInvalidSelectableError($exception->getMessage());
        }
    }
}
