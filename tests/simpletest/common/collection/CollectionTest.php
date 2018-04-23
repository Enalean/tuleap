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
    function __construct($name = 'Collection test') {
        parent::__construct($name);
        $this->collection_class_name = 'Collection';
    }
}
?>
