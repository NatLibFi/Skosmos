describe('Front page -> lang -> vocab -> cLang -> search -> concept page', () => {
  before(() => {
    cy.visit('/fi');
  });

  it('should change UI language to English, select YSO from the list and then navigate to YSO concept page', () => {
    // Click on the language "button" to switch UI lang to English
    cy.get('#language-en').click();

    // Select "YSO - General Finnish ontology (archaeology)" from the vocabulary list and go to the vocab page
    cy.contains('.list-group-item a', 'YSO - General Finnish ontology (archaeology)').click();

    // Confirm that we are on the correct page (yso)
    cy.url().should('include', '/yso/en/');

    // Open the language drop-down and select "fi" for the search language
    cy.get('#language-selector button').click();
    cy.contains('#language-list li a', 'Finnish').click();

    // Write a search term "arkeologia" and press enter
    cy.get('#search-field').type('arkeolog').type('{enter}');

    // Check that the search result page contains 5 results
    cy.get('.search-result').should('have.length', 5);

    // Check that the search result page contains "kaivaukset"
    cy.get('.search-result-term a').should('include.text', 'kaivaukset');

    // Select "kaivaukset"
    cy.contains('.search-result-term a', 'kaivaukset').click();

    // Confirm that we are on the correct page (kaivaukset) and language (fi)
    cy.url().should('include', '/yso/en/page/p14173?clang=fi');

    // Confirm that the prefLabel on the page is "kaivaukset"
    cy.get('h1').should('have.text', 'kaivaukset');
  });
});
