export const checkA11y = options => {
    cy.checkA11y(null, options, violations => {
        console.log(`${violations.length} violation(s) detected`);
        console.table(violations);
    });
};
