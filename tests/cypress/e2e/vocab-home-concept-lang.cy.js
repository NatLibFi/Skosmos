describe('Vocab home page -> concept page -> change lang', () => {
  before(() => {
    cy.visit('/yso/en');
  });

  it('vocabulary home, concept page, change clang', () => {
    // click on the link "acids" (should trigger partial page load)
    cy.get('#tab-alphabetical').contains('a', 'acids').click()

    // Confirm that we are on the correct page (acids, English)
    cy.url().should('include', '/yso/en/page/p6514');

    // Change the UI language to Finnish by clicking on the language link
    cy.get('#topbar-nav').contains('a', 'suomeksi').click()

    // Confirm that we are on the correct page (hapot, Finnish)
    cy.url().should('include', '/yso/fi/page/p6514');
  });
});
