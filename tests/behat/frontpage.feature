@theme @javascript @theme_imtpn
Feature: Front page blocks
  The front page blocks are setup correctly with content

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
    # The forum should be in group mode = 1.

  Scenario: I should be able to login from the main Log in Button
    Given I am on site homepage
    When I click on ".header__button a" "css_element"
    Then I should not see "Log in" in the ".fixed-top.navbar" "css_element"
