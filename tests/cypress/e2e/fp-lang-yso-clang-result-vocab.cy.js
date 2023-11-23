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
    cy.contains('.list-group-item a', 'YSO - Yleinen suomalainen ontologia (arkeologia)').click();
    cy.wait(1000);

    // Confirm that we are on the correct page (yso)
    cy.url().should('include', '/yso/en/');
    cy.wait(1000);
  });

  it('should select "fi" language (only for the search) and perform a search', () => {
    // Land on the page /yso/en/
    cy.visit('/yso/en/');
    cy.wait(1000);

    // Open the language drop-down and select "fi"
    cy.get('.btn.btn-outline-secondary.dropdown-toggle').select('fi');
    cy.wait(1000);

    // Write a search term "arkeologia" and press enter
    cy.get('.form-control').type('arkeologia').type('{enter}');
    cy.wait(3000);

    // Check that the search result page contains "arkeologia"
    cy.get('.search-result-term a').should('include.text', 'arkeologia');
  });
  
});
