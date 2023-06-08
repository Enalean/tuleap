<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Git\Administration\XML;

use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class XMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private XMLExporter $exporter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&JenkinsServerFactory
     */
    private $jenkins_server_factory;

    private Project $project;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&LoggerInterface
     */
    private $logger;
    private EncryptionKey $encryption_key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jenkins_server_factory = $this->createMock(JenkinsServerFactory::class);
        $this->logger                 = $this->createMock(LoggerInterface::class);
        $this->encryption_key         = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        $this->exporter = new XMLExporter(
            $this->jenkins_server_factory,
            $this->logger,
            $this->encryption_key,
        );

        $this->project = ProjectTestBuilder::aProject()->build();
    }

    public function testItExportsProjectJenkinsServer(): void
    {
        $this->jenkins_server_factory->expects(self::once())
            ->method('getJenkinsServerOfProject')
            ->willReturn([
                new JenkinsServer(1, ('https://url'), null, $this->project),
                new JenkinsServer(2, ('https://url2'), SymmetricCrypto::encrypt(new ConcealedString('my_token'), $this->encryption_key), $this->project),
            ]);

        $xml_git = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <git/>
        ');

        $this->logger->method('info');

        $this->exporter->export(
            $this->project,
            $xml_git
        );

        self::assertTrue(isset($xml_git->{"jenkins-servers-admin"}));
        self::assertCount(2, $xml_git->{"jenkins-servers-admin"}->children());

        $server_01 = $xml_git->{"jenkins-servers-admin"}->{"jenkins-server"}[0];
        self::assertEquals('https://url', (string) $server_01['url']);

        $server_02 = $xml_git->{"jenkins-servers-admin"}->{"jenkins-server"}[1];
        self::assertEquals('https://url2', (string) $server_02['url']);
        self::assertEquals('my_token', (string) $server_02['jenkins_token']);
    }

    public function testItDoesNotExportProjectJenkinsServerIfNoServerDefined(): void
    {
        $this->jenkins_server_factory->expects(self::once())
            ->method('getJenkinsServerOfProject')
            ->willReturn([]);

        $xml_git = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <git/>
        ');

        $this->exporter->export(
            $this->project,
            $xml_git
        );

        self::assertFalse(isset($xml_git->{"jenkins-servers-admin"}));
    }
}
