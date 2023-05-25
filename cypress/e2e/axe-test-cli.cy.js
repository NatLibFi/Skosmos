import { terminalLog } from '../support/accessibility.js'

describe('Accessibility', () => {
    before(() => {
        cy.visit('/')
        cy.injectAxe()
    })

    it('Logs violations to the terminal', () => {
        cy.checkA11y(null, null, terminalLog)
    })

})

