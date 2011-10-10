Feature:
    In order to spent less time in my SLA tracker
    As a core team member
    I want to not have to fill in the Close Date field when I close an artifact
    
    Background:
      Given the tracker has a field 'Status' with two values 'Open' and 'Closed'
      And it has a field 'Closed Date'
      And the tracker has a workflow enabled on this field
      And a post action exists on the transition 'Open' => 'Closed'
      And this post action tells that the field 'Closed Date' will take the current date
      And a post action exists on the transition 'Closed' => 'Open'
      And this post action tells that the field 'Closed Date' will be cleared
      
    Scenario: Set the close date
      When I switch the field 'Status' from 'Open' to 'Closed'
      And I do not touch anything else
      And I submit the form
      Then a message says that the field 'Closed Date' as been set to the current date
      And the notification email display the new 'Closed Date' value
      And the artifact has 'Closed Date' set to the current date
      
    Scenario: Clear the close date
      When I switch the field 'Status' from 'Closed' to 'Open'
      And I do not touch anything else
      And I submit the form
      Then a message says that the field 'Closed Date' as been cleared
      And the notification email displays the new 'Closed Date' value
      And the artifact has 'Closed Date' cleared
      
    Scenario: Workflow overrides submitted value
      When I choose a date (different from today) in the field 'Closed Date'
      And I switch the field 'Status' from 'Open' to 'Closed'
      And I submit the form
      Then a message says that the field 'Closed Date' as been set to the current date
      And the artifact has 'Closed Date' set to the current date instead of the submitted date
      
    Scenario: No update perms, no change
      Given I cannot update the field 'Closed Date'
      When I switch the field 'Status' from 'Closed' to 'Open'
      And I do not touch anything else
      And I submit the form
      Then a message says that the field 'Closed Date' cannot be update because of permissions settings
      And the field is not updated
      
    Scenario: No read perms, no change and no warning
      Given I cannot read the field 'Closed Date'
      When I switch the field 'Status' from 'Open' to 'Closed'
      And I do not touch anything else
      And I submit the form
      Then no feedback is displayed about the 'Closed Date' field
      And the field is not updated
      
