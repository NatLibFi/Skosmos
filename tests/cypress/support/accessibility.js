const a11yOptions = {
  runOnly: {
    type: 'tag',
    values: ['wcag21aa']
  }
}
const checkA11y = options => {
  cy.checkA11y(null, options, violations => {
    console.log(`${violations.length} violation(s) detected`)
    console.table(violations)
  })
}
function terminalLog (violations) {
  cy.task(
    'log',
         `${violations.length} accessibility violation${
            violations.length === 1 ? '' : 's'
        } ${violations.length === 1 ? 'was' : 'were'} detected`
  )
  // Table
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
export function accessibilityTestRunner () {
  const runForCLI = Cypress.env('cli') // true
  if (runForCLI) {
    getConfigurationForCLITests()
  } else {
    getConfigurationForGUITests()
  }
}
function getConfigurationForCLITests () {
  return it('Logs (CLI)', () => {
    cy.checkA11y(null, a11yOptions, terminalLog)
  })
}
function getConfigurationForGUITests () {
  return it('Check for possible accessibility errors at all logging levels set below in accordance with WCAG AA requirements', () => {
    checkA11y(a11yOptions)
  })
}
