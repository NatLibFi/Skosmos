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
  it('Search field contains the search query', () => {
      cy.visit(`/${vocab}/en/search?clang=en&q=${term}`)

      // The search field should be pre-filled with the query from the URL
      cy.get('#search-field').should('have.value', term)
  })
  it('Contains correct amount of search results ', () => {
      const count = 1;
      const searchCountTitle = `${count} results for \'${term}\'`;
      cy.visit(`/${vocab}/en/search?clang=en&q=${term}`)

      //Check that the search count is correct
      cy.get('#search-results > h1').invoke('text').should('contain', searchCountTitle);

      //Check that search count matches the number of results
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
  it('More results are loaded on scroll', () => {
    cy.visit(`/yso/en/search?clang=en&q=an`)
    // Check that there are 5 search results
    cy.get('#search-results').find('.search-result').should('have.length', 5)

    // Listen to the API call
    cy.intercept('GET', '/yso/en/search?clang=en&q=an*').as('search')

    // The scroll handler attaches via onTranslationReady(), which runs after the
    // async translation fetch resolves. Wait for translations to be ready first so
    // the listener is attached before we scroll; otherwise scrollTo happens with no
    // listener present and never dispatches a 'scroll' event, so no request fires.
    cy.window().then(win => new Promise(resolve => {
      win.onTranslationReady(() => resolve())
    }))

    cy.scrollTo('bottom')

    // Wait for the API call to finish
    cy.wait('@search', { timeout: 20000 })

    // Check that there are 6 search results
    cy.get('#search-results').find('.search-result').should('have.length', 6)
    // Check that all results message is displayed
    cy.get('#search-count').invoke('text').should('contain', 'All 6 results displayed')

  })
})
