describe('Vocabulary home page', () => {
  // Always using context because otherwise Cypress would not clear the state and tests could not be run independently

  // Each type must have a visible count
  context('Resource counts:', () => {
    it('Expects a corresponding count for each type', () => {
      cy.visit('/yso/fi')
      cy.get('#resource-counts')
        .find('tr')
        .then((row) => {
          for (let i = 0; i < row.length; i++) {
            cy.task('log', `tr:eq(${i}) td:eq(1)`)
            cy.get('#resource-counts')
              .find(`tr:eq(${i}) td:eq(1)`)
              .should('not.be.empty')
              .wrap(0)
              .should('not.be.ok')
          }
        })
    })
  })

  context('Term counts by language', () => {
    beforeEach(() => {
      cy.visit('/yso/fi')
    })

    it('should display non-empty numerical values or "n/a" in the table', () => {
      const headings = ['Concept language', 'Preferred terms', 'Alternate terms', 'Hidden terms']
      cy.get('table#term-stats th').should('have.length', headings.length).each((th, index) => {
        cy.wrap(th).should('contain.text', headings[index])
      })

      /* Numerical values
      Iterates through each table row (excluding the first row) and then iterates through each table cell
      (excluding the first cell) within that row. It uses a regular expression to ensure that the cell's content
      contains only zero or positive integers. */
      cy.get('#term-counts tr').not(':first').each((tr) => {
        cy.wrap(tr).find('td').not(':first').each((td) => {
          cy.wrap(td).invoke('text').then((text) => {
            expect(text).to.match(/^0$|^[1-9][0-9]*$/) // Allow only zero or positive integers
            cy.log('Content of a td:', text)
          })
        })
      })

      /* Language names
      Uses a regular expressions to match alphabetic characters, diacritics, and Scandinavian symbols.
      It applies to the content of the first <td> element in each row (skipping the first row) */
      cy.get('#term-counts tr').not(':first').each((tr) => {
        cy.wrap(tr).find('td').first().invoke('text').then((text) => {
          expect(text).to.match(/^[a-zA-Z\u00C0-\u017F]+$/) // Unicode ranges for alphabetic characters and diacritics
          cy.log('Content of a td:', text)
        })
      })
    })
  })
})
