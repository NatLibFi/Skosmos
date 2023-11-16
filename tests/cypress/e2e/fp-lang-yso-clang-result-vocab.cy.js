describe('Front page -> lang -> vocab -> cLang -> search -> concept page', () => {
  beforeEach(() => {
    cy.visit('/fi');
    cy.wait(3000);
  });

  it('should change UI language to English and navigate to YSO concept page', () => {
    // Click on the language "button" to switch to English
    cy.get('#language-en').click();
    cy.wait(2000);

    // Select "YSO - Yleinen suomalainen ontologia (arkeologia)" from the vocabulary list
    // Go to the vocab page
    cy.contains('.list-group-item a', 'YSO - Yleinen suomalainen ontologia (arkeologia)')
      .click();
  
    // Confirm that we are on the correct page (yso)
    cy.url().should('include', '/yso/en/');
    cy.wait(2000);
  });
  
  it('should change the language to Swedish and confirm the change', () => {
    // Targeting to https://finto.fi
    cy.origin('https://finto.fi', () => {
      cy.visit('/yso/fi/');
      cy.wait(2000);
      // Open the language selector
      cy.get('#lang-dropdown-toggle').click();
      cy.wait(2000);
      // Select "ruotsi" from the dropdown list
      cy.get('.dropdown-menu').contains('ruotsi').click();
      cy.wait(2000);
      // Confirm that the URL has changed and it inculdes "?clang=sv"
      cy.url().should('include', 'https://finto.fi/yso/fi/?clang=sv');
      cy.wait(2000);
    });
  });  
  
  it('should perform a search function and go to the search result page', () => {
    // The following will be replaced with the actual implementation once available
    cy.visit('/yso/fi/search?query=tiede');

    // Potentially wait for a long time (10-20 seconds)
    cy.wait(10000);
    cy.url().should('include', '/yso/fi/search?query=tiede');
  });

  it('should select the text "tiede" from the search results and navigate to the concept page', () => {
    cy.visit('/yso/fi/search?query=tiede');
    cy.wait(10000);

    // Select the text 'tiede' from the list and go to the page the link is leads to
    cy.get('.search-result-term a.prefLabel').contains('tiede')
      .click();

    // Confirm that we are on the correct page
    cy.url().should('include', '/yso/fi/page/p2240');

    // The page should contain the text "tiede"
    cy.get('#pref-label').should('have.text', 'tiede');
  });
});
