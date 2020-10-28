<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tuleap\Tracker\XML\Importer\TrackerImporterUser;

final class JiraAuthorRetrieverTest extends TestCase
{
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LoggerInterface
     */
    private $logger;

    /**
     * @var JiraAuthorRetriever
     */
    private $retriever;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $forge_user;

    /**
     * @var JiraUserOnTuleapCache
     */
    private $user_cache;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|JiraUserInfoQuerier
     */
    private $info_querier;

    protected function setUp(): void
    {
        $this->forge_user = \Mockery::mock(\PFUser::class);
        $this->forge_user->shouldReceive('getRealName')->andReturn("Tracker Importer (forge__tracker_importer_user)");

        $this->user_manager = \Mockery::mock(\UserManager::class);
        $this->logger       = \Mockery::mock(LoggerInterface::class);
        $this->user_cache   = \Mockery::mock(JiraUserOnTuleapCache::class);
        $this->info_querier = \Mockery::mock(JiraUserInfoQuerier::class);
        $this->retriever    = new JiraAuthorRetriever(
            $this->logger,
            $this->user_manager,
            $this->user_cache,
            $this->info_querier,
            $this->forge_user
        );

        $this->logger->shouldReceive('debug');
    }

    protected function tearDown(): void
    {
        \Mockery::close();
    }

    public function testItReturnsTheTuleapUserIfEmailAddressMatchesOnce(): void
    {
        $tuleap_user = \Mockery::mock(\PFUser::class);
        $tuleap_user->shouldReceive('getRealName')->andReturn("John Doe");
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('johndoe@example.com')->andReturn([$tuleap_user]);

        $this->user_cache->shouldReceive('isUserCached')->andReturn(false);
        $this->user_cache->shouldReceive('cacheUser')->with($tuleap_user, \Mockery::any());

        $submitter = $this->retriever->retrieveArtifactSubmitter(
            IssueAPIRepresentation::buildFromAPIResponse([
                'id'  => '10042',
                'key' => 'key01',
                'renderedFields' => [],
                'fields' => [
                    'creator' => [
                        'accountId' => '5e8dss456a2d45f3',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'johndoe@example.com'
                    ]
                ],
            ])
        );

        $this->assertEquals("John Doe", $submitter->getRealName());
    }

    public function testItReturnsForgeUserWhenMoreThanOneEmailAddressMatchesOnTuleapSide(): void
    {
        $tuleap_user = \Mockery::mock(\PFUser::class);
        $tuleap_user->shouldReceive('getRealName')->andReturn("John Doe");
        $this->user_manager->shouldReceive('getAllUsersByEmail')
            ->with('johndoe@example.com')
            ->andReturn([
                $tuleap_user,
                \Mockery::mock(\PFUser::class),
                \Mockery::mock(\PFUser::class)
            ]);

        $this->user_cache->shouldReceive('isUserCached')->andReturn(false);
        $this->user_cache->shouldReceive('cacheUser')->with($this->forge_user, \Mockery::any());

        $submitter = $this->retriever->retrieveArtifactSubmitter(
            IssueAPIRepresentation::buildFromAPIResponse([
                'id'     => '10042',
                'key' => 'key01',
                'renderedFields' => [],
                'fields' => [
                    'creator' => [
                        'accountId' => '5e8dss456a2d45f3',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'johndoe@example.com'
                    ]
                ],
            ])
        );

        $this->assertEquals("Tracker Importer (forge__tracker_importer_user)", $submitter->getRealName());
    }

    public function testItReturnsForgeUserWhenNoEmailAddressMatchesOnTuleapSide(): void
    {
        $tuleap_user = \Mockery::mock(\PFUser::class);
        $tuleap_user->shouldReceive('getRealName')->andReturn("John Doe");
        $this->user_manager->shouldReceive('getAllUsersByEmail')
            ->with('johndoe@example.com')
            ->andReturn([]);

        $this->user_cache->shouldReceive('isUserCached')->andReturn(false);
        $this->user_cache->shouldReceive('cacheUser')->with($this->forge_user, \Mockery::any());

        $submitter = $this->retriever->retrieveArtifactSubmitter(
            IssueAPIRepresentation::buildFromAPIResponse([
                'id'     => '10042',
                'key' => 'key01',
                'renderedFields' => [],
                'fields' => [
                    'creator' => [
                        'accountId' => '5e8dss456a2d45f3',
                        'displayName' => 'John Doe',
                        'emailAddress' => 'johndoe@example.com'
                    ]
                ],
            ])
        );

        $this->assertEquals("Tracker Importer (forge__tracker_importer_user)", $submitter->getRealName());
    }

    public function testItReturnsForgeUserUserDoesNotShareHisEmailAddress(): void
    {
        $this->user_cache->shouldReceive('isUserCached')->andReturn(false);
        $this->user_cache->shouldReceive('cacheUser')->with($this->forge_user, \Mockery::any());

        $submitter = $this->retriever->retrieveArtifactSubmitter(
            IssueAPIRepresentation::buildFromAPIResponse([
                'id'     => '10042',
                'key' => 'key01',
                'renderedFields' => [],
                'fields' => [
                    'creator' => [
                        'accountId' => '5e8dss456a2d45f3',
                        'displayName' => 'John Doe',
                    ]
                ],
            ])
        );

        $this->assertEquals("Tracker Importer (forge__tracker_importer_user)", $submitter->getRealName());
    }

    public function testItDoesNotCallUserManagerWhenUserExistsInCache(): void
    {
        $this->user_cache->shouldReceive('isUserCached')->andReturn(true);
        $this->user_cache->shouldReceive('getUserFromCache')
            ->with(\Mockery::any())
            ->andReturn($this->forge_user);

        $this->user_manager->shouldReceive('getAllUsersByEmail')->never();
        $this->forge_user->shouldReceive('getId')->andReturn(TrackerImporterUser::ID);

        $submitter = $this->retriever->retrieveArtifactSubmitter(
            IssueAPIRepresentation::buildFromAPIResponse(
                [
                    'id'             => '10042',
                    'key'            => 'key01',
                    'renderedFields' => [],
                    'fields'         => [
                        'creator' => [
                            'accountId'   => '5e8dss456a2d45f3',
                            'displayName' => 'John Doe'
                        ]
                    ],
                ]
            )
        );

        $this->assertEquals("Tracker Importer (forge__tracker_importer_user)", $submitter->getRealName());
    }
}
