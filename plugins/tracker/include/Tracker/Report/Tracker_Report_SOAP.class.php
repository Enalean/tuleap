<?php

require_once 'Tracker_Report.class.php';

class Tracker_Report_SOAP extends Tracker_Report {
    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    private $soap_criteria = array();

    public function __construct(
            User $current_user,
            Tracker $tracker,
            PermissionsManager $permissions_manager,
            Tracker_ReportDao $dao,
            Tracker_FormElementFactory $formelement_factory
    ) {
        $id = $name = $description = $current_renderer_id = $parent_report_id = $user_id = $is_default = $tracker_id = $is_query_displayed = $updated_by = $updated_at = 0;
        parent::__construct($id, $name, $description, $current_renderer_id, $parent_report_id, $user_id, $is_default, $tracker_id, $is_query_displayed, $updated_by, $updated_at);

        $this->current_user        = $current_user;
        $this->tracker             = $tracker;
        $this->permissions_manager = $permissions_manager;
        $this->dao                 = $dao;
        $this->formelement_factory = $formelement_factory;
        $this->criteria            = array();
    }

    public function setSoapCriteria($criteria) {
        $this->soap_criteria = $criteria;
    }

    public function getCriteria() {
        $rank = 0;

        foreach ($this->soap_criteria as $key => $value) {
            $is_advanced = false;
            if ($formelement = $this->formelement_factory->getFormElementByName($this->getTracker()->getId(), $value->field_name)) {
                if ($formelement->userCanRead($this->current_user)) {
                    $criteria = new Tracker_Report_Criteria(
                        0,
                        $this,
                        $formelement,
                        $rank,
                        $is_advanced
                    );
                    $formelement->setCriteriaValueFromSOAP($criteria, $value->value);
                    $this->criteria[$formelement->getId()] = $criteria;
                    $rank++;
                }
            }
        }
        return $this->criteria;
    }
}

?>
