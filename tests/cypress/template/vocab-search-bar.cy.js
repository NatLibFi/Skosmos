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
})
