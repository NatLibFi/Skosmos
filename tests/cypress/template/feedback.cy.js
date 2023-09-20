describe('Feedback page', () => {
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
  it('Displays correct vocab option', () => {
    // go to test vocab feedback page
    cy.visit('/test/en/feedback')

    // check that selected vocab option is test vocab
    cy.get('#vocab-select > option[selected]').invoke('attr', 'value').should('eq', 'test')
  })
})
