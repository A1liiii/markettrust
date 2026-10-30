import { defineConfig } from "cypress";
import { execSync } from "child_process";
import fs from "fs";

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
            const tempFile = 'storage/framework/cypress_task.php';
            const phpCode = `<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(\\Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();

${code}
`;
            fs.writeFileSync(tempFile, phpCode);
            const output = execSync('php storage/framework/cypress_task.php').toString();
            return output;
          } catch (err) {
            return err.message;
          }
        }
      });
    },
  },
});
