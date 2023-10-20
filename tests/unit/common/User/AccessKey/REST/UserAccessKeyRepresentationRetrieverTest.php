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

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\REST\AccessKeyHeaderExtractor;
use Tuleap\User\AccessKey\AccessKeyMetadata;
use Tuleap\User\AccessKey\AccessKeyMetadataRetriever;

final class UserAccessKeyRepresentationRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \\PHPUnit\Framework\MockObject\MockObject&AccessKeyHeaderExtractor
     */
    private $access_key_header_extractor;
    /**
     * @var \\PHPUnit\Framework\MockObject\MockObject&AccessKeyMetadataRetriever
     */
    private $metadata_retriever;

    /**
     * @var UserAccessKeyRepresentationRetriever
     */
    private $representation_retriever;

    protected function setUp(): void
    {
        $this->access_key_header_extractor = $this->createMock(AccessKeyHeaderExtractor::class);
        $this->metadata_retriever          = $this->createMock(AccessKeyMetadataRetriever::class);

        $this->representation_retriever = new UserAccessKeyRepresentationRetriever(
            $this->access_key_header_extractor,
            $this->metadata_retriever
        );
    }

    public function testFindRepresentationFromSelfID(): void
    {
        $access_key = new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString());
        $this->access_key_header_extractor->method('extractAccessKey')->willReturn($access_key);

        $this->metadata_retriever->method('getMetadataByUser')->willReturn(
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

        self::assertNotNull(
            $this->representation_retriever->getByUserAndID(
                $this->createMock(\PFUser::class),
                'self'
            )
        );
    }

    public function testFindRepresentationFromID(): void
    {
        $this->metadata_retriever->method('getMetadataByUser')->willReturn(
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

        self::assertNotNull(
            $this->representation_retriever->getByUserAndID(
                $this->createMock(\PFUser::class),
                '13'
            )
        );
    }

    public function testCannotFindRepresentationFromSelfIDWhenUserIsNotAuthenticatedWithAnAccessKey(): void
    {
        $this->access_key_header_extractor->method('extractAccessKey')->willReturn(null);

        self::assertNull(
            $this->representation_retriever->getByUserAndID(
                $this->createMock(\PFUser::class),
                'self'
            )
        );
    }

    public function testCannotFindRepresentationWhenKeyIDDoesNotExistForTheUser(): void
    {
        $this->metadata_retriever->method('getMetadataByUser')->willReturn(
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

        self::assertNull(
            $this->representation_retriever->getByUserAndID(
                $this->createMock(\PFUser::class),
                '404'
            )
        );
    }

    public function testCannotFindRepresentationWhenGivenIDParameterIsNotSelfStringOrAStringLookingLikeAnInteger(): void
    {
        self::assertNull(
            $this->representation_retriever->getByUserAndID(
                $this->createMock(\PFUser::class),
                'foo'
            )
        );
    }
}
