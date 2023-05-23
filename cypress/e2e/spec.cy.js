describe('template spec', () => {
  it('passes', () => {
    // go to the Skosmos front page
    cy.visit('http://localhost/Skosmos')
    // click on the first vocabulary in the list
    cy.get('#vocabulary-list').find('a').first().click()
    // check that we are still within the Skosmos URL space
    cy.url().should('include', 'http://localhost/Skosmos/')
    // check that we are on the vocab home page
    cy.get('#vocab-info')
  })
})
