describe('Plugins', () => {
  it('Vanilla JS plugin is loaded on vocab home page', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin/en')
    // check that plugin is loaded in the correct place and has the correct text
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin #vanilla-js-plugin-message').invoke('text').should('contain', 'Current vocab: testPlugin')
    // check that CSS is applied correctly
    cy.get('#vanilla-js-plugin #vanilla-js-plugin-message').should('have.css', 'color', 'rgb(0, 0, 255)')
  })
  it('Vue plugin is not loaded on vocab home page', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin/en')
    // check that plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('not.exist')
  })
  it('Vanilla JS plugin is not loaded on concept page', () => {
    // go to the testPlugin concept page
    cy.visit('/testPlugin/en/page/qb1')
    // check that plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin').should('not.exist')
  })
  it('Vue plugin is loaded on concept page', () => {
    // go to the testPlugin concept page
    cy.visit('/testPlugin/en/page/qb1')
    // check that plugin is loaded in the correct place and has the correct text
    cy.get('#headerbar-bottom-slot').find('#vue-plugin #vue-plugin-message').invoke('text').should('contain', 'Current concept: http://www.skosmos.skos/test/qb1')
    // check that CSS is applied correctly
    cy.get('#vue-plugin #vue-plugin-message').should('have.css', 'color', 'rgb(255, 0, 0)')
  })
  it('Vanilla JS plugin is not loaded on YSO vocab home page', () => {
    // go to the YSO vocab page
    cy.visit('/yso/en')
    // check that plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin').should('not.exist')
  })
  it('Vue JS plugin is not loaded on YSO concept page', () => {
    // go to the YSO vocab page
    cy.visit('/yso/en/page/p10416')
    // check that plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('not.exist')
  })
  it('Vanilla JS plugin is removed on partial page load', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin')
    // check that plugin is loaded in the correct place 
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin')
    // click on concept link
    cy.get('#tab-alphabetical .sidebar-list a').eq(0).click()
    // check that plugin does not exit after partial page load
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin').should('not.exist')
  })
  it('Vue plugin is changed on partial page load', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin/en/page/qb1')
    // check that plugin is loaded in the correct place and has the correct text
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('contain', 'Current concept: http://www.skosmos.skos/test/qb1')
    // click on concept link
    cy.get('#tab-alphabetical .sidebar-list a').eq(1).click()
    // check that plugin has the correct text after partial page load
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('contain', 'Current concept: http://www.skosmos.skos/test/qn1')
  })
})
