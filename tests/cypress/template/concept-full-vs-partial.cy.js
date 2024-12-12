describe('Concept page, full vs. partial page loads', () => {
  const pageLoadTypes = ["full", "partial"]

  // tests that should be executed both with and without partial page load
  pageLoadTypes.forEach((pageLoadType) => {
    it('contains concept preflabel / ' + pageLoadType, () => {
      if (pageLoadType == "full") {
        cy.visit('/yso/en/page/p39473') // go to "burial mounds" concept page
      } else {
        cy.visit('/yso/en/page/p5714') // go to "prehistoric graves" concept page
        // click on the link to "burial mounds" to trigger partial page load
        cy.get('#tab-hierarchy').contains('a', 'burial mounds').click()
      }

      // check that the vocabulary title is correct
      cy.get('#vocab-title > a').invoke('text').should('equal', 'YSO - General Finnish ontology (archaeology)')

      // check the concept prefLabel
      cy.get('#concept-heading h1').invoke('text').should('equal', 'burial mounds')
    })
    it('concept preflabel can be copied to clipboard / ' + pageLoadType, () => {
      if (pageLoadType == "full") {
        cy.visit('/yso/en/page/p39473') // go to "burial mounds" concept page
      } else {
        cy.visit('/yso/en/page/p5714') // go to "prehistoric graves" concept page
        // click on the link to "burial mounds" to trigger partial page load
        cy.get('#tab-hierarchy').contains('a', 'burial mounds').click()
      }

      // click the copy to clipboard button next to the prefLabel
      cy.get('#copy-preflabel').click()

      // check that the clipboard now contains "music pyramids"
      // NOTE: This test may fail when running Cypress interactively in a browser.
      // The reason is browser security policies for accessing the clipboard.
      // If that happens, make sure the browser window has focus and re-run the test.
      cy.window().its('navigator.clipboard').invoke('readText').then((result) => {}).should('equal', 'burial mounds');
    })
    it('contains concept URI / ' + pageLoadType, () => {
      if (pageLoadType == "full") {
        cy.visit('/yso/en/page/p39473') // go to "burial mounds" concept page
      } else {
        cy.visit('/yso/en/page/p5714') // go to "prehistoric graves" concept page
        // click on the link to "burial mounds" to trigger partial page load
        cy.get('#tab-hierarchy').contains('a', 'burial mounds').click()
      }

      // check the property name
      cy.get('.prop-uri .property-label').invoke('text').should('equal', 'URI')

      // check the concept URI
      cy.get('#concept-uri').invoke('text').should('equal', 'http://www.yso.fi/onto/yso/p39473')
    })
    it('concept URI can be copied to clipboard / ' + pageLoadType, () => {
      if (pageLoadType == "full") {
        cy.visit('/yso/en/page/p39473') // go to "burial mounds" concept page
      } else {
        cy.visit('/yso/en/page/p5714') // go to "prehistoric graves" concept page
        // click on the link to "burial mounds" to trigger partial page load
        cy.get('#tab-hierarchy').contains('a', 'burial mounds').click()
      }

      // click the copy to clipboard button next to the URI
      cy.get('#copy-uri').click()

      // check that the clipboard now contains "http://www.yso.fi/onto/yso/p39473"
      // NOTE: This test may fail when running Cypress interactively in a browser.
      // The reason is browser security policies for accessing the clipboard.
      // If that happens, make sure the browser window has focus and re-run the test.
      cy.window().its('navigator.clipboard').invoke('readText').then((result) => {}).should('equal', 'http://www.yso.fi/onto/yso/p39473');
    })
    it('concept notation can be copied to clipboard / ' + pageLoadType, () => {
      if (pageLoadType == "full") {
        cy.visit('/test-notation-sort/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta0115') // go to "Eel" concept page
      } else {
        cy.visit('/test-notation-sort/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta0114') // go to "Buri" concept page
        // click on the link to "Eel" to trigger partial page load
        cy.get('#tab-hierarchy').contains('a', 'Eel').click()
      }

      // click the copy to clipboard button next to the URI
      cy.get('#copy-notation').click()

      // check that the clipboard now contains "33.2"
      // NOTE: This test may fail when running Cypress interactively in a browser.
      // The reason is browser security policies for accessing the clipboard.
      // If that happens, make sure the browser window has focus and re-run the test.
      cy.window().its('navigator.clipboard').invoke('readText').then((result) => {}).should('equal', '33.2');
    })
    it('contains mappings / ' + pageLoadType, () => {
      if (pageLoadType == "full") {
        cy.visit('/yso/en/page/p14174') // go to "labyrinths" concept page
      } else {
        cy.visit('/yso/en/page/p5714') // go to "prehistoric graves" concept page
        // click on the link to "labyrinths" to trigger partial page load
        cy.get('#tab-hierarchy').contains('a', 'labyrinths').click()
      }

      // check that we have some mappings
      cy.get('#concept-mappings').should('not.be.empty')
      // check that spinner does not exist after load
      cy.get('#concept-mappings i.fa-spinner', {'timeout': 15000}).should('not.exist')

      // check the first mapping property name
      // NOTE: we need to increase the timeout as the mappings can take a long time to load
      cy.get('.prop-mapping h2', {'timeout': 20000}).eq(0).contains('Closely matching concepts')
      // check the first mapping property values
      cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).contains('Labyrinths')
      cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').should('have.attr', 'href', 'http://id.loc.gov/authorities/subjects/sh85073793')
      cy.get('.prop-mapping').eq(0).find('.prop-mapping-vocab').eq(0).contains('Library of Congress Subject Headings')
      // check that the first mapping property has the right number of entries
      cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').should('have.length', 1)

      // check the second mapping property name
      cy.get('.prop-mapping h2').eq(1).contains('Exactly matching concepts')
      // check the second mapping property values
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(0).contains('labyrinter (sv)')
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(0).find('a').invoke('text').should('equal', 'labyrinter')
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(0).find('a').should('have.attr', 'href', 'http://www.yso.fi/onto/allars/Y21700')
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-vocab').eq(0).contains('Allärs - General thesaurus in Swedish')
      // skipping the middle one (mapping to KOKO concept) as it's similar to the others
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(2).contains('labyrintit (fi)')
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(2).find('a').invoke('text').should('equal', 'labyrintit')
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').eq(2).find('a').should('have.attr', 'href', 'http://www.yso.fi/onto/ysa/Y108389')
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-vocab').eq(2).contains('YSA - Yleinen suomalainen asiasanasto')
      // check that the second mapping property has the right number of entries
      cy.get('.prop-mapping').eq(1).find('.prop-mapping-label').should('have.length', 3)
    })

  });
})
