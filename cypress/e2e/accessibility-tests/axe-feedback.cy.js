import { accessibilityTestRunner } from '../../support/accessibility.js'

describe('Check accessibility of the feedback page', () => {
  before(() => {
    cy.visit('/fi/feedback')
    cy.injectAxe()
  })

  accessibilityTestRunner()
})
