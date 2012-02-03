Given /^I go the fields admin page of the "([^"]*)" of the project "([^"]*)"$/ do |tracker, project|
    step "I go to the #{tracker} tracker of #{project}"
    click_on('Administration')
    click_on('Field Usage')
end

When /^I add the field "([^"]*)" from the tracker "([^"]*)"$/ do |arg1, arg2|
    click_on('Use a shared field')
    fill_in('field_id', :with => '343')
    click_on('Submit')
end

Then /^the field "([^"]*)" is present and has at least the option "([^"]*)"$/ do |arg1, arg2|
  pending # express the regexp above with the code you wish you had
end

