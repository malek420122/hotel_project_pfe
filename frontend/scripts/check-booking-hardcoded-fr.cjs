const fs = require('node:fs')
const path = require('node:path')

const targetDir = path.join(process.cwd(), 'src', 'pages', 'dashboard', 'client')
const blocked = /Récapitulatif|nuit\(s\)|Confirmer et/u

function walk(dir, acc = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const fullPath = path.join(dir, entry.name)
    if (entry.isDirectory()) walk(fullPath, acc)
    else if (entry.isFile() && entry.name.endsWith('.vue')) acc.push(fullPath)
  }
  return acc
}

const offenders = []
for (const file of walk(targetDir)) {
  const content = fs.readFileSync(file, 'utf8')
  const lines = content.split(/\r?\n/)
  lines.forEach((line, index) => {
    if (blocked.test(line)) {
      offenders.push(`${path.relative(process.cwd(), file)}:${index + 1}: ${line.trim()}`)
    }
  })
}

if (offenders.length) {
  console.error('Hardcoded French strings found in booking pages:')
  console.error(offenders.join('\n'))
  process.exit(1)
}

console.log('No blocked hardcoded French strings found in booking pages.')
