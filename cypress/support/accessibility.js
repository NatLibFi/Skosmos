export const checkA11y = options => {
    cy.checkA11y(null, options, violations => {
        console.log(`${violations.length} violation(s) detected`)
        console.table(violations)
    })
}
export function terminalLog(violations) {
    // const runForCLI = Cypress.env('cli') // true
    cy.task(
        'log',
         `${violations.length} accessibility violation${
            violations.length === 1 ? '' : 's'
        } ${violations.length === 1 ? 'was' : 'were'} detected`
    )
    // Styling the table
    const violationData = violations.map(
        ({ id, impact, description, nodes }) => ({
            id,
            impact,
            description,
            nodes: nodes.length
        })
    )
    cy.task('table', violationData)
}

export function testRunner () {
    const runForCLI = Cypress.env('cli') // true
    if (runForCLI) {
        getConfigurationForCLITests()
    } else {
        getConfigurationForGUITests()
    }
}

function getConfigurationForCLITests () {
    return it('Logs (CLI)', () => {
        cy.checkA11y(null, null, terminalLog)
    })
}

function getConfigurationForGUITests () {
    return it('Goes through all needed levels', () => {
        checkA11y(null, null, {
            includedImpacts: ['minor', 'moderate', 'serious', 'critical' ],
            runOnly: {
                type: 'tag',
                values: ['wcag2aa'],
            }
        })
    })
}



