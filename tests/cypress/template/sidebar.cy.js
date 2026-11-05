describe('Sidebar', () => {
  it('Contains tabs', () => {
    // go to the Skosmos front page
    cy.visit('/')
    // click on the first vocabulary in the list
    cy.get('#vocabulary-list').find('a').first().click()
    // check that at least one nav-item exists and that it has a correctly formatted id
    cy.get('#sidebar-tabs').find('.nav-item').first().invoke('attr', 'id').should('match', /\b(alphabetical|fullalphabetical|hierarchy|groups|changes)\b/)
    // check that at least one tab-pane exists and that it has a correctly formatted id
    cy.get('.tab-content').find('.tab-pane').first().invoke('attr', 'id').should('match', /\b(tab-(alphabetical|fullalpabetical|hierarchy|groups|changes))\b/)
    // check that there is exactly one active tab
    cy.get('#sidebar-tabs').find('.active').should('have.length', 1)
    cy.get('.tab-content').find('.active').should('have.length', 1)
  })

  it('Contains collapse button on mobile', () => {
    // Go to YSO home page
    cy.visit('/yso/fi/')
    // Check that collapse button is not visible and sidebar is visible
    cy.get('#sidebar-collapse-btn').should('not.be.visible')
    cy.get('#sidebar-col').should('be.visible')
    // Change viewport size to mobile
    cy.viewport(402, 874)
    // Check that button is visible and contains correct text
    cy.get('#sidebar-collapse-btn').should('be.visible')
    cy.get('#sidebar-collapse-btn').invoke('text').should('contain', 'Selaa käsitteitä')
    // Check that sidebar is not visible
    cy.get('#sidebar-col').should('not.be.visible')
    // Click button and check that it has correct text
    cy.get('#sidebar-collapse-btn').click()
    cy.get('#sidebar-collapse-btn').invoke('text').should('contain', 'Piilota käsitteet')
    // Check that sidebar is visible
    cy.get('#sidebar-col').should('be.visible')
    // Check that alphabetical tab has correct width
    cy.get('.sidebar-list').eq(0).then(($el) => {
      const width = $el[0].getBoundingClientRect().width
      expect(width, 'element width').to.be.greaterThan(402 * 0.8) // 80% of viewport width
    })
    // Click button again and check that everything is toggled correctly
    cy.get('#sidebar-collapse-btn').click()
    cy.get('#sidebar-collapse-btn').invoke('text').should('contain', 'Selaa käsitteitä')
    cy.get('#sidebar-col').should('not.be.visible')
  })

  it('Keyboard navigation', () => {
    // Go to YSO home page
    cy.visit('/yso/fi/')
    // Focus on first tab
    cy.get('#sidebar-tabs').find('.nav-link').first().focus()
    // Check that alphabetical list is shown
    cy.get('.tab-content').find('#tab-alphabetical').should('exist')
    // Press right arrow key
    cy.press(Cypress.Keyboard.Keys.RIGHT)
    // Check that hierarchy is loaded
    cy.get('#hierarchy-list ul').should('exist')
  })
})
