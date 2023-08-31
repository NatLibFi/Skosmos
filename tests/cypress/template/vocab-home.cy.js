describe('Vocabulary home page', () => {
  it('contains vocabulary title', () => {
    // go to the Skosmos front page
    cy.visit('/')
    // click on the first vocabulary in the list
    cy.get('#vocabulary-list').find('a').first().click()
    // check that the vocabulary title is not empty
    cy.get('#vocab-title > a').invoke('text').should('match', /.+/)
  })
})
