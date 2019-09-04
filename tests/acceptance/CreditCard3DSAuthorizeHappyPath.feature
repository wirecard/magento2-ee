Feature: CreditCard3DSAuthorizeHappyPath
  As a guest  user
  I want to make a authorization with a Credit Card 3DS
  And to see that transaction was successful

  Background:
    Given I activate payment action "authorize" in configuration
    And I prepare checkout "3DS"
    And I am redirected to "Checkout" page
    Then I fill fields with "Customer data"

  @batch @minor @major
  Scenario: authorize
    Given I am redirected to "Payment" page
    When I fill fields with "Valid Credit Card Data"
    And I am redirected to "Verified" page
    And I enter "wirecard" in field "Password"
    And I click "Continue"
    Then I am redirected to "Order Received" page
    And I see "Thank you for your purchase!"
    And I see "authorize" in transaction table
