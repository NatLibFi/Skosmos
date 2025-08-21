describe('Global search bar', () => {
  beforeEach(() => {
    cy.visit('/fi/')
    cy.get('#search-wrapper').should('exist')
  })

  it('vocab-list has two vocabularies', () => {
    cy.get('#vocab-list li').should('have.length', 2)
  })

  it('dropdown menu header text is updated according to the selected vocabularies', () => {

    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', '1. Valitse sanasto')


    cy.get('#vocab-list li').eq(0).find('input[type="checkbox"]').check({ force: true })
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'testUnknownPropertyOrder')

    cy.get('#vocab-list li').eq(1).find('input[type="checkbox"]').check({ force: true })
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'testUnknownPropertyOrder')
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'YSO')

    cy.get('#vocab-list li').eq(0).find('input[type="checkbox"]').uncheck({ force: true })
    cy.get('#vocab-selector .vocab-dropdown-btn').should('not.contain.text', 'testUnknownPropertyOrder')
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'YSO')
  })

  it('dropdown menu header text returns to original hint if no vocabularies are selected', () => {
    cy.get('#vocab-list li').eq(1).find('input[type="checkbox"]').check({ force: true })
    cy.get('#vocab-list li').eq(1).find('input[type="checkbox"]').uncheck({ force: true })
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', '1. Valitse sanasto')
  })

  it('changing the search language changes the language selector dropdown header text', () => {

    cy.get('#language-selector .dropdown-toggle').should('contain.text', '2. Valitse kieli')

    cy.get('#language-list li').contains('englanti').find('input[type="radio"]').check({ force: true })
    cy.get('#language-selector .dropdown-toggle').should('contain.text', 'englanti')

    cy.get('#language-list li').contains('ruotsi').find('input[type="radio"]').check({ force: true })
    cy.get('#language-selector .dropdown-toggle').should('contain.text', 'ruotsi')
  })
})
