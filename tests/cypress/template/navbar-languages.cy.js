describe('Navbar languages', () => {

  it('Sets and reads SKOSMOS_LANGUAGE cookie correctly', () => {
    cy.visit('/')

    cy.get('.nav-item.language a').first().click()
    cy.getCookie('SKOSMOS_LANGUAGE').should('exist')
    cy.getCookie('SKOSMOS_LANGUAGE').should('have.property', 'value', 'fi')

    cy.url().should('include', '/fi/')

    cy.visit('/')
    cy.url().should('include', '/fi/')
  })
})
