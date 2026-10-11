#!/usr/bin/env python3
"""
Builds app/Database/Data/prayer-journal-course.json from the bundled
"My Prayer Journal" PDF.

    pdftotext app/Database/Data/prayer-journal.pdf /tmp/pj.txt
    python3 scripts/build_prayer_journal_course.py /tmp/pj.txt app/Database/Data/prayer-journal-course.json

The JSON is committed, so `php spark import:prayer-journal` does not need
poppler or Python. Re-run this only if the source PDF changes. Quiz
questions, summaries and module descriptions live in this file (they are
course-design material written for the LMS, not text from the book).
"""
import html
import json
import re
import sys

LIGATURES = {'ﬁ': 'fi', 'ﬂ': 'fl', 'ﬀ': 'ff', 'ﬃ': 'ffi', 'ﬄ': 'ffl', '\x0c': ''}

# Extraction / typesetting fixes (source text -> corrected text).
FIXES = [
    ('Matthew 6:2533', 'Matthew 6:25-33'),
    ('Proverbs 24:34 reads', 'Proverbs 24:3-4 reads'),
    ('made\nyou\'re his righteousness', 'made you His righteousness'),
    ("made you're his righteousness", 'made you His righteousness'),
    ('worhip', 'worship'),
    ('whilst you are doing you duties', 'whilst you are doing your duties'),
    ('but you a spirit that is having', 'but you are a spirit that is having'),
    ('letschat@ melesswisdom.co.za', 'letschat@timelesswisdom.co.za'),
    ('non- essential', 'non-essential'),
    ("'Worry' those scripture ", "'Worry' those scriptures "),
    ('as well as how actually pray', 'as well as how you actually pray'),
    ('A prayer session as a business meeting', 'See a prayer session as a business meeting'),
    ('(Psalm 139:13) .', '(Psalm 139:13).'),
    ('(Philippians 4:19) .', '(Philippians 4:19).'),
    ('(John 3: 16)', '(John 3:16)'),
    ("Jesus' name.  Amen.", "Jesus' name. Amen."),
]

NOISE = re.compile(r'^\s*(\d{1,3}|[ivxlc]+|Date:|/|/ 20_+|M T W T F S S|Ý|Ÿ|Notes)\s*$')
WEEKDAYS = 'M T W T F S S'


def load_lines(path):
    text = open(path, encoding='utf-8').read()
    for k, v in LIGATURES.items():
        text = text.replace(k, v)
    out = []
    for line in text.split('\n'):
        # A few devotion titles share a line with the weekday row.
        if WEEKDAYS in line and line.strip() != WEEKDAYS:
            out.append(line.replace(WEEKDAYS, '').rstrip())
            out.append(WEEKDAYS)
        else:
            out.append(line)
    return out


def apply_fixes(s):
    for a, b in FIXES:
        s = s.replace(a, b)
    return s


