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

use ReferenceManager;
use Tuleap\Reference\GetProjectIdForSystemReferenceEvent;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class TuleapReferenceRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsAnErrorWhenTheArtifactCantBeFound(): void
    {
        $this->expectException(TuleapReferencedArtifactNotFoundException::class);

        $tuleap_reference_retriever = new TuleapReferenceRetriever(
            EventDispatcherStub::withIdentityCallback(),
            $this->createMock(ReferenceManager::class)
        );
        $tuleap_reference_retriever->retrieveTuleapReference(100);
    }

    public function testItThrowsAnErrorWhenTheArtifactReferenceCantBeFound(): void
    {
        $this->expectException(TuleapReferenceNotFoundException::class);

        $reference_manager = $this->createMock(ReferenceManager::class);
        $reference_manager
            ->method('loadReferenceFromKeyword')
            ->with('art', 100)
            ->willReturn(null);

        $tuleap_reference_retriever = new TuleapReferenceRetriever(
            EventDispatcherStub::withCallback(static function (object $event): object {
                if ($event instanceof GetProjectIdForSystemReferenceEvent) {
                    $event->setProjectId(101);
                }

                return $event;
            }),
            $reference_manager
        );
        $tuleap_reference_retriever->retrieveTuleapReference(100);
    }

    public function testItReturnsTheReference(): void
    {
        $reference         = new \Reference(
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
        $reference_manager = $this->createMock(ReferenceManager::class);
        $reference_manager
            ->method('loadReferenceFromKeyword')
            ->with('art', 100)
            ->willReturn($reference);

        $tuleap_reference_retriever = new TuleapReferenceRetriever(
            EventDispatcherStub::withCallback(static function (object $event): object {
                if ($event instanceof GetProjectIdForSystemReferenceEvent) {
                    $event->setProjectId(102);
                }

                return $event;
            }),
            $reference_manager
        );
        $retrieved_reference        = $tuleap_reference_retriever->retrieveTuleapReference(100);

        self::assertSame(0, $retrieved_reference->getId());
        self::assertSame(102, $retrieved_reference->getGroupId());
        self::assertSame('key', $retrieved_reference->getKeyword());
        self::assertSame('desc', $retrieved_reference->getDescription());
        self::assertSame('link', $retrieved_reference->getLink());
        self::assertSame('service_short_name', $retrieved_reference->getServiceShortName());
        self::assertSame('nature', $retrieved_reference->getNature());
    }
}
