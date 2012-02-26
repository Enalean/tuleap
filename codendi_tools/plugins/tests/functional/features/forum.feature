Feature: I can post in forums

Scenario: Post a message in a forum notify people
  Given I logon as "project_member" : "project_member"
  Given I go on Forums page of Test project
  When I select Open Discussion forum
  And I type "This is a test" as Subject
  And I type "Body of message" as Message
  When I submit my post
  Then I should see "Message Posted - Email sent - people monitoring"
