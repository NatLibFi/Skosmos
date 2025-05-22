describe('Plugins', () => {
  it('Plugins are loaded correctly on vocab home page', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin/en')
    // check that Vanilla JS plugin is loaded in the correct place and has the correct text
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin #vanilla-js-plugin-message').invoke('text').should('contain', 'Current vocab: testPlugin')
    // check that CSS is applied correctly
    cy.get('#vanilla-js-plugin #vanilla-js-plugin-message').should('have.css', 'color', 'rgb(0, 0, 255)')
    // check that Vue plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('not.exist')
  })
  it('Plugins are loaded correctly on concept page', () => {
    // go to the testPlugin concept page
    cy.visit('/testPlugin/en/page/qb1')
    // check that Vue plugin is loaded in the correct place and has the correct text
    cy.get('#headerbar-bottom-slot').find('#vue-plugin #vue-plugin-message').invoke('text').should('contain', 'Current concept: http://www.skosmos.skos/test/qb1')
    // check that CSS is applied correctly
    cy.get('#vue-plugin #vue-plugin-message').should('have.css', 'color', 'rgb(255, 0, 0)')
    // check that Vanilla JS plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin').should('not.exist')
  })
  it('Plugins are not loaded on YSO vocab home page', () => {
    // go to the YSO vocab page
    cy.visit('/yso/en')
    // check that Vanilla JS plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin').should('not.exist')
    // check that Vue plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('not.exist')
  })
  it('Plugins are updated on partial page load', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin/en')
    // check that vanilla JS plugin is loaded in the correct place 
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin')
    // check that Vue plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('not.exist')
    // click on concept link
    cy.get('#tab-alphabetical .sidebar-list a').eq(0).click()
    // check that Vanilla JS plugin does not exit after partial page load
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin').should('not.exist')
    // check that Vue plugin has the correct text after partial page load
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('contain', 'Current concept: http://www.skosmos.skos/test/qb1')
  })
})
