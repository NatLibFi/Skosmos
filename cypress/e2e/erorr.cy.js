describe('Error page', () => {
  it('Contains 404 error code', () => {
    // check that HTTP code is 404 when accessing a non-existing page
    cy.request({url: '/404', failOnStatusCode: false}).its('status').should('equal', 404)
    // go to a non-existing page
    cy.visit('/404', {failOnStatusCode: false})
    // check that the page contains 404 error code
    cy.get('.alert h3').invoke('text').should('contain', '404 Error') 
  })
})
  