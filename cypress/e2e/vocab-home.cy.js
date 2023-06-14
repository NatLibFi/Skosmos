describe('Vocabulary home page', () => {
  // using context because otherwise cypress would not clear the state and tests couldn't be run independently
  context('Vocabulary home page', () => {
    it('contains vocabulary title', () => {
      // go to the Skosmos front page
      cy.visit('/')
      // click on the first vocabulary in the list
      cy.get('#vocabulary-list').find('a').first().click()

      // check that the vocabulary title is not empty
      cy.get('#vocab-title > a').invoke('text').should('match', /.+/)
    })
  })

  context('Resource counts', () => {
    it('The amounts must match', () => {
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

  context('Term counts', () => {
    it('Term counts by language', () => {
      const headings = ['Concept language',	'Preferred terms',	'Alternate terms',	'Hidden terms']
      cy.visit('/yso/fi')
      cy.get('table tbody tr').find('th').then('ths') => {
        $(ths)
      } map('innerText').should('deep.equal', headings)

    })
  })


  // context('Term counts', () => {
  //   it('The amounts must match', () => {
  //     cy.visit('/yso/fi')
  //     cy.get('#term-counts')
  //         .contains('tr', 'englanti').find('td')
  //         .then((row) => {
  //           for (let i = 0; i < row.length; i++) {
  //             cy.task('log', `tr:eq(${i}) td:eq(1)`)
  //             cy.get('#resource-counts')
  //                 .find(`tr:eq(${i}) td:eq(1)`)
  //                 .should('not.be.empty')
  //                 .wrap(0)
  //                 .should('not.be.ok')
  //           }
  //         })
  //   })
  // })


})
