describe('Concept page', () => {
  it("doesn't contain breadcrumbs for top concepts", () => {
    cy.visit('/yso/en/page/p4762') // go to "objects" concept page

    // check that there are no breadcrumbs on the page
    cy.get('#concept-breadcrumbs').should('not.exist')
  })
  it("contains unshortened breadcrumbs (up to 5 levels)", () => {
    cy.visit('/yso/en/page/p7347') // go to "ancient castles" concept page

    // check that there are breadcrumbs on the page
    cy.get('#concept-breadcrumbs').should('exist')

    // check that there is no expand link for breadcrumbs
    cy.get('.breadcrumb-toggle').should('not.exist')

    // check that there are 5 breadcrumb links
    cy.get('#concept-breadcrumbs ol').find('li').should('have.length', 5)

    // check the first breadcrumb
    cy.get('#concept-breadcrumbs ol li').first().invoke('text').should('equal', 'properties')

    // check the last breadcrumb
    cy.get('#concept-breadcrumbs ol li').last().invoke('text').should('equal', 'ancient castles')
    cy.get('#concept-breadcrumbs ol li a').last().should('have.class', 'breadcrumb-current')
  })
  it("contains shortened breadcrumbs (more than 5 levels)", () => {
    cy.visit('/yso/en/page/p1265') // go to "archaeology" concept page

    // check that there are breadcrumbs on the page
    cy.get('#concept-breadcrumbs').should('exist')

    // check that there is an expand link for breadcrumbs
    cy.get('.breadcrumb-toggle').should('exist')

    // check that there are 8 breadcrumb links
    cy.get('#concept-breadcrumbs ol').find('li').should('have.length', 8)

    // check that there are 2 hidden breadcrumb links
    cy.get('#concept-breadcrumbs ol').find('li.collapse').should('have.length', 2)

    // check that there are no breadcrumb links currently shown
    cy.get('#concept-breadcrumbs ol').find('li.show').should('have.length', 0)

    // check the first breadcrumb (expand link)
    cy.get('#concept-breadcrumbs ol li').first().invoke('text').should('equal', '...')

    // check the last breadcrumb
    cy.get('#concept-breadcrumbs ol li').last().invoke('text').should('equal', 'archaeology')
    cy.get('#concept-breadcrumbs ol li a').last().should('have.class', 'breadcrumb-current')

    // click the expand link
    cy.get('#concept-breadcrumbs ol li').first().click()

    // check that there are 2 breadcrumb links currently shown
    cy.get('#concept-breadcrumbs ol').find('li.show').should('have.length', 2)
  })
  it("contains shortened breadcrumbs (two different paths)", () => {
    cy.visit('/yso/en/page/p38289') // go to "music archaeology" concept page

    // check that there are breadcrumbs on the page
    cy.get('#concept-breadcrumbs').should('exist')

    // check that there are 2 sets of breadcrumbs
    cy.get('#concept-breadcrumbs').find('ol').should('have.length', 2)

    // check that there are 2 expand links for breadcrumbs
    cy.get('#concept-breadcrumbs').find('.breadcrumb-toggle').should('have.length', 2)

    // check that there are 4 hidden breadcrumb links
    cy.get('#concept-breadcrumbs ol').find('li.collapse').should('have.length', 4)

    // check that there are no breadcrumb links currently shown
    cy.get('#concept-breadcrumbs ol').find('li.show').should('have.length', 0)

    // check the first breadcrumb (expand link)
    cy.get('#concept-breadcrumbs ol li').first().invoke('text').should('equal', '...')

    // check the last breadcrumb
    cy.get('#concept-breadcrumbs ol li').last().invoke('text').should('equal', 'music archaeology')
    cy.get('#concept-breadcrumbs ol li a').last().should('have.class', 'breadcrumb-current')

    // click the expand link
    cy.get('#concept-breadcrumbs ol li').first().click()

    // check that there are 2 breadcrumb links currently shown
    cy.get('#concept-breadcrumbs ol').find('li.show').should('have.length', 4)
  })
  it('contains concept preflabel', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // check that the vocabulary title is correct
    cy.get('#vocab-title > a').invoke('text').should('equal', 'YSO - General Finnish ontology (archaeology)')

    // check the concept prefLabel
    cy.get('#concept-heading h1').invoke('text').should('equal', 'music research')
  })
  it('concept preflabel can be copied to clipboard', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // click the copy to clipboard button next to the prefLabel
    cy.get('#copy-preflabel').click()

    // check that the clipboard now contains "music research"
    // NOTE: This test may fail when running Cypress interactively in a browser.
    // The reason is browser security policies for accessing the clipboard.
    // If that happens, make sure the browser window has focus and re-run the test.
    cy.window().its('navigator.clipboard').invoke('readText').then((result) => {}).should('equal', 'music research');
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
  it('contains scope notes, with HTML links', () => {
    cy.visit('/yso/fi/page/p39138') // go to "ukonvaajat" concept page (in Finnish)

    // check the property name
    cy.get('.prop-skos_scopeNote .property-label').invoke('text').should('equal', 'Käyttöhuomautus')

    // check that we have the correct number of scopeNotes
    cy.get('.prop-skos_scopeNote .property-value').find('li').should('have.length', 1)

    // check the scopeNote value
    cy.get('.prop-skos_scopeNote .property-value li').invoke('text').should('equal', 'Ukonvaajoiksi on nimitetty myös maahan osuneen salamaniskun muodostamia mineraalirakenteita. Näistä käytetään käsitettä fulguriitit.')

    // check the link within the scopeNote
    cy.get('.prop-skos_scopeNote .property-value li a').should('have.attr', 'href', 'http://www.yso.fi/onto/yso/p39144')
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
  it("doesn't contain subproperties of skos:hiddenLabel", () => {
    cy.visit('/subclass/en/page/d1') // go to "ukonvaajat" concept page (in Finnish)

    // make sure that the hidden property is not shown
    cy.contains('This subproperty should not be shown in the UI').should('not.exist')
    cy.contains('Do not show this').should('not.exist')
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
  it('concept URI can be copied to clipboard', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page

    // click the copy to clipboard button next to the URI
    cy.get('#copy-uri').click()

    // check that the clipboard now contains "http://www.yso.fi/onto/yso/p21685"
    // NOTE: This test may fail when running Cypress interactively in a browser.
    // The reason is browser security policies for accessing the clipboard.
    // If that happens, make sure the browser window has focus and re-run the test.
    cy.window().its('navigator.clipboard').invoke('readText').then((result) => {}).should('equal', 'http://www.yso.fi/onto/yso/p21685');
  })
  it('contains created & modified times (English)', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page (English)

    cy.get('#date-info').invoke('text').should('equal', 'Created 10/25/07, last modified 2/8/23')
  })
  it('contains created & modified times (Finnish)', () => {
    cy.visit('/yso/fi/page/p21685') // go to "musiikintutkimus" concept page (Finnish)

    cy.get('#date-info').invoke('text').should('equal', 'Luotu 25.10.2007, viimeksi muokattu 8.2.2023')
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
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(2).contains('musiikintutkimus (fi)')
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(2).find('a').invoke('text').should('equal', 'musiikintutkimus')
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(2).find('a').should('have.attr', 'href', 'http://www.yso.fi/onto/ysa/Y155072')
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-vocab').eq(2).contains('YSA - Yleinen suomalainen asiasanasto')
    // check that the second mapping property has the right number of entries
    cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').should('have.length', 3)
  })
})
