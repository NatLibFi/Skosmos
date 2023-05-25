import { getConfigurationForGUITests } from '../support/accessibility.js'

describe('This is used to avoid unnecessary redundancy of code', () => {
    before(() => {
        cy.visit('/')
        cy.injectAxe()
    })

    // Write your tests here

    // End

    // Configuration for the accessibility test
    getConfigurationForGUITests()
/*    it('Goes through all needed levels', () => {
        checkA11y(null, null, {
            includedImpacts: ['minor', 'moderate', 'serious', 'critical' ],
            runOnly: {
                type: 'tag',
                values: ['wcag2aa'],
            }
        })
    })*/
})

