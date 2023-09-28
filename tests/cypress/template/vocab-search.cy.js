describe('Vocabulary search page', () => {
      const vocab = 'test';
      const term = 'bass';
  it('Contains correct amount of search results ', () => {
      const count = 1;
      const searchCountTitle = `${count} results for \'${term}\'`;
      cy.visit(`/${vocab}/en/search?clang=en&q=${term}`)

      //Check that the search count is correct
      cy.get('.search-count > p > span').invoke('text').should('contain', searchCountTitle);

      //Check that search count matces the number of results
      cy.get('div.search-result').should('have.length', count)

  })
  it('Search results contains correct info', () => {
      cy.visit(`/${vocab}/en/search?clang=en&q=${term}`)

      //Check that there is a search result that contains a type icon
      cy.get('div.search-result > ul > li > span > i.property-hover.fa-solid.fa-arrows-to-circle')

      //Check that there is correct amount of different properties for the search result
      cy.get('div.search-result > ul > li').should('have.length', 3)

      //Check the order of search result properties
      cy.get('div.search-result > ul').within(() => {
        cy.get('li').eq(0).invoke('text').should('contain', 'Fish')
        cy.get('li').eq(1).invoke('text').should('contain', 'Test class')
        cy.get('li').eq(2).invoke('text').should('contain', 'http://www.skosmos.skos/test/ta116')
      })

  })
})
