describe('Concept page', () => {
  it('contains concept preflabel', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check that the vocabulary title is correct
    cy.get('#vocab-title > a').invoke('text').should('equal', 'YSO - General Finnish ontology (archaeology)')

    // check the concept prefLabel
    cy.get('#concept-heading h1').invoke('text').should('equal', 'music research')
  })
  it('contains concept type', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check the property name
    cy.get('.prop-rdf_type .property-label').invoke('text').should('equal', 'Type')

    // check the concept type
    cy.get('.prop-rdf_type .property-value a').invoke('text').should('equal', 'General concept')
  })
  it('contains definition in Finnish', () => {
    cy.visit('/yso/en/page/p21685?clang=fi') // go to "music research" concept page (Finnish content language)

    // check the property name
    cy.get('.prop-skos_definition .property-label').invoke('text').should('equal', 'Definition')

    // check the definition text
    cy.get('.prop-skos_definition .property-value li').invoke('text').should('contain', 'Musiikin ja musiikin harjoittamisen systemaattinen tutkiminen niiden kaikissa ilmenemismuodoissa.')
  })
  it("doesn't contain definition in English", () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page (English content language)

    // check that there is no definition on the page
    cy.get('.prop-skos_definition').should('not.exist')
  })
  it.skip('contains reified definition', () => {
    cy.visit('/test/en/page/ta122') // go to "Black sea bass" concept page

    // check the property name
    cy.get('.prop-skos_definition .property-label').invoke('text').should('equal', 'Definition')

    // check the definition text
    cy.get('.prop-skos_definition .reified-property-value').invoke('text').should('contain', 'The black sea bass')
  })

  it('contains broader concept', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check the property name
    cy.get('.prop-skos_broader .property-label').invoke('text').should('equal', 'Broader concept')

    // check the broader concept
    cy.get('.prop-skos_broader .property-value a').invoke('text').should('equal', 'research')
  })
  it('contains concept URI', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check the property name
    cy.get('.prop-uri .property-label').invoke('text').should('equal', 'URI')

    // check the broader concept
    cy.get('#concept-uri').invoke('text').should('equal', 'http://www.yso.fi/onto/yso/p21685')
  })
})
