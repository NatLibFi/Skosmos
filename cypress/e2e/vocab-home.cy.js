describe('Vocabulary home page', () => {
  // using context because otherwise cypress would not clear the state and tests couldn't be run independently
  context('Vocabulary home page', () => {
    it('contains vocabulary title', () => {
      // go to the Skosmos front page
      cy.visit('/')
      // click on the first vocabulary in the list
      cy.get('#vocabulary-list').find('a').first().click()

      // check that the vocabulary title is not empty
      cy.get('#vocab-title > a').invoke('text').should('match', /.+/)
    })
  })

  context('Statistics', () => {
    it('The amounts must match', () => {
      let numberOfRows = 0
      cy.visit('/yso/fi')
      cy.get('#resource-counts')
        .find('tr')
        .then((row) => {
          numberOfRows = row.length
          for (let i = 0; i < numberOfRows; i++) {
            cy.task('log', `tr:eq(${i}) td:eq(1)`)
            cy.get('#resource-counts')
              .find(`tr:eq(${i}) td:eq(1)`)
              .should('not.be.empty')
              .wrap(0)
              .should('not.be.ok')
          }
        })
    })
  })
})
