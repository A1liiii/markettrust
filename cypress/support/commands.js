// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
// Custom commands for MarketTrust Cypress testing

Cypress.Commands.add('loginByForm', (login, password) => {
  cy.visit('/login');
  cy.get('[data-cy="input-login"]').clear().type(login);
  cy.get('[data-cy="input-password"]').clear().type(password);
  cy.get('[data-cy="btn-login"]').click();
});

Cypress.Commands.add('registerByForm', (username, email, password, passwordConfirmation) => {
  cy.visit('/register');
  if (username !== undefined && username !== null && username !== '') {
    cy.get('[data-cy="input-username"]').clear().type(username);
  }
  if (email !== undefined && email !== null && email !== '') {
    cy.get('[data-cy="input-email"]').clear().type(email);
  }
  if (password !== undefined && password !== null && password !== '') {
    cy.get('[data-cy="input-password"]').clear().type(password);
  }
  if (passwordConfirmation !== undefined && passwordConfirmation !== null && passwordConfirmation !== '') {
    cy.get('[data-cy="input-password-confirmation"]').clear().type(passwordConfirmation);
  }
  cy.get('[data-cy="btn-register"]').click();
});