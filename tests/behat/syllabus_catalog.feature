@javascript @theme_imtpn @local_resourcelibrary
Feature: I check that the user profile has the right informaiton
  The user profile should have been modified to include only relevant information

  Background:

    Given the following "courses" exist:
      | shortname | fullname | summary          |
      | C1        | Course 1 | Course 1 summary |
      | C2        | Course 2 | Course 2 summary |
      | C3        | Course 3 | Course 3 summary |
    Given the following "local_resourcelibrary > category" exist:
      | component   | area   | name                             |
      | core_course | course | Resource Library: Generic fields |
    And the following "activities" exist:
      | activity | name      | intro     | course | idnumber |
      | page     | PageName1 | PageDesc1 | C1     | PAGE1    |
      | page     | PageName2 | PageDesc2 | C1     | PAGE2    |
      | page     | PageName3 | PageDesc3 | C1     | PAGE3    |
      | page     | PageName4 | PageDesc4 | C1     | PAGE4    |
      | page     | PageName5 | PageDesc5 | C1     | PAGE5    |
    Given the following "local_resourcelibrary > field" exist:
      | component             | area         | name                | customfieldcategory              | shortname | type     | configdata                                                                                                          |
      | core_course           | course       | Test Field Text     | Resource Library: Generic fields | CF1       | text     |                                                                                                                     |
      | core_course           | course       | Test Field Checkbox | Resource Library: Generic fields | CF2       | checkbox |                                                                                                                     |
      | core_course           | course       | Test Field Select   | Resource Library: Generic fields | CF4       | select   | {"required":"1","uniquevalues":"0","options":"A\r\nB\r\nC\r\nD","defaultvalue":"A,C","locked":"0","visibility":"2"} |
      | core_course           | course       | Test Field Textarea | Resource Library: Generic fields | CF5       | textarea |                                                                                                                     |
      | local_resourcelibrary | coursemodule | Test Field Text     | Resource Library: Generic fields | CM1       | text     |                                                                                                                     |
      | local_resourcelibrary | coursemodule | Test Field Checkbox | Resource Library: Generic fields | CM2       | checkbox |                                                                                                                     |
      | local_resourcelibrary | coursemodule | Test Field Select   | Resource Library: Generic fields | CM4       | select   | {"required":"1","uniquevalues":"0","options":"A\r\nB\r\nC\r\nD","defaultvalue":"A,C","locked":"0","visibility":"2"} |
      | local_resourcelibrary | coursemodule | Test Field Textarea | Resource Library: Generic fields | CM5       | textarea |                                                                                                                     |
    Given the following "local_resourcelibrary > fielddata" exist:
      | fieldshortname | value  | courseshortname | activityidnumber | activity |
      | CF1            | ABCDEF | C1              |                  |          |
      | CF1            | BCBCBC | C2              |                  |          |
      | CF1            | ZXYZXZ | C3              |                  |          |
      | CM1            | ABCDEF | C1              | PAGE1            | page     |

  Scenario: As a teacher I should see my courses in the catalog
    And I log in as "teacher1"
    And I follow "Catalog"
    And I follow "View catalog"
    And I should see "Course 1"
    And I should see "Course 2"
    And I should see "Course 3"

  Scenario: As a teacher I should see my courses in the syllabus for the coures
    And I log in as "teacher1"
    And I follow "Catalog"
    And I follow "View catalog"
    And I click on ".link-go-to-content[1]" "link"
    Then I pause scenario execution