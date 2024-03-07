describe('Vocabulary home page', () => {
  it('contains vocabulary title', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // check that the vocabulary title is correct
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

    const entries = cy.get('#tab-alphabetical .sidebar-list .list-group').children()

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
    cy.get('#tab-alphabetical .sidebar-list .list-group').children().should('have.length', 2)

    // check that the first entry is Carp
    cy.get('#tab-alphabetical .sidebar-list .list-group').children().first().invoke('text').should('equal', 'Carp')
  })
  it('alphabetical index diacritic letters are clickable', () => {
    cy.visit('/yso/sv/') // go to the YSO home page in Swedish language

    // click on the last letter (Ö)
    cy.get('#tab-alphabetical .pagination :nth-last-child(1) > .page-link').click()

    // check that we have the correct number of entries
    cy.get('#tab-alphabetical .sidebar-list .list-group').children().should('have.length', 4)

    // check that the first entry is "östliga handelsvägar"
    cy.get('#tab-alphabetical .sidebar-list .list-group').children().first().children().first().invoke('text').should('equal', 'östliga handelsvägar')
  })
  it('clicking on alphabetical index entries performs partial page load', () => {
    cy.visit('/yso/en/') // go to the YSO home page in English language

    // click on the third letter (C)
    cy.get('#tab-alphabetical .pagination :nth-child(3) > .page-link').click()

    // check that the first entry is "care institutions"
    cy.get('#tab-alphabetical .sidebar-list').children().first().invoke('text').should('equal', 'care institutions')

    // click on the first entry (should trigger partial page load)
    cy.get('#tab-alphabetical .sidebar-list').children().first().click()

    // check the concept prefLabel
    cy.get('#concept-heading h1').invoke('text').should('equal', 'care institutions')

    // check that concept mappings is not empty
    cy.get('#concept-mappings').should('not.be.empty')
  })
  it('clicking on hierarchy entries performs partial page load', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // open the hierarchy tab
    cy.get('#hierarchy a').click()

    // check that the first entry is "Fish"
    cy.get('#tab-hierarchy .sidebar-list a').invoke('text').should('equal', 'Fish')

    // click on the link (should trigger partial page load)
    cy.get('#tab-hierarchy .sidebar-list a').click()

    // check the concept prefLabel
    cy.get('#concept-heading h1').invoke('text').should('equal', 'Fish')
  })
})
