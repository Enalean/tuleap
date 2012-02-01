Feature: Create Project
  In order to easily start a new project
  as a team member
  I need to be able to create a new project at will

Scenario: I can submit a new project
  Given I logon as "testuser" : "tuleap_pass"
  When I start submitting a project
  Then I have to accept the terms of use
  And enter a project name and short name
  And accept default values for project properties
  And accept default values for project template
  And enter a short and long description
  And accept default values for project services
  And accept default values for project categorisation
  And accept default values for project license
  And confirm the project creation
  Then the site admin has to validate the project
