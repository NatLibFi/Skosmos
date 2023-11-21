describe('Front page -> lang -> vocab -> cLang -> search -> concept page', () => {
  before(() => {
    cy.visit('/fi');
    cy.wait(2000);
  });

  it('should change UI language to English, select YSO from the list and then navigate to YSO concept page', () => {
    // Click on the language "button" to switch UI lang to English
    cy.get('#language-en').click();
    cy.wait(1000);

    // Select "YSO - Yleinen suomalainen ontologia (arkeologia)" from the vocabulary list and go to the vocab page
    cy.contains('.list-group-item a', 'YSO - Yleinen suomalainen ontologia (arkeologia)')
      .click();

    // Confirm that we are on the correct page (yso)
    cy.url().should('include', '/yso/en/');
    cy.wait(1000);
  });

  it('should change the content language to Finnish and confirm the change + make a search until the search result page is reached', () => {
    // Targeting to https://finto.fi
    cy.origin('https://finto.fi', () => {
      cy.visit('/yso/en/');
      cy.wait(2000);
      // Open the language selector
      cy.get('#lang-dropdown-toggle').click();
      cy.wait(2000);
      // Select "Finnish" from the dropdown list
      cy.get('.dropdown-menu').contains('Finnish').click();
      cy.wait(2000);
      // Confirm that the URL has changed and it inculdes "?clang=fi"
      cy.url().should('include', 'https://finto.fi/yso/en/?clang=fi');
      cy.wait(2000);

      // Search
      // 1. Find the search input field
      cy.get('#search-field').type('science');
      cy.wait(2000);
      // 2. Press enter
      cy.get('#search-field').type('{enter}');
      cy.wait(2000);
      // 3. Check that the page URL is as expected
      cy.url().should('eq', 'https://finto.fi/yso/en/search?clang=fi&q=science');
      cy.wait(4000);
    });
  });

  // Once the content language selection and search functions up to the enter search have been implemented, 
  // this part of the test can be partially moved (with relevant parts) into the language selection and search 
  // function test above as its final step

  it('should make a search and then select the text "science" from the search results finally navigate to the concept page', () => {
    cy.visit('/yso/en/search?query=science');
    cy.wait(5000);

    // Select the text 'science' from the list and go to the page the link is leads to
    cy.get('.search-result-term a.prefLabel').contains('science')
      .click();

    // Confirm that we are on the correct page
    cy.url().should('include', '/yso/en/page/p2240');

    // The page should contain the text "tiede"
    cy.get('#pref-label').should('have.text', 'science');
    cy.wait(3000);
  });
});
