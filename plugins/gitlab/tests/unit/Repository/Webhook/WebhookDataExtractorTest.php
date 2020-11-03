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

namespace Tuleap\Gitlab\Repository\Webhook;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\Repository\GitlabRepository;
use Tuleap\Gitlab\Repository\GitlabRepositoryFactory;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretChecker;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotFoundException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotMatchingException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretRetriever;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;

class WebhookDataExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GitlabRepository
     */
    private $gitlab_repository;

    /**
     * @var WebhookDataExtractor
     */
    private $data_extractor;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|GitlabRepositoryFactory
     */
    private $gitlab_repository_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SecretRetriever
     */
    private $secret_retriever;

    /**
     * @var SecretChecker
     */
    private $secret_checker;

    protected function setUp(): void
    {
        $this->gitlab_repository = new GitlabRepository(
            1,
            123456,
            'repo01',
            'path/repo01',
            'description',
            'https://example.com/path/repo01',
            new DateTimeImmutable()
        );

        $this->gitlab_repository_factory = Mockery::mock(GitlabRepositoryFactory::class);
        $this->secret_retriever          = Mockery::mock(SecretRetriever::class);
        $this->secret_checker            = new SecretChecker($this->secret_retriever);

        $this->data_extractor = new WebhookDataExtractor(
            $this->gitlab_repository_factory,
            $this->secret_checker
        );
    }

    public function testItThrowsAnExceptionIfEventKeyIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream('{}')
        );

        $this->expectException(MissingKeyException::class);

        $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );
    }

    public function testItThrowsAnExceptionIfEventIsNotAPush(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "whatever"}'
            )
        );

        $this->expectException(EventNotAllowedException::class);

        $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectKeyIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push"}'
            )
        );

        $this->expectException(MissingKeyException::class);

        $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectIdKeyIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push", "project":{}}'
            )
        );

        $this->expectException(MissingKeyException::class);

        $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );
    }

    public function testItThrowsAnExceptionIfProjectHttpURLKeyIsMissing(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push", "project":{"id": 123456}}'
            )
        );

        $this->expectException(MissingKeyException::class);

        $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );
    }

    public function testItThrowsAnExceptionIfRepositoryNotFound(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push", "project":{"id": 123456, "web_url": "https://example.com/path/repo01"}}'
            )
        );

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByInternalIdandPath')
            ->once()
            ->with(123456, 'https://example.com/path/repo01')
            ->andReturnNull();

        $this->expectException(RepositoryNotFoundException::class);

        $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );
    }

    public function testItThrowsAnExceptionIfSecretHeaderNotFound(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push", "project":{"id": 123456, "web_url": "https://example.com/path/repo01"}}'
            )
        );

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByInternalIdandPath')
            ->once()
            ->with(123456, 'https://example.com/path/repo01')
            ->andReturn($this->gitlab_repository);

        $this->expectException(SecretHeaderNotFoundException::class);

        $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );
    }

    public function testItThrowsAnExceptionIfSecretHeaderNotMatching(): void
    {
        $request = (new NullServerRequest())
            ->withBody(
                HTTPFactoryBuilder::streamFactory()->createStream(
                    '{"event_name": "push", "project":{"id": 123456, "web_url": "https://example.com/path/repo01"}}'
                )
            )
            ->withHeader(
                'X-Gitlab-Token',
                'secret'
            );

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByInternalIdandPath')
            ->once()
            ->with(123456, 'https://example.com/path/repo01')
            ->andReturn($this->gitlab_repository);

        $this->secret_retriever->shouldReceive('getWebhookSecretForRepository')
            ->once()
            ->with($this->gitlab_repository)
            ->andReturn(new ConcealedString('anotherSecret'));

        $this->expectException(SecretHeaderNotMatchingException::class);

        $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );
    }

    public function testItReturnsTheGitlabRepository(): void
    {
        $request = (new NullServerRequest())
            ->withBody(
                HTTPFactoryBuilder::streamFactory()->createStream(
                    '{"event_name": "push", "project":{"id": 123456, "web_url": "https://example.com/path/repo01"}}'
                )
            )
            ->withHeader(
                'X-Gitlab-Token',
                'secret'
            );

        $this->gitlab_repository_factory->shouldReceive('getGitlabRepositoryByInternalIdandPath')
            ->once()
            ->with(123456, 'https://example.com/path/repo01')
            ->andReturn($this->gitlab_repository);

        $this->secret_retriever->shouldReceive('getWebhookSecretForRepository')
            ->once()
            ->with($this->gitlab_repository)
            ->andReturn(new ConcealedString('secret'));

        $repository = $this->data_extractor->retrieveRepositoryFromWebhookContent(
            $request
        );

        $this->assertSame(
            $this->gitlab_repository,
            $repository
        );
    }
}
