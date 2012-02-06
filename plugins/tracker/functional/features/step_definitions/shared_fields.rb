Given /^I go the fields admin page of the "([^"]*)" of the project "([^"]*)"$/ do |tracker, project|
    step "I go to the #{tracker} tracker of #{project}"
    click_on('Administration')
    click_on('Field Usage')
end

When /^I add the field "([^"]*)" from the tracker "([^"]*)" of the project "([^"]*)"$/ do |field, tracker, project|
    page.should_not have_select(field)
    field_id = search_id_of(field, tracker, project)
    find(:xpath, "//a[@name='create-formElement[shared]']").click
    fill_in('formElement_data[field_id]', :with => field_id)
    click_on('Submit')
end

Then /^the field "([^"]*)" is present and has at least the option "([^"]*)"$/ do |field, option|
    page.should have_select(field, :options => [option])
    find(:xpath,"//label[contains(@title, '#{field}')]/..//a[contains(@href, 'func=admin-formElement-remove')]").click
end

def search_id_of(field, tracker, project)
    old_url = page.current_url
    visit('my/')
    step "I go to the #{tracker} tracker of #{project}"
    field_id = find(:xpath, '//label[contains(text(), "Status")]/input')[:id].gsub(/^.*_(\d+)_.*$/, '\1')
    visit(old_url)
    field_id
end
