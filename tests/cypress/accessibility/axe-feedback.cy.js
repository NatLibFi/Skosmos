import { accessibilityTestRunner } from '../support/accessibility.js'
import 'cypress-axe';

/* If you want the test to be skipped, add a skip command after the describe part:
    - test enabled: describe('Check accessibility of ...
    - test to be skipped: describe.skip('Check accessibility of ... */

function fillInFormFields() {
  const name = 'John Smith';
  cy.get('#name-input').type(name);
  cy.get('#name-input').invoke('val').then((nameFieldValue) => {
    cy.log('The name is:', nameFieldValue);
  });

  const eMail = 'john.smith@testing.dom';
  cy.get('#email-input').type(eMail);
  cy.get('#email-input').invoke('val').then((eMailFieldValue) => {
    cy.log('The e-mail is:', eMailFieldValue);
  });

  const subject = 'Test Subject';
  cy.get('#subject-input').type(subject);
  cy.get('#subject-input').invoke('val').then((subjectValue) => {
    cy.log('The subject is:', subjectValue);
  });

  const message = 'Test Subject';
  cy.get('#message-input').type(message);
  cy.get('#message-input').invoke('val').then((messageValue) => {
    cy.log('The subject is:', messageValue);
  });
}

describe('!!! Kesken: Drop-down-menun hover-testi ', () => {
  before(() => {
    cy.visit('/fi/feedback')
    cy.wait(3000)
    
    cy.get('#vocab-select').select('');

    cy.wait(3000)

    // Avataan lista ja hoveroidaan YSOn kohdalle (pitäisi näkyä vahvennettuna)
    cy.get('#vocab-select').siblings('.select-wrapper').find('option').contains('YSO - Yleinen suomalainen ontologia (arkeologia)').then($option => {
      $option.trigger('mouseover');
      cy.injectAxe();
      cy.checkA11y($option, {
        includedImpacts: ['critical', 'serious'],
        rules: {
          // Mieti tarvitaanko jotain sääntöjä lisää
        }
      });
    });

  })
  // accessibilityTestRunner() // Lähtökohtaisesti tämä pitäisi olla se, mitä käytetään eikö tuo checkA11y, mutta tutkitaan tätä
})

describe('Testataan itse formia ennen submittausta', () => {
  before(() => {
    cy.visit('/fi/feedback')
    fillInFormFields()
    cy.injectAxe()
  })
  accessibilityTestRunner()
})

describe('Testataan sivua, joka tulee formin syötön jälkeen', () => {
  before(() => {
    cy.visit('/fi/feedback')
    fillInFormFields()
    cy.get('#submit-feedback').click()
    cy.injectAxe()
  })
  accessibilityTestRunner()
})
