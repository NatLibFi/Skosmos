describe('Global search bar', () => {
  beforeEach(() => {
    cy.visit('/fi/')
    cy.get('#search-wrapper').should('exist')
  })

  it('vocab-list has two vocabularies', () => {
    cy.get('#vocab-list li').should('have.length', 2)
  })

  it('dropdown menu header text is updated according to the selected vocabularies', () => {

    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', '1. Choose vocabulary')


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
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', '1. Choose vocabulary')
  })

  it('changing the search language changes the language selector dropdown header text', () => {

    cy.get('#language-selector .dropdown-toggle').should('contain.text', '2. Choose language')

    cy.get('#language-list li').contains('englanti').find('input[type="radio"]').check({ force: true })
    cy.get('#language-selector .dropdown-toggle').should('contain.text', 'englanti')

    cy.get('#language-list li').contains('ruotsi').find('input[type="radio"]').check({ force: true })
    cy.get('#language-selector .dropdown-toggle').should('contain.text', 'ruotsi')
  })

  it('selecting "all languages" does not change content language', () => {

    cy.get('#language-selector .dropdown-toggle').should('contain.text', '2. Choose language')
    cy.get('#language-list li label').find('input[type="radio"][value="en"]').check({ force: true })
    cy.url().should('include', 'clang=en')

    cy.get('#language-list li label').find('input[type="radio"][value="all"]').check({ force: true })
    cy.url().should('include', 'clang=en')
  })

    it('Dropdown search results are displayed for the selected vocabulary and search language', () => {

    cy.get('#global-search-toggle').click()
    cy.get('#vocab-list li').eq(1).find('input[type="checkbox"]').check({ force: true })
    cy.get('#language-list li').contains('suomi').find('input[type="radio"]').check({ force: true })

    cy.get('#search-field').type('arkeolog'); // even if the search yields no results, there shoulde a single line in the result list
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible').children().should('have.length.greaterThan', 0);
    cy.get('#search-autocomplete-results').within(() => {
      cy.get('li').should('have.length', 5)
    })
  })

  it('No results message is displayed if no results are found', () => {

    cy.get('#global-search-toggle').click()
    cy.get('#vocab-list li').eq(0).find('input[type="checkbox"]').check({ force: true })
    cy.get('#language-list li').contains('ruotsi').find('input[type="radio"]').check({ force: true })

    cy.get('#search-field').type('kissa'); // even if the search yields no results, there shoulde a single line in the result list
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible').children().should('have.length.greaterThan', 0);
    cy.get('#search-autocomplete-results').within(() => {
      cy.get('li').eq(0).invoke('text').should('contain', 'Ei tuloksia') // the single result should display a no results message
    })
  })

      it('Clear button should hide the autocomplete list', () => {
      // go to YSO vocab front page
      cy.visit('/yso/en/')

      cy.get('#search-field').type('kas');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#clear-button').click()
      cy.get('#search-autocomplete-results').should('not.be.visible'); // the autocomplete should disappear
    })

    it('Emptying the text search field hides the autocomplete list', () => {

      cy.get('#global-search-toggle').click()
      cy.get('#search-field').type('kissa');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#search-field').clear();
      cy.get('#search-autocomplete-results').should('not.be.visible'); // the autocomplete should disappear
    })

    it('Clicking outside of the autocomplete list hides the autocomplete list', () => {

      cy.get('#global-search-toggle').click()
      cy.get('#search-field').type('kissa');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#main-container').click({ force: true }); // using force true to click on elements not considered actionable
      cy.get('#search-autocomplete-results').should('not.be.visible'); // the autocomplete should disappear
    })
})
