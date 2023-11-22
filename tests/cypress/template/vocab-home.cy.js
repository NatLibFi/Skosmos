describe('Vocabulary home page', () => {
  it('contains vocabulary title', () => {
    cy.visit('/yso/en') // go to the "Test ontology" home page

    // check that the vocabulary title is not empty
    cy.get('#vocab-title > a').invoke('text').should('equal', 'YSO - General Finnish ontology (archaeology)')
  })
  it('shows alphabetical index letters', () => {
    cy.visit('/yso/en') // go to the "Test ontology" home page

    const letters = cy.get('#tab-alphabetical .pagination').children()

    // check that we have the correct number of letters
    letters.should('have.length', 23)

    // check that the first letter is B
    letters.first().invoke('text').should('equal', 'A')
  })
  it('shows alphabetical index entries', () => {
    cy.visit('/yso/en') // go to the "Test ontology" home page

    const entries = cy.get('#alpha-list').children()

    // check that we have the correct number of entries
    entries.should('have.length', 33)

    // check that the first entry is Bass
    entries.first().invoke('text').should('equal', 'abstract objects')
  })
  it('alphabetical index letters are clickable', () => {
    cy.visit('/yso/en') // go to the "Test ontology" home page

    // click on the second letter (C)
    cy.get('#tab-alphabetical .pagination :nth-child(4) > .page-link').click()

    // check that we have the correct number of entries
    cy.wait(2000);
    cy.get('#alpha-list').children().should('have.length', 8)

    // check that the first entry is Carp
    cy.get('#alpha-list').children().first().invoke('text').should('equal', 'dating (age estimation)')
  })
})

describe('Check Resource Counts', () => {
  it('should include "Resource counts by type" heading', () => {
    cy.visit('/yso/en/');
    cy.get('#resource-counts h3').should('contain.text', 'Resource counts by type');
  });

  it('should include correct type and count data', () => {
    cy.visit('/yso/en/');
    const expectedData = [
      { type: 'Concept', count: '270' },
      { type: '* Käytöstä poistettu käsite', count: '0' },
      { type: 'Collection', count: '1' },
    ];

    expectedData.forEach(({ type, count }) => {
      cy.get('#resource-stats')
        .contains('tr', type)
        .within(() => {
          cy.get('td').eq(1).should('have.text', count);
        });
    });
  });

  it('should include header texts', () => {
    cy.visit('/yso/en/');
    const headerTexts = [
      'Type',
      'Count',
      'Concept',
      '* Käytöstä poistettu käsite',
      'Collection',
    ];

    headerTexts.forEach((text) => {
      cy.get('#resource-stats').should('contain.text', text);
    });
  });
});

describe('Check Term Counts on /yso/en/ page', () => {
  it('should include: Term counts by language heading', () => {
    cy.visit('/yso/en/');
    cy.get('#term-counts h3').should('contain.text', 'Term counts by language');
  });

  it('should include correct language and term counts', () => {
    cy.visit('/yso/en/');
    const expectedData = [
      { language: 'English', prefLabel: '267', altLabel: '46', hiddenLabel: '178' },
      { language: 'Finnish', prefLabel: '270', altLabel: '113', hiddenLabel: '209' },
      { language: 'Northern Sami', prefLabel: '171', altLabel: '13', hiddenLabel: '83' },
      { language: 'Swedish', prefLabel: '270', altLabel: '191', hiddenLabel: '191' },
    ];

    expectedData.forEach(({ language, prefLabel, altLabel, hiddenLabel }) => {
      cy.get('#term-stats')
        .contains('tr', language)
        .within(() => {
          cy.get('td').eq(1).should('have.text', prefLabel);
          cy.get('td').eq(2).should('have.text', altLabel);
          cy.get('td').eq(3).should('have.text', hiddenLabel);
        });
    });
  });

  it('should include term headers', () => {
    cy.visit('/yso/en/');
    const columnHeaders = ['Concept language', 'Preferred terms', 'Alternate terms', 'Hidden terms'];

    columnHeaders.forEach((header) => {
      cy.get('#term-stats th').should('contain.text', header);
    });
  });

  it('should include language names', () => {
    cy.visit('/yso/en/');
    const languages = ['English', 'Finnish', 'Northern Sami', 'Swedish'];

    languages.forEach((language) => {
      cy.get('#term-stats td').should('contain.text', language);
    });
  });
});

