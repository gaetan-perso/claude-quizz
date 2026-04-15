const { execSync } = require('child_process');
const fs = require('fs');
const out_file = 'C:/projets/IA/app_quizz_claude/.claude/tmp/gh_output.txt';

function run(cmd) {
  try {
    return execSync(cmd, { encoding: 'utf8', timeout: 30000 });
  } catch (e) {
    return 'ERROR: ' + e.message + '\nSTDOUT: ' + (e.stdout || '') + '\nSTDERR: ' + (e.stderr || '');
  }
}

const GH = '"C:\\Program Files\\GitHub CLI\\gh.exe"';

let output = '';
output += '=== GH VERSION ===\n';
output += run(`${GH} --version`) + '\n';

output += '=== GH AUTH STATUS ===\n';
output += run(`${GH} auth status`) + '\n';

fs.writeFileSync(out_file, output);
console.log('Done');
