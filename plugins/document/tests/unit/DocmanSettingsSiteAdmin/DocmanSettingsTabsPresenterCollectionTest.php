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

namespace Tuleap\Docman\DocmanSettingsSiteAdmin;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DocmanSettingsTabsPresenterCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsAnArrayWithActiveTab(): void
    {
        $collection = new DocmanSettingsTabsPresenterCollection();

        $another_tab = new class ('/url', 'Another Tab') extends DocmanSettingsTabPresenter {
        };

        $collection->add($another_tab);

        $tabs = $collection->getTabs('/admin/document/files-upload-limits');
        $this->assertEquals('/admin/document/files-upload-limits', $tabs[0]->url);
        $this->assertEquals('File upload limits', $tabs[0]->label);
        $this->assertTrue($tabs[0]->is_active);
        $this->assertEquals('/url', $tabs[1]->url);
        $this->assertEquals('Another Tab', $tabs[1]->label);
        $this->assertFalse($tabs[1]->is_active);

        $tabs = $collection->getTabs('/url');
        $this->assertEquals('/admin/document/files-upload-limits', $tabs[0]->url);
        $this->assertEquals('File upload limits', $tabs[0]->label);
        $this->assertFalse($tabs[0]->is_active);
        $this->assertEquals('/url', $tabs[1]->url);
        $this->assertEquals('Another Tab', $tabs[1]->label);
        $this->assertTrue($tabs[1]->is_active);
    }
}
