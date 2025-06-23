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
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\FieldFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Metadata\MetadataFromOrderBuilder;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\OrderByBuilderParameters;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderBy;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SelectableVisitor;
use Tuleap\Tracker\Tracker;

/**
 * @template-implements SelectableVisitor<OrderByBuilderParameters, ParametrizedFromOrder>
 */
final readonly class OrderByBuilderVisitor implements SelectableVisitor
{
    public function __construct(
        private FieldFromOrderBuilder $field_builder,
        private MetadataFromOrderBuilder $metadata_builder,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function buildFromOrder(
        ?OrderBy $order_by,
        array $trackers,
        PFUser $user,
    ): ParametrizedFromOrder {
        if ($order_by === null) {
            return new ParametrizedFromOrder('', [], '');
        }

        return $order_by->getFilter()->acceptSelectableVisitor($this, new OrderByBuilderParameters($order_by->getDirection(), $user, $trackers));
    }

    public function visitField(Field $field, $parameters): ParametrizedFromOrder
    {
        return $this->field_builder->getFromOrder($field, $parameters);
    }

    public function visitMetaData(Metadata $metadata, $parameters): ParametrizedFromOrder
    {
        return $this->metadata_builder->getFromOrder($metadata, $parameters);
    }
}
