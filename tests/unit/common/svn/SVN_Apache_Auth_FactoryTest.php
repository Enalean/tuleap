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

use PHPUnit\Framework\TestCase;
use Tuleap\SvnCore\Cache\Parameters;
use Tuleap\Test\Builders\ProjectTestBuilder;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SVN_Apache_Auth_FactoryTest extends TestCase
{
    public function testItReturnsModPerlByDefault(): void
    {
        $factory = new SVN_Apache_Auth_Factory(
            new EventManager(),
            new Parameters(15, 25)
        );

        self::assertInstanceOf(SVN_Apache_ModPerl::class, $factory->get(ProjectTestBuilder::aProject()->build()));
    }

    public function testItReturnModPluginIfPluginAuthIsConfiguredForThisProject(): void
    {
        $my_svn_apache  = new class extends SVN_Apache {
            protected function getProjectAuthentication(\Project $project): string
            {
            }
        };

        $plugin = new class ($my_svn_apache) extends Plugin {
            /**
             * @var SVN_Apache
             */
            private $svn_apache;

            public function __construct(SVN_Apache $svn_apache)
            {
                $this->svn_apache = $svn_apache;
            }

            public function svn_apache_auth(array $params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            {
                $params['svn_apache_auth'] = $this->svn_apache;
            }
        };

        $event_manager = new EventManager();
        $event_manager->addListener(Event::SVN_APACHE_AUTH, $plugin, Event::SVN_APACHE_AUTH, false);

        $factory = new SVN_Apache_Auth_Factory(
            $event_manager,
            new Parameters(15, 25)
        );

        $mod = $factory->get(ProjectTestBuilder::aProject()->build());

        self::assertSame($my_svn_apache, $mod);
    }
}
