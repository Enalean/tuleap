<?php
require_once 'CodendiUpgrade.class.php';

/**
 * Enable projectlinks widget on project summary page for project that have
 * project links that from or on them (master or target).
 */
class Update_015 extends CodendiUpgrade {

    function getProjectWithoutWidget($field) {
        $sql = 'SELECT '.$field.' as group_id'.
            ' FROM plugin_projectlinks_relationship'.
            '   LEFT JOIN layouts_contents lc'.
            '     ON (lc.owner_id = '.$field.' AND lc.owner_type = "g" AND lc.name = "projectlinkshomepage")'.
            ' WHERE lc.owner_id IS NULL'.
            ' GROUP BY '.$field;
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            foreach($dar as $row) {
                $sql = 'INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank, is_minimized, is_removed, display_preferences, content_id)'.
                       ' SELECT '.$row['group_id'].', "g", 1, 1, "projectlinkshomepage", max(rank)+1, 0, 0, 0, 0'.
                       ' FROM layouts_contents'.
                       ' WHERE owner_id = '.$row['group_id'].
                       '   AND owner_type = "g"'.
                       '   AND layout_id = 1'.
                       '   AND column_id = 1';
                $res = $this->update($sql);
                if (!$res) {
                    $this->addUpgradeError('An error occured while adding a plugin_projectlinks widget to project '.$row['group_id'].': '.$this->da->isError());
                } else {
                    echo "Widget added for project ".$row['group_id'];
                    echo $this->getLineSeparator();
                }
            }
        }
    }

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        $this->getProjectWithoutWidget('master_group_id');
        $this->getProjectWithoutWidget('target_group_id');

        echo $this->getLineSeparator();
    }
}

?>