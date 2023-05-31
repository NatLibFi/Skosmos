import { accessibilityTestRunner } from '../../support/accessibility.js'

describe('Check accessibility of the vocab page', () => {
  before(() => {
    cy.visit('/juho/fi')
    cy.injectAxe()
  })

  accessibilityTestRunner()
})
