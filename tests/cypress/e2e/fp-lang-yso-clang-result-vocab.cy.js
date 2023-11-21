describe('Front page -> lang -> vocab -> cLang -> search -> concept page', () => {
  before(() => {
    cy.visit('/fi');
    cy.wait(3000);
  });

  it('should change UI language to English, select YSO from the list and then navigate to YSO concept page', () => {
    // Click on the language "button" to switch UI lang to English
    cy.get('#language-en').click();
    cy.wait(1000);

    // Select "YSO - Yleinen suomalainen ontologia (arkeologia)" from the vocabulary list
    // Go to the vocab page
    cy.contains('.list-group-item a', 'YSO - Yleinen suomalainen ontologia (arkeologia)')
      .click();
  
    // Confirm that we are on the correct page (yso)
    cy.url().should('include', '/yso/en/');
    cy.wait(1000);
  });
  
  it('should change the content language to Finnish and confirm the change', () => {
    // Targeting to https://finto.fi
    cy.origin('https://finto.fi', () => {
      cy.visit('/yso/en/');
      cy.wait(2000);
      // Open the language selector
      cy.get('#lang-dropdown-toggle').click();
      cy.wait(2000);
      // Select "Swedish" from the dropdown list
      cy.get('.dropdown-menu').contains('Finnish').click();
      cy.wait(2000);
      // Confirm that the URL has changed and it inculdes "?clang=sv"
      cy.url().should('include', 'https://finto.fi/yso/en/?clang=fi');
      cy.wait(2000);
    });
  });  
  
  it('should make a search and then select the text "science" from the search results finally navigate to the concept page', () => {
    cy.visit('/yso/en/search?query=science');
    cy.wait(10000);

    // Select the text 'tiede' from the list and go to the page the link is leads to
    cy.get('.search-result-term a.prefLabel').contains('science')
      .click();

    // Confirm that we are on the correct page
    cy.url().should('include', '/yso/en/page/p2240');

    // The page should contain the text "tiede"
    cy.get('#pref-label').should('have.text', 'science');
    cy.wait(5000);
  });
});
