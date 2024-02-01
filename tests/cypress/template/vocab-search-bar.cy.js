describe('Vocab search bar', () => {
  it('search can be done with a chosen content language', () => {
    // go to YSO vocab front page
    cy.visit('/yso/fi/')
    
    // Select an option from the dropdown
    cy.get('#search-wrapper select').select('sv');

    // Enter a search term
    cy.get('#search-wrapper input').type('Katt');

    // Click the search button
    cy.get('#search-button').click();

    //Verify the search page url (search result page tests are elsewhere)
    cy.url().should('include', 'search?q=Katt&clang=sv');

  })

  it('search can be done with all languages', () => {
    // go to YSO vocab front page
    cy.visit('/yso/fi/')
      
    // Choose 'all' languages
    cy.get('#search-wrapper select').select('all');

    // Enter a search term
    cy.get('#search-wrapper input').type('Katt');

    // Click the search button
    cy.get('#search-button').click();

    //Verify the search page url (search result page tests are elsewhere)
    cy.url().should('include', 'search?q=Katt');
    cy.url().should('include', 'anylang=on');
  })

  it('Writing in the text field triggers the autocomplete results list', () => {
    // go to YSO vocab front page
    cy.visit('/yso/fi/')

    cy.get('#search-field').type('kas'); // perform autocomplete search
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible').children().should('have.length.greaterThan', 4);
     cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').eq(0).click(); // results should be links that take to a Skosmos page
        cy.url().should('include', '/page/')
      })
  })

  it('No results message is displayed if no results are found', () => {
    // go to YSO vocab front page
    cy.visit('/yso/en/')

    cy.get('#search-field').type('kissa'); // even if the search yields no results, there shoulde a single line in the result list
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible').children().should('have.length.greaterThan', 0);
    cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').eq(0).invoke('text').should('contain', 'No results') // the single result should display a no results message
      })
  })

})
