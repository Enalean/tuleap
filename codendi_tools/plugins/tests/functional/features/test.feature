Feature: It is possible to log in 

Background: 
When I logon as "admin" : "siteadmin"

Scenario: Just after login the user is on his personal page 
Then I am on my personal page

Scenario: The user stays logged on when navigation from page to page
Given I am on my personal page 
When I move to the admin page 
Then I am still logged on 
