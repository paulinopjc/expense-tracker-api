/**
 * generate-docs.cjs
 *
 * Converts postman_collection.json → openapi.yaml using postman-to-openapi.
 * {{base_url}} is replaced before conversion because the library calls
 * `new URL()` on raw URL strings and throws on unresolved Postman variables.
 *
 * Usage:
 *   npm run docs
 *
 * Output:
 *   openapi.yaml  (project root)
 */

'use strict';

// @ts-ignore — no type declarations available for this package
const postmanToOpenApi = require('postman-to-openapi');
const path = require('path');
const fs = require('fs');
const os = require('os');

const BASE_URL = 'http://localhost:8090';

async function main() {
  const input  = path.join(__dirname, 'postman_collection.json');
  const output = path.join(__dirname, 'openapi.yaml');

  // Replace {{base_url}} before the library parses URLs — it calls new URL()
  // internally and throws "Invalid URL" on unresolved Postman variables.
  const raw = fs.readFileSync(input, 'utf8').replace(/\{\{base_url\}\}/g, BASE_URL);
  const tmp = path.join(os.tmpdir(), 'postman_collection_resolved.json');
  fs.writeFileSync(tmp, raw);

  const result = await postmanToOpenApi(tmp, output, {
    defaultTag: 'General',
    servers: [{ url: BASE_URL }],
  });

  console.log(`OpenAPI spec generated → openapi.yaml (${result.length} bytes)`);
}

main().catch((err) => {
  console.error('Error generating docs:', err.message);
  process.exit(1);
});
