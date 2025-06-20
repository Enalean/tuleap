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
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata\MetadataChecker;
use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderBy;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SelectableVisitor;
use Tuleap\Tracker\Report\Query\Advanced\IBuildInvalidOrderBy;
use Tuleap\Tracker\Report\Query\Advanced\InvalidOrderBy;
use Tuleap\Tracker\Tracker;

/**
 * @template-implements SelectableVisitor<InvalidOrderByBuilderParameters, ?InvalidOrderBy>
 */
final readonly class InvalidOrderByBuilder implements IBuildInvalidOrderBy, SelectableVisitor
{
    /**
     * @param Tracker[] $trackers
     */
    public function __construct(
        private DuckTypedFieldChecker $field_checker,
        private MetadataChecker $metadata_checker,
        private array $trackers,
        private PFUser $user,
    ) {
    }

    public function buildInvalidOrderBy(OrderBy $order_by): ?InvalidOrderBy
    {
        return $order_by->getFilter()->acceptSelectableVisitor($this, new InvalidOrderByBuilderParameters($this->trackers, $this->user));
    }

    public function visitField(Field $field, $parameters): ?InvalidOrderBy
    {
        return $this->field_checker->checkFieldIsValidForOrderBy($field, $parameters)
            ->match(
                static fn() => null,
                static fn(Fault $fault) => new InvalidOrderBy((string) $fault, (string) $fault),
            );
    }

    public function visitMetaData(Metadata $metadata, $parameters): ?InvalidOrderBy
    {
        try {
            $this->metadata_checker->checkMetadataIsValidForOrderBy($metadata, $parameters);
            return $parameters->getInvalidOrderBy();
        } catch (InvalidQueryException $exception) {
            return new InvalidOrderBy($exception->getMessage(), $exception->getMessage());
        }
    }
}
