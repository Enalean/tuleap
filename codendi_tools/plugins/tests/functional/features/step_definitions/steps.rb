today = Time.now.localtime.strftime("%Y-%m-%d")

When /^I go to the bugs tracker of Test Project$/ do
  find(:xpath, '//a[text()="Test Project"]').click
  find(:xpath, '//a[starts-with(@href,"/plugins/tracker/?")]').click
  find(:xpath, '//a[text()=" Bugs"]').click
end
When /^I submit a new artifact$/ do
  find(:xpath, '//a[contains(@href, "func=new-artifact")]').click
  within (:xpath, "//fieldset/legend[@title='fieldset_default_desc_key']") do
    find(:xpath, "../div/input[@type='text']").set("a bug title")
  end
  find("input[name='submit_and_stay']").click
  page.should have_css("div#feedback") #click_button('submit_and_continue')
end

Then /^a message says that the field 'Start Date' has been set to the current date$/ do
  within("div#feedback") do
    find(:xpath, ".").should have_content(today)
  end
end

Then /^the artifact has 'Start Date' set to the current date$/ do
  find(:xpath, "//label[@class='tracker_formelement_label']/..").should have_xpath("./*/input[@value='#{today}']")
end
When /^I set the 'Status' to 'Closed'$/ do
  within(:xpath, "//label[text()='Status']/..") do
    find(:xpath, "./select/option[text()='Closed']").select_option
  end
  find("input[name='submit_and_stay']").click
end

Then /^a message says that the field 'Closed Date' has been set to the current date$/ do
  within("div#feedback") do
    find(:xpath, ".").should have_content(today)
  end
end

Then /^the artifact has 'Closed Date' set to the current date$/ do
  find(:xpath, "//label[@class='tracker_formelement_label']/..").should have_xpath("./*/input[@value='#{today}']")
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

