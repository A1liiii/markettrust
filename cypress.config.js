import { defineConfig } from "cypress";
import { execSync } from "child_process";

export default defineConfig({
  e2e: {
    baseUrl: "http://127.0.0.1:8000",
    viewportWidth: 1280,
    viewportHeight: 720,
    video: false,
    screenshotOnRunFailure: true,
    specPattern: "cypress/e2e/**/*.cy.{js,jsx,ts,tsx}",
    setupNodeEvents(on, config) {
      on('task', {
        execPhp(code) {
          try {
            const escaped = code.replace(/"/g, '\\"');
            const output = execSync(`php artisan tinker --execute "${escaped}"`).toString();
            return output;
          } catch (err) {
            return err.message;
          }
        }
      });
    },
  },
});
