describe('Error page', () => {
  it('Contains title and title metadata', () => {
    // go to a non-existing page
    cy.visit('/404', {failOnStatusCode: false})

    const expectedTitle = 'Error - Skosmos being tested'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains 404 error code', () => {
    // check that HTTP code is 404 when accessing a non-existing page
    cy.request({url: '/404', failOnStatusCode: false}).its('status').should('equal', 404)
    // go to a non-existing page
    cy.visit('/404', {failOnStatusCode: false})
    // check that the page contains 404 error code
    cy.get('.alert span').invoke('text').should('contain', '404 Error')
  })
})
