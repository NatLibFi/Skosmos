describe('Feedback page', () => {
  it('Contains title metadata', () => {
    // go to the general feedback page
    cy.visit('/en/feedback')

    const expectedTitle = 'Feedback - Skosmos being tested'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains description metadata', () => {
    // go to the general feedback page
    cy.visit('/en/feedback')

    const expectedDescription = 'Feedback page for Skosmos being tested'
    // check that the page has description metadata
    cy.get('head meta[name="description"]').should('have.attr', 'content', expectedDescription);
    cy.get('head meta[property="og:description"]').should('have.attr', 'content', expectedDescription);
  })
  it('Contains site name metadata', () => {
    // go to the general feedback page
    cy.visit('/en/feedback')

    const expectedSiteName = 'Skosmos being tested'
    // check that the page has site name metadata
    cy.get('head meta[property="og:site_name"]').should('have.attr', 'content', expectedSiteName);
  })
  it('Contains canonical URL metadata', () => {
    // go to the general feedback page
    cy.visit('/en/feedback')

    const expectedUrl = Cypress.config('baseUrl') + 'en/feedback'
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl);
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl);
  })
  it('Sends feedback', () => {
    // go to the general feedback page
    cy.visit('/en/feedback')

    // type subject and message
    cy.get('#subject-input').type('test subject')
    cy.get('#message-input').type('test message')

    // submit form
    cy.get('#submit-feedback').click()

    // check that thank you message is displayed
    cy.get('#feedback-thanks')
  })

  it('Requires subject and message', () => {
    // go to the general feedback page
    cy.visit('/en/feedback')

    // submit empty form
    cy.get('#submit-feedback').click()

    // check that thank you message is not displayed
    cy.get('#feedback-thanks').should('not.exist')
  })

  it('Displays correct vocab option', () => {
    // go to the general feedback page
    cy.visit('/en/feedback')

    // check that selected vocab option is no vocab
    cy.get('#vocab-select > option[selected]').invoke('attr', 'value').should('eq', '')
  })
})

describe('Vocab feedback page', () => {
  it('Contains title metadata', () => {
    // go to test vocab feedback page
    cy.visit('/test/en/feedback')

    const expectedTitle = 'Feedback - Skosmos being tested'
    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains description metadata', () => {
    // go to test vocab feedback page
    cy.visit('/test/en/feedback')

    const expectedDescription = 'Feedback page for Skosmos being tested'
    // check that the page has description metadata
    cy.get('head meta[name="description"]').should('have.attr', 'content', expectedDescription);
    cy.get('head meta[property="og:description"]').should('have.attr', 'content', expectedDescription);
  })
  it('Contains site name metadata', () => {
    // go to test vocab feedback page
    cy.visit('/test/en/feedback')

    const expectedSiteName = 'Skosmos being tested'
    // check that the page has site name metadata
    cy.get('head meta[property="og:site_name"]').should('have.attr', 'content', expectedSiteName);
  })
  it('Contains canonical URL metadata', () => {
    // go to test vocab feedback page
    cy.visit('/test/en/feedback')

    const expectedUrl = Cypress.config('baseUrl') + 'test/en/feedback'
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl);
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl);
  })
  it('Displays correct vocab option', () => {
    // go to test vocab feedback page
    cy.visit('/test/en/feedback')

    // check that selected vocab option is test vocab
    cy.get('#vocab-select > option[selected]').invoke('attr', 'value').should('eq', 'test')
  })
})
