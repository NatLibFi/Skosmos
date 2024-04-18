describe('About page', () => {
  it('Contains title and title metadata', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')

    const expectedTitle = 'About - Skosmos being tested'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[name="twitter:title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains version number information', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')
    // check that the version information should mention it's Skosmos and something that looks like version number
    cy.get('#version > p').invoke('text').should('match', /.*Skosmos.*[0-9]/)
  })
})
