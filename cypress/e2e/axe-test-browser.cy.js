import { checkA11y } from '../support/accessibility.js'

describe('Accessibility', () => {
    before(() => {
        cy.visit('/')
        cy.injectAxe();
    });

    it('Should have no a11y violations', () => {
        checkA11y(null, null, {
            includedImpacts: ['minor', 'moderate', 'serious', 'critical' ],
            runOnly: {
                type: 'tag',
                values: ['wcag2aa'],
            }
        });
    });
});

