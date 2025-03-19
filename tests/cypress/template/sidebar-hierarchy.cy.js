describe('Hierarchy', () => {
  it('Loads top concepts', () => {
    // Go to test vocab home page
    cy.visit('/test-hierarchy/en/')
    // Check that hierarchy tab is available and click it open
    cy.get('#hierarchy').should('not.have.class', 'disabled').click()
    // Check that hierarchy includes correct top concept
    cy.get('#hierarchy-list li').should('have.length', 1).first().invoke('text').should('contain', 'Birds')
  })
  it('Loads children and hides them on button click', () => {
    // Go to test vocab home page
    cy.visit('/test-hierarchy/en/')
    // Click hierarchy tab open
    cy.get('#hierarchy').click()
    // Click hierarchy open button of top concept
    cy.get('#hierarchy-list li button').first().click({force: true})
    // Check that children are loaded in
    cy.get('#hierarchy-list li ul').first().children().should('have.length', 9)
    // Click hierarchy open button again
    cy.get('#hierarchy-list li button').first().click({force: true})
    // Check that children are hidden
    cy.get('#hierarchy-list li ul').should('not.exist')
  })
  it('Loads children on concept click', () => {
    // Go to test vocab home page
    cy.visit('/test-hierarchy/en/')
    // Click hierarchy tab open
    cy.get('#hierarchy').click()
    // Click first top concept link
    cy.get('#hierarchy-list li a').first().click()
    // Check that children are loaded in
    cy.get('#hierarchy-list li ul').first().children().should('have.length', 9)
    // Check that clicked element is selected
    cy.get('#hierarchy-list li a').first().should('have.class', 'selected')
  })
  it('Shows no button for concepts with no children', () => {
    // Go to test vocab home page
    cy.visit('/test-hierarchy/en/')
    // Click hierarchy tab open
    cy.get('#hierarchy').click()
    // Click hierarchy open button of top concept
    cy.get('#hierarchy-list li button').first().click({force: true})
    // Check that concept with no children has no button
    cy.get('#hierarchy-list li').eq(1).find('button').should('not.exist')
  })
  it('Loads hierarchy on concept page', () => {
    // Go to "Cuckoo" concept page
    cy.visit('/test-hierarchy/en/page/ta116')
    // Check that hierarchy tab is active
    cy.get('#hierarchy a').should('have.class', 'active')
    // Check that selected element is "Cuckoo"
    cy.get('#hierarchy-list .selected').should('have.length', 1).invoke('text').should('contain', 'Cuckoo')
    // Check that "Cuckoo" has 1 child "European cuckoo"
    cy.get('#hierarchy-list li:has(.selected)').last().find('ul').should('have.length', 1).invoke('text').should('contain', 'European cuckoo')
    // Check that hierarchy includes correct top concept
    cy.get('#hierarchy-list li').first().invoke('text').should('contain', 'Birds')
    // Check that other concepts are loaded
    cy.get('#hierarchy-list li ul').first().children().should('have.length', 9)
  })
  it('Loads hierarchy after opening concept page', () => {
    // Go to test vocab home page
    cy.visit('/test-hierarchy/en/')
    // Change letter to C in alphabetical view
    cy.get("#tab-alphabetical .pagination").contains('a', 'C').click()
    // Click on "Cuckoo" in alphabetical index
    cy.get('#tab-alphabetical .sidebar-list li a').last().click()
    // Check that new concept page has been loaded
    cy.get('#concept-heading h1', {'timeout': 15000}).invoke('text').should('equal', 'Cuckoo')
    // Click hierarchy tab open
    cy.get('#hierarchy').click()
    // Check that selected element is "Cuckoo"
    cy.get('#hierarchy-list .selected').should('have.length', 1).invoke('text').should('contain', 'Cuckoo')
    // Check that "Cuckoo" has 1 child "European cuckoo"
    cy.get('#hierarchy-list li:has(.selected)').last().find('ul').should('have.length', 1).invoke('text').should('contain', 'European cuckoo')
    // Check that hierarchy includes correct top concept
    cy.get('#hierarchy-list li a').first().invoke('text').should('contain', 'Birds')
    // Check that other concepts are loaded
    cy.get('#hierarchy-list li ul').first().children().should('have.length', 9)
  })
  it('Scrolls to selected concept on load', () => {
    // Go to "ages (periods of time)" YSO concept page
    cy.visit('/yso/en/page/p4623')
    // Check that opened concept was scrolled to and is visible in hierarchy
    cy.get('#hierarchy-list a[href="yso/en/page/p4623"]').should('be.visible')
  })
  it('Sorts concepts based on labels', () => {
    // Go to "properties" concept page in a vocab with sorting based on labels
    cy.visit('/yso/en/page/p2742')
    // First and second concepts in hierarchy should be sorted by label
    cy.get('#hierarchy-list .list-group-item').eq(0).find('.concept-label').invoke('text').should('contain', 'events and action')
    cy.get('#hierarchy-list .list-group-item').eq(1).find('.concept-label').invoke('text').should('contain', 'objects')
  })
  it('Sorts concepts based on labels and content language', () => {
    // Go to "properties" concept page in a vocab with sorting based on labels
    cy.visit('/yso/en/page/p2742?clang=fi')
    // First and second concepts in hierarchy should be sorted by label according to content language
    cy.get('#hierarchy-list .list-group-item').eq(0).find('.concept-label').invoke('text').should('contain', 'oliot')
    cy.get('#hierarchy-list .list-group-item').eq(1).find('.concept-label').invoke('text').should('contain', 'ominaisuudet')
  })
  it('Sorts concepts based on notation codes in lexical order', () => {
    // Go to "Tuna" concept page in a vocab with lexical sorting
    cy.visit('/test-notation-sort/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta0111')
    // 4th and 5th concepts in hierarchy should be sorted by notation codes in lexical order (33.10 < 33.2)
    cy.get('#hierarchy-list .list-group-item').eq(4).find('.concept-notation').invoke('text').should('equal', '33.10')
    cy.get('#hierarchy-list .list-group-item').eq(5).find('.concept-notation').invoke('text').should('equal', '33.2')
  })
  it('Sorts concepts based on notation codes in natural order', () => {
    // Go to "Tuna" concept page in a vocab with natural sorting
    cy.visit('/testNotation/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta0111')
    // 5th and 6th concepts in hierarchy should be sorted by notation codes in natural order (33.9 < 33.10)
    cy.get('#hierarchy-list .list-group-item').eq(5).find('.concept-notation').invoke('text').should('equal', '33.9')
    cy.get('#hierarchy-list .list-group-item').eq(6).find('.concept-notation').invoke('text').should('equal', '33.10')
  })
  // Check the correctness of Aria-labels (Sami language will be implemented later)
  it('Aria tags are correct for each language', () => {
    cy.visit('/test-hierarchy/fi/')
    cy.get('#hierarchy').should('not.have.class', 'disabled').click()
    cy.get('#hierarchy-list').find('ul.list-group button').should('have.attr', 'aria-label', 'Avaa');
    cy.get('.concept-label a').first().should('have.attr', 'aria-label', 'Mene käsitesivulle')
    cy.visit('/test-hierarchy/en/')
    cy.get('#hierarchy').should('not.have.class', 'disabled').click()
    cy.get('#hierarchy-list').find('ul.list-group button').should('have.attr', 'aria-label', 'Open');
    cy.get('.concept-label a').first().should('have.attr', 'aria-label', 'Go to the concept page')
    cy.visit('/test-hierarchy/sv/')
    cy.get('#hierarchy').should('not.have.class', 'disabled').click()
    cy.get('#hierarchy-list').find('ul.list-group button').should('have.attr', 'aria-label', 'Öppna');
    cy.get('.concept-label a').first().should('have.attr', 'aria-label', 'Gå till begreppssidan')
  })
})
