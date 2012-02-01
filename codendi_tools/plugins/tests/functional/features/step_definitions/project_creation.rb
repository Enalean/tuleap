When /^I start submitting a project$/ do
  find(:xpath, "//a[@href='/project/register.php']").click
end


When /^I have to accept the terms of use$/ do
  check("register_tos_i_agree")
  click_button("project_register_next")
end

Then /^enter a project name and short name$/ do
  fill_in('form_full_name', :with => "Test Project")
  fill_in('form_unix_name', :with => "atestproject")
  click_button("project_register_next")
end

Then /^accept default values for project (.*)$/ do |tab_is_ignored|
  click_button("project_register_next")
  page.should_not have_css("ul.feedback_error")
end

Then /^confirm the project creation$/ do
  click_button("project_register_next")
  page.should have_css("div#feedback")
  # TODO cant test anything more intelligent because the language can change and there is no discriminatory html attribute whatsoever
end

Then /^enter a short and long description$/ do
  fill_in('form_short_description', :with => "A short description")
  fill_in('form_101', :with => "Some useful longer description")
  click_button("project_register_next")
end

