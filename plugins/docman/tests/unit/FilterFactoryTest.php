<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman;

use Docman_FilterFactory;
use Docman_FilterItemType;
use Docman_ListMetadata;
use Docman_Metadata;
use Docman_MetadataFactory;
use Docman_Report;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FilterFactoryTest extends TestCase
{
    public function testCloneFilter(): void
    {
        $mdFactory = $this->createMock(Docman_MetadataFactory::class);
        $mdFactory->method('isRealMetadata')->willReturn(false);

        $md = new Docman_ListMetadata();
        $md->setLabel('item_type');

        $srcFilter     = $this->createMock(Docman_FilterItemType::class);
        $srcFilter->md = $md;
        $dstReport     = new Docman_Report();
        $dstReport->setGroupId(123);

        $filterFactory = $this->createPartialMock(Docman_FilterFactory::class, [
            'getGlobalSearchMetadata',
            'getItemTypeSearchMetadata',
            'getFilterFactory',
            'cloneFilterValues',
        ]);
        $gsMd          = new Docman_Metadata();
        $filterFactory->method('getGlobalSearchMetadata')->willReturn($gsMd);
        $gsMd->setLabel('global_txt');
        $itMd = new Docman_ListMetadata();
        $filterFactory->method('getItemTypeSearchMetadata')->willReturn($itMd);
        $itMd->setLabel('item_type');

        $itMd->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
        $metadataMapping  = ['md' => [], 'love' => []];
        $dstFilterFactory = $this->createPartialMock(Docman_FilterFactory::class, [
            'createFromMetadata',
            'createFilter',
        ]);

        $filterFactory->method('getFilterFactory')->willReturn($dstFilterFactory);
        $filterFactory->expects(self::once())->method('cloneFilterValues');

        $dstFilterFactory->expects(self::once())->method('createFromMetadata');
        $dstFilterFactory->expects(self::once())->method('createFilter');

        $filterFactory->cloneFilter($srcFilter, $dstReport, $metadataMapping);
    }
}
