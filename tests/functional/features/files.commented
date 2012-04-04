Feature: I can upload files and MD5 sums are verified
  
  Background:
    Given I logon as "project_admin" : "project_admin"
    And I go on Files page of Test project
    
  Scenario: I create a package
    When I click on "[Create a New Package]"
    Then I enter "A cucumber package" as package name
    And I disable the license approval
    And I click on submit
    Then I should be on frs page and see "A cucumber package"

  Scenario: I create a release without MD5 sum
    And I click on first "[Add Release]"
    When I enter "cucumber v1" as release name
    And I attach a file
    And I click on Release File
    Then I should be on frs page and see "cucumber v1"
    And I should see "blabla.txt"
    And I should see file's checksum

  Scenario: I create a release with MD5 sum
    And I click on first "[Add Release]"
    When I enter "cucumber v2" as release name
    And I attach file "valid_blabla.txt"
    And it's md5sum
    And I click on Release File
    Then I should be on frs page and see "cucumber v2"
    And I should see "valid_blabla.txt"
    And I should see file's checksum

  Scenario: I create a release with invalid MD5 sum
    And I click on first "[Add Release]"
    When I enter "cucumber v3" as release name
    And I attach file "invalid_blabla.txt"
    And a wrong md5sum
    And I click on Release File
    Then I should be on frs page and see "cucumber v3"
    And an error message says checksum comparison failed

  Scenario: I delete a package
    When I delete the releases
    Then I should not see "cucumber v3"
    And I delete the package
    Then I should not see "A cucumber package"

#Scenario: I upload a file without providing it's checksum
