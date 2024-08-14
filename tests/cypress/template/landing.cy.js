describe('Landing page', () => {
  it('Contains title and title metadata', () => {
    // go to the Skosmos front page
    cy.visit('/')

    const expectedTitle = 'Skosmos being tested, long title'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
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
