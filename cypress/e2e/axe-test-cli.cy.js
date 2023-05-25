import { terminalLog } from '../support/accessibility.js'
import { getConfigurationForCLITests } from '../support/accessibility.js'

describe('This is used to avoid unnecessary redundancy of code', () => {
    before(() => {
        cy.visit('/')
        cy.injectAxe()
    })

    // Write your tests here

    // End

    // Configuration for the accessibility test
    getConfigurationForCLITests()
/*    it('Logs (CLI)', () => {
        cy.checkA11y(null, null, terminalLog)
    })*/

})

