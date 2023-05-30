const { defineConfig } = require("cypress")

module.exports = defineConfig({
  e2e: {
    // You also can run like this: npx cypress run --config "baseUrl=http://localhost/skosmos"
    baseUrl: 'http://localhost/skosmos/fi',
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
  },
})
