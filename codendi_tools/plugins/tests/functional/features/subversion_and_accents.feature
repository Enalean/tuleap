Feature: Special characters are shown correctly while browsing the subversion repository
  
  Scenario: Add accented letters in a file and commit in subversion
    Given I logon as "project_member" : "project_member"
    And I commit in svn a file that contains accented letters
    Then I should see those characters in viewvc interface
