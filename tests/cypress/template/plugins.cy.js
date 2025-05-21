describe('Plugins', () => {
  it('Vanilla JS plugin is loaded on landing page', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin')
    // check that plugin is loaded in the correct place and has the correct text
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin #vanilla-js-plugin-message').invoke('text').should('contain', 'Vanilla JS plugin')
    // check that CSS is applied correctly
    cy.get('#vanilla-js-plugin #vanilla-js-plugin-message').should('have.css', 'color', 'rgb(0, 0, 255)')
  })
  it('Vue plugin is loaded on landing page', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin')
    // check that plugin is loaded in the correct place and has the correct text
    cy.get('#headerbar-bottom-slot').find('#vue-plugin #vue-plugin-message').invoke('text').should('contain', 'Vue plugin')
    // check that CSS is applied correctly
    cy.get('#vue-plugin #vue-plugin-message').should('have.css', 'color', 'rgb(255, 0, 0)')
  })
  it('Vanilla JS plugin is not loaded on vocab home', () => {
    // go to the YSO vocab page
    cy.visit('/yso/en')
    // check that plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vanilla-js-plugin').should('not.exist')
  })
  it('Vue JS plugin is not loaded on vocab home', () => {
    // go to the YSO vocab page
    cy.visit('/yso/en')
    // check that plugin does not exist
    cy.get('#headerbar-bottom-slot').find('#vue-plugin').should('not.exist')
  })
  it('Plugin order is correct', () => {
    // go to the testPlugin vocab page
    cy.visit('/testPlugin')
    // check that the first plugin is correct
    cy.get('#headerbar-bottom-slot').find('div').eq(0).invoke('text').should('contain', 'Vue plugin')
    // check that the second plugin is correct
    cy.get('#headerbar-bottom-slot').find('div').eq(1).invoke('text').should('contain', 'Vanilla JS plugin')
  })
})
