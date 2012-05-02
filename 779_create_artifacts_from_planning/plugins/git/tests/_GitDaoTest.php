<?php

require_once('GitUnitTestCase.class.php');
require_once(dirname(__FILE__).'/../include/GitDao.class.php');

class GitDaoUnitTest extends GitUnitTestCase {


    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testAddTreeNode() {
        $tree = array();
        $data = array(
                            array(
                                    GitDao::REPOSITORY_ID=>1,
                                    GitDao::REPOSITORY_NAME=>'name1',
                                    GitDao::REPOSITORY_DESCRIPTION=>'description1',
                                    GitDao::REPOSITORY_PARENT=>0 ),
                            array(
                                    GitDao::REPOSITORY_ID=>2,
                                    GitDao::REPOSITORY_NAME=>'name2',
                                    GitDao::REPOSITORY_DESCRIPTION=>'description2',
                                    GitDao::REPOSITORY_PARENT=>1 ),
                            array(
                                    GitDao::REPOSITORY_ID=>3,
                                    GitDao::REPOSITORY_NAME=>'name3',
                                    GitDao::REPOSITORY_DESCRIPTION=>'description3',
                                    GitDao::REPOSITORY_PARENT=>2 ),
                            array(
                                    GitDao::REPOSITORY_ID=>4,
                                    GitDao::REPOSITORY_NAME=>'name4',
                                    GitDao::REPOSITORY_DESCRIPTION=>'description4',
                                    GitDao::REPOSITORY_PARENT=>2 ),
                            array(
                                    GitDao::REPOSITORY_ID=>5,
                                    GitDao::REPOSITORY_NAME=>'name5',
                                    GitDao::REPOSITORY_DESCRIPTION=>'description5',
                                    GitDao::REPOSITORY_PARENT=>4 ),
                            array(
                                    GitDao::REPOSITORY_ID=>6,
                                    GitDao::REPOSITORY_NAME=>'name6',
                                    GitDao::REPOSITORY_DESCRIPTION=>'description6',
                                    GitDao::REPOSITORY_PARENT=>0 )

                    );
                    foreach($data as $row) {
                        if ( empty($row[GitDao::REPOSITORY_PARENT]) ) {
                            $tree['children'][] = $row;
                        }
                    }
                    
                    foreach ($data as $row ) {
                        if ( !empty($row[GitDao::REPOSITORY_PARENT]) ) {
                            echo 'row='.$row[GitDao::REPOSITORY_NAME].'<br/>';
                            GitDao::addTreeNode($tree, $row);
                        }
                    }
                    print_r($tree);
    }



}

?>
