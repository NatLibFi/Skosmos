describe('Groups tab', () => {
  it('Loads groups on vocab page', () => {
    // Go to test vocab home page
    cy.visit('/groups/en/')
    // Check that groups tab is available and click it open
    cy.get('#groups').should('not.have.class', 'disabled').click()
    // Check that groups includes correct top groups
    cy.get('#groups-list li').should('have.length', 3).invoke('text')
      .should('contain', 'Fish')
      .should('contain', 'Freshwater fish')
      .should('contain', 'Saltwater fish')
  })
  it('Loads groups and expands hierarchy on group page', () => {
    // Go to "Freshwater fish" group page
    cy.visit('groups/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Fgroups%2Ffresh')
    // Check that groups tab is opened
    cy.get('#groups a').should('have.class', 'active')
    // Check that selected element is "Freshwater fish"
    cy.get('#groups-list .selected').should('have.length', 1).invoke('text').should('contain', 'Freshwater fish')
    // Check that "Freshwater fish" has 1 child "Carp"
    cy.get('#groups-list li:has(.selected)').last().find('ul').should('have.length', 1).invoke('text').should('contain', 'Carp')
    // Check that other groups are loaded
    cy.get('#groups-list li').invoke('text')
      .should('contain', 'Fish')
      .should('contain', 'Saltwater fish')
  })
  it('Loads groups on concept page', () => {
    // Go to "Carp" concept page
    cy.visit('/groups/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Fgroups%2Fta112')
    // Check that groups includes correct top groups
    cy.get('#groups-list li').should('have.length', 3).invoke('text')
      .should('contain', 'Fish')
      .should('contain', 'Freshwater fish')
      .should('contain', 'Saltwater fish')
  })
  it('Loads members and hides them on button click', () => {
    // Go to test vocab home page
    cy.visit('/groups/en/')
    // Click groups tab open
    cy.get('#groups').should('not.have.class', 'disabled').click()
    // Click open button of second group
    cy.get('#groups-list li button').eq(1).click({force: true})
    // Check that child "Carp" is loaded in
    cy.get('#groups-list li ul', {'timeout': 15000}).first().children().should('have.length', 1).invoke('text').should('include', 'Carp')
    // Click open button again
    cy.get('#groups-list li button').eq(1).click({force: true})
    // Check that children are hidden
    cy.get('#groups-list li ul').should('not.exist')
  })
  it('Loads members on group click', () => {
    // Go to test vocab home page
    cy.visit('/groups/en/')
    // Click groups tab open
    cy.get('#groups').should('not.have.class', 'disabled').click()
    // Click second group
    cy.get('#groups-list li a').eq(1).click()
    // Check that children are loaded in
    cy.get('#groups-list li ul', {'timeout': 15000}).first().children().should('have.length', 1)
    // Check that child is correct
    cy.get('#groups-list li ul').invoke('text').should('include', 'Carp')
  })
  it('Has correct translations', () => {
    // Go to test vocab home page in English
    cy.visit('/yso/en/')
    // Click on groups tab
    cy.get('#groups').click()
    // Check that concepts have correct Aria labels
    cy.get('.concept-label a span').eq(0).invoke('text').should('contain', 'Go to the concept page')

    // Go to test vocab home page in Finnish
    cy.visit('/yso/fi/')
    // Click on groups tab
    cy.get('#groups').click()
    // Check that concepts have correct Aria labels
    cy.get('.concept-label a span').eq(0).invoke('text').should('contain', 'Mene käsitesivulle')

    // Go to test vocab home page in Swedish
    cy.visit('/yso/sv/')
    // Click on groups tab
    cy.get('#groups').click()
    // Check that concepts have correct Aria labels
    cy.get('.concept-label a span').eq(0).invoke('text').should('contain', 'Gå till begreppssidan')
  })
  it('Keyboard navigation', () => {
    // Go to test vocab home page
    cy.visit('/groups/en/')
    // Click on groups tab and wait for the group tree to load
    cy.get('#groups').click()
    cy.get('#groups-list .list-group-item a')
    // Press tab key and check that first list item has focus
    cy.press(Cypress.Keyboard.Keys.TAB)
    cy.get('#groups-list .list-group-item').eq(0)
      .should('have.focus')
      .and('have.attr', 'role', 'treeitem')
      .and('have.attr', 'aria-expanded', 'false')
    cy.get('#groups-list .list-group-item a').eq(0)
      .and('not.have.attr', 'role', 'treeitem')
      .and('not.have.attr', 'aria-expanded')
    // Press down arrow key and check that focus is moved
    cy.press(Cypress.Keyboard.Keys.DOWN)
    cy.get('#groups-list .list-group-item').eq(1).should('have.focus')
    // Press right arrow key and check that children are loaded
    cy.press(Cypress.Keyboard.Keys.RIGHT)
    cy.get('#groups-list li ul').eq(0).children().should('have.length', 1)
    cy.get('#groups-list .list-group-item').eq(1).should('have.attr', 'aria-expanded', 'true')
    // Press right arrow key and check that focus is moved to first child
    cy.press(Cypress.Keyboard.Keys.RIGHT)
    cy.get('#groups-list .list-group-item').eq(2).should('have.focus')
    // Press right arrow key again and check that nothing happens
    cy.press(Cypress.Keyboard.Keys.RIGHT)
    cy.get('#groups-list .list-group-item').eq(2).should('have.focus')
    // Press left arrow key and check that children are closed and focus is moved
    cy.press(Cypress.Keyboard.Keys.LEFT)
    cy.get('#groups-list .list-group-item').eq(1).find('ul').should('not.exist')
    cy.get('#groups-list .list-group-item').eq(1).should('have.focus').and('have.attr', 'aria-expanded', 'false')
    // Press down up key and check that focus is moved
    cy.press(Cypress.Keyboard.Keys.UP)
    cy.get('#groups-list .list-group-item').eq(0).should('have.focus')
    // Check that pressing space opens concept page
    cy.press(Cypress.Keyboard.Keys.SPACE)
    cy.get('#concept-heading h1', {'timeout': 15000}).invoke('text').should('equal', 'Fish')
  })
})
