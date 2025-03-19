describe('New and removed view', () => {
  it('Loads changes on tab open', () => {
    // Go to changes vocab home page
    cy.visit('/changes/en/')
    // Click on changes tab
    cy.get('#changes').click()
    // Check that changes list exists and has the right concepts
    cy.get('#tab-changes').find('.sidebar-list li a').should('have.length', 6).first().invoke('text').should('contain', 'No replacement')
    // Check that loading spinner does not exist
    cy.get('#tab-changes .sidebar-list i.fa-spinner').should('not.exist')
  })
  it('Loads changes on page load', () => {
    // Go to home page of vocab with changes view as default sidebar view
    cy.visit('/changesDefaultView/en/')
    // Check that changes list exists and has the right concepts
    cy.get('#tab-changes').find('.sidebar-list li a').should('have.length', 6).first().invoke('text').should('contain', 'No replacement')
    // Check that loading spinner does not exist
    cy.get('#tab-changes .sidebar-list i.fa-spinner').should('not.exist')
  })
  it('Groups concepts based on date in chronological order', () => {
    // Go to home page of vocab with changes view as default sidebar view
    cy.visit('/changesDefaultView/en/')
    // Check that headings exist and are in chronological order
    cy.get('#tab-changes').find('.sidebar-list li h2').should('have.length', 4).eq(0).invoke('text').should('contain', 'February 2021')
    cy.get('#tab-changes').find('.sidebar-list li h2').eq(1).invoke('text').should('contain', 'January 2021')
  })
  it('Shows deprecated concepts', () => {
    // Go to home page of vocab with changes view as default sidebar view
    cy.visit('/changesDefaultView/en/')
    // Check that an s element exists and has the correct label
    cy.get('#tab-changes').find('.sidebar-list li a s').invoke('text').should('contain', 'Fourth date')
  })
  it('Displays concepts and headings in the correct language', () => {
    // Go to YSO home page with UI language set to English and content language set to Finnish
    cy.visit('/yso/en/?clang=fi')
    // Click on changes tab
    cy.get('#changes').click()
    // Check that headings exist and are in the correct language
    cy.get('#tab-changes').find('.sidebar-list li h2').eq(0).invoke('text').should('contain', 'September 2023')
    // Check that concepts are in the correct language
    cy.get('#tab-changes').find('.sidebar-list li a').eq(0).invoke('text').should('contain', 'irtolöydöt')
  })
  it('Loads concepts on scroll', () => {
    // Go to YSO vocab home page
    cy.visit('/yso/en/')
    // click on changes tab
    cy.get('#changes').click()
    // Scroll to the bottom of sidebar list
    cy.get('#tab-changes').find('.sidebar-list').scrollTo('bottom')
    // Check that loading spinner exists
    cy.get('#tab-changes .sidebar-list i.fa-spinner')
    // Check that new concepts have been loaded
    cy.get('#tab-changes').find('.sidebar-list li').should('have.length.gt', 200, {'timeout': 20000})
    // Check that loading spinner does not exist
    cy.get('#tab-changes .sidebar-list i.fa-spinner').should('not.exist')
  })
  it('Performs partial page load when clicking on concept', () => {
    // go to the YSO home page in English language
    cy.visit('/yso/en/')
    // Click on changes tab
    cy.get('#changes').click()
    // click on the link "Bell beaker culture" (should trigger partial page load)
    cy.get('#tab-changes').contains('a', 'Bell beaker culture').click()

    // check the concept prefLabel
    cy.get('#concept-heading h1', {'timeout': 15000}).invoke('text').should('equal', 'Bell beaker culture')

    // check that the SKOSMOS object matches the newly loaded concept
    cy.window().then((win) => {
      expect(win.SKOSMOS.uri).to.equal('http://www.yso.fi/onto/yso/p40009');
      expect(win.SKOSMOS.prefLabels[0]['label']).to.equal("Bell beaker culture");
    })

    // check that we have some mappings
    cy.get('#concept-mappings').should('not.be.empty')
    // check that loading spinner does not exist
    cy.get('#concept-mappings i.fa-spinner', {'timeout': 15000}).should('not.exist')

    // check mapping property name
    cy.get('.prop-mapping h2', {'timeout': 20000}).eq(0).contains('Closely matching concepts')
    // check the mapping property values
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').invoke('text').should('equal', 'Bell beaker culture')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').eq(0).find('a').should('have.attr', 'href', 'http://id.loc.gov/authorities/subjects/sh87007797')
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-vocab').eq(0).contains('Library of Congress Subject Headings')
    // check that mapping property has the right number of entries
    cy.get('.prop-mapping').eq(0).find('.prop-mapping-label').should('have.length', 2)
    // check that mappings have the right number of properties
    cy.get('.prop-mapping h2').should('have.length', 2)
  })
  it('Has correct translations', () => {
    // go to YSO vocab front page in English
    cy.visit('/yso/en/')
    // Click on changes tab
    cy.get('#changes').click()
    // Check that concepts have correct Aria labels
    cy.get('#tab-changes').find('.sidebar-list li a').eq(0).should('have.attr', 'aria-label', 'Go to the concept page')

    // go to YSO vocab front page in Finnish
    cy.visit('/yso/fi/')
    // Click on changes tab
    cy.get('#changes').click()
    // Check that concepts have correct Aria labels
    cy.get('#tab-changes').find('.sidebar-list li a').eq(0).should('have.attr', 'aria-label', 'Mene käsitesivulle')

    // go to YSO vocab front page in Swedish
    cy.visit('/yso/sv/')
    // Click on changes tab
    cy.get('#changes').click()
    // Check that concepts have correct Aria labels
    cy.get('#tab-changes').find('.sidebar-list li a').eq(0).should('have.attr', 'aria-label', 'Gå till begreppssidan')

  })  
})
