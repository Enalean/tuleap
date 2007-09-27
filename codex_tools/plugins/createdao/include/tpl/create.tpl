
    /**
    * create a row in the table <?=$table?> 
    * @return true or id(auto_increment) if there is no error
    */
    function create($<?=implode(', $', $create_fields)?>) {
		$sql = sprintf("INSERT INTO <?=$table?> (<?=implode(', ', $create_fields)?>) VALUES (<?=implode(', ', array_fill(0, count($create_fields), '%s'))?>)",
				$this->da->quoteSmart($<?=implode("),
				\$this->da->quoteSmart($", $create_fields)?>));
        $inserted = $this->update($sql);
<?php if($auto_increment) { ?>
        if ($inserted) {
            $dar =& $this->retrieve("SELECT LAST_INSERT_ID() AS id");
            if ($row = $dar->getRow()) {
                $inserted = $row['id'];
            } else {
                $inserted = $dar->isError();
            }
        }<?php } ?> 
        return $inserted;
    }

