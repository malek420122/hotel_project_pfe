const fs = require('fs');
const path = require('path');

const walk = function(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(function(file) {
        file = dir + '/' + file;
        const stat = fs.statSync(file);
        if (stat && stat.isDirectory()) { 
            results = results.concat(walk(file));
        } else { 
            if (file.endsWith('.vue')) results.push(file);
        }
    });
    return results;
};

const vueFiles = walk(path.join(__dirname, 'src/pages')).concat(walk(path.join(__dirname, 'src/components')));

const strings = new Set();
// A simple regex to catch text between > and <
const regex = />([^<{}]+)</g;
const attrRegex = /(placeholder|title)="([^"]+)"/g;

vueFiles.forEach(file => {
    const content = fs.readFileSync(file, 'utf8');
    let match;
    while ((match = regex.exec(content)) !== null) {
        let text = match[1].trim();
        // ignore short/empty strings, numbers, common symbols
        if (text && text.length > 1 && !/^[0-9\s€$%.,:!?\-+]+$/.test(text)) {
             // filter out things that look like vue bindings or generic
             if (!text.includes('{{') && !text.includes('}}')) {
                 strings.add(text);
             }
        }
    }
    while ((match = attrRegex.exec(content)) !== null) {
        let text = match[2].trim();
        if (text && text.length > 1 && !/^[0-9\s.,]+$/.test(text)) {
            strings.add(text);
        }
    }
});

const output = Array.from(strings).slice(0, 150); // grab the first 150 to not overwhelm stdout
console.log(JSON.stringify(output, null, 2));
