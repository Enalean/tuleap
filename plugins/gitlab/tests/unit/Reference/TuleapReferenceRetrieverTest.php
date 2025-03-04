<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference;

use EventManager;
use ReferenceManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TuleapReferenceRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&EventManager
     */
    private $event_manager;

    protected function setUp(): void
    {
        $this->reference_manager          = $this->createMock(ReferenceManager::class);
        $this->event_manager              = $this->createMock(EventManager::class);
        $this->tuleap_reference_retriever = new TuleapReferenceRetriever(
            $this->event_manager,
            $this->reference_manager
        );
    }

    public function testItThrowsAnErrorWhenTheArtifactCantBeFound(): void
    {
        $this->expectException(TuleapReferencedArtifactNotFoundException::class);
        $this->event_manager
            ->expects(self::once())
            ->method('processEvent')
            ->with(
                'get_artifact_reference_group_id',
                $this->callback(function (array $params) {
                    $params['artifact_id'] = 100;
                    $params['group_id']    = null;
                    return true;
                })
            );

        $this->tuleap_reference_retriever->retrieveTuleapReference(100);
    }

    public function testItThrowsAnErrorWhenTheArtifactReferenceCantBeFound(): void
    {
        $this->expectException(TuleapReferenceNotFoundException::class);
        $this->event_manager
            ->expects(self::once())
            ->method('processEvent')
            ->with(
                'get_artifact_reference_group_id',
                $this->callback(function (array $params) {
                    $params['artifact_id'] = 100;
                    $params['group_id']    = '101';
                    return true;
                })
            );

        $this->reference_manager
            ->method('loadReferenceFromKeyword')
            ->with('art', 100)
            ->willReturn(null);

        $this->tuleap_reference_retriever->retrieveTuleapReference(100);
    }

    public function testItReturnsTheReference(): void
    {
        $this->event_manager
            ->expects(self::once())
            ->method('processEvent')
            ->with(
                'get_artifact_reference_group_id',
                $this->callback(function (array $params) {
                    $params['artifact_id'] = 100;
                    $params['group_id']    = '102';
                    return true;
                })
            );

        $reference = new \Reference(
            0,
            'key',
            'desc',
            'link',
            'P',
            'service_short_name',
            'nature',
            1,
            101
        );
        $this->reference_manager
            ->method('loadReferenceFromKeyword')
            ->with('art', 100)
            ->willReturn($reference);

        $retrieved_reference = $this->tuleap_reference_retriever->retrieveTuleapReference(100);

        self::assertSame(0, $retrieved_reference->getId());
        self::assertSame(102, $retrieved_reference->getGroupId());
        self::assertSame('key', $retrieved_reference->getKeyword());
        self::assertSame('desc', $retrieved_reference->getDescription());
        self::assertSame('link', $retrieved_reference->getLink());
        self::assertSame('service_short_name', $retrieved_reference->getServiceShortName());
        self::assertSame('nature', $retrieved_reference->getNature());
    }
}
