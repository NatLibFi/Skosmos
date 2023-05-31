import { accessibilityTestRunner } from '../../support/accessibility.js'

describe('Check accessibility of the landing page', () => {
  before(() => {
    cy.visit('/')
    cy.injectAxe()
  })

  accessibilityTestRunner()
})
