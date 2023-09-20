describe('About page', () => {
  it('Contains version number information', () => {
    // go to the Skosmos about page
    cy.visit('/en/about')
    // check if the page contains "Mikan testiteksti"
    cy.contains('Mikan testiteksti')
    // check that the version information should mention it's Skosmos and something that looks like version number
    cy.get('#version > p').invoke('text').should('match', /.*Skosmos.*[0-9]/)
  })
})
