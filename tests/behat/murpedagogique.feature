@javascript @theme_imtpn
Feature: Mur pedagogique test cases
  We can access the mur pedagogique and associated groups

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
      | student3 | Student   | 3        | student3@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student3 | C1     | student        |
    And the following "activities" exist:
      | activity | name            | intro           | type    | course | idnumber        |
      | forum    | Mur pedagogique | Mur pedagogique | general | C1     | MUR_PEDAGOGIQUE |
    And the following "groups" exist:
      | name | course | idnumber |
      | G1   | C1     | G1       |
      | G2   | C1     | G2       |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student3 | G2    |
    Given the following config values are set as admin:
      | murpedagoidnumber | MUR_PEDAGOGIQUE | theme_imtpn |
      | murpedagoenabled  | 1               | theme_imtpn |

#  Scenario: As a logged in user I should see the mur pedagogique link if I am registered in the course
#    Given I log in as "student1"
#    Then I should see "Catalog" in the ".fixed-top.navbar" "css_element"
#    Then I should see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"
#    And I log out
#
#  Scenario: As an admin I should see the mur pedagogique link if I am registered in the course
#    Given I log in as "admin"
#    Then I should see "Catalog" in the ".fixed-top.navbar" "css_element"
#    Then I should see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"
#    And I log out
#
#  Scenario: As a logged in user I should not see the mur pedagogique link if I am registered in the course
#    Given I log in as "student2"
#    Then I should see "Catalog" in the ".fixed-top.navbar" "css_element"
#    Then I should not see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"
#    And I log out
#
#  Scenario: When the mur pedagogique is disabled I should not see the menu
#    Given the following config values are set as admin:
#      | murpedagoidnumber | MUR_PEDAGOGIQUE | theme_imtpn |
#      | murpedagoenabled  | 0               | theme_imtpn |
#    Then I log in as "student1"
#    Then I should not see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"
#    And I log out

  Scenario: As a user registered in the mur pedagogique I can see all discussions
    Given I log in as "student1"
    Then I follow "Mur pédagogique"
    Then I pause scenario execution
    Then I click on "Add a new topic" "button"
    Then I should see "Subject"
    Then I should see "Message"
    And I set the following fields to these values:
      | Subject | My preferred subject |
      | Message | My preferred message |
#    And I press "Post to forum"
    Then I pause scenario execution


