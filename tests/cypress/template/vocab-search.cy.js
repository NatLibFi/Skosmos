describe('Vocabulary search page', () => {
  const vocab = 'test';
  const term = 'bass';
  it('Contains title and title metadata', () => {
      cy.visit(`/${vocab}/en/search?clang=en&q=${term}`)

      const expectedTitle = "'bass' - Test short - Skosmos being tested"
      // check that the page has a HTML title
      cy.get('title').invoke('text').should('equal', expectedTitle)
      // check that the page has title metadata
      cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
      cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains site name metadata', () => {
      cy.visit(`/${vocab}/en/search?clang=en&q=${term}`)

      const expectedSiteName = 'Skosmos being tested'
      // check that the page has site name metadata
      cy.get('head meta[property="og:site_name"]').should('have.attr', 'content', expectedSiteName);
  })
  it('Contains canonical URL metadata', () => {
      cy.visit(`/${vocab}/en/search?clang=en&q=${term}`)

    const expectedUrl = Cypress.config('baseUrl') + `${vocab}/en/search?clang=en&q=${term}`
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl);
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl);
  })
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
  it('Long result lines are truncated', () => {
      cy.visit(`/yso/en/search?clang=en&q=euro`)
      const foreignLabels = cy.get('ul.list-group li').eq(2)
      const more = foreignLabels.find('a')

      // The foreign labels element should end in anchor tag
      more.should('have.text', '... (3)')

      // When the said anchor tag is clicked, it disappears
      more.click()
      more.should('not.exist')
  })
})
