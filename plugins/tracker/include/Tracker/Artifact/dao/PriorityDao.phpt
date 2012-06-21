--TEST--
Test artifact priorities in db
--INI--
include_path=/srv/nicolas/tuleap/src:/srv/nicolas/tuleap/src/www/include:.
;include_path=/usr/share/codendi/src:/usr/share/codendi/src/www/include:.
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
        $dar = $dao->retrieve("SELECT * FROM tracker_artifact_priority");
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
    $dao->update("INSERT INTO tracker_artifact_priority VALUES (NULL, 1, 0), (1, NULL, 0)");
    dump_priorities("Table contains one element");
    $dao->artifactHasTheLeastPriority(42);
    dump_priorities("An element is inserted at the end");
    $dao->artifactHasAHigherPriorityThan(42,1);
    dump_priorities("42 is > than 1");
    $dao->artifactHasTheLeastPriority(66);
    $dao->artifactHasTheLeastPriority(123);
    $dao->artifactHasTheLeastPriority(101);
    dump_priorities("Three more elements are added at the end");
    $dao->artifactHasAHigherPriorityThan(123,1);
    dump_priorities("1 is > than 101");
--EXPECT--
Table is empty
.
Table contains one element
,1,0,
1,,0,
.
An element is inserted at the end
,1,0,
1,42,0,
42,,0,
.
42 is > than 1
,42,0,
1,,0,
42,1,0,
.
Three more elements are added at the end
,42,0,
1,66,0,
42,1,0,
66,123,0,
123,101,0,
101,,0,
.
1 is > than 101
,42,0,
1,101,0,
42,66,0,
66,123,0,
123,1,0,
101,,0,
.
