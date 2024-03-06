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
describe('Accessibility tests for dropdown menu', () => {
  before(() => {
    cy.visit('/fi/feedback')
  });

  it('should have accessible dropdown menu', () => {

    // Listataan lapsielementit
    cy.get('#vocab-select').children().each(($el) => {
      cy.log($el[0].tagName);
      cy.log($el.text());
    });
    
    // Haetaan testin vuoksi "oletus" ilman aiempia kutsuja
    cy.get('#vocab-select').invoke('val').then((value) => {
      cy.get('#vocab-select').find(`option[value="${value}"]`).invoke('text').then((text) => {
        cy.log('Oletus', text);
      });
    });

    cy.get('#vocab-select').invoke('val').then((value) => {
      cy.log('Selected:', value);
    });
    
    // Jos tätä ei kutsuta, tulee alempana olevaan (X) "Ei tiettyyn sanastoon" eli ensimmäinen option, muuten tulee tässä selectoidun tiedot
    // Mielenkiintoisen implisiittistä toimintaa. Parempi olisi jos tekisi vain niin kuin pyydetään.
    // Lopulta kuitenkaan nämä eivät vaikuttane siihen, miksi drop-down ei aukea, mutta auttamat varmistamaan, että
    // elementtiin päästään kiinni
    // cy.get('#vocab-select').select('yso');
    cy.get('#vocab-select').select('test');
    
    // X Oletustietoja lisää, riippuen yllä tehdyistä toimista
    cy.get('#vocab-select').invoke('val').then((value) => {
      cy.get('#vocab-select').find(`option[value="${value}"]`).invoke('text').then((text) => {
        cy.log('Oletustietoja implisiittisin argumetein:', text);
      });
    });

    cy.screenshot('dropdown-opened');

    cy.injectAxe();
    accessibilityTestRunner();
  });
});


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
