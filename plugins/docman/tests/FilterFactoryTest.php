<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'bootstrap.php';

Mock::generate('Docman_MetadataFactory');
Mock::generate('Docman_FilterItemType');


Mock::generatePartial('Docman_FilterFactory', 'Docman_FilterFactoryTestVersion', array('getGlobalSearchMetadata', 'getItemTypeSearchMetadata', 'cloneFilterValues', 'getFilterFactory', 'createFilter', 'createFromMetadata'));

class Docman_FilterFactoryTest extends TuleapTestCase {

    function testCloneFilter() {
        $mdFactory =  new MockDocman_MetadataFactory();
        $mdFactory->setReturnValue('isRealMetadata', false);

        $md = new Docman_ListMetadata();
        $md->setLabel('item_type');

        $srcFilter = new Docman_FilterItemType($md);
        $dstReport = new Docman_Report();
        $dstReport->setGroupId(123);

        $filterFactory = new Docman_FilterFactoryTestVersion($this);
        $gsMd = new Docman_Metadata();
        $filterFactory->setReturnValue('getGlobalSearchMetadata', $gsMd);
        $gsMd->setLabel('global_txt');
        $itMd = new Docman_ListMetadata();
        $filterFactory->setReturnValue('getItemTypeSearchMetadata', $itMd);
        $itMd->setLabel('item_type');

        $itMd->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
        $metadataMapping = array('md' => array(), 'love' => array());
        $dstFilterFactory = new Docman_FilterFactoryTestVersion($this);


        $filterFactory->setReturnValue('getFilterFactory', $dstFilterFactory);

        $filterFactory->cloneFilter($srcFilter, $dstReport, $metadataMapping);
        $dstFilterFactory->expectOnce('createFromMetadata');
        $filterFactory->expectOnce('cloneFilterValues');
        $dstFilterFactory->expectOnce('createFilter');
    }

}
