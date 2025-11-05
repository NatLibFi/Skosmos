describe('About page', () => {
  it('Contains title metadata', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')

    const expectedTitle = 'About - Skosmos being tested'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains description metadata', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')

    const expectedDescription = 'About page for Skosmos being tested'
    // check that the page has description metadata
    cy.get('head meta[name="description"]').should('have.attr', 'content', expectedDescription);
    cy.get('head meta[property="og:description"]').should('have.attr', 'content', expectedDescription);
  })
  it('Contains site name metadata', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')

    const expectedSiteName = 'Skosmos being tested'
    // check that the page has site name metadata
    cy.get('head meta[property="og:site_name"]').should('have.attr', 'content', expectedSiteName);
  })
  it('Contains canonical URL metadata', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')

    const expectedUrl = Cypress.config('baseUrl') + 'en/about'
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl);
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl);
  })
  it('Contains custom template content', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')
    // check that the about slot contains content from custom-templates/about/0-testing.twig
    cy.get('#about-slot').invoke('text').should('include', 'This is a Skosmos instance for automated tests')
  })
  it('Contains version number information', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')
    // check that the version information should mention it's Skosmos and something that looks like version number
    cy.get('#version > p').invoke('text').should('match', /.*Skosmos.*[0-9]/)
  })
})
