Feature: CreditCard3DSPurchaseHappyPath
  As a guest  user
  I want to make a purchase with a Credit Card 3DS
  And to see that transaction was successful

  Background:
    Given I prepare checkout "3DS"
    And I am on "Checkout" page
    Then I fill fields with "Customer data"

  @API-TEST @API-WDCEE-TEST @NOVA
  Scenario: purchase
    Given I am redirected to "Payment" page
    When I fill fields with "Valid Credit Card Data"
    And I am redirected to "Verified" page
    And I enter "wirecard" in field "Password"
    And I click "Continue"
    Then I am redirected to "Order Received" page
    And I see "Thank you for your purchase!"
