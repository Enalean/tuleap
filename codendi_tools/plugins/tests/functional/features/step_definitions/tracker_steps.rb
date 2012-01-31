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

