describe('Vocabulary home page', () => {
  it('Contains title and title metadata', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const expectedTitle = 'Test ontology - Skosmos being tested'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains description metadata', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const expectedDescription = 'Description of Test ontology'

    // check that the page has description metadata
    cy.get('head meta[name="description"]').should('have.attr', 'content', expectedDescription);
    cy.get('head meta[property="og:description"]').should('have.attr', 'content', expectedDescription);
  })
  it('Contains site name metadata', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const expectedSiteName = 'Skosmos being tested'
    // check that the page has site name metadata
    cy.get('head meta[property="og:site_name"]').should('have.attr', 'content', expectedSiteName);
  })
  it('Contains canonical URL metadata', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const expectedUrl = Cypress.config('baseUrl') + 'test/en/'
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl);
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl);
  })
  it('contains vocabulary title', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // check that the vocabulary title is correct
    cy.get('#vocab-title > a').invoke('text').should('equal', 'Test ontology')
  })
  it('clicking on hierarchy entries performs partial page load', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // open the hierarchy tab
    cy.get('#hierarchy a').click()

    // click on the link "Fish" (should trigger partial page load)
    cy.get('#tab-hierarchy').contains('a', 'Fish').click()

    // check the concept prefLabel
    cy.get('#concept-heading h1', {'timeout': 15000}).invoke('text').should('equal', 'Fish')

    // check that the SKOSMOS object matches the newly loaded concept
    cy.window().then((win) => {
      expect(win.SKOSMOS.uri).to.equal('http://www.skosmos.skos/test/ta1');
      expect(win.SKOSMOS.prefLabels[0]['label']).to.equal("Fish");
    })

    // check that we have some mappings
    cy.get('#concept-mappings').should('not.be.empty')
    // check that loading spinner does not exist
    cy.get('#concept-mappings i.fa-spinner', {'timeout': 15000}).should('not.exist')

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
