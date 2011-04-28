<?php
require_once('CollectionTestCase.class.php');
require_once('common/collection/Collection.class.php');

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * 
 *
 * Test the class Collection
 */
class CollectionTest extends CollectionTestCase {
    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function CollectionTest($name = 'Collection test') {
        $this->CollectionTestCase($name);
        $this->collection_class_name = 'Collection';
    }
}
?>
