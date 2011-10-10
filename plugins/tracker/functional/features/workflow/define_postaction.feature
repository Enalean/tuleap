Feature: Define postactions on a transition
    In order to sanity of the artifacts
    As a tracker admin
    I want to define post action on a transition
    
    Background:
      Given I visit the tracker workflow admin page
      And I click on the details of a transition
      
    Scenario: Add a post action
      When I select a new post action
      And I submit the form
      Then I the new post action is added
      
    Scenario: Add two post actions
      When there is already a post action
      And I select a new post action
      And I submit the form
      Then I can add another post action
      
    Scenario: A post action must be well defined
      When I select a new post action
      And I submit the form
      Then the new post action is marked as not defined
      Then I choose the right settings for the post action
      And I submit the form
      Then the post action is not anymore marked as not defined
      
    Scenario: One field = one post action
      When there is a post action with a target field
      Then I cannot define another post action with the same field
      
    Scenario: Delete a post action
      When I click on the bin next to a post action
      And I submit the form
      Then the post action is deleted
