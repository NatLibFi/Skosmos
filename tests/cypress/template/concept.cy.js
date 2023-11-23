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
    cy.get('.prop-rdf_type .property-value li').invoke('text').should('equal', 'General concept')
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
  it('contains narrower concepts', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check the property name
    cy.get('.prop-skos_narrower .property-label').invoke('text').should('equal', 'Narrower concepts')

    // check that we have the correct number of narrower concepts
    cy.get('.prop-skos_narrower .property-value').find('li').should('have.length', 8)
  })
  it('contains related concepts', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check the property name
    cy.get('.prop-skos_related .property-label').invoke('text').should('equal', 'Related concepts')

    // check that we have the correct number of related concepts
    cy.get('.prop-skos_related .property-value').find('li').should('have.length', 3)
  })
  it('contains altLabels (entry terms)', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check the property name
    cy.get('.prop-skos_altLabel .property-label').invoke('text').should('equal', 'Entry terms')

    // check that we have the correct number of altLabels
    cy.get('.prop-skos_altLabel .property-value').find('li').should('have.length', 1)

    // check the altLabel value
    cy.get('.prop-skos_altLabel .property-value li').invoke('text').should('equal', 'musicology (research activity)')
  })
  it('contains groups', () => {
    cy.visit('/yso/en/page/p38289') // go to "music archaeology" concept page

    // check the property name
    cy.get('.prop-skosmos_memberOf .property-label').invoke('text').should('equal', 'Belongs to group')

    // check that we have the correct number of groups
    cy.get('.prop-skosmos_memberOf .property-value').find('li').should('have.length', 1)

    // check the first group value
    cy.get('.prop-skosmos_memberOf .property-value a').invoke('text').should('equal', '51 Archaeology')
  })
  it('contains terms in other languages', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check the property name
    cy.get('.prop-foreignlabels .property-label').invoke('text').should('equal', 'In other languages')

    // check that we have the correct number of languages
    cy.get('#concept-other-languages').find('.row').should('have.length', 3)
  })
  it('contains concept URI', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check the property name
    cy.get('.prop-uri .property-label').invoke('text').should('equal', 'URI')

    // check the broader concept
    cy.get('#concept-uri').invoke('text').should('equal', 'http://www.yso.fi/onto/yso/p21685')
  })
  it('contains mappings', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check that we have some mappings
    cy.get('#concept-mappings').should('not.be.empty')

    // check the first mapping property name
    cy.get('.prop-mapping h2').eq(0).contains('Closely matching concepts')
    // check the first mapping property values
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).contains('Musicology')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').should('have.attr', 'href', 'http://id.loc.gov/authorities/subjects/sh85089048')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-vocab').eq(0).contains('Library of Congress Subject Headings')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(1).contains('musicology')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(1).find('a').should('have.attr', 'href', 'http://www.wikidata.org/entity/Q164204')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-vocab').eq(1).contains('www.wikidata.org')
    // check that the second mapping property has the right number of entries
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').should('have.length', 2)

    // check the second mapping property name
    cy.get('.prop-mapping h2').eq(1).contains('Exactly matching concepts')
    // check the second mapping property values (only one should be enough)
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(0).contains('musiikintutkimus (fi)')
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(0).find('a').invoke('text').should('equal', 'musiikintutkimus')
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(0).find('a').should('have.attr', 'href', 'http://www.yso.fi/onto/ysa/Y155072')
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-vocab').eq(0).contains('YSA - Yleinen suomalainen asiasanasto')
    // check that the second mapping property has the right number of entries
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').should('have.length', 3)
  })
})
