Feature: Project admin page is restricted to project admins

Scenario: Projects admins can administrate the project
  Given I logon as "project_admin" : "project_admin" 
  When I go to The Garden Project
  Then the admin page is reachable

Scenario: Project members do not have access to the project admin
  Given I logon as "project_member" : "project_member" 
  When I go to The Garden Project
  Then the admin page is not reachable

Scenario: Project members do not have access to svn admin page
  Given I logon as "project_member" : "project_member" 
  When I go to The Garden Project
  And I go to the service "Subversion"
  Then the subversion admin page is not reachable

