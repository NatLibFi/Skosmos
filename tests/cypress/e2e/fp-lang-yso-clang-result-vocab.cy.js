describe('Front page -> lang -> vocab -> cLang -> search -> concept page', () => {
  beforeEach(() => {
    cy.visit('/fi');
    cy.wait(3000);
  });

  it('should change UI language to English and navigate to YSO concept page', () => {
    // Change the UI language to English from the top bar
    cy.visit('/en');

    // Select "YSO - Yleinen suomalainen ontologia (arkeologia)" from the vocabulary list
    // Go to the vocab page
    cy.contains('.list-group-item a', 'YSO - Yleinen suomalainen ontologia (arkeologia)')
      .click();

    // Confirm that we are on the correct page (yso)
    cy.url().should('include', '/yso/en/');
  });

  it('should perform a search fuction and go to the search result page', () => {
    // This following be replaced with actual implementation once available
    cy.visit('/yso/fi/search?query=tiede');

    // Potentially wait for a long time (10-20 seconds)
    cy.wait(10000);
  });

  it('should select the text "tiede" from the search results and navigate to the concept page', () => {
    cy.visit('/yso/fi/search?query=tiede');
    // Potentially wait for a long time again (10-20 seconds)
    cy.wait(10000);

    // Select the text 'tiede' from the list and go to the page the link is directing
    cy.get('.search-result-term a.prefLabel').contains('tiede')
      .click();

    // Confirm that we are on the correct page
    cy.url().should('include', '/yso/fi/page/p2240');

    // The page should contains the text "tiede"
    cy.get('#pref-label').should('have.text', 'tiede');
  });
});

