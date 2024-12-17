describe('Vocab search bar', () => {

  describe('Search Language', () => {
    it('search can be done with a chosen content language', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      // Select a language option from the dropdown
      cy.get('#language-selector .dropdown-toggle').click();
      cy.get('#language-list .dropdown-item').contains('ruotsi').click();

      // Enter a search term
      cy.get('#search-wrapper input').type('Katt');

      // Click the search button
      cy.get('#search-button').click();

      // Verify the search page url (search result page tests are elsewhere)
      cy.url().should('include', 'q=Katt').and('include', 'clang=sv');
    })

    it('search can be done with all languages', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      // Choose 'all' languages from the dropdown
      cy.get('#language-selector .dropdown-toggle').click();
      cy.get('#language-list .dropdown-item').contains('kaikilla kielillä').click();

      // Enter a search term
      cy.get('#search-wrapper input').type('Katt');

      // Click the search button
      cy.get('#search-button').click();

      // Verify the search page url (search result page tests are elsewhere)
      cy.url().should('include', 'q=Katt').and('include', 'anylang=true');
    })

    it('search with all languages retains the previously chosen content language', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      // Choose 'sv' for search & content language
      cy.get('#language-selector .dropdown-toggle').click();
      cy.get('#language-list .dropdown-item').contains('ruotsi').click();

      // Choose 'all' for search language
      cy.get('#language-selector .dropdown-toggle').click();
      cy.get('#language-list .dropdown-item').contains('kaikilla kielillä').click();

      // Verify the search page url has the previously chosen language as the content language
      cy.url().should('include', 'clang=sv');
    })

    it('available search languages are the ones described in the vocabulary config', () => {
      cy.visit('/yso/en/') // go to the YSO home page in English language

      // check that the vocabulary languages can be found in the search bar language dropdown menu
      cy.window().then((win) => {
        cy.get('#language-list .dropdown-item').then($elements => {
          const actualLanguages = $elements.map((index, el) => Cypress.$(el).attr('value')).get();

          const expectedLanguages = ['fi', 'sv', 'se', 'en', 'all'];

          // The two language lists should be of equal length and all of the expected languages can be found
          expect(expectedLanguages).to.have.lengthOf(actualLanguages.length);
          expectedLanguages.forEach(lang => { expect(actualLanguages).to.include(lang); });
        })
      })
    })
  });

  describe('Autocomplete', () => {
    it('Writing in the text field triggers the autocomplete results list', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      cy.get('#search-field').type('kas'); // perform autocomplete search
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible').children().should('have.length.greaterThan', 2);
    })

    it('Special characters can be used in the search', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      cy.get('#search-field').type('*tus (*');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#search-autocomplete-results').within(() => { // the first result should have text ajoitus (historia)
        cy.get('li').first().should('contain', 'ajoitus (historia)')
      })
    })

    it('No results message is displayed if no results are found', () => {
      // go to YSO vocab front page
      cy.visit('/yso/en/')

      cy.get('#search-field').type('kissa'); // even if the search yields no results, there shoulde a single line in the result list
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible').children().should('have.length.greaterThan', 0);
      cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').eq(0).invoke('text').should('contain', 'No results') // the single result should display a no results message
      })
    })

    it('No results are displayed for autocomplete if there is not at leas two charecters in the search term', () => {
      // go to YSO vocab front page
      cy.visit('/yso/en/')

      cy.get('#search-field').type('k'); // even if the search yields no results, there shoulde a single line in the result list
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('not.be.visible');
    })

    it('The autocomplete list should not change due to previous searches completing', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      cy.get('#search-field').type('ka');
      cy.wait(300);
      cy.get('#search-field').type('i');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear
      cy.get('#search-autocomplete-results').children().should('have.length', 1)
      cy.wait(5000); // wait extra 5 seconds to see if the 'ka' search adds results to the list
      cy.get('#search-autocomplete-results').children().should('have.length', 1)
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
      // go to YSO vocab front page
      cy.visit('/yso/en/')

      cy.get('#search-field').type('kis');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#search-field').clear();
      cy.get('#search-autocomplete-results').should('not.be.visible'); // the autocomplete should disappear
    })

    it('Clicking outside of the autocomplete list hides the autocomplete list', () => {
      // go to YSO vocab front page
      cy.visit('/yso/en/')

      cy.get('#search-field').type('kas');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#main-container').click({ force: true }); // using force true to click on elements not considered actionable
      cy.get('#search-autocomplete-results').should('not.be.visible'); // the autocomplete should disappear
    })
    it('Search language parameter is passed to the autocomplete result links', () => {
      cy.visit('/yso/sv/')

      // Choose 'fi' for search & content language
      cy.get('#language-selector .dropdown-toggle').click();
      cy.get('#language-list .dropdown-item').contains('finska').click();

      // Searchg for 'kissa'
      cy.get('#search-field').type('aarre');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      // Click the first search result
      cy.get('#search-autocomplete-results li:first-child a').click();

      // The language parameters should persist on the concept page
      cy.url().should('include', '/sv/');
      cy.url().should('include', 'clang=fi');
    })
  });

  describe('Search Result Rendering', () => {
    it('AltLabel search results should bold the matching parts of altLabel', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      cy.get('#search-field').type('assyro');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#search-autocomplete-results').within(() => { // the first result should have matching part of text 'assyrologia' appearing in bold
        cy.get('li').last().find('b').eq(0).should('have.text', 'assyro')
      })
    })

    it('AltLabel search results should be displayed in italics', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      cy.get('#search-field').type('assyro');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#search-autocomplete-results').within(() => { // the first result should have text 'assyrologia' appearing in italics
        cy.get('li').last().find('i').eq(0).should('contain.text', 'assyrologia')
      })
    })

    it('Notation search results should bold the matching parts of the notation', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      cy.get('#search-field').type('51');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      cy.get('#search-autocomplete-results').within(() => { // the first result should have text '51' appearing in bold
        cy.get('li').last().find('b').eq(0).should('have.text', '51')
      })
    })
  });
})
