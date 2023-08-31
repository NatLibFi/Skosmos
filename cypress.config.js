const { defineConfig } = require("cypress")

module.exports = defineConfig({
  projectRoot: "tests",
  e2e: {
    // You also can run like this: npx cypress run --config "baseUrl=http://localhost/Skosmos"
    baseUrl: 'http://localhost/Skosmos',
    setupNodeEvents(on, config) {
      on('task', {
        log(message) {
          console.log(message)
          return null
        },
        table(message) {
          console.table(message)
          return null
        }
      })
    },
    supportFile: false,
    specPattern: [
      'tests/cypress/accessibility/**/*.cy.js',
      'tests/cypress/template/**/*.cy.js',
      'tests/cypress/e2e/**/*.cy.js'
    ]
  }
})
