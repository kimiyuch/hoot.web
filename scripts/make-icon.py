"""Draws the Hoot icon: a grey-lavender owl with amber eyes perched on a film clapperboard (like croc's) with the
kimiyu bear on the slate, on a late-night plum square.

The drawing lives in artwork/parts (owl, clapperboard, feet). This writes the SVGs to artwork/, renders the PNGs
with headless Chrome and copies what the site uses to public/. Needs ImageMagick (magick) for the small sizes.
Usage: python3 scripts/make-icon.py
"""
import pathlib
import shutil
import subprocess
import tempfile

root = pathlib.Path(__file__).resolve().parent.parent
parts = root / 'artwork' / 'parts'
owl = (parts / 'owl.part').read_text()
feet = (parts / 'feet.part').read_text()
clapper = (parts / 'clapper.part').read_text()
chrome = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'

# The owl's outline, also used to clip its chest and crown markings.
BODY = 'M512 186C630 186 708 250 712 350C716 430 690 490 650 530H374C334 490 308 430 312 350C316 250 394 186 512 186Z'


def flat(id, top, bottom=None, user_space=None):
    space = f' gradientUnits="userSpaceOnUse" x1="0" y1="{user_space[0]}" x2="0" y2="{user_space[1]}"' if user_space else ' x1="0" y1="0" x2="0" y2="1"'
    return f'<linearGradient id="{id}"{space}><stop offset="0" stop-color="{top}"/><stop offset="1" stop-color="{bottom or top}"/></linearGradient>'


defs = f'''
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9a6a9a"/><stop offset=".55" stop-color="#5a3560"/><stop offset="1" stop-color="#2a1631"/></linearGradient>
    <linearGradient id="sheen" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".18"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
    {flat('body', '#a9a2b6', '#76708a', (190, 530))}
    {flat('wing', '#837c96', '#5a546b', (340, 524))}
    {flat('tuft', '#7f7891', '#5b556c')}
    {flat('belly', '#e4dfe9', '#c9c2d3')}
    {flat('disc', '#f2eff5', '#d8d2e0')}
    {flat('foot', '#cfc8d8', '#aaa2b8')}
    {flat('rim', '#5b556c')}
    {flat('mark', '#5e5870')}
    <radialGradient id="iris" cx=".45" cy=".4" r=".62"><stop offset="0" stop-color="#ffd86e"/><stop offset=".75" stop-color="#f0a52c"/><stop offset="1" stop-color="#c9761a"/></radialGradient>
    <radialGradient id="pupil" cx=".42" cy=".38" r=".7"><stop offset="0" stop-color="#3b2a2f"/><stop offset=".7" stop-color="#120a0c"/></radialGradient>
    {flat('beak', '#6a5e5a', '#3a302e')}
    {flat('slate', '#45454d', '#2a2a30')}
    <radialGradient id="pin" cx=".4" cy=".35" r=".7"><stop offset="0" stop-color="#d8d8de"/><stop offset="1" stop-color="#8a8a94"/></radialGradient>
    <clipPath id="bodyClip"><path d="{BODY}"/></clipPath>
    <clipPath id="stickClip"><rect x="296" y="508" width="430" height="54" rx="12"/></clipPath>
    <clipPath id="squircle"><rect x="100" y="100" width="824" height="824" rx="185"/></clipPath>
    <filter id="soft" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#12061a" flood-opacity=".3"/></filter>
    <filter id="drop" x="-20%" y="-20%" width="140%" height="160%"><feDropShadow dx="0" dy="14" stdDeviation="18" flood-color="#12061a" flood-opacity=".4"/></filter>
    <filter id="icon" x="-10%" y="-10%" width="120%" height="125%"><feDropShadow dx="0" dy="10" stdDeviation="12" flood-color="#000" flood-opacity=".3"/></filter>'''

background = '<rect width="1024" height="1024" fill="url(#bg)"/><rect width="1024" height="560" fill="url(#sheen)"/>'
# The owl, the clapperboard in front of it, then its feet on the clapper.
art = owl + clapper + feet


def svg(view_box, body):
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="{view_box}">\n  <defs>{defs}\n  </defs>\n{body}</svg>\n'


files = {
    # Rounded square on transparent with a shadow, on the macOS icon grid like the siblings': app icon, favicon, mails.
    'icon': (svg('0 0 1024 1024', f'''  <rect x="100" y="100" width="824" height="824" rx="185" fill="#5a3560" filter="url(#icon)"/>
  <g clip-path="url(#squircle)"><g transform="translate(100 100) scale(0.8047)">{background}
{art}  </g></g>
'''), 1024),
    # Full-bleed square for the apple-touch-icon (iOS rounds the corners itself).
    'icon-square': (svg('0 0 1024 1024', background + '\n' + art), 1024),
    # The owl on its clapperboard without a background.
    'mascot': (svg('150 100 724 820', art), 800),
    # Just the owl, for the header, the log-in card and the landing page.
    'head': (svg('292 120 440 440', owl), 440),
}

with tempfile.TemporaryDirectory(prefix='hoot-icon-') as work:
    for name, (source, size) in files.items():
        (root / 'artwork' / f'{name}.svg').write_text(source)
        page = pathlib.Path(work) / 'page.html'
        page.write_text(f'<style>html,body{{margin:0;background:transparent}}svg{{display:block;width:{size}px;height:{size}px}}</style>{source}')
        subprocess.run([chrome, '--headless', '--disable-gpu', '--hide-scrollbars', '--force-device-scale-factor=1',
                        f'--window-size={size},{size}', '--default-background-color=00000000',
                        f'--user-data-dir={work}/profile', f'--screenshot={root}/artwork/{name}.png', page.as_uri()],
                       check=True, capture_output=True)
        print(f'artwork/{name}.png')

art_dir, images = root / 'artwork', root / 'public' / 'images'
shutil.copy(art_dir / 'icon.png', images / 'hoot.png')
shutil.copy(art_dir / 'head.svg', images / 'hoot-head.svg')
shutil.copy(art_dir / 'icon-square.png', root / 'public' / 'apple-touch-icon.png')
subprocess.run(['magick', art_dir / 'icon.png', '-resize', '64x64', images / 'favicon.png'], check=True)
# Mail clients don't show SVG, so the mails get a small PNG.
subprocess.run(['magick', art_dir / 'icon.png', '-resize', '112x112', images / 'mail-icon.png'], check=True)
print('public/images/hoot.png, hoot-head.svg, favicon.png, mail-icon.png, public/apple-touch-icon.png')
