import { accessibilityTestRunner } from '../../support/accessibility.js'

describe('Check accessibility of the about page', () => {
  before(() => {
    cy.visit('/fi/about')
    cy.injectAxe()
  })

  accessibilityTestRunner()
})
