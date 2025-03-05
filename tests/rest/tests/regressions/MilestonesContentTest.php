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
 * When you move an artifact from the release plan back to the product backlog and Submit the changes an error is generated
 *
 * @group Regressions
 */
#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class Regressions_MilestonesContentTest extends RestBase
{
    public function testItCanMoveBackFromReleaseBacklogToProductBacklog()
    {
        $releases = $this->getArtifactIdsIndexedByTitle('pbi-6348', 'releases');
        $epics    = $this->getArtifactIdsIndexedByTitle('pbi-6348', 'epic');
        $products = $this->getArtifactIdsIndexedByTitle('pbi-6348', 'product');

        $this->getResponse(
            $this->request_factory->createRequest('PUT', 'milestones/' . $releases['1.0'] . '/content')
                ->withBody($this->stream_factory->createStream(json_encode([$epics['One Epic']])))
        );

        $this->assertEquals($this->getMilestoneContentIds($releases['1.0']), [$epics['One Epic']]);
        $this->assertEquals($this->getMilestoneContentIds($products['Widget 2']), [$epics['One Epic'], $epics['Another Epic']]);
        $this->assertEquals($this->getMilestoneBacklogIds($products['Widget 2']), [$epics['Another Epic']]);
    }

    private function getMilestoneBacklogIds($id)
    {
        return $this->getIds("milestones/$id/backlog");
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
