Feature: CreditCardNon3DSPurchaseHappyPath
  As a guest  user
  I want to make a purchase with a Credit Card Non 3DS
  And to see that transaction was successful

  Background:
    Given I prepare checkout "Non3DS"
    And I am on "Checkout" page
    Then I fill fields with "Customer data"

  @API-TEST @API-WDCEE-TEST
  Scenario: purchase
    Given I am redirected to "Payment" page
    When I fill fields with "Valid Credit Card Data"
    Then I am redirected to "Order Received" page
    And I see "Thank you for your purchase!"