def para_html(lines, allow_headings=True):
    """Re-flows wrapped PDF lines into <p>, <h3> and <ul> blocks."""
    lines = [l.rstrip() for l in lines if not NOISE.match(l)]
    blocks, cur = [], []

    def flush():
        if cur:
            blocks.append(('p', ' '.join(x.strip() for x in cur)))
            cur.clear()

    widths = [len(l) for l in lines if len(l) > 40]
    typical = sorted(widths)[len(widths) // 2] if widths else 70
    for i, raw in enumerate(lines):
        line = raw.strip()
        if not line:
            continue
        is_heading = (
            allow_headings and not cur and len(line) < 60 and line[-1] not in '.,;:”"' and
            sum(1 for w in line.split() if w[:1].isupper()) >= max(2, len(line.split()) - 1) and
            not re.search(r'\d', line)
        )
        if line.startswith('Ý '):
            flush()
            blocks.append(('h3', line[2:].strip()))
            continue
        title_like = (
            len(line) < 60 and line[-1] not in '.,;:”"' and not re.search(r'\d', line) and
            sum(1 for w in line.split() if w[:1].isupper()) >= max(3, len(line.split()) - 1)
        )
        if allow_headings and cur and title_like and cur[-1].strip()[-1:] in '.!?”':
            flush()
            blocks.append(('h3', line))
            continue
        if re.match(r'^in prayer, \w+ came out with', line, re.I):
            flush()
            blocks.append(('li', line))
            continue
        if is_heading and i + 1 < len(lines):
            flush()
            blocks.append(('h3', line))
            continue
        cur.append(line)
        if line[-1] in '.!?:”"' and len(line) < typical * 0.82:
            flush()
    flush()

    # A quotation followed by a lone "(Reference)" paragraph becomes a scripture block.
    merged = []
    for kind, text in blocks:
        if kind == 'p' and re.match(r'^\([^)]*\d[^)]*\)$', text.strip()) and merged and merged[-1][0] == 'p' and merged[-1][1].lstrip().startswith('“'):
            merged[-1] = ('scripture', merged[-1][1] + ' ' + text.strip())
            continue
        merged.append((kind, text))
    blocks = merged

    out, in_list = [], False
    for kind, text in blocks:
        text = html.escape(apply_fixes(re.sub(r'\s+', ' ', text)), quote=False)
        text = text.replace(' ...', '…').replace('...', '…')
        if kind == 'li':
            if not in_list:
                out.append('<ul>')
                in_list = True
            out.append('<li>' + text + '</li>')
            continue
        if in_list:
            out.append('</ul>')
            in_list = False
        if kind == 'scripture':
            out.append('<div class="scripture">%s</div>' % text)
            continue
        out.append('<%s>%s</%s>' % (kind, text, kind))
    if in_list:
        out.append('</ul>')
    return '\n'.join(out)


def section(lines, start_pat, end_pat, after=0):
    s = next(i for i, l in enumerate(lines) if i >= after and re.match(start_pat, l.strip()))
    e = next(i for i, l in enumerate(lines) if i > s and re.match(end_pat, l.strip()))
    return s, e


def split_title(head):
    m = re.match(r'^(.*?)\s*\((.+)\)\s*$', head)
    if not m:
        return head.strip(), None
    title, ref = m.group(1).strip(), m.group(2).strip()
    if ref.lower().startswith('part'):
        return '%s (%s)' % (title, ref), None
    return title, ref


def build(path):
    L = load_lines(path)
    secret_idx = [i for i, l in enumerate(L) if re.match(r'^Prayer Secret No\. \d$', l.strip()) and i > 300]
    salvation = max(i for i, l in enumerate(L) if l.strip() == 'Prayer Of Salvation')

    # Front matter
    h_s, h_e = section(L, r'^How To Use This Prayer Journal$', r'^PREAMBLE$', after=110)
    how_to = para_html(L[h_s + 1:h_e])
    p_s, p_e = section(L, r'^PREAMBLE$', r'^Introduction To Prayer$', after=h_e - 1)
    preamble = preamble_html(L[p_s + 1:p_e])
    i_s = p_e
    intro = para_html(L[i_s + 1:secret_idx[0]])

    secrets = []
    for k, si in enumerate(secret_idx):
        end = secret_idx[k + 1] if k + 1 < len(secret_idx) else salvation
        seg = L[si:end]
        num = int(seg[0].split()[-1])
        title = seg[1].strip()
        bi = next(i for i, l in enumerate(seg) if l.startswith('This block of your prayer journey'))
        teaching = para_html(seg[2:bi])
        devotions, prev_end, last_ref = [], bi, None
        prompts = [i for i, l in enumerate(seg) if l.strip() == 'My Prayer Points and Supporting Scriptures']
        for pi in prompts:
            mi = max(i for i in range(prev_end, pi) if seg[i].strip() == WEEKDAYS)
            head = [l.strip() for l in seg[prev_end:mi] if l.strip() and not NOISE.match(l)]
            marker = 'continue to make your wants known to God (Philippians 4:6)'
            if marker in head:
                head = head[len(head) - head[::-1].index(marker):]
            if prev_end == bi:
                head = head[-2:] if head and head[-1].startswith('(') else head[-1:]
            t, ref = split_title(' '.join(head))
            if ref is None and '(Part' in t:
                ref = last_ref
            last_ref = ref or last_ref
            body = para_html(seg[mi + 1:pi], allow_headings=False)
            devotions.append({'title': apply_fixes(t), 'reference': ref, 'html': body})
            prev_end = pi
        secrets.append({'num': num, 'title': title, 'teaching': teaching, 'devotions': devotions})

    salv_end = next(i for i, l in enumerate(L) if i > salvation and l.strip() == 'Notes')
    salvation_html = salvation_prayer_html(L[salvation + 1:salv_end])
    return how_to, preamble, intro, secrets, salvation_html


def preamble_html(lines):
    out = ['<p><strong>Whatever You Do, Seek Wisdom</strong> (Proverbs 8)</p>']
    cur = None
    for raw in lines:
        line = raw.strip()
        if not line or NOISE.match(line) or line.startswith('Whatever You Do') or line == '(Proverbs 8)':
            continue
        if line.startswith('I, WISDOM'):
            if cur:
                out.append('<p>%s</p>' % cur)
                cur = None
            label = line.replace('I, WISDOM – ', '').replace('I, WISDOM, ', '').title()
            if line.startswith('I, WISDOM, AM'):
                out.append('<div class="callout"><strong>I, Wisdom, am a blessing from God.</strong></div>')
                continue
            out.append('<h3>%s</h3>' % html.escape('I, Wisdom — ' + label))
            continue
        m = re.match(r'^(\d{1,2}) (.*)$', line)
        if m:
            if cur:
                out.append('<p>%s</p>' % cur)
            cur = '<sup>%s</sup> %s' % (m.group(1), html.escape(m.group(2), quote=False))
        elif cur is not None:
            cur += ' ' + html.escape(line, quote=False)
    if cur:
        out.append('<p>%s</p>' % cur)
    return '\n'.join(out)


def salvation_prayer_html(lines):
    text = ' '.join(l.strip() for l in lines if l.strip() and not NOISE.match(l))
    text = apply_fixes(re.sub(r'\s+', ' ', text))
    a, rest = text.split('Heavenly Father,', 1)
    prayer, after = rest.split('Amen.', 1)
    return '\n'.join([
        '<p>%s</p>' % html.escape(a.strip(), quote=False),
        '<div class="scripture">Heavenly Father,%s Amen.</div>' % html.escape(prayer, quote=False),
        '<p>%s</p>' % html.escape(after.strip(), quote=False),
    ])


SECRET_META = {
    1: {
        'summary': 'Unlearn religious habits about prayer and rediscover a God who answers in real time — and your identity as His child.',
        'quiz': [
            {'type': 'single', 'question': 'In Matthew 6:5,7 Jesus warned against two religious habits in prayer. Which ones?',
             'options': ['Praying to be seen by people, and heaping up empty repeated phrases', 'Praying in private, and praying briefly', 'Fasting, and giving to the poor', 'Praying in tongues, and singing'], 'correct': [0],
             'explanation': 'Jesus pointed out hypocrites who pray to be seen and Gentiles who think they are heard for their much speaking.'},
            {'type': 'true_false', 'question': 'The journal teaches that God normally takes a long time to answer prayer.', 'correct': [1],
             'explanation': 'The ideal is that petition and answer interact in real time — the idea of a slow God comes from a religious attitude.'},
            {'type': 'single', 'question': 'According to "No Longer A Sinner", what is your title now?',
             'options': ['Child of God', 'A sinner saved by grace', 'A servant without rights', 'A member of a religion'], 'correct': [0],
             'explanation': '1 John 3:1 — you are called a child of God; you cannot be both at the same time.'},
            {'type': 'single', 'question': 'In "First Things First", the Word of God is described as…',
             'options': ['The law that governs the realm of the spirit', 'A book of good advice', 'A record of history only', 'Something reserved for pastors'], 'correct': [0]},
            {'type': 'multiple', 'question': 'Which attitudes does this block encourage when you approach God? (Select all that apply.)',
             'options': ['Childlike faith', 'Boldness at the throne of grace', 'A sense of guilt and condemnation', 'Ritual repetition'], 'correct': [0, 1],
             'explanation': 'Matthew 18:3 and Hebrews 4:16.'},
            {'type': 'short_text', 'question': 'Complete Hosea 4:6 as quoted: "God\'s people are destroyed because of the lack of ____."', 'correct_text': 'knowledge|knowledge of the word of god'},
        ],
    },
    2: {
        'summary': 'Prayer is not an activity you leave behind in the closet — your words, faith and life must agree with what you pray.',
        'quiz': [
            {'type': 'single', 'question': 'Why does separating prayer from daily life lead to defeat and unanswered prayer?',
             'options': ['What you say outside the prayer closet contradicts what you prayed', 'Because you prayed too little', 'Because God only listens in church', 'Because prayer must always be loud'], 'correct': [0],
             'explanation': 'Words govern reality — Mark 11:23-24.'},
            {'type': 'single', 'question': 'According to James 4:1-3 as taught here, why do some people fail to receive?',
             'options': ['They pray from lustful desires not based on the Word', 'They do not fast long enough', 'They pray in the wrong posture', 'They pray too early in the day'], 'correct': [0]},
            {'type': 'true_false', 'question': 'The Holy Spirit is described as a gentle Spirit who becomes involved in your life when He is invited.', 'correct': [0]},
            {'type': 'single', 'question': 'Who does the journal call your "prayer assistant" (Romans 8:26-27)?',
             'options': ['The Holy Spirit', 'Your pastor', 'Your prayer partner', 'An angel'], 'correct': [0]},
            {'type': 'multiple', 'question': 'Which of these are devotion themes in this block?',
             'options': ['Watch Your Mouth', 'Let Love Dominate', 'Synchronise Your Life', 'Pray Only In A Crisis'], 'correct': [0, 1, 2]},
            {'type': 'short_text', 'question': 'Matthew 6:33: "But seek first his ____ and his righteousness…" (one word)', 'correct_text': 'kingdom'},
        ],
    },
    3: {
        'summary': 'If you can worry, you can meditate. Learn to fix your thought life on God\'s word until it shapes what you believe and say.',
        'quiz': [
            {'type': 'single', 'question': 'How does the author describe worry?',
             'options': ['Meditation on the lies of satan', 'A harmless habit', 'A sign of deep faith', 'A gift for intercessors'], 'correct': [0]},
            {'type': 'single', 'question': 'How long does the author suggest a beginner start meditating?',
             'options': ['10 or 15 minutes for a couple of days', 'Two hours a day from the start', 'A whole day each week', 'Only during a fast'], 'correct': [0]},
            {'type': 'true_false', 'question': 'It is safe to practise meditation without constantly studying the word of God.', 'correct': [1],
             'explanation': 'You must judge whatever you hear with the word of the living God.'},
            {'type': 'single', 'question': 'Which verse describes the goal of meditation in this block?',
             'options': ['Joshua 1:8', 'John 11:35', 'Genesis 1:1', 'Revelation 22:21'], 'correct': [0]},
            {'type': 'single', 'question': 'Complete the chain: what you believe becomes…',
             'options': ['What you speak, the seed you sow, and the harvest you reap', 'What other people think of you', 'Irrelevant once you pray', 'Something only God controls'], 'correct': [0]},
            {'type': 'short_text', 'question': 'Psalm 116:10: "I believed, therefore have I ____."', 'correct_text': 'spoken'},
        ],
    },
    4: {
        'summary': 'Treat prayer like a business meeting with God about His kingdom — and discover the joy of praying for others.',
        'quiz': [
            {'type': 'single', 'question': 'How old was Jesus when He spoke of being about His Father\'s business (Luke 2:49)?',
             'options': ['Twelve', 'Thirty', 'Eight', 'Eighteen'], 'correct': [0]},
            {'type': 'single', 'question': 'The author compares a prayer session to…',
             'options': ['A business meeting with God about kingdom matters', 'A shopping list', 'A complaint session', 'A school exam'], 'correct': [0]},
            {'type': 'single', 'question': 'What is described as the highest form of prayer?',
             'options': ['Prayer offered on behalf of others', 'Prayer for personal luxuries', 'Prayer said in a loud voice', 'Prayer read from a book'], 'correct': [0]},
            {'type': 'true_false', 'question': 'John 14:13-14 is mainly about acquiring personal possessions.', 'correct': [1],
             'explanation': 'In context Jesus was speaking about the advancement of the kingdom of God.'},
            {'type': 'single', 'question': 'Why does God provide money, houses and cars, according to this teaching?',
             'options': ['So you can be a blessing and meet the needs of others', 'So you can compete with others', 'As a reward for long prayers', 'So you never need to pray again'], 'correct': [0]},
        ],
    },
    5: {
        'summary': 'Pray from covenant: every promise is already "Yes" and "Amen" in Christ, and you are a joint-heir of all that belongs to God.',
        'quiz': [
            {'type': 'single', 'question': 'Who is the High Priest of our confession (Hebrews 3:1)?',
             'options': ['Jesus', 'Moses', 'Aaron', 'Melchizedek'], 'correct': [0]},
            {'type': 'single', 'question': 'In Genesis 14:19, whom did Melchizedek call "possessor of heaven and earth"?',
             'options': ['Abram', 'Lot', 'Isaac', 'The king of Sodom'], 'correct': [0]},
            {'type': 'single', 'question': 'What do we have that God values and can use to relate to the lost world?',
             'options': ['Our physical bodies', 'Our church buildings', 'Our money', 'Our titles'], 'correct': [0],
             'explanation': 'Romans 12:1 — offer your bodies as a living sacrifice.'},
            {'type': 'true_false', 'question': 'According to 2 Corinthians 1:20, all God\'s promises are "Yes" and "Amen" in Christ.', 'correct': [0]},
            {'type': 'single', 'question': 'Romans 8:17 makes everyone who comes to Jesus…',
             'options': ['A joint-heir with Christ', 'A distant servant only', 'A guest in God\'s house', 'Responsible for earning salvation'], 'correct': [0]},
            {'type': 'short_text', 'question': 'Romans 12:1 urges us to offer our bodies as a living ____.', 'correct_text': 'sacrifice'},
        ],
    },
    6: {
        'summary': 'Fasting changes you, not God. Learn its true place alongside faith, and how to fight wisely in spiritual warfare.',
        'quiz': [
            {'type': 'single', 'question': 'According to this teaching, what does fasting change?',
             'options': ['You — it positions you to receive from God', 'God\'s mind about your request', 'How quickly God answers', 'How serious God thinks you are'], 'correct': [0]},
            {'type': 'true_false', 'question': 'We receive answers to prayer by faith in God\'s promises, not because we fasted.', 'correct': [0]},
            {'type': 'single', 'question': 'Fasting without time in the Word and prayer is no different from…',
             'options': ['A diet program', 'A church service', 'A Bible study', 'A pilgrimage'], 'correct': [0]},
            {'type': 'single', 'question': 'Which chapter of Isaiah shows the wrong and the right way to fast?',
             'options': ['Isaiah 58', 'Isaiah 53', 'Isaiah 6', 'Isaiah 40'], 'correct': [0]},
            {'type': 'multiple', 'question': 'What does fasting develop in your spirit? (Select all that apply.)',
             'options': ['Sensitivity to the Holy Spirit', 'Spiritual agility and strength', 'Leverage to twist God\'s arm', 'Proof to God that you are serious'], 'correct': [0, 1]},
        ],
    },
}


def main():
    src, dest = sys.argv[1], sys.argv[2]
    how_to, preamble, intro, secrets, salvation = build(src)
    for s in secrets:
        s.update(SECRET_META[s['num']])
    data = {
        'source': 'My Prayer Journal: The Believer\'s Prayer Companion — Nomvula Rakolote (Vutlhari Tlakula Publications, 2021, 2024). ISBN 978-0-620-91489-5.',
        'how_to': how_to,
        'preamble': preamble,
        'introduction': intro,
        'secrets': secrets,
        'salvation': salvation,
    }
    with open(dest, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, indent=1)
    print('secrets:', len(secrets), 'devotions:', sum(len(s['devotions']) for s in secrets))


if __name__ == '__main__':
    main()
