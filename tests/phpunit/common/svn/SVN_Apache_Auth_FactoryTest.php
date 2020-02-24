<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SVN_Apache_Auth_FactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $event_manager;
    private $cache_parameters;
    private $factory;
    private $project_info;

    /**
     * @var SVN_Apache
     */
    private $my_svn_apache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->my_svn_apache  = new class ([]) extends SVN_Apache {
            protected function getProjectAuthentication($row)
            {
            }
        };

        $this->event_manager                    = \Mockery::mock(\EventManager::class);
        $this->event_manager_with_plugin_answer = new class($this->my_svn_apache) extends EventManager
        {
            /**
             * @var SVN_Apache
             */
            private $given_svn_apache;

            public function __construct(SVN_Apache $given_svn_apache)
            {
                $this->given_svn_apache = $given_svn_apache;
            }

            public function processEvent($event_name, $params = [])
            {
                $params['svn_apache_auth'] = $this->given_svn_apache;
            }
        };

        $this->cache_parameters = \Mockery::spy(\Tuleap\SvnCore\Cache\Parameters::class);

        $this->factory = new SVN_Apache_Auth_Factory(
            $this->event_manager,
            $this->cache_parameters
        );

        $this->factory_with_plugin_answer = new SVN_Apache_Auth_Factory(
            $this->event_manager_with_plugin_answer,
            $this->cache_parameters
        );

        $this->project_info = array();
    }

    public function testItReturnsModPerlByDefault(): void
    {
        $this->event_manager->shouldReceive('processEvent');
        $this->assertInstanceOf('SVN_Apache_ModPerl', $this->factory->get($this->project_info));
    }

    public function testItReturnModPluginIfPluginAuthIsConfiguredForThisProject(): void
    {
        $mod = $this->factory_with_plugin_answer->get($this->project_info);

        $this->assertSame($this->my_svn_apache, $mod);
    }
}
