describe('Vocabulary home page', () => {
  it('contains vocabulary title', () => {
    cy.visit('/yso/en') // Go to the "YSO - General Finnish ontology (archaeology)" home page

    // Check that the vocabulary title is not empty
    cy.get('#vocab-title > a').invoke('text').should('equal', 'YSO - General Finnish ontology (archaeology)')
  })
  it('shows alphabetical index letters', () => {
    cy.visit('/yso/en') // Go to the "YSO - General Finnish ontology (archaeology)" home page

    const letters = cy.get('#tab-alphabetical .pagination').children()

    // Check that we have the correct number of letters
    letters.should('have.length', 23)

    // Check that the first letter is A
    letters.first().invoke('text').should('equal', 'A')
  })
  it('shows alphabetical index entries', () => {
    cy.visit('/yso/en') // Go to the "YSO - General Finnish ontology (archaeology)" home page

    const entries = cy.get('#alpha-list').children()

    // Check that we have the correct number of entries
    entries.should('have.length', 33)

    // Check that the first entry is "abstract objects"
    entries.first().invoke('text').should('equal', 'abstract objects')
  })
  it('alphabetical index letters are clickable', () => {
    cy.visit('/yso/en') // Go to the "YSO - General Finnish ontology (archaeology)" home page

    // Click on the second letter (D)
    cy.get('#tab-alphabetical .pagination :nth-child(4) > .page-link').click()

    // Check that we have the correct number of entries
    cy.get('#alpha-list').children().should('have.length', 8)

    // check that the first entry is "dating (age estimation)"
    cy.get('#alpha-list').children().first().invoke('text').should('equal', 'dating (age estimation)')
  })
})

// The purpose of the test is to go through the table on the vocabulary's front page
// displaying the counts of resources by type
describe('Check Resource Counts', () => {
  it('should check all the resource types, quantities and corresponding headers', () => {
    // Go to the "YSO - General Finnish ontology (archaeology)" home page
    cy.visit('/yso/en/');
    // Checks if the header "Resource counts by type" exists
    cy.get('#resource-counts h3').should('contain.text', 'Resource counts by type');

    // Setting up conditions for the 'Resource counts by type' test using an array of objects 
    // representing concept types and their occurrences, which will be utilized later in the test
    const expectedData = [
      { type: 'Concept', count: '270' },
      { type: '* Käytöstä poistettu käsite', count: '0' },
      { type: 'Collection', count: '1' },
    ];

    // Iterating through the array containing data objects (concept types and corresponding counts)
    // to check if the values on the table are correct
    expectedData.forEach(({ type, count }) => {
      cy.get('#resource-stats')
        .contains('tr', type)
        .within(() => {
          cy.get('td').eq(1).should('have.text', count);
        });
    });

    // To make the code more concise: headers as an array that can be looped later
    const headerTexts = [
      'Type',
      'Count',
      'Concept',
      '* Käytöstä poistettu käsite',
      'Collection',
    ];

    // Checks that the texts of the headers are correct
    headerTexts.forEach((text) => {
      cy.get('#resource-stats').should('contain.text', text);
    });
  });
});

// The purpose of the test is to verify that the headers of the "label table" are correct and that 
// the corresponding counts for each label type are accurate
describe('Check names of the labels in all used languages and corresponding quantities', () => {
  it('should go through the names of labels and their corresponding counts, grouped by language', () => {
    // Go to the "YSO - General Finnish ontology (archaeology)" home page
    cy.visit('/yso/en/');

    // Verifying the correctness of the term table header
    cy.get('#term-counts h3').should('contain.text', 'Term counts by language');

    // A table containing objects for later verification of the correctness of labels in 
    // different languages and their corresponding quantities
    const expectedData = [
      { language: 'English', prefLabel: '267', altLabel: '46', hiddenLabel: '178' },
      { language: 'Finnish', prefLabel: '270', altLabel: '113', hiddenLabel: '209' },
      { language: 'Northern Sami', prefLabel: '171', altLabel: '13', hiddenLabel: '83' },
      { language: 'Swedish', prefLabel: '270', altLabel: '191', hiddenLabel: '191' },
    ];

    // Iterating through a table that contains the names of each language, corresponding labels, and their quantities
    expectedData.forEach(({ language, prefLabel, altLabel, hiddenLabel }) => {
      cy.get('#term-stats')
        .contains('tr', language)
        .within(() => {
          cy.get('td').eq(1).should('have.text', prefLabel);
          cy.get('td').eq(2).should('have.text', altLabel);
          cy.get('td').eq(3).should('have.text', hiddenLabel);
        });
    });
    
    // A table used to later iterate and check that the column headers are correct
    const columnHeaders = ['Concept language', 'Preferred terms', 'Alternate terms', 'Hidden terms'];

    // Iterating over the column headers listed above
    columnHeaders.forEach((header) => {
      cy.get('#term-stats th').should('contain.text', header);
    });

    // A table containing the names of languages used in concepts for later iteration
    const languages = ['English', 'Finnish', 'Northern Sami', 'Swedish'];

    // Looping through the array containing language names to verify the accuracy of them
    languages.forEach((language) => {
      cy.get('#term-stats td').should('contain.text', language);
    });
  });
});

