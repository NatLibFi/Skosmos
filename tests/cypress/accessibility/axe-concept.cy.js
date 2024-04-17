// When you start creating tests, remove the following comment(s).
import { accessibilityTestRunner } from '../support/accessibility.js'
import 'cypress-axe'

/* If you want the test to be skipped, add a skip command after the describe part:
    - test enabled: describe('Check accessibility of ...
    - test to be skipped: describe.skip('Check accessibility of ... */
describe('Check accessibility of the concept page', () => {
  before(() => {
    cy.visit('yso/fi/page/p4623')
    cy.injectAxe()
  })

  accessibilityTestRunner()
})
