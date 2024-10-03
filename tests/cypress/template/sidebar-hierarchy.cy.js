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
