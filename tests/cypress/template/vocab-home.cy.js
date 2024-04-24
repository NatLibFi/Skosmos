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

    // click on the the letter C
    cy.get('#tab-alphabetical').contains('a', 'C').click()

    // click on the link "care institutions" (should trigger partial page load)
    cy.get('#tab-alphabetical').contains('a', 'care institutions').click()

    // check the concept prefLabel
    cy.get('#concept-heading h1').invoke('text').should('equal', 'care institutions')

    // check that the SKOSMOS object matches the newly loaded concept
    cy.window().then((win) => {
      expect(win.SKOSMOS.uri).to.equal('http://www.yso.fi/onto/yso/p6034');
      expect(win.SKOSMOS.prefLabels[0]['label']).to.equal("care institutions");
    })

    // check that we have some mappings
    cy.get('#concept-mappings').should('not.be.empty')

    // check the second mapping property name
    cy.get('.prop-mapping h2', {'timeout': 15000}).eq(0).contains('Exactly matching concepts')
    // check the second mapping property values
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).contains('vårdinrättningar (sv)')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').invoke('text').should('equal', 'vårdinrättningar')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').should('have.attr', 'href', 'http://www.yso.fi/onto/allars/Y29009')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-vocab').eq(0).contains('Allärs - General thesaurus in Swedish')
    // skipping the middle one (mapping to KOKO concept) as it's similar to the others
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(2).contains('hoitolaitokset (fi)')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(2).find('a').invoke('text').should('equal', 'hoitolaitokset')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(2).find('a').should('have.attr', 'href', 'http://www.yso.fi/onto/ysa/Y95404')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-vocab').eq(2).contains('YSA - Yleinen suomalainen asiasanasto')
    // check that the second mapping property has the right number of entries
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').should('have.length', 3)
  })
  it('clicking on hierarchy entries performs partial page load', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // open the hierarchy tab
    cy.get('#hierarchy a').click()

    // click on the link "Fish" (should trigger partial page load)
    cy.get('#tab-hierarchy').contains('a', 'Fish').click()

    // check the concept prefLabel
    cy.get('#concept-heading h1').invoke('text').should('equal', 'Fish')

    // check that the SKOSMOS object matches the newly loaded concept
    cy.window().then((win) => {
      expect(win.SKOSMOS.uri).to.equal('http://www.skosmos.skos/test/ta1');
      expect(win.SKOSMOS.prefLabels[0]['label']).to.equal("Fish");
    })

    // check that we have some mappings
    cy.get('#concept-mappings').should('not.be.empty')

    // check the second mapping property name
    cy.get('.prop-mapping h2', {'timeout': 15000}).eq(0).contains('Exactly matching concepts')
    // check the second mapping property values
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').invoke('text').should('equal', 'fish')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').should('have.attr', 'href', 'http://www.wikidata.org/entity/Q152')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-vocab').eq(0).contains('www.wikidata.org')

    // check that the second mapping property has the right number of entries
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').should('have.length', 1)
  })
})
