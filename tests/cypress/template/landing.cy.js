describe('Landing page', () => {
  it('Contains title metadata', () => {
    // go to the Skosmos front page
    cy.visit('/')

    const expectedTitle = 'Skosmos being tested, long title'

    // check that the page has a HTML title
    cy.get('title').invoke('text').should('equal', expectedTitle)
    // check that the page has title metadata
    cy.get('head meta[name="title"]').should('have.attr', 'content', expectedTitle);
    cy.get('head meta[property="og:title"]').should('have.attr', 'content', expectedTitle);
  })
  it('Contains description metadata', () => {
    // go to the Skosmos front page
    cy.visit('/')

    const expectedDescription = 'Description of Skosmos being tested'

    // check that the page has description metadata
    cy.get('head meta[name="description"]').should('have.attr', 'content', expectedDescription);
    cy.get('head meta[property="og:description"]').should('have.attr', 'content', expectedDescription);
  })
  it('Contains site name metadata', () => {
    // go to the Skosmos front page
    cy.visit('/')

    const expectedSiteName = 'Skosmos being tested'
    // check that the page has site name metadata
    cy.get('head meta[property="og:site_name"]').should('have.attr', 'content', expectedSiteName);
  })
  it('Contains canonical URL metadata', () => {
    // go to the Skosmos front page
    cy.visit('/')

    const expectedUrl = Cypress.config('baseUrl') + 'en/'
    // check that the page has canonical URL metadata
    cy.get('link[rel="canonical"]').should('have.attr', 'href', expectedUrl);
    cy.get('head meta[property="og:url"]').should('have.attr', 'content', expectedUrl);
  })
  it('links to vocabulary home', () => {
    // go to the Skosmos front page
    cy.visit('/')
    // click on the first vocabulary in the list
    cy.get('#vocabulary-list').find('a').first().click()
    // check that we are still within the Skosmos URL space
    cy.url().should('include', Cypress.config().baseUrl)
    // check that we are on the vocab home page
    cy.get('.vocabpage')
  })
})
