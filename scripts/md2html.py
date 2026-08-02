#!/usr/bin/env python3
"""Markdown -> HTML tuned for Google Docs import (tables, headings, inline code)."""
import html
import re
import sys


def inline(text):
    text = html.escape(text, quote=False)
    # `code`
    text = re.sub(r'`([^`]+)`', lambda m: '<code>' + m.group(1) + '</code>', text)
    # **bold**
    text = re.sub(r'\*\*([^*]+)\*\*', lambda m: '<strong>' + m.group(1) + '</strong>', text)
    # *italic* / _italic_
    text = re.sub(r'(?<![\w*])\*([^*\n]+)\*(?![\w*])', lambda m: '<em>' + m.group(1) + '</em>', text)
    return text


def split_row(line):
    line = line.strip()
    if line.startswith('|'):
        line = line[1:]
    if line.endswith('|'):
        line = line[:-1]
    return [c.strip() for c in line.split('|')]


def convert(md):
    lines = md.split('\n')
    out = []
    i = 0
    n = len(lines)
    while i < n:
        line = lines[i]
        stripped = line.strip()

        if not stripped:
            i += 1
            continue

        # horizontal rule
        if re.fullmatch(r'-{3,}', stripped):
            out.append('<hr/>')
            i += 1
            continue

        # heading
        m = re.match(r'^(#{1,6})\s+(.*)$', stripped)
        if m:
            lvl = len(m.group(1))
            out.append('<h%d>%s</h%d>' % (lvl, inline(m.group(2)), lvl))
            i += 1
            continue

        # table: a | row followed by a separator row
        if stripped.startswith('|') and i + 1 < n and re.fullmatch(r'\|[\s:|-]+\|', lines[i + 1].strip()):
            header = split_row(stripped)
            i += 2
            rows = []
            while i < n and lines[i].strip().startswith('|'):
                rows.append(split_row(lines[i].strip()))
                i += 1
            out.append('<table border="1" cellspacing="0" cellpadding="6" '
                       'style="border-collapse:collapse;width:100%">')
            out.append('<thead><tr>' + ''.join(
                '<th style="background:#eef2fa;text-align:left;vertical-align:top">%s</th>' % inline(c)
                for c in header) + '</tr></thead><tbody>')
            for r in rows:
                # pad/trim to the header width so Docs does not mis-render
                r = (r + [''] * len(header))[:len(header)]
                out.append('<tr>' + ''.join(
                    '<td style="vertical-align:top">%s</td>' % inline(c) for c in r) + '</tr>')
            out.append('</tbody></table>')
            continue

        # blockquote
        if stripped.startswith('>'):
            buf = []
            while i < n and lines[i].strip().startswith('>'):
                buf.append(lines[i].strip().lstrip('>').strip())
                i += 1
            out.append('<blockquote style="border-left:3px solid #c9a14a;margin-left:0;'
                       'padding-left:12px;color:#3f4658">%s</blockquote>' % inline(' '.join(buf)))
            continue

        # ordered list
        if re.match(r'^\d+\.\s+', stripped):
            out.append('<ol>')
            while i < n and re.match(r'^\d+\.\s+', lines[i].strip()):
                out.append('<li>%s</li>' % inline(re.sub(r'^\d+\.\s+', '', lines[i].strip())))
                i += 1
            out.append('</ol>')
            continue

        # unordered list
        if re.match(r'^[-*]\s+', stripped):
            out.append('<ul>')
            while i < n and re.match(r'^[-*]\s+', lines[i].strip()):
                out.append('<li>%s</li>' % inline(re.sub(r'^[-*]\s+', '', lines[i].strip())))
                i += 1
            out.append('</ul>')
            continue

        # paragraph
        buf = []
        while i < n and lines[i].strip() and not re.match(r'^(#{1,6}\s|\||>|[-*]\s|\d+\.\s)', lines[i].strip()) \
                and not re.fullmatch(r'-{3,}', lines[i].strip()):
            buf.append(lines[i].strip())
            i += 1
        if buf:
            out.append('<p>%s</p>' % inline(' '.join(buf)))
        else:
            i += 1

    return '\n'.join(out)


src, dst = sys.argv[1], sys.argv[2]
with open(src, encoding='utf-8') as f:
    body = convert(f.read())

doc = """<!DOCTYPE html>
<html><head><meta charset="utf-8">
<title>Mekane Selam Senbet School - Feature &amp; Data Flow Test Matrix</title>
<style>
 body { font-family: Arial, 'Noto Sans Ethiopic', sans-serif; font-size: 10.5pt; line-height: 1.45; color:#141824; }
 h1 { font-size: 20pt; color:#16357e; }
 h2 { font-size: 15pt; color:#16357e; margin-top: 22pt; }
 h3 { font-size: 12pt; color:#16357e; }
 table { border-collapse: collapse; width: 100%; margin: 8pt 0 14pt; }
 th, td { border: 1px solid #c4d0e4; padding: 6px; font-size: 9.5pt; vertical-align: top; }
 th { background: #eef2fa; }
 code { font-family: 'Courier New', monospace; font-size: 9pt; background: #eef2fa; }
 hr { border: none; border-top: 1px solid #c9a14a; margin: 18pt 0; }
</style></head><body>
""" + body + "\n</body></html>\n"

with open(dst, 'w', encoding='utf-8') as f:
    f.write(doc)
print('wrote', dst, len(doc), 'bytes')
