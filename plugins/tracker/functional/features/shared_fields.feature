Feature: Use a field defined in another tracker
  In order to have a synthetical view (for instance remaining work in the hierarchical view)
  As a tracker user
  I want to be able to query multiple trackers, thus they must share a minimum 
  set of common properties (for instance definition of status=ToDO|OnGoing|Done)
  
  Scenario: We can add a field defined in another tracker
    Given I logon as "testuser" : "tuleap_pass"
    And I go the fields admin page of the "Shared Field Tracker" of the project "Test Project"
    When I add the field "Status" from the tracker "Bugs" of the project "Test Project"
    Then the field "Status" is present and has at least the option "Reopened"
