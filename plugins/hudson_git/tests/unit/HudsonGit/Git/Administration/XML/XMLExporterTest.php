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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\HudsonGit\Git\Administration\JenkinsServer;
use Tuleap\HudsonGit\Git\Administration\JenkinsServerFactory;

final class XMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var XMLExporter
     */
    private $exporter;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|JenkinsServerFactory
     */
    private $jenkins_server_factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    private EncryptionKey $encryption_key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jenkins_server_factory = Mockery::mock(JenkinsServerFactory::class);
        $this->logger                 = Mockery::mock(LoggerInterface::class);
        $this->encryption_key         = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));

        $this->exporter = new XMLExporter(
            $this->jenkins_server_factory,
            $this->logger,
            $this->encryption_key,
        );

        $this->project = Mockery::mock(Project::class);
    }

    public function testItExportsProjectJenkinsServer(): void
    {
        $this->jenkins_server_factory->shouldReceive('getJenkinsServerOfProject')
            ->once()
            ->andReturn([
                new JenkinsServer(1, ('https://url'), null, $this->project),
                new JenkinsServer(2, ('https://url2'), SymmetricCrypto::encrypt(new ConcealedString('my_token'), $this->encryption_key), $this->project),
            ]);

        $xml_git = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <git/>
        ');

        $this->logger->shouldReceive('info');

        $this->exporter->export(
            $this->project,
            $xml_git
        );

        $this->assertTrue(isset($xml_git->{"jenkins-servers-admin"}));
        $this->assertCount(2, $xml_git->{"jenkins-servers-admin"}->children());

        $server_01 = $xml_git->{"jenkins-servers-admin"}->{"jenkins-server"}[0];
        $this->assertEquals('https://url', (string) $server_01['url']);

        $server_02 = $xml_git->{"jenkins-servers-admin"}->{"jenkins-server"}[1];
        $this->assertEquals('https://url2', (string) $server_02['url']);
        self::assertEquals('my_token', (string) $server_02['jenkins_token']);
    }

    public function testItDoesNotExportProjectJenkinsServerIfNoServerDefined(): void
    {
        $this->jenkins_server_factory->shouldReceive('getJenkinsServerOfProject')
            ->once()
            ->andReturn([]);

        $xml_git = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <git/>
        ');

        $this->exporter->export(
            $this->project,
            $xml_git
        );

        $this->assertFalse(isset($xml_git->{"jenkins-servers-admin"}));
    }
}
