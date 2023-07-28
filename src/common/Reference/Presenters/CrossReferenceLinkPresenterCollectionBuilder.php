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

declare(strict_types=1);

namespace Tuleap\Reference\Presenters;

use Tuleap\Reference\CrossReference;

class CrossReferenceLinkPresenterCollectionBuilder
{
    /**
     * @param CrossReference[] $cross_references
     * @return CrossReferenceLinkPresenter[]
     */
    public function build(
        array $cross_references,
        string $key,
        bool $display_params,
    ): array {
        $cross_ref_link_collection = [];

        foreach ($cross_references as $index => $current_cross_ref) {
            if ($key === 'source') {
                $id  = $current_cross_ref->getRefSourceKey() . "_" . $current_cross_ref->getRefSourceId();
                $ref = $current_cross_ref->getRefSourceKey() . " #" . $current_cross_ref->getRefSourceId();
                $url = $current_cross_ref->getRefSourceUrl();
            } else {
                $id  = $current_cross_ref->getRefTargetKey() . "_" . $current_cross_ref->getRefTargetId();
                $ref = $current_cross_ref->getRefTargetKey() . " #" . $current_cross_ref->getRefTargetId();
                $url = $current_cross_ref->getRefTargetUrl();
            }

            $cross_ref_link_collection[] = new CrossReferenceLinkPresenter(
                $id,
                $ref,
                $url,
                $this->getParams($current_cross_ref, $display_params),
                $this->displayComma($cross_references, $index)
            );
        }

        return $cross_ref_link_collection;
    }

    private function getParams(CrossReference $currRef, bool $display_params): ?string
    {
        if (! $display_params) {
            return null;
        }

        $params  = "?target_id=" . $currRef->getRefTargetId();
        $params .= "&target_gid=" . $currRef->getRefTargetGid();
        $params .= "&target_type=" . $currRef->getRefTargetType();
        $params .= "&target_key=" . $currRef->getRefTargetKey();
        $params .= "&source_id=" . $currRef->getRefSourceId();
        $params .= "&source_gid=" . $currRef->getRefSourceGid();
        $params .= "&source_type=" . $currRef->getRefSourceType();
        $params .= "&source_key=" . $currRef->getRefSourceKey();
        return $params;
    }

    /**
     * @param CrossReference[] $cross_references
     */
    private function displayComma(array $cross_references, int $index): bool
    {
        return count($cross_references) > 1 && $index < count($cross_references) - 1;
    }
}
