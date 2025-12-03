describe('Vocabulary home page', () => {
  it('Contains title and title metadata', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const expectedTitle = 'Test ontology - Skosmos being tested'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle)
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle)
  })
  it('Contains description metadata', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const expectedDescription = 'Description of Test ontology'

    // check that the page has description metadata
    cy.get('head meta[name="description"]').should('have.attr', 'content', expectedDescription)
    cy.get('head meta[property="og:description"]').should('have.attr', 'content', expectedDescription)
  })
  it('Contains site name metadata', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const expectedSiteName = 'Skosmos being tested'
    // check that the page has site name metadata
    cy.get('head meta[property="og:site_name"]').should('have.attr', 'content', expectedSiteName)
  })
  it('Contains canonical URL metadata', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    const expectedUrl = Cypress.config('baseUrl') + 'test/en/'
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl)
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl)
  })
  it('Contains vocabulary title', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // check that the vocabulary title is correct
    cy.get('#vocab-title > a').invoke('text').should('contain', 'Test ontology')
  })
  it('Clicking on hierarchy entries performs partial page load', () => {
    cy.visit('/test/en') // go to the "Test ontology" home page

    // open the hierarchy tab
    cy.get('#hierarchy a').click()

    // click on the link "Fish" (should trigger partial page load)
    cy.get('#tab-hierarchy').contains('a', 'Fish').click()

    // check the concept prefLabel
    cy.get('#concept-heading h1', {'timeout': 15000}).invoke('text').should('equal', 'Fish')

    // check that the SKOSMOS object matches the newly loaded concept
    cy.window().then((win) => {
      expect(win.SKOSMOS.uri).to.equal('http://www.skosmos.skos/test/ta1')
      expect(win.SKOSMOS.prefLabels[0]['label']).to.equal("Fish")
    })

    // check that we have some mappings
    cy.get('#concept-mappings').should('not.be.empty')
    // check that loading spinner does not exist
    cy.get('#concept-mappings i.fa-spinner', {'timeout': 15000}).should('not.exist')

    // check the second mapping property name
    cy.get('.prop-mapping h2', {'timeout': 15000}).eq(0).contains('Exactly matching concepts')
    // check the second mapping property values
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').invoke('text').should('match', /^(fish|wd:Q152)$/)
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').should('have.attr', 'href', 'http://www.wikidata.org/entity/Q152')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-vocab').eq(0).contains('www.wikidata.org')

    // check that the second mapping property has the right number of entries
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').should('have.length', 1)
  })

  // The purpose of the test is to go through the table on the vocabulary's front page
  // displaying the counts of resources by type
  describe('Resource counts', () => {
    it('Check all the resource types, quantities and corresponding headers (en)', () => {
      // Go to the "YSO - General Finnish ontology (archaeology)" home page
      cy.visit('/yso/en/')
      // Checks if the header "Resource counts by type" exists
      cy.get('#resource-counts h3').should('contain.text', 'Resource counts by type')

      // Setting up conditions for the 'Resource counts by type' test using an array of objects 
      // representing concept types and their occurrences, which will be utilized later in the test
      const expectedData = [
        { type: 'Concept', count: '270' },
        { type: 'Deprecated concept', count: '0' },
        { type: 'Collection', count: '1' },
      ]

      // Iterating through the array containing data objects (concept types and corresponding counts)
      // to check if the values on the table are correct
      expectedData.forEach(({ type, count }) => {
        cy.get('#resource-stats')
          .contains('tr', type)
          .within(() => {
            cy.get('td').eq(1).should('have.text', count)
          })
      })

      // To make the code more concise: headers as an array that can be looped later
      const headerTexts = [
        'Type',
        'Count',
        'Concept',
        'Deprecated concept',
        'Collection',
      ]

      // Checks that the texts of the headers are correct
      headerTexts.forEach((text) => {
        cy.get('#resource-stats').should('contain.text', text)
      })
    })

    it('Check all the resource types, quantities and corresponding headers (fi)', () => {
      // Go to the "YSO - General Finnish ontology (archaeology)" home page
      cy.visit('/yso/fi/')
      // Checks if the header "Resource counts by type" exists
      cy.get('#resource-counts h3').should('contain.text', 'Resurssien lukumäärä tyypeittäin')

      // Setting up conditions for the 'Resource counts by type' test using an array of objects 
      // representing concept types and their occurrences, which will be utilized later in the test
      const expectedData = [
        { type: 'Käsite', count: '270' },
        { type: 'Käytöstä poistettu käsite', count: '0' },
        { type: 'Kokoelma', count: '1' },
      ]

      // Iterating through the array containing data objects (concept types and corresponding counts)
      // to check if the values on the table are correct
      expectedData.forEach(({ type, count }) => {
        cy.get('#resource-stats')
          .contains('tr', type)
          .within(() => {
            cy.get('td').eq(1).should('have.text', count)
          })
      })

      // To make the code more concise: headers as an array that can be looped later
      const headerTexts = [
        'Tyyppi',
        'Lukumäärä',
        'Käsite',
        'Käytöstä poistettu käsite',
        'Kokoelma',
      ]

      // Checks that the texts of the headers are correct
      headerTexts.forEach((text) => {
        cy.get('#resource-stats').should('contain.text', text)
      })
    })

  })

  // The purpose of the test is to verify that the headers of the "label table" are correct and that 
  // the corresponding counts for each label type are accurate
  describe('Term counts', () => {
    it('Check labels and their corresponding counts, grouped by language (en)', () => {
      // Go to the "YSO - General Finnish ontology (archaeology)" home page
      cy.visit('/yso/en/')

      // Verifying the correctness of the term table header
      cy.get('#term-counts h3').should('contain.text', 'Term counts by language')

      // A table containing objects for later verification of the correctness of labels in 
      // different languages and their corresponding quantities
      const expectedData = [
        { language: 'English', prefLabel: '267', altLabel: '46', hiddenLabel: '178' },
        { language: 'Finnish', prefLabel: '270', altLabel: '113', hiddenLabel: '209' },
        { language: 'Northern Sami', prefLabel: '171', altLabel: '13', hiddenLabel: '83' },
        { language: 'Swedish', prefLabel: '270', altLabel: '191', hiddenLabel: '191' },
      ]

      // Iterating through a table that contains the names of each language, corresponding labels, and their quantities
      expectedData.forEach(({ language, prefLabel, altLabel, hiddenLabel }) => {
        cy.get('#term-stats')
          .contains('tr', language)
          .within(() => {
            cy.get('td').eq(1).should('have.text', prefLabel)
            cy.get('td').eq(2).should('have.text', altLabel)
            cy.get('td').eq(3).should('have.text', hiddenLabel)
          })
      })
      
      // A table used to later iterate and check that the column headers are correct
      const columnHeaders = ['Language', 'Preferred terms', 'Alternate terms', 'Hidden terms']

      // Iterating over the column headers listed above
      columnHeaders.forEach((header) => {
        cy.get('#term-stats th').should('contain.text', header)
      })

      // A table containing the names of languages used in concepts for later iteration
      const languages = ['English', 'Finnish', 'Northern Sami', 'Swedish']

      // Looping through the array containing language names to verify the accuracy of them
      languages.forEach((language) => {
        cy.get('#term-stats td').should('contain.text', language)
      })
    })

    it('Check labels and their corresponding counts, grouped by language (fi)', () => {
      // Go to the "YSO - General Finnish ontology (archaeology)" home page
      cy.visit('/yso/fi/')

      // Verifying the correctness of the term table header
      cy.get('#term-counts h3').should('contain.text', 'Termien lukumäärät kielittäin')

      // A table containing objects for later verification of the correctness of labels in 
      // different languages and their corresponding quantities
      const expectedData = [
        { language: 'englanti', prefLabel: '267', altLabel: '46', hiddenLabel: '178' },
        { language: 'suomi', prefLabel: '270', altLabel: '113', hiddenLabel: '209' },
        { language: 'pohjoissaame', prefLabel: '171', altLabel: '13', hiddenLabel: '83' },
        { language: 'ruotsi', prefLabel: '270', altLabel: '191', hiddenLabel: '191' },
      ]

      // Iterating through a table that contains the names of each language, corresponding labels, and their quantities
      expectedData.forEach(({ language, prefLabel, altLabel, hiddenLabel }) => {
        cy.get('#term-stats')
          .contains('tr', language)
          .within(() => {
            cy.get('td').eq(1).should('have.text', prefLabel)
            cy.get('td').eq(2).should('have.text', altLabel)
            cy.get('td').eq(3).should('have.text', hiddenLabel)
          })
      })
      
      // A table used to later iterate and check that the column headers are correct
      const columnHeaders = ['Kieli', 'Päätermit', 'Ohjaustermit', 'Piilotermit']

      // Iterating over the column headers listed above
      columnHeaders.forEach((header) => {
        cy.get('#term-stats th').should('contain.text', header)
      })

      // A table containing the names of languages used in concepts for later iteration
      const languages = ['englanti', 'suomi', 'pohjoissaame', 'ruotsi']

      // Looping through the array containing language names to verify the accuracy of them
      languages.forEach((language) => {
        cy.get('#term-stats td').should('contain.text', language)
      })
    })
  })
})

