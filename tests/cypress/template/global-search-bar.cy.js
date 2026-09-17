describe('Global search bar', () => {
  beforeEach(() => {
    cy.visit('/fi/')
    cy.get('#search-wrapper').should('exist')
    cy.get('#global-search-toggle').click()
  })

  it('Vocab-list has 14 vocabularies', () => {
    cy.get('#vocab-list li').should('have.length', 14)
  })

  it('dropdown menu header text is updated according to the selected vocabularies', () => {
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'kaikki sanastot')
    // select "altlabel"
    cy.get('#vocab-list').contains('label', 'altlabel').find('input[type="checkbox"]').check({ force: true })
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'altlabel')
    // select "YSO"
    cy.get('#vocab-list').contains('label', 'YSO').find('input[type="checkbox"]').check({ force: true })
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'altlabel')
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'YSO')
    // unselect "altlabel"
    cy.get('#vocab-list').contains('label', 'altlabel').find('input[type="checkbox"]').uncheck({ force: true })
    cy.get('#vocab-selector .vocab-dropdown-btn').should('not.contain.text', 'altlabel')
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'YSO')
  })

  it('Dropdown menu header text returns to original hint if no vocabularies are selected', () => {
    cy.get('#vocab-list li').eq(1).find('input[type="checkbox"]').check({ force: true })
    cy.get('#vocab-list li').eq(1).find('input[type="checkbox"]').uncheck({ force: true })
    cy.get('#vocab-selector .vocab-dropdown-btn').should('contain.text', 'kaikki sanastot')
  })

  it('changing the search language changes the language selector dropdown header text', () => {

    cy.get('#language-selector .dropdown-toggle').should('contain.text', 'suomi')
    cy.get('#language-selector .dropdown-toggle').click()
    cy.get('#language-list').should('be.visible')

    cy.get('#language-list li').contains('label', 'englanti').click()
    cy.get('#language-selector .dropdown-toggle').should('contain.text', 'englanti')
    cy.get('#language-list').should('be.hidden')

    cy.get('#language-selector .dropdown-toggle').parent().click()
    cy.get('#language-list li').contains('label', 'ruotsi').click()
    cy.get('#language-selector .dropdown-toggle').should('contain.text', 'ruotsi')
  })

  it('Selecting "all languages" does not change content language', () => {

    cy.get('#language-selector .dropdown-toggle').should('contain.text', 'suomi')
    cy.get('#language-list li label').find('input[type="radio"][value="en"]').check({ force: true })
    cy.url().should('include', 'clang=en')

    cy.get('#language-selector .dropdown-toggle').click()
    cy.get('#language-list').should('be.visible')
    cy.get('#language-list li').contains('label', 'kaikki kielet').click()
    cy.url().should('include', 'clang=en')
  })

  it('Dropdown search results are displayed for the selected vocabulary and search language', () => {

    cy.get('#vocab-list').contains('label', 'YSO').find('input[type="checkbox"]').check({ force: true })
    cy.get('#language-selector .dropdown-toggle').click()
    cy.get('#language-list li').contains('label', 'suomi').click()

    cy.get('#search-field').type('arkeolog') // even if the search yields no results, there shoulde a single line in the result list
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible').children().should('have.length.greaterThan', 0)
    cy.get('#search-autocomplete-results').within(() => {
      cy.get('li').should('have.length', 5)
    })
  })

  it('No results message is displayed if no results are found', () => {

    cy.get('#global-search-toggle').click()
    cy.get('#vocab-list li').eq(0).find('input[type="checkbox"]').check({ force: true })
    cy.get('#language-selector .dropdown-toggle').click()
    cy.get('#language-list li').contains('label', 'ruotsi').click()

    cy.get('#search-field').type('kissa') // even if the search yields no results, there shoulde a single line in the result list
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible').children().should('have.length.greaterThan', 0)
    cy.get('#search-autocomplete-results').within(() => {
      cy.get('li').eq(0).invoke('text').should('contain', 'Ei tuloksia') // the single result should display a no results message
    })
  })

  it('Clear button should hide the autocomplete list', () => {
    // go to landing page
    cy.visit('/en/')

    // open search bar
    cy.get('#global-search-toggle').click()

    cy.get('#search-field').type('kas')
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible') // the autocomplete should appear

    cy.get('#clear-button').should('have.attr', 'aria-label', 'Clear search field') // the clear search button should have an aria label

    cy.get('#clear-button').click()
    cy.get('#search-autocomplete-results').should('not.be.visible') // the autocomplete should disappear

    // check that the focus is moved to the search field
    cy.get('#search-field').should('be.focused')
  })

  it('Emptying the text search field hides the autocomplete list', () => {

    cy.get('#search-field').type('kissa')
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible') // the autocomplete should appear

    cy.get('#search-field').clear()
    cy.get('#search-autocomplete-results').should('not.be.visible') // the autocomplete should disappear
  })

  it('Clicking outside of the autocomplete list hides the autocomplete list', () => {

    cy.get('#search-field').type('kissa')
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible') // the autocomplete should appear

    cy.get('#main-container').click({ force: true }) // using force true to click on elements not considered actionable
    cy.get('#search-autocomplete-results').should('not.be.visible') // the autocomplete should disappear
  })

  it('Autocomplete search result list contains concept types', () => {
    // go to landing page
    cy.visit('/en/')

    // open search bar
    cy.get('#global-search-toggle').click()

    // select a vocabulary
    cy.contains('#vocab-list li label.vocab-select', 'test-notation-sort').parents('li').find('input[type="checkbox"]').check({ force: true })

    // Choose English from the language dropdown
    cy.get('#language-selector .dropdown-toggle').click()
    cy.get('#language-list .dropdown-item').contains('English').click()

    // Enter a search term
    cy.get('#search-field').type('Barra')

    // Autocomplete should appear
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible')

    // Verify the dropdown should have the concept type literal
    cy.get('#search-autocomplete-results').within(() => {
      cy.get('li').first().should('contain', 'Test class')
    })
  })

  it('Autocomplete search result list concept types are translated', () => {
    // go to landing page
    cy.visit('/en/')

    // open search bar
    cy.get('#global-search-toggle').click()

    // select a vocabulary
    cy.contains('#vocab-list li label.vocab-select', 'conceptPropertyLabels').parents('li').find('input[type="checkbox"]').check({ force: true })

    // Choose English from the language dropdown
    cy.get('#language-selector .dropdown-toggle').click()
    cy.get('#language-list .dropdown-item').contains('English').click()

    // Enter a search term
    cy.get('#search-field').type('Fish')

    // Autocomplete should appear
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible')

    // Verify the dropdown should have the concept type literal
    cy.get('#search-autocomplete-results').within(() => {
	  cy.get('li').first().find('div.col-auto.align-self-end.pr-1').should('have.text', 'Concept')
    })
  })

  it ('Autocomplete search result links point to concept pages', () => {
    // go to test vocab
    cy.visit('/en/')

    // open search bar
    cy.get('#global-search-toggle').click()

    // select a vocabulary
    cy.contains('#vocab-list li label.vocab-select', 'test-notation-sort').parents('li').find('input[type="checkbox"]').check({ force: true })

    // Choose English from the language dropdown
    cy.get('#language-selector .dropdown-toggle').click()
    cy.get('#language-list .dropdown-item').contains('English').click()

    // Enter a search term
    cy.get('#search-field').type('Barra')

    // Autocomplete should appear
    cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible')
    cy.get('#search-autocomplete-results').within(() => {
      cy.get('li').first().click()
    })

    // Verify the search took us to the concept page
    cy.url().should('include', 'uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta0116')

  })

  describe('Keyboard navigation', () => {
    beforeEach(() => {
      cy.visit('/fi/')
      cy.get('#global-search-toggle').click()
      cy.get('#search-wrapper').should('exist')
    })

    const getVocabButton = () => cy.get('#vocab-selector .dropdown-toggle').first()
    const getLangButton = () => cy.get('#language-selector .dropdown-toggle').first()

    const press = (key) => cy.focused().type(`{${key}}`)

    it('Left/Right: navigate between vocab selector and language selector', () => {
      getVocabButton().focus().should('be.focused')

      press('rightarrow')
      getLangButton().should('be.focused')

      press('leftarrow')
      getVocabButton().should('be.focused')
    })

    it('Arrow down opens the vocabulary dropdown when vocab selector is focused', () => {
      getVocabButton().focus().should('be.focused')
      press('downarrow')

      cy.get('#vocab-selector .dropdown-menu').should('have.class', 'show')
    })

    it('Escape closes the vocabulary dropdown', () => {
      getVocabButton().focus()
      press('downarrow')
      cy.get('#vocab-selector .dropdown-menu').should('have.class', 'show')

      cy.focused().type('{esc}')
      cy.get('#vocab-selector .dropdown-menu').should('not.have.class', 'show')

    })

    it('Arrow up on top item closes the vocabulary dropdown', () => {
      getVocabButton().focus()
      press('downarrow')
      cy.get('#vocab-selector .dropdown-menu').should('have.class', 'show')

      cy.focused().type('{uparrow}')
      cy.get('#vocab-selector .dropdown-menu').should('not.have.class', 'show')
    })

    it('Arrow up / drrow down navigates within vocabulary list', () => {
      getVocabButton().focus()
      press('downarrow')
      cy.get('#vocab-selector .dropdown-menu').should('have.class', 'show')

      cy.get('#vocab-list li').first().as('firstItem').get('input').should('be.focused')

      cy.focused().type('{downarrow}')
      cy.get('#vocab-list li').eq(1).get('input').should('be.focused')

      cy.focused().type('{downarrow}')
      cy.get('#vocab-list li').eq(2).get('input').should('be.focused')

      cy.focused().type('{uparrow}')
      cy.get('#vocab-list li').eq(1).get('input').should('be.focused')
    })

    it('Enter toggles a vocabulary in the dropdown', () => {
      getVocabButton().focus()
      press('downarrow')
      cy.get('#vocab-selector .dropdown-menu').should('have.class', 'show')

      cy.get('#vocab-list li').eq(1).focus().should('be.focused')
      cy.focused().type('{enter}')

      cy.get('#vocab-list li').eq(1).get('input').should('be.checked')
    })

    it('Arrow down opens the language dropdown when language selector is focused', () => {
      getLangButton().focus().should('be.focused')
      cy.focused().type('{downarrow}')
      cy.get('#language-selector .dropdown-menu').should('have.class', 'show')
    })

    it('Escape closes the language dropdown', () => {
      getLangButton().click()
      cy.get('#language-selector .dropdown-menu').should('have.class', 'show')

      cy.focused().type('{esc}')
      cy.get('#language-selector .dropdown-menu').should('not.have.class', 'show')
    })

    it('Enter selects a language in the language dropdown', () => {
      getLangButton().click()
      cy.get('#language-selector .dropdown-menu').should('have.class', 'show')
      cy.focused().type('{downarrow}')

      cy.focused().type('{enter}')

      cy.get('#language-selector .dropdown-toggle').should('contain.text', 'englanti')
    })
  })

  describe('Translations', () => {

    it('Global search bar has correct translations', () => {

      cy.visit('/en/')

      cy.get('#global-search-toggle').click()
      cy.get('#search-wrapper').should('exist')

      // Check that vocabulary selector has correct place holder text
      cy.get('#vocab-selector button').should('have.text', 'all vocabularies')
      // Check that vocabulary selector has correct label
      cy.get('#vocab-selector-label').should('contain', 'Choose vocabulary')
      // Check that search language selector has correct place holder text
      cy.get('#language-selector button').should('have.text', 'English')
      // Check that search language selector has correct label
      cy.get('#content-language-label').should('contain', 'Content language')
      // Check that search field has correct label
      cy.get('label[for="search-field"]').should('contain', 'Enter search term')
      // Check that search button has correct aria-label
      cy.get('#search-button').should('have.attr', 'aria-label', 'Search')
      // Check that search language list has correctly translated text for anylang selector
      cy.get('#language-list li').contains('label', 'all languages')
      // Check that search results have correct message when no results were found
      cy.get('#search-field').type('Ei tuloksia')
      cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').eq(0).invoke('text').should('contain', 'No results')
      })
      // the clear search button should have an aria label
      cy.get('#clear-button').should('have.attr', 'aria-label', 'Clear search field')


      // go to YSO vocab front page in Finnish
      cy.visit('/fi/')

      cy.get('#global-search-toggle').click()
      cy.get('#search-wrapper').should('exist')

      // Check that vocabulary selector has correct place holder text
      cy.get('#vocab-selector button').should('have.text', 'kaikki sanastot')
      // Check that vocabulary selector has correct label
      cy.get('#vocab-selector-label').should('contain', 'Valitse sanasto')
      // Check that search language selector has correct label
      cy.get('#content-language-label').should('contain', 'Sisällön kieli')
      // Check that search field has correct label
      cy.get('label[for="search-field"]').should('contain', 'Syötä haettava termi')
      // Check that search button has correct aria-label
      cy.get('#search-button').should('have.attr', 'aria-label', 'Hae')
      // Check that search language list has correctly translated text for anylang selector
      cy.get('#language-list li').contains('label', 'kaikki kielet')
      // Check that search results have correct message when no results were found
      cy.get('#search-field').type('Ei tuloksia')
      cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').eq(0).invoke('text').should('contain', 'Ei tuloksia')
      })
      // the clear search button should have an aria label
      cy.get('#clear-button').should('have.attr', 'aria-label', 'Tyhjennä hakukenttä')

    })
  })
})
