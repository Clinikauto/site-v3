from PIL import Image
from collections import Counter
p = r"E:/site clinikauto/assets/logo.png"
im = Image.open(p).convert('RGBA')
# include opaque pixels
pixels = [px for px in im.getdata() if px[3] > 0]
if not pixels:
    print('NO_OPAQUE_PIXELS')
else:
    # ignore near-white pixels to find background color
    filtered = [(r,g,b) for r,g,b,a in pixels if not (r>240 and g>240 and b>240)]
    if not filtered:
        print('ONLY_WHITE')
    else:
        # show overall palette including whites
        counter_all = Counter((r,g,b) for r,g,b,a in pixels)
        print('Top colors (including white):')
        for i, (col, cnt) in enumerate(counter_all.most_common(12), 1):
            print(i, '#%02x%02x%02x' % col, cnt)
        print('\nTop non-white colors:')
        counter = Counter(filtered)
        for i, (col, cnt) in enumerate(counter.most_common(12), 1):
            print(i, '#%02x%02x%02x' % col, cnt)
