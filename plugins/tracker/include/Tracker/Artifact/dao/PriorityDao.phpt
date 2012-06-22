--TEST--
Test artifact priorities in db
--INI--
include_path=/home/nicolas/tuleap/src:.
;include_path=/usr/share/codendi/src:.
--FILE--
<?php
    $sys_dbhost   = 'localhost';
    $sys_dbuser   = 'root';
    $sys_dbname   = 'test';
    $sys_dbpasswd = '';

    require_once 'common/dao/CodendiDataAccess.class.php';
    require_once 'PriorityDao.class.php';

    function dump_priorities($msg) {
        echo $msg .PHP_EOL;
        global $dao;
        $dar = $dao->retrieve("SELECT * FROM tracker_artifact_priority ORDER BY rank");
        foreach ($dar as $row) {
            foreach ($row as $cell) {
                echo $cell .',';
            }
            echo PHP_EOL;
        }
        echo '.'. PHP_EOL;
    }

    $dao = new Tracker_Artifact_PriorityDao();

    $dao->update("DROP TABLE IF EXISTS tracker_artifact_priority");
    $dao->update("CREATE TABLE tracker_artifact_priority(
        curr_id int(11) NULL,
        succ_id int(11) NULL,
        rank    int(11) NOT NULL,
        UNIQUE idx(curr_id, succ_id)
    ) ENGINE=InnoDB");

    dump_priorities("Table is empty");
    $dao->update("INSERT INTO tracker_artifact_priority VALUES (NULL, 1, 0), (1, NULL, 1)");
    dump_priorities("Table contains one element");
    $dao->artifactHasTheLowestPriority(42);
    dump_priorities("An element is inserted at the end");
    $dao->artifactHasAHigherPriorityThan(42,1);
    dump_priorities("42 is > than 1");
    $dao->artifactHasTheLowestPriority(66);
    $dao->artifactHasTheLowestPriority(123);
    $dao->artifactHasTheLowestPriority(101);
    dump_priorities("Three more elements are added at the end");
    $dao->artifactHasAHigherPriorityThan(1,101);
    dump_priorities("1 is > than 101");
    $dao->artifactHasALesserPriorityThan(42, 1);
    dump_priorities("42 is < than 1");
--EXPECT--
Table is empty
.
Table contains one element
,1,0,
1,,1,
.
An element is inserted at the end
,1,0,
1,42,1,
42,,2,
.
42 is > than 1
,42,0,
42,1,1,
1,,2,
.
Three more elements are added at the end
,42,0,
42,1,1,
1,66,2,
66,123,3,
123,101,4,
101,,5,
.
1 is > than 101
,42,0,
42,66,1,
66,123,2,
123,1,3,
1,101,4,
101,,5,
.
42 is < than 1
,66,0,
66,123,1,
123,1,2,
1,42,3,
42,101,4,
101,,5,
.

