describe('Concept page', () => {
  it('contains concept preflabel', () => {
    cy.visit('/test/en/page/ta122') // go to "Black sea bass" concept page

    // check that the vocabulary title is correct
    cy.get('#vocab-title > a').invoke('text').should('equal', 'Test ontology')

    // check the concept prefLabel
    cy.get('#pref-label').invoke('text').should('equal', 'Black sea bass')
  })
  it('contains concept type', () => {
    cy.visit('/test/en/page/ta122') // go to "Black sea bass" concept page

    // check the property name
    cy.get('.prop-rdf_type .main-table-label').invoke('text').should('equal', 'Type')

    // check the concept type
    cy.get('.prop-rdf_type .align-middle > p').invoke('text').should('equal', 'Test class')
  })
  it('contains definition', () => {
    cy.visit('/test/en/page/ta122') // go to "Black sea bass" concept page

    // check the property name
    cy.get('.prop-skos_definition .main-table-label').invoke('text').should('equal', 'Definition')

    // check the definition text
    cy.get('.prop-skos_definition .reified-property-value').invoke('text').should('contain', 'The black sea bass')
  })
  it('contains broader concept', () => {
    cy.visit('/test/en/page/ta122') // go to "Black sea bass" concept page

    // check the property name
    cy.get('.prop-skos_broader .main-table-label').invoke('text').should('equal', 'Broader concept')

    // check the broader concept
    cy.get('.prop-skos_broader .align-middle a').invoke('text').should('equal', 'Bass')
  })
  it('contains concept URI', () => {
    cy.visit('/test/en/page/ta122') // go to "Black sea bass" concept page

    // check the property name
    cy.get('.prop-uri .main-table-label').invoke('text').should('equal', 'URI')

    // check the broader concept
    cy.get('.prop-uri .align-middle').invoke('text').should('equal', 'http://www.skosmos.skos/test/ta122')
  })
})
