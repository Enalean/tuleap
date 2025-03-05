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
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretChecker;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotFoundException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretHeaderNotMatchingException;
use Tuleap\Gitlab\Repository\Webhook\Secret\SecretRetriever;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SecretCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SecretRetriever
     */
    private $secret_retriever;

    private GitlabRepositoryIntegration $gitlab_repository_integration;
    private SecretChecker $secret_checker;

    protected function setUp(): void
    {
        $this->gitlab_repository_integration = new GitlabRepositoryIntegration(
            1,
            123456,
            'path/repo01',
            'description',
            'https://example.com/path/repo01',
            new DateTimeImmutable(),
            ProjectTestBuilder::aProject()->build(),
            false
        );

        $this->secret_retriever = $this->createMock(SecretRetriever::class);

        $this->secret_checker = new SecretChecker(
            $this->secret_retriever
        );
    }

    public function testItThrowsAnExceptionIfSecretHeaderNotFound(): void
    {
        $request = (new NullServerRequest())->withBody(
            HTTPFactoryBuilder::streamFactory()->createStream(
                '{"event_name": "push", "project":{"id": 123456, "web_url": "https://example.com/path/repo01"}}'
            )
        );

        $this->expectException(SecretHeaderNotFoundException::class);

        $this->secret_checker->checkSecret(
            $this->gitlab_repository_integration,
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

        $this->secret_retriever
            ->expects(self::once())
            ->method('getWebhookSecretForRepository')
            ->with($this->gitlab_repository_integration)
            ->willReturn(new ConcealedString('anotherSecret'));

        $this->expectException(SecretHeaderNotMatchingException::class);

        $this->secret_checker->checkSecret(
            $this->gitlab_repository_integration,
            $request
        );
    }

    public function testItDoesNotThrowExceptionIfAllChecksAreOK(): void
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

        $this->secret_retriever
            ->expects(self::once())
            ->method('getWebhookSecretForRepository')
            ->with($this->gitlab_repository_integration)
            ->willReturn(new ConcealedString('secret'));

        $this->secret_checker->checkSecret(
            $this->gitlab_repository_integration,
            $request
        );
    }
}
