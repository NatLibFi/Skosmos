import { accessibilityTestRunner } from '../support/accessibility.js'
import 'cypress-axe'

/* If you want the test to be skipped, add a skip command after the describe part:
    - test enabled: describe('Check accessibility of ...
    - test to be skipped: describe.skip('Check accessibility of ... */

// To avoid redundancy, the function offering input values for fields that are needed more than once
function fillInFormFields () {
  // Input for the name field
  const name = 'John Smith'
  cy.get('#name-input').type(name)

  // Input for the email field
  const eMail = 'john.smith@testing.dom'
  cy.get('#email-input').type(eMail)

  // Input for the subject field
  const subject = 'Test Subject'
  cy.get('#subject-input').type(subject)

  // Input for the message field
  const message = 'Test message'
  cy.get('#message-input').type(message)
}

describe('Testing the accessibility of the form', () => {
  before(() => {
    cy.visit('/fi/feedback')

    // Call the function that fills in the form fields
    fillInFormFields()
    cy.injectAxe()
  })
  accessibilityTestRunner()
})

describe('Testing the accessibility of the thank you page after submitting the filled form', () => {
  before(() => {
    cy.visit('/fi/feedback')
    // Call the function that fills in the form fields
    fillInFormFields()

    // Submit the form
    cy.get('#submit-feedback').click()
    cy.injectAxe()
  })
  accessibilityTestRunner()
})
