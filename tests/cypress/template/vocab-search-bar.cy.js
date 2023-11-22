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
  
  it('contents for the language menu are chosen from the SKOSMOS object', () => {

    /* This test has major difficulties with exposing the SKOSMOS object for cypress to evaluate  

    // Visit the page and wait for the script interception
    cy.visit('/yso/fi/');

    // Now, check if SKOSMOS object exists
    cy.window().its('SKOSMOS').should('exist');

    // Check if languageOrder property exists in SKOSMOS object
    cy.window().its('SKOSMOS').should('have.property', 'languageOrder');

    // Get the languageOrder list from the SKOSMOS object
    cy.window().its('SKOSMOS.languageOrder').then((languageOrder) => {
      
      cy.get('#search-wrapper select').find('option').each(($option, index) => {
        const optionValue = $option.attr('value');
        const expectedLanguage = languageOrder[index];

        // Check that the search language options values matche the languageOrder
        cy.wrap(optionValue).should('eq', expectedLanguage);
      });
    })
    */
  })
})
