describe('Concept page', () => {
  it('Contains title and title metadata', () => {
    cy.visit('/yso/en/page/p1265') // go to "archaeology" concept page

    const expectedTitle = 'archaeology - YSO - Skosmos being tested'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains description metadata', () => {
    cy.visit('/yso/en/page/p1265') // go to "archaeology" concept page

    const expectedDescription = 'Concept archaeology in vocabulary YSO - General Finnish ontology (archaeology)'
    // check that the page has description metadata
    cy.get('head meta[name="description"]').should('have.attr', 'content', expectedDescription);
    cy.get('head meta[property="og:description"]').should('have.attr', 'content', expectedDescription);
  })
  it('Contains site name metadata', () => {
    cy.visit('/yso/en/page/p1265') // go to "archaeology" concept page

    const expectedSiteName = 'Skosmos being tested'
    // check that the page has site name metadata
    cy.get('head meta[property="og:site_name"]').should('have.attr', 'content', expectedSiteName);
  })
  it('Contains canonical URL metadata (short URL)', () => {
    cy.visit('/yso/en/page/p1265') // go to "archaeology" concept page

    const expectedUrl = Cypress.config('baseUrl') + 'yso/en/page/p1265'
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl);
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl);
  })
  it('Contains canonical URL metadata (long URL)', () => {
    // go to "archaeology" concept page using long URL based on URI
    cy.visit('/yso/en/page/?uri=http%3A%2F%2Fwww.yso.fi%2Fonto%2Fyso%2Fp1265')

    const expectedUrl = Cypress.config('baseUrl') + 'yso/en/page/p1265'
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl);
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl);
  })
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

  it('overrides property labels', () => {
    // Go to "Carp" concept page in vocab with property label overrides
    cy.visit('/conceptPropertyLabels/en/page/ta112')
    // Check that prefLabel property label is overridden correctly
    cy.get('#concept-property-label').invoke('text').should('include', 'Caption')
    // Check that notation property label is overridden correctly
    cy.get('.prop-skos_notation .property-label h2').invoke('text').should('include', 'UDC number')
    // Check that mapping property name is overridden correctly
    cy.get('.prop-mapping h2', {'timeout': 20000}).eq(0).contains('Exactly matching classes')
    // Check that mapping property title is overridden correctly
    cy.get('.prop-mapping .property-label').eq(0).should('have.attr', 'title').and('contain', 'Exactly matching classes in another vocabulary.')
  })
  it('contains SKOS XL information for concept prefLabel', () => {
    cy.visit('/yso/en/page/p4625?clang=se') // go to "bronsaáigi" concept page ('Bronze Age' in Northern Sami)

    // the tooltip should originally be invisible
    cy.get('#concept-label .tooltip-html-content').should('not.be.visible')

    // click the button to trigger the tooltip
    cy.get('#concept-label .tooltip-html button').click()

    // the tooltip should now be visible
    cy.get('#concept-label .tooltip-html-content').should('be.visible')
  })
  it('contains concept type (skos:Collection and iso-thes)', () => {
    cy.visit('/groups/en/page/fish') // go to "Fish" ConceptGroup page

    // check the property name
    cy.get('.prop-rdf_type .property-label').invoke('text').should('equal', 'Type')

    // check the concept type
    cy.get('.prop-rdf_type .property-value li').invoke('text').should('contain', 'Collection')
    cy.get('.prop-rdf_type .property-value li').invoke('text').should('contain', 'Array of sibling concepts')
  })
  it('contains concept type (vocabulary-specific type)', () => {
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
  it('contains notation codes for narrower/broader concepts', () => {
    // Go to "Karppi" concept page
    cy.visit('/testNotation/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta01')
    // Check the notation code for narrower concept
    cy.get('.prop-skos_narrower .property-value-notation').eq(0).invoke('text').should('contain', '33.01')

    // Go to "Crucian carp" concept page
    cy.visit('/testNotation/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta0121')
    // Check the notation code for broader concept
    cy.get('.prop-skos_broader .property-value-notation').eq(0).invoke('text').should('contain', '33.1')
  })
  it('contains submembers narrower concepts', () => {
    // Go to "Grouped fish" concept page
    cy.visit('groups/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Fgroups%2Fta1')
    // Check the submembers of "Freshwater fish"
    cy.get('.prop-skos_narrower .property-value-submembers').eq(0).find('li').should('have.length', 1)
    cy.get('.prop-skos_narrower .property-value-submembers li').eq(0).invoke('text').should('contain', 'Carp')
    // Check the submembers of "Saltwater fish"
    cy.get('.prop-skos_narrower .property-value-submembers').eq(1).find('li').should('have.length', 2)
    cy.get('.prop-skos_narrower .property-value-submembers').eq(1).find('li').eq(0).invoke('text').should('contain', 'Flatfish')
    cy.get('.prop-skos_narrower .property-value-submembers').eq(1).find('li').eq(1).invoke('text').should('contain', 'Tuna')
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
  it('contains SKOS XL information for altLabel', () => {
    cy.visit('/xl/en/page/c1') // go to "Concept" concept page in 'xl' test vocabulary

    // the tooltip should originally be invisible
    cy.get('.prop-skos_altLabel .tooltip-html-content').should('not.be.visible')

    // focus on the button to trigger the tooltip
    cy.get('.prop-skos_altLabel .tooltip-html button').focus()

    // the tooltip should now be visible
    cy.get('.prop-skos_altLabel .tooltip-html-content').should('be.visible')

    // lose the focus on the button to hide the tooltip
    cy.get('.prop-skos_altLabel .tooltip-html button').blur()

    // the tooltip should now be invisible again
    cy.get('.prop-skos_altLabel .tooltip-html-content').should('not.be.visible')
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
  it('contains SKOS XL information for terms in other languages', () => {
    cy.visit('/yso/en/page/p4625') // go to "Bronze Age" concept page

    // the tooltip should originally be invisible
    cy.get('#concept-other-languages').find('.row').eq(1).find('.tooltip-html-content').should('not.be.visible')

    // click the button to trigger the tooltip
    cy.get('#concept-other-languages').find('.row').eq(1).find('.tooltip-html button').click()

    // the tooltip should now be visible
    cy.get('#concept-other-languages').find('.row').eq(1).find('.tooltip-html-content').should('be.visible')
  })
  it('contains created & modified times (English)', () => {
    cy.visit('/yso/en/page/p21685') // go to "music research" concept page (English)

    cy.get('#date-info').invoke('text').should('equal', 'Created 10/25/07, last modified 2/8/23')
  })
  it('contains created & modified times (Finnish)', () => {
    cy.visit('/yso/fi/page/p21685') // go to "musiikintutkimus" concept page (Finnish)

    cy.get('#date-info').invoke('text').should('equal', 'Luotu 25.10.2007, viimeksi muokattu 8.2.2023')
  })
})
