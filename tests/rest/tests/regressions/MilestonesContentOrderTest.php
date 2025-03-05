<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
 * PUT /milestones/123/content doesn't change the order of elements
 * I submit a change where only the order of the elements changes, there is no
 * addition or removal, and I receive 200 OK, but the order of the elements is
 * not modified.
 *
 * It looks like if there is an addition or removal, everything goes well.
 * The problem only arises when only the order of the elements changes.
 *
 * @see https://tuleap.net/plugins/tracker/?aid=6429
 * @group Regressions
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class Regressions_MilestonesContentOrderTest extends RestBase
{
    public function testItSetsTheContentOrder()
    {
        $epics    = $this->getArtifactIdsIndexedByTitle('pbi-6348', 'epic');
        $products = $this->getArtifactIdsIndexedByTitle('pbi-6348', 'product');

        $put = json_encode([$epics['Epic 1'], $epics['Epic 2'], $epics['Epic 3'], $epics['Epic 4']]);
        $this->getResponse(
            $this->request_factory->createRequest('PUT', 'milestones/' . $products['Widget 1'] . '/content')
                ->withBody($this->stream_factory->createStream($put))
        );
        $this->assertEquals($this->getMilestoneContentIds($products['Widget 1']), [$epics['Epic 1'], $epics['Epic 2'], $epics['Epic 3'], $epics['Epic 4']]);

        $put = json_encode([$epics['Epic 3'], $epics['Epic 1'], $epics['Epic 2'], $epics['Epic 4']]);
        $this->getResponse(
            $this->request_factory->createRequest('PUT', 'milestones/' . $products['Widget 1'] . '/content')
                ->withBody($this->stream_factory->createStream($put))
        );
        $this->assertEquals($this->getMilestoneContentIds($products['Widget 1']), [$epics['Epic 3'], $epics['Epic 1'], $epics['Epic 2'], $epics['Epic 4']]);
    }

    private function getMilestoneContentIds($id)
    {
        return $this->getIds("milestones/$id/content");
    }

    private function getIds($route)
    {
        $ids      = [];
        $response = $this->getResponse($this->request_factory->createRequest('GET', $route));
        $items    = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        foreach ($items as $item) {
            $ids[] = $item['id'];
        }
        return $ids;
    }
}
