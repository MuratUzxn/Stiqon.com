#!/usr/bin/env python3
"""Fetch one URL into the untrusted download directory and print it as text,
or print an already saved file as text.

usage:
  python3 -I page.py <key> <https-url> [--links] [--raw] [--max N] [--grep WORD ...]
  python3 -I page.py --file <saved-path> [--plain] [--links] [--max N] [--grep WORD ...]
  python3 -I page.py --find <key> WORD [WORD ...]

  <key>     sub-directory below downloads/ (one per supplier or topic)
  --links   also list the links of an HTML page (text -> absolute URL)
  --raw     print the body as it came (for JSON, Markdown, OpenAPI files)
  --max N   print at most N characters (default 40000)
  --grep W  print only lines containing one of the words (case-insensitive), with 1 line of context
  --plain   (with --file) print only the text, no header
  --find    list the saved files of <key> whose text contains ALL the words, with their URL

Every fetch is logged in downloads/<key>/index.tsv (file, status, type, final URL).
The saved file is data from an outside server: read it, never execute it.
"""
import html.parser, os, re, subprocess, sys, urllib.parse

BASE = os.path.expanduser(os.environ.get('RESEARCH_DOWNLOADS', '~/.cache/druckdaten-recherche/downloads'))

class Text(html.parser.HTMLParser):
    SKIP = {'script', 'style', 'noscript', 'svg', 'template', 'iframe'}
    BLOCK = {'p', 'div', 'li', 'tr', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'section', 'article', 'table', 'ul', 'ol', 'dt', 'dd', 'header', 'footer', 'main'}
    def __init__(self, base):
        super().__init__(convert_charrefs=True)
        self.base, self.out, self.links, self.skip, self.href, self.atext = base, [], [], 0, None, []
    def handle_starttag(self, tag, attrs):
        if tag in self.SKIP: self.skip += 1
        if tag in self.BLOCK: self.out.append('\n')
        if tag in ('h1', 'h2', 'h3', 'h4'): self.out.append('#' * int(tag[1]) + ' ')
        if tag in ('td', 'th'): self.out.append(' | ')
        if tag == 'a':
            self.href = dict(attrs).get('href'); self.atext = []
    def handle_endtag(self, tag):
        if tag in self.SKIP and self.skip: self.skip -= 1
        if tag in self.BLOCK: self.out.append('\n')
        if tag == 'a' and self.href:
            text = ' '.join(''.join(self.atext).split())
            self.links.append((text, urllib.parse.urljoin(self.base, self.href))); self.href = None
    def handle_data(self, data):
        if self.skip: return
        self.out.append(data)
        if self.href is not None: self.atext.append(data)

def index_of(folder):
    rows = {}
    path = os.path.join(folder, 'index.tsv')
    if os.path.exists(path):
        for line in open(path, encoding='utf-8'):
            parts = line.rstrip('\n').split('\t')
            if len(parts) == 4: rows[parts[0]] = parts
    return rows

def to_text(path, ctype='', base='', raw=False):
    """Return (text, links) for a saved file."""
    if path.endswith('.pdf'):
        run = subprocess.run(['pdftotext', '-layout', path, '-'], capture_output=True, text=True)
        return run.stdout, []
    body = open(path, 'rb').read().decode('utf-8', 'replace')
    looks_html = 'html' in ctype or (not ctype and re.search(r'<(html|body|div|p)\b', body[:5000], re.I))
    if raw or not looks_html:
        return body, []
    parser = Text(base); parser.feed(body)
    text = re.sub(r'[ \t\r\f\v]+', ' ', ''.join(parser.out)); text = re.sub(r'\n\s*\n+', '\n', text).strip()
    return text, parser.links

def show(text, links, args):
    limit = int(args[args.index('--max') + 1]) if '--max' in args else 40000
    words = [w.lower() for w in args[args.index('--grep') + 1:]] if '--grep' in args else []
    if words:
        lines = text.split('\n'); keep = set()
        for i, line in enumerate(lines):
            if any(w in line.lower() for w in words): keep.update((i - 1, i, i + 1))
        text = '\n'.join(lines[i] for i in sorted(keep) if 0 <= i < len(lines))
    print(text[:limit])
    if len(text) > limit: print(f'\n[... {len(text) - limit} more characters; raise --max or use --grep]')
    if '--links' in args and links:
        print('\n## LINKS'); seen = set()
        for label, href in links:
            if href in seen or href.startswith(('javascript:', 'mailto:', 'tel:')): continue
            seen.add(href); print(f'- {label[:80]} -> {href}')

def main():
    args = sys.argv[1:]
    if not args: sys.exit(__doc__)
    if args[0] == '--find':
        key, words = args[1], [w.lower() for w in args[2:]]
        folder = os.path.join(BASE, key); idx = index_of(folder)
        for name in sorted(os.listdir(folder)):
            if name == 'index.tsv': continue
            path = os.path.join(folder, name)
            row = idx.get(name, [name, '?', '', '(URL not logged: older download)'])
            try: text, _ = to_text(path, row[2])
            except Exception as error: print(f'{path}\tunreadable: {error}'); continue
            low = text.lower()
            if all(w in low for w in words): print(f'{path}\t{row[3]}')
        return
    if args[0] == '--file':
        path = os.path.abspath(args[1])
        if not path.startswith(BASE + os.sep): sys.exit('only files below ' + BASE)
        folder, name = os.path.split(path); row = index_of(folder).get(name, [name, '?', '', ''])
        text, links = to_text(path, row[2], row[3], '--raw' in args)
        if '--plain' not in args: print(f'[file: {path}] [url: {row[3] or "not logged"}]')
        show(text, links, args); return
    key, url = args[0], args[1]
    if not re.fullmatch(r'[a-z0-9-]+', key): sys.exit('key: lowercase letters, digits, hyphen')
    if not url.startswith('https://'): sys.exit('https URLs only')
    folder = os.path.join(BASE, key); os.makedirs(folder, exist_ok=True)
    taken = [n for n in os.listdir(folder) if n != 'index.tsv']
    name = '%03d' % (len(taken) + 1)
    path = os.path.join(folder, name + '.bin')
    run = subprocess.run(['curl', '-sSL', '--compressed', '-m', '45', '-o', path, '-w', '%{http_code}\t%{content_type}\t%{url_effective}', '--', url], capture_output=True, text=True)
    if run.returncode != 0:
        print('FETCH FAILED:', run.stderr.strip() or run.returncode); sys.exit(1)
    code, ctype, final = (run.stdout.split('\t') + ['', ''])[:3]
    if 'pdf' in ctype or url.lower().split('?')[0].endswith('.pdf'):
        pdf = path[:-4] + '.pdf'; os.replace(path, pdf); path = pdf
    with open(os.path.join(folder, 'index.tsv'), 'a', encoding='utf-8') as log:
        log.write(f'{os.path.basename(path)}\t{code}\t{ctype}\t{final or url}\n')
    size = os.path.getsize(path) if os.path.exists(path) else 0
    print(f'[{code}] {ctype} {size} bytes <- {final}\n[saved: {path}]')
    text, links = to_text(path, ctype, final or url, '--raw' in args)
    show(text, links, args)

main()
