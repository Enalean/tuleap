<?php

require_once 'CodendiUpgrade.class.php';

class Update_019 extends CodendiUpgrade {

    function _process() {
        echo $this->getLineSeparator();
        echo "Execution of script : ".get_class($this);
        echo $this->getLineSeparator();

        $forumml_inc = $GLOBALS['sys_custompluginsroot'].'/forumml/etc/forumml.inc';
        if (file_exists($forumml_inc)) {
            include $forumml_inc;
        }

        if (!is_dir($forumml_dir)) {
            $this->addUpgradeError("Unable to locate forumml data dir. Please check the 'forumml_dir' variable defined in ".$forumml_inc);
        }

        if ($this->tableExists('plugin_forumml_attachment')) {
            if (!$this->fieldExists('plugin_forumml_attachment', 'file_path')) {
                $res = $this->update('ALTER TABLE plugin_forumml_attachment ADD COLUMN file_path VARCHAR(255) NOT NULL AFTER file_size');
                if ($this->da->isError()) {
                    $this->addUpgradeError("An error occured while adding column 'file_path' to 'plugin_forumml_attachment': ".$this->da->isError());
                } else {
                    $this->updatePaths($forumml_dir);
                }
            }
        }
    }

    function updatePaths($forumml_dir) {
        $sql = 'SELECT id_attachment,  mh.value as date, file_name, list_name'.
            ' FROM plugin_forumml_attachment a'.
            '  JOIN plugin_forumml_message m ON (m.id_message = a.id_message)'.
            '  JOIN plugin_forumml_messageheader mh ON (mh.id_message = m.id_message)'.
            '  JOIN plugin_forumml_header h ON (h.id_header = mh.id_header)'.
            '  JOIN mail_group_list list ON (list.group_list_id = m.id_list)'.
            ' WHERE h.name = "date"';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError()) {
            foreach($dar as $row) {
                $date = date("Y_m_d",strtotime($row['date']));
                $path = $this->_getPath($forumml_dir, $row['file_name'], $row['list_name'], $date, 'store');
                if (file_exists($path)) {
                    $sql = 'UPDATE plugin_forumml_attachment SET file_path = '.$this->da->quoteSmart($path)." WHERE id_attachment = ".$row['id_attachment'];
                    $this->update($sql);
                } else {
                    echo "Unable to locate file '".$path."' for attachment ".$row['id_attachment'].$this->getLineSeparator();
                }
            }
        } else {
            $this->addUpgradeError("An error occured while fetching attachments: ".$this->da->isError());
        }
    }

    /**
     * _getPath - Stolen from ForumML_FileStorage (r12563, before fix to support several attch with same name the same day)
     *
     * Get the absolute path where to Upload/Store attached file
     *
     * @param name: basename of attached file
     * @param list: mailing-list name
     * @param date: attachment date (YYYY_MM_DD)
     * @param string type: upload/store
     *
     * @return string path
     */
    function _getPath($root, $name, $list, $date, $type) {

        // restrict file name to 64 characters (maximum)
                if (strlen($name) > 64) {
                         $name = substr($name, 0, 64);
                }

        $name = preg_replace('`[^a-z0-9_-]`i', '_', $name);
        $name = preg_replace('`_{2,}`', '_', $name);

        if ($type == "upload") {
                $path_elements = array($root, $type);
        } else if ($type == "store") {
                $path_elements = array($root, $list, $date);
        }

        $path = '';
        foreach($path_elements as $elem) {
            $path .= $elem .'/';
            if (!is_dir($path)) {
                mkdir($path, 0755);
            }
        }
        $path .= $name;
        return $path;
    }

}

?>