import { accessibilityTestRunner } from '../../support/accessibility.js'

describe('Check accessibility of the vocab-home page', () => {
  before(() => {
    cy.visit('/agrovoc/fi/')
    cy.injectAxe()
  })

  accessibilityTestRunner()
})
