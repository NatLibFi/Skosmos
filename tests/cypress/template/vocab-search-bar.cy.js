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

  it('No results are displayed for autocomplete if there is not at leas two charecters in the search term', () => {
    // go to YSO vocab front page
    cy.visit('/yso/en/')

    cy.get('#search-field').type('k'); // even if the search yields no results, there shoulde a single line in the result list
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('not.be.visible');
  })

  it('The autocomplete list should not change due to previous searches completing', () => {
    //No-op placeholder
  })

  it('Clear button should hide the autocomplete list', () => {
    // go to YSO vocab front page
    cy.visit('/yso/en/')

    cy.get('#search-field').type('kas');
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

    cy.get('#clear-button').click()
    cy.get('#search-autocomplete-results').should('not.be.visible'); // the autocomplete should disappear
  })

  it('Emptying the text search field hides the autocomplete list', () => {
    //No-op placeholder
  })

  it('Clicking outside of the autocomplete list hides the autocomplete list', () => {
    // go to YSO vocab front page
    cy.visit('/yso/en/')

    cy.get('#search-field').type('kas');
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

    cy.get('#main-container').click({ force: true }); // using force true to click on elements not considered actionable
    cy.get('#search-autocomplete-results').should('not.be.visible'); // the autocomplete should disappear
  })

  it('AltLabel search results should bold the matching parts of altLabel and prefLabel', () => {
    //No-op placeholder
  })
})
