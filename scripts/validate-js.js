const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const root = path.resolve(__dirname, '..');
const skip = new Set(['node_modules', 'vendor', 'storage', 'test-results']);
const files = [];

function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (skip.has(entry.name)) continue;
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(full);
    else if (entry.isFile() && full.endsWith('.js')) files.push(full);
  }
}

walk(root);
let failures = 0;
for (const file of files) {
  try {
    execFileSync(process.execPath, ['--check', file], { stdio: 'pipe' });
  } catch (error) {
    failures += 1;
    process.stderr.write(`JS syntax error: ${path.relative(root, file)}\n`);
    process.stderr.write(String(error.stderr || error.message) + '\n');
  }
}

if (failures) process.exit(1);
console.log(`JS syntax validation: ${files.length} files OK`);
