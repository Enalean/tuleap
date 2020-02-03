<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey\REST;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\REST\AccessKeyHeaderExtractor;
use Tuleap\User\AccessKey\AccessKeyMetadata;
use Tuleap\User\AccessKey\AccessKeyMetadataRetriever;

final class UserAccessKeyRepresentationRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessKeyHeaderExtractor
     */
    private $access_key_header_extractor;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AccessKeyMetadataRetriever
     */
    private $metadata_retriever;

    /**
     * @var UserAccessKeyRepresentationRetriever
     */
    private $representation_retriever;

    protected function setUp(): void
    {
        $this->access_key_header_extractor = \Mockery::mock(AccessKeyHeaderExtractor::class);
        $this->metadata_retriever          = \Mockery::mock(AccessKeyMetadataRetriever::class);

        $this->representation_retriever    = new UserAccessKeyRepresentationRetriever(
            $this->access_key_header_extractor,
            $this->metadata_retriever
        );
    }

    public function testFindRepresentationFromSelfID(): void
    {
        $access_key = new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString());
        $this->access_key_header_extractor->shouldReceive('extractAccessKey')->andReturn($access_key);

        $this->metadata_retriever->shouldReceive('getMetadataByUser')->andReturn(
            [
                new AccessKeyMetadata(
                    478,
                    new \DateTimeImmutable(),
                    'Description',
                    null,
                    null,
                    null,
                    []
                ),
                new AccessKeyMetadata(
                    12,
                    new \DateTimeImmutable(),
                    'Description',
                    null,
                    null,
                    null,
                    []
                ),
            ]
        );

        $this->assertNotNull(
            $this->representation_retriever->getByUserAndID(
                \Mockery::mock(\PFUser::class),
                'self'
            )
        );
    }

    public function testFindRepresentationFromID(): void
    {
        $this->metadata_retriever->shouldReceive('getMetadataByUser')->andReturn(
            [
                new AccessKeyMetadata(
                    13,
                    new \DateTimeImmutable(),
                    'Description',
                    null,
                    null,
                    null,
                    []
                ),
            ]
        );

        $this->assertNotNull(
            $this->representation_retriever->getByUserAndID(
                \Mockery::mock(\PFUser::class),
                '13'
            )
        );
    }

    public function testCannotFindRepresentationFromSelfIDWhenUserIsNotAuthenticatedWithAnAccessKey(): void
    {
        $this->access_key_header_extractor->shouldReceive('extractAccessKey')->andReturnNull();

        $this->assertNull(
            $this->representation_retriever->getByUserAndID(
                \Mockery::mock(\PFUser::class),
                'self'
            )
        );
    }

    public function testCannotFindRepresentationWhenKeyIDDoesNotExistForTheUser(): void
    {
        $this->metadata_retriever->shouldReceive('getMetadataByUser')->andReturn(
            [
                new AccessKeyMetadata(
                    14,
                    new \DateTimeImmutable(),
                    'Description',
                    null,
                    null,
                    null,
                    []
                ),
            ]
        );

        $this->assertNull(
            $this->representation_retriever->getByUserAndID(
                \Mockery::mock(\PFUser::class),
                '404'
            )
        );
    }

    public function testCannotFindRepresentationWhenGivenIDParameterIsNotSelfStringOrAStringLookingLikeAnInteger(): void
    {
        $this->assertNull(
            $this->representation_retriever->getByUserAndID(
                \Mockery::mock(\PFUser::class),
                'foo'
            )
        );
    }
}
