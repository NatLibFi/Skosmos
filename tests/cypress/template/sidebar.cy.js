describe('Alphabetical index', () => {
  it('Loads letters and concepts on page load', () => {
    // Go to YSO vocab home page
    cy.visit('/yso/en/')
    // Check that letter pagination exists and has the right number of items
    cy.get('#tab-alphabetical').find('.pagination li').should('have.length', 23)
    // Check that the first letter is correct
    cy.get('#tab-alphabetical').find('.pagination li').first().invoke('text').should('contain', 'A')
    // Check that alphabetical list exists and has the right concepts
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', 'abstract objects')
  })
  it('Loads letters and concepts on tab open', () => {
    // Go to "properties" YSO concept page
    cy.visit('/yso/en/page/p2742')
    // Click on alphabetical index tab
    cy.get('#alphabetical').click()
    // Check that letter pagination exists and has the right number of items
    cy.get('#tab-alphabetical').find('.pagination li').should('have.length', 23)
    // Check that the first letter is correct
    cy.get('#tab-alphabetical').find('.pagination li').first().invoke('text').should('contain', 'A')
    // Check that alphabetical list exists and has the right concepts
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', 'abstract objects')
  })
  it('Loads concepts on letter change', () => {
    // Go to YSO vocab home page
    cy.visit('/yso/en/')
    // Click on second pagination item
    cy.get('#tab-alphabetical').find('.pagination li').eq(1).click()
    // Check that alphabetical list has the right concepts
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', 'birch bark manuscripts')
  })
  it('Concepts and letters in alphabetical index are displayed in the correct language', () => {
    // Go to YSO vocab page with UI language set to English and content language set to Finnish
    cy.visit('/yso/en/?clang=fi')
    // Check that letters contain Y and not C
    cy.get('#tab-alphabetical').find('.pagination li').invoke('text').should('contain', 'Y').should('not.contain', 'C')
    // Check that the first item in the list is in the correct language
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', 'aarrelöydöt')
  })
  it('Shows notation codes', () => {
    // Go to vocab home page in a vocab with notation codes in alphabetical index
    cy.visit('/test-notation-sort/en/')
    // Check that notation codes are displayed
    cy.get('#tab-alphabetical').find('.sidebar-list li').first().invoke('text').should('contain', '(33.02)')
  })
  it('Loads concepts on scroll', () => {
    // Go to YSO vocab home page
    cy.visit('/modified-vocab/fi/')
    // Scroll to the bottom of sidebar list
    cy.get('#tab-alphabetical').find('.sidebar-list').scrollTo('bottom')
    // Check that new concepts have been loaded
    cy.get('#tab-alphabetical').find('.sidebar-list li').should('have.length', 500, {'timeout': 20000})
    // Scroll to the bottom of sidebar list again
    cy.get('#tab-alphabetical').find('.sidebar-list').scrollTo('bottom')
    // Check that new concepts have been loaded
    cy.get('#tab-alphabetical').find('.sidebar-list li').should('have.length.gt', 500, {'timeout': 20000})
  })
})

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

describe('Hierarchy', () => {
  it('Sorts concepts based on labels', () => {
    // Go to "properties" concept page in a vocab with sorting based on labels
    cy.visit('/yso/en/page/p2742')
    // First and second concepts in hierarchy should be sorted by label
    cy.get('#hierarchy-list .list-group-item').eq(0).find('.concept-label').invoke('text').should('contain', 'events and action')
    cy.get('#hierarchy-list .list-group-item').eq(1).find('.concept-label').invoke('text').should('contain', 'objects')
  })
  it('Sorts concepts based on notation codes in lexical order', () => {
    // Go to "Tuna" concept page in a vocab with lexical sorting
    cy.visit('/test-notation-sort/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta0111')
    // First and second concepts in hierarchy should be sorted by notation codes in lexical order
    cy.get('#hierarchy-list .list-group-item').eq(1).find('.concept-notation').invoke('text').should('equal', '33.01')
    cy.get('#hierarchy-list .list-group-item').eq(2).find('.concept-notation').invoke('text').should('equal', '33.02')
  })
  it('Sorts concepts based on notation codes in natural order', () => {
    // Go to "Tuna" concept page in a vocab with natural sorting
    cy.visit('/testNotation/en/page/?uri=http%3A%2F%2Fwww.skosmos.skos%2Ftest%2Fta0111')
    // First and second concepts in hierarchy should be sorted by notation codes in natural order
    cy.get('#hierarchy-list .list-group-item').eq(1).find('.concept-notation').invoke('text').should('equal', '33.1')
    cy.get('#hierarchy-list .list-group-item').eq(2).find('.concept-notation').invoke('text').should('equal', '33.01')
  })
})
