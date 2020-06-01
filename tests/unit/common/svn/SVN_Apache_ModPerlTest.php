<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SVN_Apache_ModPerlTest extends TestCase
{
    use \Tuleap\ForgeConfigSandbox;

    /**
     * @var SVN_Apache_ModPerl
     */
    private $modperl;
    /**
     * @var array
     */
    private $gpig_project_row;

    protected function setUp(): void
    {
        $this->gpig_project_row = [
            'unix_group_name' => 'gpig',
            'public_path'     => '/svnroot/gpig',
            'system_path'     => '/svnroot/gpig',
            'group_name'      => 'Guinea Pig',
            'group_id'        => 101
        ];
        $this->modperl = new SVN_Apache_ModPerl(
            new \Tuleap\SvnCore\Cache\Parameters(50, 3600),
            $this->gpig_project_row,
        );
    }

    public function testGetSVNApacheConfHeadersShouldInsertModPerl(): void
    {
        $this->assertStringContainsString('PerlLoadModule Apache::Tuleap', $this->modperl->getHeaders());
    }

    public function testGetApacheAuthShouldContainsDefaultValues(): void
    {
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertMatchesRegularExpression('/Require valid-user/', $conf);
        $this->assertMatchesRegularExpression('/AuthType Basic/', $conf);
        $this->assertMatchesRegularExpression('/AuthName "Subversion Authorization \(Guinea Pig\)"/', $conf);
    }

    public function testGetApacheAuthShouldSetupPerlAccess(): void
    {
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertMatchesRegularExpression('/PerlAccessHandler/', $conf);
        $this->assertMatchesRegularExpression('/TuleapDSN/', $conf);
    }

    public function testGetApacheAuthShouldNotReferenceAuthMysql(): void
    {
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertDoesNotMatchRegularExpression('/AuthMYSQLEnable/', $conf);
    }

    public function testItShouldUseCacheParameters(): void
    {
        $apache_modperl          = new SVN_Apache_ModPerl(new \Tuleap\SvnCore\Cache\Parameters(877, 947), $this->gpig_project_row);
        $generated_configuration = $apache_modperl->getConf(
            $this->gpig_project_row['public_path'],
            $this->gpig_project_row['system_path']
        );

        $this->assertStringContainsString('TuleapCacheCredsMax 877', $generated_configuration);
        $this->assertStringContainsString('TuleapCacheLifetime 947', $generated_configuration);
    }

    public function testDSNWithClearTextDB(): void
    {
        ForgeConfig::set('sys_dbname', 'tuleap');
        ForgeConfig::set('sys_dbhost', 'db-server.example.com');
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringContainsString('TuleapDSN "DBI:mysql:tuleap:db-server.example.com"', $conf);

        $this->assertDoesNotMatchRegularExpression('/AuthMYSQLEnable/', $conf);
    }

    public function testDSNCertificateValidationIsAlwaysDisabledBecauseItDoesnWorkReliablyOnRHEL7(): void
    {
        $ca_bundle_path = vfsStream::setup()->url() . '/ca-bundle.pem';
        touch($ca_bundle_path);
        ForgeConfig::set('sys_dbname', 'tuleap');
        ForgeConfig::set('sys_dbhost', 'db-server.example.com');
        ForgeConfig::set('sys_enablessl', '1');
        ForgeConfig::set('sys_db_ssl_ca', $ca_bundle_path);
        ForgeConfig::set('sys_db_ssl_verify_cert', '1');

        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringContainsString('TuleapDSN "DBI:mysql:tuleap:db-server.example.com;mysql_ssl=1;mysql_ssl_ca_file=' . $ca_bundle_path . ';mysql_ssl_verify_server_cert=0"', $conf);
    }

    public function testDSNWithSSLDBWithoutCertificateValidation(): void
    {
        $ca_bundle_path = vfsStream::setup()->url() . '/ca-bundle.pem';
        touch($ca_bundle_path);
        ForgeConfig::set('sys_dbname', 'tuleap');
        ForgeConfig::set('sys_dbhost', 'db-server.example.com');
        ForgeConfig::set('sys_enablessl', '1');
        ForgeConfig::set('sys_db_ssl_ca', $ca_bundle_path);
        ForgeConfig::set('sys_db_ssl_verify_cert', '0');

        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringContainsString('TuleapDSN "DBI:mysql:tuleap:db-server.example.com;mysql_ssl=1;mysql_ssl_ca_file=' . $ca_bundle_path . ';mysql_ssl_verify_server_cert=0"', $conf);
    }

    public function testDSNWithCustomPort(): void
    {
        ForgeConfig::set('sys_dbname', 'tuleap');
        ForgeConfig::set('sys_dbhost', 'db-server.example.com');
        ForgeConfig::set('sys_dbport', 3307);
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringContainsString('TuleapDSN "DBI:mysql:tuleap:db-server.example.com;port=3307"', $conf);
    }

    public function testDSNWithCustomPortAndSSL(): void
    {
        $ca_bundle_path = vfsStream::setup()->url() . '/ca-bundle.pem';
        touch($ca_bundle_path);
        ForgeConfig::set('sys_dbname', 'tuleap');
        ForgeConfig::set('sys_dbhost', 'db-server.example.com');
        ForgeConfig::set('sys_dbport', 3307);
        ForgeConfig::set('sys_enablessl', '1');
        ForgeConfig::set('sys_db_ssl_ca', $ca_bundle_path);
        ForgeConfig::set('sys_db_ssl_verify_cert', '1');
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringContainsString('TuleapDSN "DBI:mysql:tuleap:db-server.example.com;port=3307;mysql_ssl=1;mysql_ssl_ca_file=' . $ca_bundle_path . ';mysql_ssl_verify_server_cert=0"', $conf);
    }

    public function testItDoesntHaveRedis(): void
    {
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringNotContainsString('TuleapRedisServer', $conf);
    }

    public function testItHasRedisCacheServerWithoutPassword(): void
    {
        ForgeConfig::set('redis_server', 'some-redis');
        ForgeConfig::set('redis_port', 3679);
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringContainsString('TuleapRedisServer "some-redis:3679"', $conf);
        $this->assertStringNotContainsString('TuleapRedisPassword', $conf);
    }

    public function testItHasRedisCacheServerWithPassword(): void
    {
        ForgeConfig::set('redis_server', 'some-redis');
        ForgeConfig::set('redis_port', 3679);
        ForgeConfig::set('redis_password', 'stuff');
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringContainsString('TuleapRedisServer "some-redis:3679"', $conf);
        $this->assertStringContainsString('TuleapRedisPassword "stuff"', $conf);
    }

    public function testItHasNoRedisCacheWhenRedisIsOverSSL(): void
    {
        ForgeConfig::set('redis_server', 'tls://some-redis');
        ForgeConfig::set('redis_port', 3679);
        ForgeConfig::set('redis_password', 'stuff');
        $conf = $this->modperl->getConf($this->gpig_project_row["public_path"], $this->gpig_project_row["system_path"]);

        $this->assertStringNotContainsString('TuleapRedisServer', $conf);
        $this->assertStringNotContainsString('TuleapRedisPassword', $conf);
    }
}
