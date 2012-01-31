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

When /^I go to the service Wiki$/ do
    find(:xpath, "//li/a[text()='Wiki']").click
    page.should have_content('Wiki Documents')
end

Then /^the PhpWikiAdministration page is not reachable$/ do
    find(:xpath, "//a[text()='Home Page' and contains(@href, 'wiki')]").click
    page.should have_content('HomePage')
    url = page.current_url().gsub(/HomePage/, 'PhpWikiAdministration')
    visit(url)
    page.should have_content("Permission Denied")
end

