Given /^I am on the bugs tracker of Test Project$/ do
  pending # express the regexp above with the code you wish you had
end

When /^I submit a new artifact$/ do
  pending # express the regexp above with the code you wish you had
end

Then /^a message says that the field 'Start Date' as been set to the current date$/ do
  pending # express the regexp above with the code you wish you had
end

Then /^the artifact has 'Start Date' set to the current date$/ do
  pending # express the regexp above with the code you wish you had
end

When /^I logon as "([^"]*)" : "([^"]*)"$/ do |user, pwd|
    find(:xpath, "//a[@href='/account/login.php']").click
    fill_in('form_loginname', :with => user)
    fill_in('form_pw', :with => pwd)
    find("input[name='login']").click
end

Then /^I am on my personal page$/ do
    page.should have_content('Site Administrator')
end

Given /^I move to the admin page$/ do
  find(:xpath, "//a[@href='/admin/']").click
  #visit('admin/')
end

Then /^I am still logged on$/ do
  page.should have_content('admin')
end

