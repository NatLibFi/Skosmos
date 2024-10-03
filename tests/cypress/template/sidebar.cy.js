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
})
