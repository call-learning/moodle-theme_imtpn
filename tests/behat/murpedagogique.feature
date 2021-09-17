@theme @javascript @theme_imtpn
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
    # The forum should be in group mode = 1.
    And the following "activities" exist:
      | activity | name            | intro           | type    | course | idnumber        | groupmode |
      | forum    | Mur pedagogique | Mur pedagogique | general | C1     | MUR_PEDAGOGIQUE | 1         |
    And the following "groups" exist:
      | name | course | idnumber | enrolmentkey |
      | G1   | C1     | G1       |              |
      | G2   | C1     | G2       |              |
      | G4   | C1     | G4       |  abcd        |
    And the following "group members" exist:
      | user     | group |
      | student1 | G1    |
      | student3 | G2    |
    Given the following config values are set as admin:
      | murpedagoidnumber | MUR_PEDAGOGIQUE | theme_imtpn |
      | murpedagoenabled  | 1               | theme_imtpn |
    # We just changed the mur pedagogique identifier so we need to reset the blocks
    Then I reset the murpedagogique blocks

  Scenario Outline: As a logged in user I should see the mur pedagogique link if I am registered in the course
    Given I log in as "<user>"
    Then I <catalogexistence> see "Catalog" in the ".fixed-top.navbar" "css_element"
    Then I <murpedagoexistence> see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"
    And I log out
    Examples:
      | user     | catalogexistence | murpedagoexistence |
      | student1 | should           | should             |
      | student2 | should           | should not         |
      | admin    | should           | should             |

  Scenario: When the mur pedagogique is disabled I should not see the menu
    Given the following config values are set as admin:
      | murpedagoidnumber | MUR_PEDAGOGIQUE | theme_imtpn |
      | murpedagoenabled  | 0               | theme_imtpn |
    Then I log in as "student1"
    Then I should not see "Mur pédagogique" in the ".fixed-top.navbar" "css_element"
    And I log out

  Scenario: As a user registered in the mur pedagogique I can see all discussions
    Given I log in as "student1"
    Then I follow "Mur pédagogique"
    Then I click on "Add a new topic" "link"
    Then I should see "Subject"
    Then I should see "Message"
    And I set the following fields to these values:
      | Subject | My preferred subject for Group G1 |
      | Message | My preferred message for Group G1 |
    And I press "Post to forum"
    And I should see "My preferred message for Group G1" in the "article" "css_element"
    Then I click on "a.mpedago-group-link" "css_element"
    Then I wait until the page is ready
    And I should see "G1" in the ".groupinfobox" "css_element"
    And I should see "G1" in the "#page-navbar" "css_element"
    And I should see "Mur pédagogique" in the "#page-navbar" "css_element"

  Scenario: As a teacher I can add a new group
    Given I log in as "teacher1"
    Then I follow "Mur pédagogique"
    Then I click on "View all groups" "button"
    Then I click on "Add new Group" "button"
    Then I should see "Group name"
    And I set the following fields to these values:
      | Group name        | G3                  |
      | Group description | A new group created |
    And I press "Save changes"
    And I should see "G3" in the ".groupinfobox" "css_element"
    And I should see "G3" in the "#page-navbar" "css_element"
    And I should see "Mur pédagogique" in the "#page-navbar" "css_element"

  Scenario: As a student I can join a group
    Given I log in as "student3"
    Then I follow "Mur pédagogique"
    Then I click on "View all groups" "button"
    And I click on "a.murpedago-group-link" "css_element" in the "G1" "table_row"
    And I should not see "Add a new topic"
    And I should see "Règles de participation"
    Then I click on "Join group" "button"
    Then I should see "Congratulation ! You have joined"
    Then I click on "Continue" "button"
    And I should see "Add a new topic"

  Scenario: As a student I can join a group with a password
    Given I log in as "student3"
    Then I follow "Mur pédagogique"
    Then I click on "View all groups" "button"
    And I click on "a.murpedago-group-link" "css_element" in the "G4" "table_row"
    And I should not see "Add a new topic"
    And I should see "Règles de participation"
    Then I click on "Join group" "button"
    Then I should see "Enrolment key"
    Then I set the field "Enrolment key" to "abc"
    And I click on "Save" "button"
    Then I should see "The enrolment key you entered is not valid"
    Then I set the field "Enrolment key" to "abcd"
    And I click on "Save" "button"
    Then I should see "Congratulation ! You have joined"
    Then I click on "Continue" "button"
    And I should see "Add a new topic"

  Scenario: As a student I can leave a group I already joined
    Given I log in as "student3"
    Then I follow "Mur pédagogique"
    Then I click on "View all groups" "button"
    And I click on "a.murpedago-group-link" "css_element" in the "G2" "table_row"
    And I should see "Leave group"
    Then I click on "Leave group" "button"
    Then I should see "You left the group "
    Then I click on "Continue" "button"
    And I should not see "Add a new topic"
