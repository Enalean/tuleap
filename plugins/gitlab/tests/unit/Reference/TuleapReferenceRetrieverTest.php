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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use ReferenceManager;

class TuleapReferenceRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReferenceManager
     */
    private $reference_manager;
    /**
     * @var EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;

    protected function setUp(): void
    {
        $this->reference_manager          = Mockery::mock(ReferenceManager::class);
        $this->event_manager              = Mockery::mock(EventManager::class);
        $this->tuleap_reference_retriever = new TuleapReferenceRetriever(
            $this->event_manager,
            $this->reference_manager
        );
    }

    public function testItThrowsAnErrorWhenTheArtifactCantBeFound(): void
    {
        $this->expectException(TuleapReferencedArtifactNotFoundException::class);
        $this->event_manager->shouldReceive('processEvent')
            ->once()
            ->with(
                'get_artifact_reference_group_id',
                Mockery::on(function (array &$params) {
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
        $this->event_manager->shouldReceive('processEvent')
            ->once()
            ->with(
                'get_artifact_reference_group_id',
                Mockery::on(function (array &$params) {
                    $params['artifact_id'] = 100;
                    $params['group_id']    = "101";
                    return true;
                })
            );

        $this->reference_manager->shouldReceive('loadReferenceFromKeyword')
            ->with('art', 100)
            ->andReturn(null);

        $this->tuleap_reference_retriever->retrieveTuleapReference(100);
    }

    public function testItReturnsTheReference(): void
    {
        $this->event_manager->shouldReceive('processEvent')
            ->once()
            ->with(
                'get_artifact_reference_group_id',
                Mockery::on(function (array &$params) {
                    $params['artifact_id'] = 100;
                    $params['group_id']    = "101";
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
        $this->reference_manager->shouldReceive('loadReferenceFromKeyword')
            ->with('art', 100)
            ->andReturn($reference);
        $retrieved_reference = $this->tuleap_reference_retriever->retrieveTuleapReference(100);

        self::assertSame($reference, $retrieved_reference);
    }
}
