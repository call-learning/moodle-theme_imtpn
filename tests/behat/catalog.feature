@javascript @theme_imtpn
Feature: I check that the user profile has the right informaiton
  The user profile should have been modified to include only relevant information

  Background:
    Given the following "courses" exist:
      | shortname | fullname |
      | C1        | Course 1 |
    Given the following "local_resourcelibrary > category" exist:
      | component   | area   | name                             |
      | core_course | course | Resource Library: Generic fields |
    Given the following "local_resourcelibrary > field" exist:
      | component   | area   | name                | customfieldcategory              | shortname | type     | configdata                                                                                                          |
      | core_course | course | Test Field Text     | Resource Library: Generic fields | CF1       | text     |                                                                                                                     |
      | core_course | course | Test Field Checkbox | Resource Library: Generic fields | CF2       | checkbox |                                                                                                                     |
      | core_course | course | Test Field Select   | Resource Library: Generic fields | CF4       | select   | {"required":"1","uniquevalues":"0","options":"A\r\nB\r\nC\r\nD","defaultvalue":"A,C","locked":"0","visibility":"2"} |
      | core_course | course | Test Field Textarea | Resource Library: Generic fields | CF5       | textarea |                                                                                                                     |