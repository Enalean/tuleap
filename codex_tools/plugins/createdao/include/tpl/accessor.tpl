    /**
    * Searches <?=$classname?> by <?=$fieldname?> 
    * @return DataAccessResult
    */
    function & searchBy<?=$fieldname?>($<?=strtolower(substr($fieldname, 0, 1)).substr($fieldname,1)?>) {
        $sql = sprintf("SELECT <?=$other_fields?> FROM <?=$table?> WHERE <?=$field?> = %s",
				$this->da->quoteSmart($<?=strtolower(substr($fieldname, 0, 1)).substr($fieldname,1)?>));
        return $this->retrieve($sql);
    }

