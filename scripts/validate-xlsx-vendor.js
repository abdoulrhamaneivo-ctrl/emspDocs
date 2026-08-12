#!/usr/bin/env node
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');

const file = path.resolve(__dirname, '..', 'assets', 'js', 'xlsx.full.min.js');
if (!fs.existsSync(file)) throw new Error(`Missing local SheetJS bundle: ${file}`);
const stat = fs.statSync(file);
if (stat.size < 500000) throw new Error(`SheetJS bundle is unexpectedly small: ${stat.size} bytes`);
const source = fs.readFileSync(file, 'utf8');
if (!source.includes('SheetJS')) throw new Error('SheetJS marker not found');
if (!source.includes('0.18.5')) throw new Error('Expected SheetJS 0.18.5 marker not found');
execFileSync(process.execPath, ['--check', file], { stdio: 'inherit' });
const sha256 = crypto.createHash('sha256').update(source).digest('hex');
console.log(`SheetJS local bundle: OK (${stat.size} bytes)`);
console.log(`SHA-256: ${sha256}`);
