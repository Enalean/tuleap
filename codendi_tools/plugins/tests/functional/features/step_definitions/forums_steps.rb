# Forums
Given /^I go on Forums page of Test project$/ do
  find(:xpath, '//a[text()="MV Valid 4.0.26"]').click
  find(:xpath, "//a[contains(@href, '/forum/?group_id=')]").click
end

When /^I select Open Discussion forum$/ do  
  find(:xpath, '//a[contains(text(), "Open Discussion")]').click
end

When /^I type "([^"]*)" as Subject$/ do |subject|
  fill_in('subject', :with => subject)
end

When /^I type "([^"]*)" as Message$/ do |message|
  fill_in('body', :with => message)
end

When /^I submit my post$/ do
  find("input[value='Post Comment']").click
end

Then /^I should see "([^"]*)"$/ do |message|
  page.should have_content(message)
end
