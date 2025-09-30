<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\List\SelectboxField;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatusFactory;
use Tuleap\Tracker\Tracker;

/**
 * Retrieves the semantic status field of the given artifact
 */
class Cardwall_FieldProviders_SemanticStatusFieldRetriever implements Cardwall_FieldProviders_IProvideFieldGivenAnArtifact // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    /**
     * Retrieves the semantic status field of the given artifact
     *
     * @return SelectboxField | null
     */
    #[\Override]
    public function getField(Tracker $tracker)
    {
        $field = TrackerSemanticStatusFactory::instance()->getByTracker($tracker)->getField();
        assert(
            $field instanceof SelectboxField ||
            $field === null
        );

        return $field;
    }
}
