process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';

const sass = require('sass');
const chokidar = require('chokidar');
const fs = require('fs');
const path = require('path');
const notifier = require('node-notifier');

// ── Config (from package.json → sassdev) ─────────────────
const pkg = JSON.parse(fs.readFileSync(path.resolve('package.json'), 'utf8'));
const config = pkg.sassdev || {};
const PROXY_TARGET = config.proxy || 'http://localhost';
const BS_PORT = config.port || 4000;
const IS_BUILD = process.argv.includes('--build');

const SCSS_DIR = 'scss';
const CSS_DIR = '.';
const ERROR_CSS = 'sass-error.css';
const STYLE_CSS = 'style.css';

// Preserve WordPress theme header from style.css
let themeHeader = '';
if (fs.existsSync(STYLE_CSS)) {
  const match = fs.readFileSync(STYLE_CSS, 'utf8').match(/^\/\*[\s\S]*?\*\//);
  if (match) themeHeader = match[0] + '\n\n';
}

// Auto-detect entries: every .scss in scss/ that doesn't start with _
const entries = fs.readdirSync(SCSS_DIR)
  .filter(f => f.endsWith('.scss') && !f.startsWith('_'))
  .map(f => ({
    input:  path.join(SCSS_DIR, f),
    output: path.join(CSS_DIR, f.replace('.scss', '.css')),
  }));

if (!entries.length) {
  console.error('  No se encontraron archivos .scss en ' + SCSS_DIR + '/');
  process.exit(1);
}

// ── Helpers ──────────────────────────────────────────────
function timestamp() {
  return new Date().toLocaleTimeString('en-GB', { hour12: false });
}

function clearError() {
  fs.writeFileSync(ERROR_CSS, '');
}

function writeError(msg) {
  const clean = msg.replace(/\u001b\[[0-9;]*m/g, '');
  const lines = clean.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
  const summary = lines[0] || 'Unknown error';
  const fileLine = lines.find(l => l.match(/\.scss\s+\d/)) || '';
  const escaped = (summary + (fileLine ? ' — ' + fileLine : ''))
    .replace(/\\/g, '/')
    .replace(/"/g, "'");

  fs.writeFileSync(ERROR_CSS,
`body::before {
  content: "\\26A0  SASS ERROR:  ${escaped}";
  display: flex;
  align-items: center;
  justify-content: center;
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 2147483647;
  box-sizing: border-box;
  margin: 0;
  padding: 32px;
  background: #0e0e12;
  color: #ff6b6b;
  font: 15px/1.6 Consolas, 'Fira Code', monospace;
  text-align: center;
  white-space: pre-wrap;
  word-break: break-word;
}
body::after {
  content: "Corrige el error y guarda";
  position: fixed;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2147483647;
  padding: 8px 20px;
  background: rgba(255,255,255,.06);
  border-radius: 20px;
  color: #555;
  font: 11px/1 'Inter', sans-serif;
  letter-spacing: 0.3px;
  white-space: nowrap;
}
`);
}

// ── Compile ──────────────────────────────────────────────
function compile(entry) {
  const t0 = Date.now();
  try {
    const result = sass.compile(entry.input, {
      style: IS_BUILD ? 'compressed' : 'expanded',
      sourceMap: !IS_BUILD,
      sourceMapIncludeSources: !IS_BUILD,
    });

    let css = result.css;
    if (!IS_BUILD && result.sourceMap) {
      const mapJson = JSON.stringify(result.sourceMap);
      const b64 = Buffer.from(mapJson).toString('base64');
      css += `\n/*# sourceMappingURL=data:application/json;base64,${b64} */\n`;
    }

    if (entry.output === STYLE_CSS && themeHeader) {
      css = themeHeader + css;
    }
    fs.writeFileSync(entry.output, css);

    const ms = Date.now() - t0;
    console.log(`  \x1b[32m✓\x1b[0m ${entry.output} \x1b[90m(${ms}ms)\x1b[0m`);
    return true;
  } catch (err) {
    console.error(`  \x1b[31m✗\x1b[0m ${entry.input}`);
    console.error(`    ${err.message}\n`);

    notifier.notify({
      title: 'Sass Error',
      message: err.message.substring(0, 200),
      sound: true,
    });

    writeError(err.message);
    return false;
  }
}

function compileAll() {
  console.log(`\n\x1b[36m[${timestamp()}]\x1b[0m Compiling...`);
  const results = entries.map(compile);
  const allOk = results.every(Boolean);
  if (allOk) clearError();
  return allOk;
}

// ── Build mode ───────────────────────────────────────────
if (IS_BUILD) {
  console.log('\n  \x1b[33m▸\x1b[0m Modo producción (compressed, sin sourcemaps)\n');
  const ok = compileAll();
  console.log(ok ? '\n  \x1b[32m✓\x1b[0m Build completado\n' : '\n  \x1b[31m✗\x1b[0m Build con errores\n');
  process.exit(ok ? 0 : 1);
}

// ── Dev mode: Watch + BrowserSync ────────────────────────
const browserSync = require('browser-sync').create();

compileAll();

browserSync.init({
  proxy: {
    target: PROXY_TARGET,
    proxyOptions: { secure: false },
  },
  port: BS_PORT,
  open: false,
  notify: false,
  injectChanges: false,
  ghostMode: false,
  ui: false,
  logLevel: 'info',
}, function (err, bsInstance) {
  if (err) {
    console.error(`\n  \x1b[31m✗\x1b[0m No se pudo iniciar BrowserSync: ${err.message}`);
    process.exit(1);
  }

  const actualPort = bsInstance.options.get('port');
  const scheme = bsInstance.options.getIn(['urls', 'local']).startsWith('https') ? 'https' : 'http';

  if (actualPort !== BS_PORT) {
    console.error(`\n  \x1b[31m✗\x1b[0m Puerto ${BS_PORT} ocupado (BrowserSync usaría ${actualPort}).`);
    console.error(`    Libera el puerto ${BS_PORT} y vuelve a ejecutar npm run dev.\n`);
    process.exit(1);
  }

  console.log('');
  console.log('  ──────────────────────────────────────────');
  console.log(`  \x1b[32m✓\x1b[0m BrowserSync listo`);
  console.log('');
  console.log(`  \x1b[1m  Abre:\x1b[0m`);
  console.log(`  \x1b[36m  ${scheme}://localhost:${BS_PORT}\x1b[0m`);
  console.log('');
  console.log(`  \x1b[90m  Proxy → ${PROXY_TARGET}\x1b[0m`);
  console.log(`  \x1b[90m  SCSS  → ${entries.map(e => e.input).join(', ')}\x1b[0m`);
  console.log(`  \x1b[90m  CSS   → raíz del tema\x1b[0m`);
  console.log(`  \x1b[90m  Modo  → expanded (desarrollo)\x1b[0m`);
  console.log('  ──────────────────────────────────────────');
  console.log('');
});

const scssDir = path.resolve(SCSS_DIR);
chokidar.watch(scssDir, {
  ignoreInitial: true,
  usePolling: true,
  interval: 300,
})
  .on('change', (file) => {
    if (!file.endsWith('.scss')) return;
    console.log(`  \x1b[90mChanged: ${path.relative('.', file)}\x1b[0m`);
    compileAll();
    browserSync.reload();
  })
  .on('add', (file) => {
    if (!file.endsWith('.scss')) return;
    console.log(`  \x1b[90mAdded: ${path.relative('.', file)}\x1b[0m`);
    compileAll();
    browserSync.reload();
  });
