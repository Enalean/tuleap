
Given /^I am on the home page$/ do
  visit('/')
end

When /^I logon as "([^"]*)" : "([^"]*)"$/ do |user, pwd|
    find(:xpath, "//a[@href='/my/']").click
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

