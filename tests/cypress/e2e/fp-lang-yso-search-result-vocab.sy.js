describe('Front page - lang - list - yso - search - result - vocab', () => {
    it('should navigate through Finto application', () => {
      cy.visit('/en/');
  
      cy.contains('Change language').click();
      cy.contains('Suomeksi').click();
  
      // Select "YSO - Yleinen suomalainen ontologia" from the vocabulary list
      cy.get('#tähän_sopiva_id').select('YSO - Yleinen suomalainen ontologia');
  
      cy.visit('/yso/fi/search?query=kissaeläimet');
  
      // Select the concept 'kissaeläimet' from the list and go to the concept page
      cy.contains('joku sopiva käsite').click();
  
      // After transitioning from the search results page to the concept page, you should enter
      // on the following page:
      cy.url().should('eq', 'https://test.dev.finto.fi/yso/fi/page/p864');
    });
  });
  