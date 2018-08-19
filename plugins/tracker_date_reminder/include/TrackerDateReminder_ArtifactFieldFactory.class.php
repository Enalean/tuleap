<?php

class TrackerDateReminder_ArtifactFieldFactory {
    protected $fieldsWithNotification = array();
    function __construct() {
        
    }
    /**
     * Return all date fields used
     *
     *                @return array
     */
    function getUsedDateFields(ArtifactFieldFactory $art_field_fact) {
        $result_fields = array();
        foreach ($art_field_fact->USAGE_BY_NAME as $key => $field) {
            if ( $field->getUseIt() == 1 && $field->isDateField()) {
                $result_fields[$key] = $field;
            }
        }
        return $result_fields;
    }

    function cacheFieldsWithNotification($group_artifact_id) {
        $sql = 'SELECT field_id'.
               ' FROM artifact_date_reminder_settings'.
               ' WHERE group_artifact_id = '.db_ei($group_artifact_id);
        $res = db_query($sql);
        if ($res && !db_error($res)) {
            while(($row = db_fetch_array($res))) {
                $this->fieldsWithNotification[$row['field_id']] = true;
            }
        }
        
    }
    
    function notificationEnabled($field_id) {
        return isset($this->fieldsWithNotification[$field_id]);
    }
}
?>
