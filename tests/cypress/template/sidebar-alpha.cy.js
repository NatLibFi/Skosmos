describe('Alphabetical index', () => {
  it('Loads letters and concepts on page load', () => {
    // Go to YSO vocab home page
    cy.visit('/yso/en/')
    // Check that letter pagination exists and has the right number of items
    cy.get('#tab-alphabetical').find('.pagination li').should('have.length', 23)
    // Check that the first letter is correct
    cy.get('#tab-alphabetical').find('.pagination li').first().invoke('text').should('contain', 'A')
    // Check that alphabetical list exists and has the right concepts
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', 'abstract objects')
    // Check that loading spinner does not exist
    cy.get('#tab-alphabetical .sidebar-list i.fa-spinner').should('not.exist')
  })
  it('Loads letters and concepts on tab open', () => {
    // Go to "properties" YSO concept page
    cy.visit('/yso/en/page/p2742')
    // Click on alphabetical index tab
    cy.get('#alphabetical').click()
    // Check that letter pagination exists and has the right number of items
    cy.get('#tab-alphabetical').find('.pagination li').should('have.length', 23)
    // Check that the first letter is correct
    cy.get('#tab-alphabetical').find('.pagination li').first().invoke('text').should('contain', 'A')
    // Check that alphabetical list exists and has the right concepts
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', 'abstract objects')
    // Check that loading spinner does not exist
    cy.get('#tab-alphabetical .sidebar-list i.fa-spinner').should('not.exist')
  })
  it('Loads concepts on letter change', () => {
    // Go to YSO vocab home page
    cy.visit('/yso/en/')
    // Click on second pagination item
    cy.get('#tab-alphabetical').find('.pagination li').eq(1).click()
    // Check that alphabetical list has the right concepts
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', 'birch bark manuscripts')
    // Check that loading spinner does not exist
    cy.get('#tab-alphabetical .sidebar-list i.fa-spinner').should('not.exist')
  })
  it('Diacritic letters are clickable', () => {
    // go to the YSO home page in Swedish language
    cy.visit('/yso/sv/')
    // click on the last letter (Ö)
    cy.get('#tab-alphabetical .pagination :nth-last-child(1) > .page-link').click()
    // check that we have the correct number of entries
    cy.get('#tab-alphabetical .sidebar-list .list-group').children().should('have.length', 4)
    // check that the first entry is "östliga handelsvägar"
    cy.get('#tab-alphabetical .sidebar-list .list-group').children().first().children().first().invoke('text').should('equal', 'östliga handelsvägar')
    // Check that loading spinner does not exist
    cy.get('#tab-alphabetical .sidebar-list i.fa-spinner').should('not.exist')
  })
  it('Concepts and letters in alphabetical index are displayed in the correct language', () => {
    // Go to YSO vocab page with UI language set to English and content language set to Finnish
    cy.visit('/yso/en/?clang=fi')
    // Check that letters contain Y and not C
    cy.get('#tab-alphabetical').find('.pagination li').invoke('text').should('contain', 'Y').should('not.contain', 'C')
    // Check that the first item in the list is in the correct language
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', 'aarrelöydöt')
  })
  it('Shows altLabels', () => {
    // Go to YSO vocab home page
    cy.visit('/yso/fi/')
    // Check that notation codes are displayed
    cy.get('#tab-alphabetical').find('.sidebar-list li').eq(2).invoke('text').should('contain', 'aDNA').should('contain', 'muinais-DNA')
  })
  it('Shows notation codes', () => {
    // Go to vocab home page in a vocab with notation codes in alphabetical index
    cy.visit('/test-notation-sort/en/')
    // Check that notation codes are displayed
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', '(33.02)')
  })
  it('Loads concepts on scroll', () => {
    // Go to YSO vocab home page
    cy.visit('/test-551-A/fi/')
    // Scroll to the bottom of sidebar list
    cy.get('#tab-alphabetical').find('.sidebar-list').scrollTo('bottom')
    // Check that new concepts have been loaded
    cy.get('#tab-alphabetical').find('.sidebar-list li').should('have.length', 500, {'timeout': 20000})
    // Scroll to the bottom of sidebar list again
    cy.get('#tab-alphabetical').find('.sidebar-list').scrollTo('bottom')
    // Check that new concepts have been loaded
    cy.get('#tab-alphabetical').find('.sidebar-list li').should('have.length.gt', 500, {'timeout': 20000})
    // Check that loading spinner does not exist
    cy.get('#tab-alphabetical .sidebar-list i.fa-spinner').should('not.exist')
  })
  it('clicking on alphabetical index entries performs partial page load', () => {
    cy.visit('/yso/en/') // go to the YSO home page in English language

    // click on the the letter C
    cy.get('#tab-alphabetical').contains('a', 'C').click()

    // click on the link "care institutions" (should trigger partial page load)
    cy.get('#tab-alphabetical').contains('a', 'care institutions').click()

    // check the concept prefLabel
    cy.get('#concept-heading h1', {'timeout': 15000}).invoke('text').should('equal', 'care institutions')

    // check that the SKOSMOS object matches the newly loaded concept
    cy.window().then((win) => {
      expect(win.SKOSMOS.uri).to.equal('http://www.yso.fi/onto/yso/p6034');
      expect(win.SKOSMOS.prefLabels[0]['label']).to.equal("care institutions");
    })

    // check that we have some mappings
    cy.get('#concept-mappings').should('not.be.empty')
    // check that loading spinner does not exist
    cy.get('#concept-mappings i.fa-spinner', {'timeout': 15000}).should('not.exist')

    // check the second mapping property name
    cy.get('.prop-mapping h2', {'timeout': 20000}).eq(0).contains('Exactly matching concepts')
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
  // Check the correctness of Aria-labels (Sami language will be implemented later)"
  it('Aria tags are correct for each language', () => {
    cy.visit('/yso/en/')
    cy.get('#tab-alphabetical .list-group-item > a').should('have.attr', 'aria-label', 'Go to the concept page')
    cy.visit('/yso/sv/')
    cy.get('#tab-alphabetical .list-group-item > a').should('have.attr', 'aria-label', 'Gå till begreppssidan')
    cy.visit('/yso/fi/')
    cy.get('#tab-alphabetical .list-group-item > a').should('have.attr', 'aria-label', 'Mene käsitesivulle')
  })
})
