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
  it('Concepts and letters in alphabetical index are displayed in the correct language', () => {
    // go to YSO vocab from page with UI language set to English and content language set to Finnish
    cy.visit('/yso/en/?clang=fi')
    // check that the first item in the list is in the correct language
    cy.get('#tab-alphabetical .sidebar-list a').first().invoke('text').should('contain', 'aarrelöydöt')
    // check that letters contain Y and not C
    cy.get('#tab-alphabetical .pagination a').invoke('text').should('contain', 'Y').should('not.contain', 'C')
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
