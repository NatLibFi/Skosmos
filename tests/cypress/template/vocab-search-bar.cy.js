describe('Vocab search bar', () => {

  describe('Search Language', () => {
    it('search can be done with a chosen content language', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      // Select a language option from the dropdown
      cy.get('#language-selector button').click();
      cy.get('#language-selector .dropdown-item').contains('ruotsi').click();

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
      cy.get('#language-selector button').click();
      cy.get('#language-selector .dropdown-item').contains('kaikki kielet').click();

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
      cy.get('#language-selector button').click();
      cy.get('#language-selector .dropdown-item').contains('ruotsi').click();

      //SKOSMOS object should have Swedish as the content language
      cy.window().then((win) => {
        expect(win.SKOSMOS.content_lang).to.equal('sv');
      })

      // Choose 'all' for search language
      cy.get('#language-selector button').click();
      cy.get('#language-selector .dropdown-item').contains('kaikki kielet').click();

      // Verify the search page url has the previously chosen language as the content language
      cy.url().should('include', 'clang=sv');

      //SKOSMOS object should have Swedish as the content language
      cy.window().then((win) => {
        expect(win.SKOSMOS.content_lang).to.equal('sv');
      })
    })

    it('available search languages are the ones described in the vocabulary config', () => {
      cy.visit('/yso/en/') // go to the YSO home page in English language

      // check that the vocabulary languages can be found in the search bar language dropdown menu
      cy.window().then((win) => {
        cy.get('#language-selector .dropdown-item').then($elements => {
          const actualLanguages = $elements.map((index, el) => Cypress.$(el).text()).get();

          const expectedLanguages = ['Finnish','English','Northern Sami','Swedish','all languages'];
          expect(expectedLanguages).to.have.lengthOf(actualLanguages.length);
          expectedLanguages.forEach(lang => { expect(actualLanguages).to.include(lang); });
        })
      })
    })
  })

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

      cy.get('#clear-button').should('have.attr', 'aria-label', 'Clear search field'); // the clear search button should have an aria label

      cy.get('#clear-button').click()
      cy.get('#search-autocomplete-results').should('not.be.visible'); // the autocomplete should disappear

      // check that the focus is moved to the search field
      cy.get('#search-field').should('be.focused')
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
      cy.get('#language-selector button').click();
      cy.get('#language-selector .dropdown-item').contains('finska').click();

      // Searchg for 'kissa'
      cy.get('#search-field').type('aarre');
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible'); // the autocomplete should appear

      // Click the first search result
      cy.get('#search-autocomplete-results li:first-child a').click();

      // The language parameters should persist on the concept page
      cy.url().should('include', '/sv/');
      cy.url().should('include', 'clang=fi');
    })
    it('Autocomplete search result list contains concept types', () => {
      // go to test vocab
      cy.visit('/test/en/')

      // Choose English from the language dropdown
      cy.get('#language-selector button').click();
      cy.get('#language-selector .dropdown-item').contains('English').click();

      // Enter a search term
      cy.get('#search-wrapper input').type('Bass');

      // Autocomplete should appear
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible');

      // Verify the dropdown should have the concept type literal
      cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').first().should('contain', 'Test class')
      })
    })
    it('Autocomplete search result list concept types are translated', () => {
      // go to test vocab
      cy.visit('/groups/en/')

      // Choose English from the language dropdown
      cy.get('#language-selector button').click();
      cy.get('#language-selector .dropdown-item').contains('English').click();

      // Enter a search term
      cy.get('#search-wrapper input').type('Fish');

      // Autocomplete should appear
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible');

      // Verify the dropdown should have the concept type literal
      cy.get('#search-autocomplete-results').within(() => {
	cy.get('li').first().find('div.col-auto.align-self-end.pr-1').should('have.text', 'Collection')
      })
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
    it('Long autocomplete result list should have a scroll bar', () => {
      // go to YSO vocab front page
      cy.visit('/yso/fi/')

      // resize the window to smaller size
      cy.viewport(1200, 600)

      // type a search term and wait for the autocomplete to appear
      cy.get('#search-field').type('mu')
      cy.get('#search-autocomplete-results').should('be.visible')

      // the result list should have a CSS-based scroll
      cy.get('#search-autocomplete-results').should('have.css', 'overflow-y', 'auto')
      cy.get('#search-autocomplete-results').should('have.css', 'max-height', '300px')
    })
  });

  describe('Translations', () => {
    it('Has correct translations', () => {
      // go to YSO vocab front page in English
      cy.visit('/yso/en/')
      // Check that language selector has correct label
      cy.get('#content-language-label').should('contain', 'Content language')
      // Check that search field has correct label
      cy.get('label[for="search-field"]').should('contain', 'Enter search term')
      // Check that search button has correct Aria label
      cy.get('#search-button').should('have.attr', 'aria-label', 'Search')
      // Check that search results have correct message when no results were found
      cy.get('#search-field').type('No results')
      cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').eq(0).invoke('text').should('contain', 'No results') // the single result should display a no results message
      })

      // go to YSO vocab front page in Finnish
      cy.visit('/yso/fi/')
      // Check that language selector has correct label
      cy.get('#content-language-label').should('contain', 'Sisällön kieli')
      // Check that search field has correct label
      cy.get('label[for="search-field"]').should('contain', 'Syötä haettava termi')
      // Check that search field has correct Aria label
      cy.get('#search-button').should('have.attr', 'aria-label', 'Hae')
      // Check that search results have correct message when no results were found
      cy.get('#search-field').type('No results')
      cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').eq(0).invoke('text').should('contain', 'Ei tuloksia') // the single result should display a no results message
      })

    })
  })
  describe('Keyboard navigation', () => {
    it('Content language can be chosen with keyboard', () => {
      cy.visit('/yso/fi/')

      cy.get('#language-selector button')
        .focus()
        .should('have.focus')

      cy.focused().type('{downarrow}{downarrow}')
      cy.focused().should('contain.text', 'englanti')

      cy.focused().type('{enter}')
      cy.url().should('include', 'clang=en')
    })

    it('Arrow up on the first language item closes the dropdown without moving focus into the list', () => {
      cy.visit('/yso/fi/')

      cy.get('#language-selector button').focus()
      cy.focused().type('{downarrow}')
      cy.get('#language-list li').first().should('be.focused')

      cy.focused().type('{uparrow}')
      cy.get('#language-selector .dropdown-menu').should('not.have.class', 'show')
      cy.get('#language-selector button').should('be.focused')
    })

    it('Escape closes the language dropdown without changing the language, and Arrow down after re-opening focuses the first item', () => {
      cy.visit('/yso/fi/')

      cy.get('#language-selector button').focus()
      cy.focused().type('{downarrow}{downarrow}')
      cy.focused().should('contain.text', 'englanti')

      cy.focused().type('{esc}')
      cy.get('#language-selector .dropdown-menu').should('not.have.class', 'show')
      cy.get('#language-selector button').should('be.focused')
      cy.url().should('not.include', 'clang=en')

      // re-open: the first item must be focused again, not the one that was focused before Escape
      cy.focused().type('{downarrow}')
      cy.get('#language-list li').first().should('be.focused')
    })
  })

  describe('Keyboard navigation of search results', () => {
    // Populate the autocomplete with a known set of results:
    // YSO + 'arkeolog' (Finnish) yields exactly 5 result links
    const typeSearchAndOpenResults = () => {
      cy.visit('/yso/fi/')
      cy.get('#search-field').type('arkeolog')
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible')
      cy.get('#search-autocomplete-results a', { timeout: 20000 }).should('have.length', 5)
    }

    it('Arrow down in the search field moves focus to the first search result', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')
    })

    it('Arrow down and arrow up move focus between the search results', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')

      cy.focused().type('{downarrow}')
      cy.get('#search-autocomplete-results a').eq(1).should('be.focused')

      cy.focused().type('{downarrow}')
      cy.get('#search-autocomplete-results a').eq(2).should('be.focused')

      cy.focused().type('{uparrow}')
      cy.get('#search-autocomplete-results a').eq(1).should('be.focused')
    })

    it('Arrow down on the last search result wraps focus back to the first result', () => {
      typeSearchAndOpenResults()

      cy.get('#search-autocomplete-results a').last().focus()
      cy.get('#search-autocomplete-results a').last().should('be.focused')

      cy.focused().type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')
    })

    it('Arrow up on the first search result returns focus to the search field', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')

      cy.focused().type('{uparrow}')
      cy.get('#search-field').should('be.focused')
    })

    it('Home key moves focus to the first search result', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}{downarrow}{downarrow}')
      cy.get('#search-autocomplete-results a').eq(2).should('be.focused')

      cy.focused().type('{home}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')
    })

    it('End key moves focus to the last search result', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')

      cy.focused().type('{end}')
      cy.get('#search-autocomplete-results a').last().should('be.focused')
    })

    it('Arrow down scrolls the list so the last result is fully visible', () => {
      // small viewport so that 'mu' in YSO produces a long, scrollable list
      cy.viewport(1200, 600)
      cy.visit('/yso/fi/')
      cy.get('#search-field').type('mu')
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible')
      cy.get('#search-autocomplete-results a', { timeout: 20000 }).should('have.length.greaterThan', 3)

      cy.get('#search-field').type('{downarrow}')
      // step down to the last result with arrow keys
      cy.get('#search-autocomplete-results a').then(($links) => {
        cy.focused().type(`{downarrow}`.repeat($links.length - 1))
      })
      cy.get('#search-autocomplete-results a').last().should('be.focused')

      // the focused last result must not be clipped by the scroll container
      cy.get('#search-autocomplete-results').then(($list) => {
        const listRect = $list[0].getBoundingClientRect()
        cy.get('#search-autocomplete-results a').last().then(($link) => {
          const linkRect = $link[0].getBoundingClientRect()
          expect(linkRect.bottom, 'last result bottom').to.be.at.most(listRect.bottom)
          expect(linkRect.top, 'last result top').to.be.at.least(listRect.top)
        })
      })
    })

    it('Escape hides the search results and returns focus to the search field', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')

      cy.focused().type('{esc}')
      cy.get('#search-autocomplete-results').should('not.be.visible')
      cy.get('#search-field').should('be.focused')
    })

    it('Arrow up in the search field closes the autocomplete list', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{uparrow}')
      cy.get('#search-autocomplete-results').should('not.be.visible')
      cy.get('#search-field').should('be.focused')
    })

    it('Escape in the search field closes the autocomplete list', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{esc}')
      cy.get('#search-autocomplete-results').should('not.be.visible')
      cy.get('#search-field').should('be.focused')
    })

    it('Tab and Shift-Tab in the search field close the autocomplete list', () => {
      typeSearchAndOpenResults()

      // simulate a native Tab keydown on the focused input (no preventDefault in the handler,
      // so focus would also move to the next element in a real browser)
      cy.get('#search-field').trigger('keydown', { key: 'Tab', which: 9, shiftKey: false })
      cy.get('#search-autocomplete-results').should('not.be.visible')

      cy.get('#search-field').focus()
      cy.get('#search-autocomplete-results').should('be.visible')

      cy.get('#search-field').trigger('keydown', { key: 'Tab', which: 9, shiftKey: true })
      cy.get('#search-autocomplete-results').should('not.be.visible')
    })

    it('Arrow down re-opens the autocomplete list after it was closed', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{esc}')
      cy.get('#search-autocomplete-results').should('not.be.visible')

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results').should('be.visible')
      cy.get('#search-autocomplete-results a').first().should('be.focused')
    })

    it('Focusing the search field again re-opens the autocomplete list', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{uparrow}')
      cy.get('#search-autocomplete-results').should('not.be.visible')

      cy.get('#clear-button').focus()
      cy.get('#search-field').focus()
      cy.get('#search-autocomplete-results').should('be.visible')
    })

    it('Shift-Tab in the search results closes the list and moves focus to the language selector', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')

      cy.focused().trigger('keydown', { key: 'Tab', which: 9, shiftKey: true })
      cy.get('#search-autocomplete-results').should('not.be.visible')
      cy.get('#language-selector .dropdown-toggle').should('be.focused')
    })

    it('Tab in the search results closes the list and moves focus past the search field', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused')

      cy.focused().trigger('keydown', { key: 'Tab', which: 9, shiftKey: false })
      cy.get('#search-autocomplete-results').should('not.be.visible')
      cy.get('#clear-button').should('be.focused')
    })

    it('Enter on a focused search result navigates to the concept page', () => {
      typeSearchAndOpenResults()

      cy.get('#search-field').type('{downarrow}')
      cy.get('#search-autocomplete-results a').first().should('be.focused').and('have.attr', 'href').should('include', 'p1265')

      cy.focused().type('{enter}')
      cy.url().should('include', 'yso/fi/page/p1265')
    })

    it('Key presses on a results list without links (no results) are ignored', () => {
      cy.visit('/yso/en/')
      cy.get('#search-field').type('kissa')
      cy.get('#search-autocomplete-results', { timeout: 20000 }).should('be.visible')
      cy.get('#search-autocomplete-results').within(() => {
        cy.get('li').eq(0).should('contain', 'No results')
        cy.get('a').should('not.exist')
      })

      // the handler returns early when there are no <a> items; it must not throw
      cy.get('#search-autocomplete-results').trigger('keydown', { key: 'ArrowDown' })
      cy.get('#search-autocomplete-results').trigger('keydown', { key: 'Home' })
      cy.get('#search-autocomplete-results').trigger('keydown', { key: 'End' })
      cy.get('#search-autocomplete-results').should('be.visible')

      cy.get('#search-autocomplete-results').trigger('keydown', { key: 'Escape' })
      cy.get('#search-autocomplete-results').should('be.visible')
    })
  })
})
