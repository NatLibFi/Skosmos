import { accessibilityTestRunner } from '../../support/accessibility.js'

describe('This is used to avoid unnecessary redundancy of code', () => {
  before(() => {
    cy.visit('/fi/feedback')
    cy.injectAxe()
  })

  accessibilityTestRunner()
})
