import {accessibilityTestRunner, testRunner} from '../../support/accessibility.js'

describe('This is used to avoid unnecessary redundancy of code', () => {
    before(() => {
        cy.visit('/juho/fi')
        cy.injectAxe()
    })

    accessibilityTestRunner()
})

