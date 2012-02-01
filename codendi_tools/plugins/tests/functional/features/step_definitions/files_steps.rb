### Files

# Package
Given /^I go on Files page of Test project$/ do
  find(:xpath, '//a[text()="The Garden Project"]').click
  find(:xpath, "//a[contains(@href, '/file/showfiles.php?group_id=')]").click
end

When /^I click on "([^"]*)"$/ do |linktitle|
  find(:xpath, "//a[contains(text(), '#{linktitle}')]").click
end

Then /^I enter "([^"]*)" as package name$/ do |package_name|
  fill_in('package[name]', :with => package_name)
end

Then /^I disable the license approval$/ do
  select("No", :from => 'package[approve_license]');
end

Then /^I click on submit$/ do
  find("input[name='submit']").click
end

Then /^I should be on frs page and see "([^"]*)"$/ do |package_name|
  page.should have_content("Package Releases")
  page.should have_content(package_name)
end

# Release

When /^I click on first "([^"]*)"$/ do |linktitle|
  find(:xpath, "//a[contains(text(), '#{linktitle}')][1]").click
end

When /^I enter "([^"]*)" as release name$/ do |release_name|
  fill_in('release[name]', :with => release_name)
end

When /^I attach a file$/ do
  select("browse", :from => 'ftp_file_list');
  attach_file('file[]', 'blabla.txt');
end
  
When /^I click on Release File$/ do
  find("input[value='Release File']").click
end

Then /^I should see file's checksum$/ do
  page.should have_content("c39f5e41d28ad813b1e3730f96d4e2ee")
end

When /^I attach file "([^"]*)"$/ do |file|
  select("browse", :from => 'ftp_file_list');
  attach_file('file[]', file);
end

When /^it's md5sum$/ do
  fill_in('reference_md5[]', :with => "d14da144fb4702b5e1477792a3545eec");
end

When /^a wrong md5sum$/ do
  fill_in('reference_md5[]', :with => "42");
end

Then /^an error message says checksum comparison failed$/ do
   within("ul.feedback_error") do
    msg = find(:xpath, ".")
    msg.should have_content("failed")
    msg.should have_content("27923ab725c4dbc439a2b10875f6d8a1")
  end
end

Then /^I should not see "([^"]*)"$/ do |text|
  page.should_not have_content(text)
end
