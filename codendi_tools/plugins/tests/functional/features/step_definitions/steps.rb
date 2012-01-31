today = Time.now.localtime.strftime("%Y-%m-%d")

When /^I go to the bugs tracker of Test Project$/ do
  find(:xpath, '//a[text()="Test Project"]').click
  find(:xpath, "//a[contains(@href, '/plugins/tracker/?tracker=')]").click
end
When /^I submit a new artifact$/ do
  find(:xpath, '//a[contains(@href, "func=new-artifact")]').click
  within(:xpath, "//fieldset/legend[@title='fieldset_default_desc_key']") do
    find(:xpath, "../div/input[@type='text']").set("a bug title")
  end
  find("input[name='submit_and_stay']").click
  page.should have_css("div#feedback") #click_button('submit_and_continue')
end

When /^I set the 'Status' to "([^"]*)"$/ do |status|
  within(:xpath, "//label[text()='Status']/..") do
    find(:xpath, "./select/option[text()='#{status}']").select_option
  end
  find("input[name='submit_and_stay']").click
end

Given /^a closed bug$/ do
  steps %Q{
    Given I submit a new artifact
    Given I set the 'Status' to "Closed"
  }
end

Then /^a message says that (.*) has been cleared$/ do |field|
  within("div#feedback") do
    find(:xpath, ".").should have_content("'#{field}' a été automatiquement effacé")
  end
end
Then /^a message says that '(.*)' has been set to the current date$/ do |ignored|
  within("div#feedback") do
    find(:xpath, ".").should have_content(today)
  end
end

def tracker_element_with(label)
  "//label[@class='tracker_formelement_label' and text() = '#{label}']/.."
end

Then /^the artifact has '(.*)' cleared$/ do |label|
  find(:xpath, tracker_element_with(label)).should have_xpath("./*/input[@value = '']")
end
Then /^the artifact has '(.*)' set to the current date$/ do |ignored|
  find(:xpath, tracker_element_with(ignored)).should have_xpath("./*/input[@value='#{today}']")
end

When /^I provide a 'Closed Date' different from today$/ do
  not_today = (Time.now.day + 1).remainder(28)
  find(:xpath, tracker_element_with('Closed Date') + "//a[@class='date-picker-control']").click
  find(:xpath, "//div[@class='datePicker'][2]//td[contains(@class, 'dm-#{not_today}')]").click
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

When /^I go to The Garden Project/ do
  visit('projects/garden/')
  page.should have_content('The Garden Project')
end

Then /^the admin page is reachable/ do
    find(:xpath, "//a[contains(@href, '/project/admin/?group_id=')]").click
    find(:xpath, "//title").should have_content("Project Admin")
end

Then /^the admin page is not reachable$/ do
    page.should_not have_xpath("//a[contains(@href, '/project/admin/?group_id=')]")
    # hack the url
    link_to_project_information = find(:xpath, "//a[contains(text(), '[More information...]')]")['href']
    group_id = link_to_project_information.scan(/group_id=(\d+)/)[0][0]
    visit('project/admin/?group_id=' + group_id)
    page.should have_content("Insufficient Group Access")
end

When /^I go to the service Subversion$/ do
    find(:xpath, "//a[text()='Subversion']").click
    page.should have_content('Subversion Access')
end

Then /^the subversion admin page is not reachable$/ do
    page.should_not have_xpath("//a[contains(@href, '/svn/admin/?group_id=')]")
    # hack the url
    visit(page.current_url().gsub(/\/svn\/\?group_id=/, '/svn/admin/?group_id='))
    page.should have_content("Permission Denied")
end

When /^I go to the service Files$/ do
    find(:xpath, "//li/a[text()='Files']").click
    page.should have_content('Packages')
end

Then /^the file admin page is not reachable$/ do
    page.should_not have_xpath("//a[contains(@href, '/file/admin/?group_id=')]")
    # hack the url
    url = page.current_url().gsub(/\/file\/showfiles.php\?group_id=/, '/file/admin/?group_id=')
    visit(url)
    page.should have_content("Permission Denied")
end

