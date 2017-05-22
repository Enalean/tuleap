<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tests\Selenium\SVN;

use Facebook\WebDriver\WebDriverBy;
use Lmc\Steward\Test\AbstractTestCase;
use Lmc\Steward\Component\Legacy;
use Tuleap\Tests\Selenium\SeedCookiesTest;

/**
 * @delayAfter Tuleap\Tests\Selenium\SVN\SVNCLITest
 * @delayAfter Tuleap\Tests\Selenium\SeedCookiesTest
 * @delayMinutes 0.2
 */
class SVNBrowsingTest extends AbstractTestCase
{

    /** @var array */
    private $cookies;
    /**
     * @before
     */
    public function init()
    {
        $this->cookies = (new Legacy($this))
            ->loadWithName(SeedCookiesTest::COOKIES);
    }

    public function testShouldDisplayRepositoryContent()
    {
        $this->wd->get('https://reverse-proxy/');
        $this->wd->manage()->addCookie($this->cookies[SeedCookiesTest::ALICE_COOKIE]);

        $this->wd->get('https://reverse-proxy/plugins/svn/?group_id=102');

        $this->assertContains('SVN', $this->wd->getTitle());

        $this->wd->findElement(WebDriverBy::linkText('sample'))->click();

        $this->waitForClass('tuleap-viewvc-body');

        $this->assertViewVcLink('branches');
        $this->assertViewVcLink('tags');
        $this->assertViewVcLink('trunk');
    }

    private function assertViewVcLink($text)
    {
        $this->assertEquals(trim($this->findByLinkText($text)->getText()), $text);
    }
}
