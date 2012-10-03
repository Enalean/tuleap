<?php

require_once 'Tracker_Report.class.php';

class Tracker_Report_SOAP extends Tracker_Report {
    public function __construct(User $current_user, Tracker $tracker, PermissionsManager $permissions_manager, Tracker_ReportDao $dao) {
        $id = $name = $description = $current_renderer_id = $parent_report_id = $user_id = $is_default = $tracker_id = $is_query_displayed = $updated_by = $updated_at = 0;
        parent::__construct($id, $name, $description, $current_renderer_id, $parent_report_id, $user_id, $is_default, $tracker_id, $is_query_displayed, $updated_by, $updated_at);
        
        $this->current_user = $current_user;
        $this->tracker = $tracker;
        $this->permissions_manager = $permissions_manager;
        $this->dao = $dao;
    }
    
    public function getCriteria() {
        return array();
    }
}

?>
