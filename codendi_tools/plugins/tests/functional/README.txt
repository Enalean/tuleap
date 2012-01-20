Launch tests
============

Cucumber tests
--------------

$> cd feature
$> cucumber
Feature: Make cucumber work

  Scenario: A user can logon              # features/test.feature:3
    Given I am on the home page           # features/step_definitions/steps.rb:2
    When I logon as "admin" : "siteadmin" # features/step_definitions/steps.rb:6
    Then I am on my personal page         # features/step_definitions/steps.rb:13

1 scenario (1 passed)
3 steps (3 passed)
0m13.740s


Installation & Setup
====================

# The recommended way to setup the whole platform is to rely on a local ruby installation.

RVM
===
RVM is the easiest way to build your own ruby in your homedir.

First, install it:
bash -s stable < <(curl -s https://raw.github.com/wayneeseguin/rvm/master/binscripts/rvm-installer )

# Load rvm:
. $HOME/.rvm/scripts/rvm

# Check all requirements are installed
rvm requirements
-> look for "additional dependencies" and install what is needed by "# For Ruby / Ruby HEAD (MRI, Rubinius, & REE), install the following:" section.

# Install ruby head
rvm install ruby-1.8.7-head

# Load your ruby in your environement:
$> rvm use ruby-1.8.7-head
$> which ruby
/home/manuel/.rvm/rubies/ruby-1.8.7-head/bin/ruby
$> which gem
/home/manuel/.rvm/rubies/ruby-1.8.7-head/bin/gem

# Install gems
gem install rspec cucumber capybara selenium

Environement
============

# add the following lines to your ~/.bashrc and source it
. $HOME/.rvm/scripts/rvm
rvm use ruby-1.8.7-head

Running with webkit (faster selenium)
=======
install webkit (or qt on mac)

gem install capybara-webkit

# if the current gem of capybara webkit is still 0.7.2, you must build it to get the ssl_error_ignore option :
git clone https://github.com/thoughtbot/capybara-webkit.git
cd capybara-webkit
bundle && rake
sudo gem install pkg/capybara-webkit-0.7.2.gem
