#!/usr/bin/env python3
"""Check that every first-hand claim in a JSON file is backed by a verbatim quote
that really occurs in the saved page it names.

usage: python3 -I quotecheck.py <review-dir> <json-file> [<json-file> ...]

A claim is any JSON object that carries "quote" and "file" (checked), or that says
"evidence": "F" without them (reported as unbacked). Products may carry a list
"quotes": [{"quote": ..., "file": ...}]. For every checked quote the review file
<review-dir>/<json-name>.review.md gets the quote with its surrounding text, so a
reader can judge whether the quote really says what the claim makes of it.
"""
import html, json, os, re, subprocess, sys, unicodedata

TOOLS = os.path.dirname(os.path.abspath(__file__))
BASE = os.path.expanduser(os.environ.get('RESEARCH_DOWNLOADS', '~/.cache/druckdaten-recherche/downloads'))
CACHE = {}

def norm(s):
    s = unicodedata.normalize('NFKC', s).replace('­', '')
    for a, b in (('‘', "'"), ('’', "'"), ('‚', "'"), ('“', '"'), ('”', '"'), ('„', '"'), ('–', '-'), ('—', '-'), (' ', ' ')):
        s = s.replace(a, b)
    return re.sub(r'\s+', ' ', s).strip().casefold()

def text_of(path):
    """Searchable forms of a saved file: the text the helper prints, and the raw body
    (as is and with HTML entities and JSON escapes resolved), because some pages carry
    their data in script blocks that the text view leaves out."""
    if path not in CACHE:
        run = subprocess.run([sys.executable, '-I', os.path.join(TOOLS, 'page.py'), '--file', path, '--plain', '--max', '100000000'], capture_output=True, text=True)
        forms = [run.stdout] if run.returncode == 0 else []
        if not path.endswith('.pdf'):
            body = open(path, 'rb').read().decode('utf-8', 'replace')
            forms += [body, html.unescape(body), html.unescape(body).replace('\\"', '"').replace('\\/', '/')]
        CACHE[path] = forms or None
    return CACHE[path]

def walk(node, trail, found):
    if isinstance(node, dict):
        if 'quote' in node and 'file' in node:
            found.append(('/'.join(trail), node.get('value', node.get('claim', '')), node['quote'], node['file'], node.get('source', '')))
        elif node.get('evidence') == 'F' and 'quotes' not in node:
            found.append(('/'.join(trail), node.get('value', ''), None, None, node.get('source', '')))
        for q in node.get('quotes', []) if isinstance(node.get('quotes'), list) else []:
            if isinstance(q, dict):
                found.append(('/'.join(trail) + '/quotes', node.get('product', ''), q.get('quote'), q.get('file'), node.get('source', '')))
        for k, v in node.items():
            if k != 'quotes': walk(v, trail + [str(k)], found)
    elif isinstance(node, list):
        for i, v in enumerate(node): walk(v, trail + [str(i)], found)

def main():
    review_dir, files = sys.argv[1], sys.argv[2:]
    os.makedirs(review_dir, exist_ok=True)
    total = {'ok': 0, 'missing_quote': 0, 'not_found': 0, 'bad_file': 0}
    for jf in files:
        try: data = json.load(open(jf, encoding='utf-8'))
        except Exception as error: print(f'## {jf}: INVALID JSON: {error}'); continue
        found = []; walk(data, [], found)
        lines = [f'# Review: {os.path.basename(jf)}\n']
        counts = {'ok': 0, 'missing_quote': 0, 'not_found': 0, 'bad_file': 0}
        problems = []
        for trail, value, quote, path, source in found:
            if not quote or not path:
                counts['missing_quote'] += 1; problems.append(f'  UNBACKED  {trail} = {str(value)[:80]}'); continue
            path = os.path.abspath(path)
            if not path.startswith(BASE + os.sep) or not os.path.exists(path):
                counts['bad_file'] += 1; problems.append(f'  BAD FILE  {trail}: {path}'); continue
            text = text_of(path)
            if text is None:
                counts['bad_file'] += 1; problems.append(f'  UNREADABLE {trail}: {path}'); continue
            needle = norm(quote); pos = -1; raw_only = False
            for i, form in enumerate(text):
                hay = norm(form); pos = hay.find(needle)
                if pos >= 0: raw_only = i > 0; break
            if pos < 0:
                counts['not_found'] += 1; problems.append(f'  NOT FOUND {trail}: "{quote[:100]}" in {os.path.basename(path)}')
                lines.append(f'## NOT FOUND - {trail}\nclaimed value: {value}\nquote: {quote}\nfile: {path}\n')
                continue
            counts['ok'] += 1
            ctx = re.sub(r'<[^>]{0,400}>', ' ', hay[max(0, pos - 400): pos + len(needle) + 400])
            lines.append(f'## {trail}\nclaimed value: {value}\nsource: {source}\nfile: {path}{" (found in raw page data)" if raw_only else ""}\ncontext: ...{ctx}...\n')
        for k in total: total[k] += counts[k]
        print(f'## {os.path.basename(jf)}: {counts["ok"]} ok, {counts["not_found"]} not found, {counts["missing_quote"]} first-hand claims without quote, {counts["bad_file"]} bad file')
        for p in problems: print(p)
        open(os.path.join(review_dir, os.path.basename(jf) + '.review.md'), 'w', encoding='utf-8').write('\n'.join(lines))
    print(f'\nTOTAL: {total}')

main()
