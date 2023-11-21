describe('Vocabulary home page', () => {
  it('contains vocabulary title', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // check that the vocabulary title is not empty
    cy.get('#vocab-title > a').invoke('text').should('equal', 'Test ontology')
  })
  it('shows alphabetical index letters', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const letters = cy.get('#tab-alphabetical .pagination').children()

    // check that we have the correct number of letters
    letters.should('have.length', 8)

    // check that the first letter is B
    letters.first().invoke('text').should('equal', 'B')
  })
  it('shows alphabetical index entries', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const entries = cy.get('#tab-alphabetical .sidebar-list').children()

    // check that we have the correct number of entries
    entries.should('have.length', 3)

    // check that the first entry is Bass
    entries.first().invoke('text').should('equal', 'Bass')
  })
  it('alphabetical index letters are clickable', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // click on the second letter (C)
    cy.get('#tab-alphabetical .pagination :nth-child(2) > .page-link').click()

    // check that we have the correct number of entries
    cy.get('#tab-alphabetical .sidebar-list').children().should('have.length', 2)

    // check that the first entry is Carp
    cy.get('#tab-alphabetical .sidebar-list').children().first().invoke('text').should('equal', 'Carp')
  })
})
