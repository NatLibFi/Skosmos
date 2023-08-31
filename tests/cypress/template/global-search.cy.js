describe('Global search page', () => {
  it('contains search result info', () => {
      cy.visit('/yso/fi/search?clang=fi&q=kissa')

      //Check that there are at least some search results
      cy.get('.search-count > p > span').invoke('text')

      .then(text => {
        const searchCount = text.charAt(0);
        cy.wrap(searchCount).then(parseFloat).should('be.gt', 0)
      });
  })
})
