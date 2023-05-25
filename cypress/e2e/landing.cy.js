import { checkA11y } from '../support/accessibility.js'

describe('Landing page', () => {
  it('links to vocabulary home', () => {
    // go to the Skosmos front page
    cy.visit('/')
    // click on the first vocabulary in the list
    cy.get('#vocabulary-list').find('a').first().click()
    // check that we are still within the Skosmos URL space
    cy.url().should('include', Cypress.config().baseUrl)
    // check that we are on the vocab home page
    cy.get('#vocab-info')
  })
})
