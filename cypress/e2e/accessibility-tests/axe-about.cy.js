// import { getConfigurationForCLITests } from '../../support/accessibility.js'
import {accessibilityTestRunner, testRunner} from '../../support/accessibility.js'

describe('This is used to avoid unnecessary redundancy of code', () => {
    before(() => {
        cy.visit('/about')
        cy.injectAxe()
    })

    // Configuration for the accessibility test
    // getConfigurationForCLITests()
    accessibilityTestRunner()
})

